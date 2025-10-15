jQuery(document).ready(function($) {
    let tooltip = null;
    let currentTermId = null;
    
    // Create tooltip element
    function createTooltip() {
        if (!tooltip) {
            tooltip = $('<div class="glossary-tooltip"><div class="glossary-tooltip-inner"><div class="glossary-tooltip-title"></div><div class="glossary-tooltip-content"></div><a href="#" class="glossary-tooltip-link">Read more →</a></div></div>');
            $('body').append(tooltip);
        }
        return tooltip;
    }
    
    // Show tooltip
    function showTooltip(element, termId) {
        const $tooltip = createTooltip();
        
        // If already loading this term, don't reload
        if (currentTermId === termId && $tooltip.hasClass('loading')) {
            return;
        }
        
        currentTermId = termId;
        $tooltip.addClass('loading').removeClass('show');
        
        // Clear previous content but keep structure
        $tooltip.find('.glossary-tooltip-title').text('');
        $tooltip.find('.glossary-tooltip-content').text('Loading...');
        $tooltip.find('.glossary-tooltip-link').attr('href', '#').hide();
        
        // Position tooltip immediately so loading state is visible
        positionTooltip(element);
        $tooltip.addClass('show');
        
        // Get definition via AJAX
        $.ajax({
            url: glossaryAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_glossary_definition',
                term_id: termId,
                nonce: glossaryAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update content
                    $tooltip.find('.glossary-tooltip-title').text(data.title);
                    $tooltip.find('.glossary-tooltip-content').text(data.description);
                    $tooltip.find('.glossary-tooltip-link').attr('href', data.link).show();
                    
                    // Reposition in case content changed size
                    setTimeout(function() {
                        positionTooltip(element);
                        $tooltip.removeClass('loading');
                    }, 50);
                }
            },
            error: function(xhr, status, error) {
                $tooltip.removeClass('loading');
                $tooltip.find('.glossary-tooltip-content').text('Error loading definition');
            }
        });
    }
    
    // Position tooltip
    function positionTooltip(element) {
        const $element = $(element);
        const $tooltip = createTooltip();
        const offset = $element.offset();
        const elementHeight = $element.outerHeight();
        const elementWidth = $element.outerWidth();
        
        // Force a reflow to get accurate dimensions
        $tooltip.css('display', 'block');
        const tooltipWidth = $tooltip.outerWidth();
        const tooltipHeight = $tooltip.outerHeight();
        
        const windowWidth = $(window).width();
        const windowHeight = $(window).height();
        const scrollTop = $(window).scrollTop();
        
        // Default: show below element (arrow pointing up)
        let top = offset.top + elementHeight + 10;
        let left = offset.left + (elementWidth / 2) - (tooltipWidth / 2);
        
        // Remove previous position classes
        $tooltip.removeClass('below above');
        
        // Check if tooltip goes off screen horizontally
        if (left < 10) {
            left = 10;
        } else if (left + tooltipWidth > windowWidth - 10) {
            left = windowWidth - tooltipWidth - 10;
        }
        
        // Check if tooltip goes below viewport, show above element instead
        const spaceBelow = (scrollTop + windowHeight) - (offset.top + elementHeight);
        const spaceAbove = offset.top - scrollTop;
        
        if (spaceBelow < tooltipHeight + 20 && spaceAbove > tooltipHeight + 20) {
            // Not enough space below, show above (arrow pointing down)
            top = offset.top - tooltipHeight - 10;
            $tooltip.addClass('above');
        } else {
            // Show below (arrow pointing up) - this is default
            $tooltip.addClass('below');
        }
        
        $tooltip.css({
            top: top + 'px',
            left: left + 'px',
            display: ''
        });
    }
    
    // Hide tooltip
    function hideTooltip() {
        if (tooltip) {
            tooltip.removeClass('show');
            currentTermId = null;
        }
    }
    
    // Event handlers
    $(document).on('mouseenter', '.glossary-term', function() {
        const termId = $(this).data('term-id');
        if (termId) {
            showTooltip(this, termId);
        }
    });
    
    $(document).on('mouseleave', '.glossary-term', function() {
        setTimeout(function() {
            if (!$('.glossary-tooltip:hover').length && !$('.glossary-term:hover').length) {
                hideTooltip();
            }
        }, 100);
    });
    
    $(document).on('mouseleave', '.glossary-tooltip', function() {
        setTimeout(function() {
            if (!$('.glossary-term:hover').length) {
                hideTooltip();
            }
        }, 100);
    });
    
    // Reposition on scroll/resize
    let resizeTimer;
    $(window).on('scroll resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (tooltip && tooltip.hasClass('show')) {
                const hoveredTerm = $('.glossary-term:hover');
                if (hoveredTerm.length) {
                    positionTooltip(hoveredTerm[0]);
                }
            }
        }, 10);
    });
});