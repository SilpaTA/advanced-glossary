(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { SelectControl } = wp.components;
    const { useEffect, useState } = wp.element;
    const { RichText } = wp.blockEditor;

    registerBlockType('advanced-glossary/term', {
        title: 'Glossary Term',
        icon: 'book',
        category: 'text',
        attributes: {
            termId: {
                type: 'number',
                default: 0
            },
            termTitle: {
                type: 'string',
                default: ''
            },
            displayText: {
                type: 'string',
                default: ''
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { termId, termTitle, displayText } = attributes;
            const [terms, setTerms] = useState([]);
            const [loading, setLoading] = useState(true);

            useEffect(() => {
                wp.apiFetch({
                    path: '/wp/v2/glossary?per_page=100&orderby=title&order=asc'
                }).then((fetchedTerms) => {
                    const termOptions = [
                        { label: 'Select a glossary term...', value: 0 },
                        ...fetchedTerms.map(term => ({
                            label: term.title.rendered,
                            value: term.id
                        }))
                    ];
                    setTerms(termOptions);
                    setLoading(false);
                }).catch(() => {
                    setLoading(false);
                });
            }, []);

            const onTermChange = (newTermId) => {
                const selectedTerm = terms.find(t => t.value === parseInt(newTermId));
                setAttributes({
                    termId: parseInt(newTermId),
                    termTitle: selectedTerm ? selectedTerm.label : '',
                    displayText: displayText || (selectedTerm ? selectedTerm.label : '')
                });
            };

            const onDisplayTextChange = (value) => {
                setAttributes({ displayText: value });
            };

            return wp.element.createElement(
                'div',
                { className: 'glossary-block-editor' },
                wp.element.createElement(SelectControl, {
                    label: 'Select Glossary Term',
                    value: termId,
                    options: terms,
                    onChange: onTermChange,
                    disabled: loading
                }),
                termId > 0 && wp.element.createElement(
                    'div',
                    { style: { marginTop: '12px' } },
                    wp.element.createElement('label', {
                        style: { display: 'block', marginBottom: '4px', fontWeight: '600' }
                    }, 'Display Text:'),
                    wp.element.createElement('input', {
                        type: 'text',
                        value: displayText,
                        onChange: (e) => onDisplayTextChange(e.target.value),
                        placeholder: termTitle,
                        style: {
                            width: '100%',
                            padding: '8px',
                            border: '1px solid #ddd',
                            borderRadius: '4px'
                        }
                    }),
                    wp.element.createElement('p', {
                        style: {
                            fontSize: '12px',
                            color: '#666',
                            marginTop: '4px'
                        }
                    }, 'Leave empty to use the term title')
                ),
                termId > 0 && wp.element.createElement(
                    'div',
                    {
                        style: {
                            marginTop: '16px',
                            padding: '12px',
                            background: '#f5f5f5',
                            borderRadius: '4px'
                        }
                    },
                    wp.element.createElement('strong', {}, 'Preview: '),
                    wp.element.createElement('span', {
                        className: 'glossary-term',
                        style: {
                            color: '#2271b1',
                            textDecoration: 'underline dotted',
                            cursor: 'help'
                        }
                    }, displayText || termTitle)
                )
            );
        },

        save: function(props) {
            const { attributes } = props;
            const { termId, termTitle, displayText } = attributes;

            if (!termId) {
                return null;
            }

            return wp.element.createElement(
                'p',
                null,
                '[glossary id="' + termId + '"]' + (displayText || termTitle) + '[/glossary]'
            );
        }
    });
})(window.wp);
