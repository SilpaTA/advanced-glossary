jQuery(document).ready(function($) {
    // Add custom icon styling for TinyMCE button
    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function(e) {
            // Add custom styles when editor is added
        });
    }
    
    // Helper function to add glossary shortcode (if needed in future)
    window.glossaryHelper = {
        insertTerm: function(termId, displayText) {
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                // Escape values to prevent XSS
                var escapedTermId = String(termId).replace(/[^0-9]/g, '');
                var escapedDisplayText = $('<div>').text(displayText || '').html();
                
                tinymce.activeEditor.insertContent(
                    '<span class="advgls-term" data-term-id="' + escapedTermId + '">' + 
                    escapedDisplayText + 
                    '</span>'
                );
            }
        }
    };
});