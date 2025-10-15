jQuery(document).ready(function($) {
    // Generate shortcode
    $('#generate_shortcode').on('click', function() {
        var term = $('#glossary_term_select').val();
        var termId = $('#glossary_term_select option:selected').data('id');
        
        if (!term) {
            alert('Please select a glossary term first.');
            return;
        }
        
        // Generate shortcode with term ID for better reliability
        var shortcode = '[glossary id="' + termId + '"]' + term + '[/glossary]';
        $('#shortcode_output').val(shortcode);
        
        // Enable the copy button
        $('#copy_shortcode').prop('disabled', false);
    });
    
    // Reset copy button when term selection changes
    $('#glossary_term_select').on('change', function() {
        $('#shortcode_output').val('');
        $('#copy_shortcode').prop('disabled', true);
    });
    
    // Copy to clipboard
    $('#copy_shortcode').on('click', function() {
        var shortcodeInput = $('#shortcode_output');
        
        if (!shortcodeInput.val()) {
            alert('Please generate a shortcode first.');
            return;
        }
        
        // Select the text
        shortcodeInput.select();
        shortcodeInput[0].setSelectionRange(0, 99999); // For mobile devices
        
        // Copy to clipboard
        try {
            navigator.clipboard.writeText(shortcodeInput.val()).then(function() {
                // Success feedback
                var originalText = $('#copy_shortcode').text();
                $('#copy_shortcode').text('Copied!').addClass('button-primary');
                
                setTimeout(function() {
                    $('#copy_shortcode').text(originalText).removeClass('button-primary');
                }, 2000);
            }).catch(function(err) {
                // Fallback for older browsers
                document.execCommand('copy');
                var originalText = $('#copy_shortcode').text();
                $('#copy_shortcode').text('Copied!').addClass('button-primary');
                
                setTimeout(function() {
                    $('#copy_shortcode').text(originalText).removeClass('button-primary');
                }, 2000);
            });
        } catch (err) {
            // Final fallback
            try {
                shortcodeInput.select();
                document.execCommand('copy');
                var originalText = $('#copy_shortcode').text();
                $('#copy_shortcode').text('Copied!').addClass('button-primary');
                
                setTimeout(function() {
                    $('#copy_shortcode').text(originalText).removeClass('button-primary');
                }, 2000);
            } catch (e) {
                alert('Failed to copy shortcode. Please copy it manually.');
            }
        }
    });
});