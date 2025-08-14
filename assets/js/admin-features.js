/**
 * Container Block Designer - Admin Features Management
 * Version: 2.2.0 - Complete with all 5 features
 */

(function($) {
    'use strict';
    
    window.CBDFeatures = {
        
        /**
         * Initialize Features System
         */
        init: function() {
            console.log('🚀 CBD Features System initialisiert');
            
            this.ensureModalExists();
            this.bindEvents();
            this.debugFeatures();
        },
        
        /**
         * Ensure modal exists in DOM
         */
        ensureModalExists: function() {
            // Check if modal already exists
            if ($('#cbd-features-modal').length > 0) {
                console.log('✅ Features Modal bereits vorhanden');
                return;
            }
            
            console.log('📦 Erstelle Features Modal mit allen 5 Features...');
            
            // Create modal with ALL 5 features
            const modalHTML = `
                <div id="cbd-features-modal" class="cbd-modal" style="display: none;">
                    <div class="cbd-modal-backdrop"></div>
                    <div class="cbd-modal-content">
                        <div class="cbd-modal-header">
                            <h2 class="cbd-modal-title">Container-Features konfigurieren</h2>
                            <button type="button" class="cbd-modal-close" aria-label="Schließen">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        
                        <div class="cbd-modal-body">
                            <form id="cbd-features-form">
                                <input type="hidden" id="features-block-id" value="">
                                
                                <!-- FEATURE 1: Block-Icon -->
                                <div class="cbd-feature-item">
                                    <div class="cbd-feature-header">
                                        <label class="cbd-feature-toggle">
                                            <input type="checkbox" id="feature-icon-enabled">
                                            <span class="cbd-toggle-slider"></span>
                                        </label>
                                        <div class="cbd-feature-info">
                                            <strong>Block-Icon</strong>
                                            <p>Zeigt ein Icon im Container-Header an</p>
                                        </div>
                                    </div>
                                    <div class="cbd-feature-settings" id="feature-icon-settings" style="display: none;">
                                        <label>Icon auswählen:</label>
                                        <div class="cbd-icon-selector">
                                            <input type="text" id="block-icon-value" value="dashicons-admin-generic" class="regular-text">
                                            <button type="button" class="button cbd-icon-picker">Icon wählen</button>
                                            <div class="cbd-current-icon">
                                                <span class="dashicons dashicons-admin-generic"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- FEATURE 2: Ein-/Ausklappbar -->
                                <div class="cbd-feature-item">
                                    <div class="cbd-feature-header">
                                        <label class="cbd-feature-toggle">
                                            <input type="checkbox" id="feature-collapse-enabled">
                                            <span class="cbd-toggle-slider"></span>
                                        </label>
                                        <div class="cbd-feature-info">
                                            <strong>Ein-/Ausklappbar</strong>
                                            <p>Container kann ein- und ausgeklappt werden</p>
                                        </div>
                                    </div>
                                    <div class="cbd-feature-settings" id="feature-collapse-settings" style="display: none;">
                                        <label>Standard-Zustand:</label>
                                        <select id="collapse-default-state" class="regular-text">
                                            <option value="expanded">Ausgeklappt</option>
                                            <option value="collapsed">Eingeklappt</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- FEATURE 3: Nummerierung -->
                                <div class="cbd-feature-item">
                                    <div class="cbd-feature-header">
                                        <label class="cbd-feature-toggle">
                                            <input type="checkbox" id="feature-numbering-enabled">
                                            <span class="cbd-toggle-slider"></span>
                                        </label>
                                        <div class="cbd-feature-info">
                                            <strong>Nummerierung</strong>
                                            <p>Automatische Nummerierung der Container</p>
                                        </div>
                                    </div>
                                    <div class="cbd-feature-settings" id="feature-numbering-settings" style="display: none;">
                                        <label>Format:</label>
                                        <select id="numbering-format" class="regular-text">
                                            <option value="numeric">1, 2, 3...</option>
                                            <option value="alpha">A, B, C...</option>
                                            <option value="roman">I, II, III...</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- FEATURE 4: Text kopieren -->
                                <div class="cbd-feature-item">
                                    <div class="cbd-feature-header">
                                        <label class="cbd-feature-toggle">
                                            <input type="checkbox" id="feature-copy-enabled">
                                            <span class="cbd-toggle-slider"></span>
                                        </label>
                                        <div class="cbd-feature-info">
                                            <strong>Text kopieren</strong>
                                            <p>Button zum Kopieren des Container-Inhalts</p>
                                        </div>
                                    </div>
                                    <div class="cbd-feature-settings" id="feature-copy-settings" style="display: none;">
                                        <label>Button-Text:</label>
                                        <input type="text" id="copy-button-text" value="Text kopieren" class="regular-text">
                                    </div>
                                </div>
                                
                                <!-- FEATURE 5: Screenshot -->
                                <div class="cbd-feature-item">
                                    <div class="cbd-feature-header">
                                        <label class="cbd-feature-toggle">
                                            <input type="checkbox" id="feature-screenshot-enabled">
                                            <span class="cbd-toggle-slider"></span>
                                        </label>
                                        <div class="cbd-feature-info">
                                            <strong>Screenshot</strong>
                                            <p>Screenshot-Funktion für den Container</p>
                                        </div>
                                    </div>
                                    <div class="cbd-feature-settings" id="feature-screenshot-settings" style="display: none;">
                                        <label>Button-Text:</label>
                                        <input type="text" id="screenshot-button-text" value="Screenshot" class="regular-text">
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div class="cbd-modal-footer">
                            <button type="button" id="cbd-save-features" class="button button-primary">Features speichern</button>
                            <button type="button" id="cbd-modal-cancel" class="button">Abbrechen</button>
                            <button type="button" id="cbd-reset-features" class="button">Zurücksetzen</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Append modal to body
            $('body').append(modalHTML);
            console.log('✅ Features Modal wurde erstellt mit allen 5 Features');
        },
        
        /**
         * Bind all events
         */
        bindEvents: function() {
            const self = this;
            
            // Open Features Modal - Multiple selectors for compatibility
            $(document).on('click', '.cbd-features-btn, .cbd-configure-features, [data-action="configure-features"]', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                const blockId = $btn.data('block-id') || $btn.data('id') || $btn.closest('tr').data('block-id') || $('#block-id').val();
                const blockName = $btn.data('block-name') || $btn.data('name') || $('#block-name').val() || 'Block';
                
                console.log('📂 Öffne Features Modal für Block:', {id: blockId, name: blockName});
                
                if (blockId && blockId !== '0') {
                    self.openFeaturesModal(blockId, blockName);
                } else {
                    alert('Bitte speichern Sie zuerst den Block, bevor Sie Features konfigurieren.');
                }
            });
            
            // Feature toggle switches
            $(document).on('change', '#cbd-features-form input[type="checkbox"]', function() {
                const $settings = $(this).closest('.cbd-feature-item').find('.cbd-feature-settings');
                if ($(this).prop('checked')) {
                    $settings.slideDown(200);
                } else {
                    $settings.slideUp(200);
                }
            });
            
            // Save Features
            $(document).on('click', '#cbd-save-features', function() {
                self.saveFeatures();
            });
            
            // Close Modal
            $(document).on('click', '#cbd-modal-cancel, .cbd-modal-close, .cbd-modal-backdrop', function() {
                self.closeModal();
            });
            
            // Reset Features
            $(document).on('click', '#cbd-reset-features', function() {
                if (confirm(cbdFeatures?.strings?.confirmReset || 'Wirklich alle Features zurücksetzen?')) {
                    self.resetFeatures();
                }
            });
            
            // Icon Picker
            $(document).on('click', '.cbd-icon-picker', function(e) {
                e.preventDefault();
                self.openIconPicker($(this));
            });
            
            // Icon value change
            $(document).on('input', '#block-icon-value', function() {
                const iconClass = $(this).val();
                $('.cbd-current-icon span').attr('class', 'dashicons ' + iconClass);
            });
            
            // Escape key to close modal
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape') {
                    self.closeModal();
                }
            });
            
            console.log('✅ Event-Handler wurden gebunden');
        },
        
        /**
         * Open Features Modal
         */
        openFeaturesModal: function(blockId, blockName) {
            console.log('📂 Öffne Features Modal für Block:', blockId, blockName);
            
            if (!blockId) {
                alert('Fehler: Keine Block-ID gefunden.');
                return;
            }
            
            // Ensure modal exists
            this.ensureModalExists();
            
            // Set block ID and name
            $('#features-block-id').val(blockId);
            $('.cbd-modal-title').text('Features für "' + blockName + '" konfigurieren');
            
            // Show modal
            $('#cbd-features-modal').fadeIn(200);
            $('body').addClass('cbd-modal-open');
            
            // Load features for this block
            this.loadFeatures(blockId);
        },
        
        /**
         * Load features from server
         */
        loadFeatures: function(blockId) {
            const self = this;
            
            $.ajax({
                url: cbdFeatures?.ajaxUrl || cbdAdmin?.ajaxUrl || ajaxurl,
                method: 'POST',
                data: {
                    action: 'cbd_get_features',
                    block_id: blockId,
                    nonce: cbdFeatures?.nonce || cbdAdmin?.nonce || ''
                },
                beforeSend: function() {
                    $('#cbd-features-form').css('opacity', '0.5');
                },
                success: function(response) {
                    console.log('📥 Features geladen:', response);
                    
                    if (response.success && response.data) {
                        self.loadFeaturesIntoModal(response.data);
                    } else {
                        // Load defaults
                        self.loadFeaturesIntoModal(self.getDefaultFeatures());
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Fehler beim Laden der Features:', error);
                    self.loadFeaturesIntoModal(self.getDefaultFeatures());
                },
                complete: function() {
                    $('#cbd-features-form').css('opacity', '1');
                }
            });
        },
        
        /**
         * Load features into modal form
         */
        loadFeaturesIntoModal: function(features) {
            console.log('📝 Lade Features in Modal:', features);
            
            // Reset all checkboxes and hide settings
            $('#cbd-features-form input[type="checkbox"]').prop('checked', false);
            $('#cbd-features-form .cbd-feature-settings').hide();
            
            // Feature 1: Icon
            if (features.icon) {
                const iconEnabled = features.icon.enabled === true || features.icon.enabled === 'true' || features.icon.enabled === 1;
                $('#feature-icon-enabled').prop('checked', iconEnabled);
                if (iconEnabled) {
                    $('#feature-icon-settings').show();
                    $('#block-icon-value').val(features.icon.value || 'dashicons-admin-generic');
                    $('.cbd-current-icon span').attr('class', 'dashicons ' + (features.icon.value || 'dashicons-admin-generic'));
                }
            }
            
            // Feature 2: Collapse
            if (features.collapse) {
                const collapseEnabled = features.collapse.enabled === true || features.collapse.enabled === 'true' || features.collapse.enabled === 1;
                $('#feature-collapse-enabled').prop('checked', collapseEnabled);
                if (collapseEnabled) {
                    $('#feature-collapse-settings').show();
                    $('#collapse-default-state').val(features.collapse.defaultState || 'expanded');
                }
            }
            
            // Feature 3: Numbering
            if (features.numbering) {
                const numberingEnabled = features.numbering.enabled === true || features.numbering.enabled === 'true' || features.numbering.enabled === 1;
                $('#feature-numbering-enabled').prop('checked', numberingEnabled);
                if (numberingEnabled) {
                    $('#feature-numbering-settings').show();
                    $('#numbering-format').val(features.numbering.format || 'numeric');
                }
            }
            
            // Feature 4: Copy Text
            if (features.copyText) {
                const copyEnabled = features.copyText.enabled === true || features.copyText.enabled === 'true' || features.copyText.enabled === 1;
                $('#feature-copy-enabled').prop('checked', copyEnabled);
                if (copyEnabled) {
                    $('#feature-copy-settings').show();
                    $('#copy-button-text').val(features.copyText.buttonText || 'Text kopieren');
                }
            }
            
            // Feature 5: Screenshot
            if (features.screenshot) {
                const screenshotEnabled = features.screenshot.enabled === true || features.screenshot.enabled === 'true' || features.screenshot.enabled === 1;
                $('#feature-screenshot-enabled').prop('checked', screenshotEnabled);
                if (screenshotEnabled) {
                    $('#feature-screenshot-settings').show();
                    $('#screenshot-button-text').val(features.screenshot.buttonText || 'Screenshot');
                }
            }
            
            console.log('✅ Alle 5 Features wurden geladen');
        },
        
        /**
         * Save features
         */
        saveFeatures: function() {
            const blockId = $('#features-block-id').val();
            
            if (!blockId) {
                alert('Fehler: Keine Block-ID gefunden.');
                return;
            }
            
            const features = {
                icon: {
                    enabled: $('#feature-icon-enabled').prop('checked'),
                    value: $('#block-icon-value').val() || 'dashicons-admin-generic'
                },
                collapse: {
                    enabled: $('#feature-collapse-enabled').prop('checked'),
                    defaultState: $('#collapse-default-state').val() || 'expanded'
                },
                numbering: {
                    enabled: $('#feature-numbering-enabled').prop('checked'),
                    format: $('#numbering-format').val() || 'numeric'
                },
                copyText: {
                    enabled: $('#feature-copy-enabled').prop('checked'),
                    buttonText: $('#copy-button-text').val() || 'Text kopieren'
                },
                screenshot: {
                    enabled: $('#feature-screenshot-enabled').prop('checked'),
                    buttonText: $('#screenshot-button-text').val() || 'Screenshot'
                }
            };
            
            console.log('💾 Speichere alle 5 Features:', features);
            
            $.ajax({
                url: cbdFeatures?.ajaxUrl || cbdAdmin?.ajaxUrl || ajaxurl,
                method: 'POST',
                data: {
                    action: 'cbd_save_features',
                    block_id: blockId,
                    features: JSON.stringify(features),
                    nonce: cbdFeatures?.nonce || cbdAdmin?.nonce || ''
                },
                beforeSend: function() {
                    $('#cbd-save-features').prop('disabled', true).text('Wird gespeichert...');
                },
                success: function(response) {
                    if (response.success) {
                        console.log('✅ Features erfolgreich gespeichert');
                        
                        // Show success message
                        const $button = $('#cbd-save-features');
                        $button.removeClass('button-primary').addClass('button-success').text('✓ Gespeichert');
                        
                        // Update features display in the list
                        CBDFeatures.updateFeaturesDisplay(blockId, features);
                        
                        setTimeout(function() {
                            CBDFeatures.closeModal();
                        }, 1500);
                    } else {
                        alert(response.data || 'Fehler beim Speichern der Features');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Fehler:', error);
                    alert('Fehler beim Speichern der Features: ' + error);
                },
                complete: function() {
                    setTimeout(function() {
                        $('#cbd-save-features').prop('disabled', false).removeClass('button-success').addClass('button-primary').text('Features speichern');
                    }, 2000);
                }
            });
        },
        
        /**
         * Reset features to defaults
         */
        resetFeatures: function() {
            console.log('🔄 Features zurücksetzen auf Standardwerte');
            this.loadFeaturesIntoModal(this.getDefaultFeatures());
        },
        
        /**
         * Get default features
         */
        getDefaultFeatures: function() {
            return {
                icon: { enabled: false, value: 'dashicons-admin-generic' },
                collapse: { enabled: false, defaultState: 'expanded' },
                numbering: { enabled: false, format: 'numeric' },
                copyText: { enabled: false, buttonText: 'Text kopieren' },
                screenshot: { enabled: false, buttonText: 'Screenshot' }
            };
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            $('#cbd-features-modal').fadeOut(200);
            $('body').removeClass('cbd-modal-open');
        },
        
        /**
         * Update features display in block list
         */
        updateFeaturesDisplay: function(blockId, features) {
            const $row = $('tr[data-block-id="' + blockId + '"]');
            if ($row.length === 0) return;
            
            const activeFeatures = [];
            if (features.icon && features.icon.enabled) activeFeatures.push('Icon');
            if (features.collapse && features.collapse.enabled) activeFeatures.push('Ein-/Ausklappbar');
            if (features.numbering && features.numbering.enabled) activeFeatures.push('Nummerierung');
            if (features.copyText && features.copyText.enabled) activeFeatures.push('Text kopieren');
            if (features.screenshot && features.screenshot.enabled) activeFeatures.push('Screenshot');
            
            const $featuresCell = $row.find('.column-features');
            if ($featuresCell.length > 0) {
                if (activeFeatures.length > 0) {
                    $featuresCell.html('<span class="cbd-features-badges">' + 
                        activeFeatures.map(f => '<span class="cbd-feature-badge">' + f + '</span>').join(' ') +
                        '</span>');
                } else {
                    $featuresCell.html('<span style="color: #999;">—</span>');
                }
            }
            
            // Update in edit form if present
            if ($('#block-id').val() == blockId) {
                location.reload(); // Reload to show updated features
            }
        },
        
        /**
         * Open icon picker dialog
         */
        openIconPicker: function($button) {
            const self = this;
            
            // Common Dashicons list
            const dashicons = [
                'dashicons-menu', 'dashicons-admin-site', 'dashicons-dashboard', 'dashicons-admin-media',
                'dashicons-admin-page', 'dashicons-admin-comments', 'dashicons-admin-appearance', 
                'dashicons-admin-plugins', 'dashicons-admin-users', 'dashicons-admin-tools',
                'dashicons-admin-settings', 'dashicons-admin-network', 'dashicons-admin-generic',
                'dashicons-admin-home', 'dashicons-admin-collapse', 'dashicons-filter',
                'dashicons-admin-customizer', 'dashicons-admin-multisite', 'dashicons-admin-links',
                'dashicons-format-links', 'dashicons-admin-post', 'dashicons-format-standard',
                'dashicons-format-image', 'dashicons-format-gallery', 'dashicons-format-audio',
                'dashicons-format-video', 'dashicons-format-chat', 'dashicons-format-status',
                'dashicons-format-aside', 'dashicons-format-quote', 'dashicons-welcome-write-blog',
                'dashicons-welcome-edit-page', 'dashicons-welcome-add-page', 'dashicons-welcome-view-site',
                'dashicons-welcome-widgets-menus', 'dashicons-welcome-comments', 'dashicons-welcome-learn-more',
                'dashicons-image-crop', 'dashicons-image-rotate', 'dashicons-image-rotate-left',
                'dashicons-image-rotate-right', 'dashicons-image-flip-vertical', 'dashicons-image-flip-horizontal',
                'dashicons-image-filter', 'dashicons-undo', 'dashicons-redo', 'dashicons-editor-bold',
                'dashicons-editor-italic', 'dashicons-editor-ul', 'dashicons-editor-ol',
                'dashicons-editor-quote', 'dashicons-editor-alignleft', 'dashicons-editor-aligncenter',
                'dashicons-editor-alignright', 'dashicons-editor-insertmore', 'dashicons-editor-spellcheck',
                'dashicons-editor-expand', 'dashicons-editor-contract', 'dashicons-editor-kitchensink',
                'dashicons-editor-underline', 'dashicons-editor-justify', 'dashicons-editor-textcolor',
                'dashicons-editor-paste-word', 'dashicons-editor-paste-text', 'dashicons-editor-removeformatting',
                'dashicons-editor-video', 'dashicons-editor-customchar', 'dashicons-editor-outdent',
                'dashicons-editor-indent', 'dashicons-editor-help', 'dashicons-editor-strikethrough',
                'dashicons-editor-unlink', 'dashicons-editor-rtl', 'dashicons-editor-break',
                'dashicons-editor-code', 'dashicons-editor-paragraph', 'dashicons-editor-table',
                'dashicons-align-left', 'dashicons-align-right', 'dashicons-align-center',
                'dashicons-align-none', 'dashicons-lock', 'dashicons-unlock', 'dashicons-calendar',
                'dashicons-calendar-alt', 'dashicons-visibility', 'dashicons-hidden',
                'dashicons-post-status', 'dashicons-edit', 'dashicons-trash', 'dashicons-sticky',
                'dashicons-external', 'dashicons-arrow-up', 'dashicons-arrow-down',
                'dashicons-arrow-left', 'dashicons-arrow-right', 'dashicons-arrow-up-alt',
                'dashicons-arrow-down-alt', 'dashicons-arrow-left-alt', 'dashicons-arrow-right-alt',
                'dashicons-arrow-up-alt2', 'dashicons-arrow-down-alt2', 'dashicons-arrow-left-alt2',
                'dashicons-arrow-right-alt2', 'dashicons-leftright', 'dashicons-sort',
                'dashicons-randomize', 'dashicons-list-view', 'dashicons-excerpt-view',
                'dashicons-grid-view', 'dashicons-hammer', 'dashicons-art', 'dashicons-migrate',
                'dashicons-performance', 'dashicons-universal-access', 'dashicons-universal-access-alt',
                'dashicons-tickets', 'dashicons-nametag', 'dashicons-clipboard', 'dashicons-heart',
                'dashicons-megaphone', 'dashicons-schedule', 'dashicons-wordpress', 'dashicons-wordpress-alt',
                'dashicons-pressthis', 'dashicons-update', 'dashicons-screenoptions', 'dashicons-cart',
                'dashicons-feedback', 'dashicons-cloud', 'dashicons-translation', 'dashicons-tag',
                'dashicons-category', 'dashicons-archive', 'dashicons-tagcloud', 'dashicons-text',
                'dashicons-media-archive', 'dashicons-media-audio', 'dashicons-media-code',
                'dashicons-media-default', 'dashicons-media-document', 'dashicons-media-interactive',
                'dashicons-media-spreadsheet', 'dashicons-media-text', 'dashicons-media-video',
                'dashicons-playlist-audio', 'dashicons-playlist-video', 'dashicons-controls-play',
                'dashicons-controls-pause', 'dashicons-controls-forward', 'dashicons-controls-skipforward',
                'dashicons-controls-back', 'dashicons-controls-skipback', 'dashicons-controls-repeat',
                'dashicons-controls-volumeon', 'dashicons-controls-volumeoff', 'dashicons-yes',
                'dashicons-no', 'dashicons-no-alt', 'dashicons-plus', 'dashicons-plus-alt',
                'dashicons-plus-alt2', 'dashicons-minus', 'dashicons-dismiss', 'dashicons-marker',
                'dashicons-star-filled', 'dashicons-star-half', 'dashicons-star-empty', 'dashicons-flag',
                'dashicons-info', 'dashicons-warning', 'dashicons-share', 'dashicons-share-alt',
                'dashicons-share-alt2', 'dashicons-twitter', 'dashicons-rss', 'dashicons-email',
                'dashicons-email-alt', 'dashicons-facebook', 'dashicons-facebook-alt', 'dashicons-networking',
                'dashicons-googleplus', 'dashicons-location', 'dashicons-location-alt', 'dashicons-camera',
                'dashicons-images-alt', 'dashicons-images-alt2', 'dashicons-video-alt', 'dashicons-video-alt2',
                'dashicons-video-alt3', 'dashicons-vault', 'dashicons-shield', 'dashicons-shield-alt',
                'dashicons-sos', 'dashicons-search', 'dashicons-slides', 'dashicons-analytics',
                'dashicons-chart-pie', 'dashicons-chart-bar', 'dashicons-chart-line', 'dashicons-chart-area',
                'dashicons-groups', 'dashicons-businessman', 'dashicons-id', 'dashicons-id-alt',
                'dashicons-products', 'dashicons-awards', 'dashicons-forms', 'dashicons-testimonial',
                'dashicons-portfolio', 'dashicons-book', 'dashicons-book-alt', 'dashicons-download',
                'dashicons-upload', 'dashicons-backup', 'dashicons-clock', 'dashicons-lightbulb',
                'dashicons-microphone', 'dashicons-desktop', 'dashicons-tablet', 'dashicons-smartphone',
                'dashicons-phone', 'dashicons-smiley', 'dashicons-index-card', 'dashicons-carrot',
                'dashicons-building', 'dashicons-store', 'dashicons-album', 'dashicons-palmtree',
                'dashicons-tickets-alt', 'dashicons-money', 'dashicons-thumbs-up', 'dashicons-thumbs-down',
                'dashicons-layout', 'dashicons-paperclip'
            ];
            
            // Create icon picker dialog
            const pickerHTML = `
                <div id="cbd-icon-picker-dialog" class="cbd-icon-picker-overlay">
                    <div class="cbd-icon-picker-content">
                        <div class="cbd-icon-picker-header">
                            <h3>Icon auswählen</h3>
                            <button type="button" class="cbd-icon-picker-close">&times;</button>
                        </div>
                        <div class="cbd-icon-picker-search">
                            <input type="text" id="cbd-icon-search" placeholder="Icon suchen..." class="widefat">
                        </div>
                        <div class="cbd-icon-picker-grid">
                            ${dashicons.map(icon => `
                                <div class="cbd-icon-picker-item" data-icon="${icon}" title="${icon}">
                                    <span class="dashicons ${icon}"></span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            
            // Add to body if not exists
            if ($('#cbd-icon-picker-dialog').length === 0) {
                $('body').append(pickerHTML);
            }
            
            // Show dialog
            $('#cbd-icon-picker-dialog').fadeIn(200);
            
            // Search functionality
            $('#cbd-icon-search').off('input').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.cbd-icon-picker-item').each(function() {
                    const iconName = $(this).data('icon').toLowerCase();
                    if (iconName.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Select icon
            $('#cbd-icon-picker-dialog .cbd-icon-picker-item').off('click').on('click', function() {
                const selectedIcon = $(this).data('icon');
                $('#block-icon-value').val(selectedIcon).trigger('input');
                $('#cbd-icon-picker-dialog').fadeOut(200);
            });
            
            // Close dialog
            $('#cbd-icon-picker-dialog .cbd-icon-picker-close').off('click').on('click', function() {
                $('#cbd-icon-picker-dialog').fadeOut(200);
            });
        },
        
        /**
         * Debug features
         */
        debugFeatures: function() {
            console.log('🔍 Features Debug:');
            console.log('- Modal vorhanden:', $('#cbd-features-modal').length > 0 ? '✅' : '❌');
            console.log('- Feature 1 Icon:', $('#feature-icon-enabled').length > 0 ? '✅' : '❌');
            console.log('- Feature 2 Collapse:', $('#feature-collapse-enabled').length > 0 ? '✅' : '❌');
            console.log('- Feature 3 Numbering:', $('#feature-numbering-enabled').length > 0 ? '✅' : '❌');
            console.log('- Feature 4 Copy Text:', $('#feature-copy-enabled').length > 0 ? '✅' : '❌');
            console.log('- Feature 5 Screenshot:', $('#feature-screenshot-enabled').length > 0 ? '✅' : '❌');
            console.log('✅ Alle 5 Features sind verfügbar!');
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        CBDFeatures.init();
    });
    
    // Also initialize on Gutenberg ready if in editor
    if (window.wp && window.wp.domReady) {
        wp.domReady(function() {
            CBDFeatures.init();
        });
    }
    
})(jQuery);