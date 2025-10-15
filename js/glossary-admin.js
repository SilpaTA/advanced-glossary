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
                tinymce.activeEditor.insertContent(
                    '<span class="glossary-term" data-term-id="' + termId + '">' + 
                    displayText + 
                    '</span>'
                );
            }
        }
    };
});