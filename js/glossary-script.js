jQuery(document).ready(function($) {
    let tooltip = null;
    let currentTermId = null;
    
    // Create tooltip element
    function createTooltip() {
        if (!tooltip) {
            tooltip = $('<div class="advgls-tooltip"><div class="advgls-tooltip-inner"><div class="advgls-tooltip-title"></div><div class="advgls-tooltip-content"></div></div></div>');
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
        $tooltip.find('.advgls-tooltip-title').text('');
        $tooltip.find('.advgls-tooltip-content').text(glossaryAjax.loading_text || 'Loading...');
        $tooltip.find('.advgls-tooltip-link').attr('href', '#').hide();
        
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
                    $tooltip.find('.advgls-tooltip-title').text(data.title);
                    $tooltip.find('.advgls-tooltip-content').text(data.description);
                    $tooltip.find('.advgls-tooltip-link').attr('href', data.link).show();
                    
                    // Reposition in case content changed size
                    setTimeout(function() {
                        positionTooltip(element);
                        $tooltip.removeClass('loading');
                    }, 50);
                }
            },
            error: function(xhr, status, error) {
                $tooltip.removeClass('loading');
                $tooltip.find('.advgls-tooltip-content').text(glossaryAjax.error_text || 'Error loading definition');
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
    $(document).on('mouseenter', '.advgls-term', function() {
        const termId = $(this).data('term-id');
        if (termId) {
            showTooltip(this, termId);
        }
    });
    
    $(document).on('mouseleave', '.advgls-term', function() {
        setTimeout(function() {
            if (!$('.advgls-tooltip:hover').length && !$('.advgls-term:hover').length) {
                hideTooltip();
            }
        }, 100);
    });
    
    $(document).on('mouseleave', '.advgls-tooltip', function() {
        setTimeout(function() {
            if (!$('.advgls-term:hover').length) {
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
                const hoveredTerm = $('.advgls-term:hover');
                if (hoveredTerm.length) {
                    positionTooltip(hoveredTerm[0]);
                }
            }
        }, 10);
    });
});