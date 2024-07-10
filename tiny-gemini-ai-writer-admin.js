jQuery(document).ready(function($) {
    window.generateTinyGeminiContent = function() {
        var prompt = $('#tiny-gemini-ai-prompt').val();
        
        // Show the generating message
        $('#tiny-gemini-ai-output').html('<p>Generating content. Please wait a few seconds.</p>');
        
        $.ajax({
            url: tiny_gemini_ai_writer.ajax_url,
            method: 'POST',
            data: {
                action: 'generate_tiny_gemini_content',
                prompt: prompt
            },
            success: function(response) {
                if (response.success) {
                    $('#tiny-gemini-ai-output').html('<p>Post created successfully! <a href="' + response.data.edit_link + '">Edit Post</a></p>');
                } else {
                    $('#tiny-gemini-ai-output').html('<p>' + response.data + '</p>');
                }
            },
            error: function() {
                $('#tiny-gemini-ai-output').html('<p>An error occurred.</p>');
            }
        });
    };
});
