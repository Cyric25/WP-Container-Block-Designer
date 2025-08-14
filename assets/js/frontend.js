/**
 * Container Block Designer - Frontend JavaScript
 * Vereinfachte Version ohne globale Einstellungen
 * 
 * @package ContainerBlockDesigner
 * @version 3.0.0
 */

(function($) {
    'use strict';

    // Frontend-Objektnamensraum
    window.CBD_Frontend = {
        
        /**
         * Initialize numbering for container blocks
         */
        initializeNumbering: function($block) {
            const format = $block.data('numbering-format') || 'numeric';
            const startValue = parseInt($block.data('numbering-start')) || 1;
            const $numberElement = $block.find('.cbd-block-number');
            
            if ($numberElement.length) {
                // Find all numbered blocks on the page
                const $allNumberedBlocks = $('.cbd-container-block[data-enable-numbering="true"]');
                const blockIndex = $allNumberedBlocks.index($block);
                const currentNumber = startValue + blockIndex;
                
                // Format the number based on the specified format
                const formattedNumber = this.formatNumber(currentNumber, format);
                $numberElement.text(formattedNumber);
            }
        },
        
        /**
         * Initialize collapse/expand functionality
         */
        initializeCollapse: function($block) {
            const $toggleButton = $block.find('.cbd-collapse-toggle');
            const $content = $block.find('.cbd-block-content');
            const defaultState = $block.data('collapse-default') || 'expanded';
            
            if ($toggleButton.length && $content.length) {
                $toggleButton.on('click', function(e) {
                    e.preventDefault();
                    
                    const $button = $(this);
                    const $icon = $button.find('.cbd-toggle-icon');
                    const isExpanded = $button.attr('aria-expanded') === 'true';
                    
                    if (isExpanded) {
                        // Collapse
                        $content.slideUp(300);
                        $button.attr('aria-expanded', 'false');
                        $icon.removeClass('dashicons-minus').addClass('dashicons-plus');
                        $block.removeClass('cbd-expanded').addClass('cbd-collapsed');
                    } else {
                        // Expand
                        $content.slideDown(300);
                        $button.attr('aria-expanded', 'true');
                        $icon.removeClass('dashicons-plus').addClass('dashicons-minus');
                        $block.removeClass('cbd-collapsed').addClass('cbd-expanded');
                    }
                    
                    // Save state in localStorage (optional)
                    if ($block.attr('id')) {
                        localStorage.setItem(
                            'cbd_collapse_state_' + $block.attr('id'), 
                            isExpanded ? 'collapsed' : 'expanded'
                        );
                    }
                });
                
                // Restore state from localStorage on page load
                if ($block.attr('id')) {
                    const savedState = localStorage.getItem('cbd_collapse_state_' + $block.attr('id'));
                    if (savedState && savedState !== defaultState) {
                        // Trigger the collapse/expand to match saved state
                        setTimeout(function() {
                            $toggleButton.trigger('click');
                        }, 100);
                    }
                }
            }
        },
        
        /**
         * Initialize copy text functionality
         */
        initializeCopyText: function($block) {
            const $copyButton = $block.find('.cbd-copy-text-btn');
            const $content = $block.find('.cbd-block-content');
            
            if ($copyButton.length && $content.length) {
                $copyButton.on('click', function(e) {
                    e.preventDefault();
                    
                    const $button = $(this);
                    const originalText = $button.text().trim();
                    
                    // Get text content (without HTML)
                    const textToCopy = $content.text().trim();
                    
                    // Copy to clipboard
                    navigator.clipboard.writeText(textToCopy).then(function() {
                        // Success feedback
                        $button.addClass('cbd-success');
                        const $icon = $button.find('.dashicons');
                        $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes-alt');
                        $button.find('span:not(.dashicons)').text(cbdFrontend.strings.copied);
                        
                        // Reset after 2 seconds
                        setTimeout(function() {
                            $button.removeClass('cbd-success');
                            $icon.removeClass('dashicons-yes-alt').addClass('dashicons-clipboard');
                            $button.find('span:not(.dashicons)').text(originalText);
                        }, 2000);
                        
                    }).catch(function(err) {
                        // Error feedback
                        console.error('Copy failed:', err);
                        $button.addClass('cbd-error');
                        $button.find('span:not(.dashicons)').text(cbdFrontend.strings.copyError);
                        
                        // Reset after 2 seconds
                        setTimeout(function() {
                            $button.removeClass('cbd-error');
                            $button.find('span:not(.dashicons)').text(originalText);
                        }, 2000);
                    });
                });
            }
        },
        
        /**
         * Initialize screenshot functionality
         */
        initializeScreenshot: function($block) {
            const $screenshotButton = $block.find('.cbd-screenshot-btn');
            
            if ($screenshotButton.length) {
                $screenshotButton.on('click', function(e) {
                    e.preventDefault();
                    
                    const $button = $(this);
                    const originalText = $button.text().trim();
                    
                    // Check if html2canvas is loaded (external library needed)
                    if (typeof html2canvas === 'undefined') {
                        console.warn('html2canvas library not loaded. Loading dynamically...');
                        
                        // Load html2canvas dynamically
                        const script = document.createElement('script');
                        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                        script.onload = function() {
                            CBD_Frontend.createScreenshot($block, $button, originalText);
                        };
                        document.head.appendChild(script);
                    } else {
                        CBD_Frontend.createScreenshot($block, $button, originalText);
                    }
                });
            }
        },
        
        /**
         * Create screenshot using html2canvas
         */
        createScreenshot: function($block, $button, originalText) {
            $button.prop('disabled', true);
            $button.find('span:not(.dashicons)').text('Erstelle Screenshot...');
            
            html2canvas($block[0], {
                backgroundColor: '#ffffff',
                scale: 2,
                useCORS: true,
                allowTaint: true
            }).then(function(canvas) {
                // Create download link
                const link = document.createElement('a');
                link.download = 'container-block-screenshot-' + new Date().getTime() + '.png';
                link.href = canvas.toDataURL();
                
                // Trigger download
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Success feedback
                $button.addClass('cbd-success');
                const $icon = $button.find('.dashicons');
                $icon.removeClass('dashicons-camera').addClass('dashicons-yes-alt');
                $button.find('span:not(.dashicons)').text(cbdFrontend.strings.screenshotSaved);
                
                // Reset after 3 seconds
                setTimeout(function() {
                    $button.removeClass('cbd-success');
                    $icon.removeClass('dashicons-yes-alt').addClass('dashicons-camera');
                    $button.find('span:not(.dashicons)').text(originalText);
                    $button.prop('disabled', false);
                }, 3000);
                
            }).catch(function(error) {
                console.error('Screenshot failed:', error);
                
                // Error feedback
                $button.addClass('cbd-error');
                $button.find('span:not(.dashicons)').text(cbdFrontend.strings.screenshotError);
                
                // Reset after 3 seconds
                setTimeout(function() {
                    $button.removeClass('cbd-error');
                    $button.find('span:not(.dashicons)').text(originalText);
                    $button.prop('disabled', false);
                }, 3000);
            });
        },
        
        /**
         * Format number based on numbering format
         */
        formatNumber: function(number, format) {
            switch (format) {
                case 'alphabetic':
                    // Convert to alphabetic (A, B, C, ...)
                    return String.fromCharCode(64 + number); // A=65, so 64+1=A
                    
                case 'roman':
                    // Convert to roman numerals
                    return this.intToRoman(number);
                    
                case 'numeric':
                default:
                    return number.toString();
            }
        },
        
        /**
         * Convert integer to roman numeral
         */
        intToRoman: function(num) {
            const values = [1000, 900, 500, 400, 100, 90, 50, 40, 10, 9, 5, 4, 1];
            const literals = ['M', 'CM', 'D', 'CD', 'C', 'XC', 'L', 'XL', 'X', 'IX', 'V', 'IV', 'I'];
            let roman = '';
            
            for (let i = 0; i < values.length; i++) {
                while (num >= values[i]) {
                    roman += literals[i];
                    num -= values[i];
                }
            }
            
            return roman;
        },
        
        /**
         * Utility: Get text content without HTML tags
         */
        getTextContent: function($element) {
            return $element.clone()
                .children()
                .remove()
                .end()
                .text()
                .trim();
        },
        
        /**
         * Initialize all blocks on page load
         */
        initializeAll: function() {
            $('.cbd-container-block').each(function() {
                const $block = $(this);
                
                // Initialize features based on block attributes
                if ($block.data('enable-numbering')) {
                    CBD_Frontend.initializeNumbering($block);
                }
                
                if ($block.data('enable-collapse')) {
                    CBD_Frontend.initializeCollapse($block);
                }
                
                if ($block.data('enable-copy-text')) {
                    CBD_Frontend.initializeCopyText($block);
                }
                
                if ($block.data('enable-screenshot')) {
                    CBD_Frontend.initializeScreenshot($block);
                }
            });
        }
    };
    
    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        CBD_Frontend.initializeAll();
    });
    
    // Re-initialize when new content is loaded (e.g., AJAX)
    $(document).on('cbd:content-loaded', function() {
        CBD_Frontend.initializeAll();
    });

})(jQuery);