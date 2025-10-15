(function(wp) {
    const { registerFormatType, applyFormat, removeFormat } = wp.richText;
    const { RichTextToolbarButton } = wp.blockEditor;
    const { useState, useEffect } = wp.element;
    const { Modal, Button, Spinner, SearchControl } = wp.components;

    // Custom component for glossary term selector
    const GlossaryTermSelector = function(props) {
        const { onSelect, onClose } = props;
        const [glossaryTerms, setGlossaryTerms] = useState([]);
        const [loading, setLoading] = useState(true);
        const [searchTerm, setSearchTerm] = useState('');

        useEffect(function() {
            // Fetch all glossary terms
            wp.apiFetch({
                path: '/wp/v2/glossary?per_page=100&orderby=title&order=asc'
            }).then(function(terms) {
                setGlossaryTerms(terms);
                setLoading(false);
            }).catch(function(error) {
                console.error('Error fetching glossary terms:', error);
                setLoading(false);
            });
        }, []);

        const filteredTerms = glossaryTerms.filter(function(term) {
            return term.title.rendered.toLowerCase().includes(searchTerm.toLowerCase());
        });

        return wp.element.createElement(
            Modal,
            {
                title: 'Select Glossary Term',
                onRequestClose: onClose,
                className: 'glossary-term-modal'
            },
            loading ? wp.element.createElement(
                'div',
                { style: { textAlign: 'center', padding: '20px' } },
                wp.element.createElement(Spinner, null),
                wp.element.createElement('p', null, 'Loading glossary terms...')
            ) : wp.element.createElement(
                'div',
                null,
                wp.element.createElement(SearchControl, {
                    value: searchTerm,
                    onChange: setSearchTerm,
                    placeholder: 'Search terms...'
                }),
                wp.element.createElement(
                    'div',
                    { 
                        style: { 
                            maxHeight: '400px', 
                            overflowY: 'auto', 
                            marginTop: '15px',
                            border: '1px solid #ddd',
                            borderRadius: '4px'
                        } 
                    },
                    filteredTerms.length === 0 ? wp.element.createElement(
                        'p',
                        { style: { padding: '20px', textAlign: 'center', color: '#666' } },
                        searchTerm ? 'No terms found matching "' + searchTerm + '"' : 'No glossary terms available'
                    ) : filteredTerms.map(function(term) {
                        return wp.element.createElement(
                            'div',
                            {
                                key: term.id,
                                onClick: function() { onSelect(term); },
                                style: {
                                    padding: '12px 15px',
                                    cursor: 'pointer',
                                    borderBottom: '1px solid #f0f0f0',
                                    transition: 'background-color 0.2s'
                                },
                                onMouseEnter: function(e) {
                                    e.currentTarget.style.backgroundColor = '#f5f5f5';
                                },
                                onMouseLeave: function(e) {
                                    e.currentTarget.style.backgroundColor = 'transparent';
                                }
                            },
                            wp.element.createElement(
                                'div',
                                { style: { fontWeight: '600', marginBottom: '4px' } },
                                term.title.rendered
                            ),
                            term.excerpt && term.excerpt.rendered ? wp.element.createElement(
                                'div',
                                { 
                                    style: { 
                                        fontSize: '13px', 
                                        color: '#666',
                                        overflow: 'hidden',
                                        textOverflow: 'ellipsis',
                                        whiteSpace: 'nowrap'
                                    },
                                    dangerouslySetInnerHTML: { __html: term.excerpt.rendered }
                                }
                            ) : null
                        );
                    })
                )
            )
        );
    };

    registerFormatType('advanced-glossary/inline', {
        title: 'Glossary Term',
        tagName: 'span',
        className: 'glossary-term',
        attributes: {
            'data-term-id': 'data-term-id'
        },
        edit: function(props) {
            const { isActive, value, onChange } = props;
            const [showModal, setShowModal] = useState(false);

            const onToggle = function() {
                if (isActive) {
                    onChange(removeFormat(value, 'advanced-glossary/inline'));
                } else {
                    setShowModal(true);
                }
            };

            const onSelectTerm = function(term) {
                const format = {
                    type: 'advanced-glossary/inline',
                    attributes: {
                        'data-term-id': term.id.toString()
                    }
                };
                onChange(applyFormat(value, format));
                setShowModal(false);
            };

            return wp.element.createElement(
                wp.element.Fragment,
                null,
                wp.element.createElement(
                    RichTextToolbarButton,
                    {
                        icon: 'book',
                        title: 'Glossary Term',
                        onClick: onToggle,
                        isActive: isActive
                    }
                ),
                showModal && wp.element.createElement(GlossaryTermSelector, {
                    onSelect: onSelectTerm,
                    onClose: function() { setShowModal(false); }
                })
            );
        }
    });
})(window.wp);