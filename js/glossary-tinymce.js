(function () {
    tinymce.PluginManager.add('advgls_button', function (editor, url) {

        // Add button for TinyMCE 5+
        if (editor.ui && editor.ui.registry) {
            editor.ui.registry.addButton('advgls_button', {
                icon: 'dashicons-book-alt',
                tooltip: 'Convert to Glossary Term',
                onAction: function () {
                    handleGlossaryAction();
                }
            });
        } else {
            // Fallback for TinyMCE 4
            editor.addButton('advgls_button', {
                icon: 'dashicons-book-alt',
                tooltip: 'Convert to Glossary Term',
                onclick: function () {
                    handleGlossaryAction();
                }
            });
        }

        // Replace shortcode with span in visual mode
        editor.on('BeforeSetContent', function (e) {
            if (e.content && e.content.indexOf('[glossary') !== -1) {
                e.content = e.content.replace(/\[glossary id="(\d+)"\](.*?)\[\/glossary\]/g, function (match, id, text) {
                    return '<span class="advgls-term advgls-highlight" data-term-id="' + id + '" style="color:#2271b1; text-decoration:underline dotted; text-decoration-thickness:2px; cursor:help; position:relative; transition:color 0.2s ease;">' + text + '</span>';
                });
            }
        });

        // Convert span back to shortcode when saving
        editor.on('PostProcess', function (e) {
            if (e.get) {
                e.content = e.content.replace(/<span[^>]*class="[^"]*advgls-term[^"]*"[^>]*data-term-id="(\d+)"[^>]*>(.*?)<\/span>/g, function (match, id, text) {
                    return '[glossary id="' + id + '"]' + text + '[/glossary]';
                });
            }
        });

        // Also convert when switching to text mode
        editor.on('GetContent', function (e) {
            if (e.format === 'raw' || e.format === 'html') {
                e.content = e.content.replace(/<span[^>]*class="[^"]*advgls-term[^"]*"[^>]*data-term-id="(\d+)"[^>]*>(.*?)<\/span>/g, function (match, id, text) {
                    return '[glossary id="' + id + '"]' + text + '[/glossary]';
                });
            }
        });

        function handleGlossaryAction() {
            openGlossaryDialog();
        }

        function openGlossaryDialog() {
            if (typeof jQuery === 'undefined' || typeof ajaxurl === 'undefined') {
                console.error('jQuery or ajaxurl not available');
                return;
            }

            var selectedText = editor.selection.getContent({ format: 'text' }).trim();

            if (!selectedText) {
                alert(glossaryAdmin.select_text || 'Please select some text in the editor to link to a glossary term.');
                return;
            }

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: glossaryAdmin.action || 'advgls_get_glossary_terms',
                    nonce: glossaryAdmin.nonce
                },
                success: function (response) {
                    if (response.success) {
                        showTermSelectionDialog(response.data, selectedText);
                    } else {
                        alert(glossaryAdmin.load_error || 'Failed to load glossary terms');
                    }
                },
                error: function () {
                    alert(glossaryAdmin.error_loading || 'Error loading glossary terms');
                }
            });
        }

        function showTermSelectionDialog(terms, selectedText) {
            if (!terms || terms.length === 0) {
                alert(glossaryAdmin.no_terms || 'No glossary terms available. Please create some glossary terms first.');
                return;
            }

            var suggestedTermId = null;
            var selectedTextLower = (selectedText || '').toLowerCase().trim();

            for (var i = 0; i < terms.length; i++) {
                if (terms[i].title.toLowerCase() === selectedTextLower) {
                    suggestedTermId = terms[i].id;
                    break;
                }
            }

            var isTinyMCE5Plus = editor.windowManager.open && typeof editor.windowManager.open === 'function' &&
                editor.ui && editor.ui.registry;

            if (isTinyMCE5Plus) {
                var dialogConfig = {
                    title: 'Link Text to Glossary Term',
                    body: {
                        type: 'panel',
                        items: [
                            {
                                type: 'htmlpanel',
                                html: '<p style="padding: 10px; background: #e8f4f8; border-left: 3px solid #0073aa; margin-bottom: 15px;">Selected text: <strong>' + selectedText + '</strong></p>'
                            },
                            {
                                type: 'selectbox',
                                name: 'termId',
                                label: 'Choose Glossary Term',
                                items: terms.map(function (term) {
                                    return { text: term.title, value: term.id };
                                })
                            },
                            {
                                type: 'checkbox',
                                name: 'keepText',
                                label: 'Keep selected text as display text',
                                checked: true
                            }
                        ]
                    },
                    buttons: [
                        { type: 'cancel', text: 'Cancel' },
                        { type: 'submit', text: 'Link Term', primary: true }
                    ],
                    onSubmit: function (api) {
                        var data = api.getData();
                        insertGlossaryTerm(data, terms, selectedText);
                        api.close();
                    }
                };

                if (suggestedTermId) {
                    dialogConfig.initialData = {
                        termId: suggestedTermId,
                        keepText: true
                    };
                }

                editor.windowManager.open(dialogConfig);
            } else {
                editor.windowManager.open({
                    title: 'Link Text to Glossary Term',
                    body: [
                        {
                            type: 'container',
                            html: '<p style="padding: 10px; background: #e8f4f8; border-left: 3px solid #0073aa; margin-bottom: 15px;">Selected text: <strong>' + selectedText + '</strong></p>'
                        },
                        {
                            type: 'listbox',
                            name: 'termId',
                            label: 'Choose Glossary Term',
                            values: terms.map(function (term) {
                                return { text: term.title, value: term.id };
                            }),
                            value: suggestedTermId || terms[0].id
                        },
                        {
                            type: 'checkbox',
                            name: 'keepText',
                            label: 'Keep selected text as display text',
                            checked: true
                        }
                    ],
                    onsubmit: function (e) {
                        insertGlossaryTerm(e.data, terms, selectedText);
                    }
                });
            }
        }

        function insertGlossaryTerm(data, terms, selectedText) {
            var termId = data.termId;
            var keepText = data.keepText !== false;

            if (!termId) {
                alert(glossaryAdmin.select_term || 'Please select a glossary term');
                return;
            }

            var selectedTerm = terms.find(function (term) {
                return term.id == termId;
            });

            if (!selectedTerm) {
                alert(glossaryAdmin.term_not_found || 'Selected term not found');
                return;
            }

            var displayText = keepText ? selectedText : selectedTerm.title;
            var shortcode = '[glossary id="' + termId + '"]' + displayText + '[/glossary]';
            editor.selection.setContent(shortcode);
        }
    });
})();