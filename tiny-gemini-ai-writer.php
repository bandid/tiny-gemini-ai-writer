<?php
/*
Plugin Name: Tiny Gemini AI Writer
Plugin URI: https://github.com/bandid/tiny-gemini-ai-writer
Description: A simple and lightweight AI article writer plugin for WordPress that uses Google Gemini.
Version: 1.0
Author: Daniel Bandi
Author URI: https://github.com/bandid
Text Domain: tiny-gemini-ai-writer
*/

// Add admin menu
add_action('admin_menu', 'tiny_gemini_ai_writer_menu');

function tiny_gemini_ai_writer_menu() {
    // Calculate an available position dynamically
    $menu_position = calculate_menu_position();
    
    add_menu_page(
        'Tiny Gemini AI Writer',          // Page title
        'Tiny Gemini AI Writer',          // Menu title
        'manage_options',                 // Capability
        'tiny-gemini-ai-writer',          // Menu slug
        'tiny_gemini_ai_writer_page',     // Function
        'dashicons-analytics',            // Icon URL (Dashicons or custom URL)
        $menu_position                    // Position
    );
    add_submenu_page(
        'tiny-gemini-ai-writer',          // Parent slug
        'Settings',                       // Page title
        'Settings',                       // Menu title
        'manage_options',                 // Capability
        'tiny-gemini-ai-writer-settings', // Menu slug
        'tiny_gemini_ai_writer_settings_page' // Function
    );
}

function calculate_menu_position() {
    global $menu;
    $default_position = 26; // Starting position after typical core items and some common plugins
    $occupied_positions = [];

    foreach ($menu as $menu_item) {
        if (isset($menu_item[2])) {
            $occupied_positions[] = $menu_item[2];
        }
    }

    while (in_array($default_position, $occupied_positions)) {
        $default_position++;
    }

    return $default_position;
}

function tiny_gemini_ai_writer_page() {
    ?>
    <div class="wrap">
        <h1>Tiny Gemini AI Writer</h1>
        <form id="tiny-gemini-ai-form">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Prompt</th>
                    <td><input type="text" id="tiny-gemini-ai-prompt" class="regular-text"></td>
                </tr>
            </table>
            <input type="button" class="button-primary" value="Generate Content" onclick="generateTinyGeminiContent()">
        </form>
        <div id="tiny-gemini-ai-output"></div>
    </div>
    <?php
}

function tiny_gemini_ai_writer_settings_page() {
    ?>
    <div class="wrap">
        <h1>Tiny Gemini AI Writer Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('tiny_gemini_ai_writer_settings');
            do_settings_sections('tiny-gemini-ai-writer-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'tiny_gemini_ai_writer_settings');

function tiny_gemini_ai_writer_settings() {
    register_setting('tiny_gemini_ai_writer_settings', 'tiny_gemini_ai_api_key');

    add_settings_section('tiny_gemini_ai_writer_settings_section', 'API Settings', null, 'tiny-gemini-ai-writer-settings');

    add_settings_field('tiny_gemini_ai_api_key', 'API Key', 'tiny_gemini_ai_api_key_render', 'tiny-gemini-ai-writer-settings', 'tiny_gemini_ai_writer_settings_section');
}

function tiny_gemini_ai_api_key_render() {
    $api_key = get_option('tiny_gemini_ai_api_key');
    echo '<input type="text" name="tiny_gemini_ai_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
}

// AJAX handler for generating content
add_action('wp_ajax_generate_tiny_gemini_content', 'generate_tiny_gemini_content');

function generate_tiny_gemini_content() {
    $prompt = sanitize_text_field($_POST['prompt']);
    $api_key = get_option('tiny_gemini_ai_api_key');

    if (!$api_key) {
        wp_send_json_error('API key is not set.');
    }

    $geminiResponse = generate_response_with_gemini($prompt, $api_key);

    if ($geminiResponse) {
        // Create a new draft post with the generated content
        $post_id = wp_insert_post(array(
            'post_title' => 'Generated Content',
            'post_content' => $geminiResponse,
            'post_status' => 'draft',
            'post_type' => 'post'
        ));

        if ($post_id) {
            wp_send_json_success(['message' => 'Post created successfully!', 'edit_link' => get_edit_post_link($post_id)]);
        } else {
            wp_send_json_error('Failed to create post.');
        }
    } else {
        wp_send_json_error('Failed to generate content.');
    }
}

function generate_response_with_gemini($prompt, $api_key) {
    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;

    // Headers for the Gemini API
    $headers = [
        'Content-Type' => 'application/json'
    ];

    // Body for the Gemini API
    $body = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    // Args for the WordPress HTTP API
    $args = [
        'method' => 'POST',
        'headers' => $headers,
        'body' => json_encode($body),
        'timeout' => 120
    ];

    // Send the request
    $response = wp_remote_request($api_url, $args);

    // Extract the body from the response
    $responseBody = wp_remote_retrieve_body($response);

    // Decode the text response body
    $decoded = json_decode($responseBody, true);

    // Extract the text
    if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
        $extractedText = $decoded['candidates'][0]['content']['parts'][0]['text'];
        return $extractedText;
    } else {
        return 'Text not found in response';
    }
}

// Enqueue the JavaScript for AJAX
add_action('admin_enqueue_scripts', 'tiny_gemini_ai_writer_admin_scripts');

function tiny_gemini_ai_writer_admin_scripts() {
    wp_enqueue_script('tiny-gemini-ai-writer-admin', plugin_dir_url(__FILE__) . 'tiny-gemini-ai-writer-admin.js', ['jquery'], null, true);
    wp_localize_script('tiny-gemini-ai-writer-admin', 'tiny_gemini_ai_writer', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
