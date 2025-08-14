/**
 * Container Block Designer - Admin JavaScript
 * Version: 2.2.2 - Bugfix Release
 * Fixed: Event handler bindings for save, edit, delete, duplicate actions
 */

(function($) {
    'use strict';

    const CBDAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            console.log('🚀 CBD Admin initialization starting...');
            
            // Check if jQuery is loaded
            if (typeof $ === 'undefined') {
                console.error('❌ jQuery is not loaded!');
                return;
            }
            
            // Check if cbdAdmin localization exists
            if (typeof cbdAdmin === 'undefined') {
                console.warn('⚠️ cbdAdmin localization object not found. Creating fallback...');
                
                // Try to get ajaxurl from different sources
                let ajaxUrl = '';
                if (typeof ajaxurl !== 'undefined') {
                    ajaxUrl = ajaxurl;
                } else if (typeof wp !== 'undefined' && wp.ajax && wp.ajax.settings && wp.ajax.settings.url) {
                    ajaxUrl = wp.ajax.settings.url;
                } else {
                    ajaxUrl = '/wp-admin/admin-ajax.php';
                }
                
                window.cbdAdmin = {
                    ajaxUrl: ajaxUrl,
                    nonce: $('#cbd_nonce').val() || $('input[name="cbd_nonce"]').val() || '',
                    strings: {
                        confirmDelete: 'Sind Sie sicher, dass Sie diesen Block löschen möchten?',
                        saving: 'Speichern...',
                        saved: 'Gespeichert!',
                        error: 'Ein Fehler ist aufgetreten.',
                        active: 'Aktiv',
                        inactive: 'Inaktiv',
                        activate: 'Aktivieren',
                        deactivate: 'Deaktivieren'
                    },
                    blocksListUrl: window.location.origin + '/wp-admin/admin.php?page=container-block-designer'
                };
            }
            
            this.bindEvents();
            this.initColorPickers();
            this.initPreview();
            this.initBulkActions();
            this.initSearch();
            
            console.log('✅ CBD Admin initialized successfully');
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            const self = this;
            console.log('📌 Binding event handlers...');
            
            // SAVE BUTTON - FIX für neuen Block
            $(document).on('click', '#cbd-save-block, button[type="submit"][name="cbd-save-block"]', function(e) {
                e.preventDefault();
                console.log('💾 Save button clicked');
                self.saveBlock();
            });
            
            // Alternative für Forms
            $(document).on('submit', '#cbd-block-form, form.cbd-block-form', function(e) {
                e.preventDefault();
                console.log('📝 Form submitted');
                self.saveBlock();
            });
            
            // DELETE - mit verbesserter Selektor-Kompatibilität
            $(document).on('click', '.cbd-delete-btn, a[data-action="delete"]', function(e) {
                e.preventDefault();
                const blockId = $(this).data('id') || $(this).data('block-id');
                console.log('🗑️ Delete button clicked for block:', blockId);
                if (blockId) {
                    self.deleteBlock(blockId);
                }
            });
            
            // TOGGLE STATUS
            $(document).on('click', '.cbd-toggle-status-btn, a[data-action="toggle-status"]', function(e) {
                e.preventDefault();
                const blockId = $(this).data('id') || $(this).data('block-id');
                console.log('🔄 Toggle status clicked for block:', blockId);
                if (blockId) {
                    self.toggleStatus(blockId);
                }
            });
            
            // DUPLICATE
            $(document).on('click', '.cbd-duplicate-btn, a[data-action="duplicate"]', function(e) {
                e.preventDefault();
                const blockId = $(this).data('id') || $(this).data('block-id');
                console.log('📋 Duplicate clicked for block:', blockId);
                if (blockId) {
                    self.duplicateBlock(blockId);
                }
            });
            
            // EDIT - direkte Navigation
            $(document).on('click', '.cbd-edit-btn, a[data-action="edit"]', function(e) {
                // Lass den normalen Link funktionieren, aber log es
                console.log('✏️ Edit button clicked');
            });
            
            // Features modal
            $(document).on('click', '.cbd-configure-features, .cbd-features-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                let blockId = $(this).data('id') || $(this).data('block-id') || 
                             $(this).closest('tr').data('block-id') || 
                             $('#block-id').val();
                
                const blockName = $(this).data('name') || $('#block-name').val() || 'Block';
                
                console.log('⚙️ Opening features modal for block:', {id: blockId, name: blockName});
                
                if (blockId && blockId !== '0') {
                    self.openFeaturesModal(blockId, blockName);
                } else {
                    self.showMessage('⚠️ Bitte speichern Sie zuerst den Block, bevor Sie Features konfigurieren.', 'warning');
                }
            });
            
            // Live Preview
            $(document).on('input change', '.cbd-form-field input, .cbd-form-field textarea, .cbd-form-field select', function() {
                self.updatePreview();
            });
            
            // Generate slug from name
            $(document).on('input', '#block-name', function() {
                self.generateSlug();
            });
            
            console.log('✅ Event handlers bound successfully');
        },

        /**
         * Save block (create or update)
         */
        saveBlock: function() {
            const self = this;
            console.log('💾 Starting save process...');
            
            // Get form values
            const name = $('#block-name').val();
            const slug = $('#block-slug').val();
            const description = $('#block-description').val() || '';
            const status = $('#block-status').val() || 'active';
            
            // Validate
            if (!name || !slug) {
                self.showMessage('❌ Name und Slug sind erforderlich', 'error');
                return;
            }
            
            // Collect styles data - mit Fallbacks
            const styles = {
                padding: {
                    top: $('#padding-top').val() || 20,
                    right: $('#padding-right').val() || 20,
                    bottom: $('#padding-bottom').val() || 20,
                    left: $('#padding-left').val() || 20
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
            };
            
            // Check if we're updating or creating
            const blockId = $('#block-id').val();
            const action = blockId ? 'cbd_update_block' : 'cbd_save_block';
            
            console.log('📦 Preparing data:', {action, blockId, name, slug});
            
            // Prepare data
            const data = {
                action: action,
                name: name,
                slug: slug,
                description: description,
                status: status,
                styles: styles,
                nonce: cbdAdmin.nonce || $('#cbd_nonce').val() || ''
            };
            
            // Add block_id for updates
            if (blockId) {
                data.block_id = blockId;
            }
            
            // Show loading
            const $button = $('#cbd-save-block, button[type="submit"]').first();
            const originalText = $button.text();
            $button.text(cbdAdmin.strings?.saving || 'Speichern...').prop('disabled', true);
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: data,
                success: function(response) {
                    console.log('✅ Save response:', response);
                    if (response.success) {
                        self.showMessage('✅ ' + (cbdAdmin.strings?.saved || 'Gespeichert!'), 'success');
                        // Redirect to blocks list after save
                        setTimeout(function() {
                            window.location.href = cbdAdmin.blocksListUrl;
                        }, 1000);
                    } else {
                        self.showMessage('❌ ' + (response.data?.message || cbdAdmin.strings?.error || 'Fehler'), 'error');
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Save error:', {xhr, status, error});
                    self.showMessage('❌ ' + (cbdAdmin.strings?.error || 'Ein Fehler ist aufgetreten'), 'error');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Delete block
         */
        deleteBlock: function(blockId) {
            if (!confirm(cbdAdmin.strings?.confirmDelete || 'Wirklich löschen?')) {
                return;
            }
            
            const self = this;
            console.log('🗑️ Deleting block:', blockId);
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'cbd_delete_block',
                    block_id: blockId,
                    nonce: cbdAdmin.nonce || ''
                },
                success: function(response) {
                    console.log('✅ Delete response:', response);
                    if (response.success) {
                        // Remove the row from table
                        $('tr[data-block-id="' + blockId + '"]').fadeOut(400, function() {
                            $(this).remove();
                        });
                        // Also try alternative selectors
                        $('.cbd-delete-btn[data-id="' + blockId + '"]').closest('tr').fadeOut(400, function() {
                            $(this).remove();
                        });
                        self.showMessage('✅ Block gelöscht', 'success');
                    } else {
                        self.showMessage('❌ ' + (response.data?.message || 'Fehler beim Löschen'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Delete error:', {xhr, status, error});
                    self.showMessage('❌ Fehler beim Löschen', 'error');
                }
            });
        },

        /**
         * Toggle block status
         */
        toggleStatus: function(blockId) {
            const self = this;
            const $button = $('.cbd-toggle-status-btn[data-id="' + blockId + '"]');
            const currentStatus = $button.data('status') || 'active';
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            console.log('🔄 Toggling status:', {blockId, from: currentStatus, to: newStatus});
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'cbd_toggle_status',
                    block_id: blockId,
                    status: newStatus,
                    nonce: cbdAdmin.nonce || ''
                },
                success: function(response) {
                    console.log('✅ Toggle response:', response);
                    if (response.success) {
                        // Update button
                        $button.data('status', newStatus);
                        $button.text(newStatus === 'active' ? 'Deaktivieren' : 'Aktivieren');
                        
                        // Update status badge if exists
                        const $badge = $button.closest('tr').find('.cbd-status-badge');
                        $badge.removeClass('status-active status-inactive').addClass('status-' + newStatus);
                        $badge.text(newStatus === 'active' ? 'Aktiv' : 'Inaktiv');
                        
                        self.showMessage('✅ Status aktualisiert', 'success');
                    } else {
                        self.showMessage('❌ ' + (response.data?.message || 'Fehler'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Toggle error:', {xhr, status, error});
                    self.showMessage('❌ Fehler beim Statuswechsel', 'error');
                }
            });
        },

        /**
         * Duplicate block
         */
        duplicateBlock: function(blockId) {
            const self = this;
            console.log('📋 Duplicating block:', blockId);
            
            $.ajax({
                url: cbdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'cbd_duplicate_block',
                    block_id: blockId,
                    nonce: cbdAdmin.nonce || ''
                },
                success: function(response) {
                    console.log('✅ Duplicate response:', response);
                    if (response.success) {
                        self.showMessage('✅ Block dupliziert', 'success');
                        // Reload page to show new block
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showMessage('❌ ' + (response.data?.message || 'Fehler'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Duplicate error:', {xhr, status, error});
                    self.showMessage('❌ Fehler beim Duplizieren', 'error');
                }
            });
        },

        /**
         * Open Features Modal
         */
        openFeaturesModal: function(blockId, blockName) {
            console.log('⚙️ Opening features modal:', {blockId, blockName});
            
            // Trigger the features modal from admin-features.js
            if (typeof CBDFeatures !== 'undefined' && CBDFeatures.openFeaturesModal) {
                CBDFeatures.openFeaturesModal(blockId, blockName);
            } else {
                console.warn('⚠️ CBDFeatures not available');
                this.showMessage('⚠️ Features-Modul nicht geladen', 'warning');
            }
        },

        /**
         * Show message
         */
        showMessage: function(message, type = 'info') {
            // Remove existing messages
            $('.cbd-notice').remove();
            
            const $notice = $('<div class="cbd-notice notice notice-' + type + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss</span>' +
                '</button>' +
                '</div>');
            
            // Try different container selectors
            const $container = $('.cbd-admin-content, .wrap').first();
            if ($container.length) {
                $container.prepend($notice);
            } else {
                $('body').prepend($notice);
            }
            
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
         * Initialize color pickers
         */
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.cbd-color-picker').wpColorPicker({
                    change: function(event, ui) {
                        $(event.target).trigger('change');
                    }
                });
                console.log('🎨 Color pickers initialized');
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
            const $preview = $('.cbd-preview-content, .cbd-container-preview').first();
            if ($preview.length === 0) return;
            
            // Get values
            const padding = {
                top: $('#padding-top').val() || 20,
                right: $('#padding-right').val() || 20,
                bottom: $('#padding-bottom').val() || 20,
                left: $('#padding-left').val() || 20
            };
            
            const styles = {
                paddingTop: padding.top + 'px',
                paddingRight: padding.right + 'px',
                paddingBottom: padding.bottom + 'px',
                paddingLeft: padding.left + 'px',
                backgroundColor: $('#background-color').val() || '#ffffff',
                color: $('#text-color').val() || '#333333',
                textAlign: $('#text-alignment').val() || 'left',
                borderWidth: ($('#border-width').val() || 0) + 'px',
                borderStyle: 'solid',
                borderColor: $('#border-color').val() || '#dddddd',
                borderRadius: ($('#border-radius').val() || 0) + 'px'
            };
            
            // Apply styles
            $preview.css(styles);
        },

        /**
         * Generate slug from name
         */
        generateSlug: function() {
            const $name = $('#block-name');
            const $slug = $('#block-slug');
            
            // Only generate if slug is empty
            if (!$slug.val()) {
                const name = $name.val();
                const slug = name.toLowerCase()
                    .replace(/[äöü]/g, function(match) {
                        return {'ä': 'ae', 'ö': 'oe', 'ü': 'ue'}[match];
                    })
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                
                $slug.val(slug);
            }
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
         * Initialize search
         */
        initSearch: function() {
            $('#cbd-search-blocks').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.cbd-blocks-tbody tr, tbody tr').each(function() {
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

    // Initialize on DOM ready with multiple fallbacks
    $(document).ready(function() {
        console.log('📄 DOM ready - initializing CBD Admin...');
        CBDAdmin.init();
    });
    
    // Fallback for late loading
    $(window).on('load', function() {
        if (!window.CBDAdminInitialized) {
            console.log('📄 Window load - initializing CBD Admin (fallback)...');
            CBDAdmin.init();
            window.CBDAdminInitialized = true;
        }
    });

    // Export for global access
    window.CBDAdmin = CBDAdmin;

})(jQuery);