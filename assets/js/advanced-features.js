/**
 * Container Block Designer - Advanced Features JavaScript
 * Version: 2.3.0 - Fixes duplicate button issues
 */

(function($) {
    'use strict';
    
    // Namespace for our plugin
    window.CBDFeatures = window.CBDFeatures || {};
    
    /**
     * Main Container Features Class
     */
    class ContainerFeatures {
        constructor(element) {
            this.element = element;
            this.$element = $(element);
            this.features = this.parseFeatures();
            this.isProcessing = false;
            this.collapseState = this.features.collapse?.defaultState || 'expanded';
            
            this.init();
        }
        
        init() {
            console.log('Initializing container features:', this.features);
            
            // Setup collapse structure if needed
            if (this.features.collapse?.enabled) {
                this.setupCollapseStructure();
            } else {
                // If no collapse, ensure action buttons are properly positioned
                this.ensureActionButtons();
            }
            
            this.bindEvents();
            this.updateButtonVisibility();
        }
        
        parseFeatures() {
            const features = {};
            
            // Icon feature
            if (this.$element.data('icon') === true) {
                features.icon = {
                    enabled: true,
                    value: this.$element.data('icon-value') || 'dashicons-admin-generic'
                };
            }
            
            // Collapse feature
            if (this.$element.data('collapse') === true) {
                features.collapse = {
                    enabled: true,
                    defaultState: this.$element.data('collapse-default') || 'expanded',
                    animationSpeed: 300,
                    saveState: true
                };
            }
            
            // Numbering feature
            if (this.$element.data('numbering') === true) {
                features.numbering = {
                    enabled: true,
                    format: this.$element.data('numbering-format') || 'numeric'
                };
            }
            
            // Copy feature
            if (this.$element.data('copy') === true) {
                features.copyText = {
                    enabled: true,
                    buttonText: this.$element.data('copy-text') || 'Text kopieren',
                    copyFormat: 'plain'
                };
            }
            
            // Screenshot feature
            if (this.$element.data('screenshot') === true) {
                features.screenshot = {
                    enabled: true,
                    buttonText: this.$element.data('screenshot-text') || 'Screenshot'
                };
            }
            
            return features;
        }
        
        setupCollapseStructure() {
            // Check if structure already exists
            if (this.$element.find('.cbd-collapse-header').length > 0) {
                // Structure already exists, just update state
                this.updateCollapseState();
                return;
            }
            
            // Create collapse structure
            const $header = $('<div>', {
                class: 'cbd-collapse-header',
                role: 'button',
                tabindex: '0',
                'aria-expanded': this.collapseState === 'expanded'
            });
            
            // Toggle icon
            const $toggleIcon = $('<span>', {
                class: 'dashicons dashicons-arrow-down-alt2 cbd-collapse-toggle'
            });
            
            // Title
            const $title = $('<span>', {
                class: 'cbd-collapse-title',
                text: 'Container Inhalt'
            });
            
            // Header action buttons (for collapsed state)
            const $headerButtons = this.createActionButtons('header');
            
            $header.append($toggleIcon, $title, $headerButtons);
            
            // Wrap existing content
            const $content = this.$element.children().not('.cbd-collapse-header');
            const $contentWrapper = $('<div>', {
                class: 'cbd-collapse-content'
            });
            
            $content.appendTo($contentWrapper);
            
            // Add action buttons to content (for expanded state)
            const $contentButtons = this.createActionButtons('content');
            if ($contentButtons.children().length > 0) {
                $contentWrapper.append($contentButtons);
            }
            
            // Clear container and add new structure
            this.$element.empty().append($header, $contentWrapper);
            
            // Set initial state
            this.updateCollapseState();
        }
        
        createActionButtons(context) {
            const $container = $('<div>', {
                class: `cbd-action-buttons cbd-action-buttons-${context}`
            });
            
            // Copy button
            if (this.features.copyText?.enabled) {
                const $button = $('<button>', {
                    type: 'button',
                    class: 'cbd-copy-button',
                    'data-context': context
                });
                
                $button.append(
                    $('<span>', { class: 'dashicons dashicons-clipboard' }),
                    $('<span>', { text: this.features.copyText.buttonText })
                );
                
                $container.append($button);
            }
            
            // Screenshot button
            if (this.features.screenshot?.enabled) {
                const $button = $('<button>', {
                    type: 'button',
                    class: 'cbd-screenshot-button',
                    'data-context': context
                });
                
                $button.append(
                    $('<span>', { class: 'dashicons dashicons-camera' }),
                    $('<span>', { text: this.features.screenshot.buttonText })
                );
                
                $container.append($button);
            }
            
            return $container;
        }
        
        ensureActionButtons() {
            // Only add buttons if they don't exist and features are enabled
            if (this.$element.find('.cbd-action-buttons').length === 0) {
                const hasButtons = (this.features.copyText?.enabled || this.features.screenshot?.enabled);
                
                if (hasButtons) {
                    const $buttons = this.createActionButtons('default');
                    this.$element.append($buttons);
                }
            }
        }
        
        bindEvents() {
            const self = this;
            
            // Collapse/Expand toggle
            if (this.features.collapse?.enabled) {
                this.$element.on('click', '.cbd-collapse-header', function(e) {
                    if (!$(e.target).closest('.cbd-action-buttons').length) {
                        self.toggleCollapse();
                    }
                });
                
                // Keyboard support
                this.$element.on('keydown', '.cbd-collapse-header', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (!$(e.target).closest('.cbd-action-buttons').length) {
                            self.toggleCollapse();
                        }
                    }
                });
            }
            
            // Copy functionality
            this.$element.on('click', '.cbd-copy-button', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.handleCopyClick($(this));
            });
            
            // Screenshot functionality
            this.$element.on('click', '.cbd-screenshot-button', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.handleScreenshotClick($(this));
            });
        }
        
        toggleCollapse() {
            if (!this.features.collapse?.enabled) return;
            
            this.collapseState = this.collapseState === 'expanded' ? 'collapsed' : 'expanded';
            this.updateCollapseState();
            
            // Save state to localStorage if enabled
            if (this.features.collapse.saveState) {
                this.saveCollapseState();
            }
            
            // Trigger custom event
            this.$element.trigger('cbd:collapse:toggle', [this.collapseState]);
        }
        
        updateCollapseState() {
            const $header = this.$element.find('.cbd-collapse-header');
            const $content = this.$element.find('.cbd-collapse-content');
            const $toggle = this.$element.find('.cbd-collapse-toggle');
            
            if (this.collapseState === 'collapsed') {
                this.$element.addClass('cbd-collapsed');
                $header.attr('aria-expanded', 'false');
                $content.slideUp(this.features.collapse.animationSpeed || 300);
            } else {
                this.$element.removeClass('cbd-collapsed');
                $header.attr('aria-expanded', 'true');
                $content.slideDown(this.features.collapse.animationSpeed || 300);
            }
            
            // Update button visibility
            setTimeout(() => {
                this.updateButtonVisibility();
            }, 50);
        }
        
        updateButtonVisibility() {
            const $headerButtons = this.$element.find('.cbd-action-buttons-header');
            const $contentButtons = this.$element.find('.cbd-action-buttons-content');
            
            if (this.features.collapse?.enabled) {
                if (this.collapseState === 'collapsed') {
                    // Show header buttons, hide content buttons
                    $headerButtons.css({
                        opacity: 1,
                        visibility: 'visible'
                    });
                    $contentButtons.css({
                        opacity: 0,
                        visibility: 'hidden'
                    });
                } else {
                    // Hide header buttons, show content buttons
                    $headerButtons.css({
                        opacity: 0,
                        visibility: 'hidden'
                    });
                    $contentButtons.css({
                        opacity: 1,
                        visibility: 'visible'
                    });
                }
            }
        }
        
        saveCollapseState() {
            if (!this.features.collapse?.saveState) return;
            
            try {
                const containerId = this.$element.attr('id') || 
                                  this.$element.index() + '_' + Date.now();
                localStorage.setItem(`cbd_collapse_${containerId}`, this.collapseState);
            } catch (e) {
                console.warn('Could not save collapse state:', e);
            }
        }
        
        handleCopyClick($button) {
            if (this.isProcessing) return;
            
            this.isProcessing = true;
            $button.addClass('processing').prop('disabled', true);
            
            const originalText = $button.find('span:last').text();
            
            // Get content to copy
            let content = '';
            if (this.features.copyText.copyFormat === 'html') {
                content = this.$element.find('.cbd-collapse-content').length > 0 
                         ? this.$element.find('.cbd-collapse-content').html()
                         : this.$element.html();
            } else {
                content = this.$element.find('.cbd-collapse-content').length > 0
                         ? this.$element.find('.cbd-collapse-content').text().trim()
                         : this.$element.text().trim();
            }
            
            // Clean up content (remove button text)
            content = content.replace(this.features.copyText.buttonText, '').trim();
            if (this.features.screenshot?.enabled) {
                content = content.replace(this.features.screenshot.buttonText, '').trim();
            }
            
            this.copyToClipboard(content).then(() => {
                this.showButtonSuccess($button, 'Kopiert!');
            }).catch((error) => {
                console.error('Copy failed:', error);
                this.showButtonError($button, 'Fehler beim Kopieren');
            }).finally(() => {
                setTimeout(() => {
                    this.resetButton($button, originalText);
                    this.isProcessing = false;
                }, 2000);
            });
        }
        
        handleScreenshotClick($button) {
            if (this.isProcessing) return;
            
            this.isProcessing = true;
            $button.addClass('processing').prop('disabled', true);
            
            const originalText = $button.find('span:last').text();
            
            // Temporarily hide buttons during screenshot
            const $allButtons = this.$element.find('.cbd-action-buttons');
            $allButtons.css('visibility', 'hidden');
            
            this.takeScreenshot().then((canvas) => {
                this.downloadScreenshot(canvas);
                this.showButtonSuccess($button, 'Screenshot erstellt!');
            }).catch((error) => {
                console.error('Screenshot failed:', error);
                this.showButtonError($button, 'Fehler beim Screenshot');
            }).finally(() => {
                $allButtons.css('visibility', 'visible');
                setTimeout(() => {
                    this.resetButton($button, originalText);
                    this.isProcessing = false;
                }, 2000);
            });
        }
        
        async copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(text);
                    return;
                } catch (err) {
                    console.warn('Modern clipboard API failed, falling back:', err);
                }
            }
            
            // Fallback method
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            return new Promise((resolve, reject) => {
                if (document.execCommand('copy')) {
                    resolve();
                } else {
                    reject(new Error('execCommand copy failed'));
                }
                document.body.removeChild(textArea);
            });
        }
        
        async takeScreenshot() {
            if (!window.html2canvas) {
                throw new Error('html2canvas library not loaded');
            }
            
            const element = this.$element.find('.cbd-collapse-content').length > 0
                           ? this.$element.find('.cbd-collapse-content')[0]
                           : this.$element[0];
            
            return html2canvas(element, {
                backgroundColor: '#ffffff',
                scale: 2,
                logging: false,
                useCORS: true,
                allowTaint: true
            });
        }
        
        downloadScreenshot(canvas) {
            const link = document.createElement('a');
            link.download = `container-screenshot-${Date.now()}.png`;
            link.href = canvas.toDataURL();
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        showButtonSuccess($button, message) {
            $button.removeClass('processing').addClass('success');
            $button.find('span:last').text(message);
        }
        
        showButtonError($button, message) {
            $button.removeClass('processing').addClass('error');
            $button.find('span:last').text(message);
        }
        
        resetButton($button, originalText) {
            $button.removeClass('processing success error')
                   .prop('disabled', false)
                   .find('span:last').text(originalText);
        }
    }
    
    /**
     * jQuery Plugin
     */
    $.fn.cbdFeatures = function(options) {
        return this.each(function() {
            if (!$(this).data('cbdFeatures')) {
                $(this).data('cbdFeatures', new ContainerFeatures(this, options));
            }
        });
    };
    
    /**
     * Auto-initialize on document ready
     */
    $(document).ready(function() {
        // Initialize all container blocks with features
        $('.cbd-container[data-collapse="true"], .cbd-container[data-copy="true"], .cbd-container[data-screenshot="true"]')
            .cbdFeatures();
            
        // Also initialize containers with icons or numbering that might need special handling
        $('.cbd-container[data-icon="true"], .cbd-container[data-numbering="true"]')
            .cbdFeatures();
    });
    
    /**
     * Re-initialize after AJAX or dynamic content loading
     */
    $(document).on('cbd:reinit', function() {
        $('.cbd-container').each(function() {
            if (!$(this).data('cbdFeatures')) {
                $(this).cbdFeatures();
            }
        });
    });
    
    // Expose to global namespace
    window.CBDFeatures.ContainerFeatures = ContainerFeatures;
    
})(jQuery);