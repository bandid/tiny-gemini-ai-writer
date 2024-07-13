<?php
// If uninstall.php is not called by WordPress, die.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Delete the stored API keys from the database
delete_option('gemini_ai_api_key');
