/**
 * Container Block Designer - Frontend Features
 * Version: 2.2.0
 */

(function($) {
    'use strict';
    
    const CBDFrontendFeatures = {
        
        /**
         * Initialize all features
         */
        init: function() {
            this.initCollapse();
            this.initNumbering();
            this.initCopyText();
            this.initScreenshot();
            console.log('✅ CBD Frontend Features initialized');
        },
        
        /**
         * Feature 1: Icon (CSS only, no JS needed)
         */
        
        /**
         * Feature 2: Collapse functionality
         */
        initCollapse: function() {
            $('.cbd-container[data-collapse="true"]').each(function() {
                const $container = $(this);
                const defaultState = $container.data('collapse-default') || 'expanded';
                
                // Add collapse header
                const $header = $('<div class="cbd-collapse-header"></div>');
                const $toggle = $('<span class="cbd-collapse-toggle dashicons dashicons-arrow-down-alt2"></span>');
                const $title = $container.find('h1, h2, h3, h4, h5, h6').first().clone();
                
                if ($title.length === 0) {
                    $title.text('Container');
                }
                
                $header.append($toggle).append($title);
                $container.prepend($header);
                
                // Add content wrapper
                const $content = $('<div class="cbd-collapse-content"></div>');
                $container.children().not('.cbd-collapse-header').appendTo($content);
                $container.append($content);
                
                // Set initial state
                if (defaultState === 'collapsed') {
                    $container.addClass('cbd-collapsed');
                    $content.hide();
                    $toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                }
                
                // Toggle on click
                $header.on('click', function() {
                    $container.toggleClass('cbd-collapsed');
                    $content.slideToggle(300);
                    
                    if ($container.hasClass('cbd-collapsed')) {
                        $toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                    } else {
                        $toggle.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                    }
                });
            });
        },
        
        /**
         * Feature 3: Numbering
         */
        initNumbering: function() {
            const containers = $('.cbd-container[data-numbering="true"]');
            const format = containers.first().data('numbering-format') || 'numeric';
            
            containers.each(function(index) {
                const $container = $(this);
                let number;
                
                switch(format) {
                    case 'alpha':
                        number = String.fromCharCode(65 + index); // A, B, C...
                        break;
                    case 'roman':
                        number = CBDFrontendFeatures.toRoman(index + 1);
                        break;
                    default:
                        number = index + 1;
                }
                
                // Add number to container
                const $number = $('<span class="cbd-container-number">' + number + '.</span>');
                $container.prepend($number);
            });
        },
        
        /**
         * Feature 4: Copy text functionality
         */
        initCopyText: function() {
            $('.cbd-container[data-copy="true"]').each(function() {
                const $container = $(this);
                const buttonText = $container.data('copy-text') || 'Text kopieren';
                
                // Add copy button
                const $button = $('<button class="cbd-copy-button">' + buttonText + '</button>');
                $container.append($button);
                
                $button.on('click', function() {
                    const text = $container.clone()
                        .find('.cbd-copy-button, .cbd-screenshot-button, .cbd-container-number')
                        .remove()
                        .end()
                        .text()
                        .trim();
                    
                    // Copy to clipboard
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text).then(function() {
                            CBDFrontendFeatures.showMessage('✓ ' + (cbdFrontend?.strings?.copied || 'Text kopiert!'));
                        }).catch(function() {
                            CBDFrontendFeatures.fallbackCopy(text);
                        });
                    } else {
                        CBDFrontendFeatures.fallbackCopy(text);
                    }
                });
            });
        },
        
        /**
         * Feature 5: Screenshot functionality
         */
        initScreenshot: function() {
            // Only init if html2canvas is available
            if (typeof html2canvas === 'undefined') {
                // Try to load html2canvas from CDN
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                script.onload = function() {
                    CBDFrontendFeatures.initScreenshotButtons();
                };
                document.head.appendChild(script);
            } else {
                this.initScreenshotButtons();
            }
        },
        
        /**
         * Initialize screenshot buttons
         */
        initScreenshotButtons: function() {
            $('.cbd-container[data-screenshot="true"]').each(function() {
                const $container = $(this);
                const buttonText = $container.data('screenshot-text') || 'Screenshot';
                
                // Add screenshot button
                const $button = $('<button class="cbd-screenshot-button">' + buttonText + '</button>');
                $container.append($button);
                
                $button.on('click', function() {
                    if (typeof html2canvas === 'function') {
                        // Hide buttons temporarily
                        $container.find('.cbd-copy-button, .cbd-screenshot-button').hide();
                        
                        html2canvas($container[0], {
                            backgroundColor: null,
                            scale: 2,
                            logging: false
                        }).then(function(canvas) {
                            // Convert to blob
                            canvas.toBlob(function(blob) {
                                // Create download link
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'container-screenshot-' + Date.now() + '.png';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                URL.revokeObjectURL(url);
                                
                                CBDFrontendFeatures.showMessage('✓ ' + (cbdFrontend?.strings?.screenshotSaved || 'Screenshot gespeichert!'));
                            });
                            
                            // Show buttons again
                            $container.find('.cbd-copy-button, .cbd-screenshot-button').show();
                        }).catch(function(error) {
                            console.error('Screenshot error:', error);
                            $container.find('.cbd-copy-button, .cbd-screenshot-button').show();
                            CBDFrontendFeatures.showMessage('✗ ' + (cbdFrontend?.strings?.screenshotError || 'Fehler beim Screenshot'));
                        });
                    } else {
                        alert('Screenshot-Funktion nicht verfügbar');
                    }
                });
            });
        },
        
        /**
         * Fallback copy method
         */
        fallbackCopy: function(text) {
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            
            try {
                document.execCommand('copy');
                this.showMessage('✓ ' + (cbdFrontend?.strings?.copied || 'Text kopiert!'));
            } catch (err) {
                this.showMessage('✗ ' + (cbdFrontend?.strings?.copyError || 'Fehler beim Kopieren'));
            }
            
            $temp.remove();
        },
        
        /**
         * Convert to Roman numerals
         */
        toRoman: function(num) {
            const roman = {
                M: 1000, CM: 900, D: 500, CD: 400,
                C: 100, XC: 90, L: 50, XL: 40,
                X: 10, IX: 9, V: 5, IV: 4, I: 1
            };
            let str = '';
            
            for (let i of Object.keys(roman)) {
                const q = Math.floor(num / roman[i]);
                num -= q * roman[i];
                str += i.repeat(q);
            }
            
            return str;
        },
        
        /**
         * Show message to user
         */
        showMessage: function(message) {
            const $message = $('<div class="cbd-message">' + message + '</div>');
            $('body').append($message);
            
            $message.fadeIn(200);
            
            setTimeout(function() {
                $message.fadeOut(200, function() {
                    $message.remove();
                });
            }, 2000);
        }
    };
    
    // Initialize on DOM ready
    $(document).ready(function() {
        CBDFrontendFeatures.init();
    });
    
})(jQuery);