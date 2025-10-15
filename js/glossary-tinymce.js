(function() {
    tinymce.PluginManager.add('glossary_button', function(editor, url) {

        // Add button to toolbar
        editor.addButton('glossary_button', {
            title: 'Insert Glossary Term',
            icon: 'icon dashicons-book',
            onclick: function() {
                openGlossaryDialog();
            }
        });

        // Open dialog to select glossary term
        function openGlossaryDialog() {
            // Fetch glossary terms via AJAX
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_glossary_terms',
                    nonce: glossaryAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showTermSelectionDialog(response.data);
                    }
                }
            });
        }

        // Show term selection dialog
        function showTermSelectionDialog(terms) {
            editor.windowManager.open({
                title: 'Insert Glossary Term',
                body: [
                    {
                        type: 'listbox',
                        name: 'termId',
                        label: 'Glossary Term',
                        values: terms.map(function(term) {
                            return { text: term.title, value: term.id };
                        })
                    },
                    {
                        type: 'textbox',
                        name: 'displayText',
                        label: 'Display Text (optional)',
                        placeholder: 'Leave empty to use term title'
                    }
                ],
                onsubmit: function(e) {
                    const termId = e.data.termId;
                    let displayText = e.data.displayText;

                    if (!termId) {
                        alert('Please select a glossary term');
                        return;
                    }

                    // Find the selected term
                    const selectedTerm = terms.find(function(term) {
                        return term.id == termId;
                    });

                    if (!displayText) {
                        displayText = selectedTerm.title;
                    }

                    // Insert the glossary shortcode
                    editor.insertContent('[glossary id="' + termId + '"]' + displayText + '[/glossary]');
                }
            });
        }
    });
})();