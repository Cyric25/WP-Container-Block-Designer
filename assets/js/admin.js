/**
 * Container Block Designer - Admin JavaScript
 * Version: 2.2.1 - Bugfix Release
 */

(function($) {
    'use strict';

    const CBDAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initColorPickers();
            this.initPreview();
            this.initBulkActions();
            this.initSearch();
            
            console.log('✅ CBD Admin initialized');
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            const self = this;
            
            // Block actions - Korrigierte Selektoren
            $(document).on('click', '.cbd-delete-btn', function(e) {
                e.preventDefault();
                self.deleteBlock($(this).data('id'));
            });
            
            $(document).on('click', '.cbd-toggle-status-btn', function(e) {
                e.preventDefault();
                self.toggleStatus($(this).data('id'));
            });
            
            $(document).on('click', '.cbd-duplicate-btn', function(e) {
                e.preventDefault();
                self.duplicateBlock($(this).data('id'));
            });
            
            // Features modal - Verbessert für alle Situationen
            $(document).on('click', '.cbd-configure-features, .cbd-features-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                let blockId = $(this).data('id');
                
                // Fallback 1: Try to get from button's data-block-id
                if (!blockId) {
                    blockId = $(this).data('block-id');
                }
                
                // Fallback 2: Try to get from parent row
                if (!blockId) {
                    blockId = $(this).closest('tr').data('block-id');
                }
                
                // Fallback 3: Try to get from edit form
                if (!blockId) {
                    blockId = $('#block-id').val();
                }
                
                // Fallback 4: Try to get from URL
                if (!blockId) {
                    const urlParams = new URLSearchParams(window.location.search);
                    blockId = urlParams.get('id');
                }
                
                const blockName = $(this).data('name') || $('#block-name').val() || 'Block';
                
                console.log('Opening features modal for block:', {id: blockId, name: blockName});
                
                if (blockId && blockId !== '0') {
                    self.openFeaturesModal(blockId, blockName);
                } else {
                    self.showMessage('⚠️ Bitte speichern Sie zuerst den Block, bevor Sie Features konfigurieren.', 'warning');
                }
            });
            
            // Modal close handlers
            $(document).on('click', '.cbd-modal-close, .cbd-modal-backdrop, #cbd-modal-cancel', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Form handlers
            $(document).on('submit', '#cbd-block-form', function(e) {
                e.preventDefault();
                self.saveBlock();
            });
            
            // Live Preview
            $(document).on('input change', '.cbd-form-field input, .cbd-form-field textarea, .cbd-form-field select', function() {
                self.updatePreview();
            });
            
            // Preview Modes - Korrigiert
            $(document).on('click', '.cbd-preview-mode', function(e) {
                e.preventDefault();
                self.switchPreviewMode($(this));
            });
            
            // Color pickers
            $(document).on('change', '.cbd-color-picker', function() {
                self.updateColorPreview($(this));
            });
            
            // Generate slug from name
            $(document).on('input', '#block-name', function() {
                self.generateSlug();
            });
            
            // Bulk actions
            $(document).on('change', '.cbd-block-checkbox, #cbd-select-all', function() {
                self.handleBulkSelection();
            });
            
            $(document).on('click', '#cbd-apply-bulk', function(e) {
                e.preventDefault();
                self.applyBulkAction();
            });
        },

        /**
         * Initialize color pickers
         */
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.cbd-color-picker').wpColorPicker({
                    change: function(event, ui) {
                        $(event.target).trigger('change');
                    }
                });
            }
        },

        /**
         * Initialize preview
         */
        initPreview: function() {
            this.updatePreview();
        },

        /**
         * Update live preview
         */
        updatePreview: function() {
            const $preview = $('.cbd-preview-content');
            if ($preview.length === 0) return;
            
            // Get values
            const name = $('#block-name').val() || 'Neuer Block';
            const paddingTop = $('#padding-top').val() || 20;
            const paddingRight = $('#padding-right').val() || 20;
            const paddingBottom = $('#padding-bottom').val() || 20;
            const paddingLeft = $('#padding-left').val() || 20;
            const bgColor = $('#background-color').val() || '#ffffff';
            const textColor = $('#text-color').val() || '#333333';
            const textAlign = $('#text-alignment').val() || 'left';
            const borderWidth = $('#border-width').val() || 0;
            const borderColor = $('#border-color').val() || '#dddddd';
            const borderRadius = $('#border-radius').val() || 0;
            
            // Update preview name
            $('.cbd-preview-name').text(name);
            
            // Update preview styles
            $preview.css({
                'padding': `${paddingTop}px ${paddingRight}px ${paddingBottom}px ${paddingLeft}px`,
                'background-color': bgColor,
                'color': textColor,
                'text-align': textAlign,
                'border': borderWidth > 0 ? `${borderWidth}px solid ${borderColor}` : 'none',
                'border-radius': `${borderRadius}px`
            });
            
            // Update slug preview
            const slug = $('#block-slug').val();
            if (slug) {
                $('.cbd-preview-slug code').text(slug);
            }
        },

        /**
         * Switch preview mode (Desktop/Tablet/Mobile)
         */
        switchPreviewMode: function($button) {
            const mode = $button.data('mode');
            
            // Update active state
            $('.cbd-preview-mode').removeClass('active');
            $button.addClass('active');
            
            // Update preview container
            $('.cbd-preview-container').attr('data-preview-mode', mode);
            
            // Apply responsive styles
            const $container = $('.cbd-preview-container');
            switch(mode) {
                case 'tablet':
                    $container.css('max-width', '768px');
                    break;
                case 'mobile':
                    $container.css('max-width', '375px');
                    break;
                default: // desktop
                    $container.css('max-width', '100%');
            }
        },

        /**
         * Generate slug from name
         */
        generateSlug: function() {
            const name = $('#block-name').val();
            if (!name) return;
            
            const slug = name
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            
            $('#block-slug').val(slug);
            this.updatePreview();
        },

        /**
         * Update color preview
         */
        updateColorPreview: function($input) {
            const color = $input.val();
            const $text = $input.siblings('.cbd-color-text');
            
            if ($text.length) {
                $text.val(color);
            }
            
            this.updatePreview();
        },

        /**
         * Open Features Modal
         */
        openFeaturesModal: function(blockId, blockName) {
            const self = this;
            
            console.log('📂 Opening Features Modal for Block:', {id: blockId, name: blockName});
            
            // Validate block ID
            if (!blockId || blockId === '0' || blockId === 0) {
                console.error('Invalid block ID:', blockId);
                self.showMessage('❌ Ungültige Block-ID. Bitte versuchen Sie es erneut.', 'error');
                return;
            }
            
            // Check if modal exists, if not create it
            let $modal = $('#cbd-features-modal');
            if ($modal.length === 0) {
                console.error('Features Modal not found, attempting to create...');
                // Trigger reload to ensure modal is created
                location.reload();
                return;
            }
            
            // WICHTIG: Block-ID ins Modal setzen BEVOR es angezeigt wird
            $('#features-block-id').val(blockId);
            console.log('Block ID set in modal:', $('#features-block-id').val());
            
            // Set block name in title
            $('.cbd-modal-title').text('Features für "' + blockName + '" konfigurieren');
            
            // Show modal
            $modal.fadeIn(200);
            
            // Load features for this block
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'cbd_get_features',
                    block_id: blockId,
                    nonce: cbdAdmin.nonce
                },
                success: function(response) {
                    console.log('Features loaded:', response);
                    
                    if (response.success && response.data) {
                        self.loadFeaturesIntoModal(response.data);
                    } else {
                        // Load default features for new blocks
                        console.log('Loading default features');
                        self.loadFeaturesIntoModal({
                            icon: { enabled: false, value: 'dashicons-admin-generic' },
                            collapse: { enabled: false, defaultState: 'expanded' },
                            numbering: { enabled: false, format: 'numeric' },
                            copyText: { enabled: false, buttonText: 'Text kopieren' },
                            screenshot: { enabled: false, buttonText: 'Screenshot' }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading features:', {status, error});
                    // Load defaults on error
                    self.loadFeaturesIntoModal({
                        icon: { enabled: false, value: 'dashicons-admin-generic' },
                        collapse: { enabled: false, defaultState: 'expanded' },
                        numbering: { enabled: false, format: 'numeric' },
                        copyText: { enabled: false, buttonText: 'Text kopieren' },
                        screenshot: { enabled: false, buttonText: 'Screenshot' }
                    });
                }
            });
        },

        /**
         * Load features into modal
         */
        loadFeaturesIntoModal: function(features) {
            // Reset all checkboxes and hide all settings
            $('#cbd-features-form input[type="checkbox"]').prop('checked', false);
            $('#cbd-features-form .cbd-feature-settings').hide();
            
            // Icon
            if (features.icon) {
                const iconEnabled = features.icon.enabled === true || features.icon.enabled === 'true';
                $('#feature-icon-enabled').prop('checked', iconEnabled);
                if (iconEnabled) {
                    $('#feature-icon-settings').show();
                    $('#block-icon-value').val(features.icon.value || 'dashicons-admin-generic');
                    $('.cbd-current-icon span').attr('class', 'dashicons ' + (features.icon.value || 'dashicons-admin-generic'));
                }
            }
            
            // Collapse
            if (features.collapse) {
                const collapseEnabled = features.collapse.enabled === true || features.collapse.enabled === 'true';
                $('#feature-collapse-enabled').prop('checked', collapseEnabled);
                if (collapseEnabled) {
                    $('#feature-collapse-settings').show();
                    $('#collapse-default-state').val(features.collapse.defaultState || 'expanded');
                }
            }
            
            // Numbering
            if (features.numbering) {
                const numberingEnabled = features.numbering.enabled === true || features.numbering.enabled === 'true';
                $('#feature-numbering-enabled').prop('checked', numberingEnabled);
                if (numberingEnabled) {
                    $('#feature-numbering-settings').show();
                    $('#numbering-format').val(features.numbering.format || 'numeric');
                }
            }
            
            // Copy Text
            if (features.copyText) {
                const copyEnabled = features.copyText.enabled === true || features.copyText.enabled === 'true';
                $('#feature-copy-enabled').prop('checked', copyEnabled);
                if (copyEnabled) {
                    $('#feature-copy-settings').show();
                    $('#copy-button-text').val(features.copyText.buttonText || 'Text kopieren');
                }
            }
            
            // Screenshot
            if (features.screenshot) {
                const screenshotEnabled = features.screenshot.enabled === true || features.screenshot.enabled === 'true';
                $('#feature-screenshot-enabled').prop('checked', screenshotEnabled);
                if (screenshotEnabled) {
                    $('#feature-screenshot-settings').show();
                    $('#screenshot-button-text').val(features.screenshot.buttonText || 'Screenshot');
                }
            }
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.cbd-modal').fadeOut(200);
        },

        /**
         * Delete block
         */
        deleteBlock: function(blockId) {
            if (!confirm(cbdAdmin.strings.confirmDelete)) {
                return;
            }
            
            const self = this;
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'cbd_delete_block',
                    block_id: blockId,
                    nonce: cbdAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Find and remove the correct row
                        const $row = $('.cbd-delete-btn[data-id="' + blockId + '"]').closest('tr');
                        
                        $row.fadeOut(400, function() {
                            $(this).remove();
                            
                            // Update statistics
                            self.updateBlockStatistics();
                            
                            // Check if table is empty
                            if ($('#cbd-blocks-tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                        
                        self.showMessage(cbdAdmin.strings.blockDeleted, 'success');
                    } else {
                        self.showMessage(response.data.message || cbdAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showMessage(cbdAdmin.strings.error, 'error');
                }
            });
        },
        
        /**
         * Update block statistics
         */
        updateBlockStatistics: function() {
            const totalBlocks = $('#cbd-blocks-tbody tr').length;
            let activeBlocks = 0;
            let inactiveBlocks = 0;
            
            $('#cbd-blocks-tbody tr').each(function() {
                const status = $(this).data('status');
                if (status === 'active') {
                    activeBlocks++;
                } else {
                    inactiveBlocks++;
                }
            });
            
            // Update statistics display
            $('.cbd-stat-item:eq(0) .cbd-stat-number').text(totalBlocks);
            $('.cbd-stat-item:eq(1) .cbd-stat-number').text(activeBlocks);
            $('.cbd-stat-item:eq(2) .cbd-stat-number').text(inactiveBlocks);
        },

        /**
         * Toggle block status
         */
        toggleStatus: function(blockId) {
            const self = this;
            const $button = $('.cbd-toggle-status-btn[data-id="' + blockId + '"]');
            const currentStatus = $button.data('status');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'cbd_toggle_status',
                    block_id: blockId,
                    status: newStatus,
                    nonce: cbdAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update button
                        $button.data('status', newStatus);
                        $button.text(newStatus === 'active' ? cbdAdmin.strings.deactivate : cbdAdmin.strings.activate);
                        
                        // Update status badge
                        const $badge = $button.closest('tr').find('.cbd-status-badge');
                        $badge.removeClass('status-active status-inactive').addClass('status-' + newStatus);
                        $badge.text(newStatus === 'active' ? cbdAdmin.strings.active : cbdAdmin.strings.inactive);
                        
                        self.showMessage('Status aktualisiert', 'success');
                    } else {
                        self.showMessage(response.data.message || cbdAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showMessage(cbdAdmin.strings.error, 'error');
                }
            });
        },

        /**
         * Duplicate block
         */
        duplicateBlock: function(blockId) {
            const self = this;
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'cbd_duplicate_block',
                    block_id: blockId,
                    nonce: cbdAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Block dupliziert', 'success');
                        // Reload page to show new block
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showMessage(response.data.message || cbdAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showMessage(cbdAdmin.strings.error, 'error');
                }
            });
        },

        /**
         * Save block
         */
        saveBlock: function() {
            const self = this;
            const $form = $('#cbd-block-form');
            
            // Get form values
            const name = $('#block-name').val();
            const slug = $('#block-slug').val();
            const description = $('#block-description').val() || '';
            const status = $('#block-status').val() || 'active';
            
            // Validate
            if (!name || !slug) {
                self.showMessage('Name und Slug sind erforderlich', 'error');
                return;
            }
            
            // Collect config data
            const config = {
                styles: {
                    padding: {
                        top: $('#padding-top').val() || 0,
                        right: $('#padding-right').val() || 0,
                        bottom: $('#padding-bottom').val() || 0,
                        left: $('#padding-left').val() || 0
                    },
                    margin: {
                        top: $('#margin-top').val() || 0,
                        right: $('#margin-right').val() || 0,
                        bottom: $('#margin-bottom').val() || 0,
                        left: $('#margin-left').val() || 0
                    },
                    background: {
                        color: $('#background-color').val() || '#ffffff'
                    },
                    text: {
                        color: $('#text-color').val() || '#333333',
                        alignment: $('#text-alignment').val() || 'left'
                    },
                    border: {
                        width: $('#border-width').val() || 0,
                        color: $('#border-color').val() || '#dddddd',
                        radius: $('#border-radius').val() || 0
                    }
                }
            };
            
            // Check if we're updating or creating
            const blockId = $('#block-id').val();
            const action = blockId ? 'cbd_update_block' : 'cbd_save_block';
            
            // Prepare data
            const data = {
                action: action,
                name: name,
                slug: slug,
                description: description,
                status: status,
                config: JSON.stringify(config),
                nonce: cbdAdmin.nonce
            };
            
            // Add block_id for updates
            if (blockId) {
                data.block_id = blockId;
            }
            
            // Show loading
            const $button = $('#cbd-save-block');
            const originalText = $button.text();
            $button.text(cbdAdmin.strings.saving).prop('disabled', true);
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.showMessage(cbdAdmin.strings.saved, 'success');
                        // Redirect to blocks list after save
                        setTimeout(function() {
                            window.location.href = cbdAdmin.blocksListUrl;
                        }, 1000);
                    } else {
                        self.showMessage(response.data.message || cbdAdmin.strings.error, 'error');
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    self.showMessage(cbdAdmin.strings.error, 'error');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            // Remove existing messages
            $('.cbd-notice').remove();
            
            const $notice = $('<div class="cbd-notice notice notice-' + type + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss</span>' +
                '</button>' +
                '</div>');
            
            $('.cbd-admin-content').prepend($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Handle dismiss button
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(400, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Initialize bulk actions
         */
        initBulkActions: function() {
            // Select all checkbox
            $('#cbd-select-all').on('change', function() {
                $('.cbd-block-checkbox').prop('checked', $(this).prop('checked'));
            });
        },

        /**
         * Handle bulk selection
         */
        handleBulkSelection: function() {
            const checkedCount = $('.cbd-block-checkbox:checked').length;
            const totalCount = $('.cbd-block-checkbox').length;
            
            // Update select all checkbox
            $('#cbd-select-all').prop('checked', checkedCount === totalCount && totalCount > 0);
        },

        /**
         * Apply bulk action
         */
        applyBulkAction: function() {
            const action = $('#bulk-action-selector').val();
            const selectedIds = [];
            
            $('.cbd-block-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                this.showMessage('Bitte wählen Sie mindestens einen Block aus', 'warning');
                return;
            }
            
            if (!action) {
                this.showMessage('Bitte wählen Sie eine Aktion aus', 'warning');
                return;
            }
            
            const self = this;
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'cbd_bulk_action',
                    bulk_action: action,
                    block_ids: selectedIds,
                    nonce: cbdAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showMessage(response.data.message || cbdAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showMessage(cbdAdmin.strings.error, 'error');
                }
            });
        },

        /**
         * Initialize search
         */
        initSearch: function() {
            $('#cbd-search-blocks').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.cbd-blocks-tbody tr').each(function() {
                    const $row = $(this);
                    const name = $row.find('.column-name').text().toLowerCase();
                    const slug = $row.find('.column-slug').text().toLowerCase();
                    const description = $row.find('.column-description').text().toLowerCase();
                    
                    if (name.includes(searchTerm) || slug.includes(searchTerm) || description.includes(searchTerm)) {
                        $row.show();
                    } else {
                        $row.hide();
                    }
                });
            });
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        CBDAdmin.init();
    });

    // Export for global access
    window.CBDAdmin = CBDAdmin;

})(jQuery);