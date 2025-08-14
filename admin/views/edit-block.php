<?php
/**
 * Edit Block View
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get block ID
$block_id = isset($_GET['block_id']) ? intval($_GET['block_id']) : 0;

if (!$block_id) {
    wp_die(__('Ungültige Block-ID.', 'container-block-designer'));
}

// Get block data
global $wpdb;
$block = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d", $block_id));

if (!$block) {
    wp_die(__('Block nicht gefunden.', 'container-block-designer'));
}

// Parse config and features
$config = json_decode($block->config, true) ?: [];
$styles = $config['styles'] ?? [];
$features = json_decode($block->features, true) ?: [];

// Helper function to get style value
function get_style_value($styles, $path, $default = '') {
    $keys = explode('.', $path);
    $value = $styles;
    
    foreach ($keys as $key) {
        if (isset($value[$key])) {
            $value = $value[$key];
        } else {
            return $default;
        }
    }
    
    return $value;
}

// Count active features
$active_features = [];
if (!empty($features['icon']) && $features['icon']['enabled']) $active_features[] = 'Block-Icon';
if (!empty($features['collapse']) && $features['collapse']['enabled']) $active_features[] = 'Ein-/Ausklappbar';
if (!empty($features['numbering']) && $features['numbering']['enabled']) $active_features[] = 'Nummerierung';
if (!empty($features['copyText']) && $features['copyText']['enabled']) $active_features[] = 'Text kopieren';
if (!empty($features['screenshot']) && $features['screenshot']['enabled']) $active_features[] = 'Screenshot';
?>

<div class="wrap cbd-admin-wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Block bearbeiten', 'container-block-designer'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=container-block-designer'); ?>" class="page-title-action">
        <?php echo esc_html__('← Zurück zur Übersicht', 'container-block-designer'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['saved'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Block wurde erfolgreich gespeichert.', 'container-block-designer'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="cbd-edit-container">
        <div class="cbd-edit-main">
            <form id="cbd-block-form" method="post">
                <input type="hidden" id="block-id" value="<?php echo esc_attr($block->id); ?>">
                
                <!-- Block Info Card -->
                <div class="cbd-card">
                    <div class="cbd-card-header">
                        <h2><?php echo esc_html__('Grundinformationen', 'container-block-designer'); ?></h2>
                    </div>
                    <div class="cbd-card-body">
                        <div class="cbd-form-grid">
                            <div class="cbd-form-field">
                                <label for="block-name"><?php echo esc_html__('Block-Name', 'container-block-designer'); ?> *</label>
                                <input type="text" id="block-name" name="block-name" value="<?php echo esc_attr($block->name); ?>" required>
                                <p class="description"><?php echo esc_html__('Anzeigename des Blocks im Gutenberg Editor', 'container-block-designer'); ?></p>
                            </div>
                            
                            <div class="cbd-form-field">
                                <label for="block-slug"><?php echo esc_html__('Block-Slug', 'container-block-designer'); ?> *</label>
                                <input type="text" id="block-slug" name="block-slug" value="<?php echo esc_attr($block->slug); ?>" required>
                                <p class="description"><?php echo esc_html__('Eindeutige Kennung (wird automatisch generiert)', 'container-block-designer'); ?></p>
                            </div>
                        </div>
                        
                        <div class="cbd-form-field">
                            <label for="block-description"><?php echo esc_html__('Beschreibung', 'container-block-designer'); ?></label>
                            <textarea id="block-description" name="block-description" rows="3"><?php echo esc_textarea($block->description); ?></textarea>
                            <p class="description"><?php echo esc_html__('Kurze Beschreibung des Block-Zwecks', 'container-block-designer'); ?></p>
                        </div>
                        
                        <div class="cbd-form-field">
                            <label><?php echo esc_html__('Status', 'container-block-designer'); ?></label>
                            <label class="cbd-switch">
                                <input type="checkbox" id="block-status" name="block-status" <?php checked($block->status, 'active'); ?>>
                                <span class="cbd-switch-slider"></span>
                                <span class="cbd-switch-label"><?php echo esc_html__('Block ist aktiv', 'container-block-designer'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Features Card -->
                <div class="cbd-card">
                    <div class="cbd-card-header">
                        <h2><?php echo esc_html__('Container-Features', 'container-block-designer'); ?></h2>
                        <button type="button" class="button cbd-features-btn" data-block-id="<?php echo esc_attr($block->id); ?>" data-block-name="<?php echo esc_attr($block->name); ?>">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php echo esc_html__('Features konfigurieren', 'container-block-designer'); ?>
                        </button>
                    </div>
                    <div class="cbd-card-body">
                        <?php if (empty($active_features)) : ?>
                            <p class="cbd-no-features">
                                <?php echo esc_html__('Keine Features aktiviert.', 'container-block-designer'); ?>
                                <br>
                                <small><?php echo esc_html__('Klicken Sie auf "Features konfigurieren", um Features hinzuzufügen.', 'container-block-designer'); ?></small>
                            </p>
                        <?php else : ?>
                            <div class="cbd-active-features">
                                <p><strong><?php echo esc_html__('Aktive Features:', 'container-block-designer'); ?></strong></p>
                                <div class="cbd-features-badges">
                                    <?php foreach ($active_features as $feature) : ?>
                                        <span class="cbd-feature-badge"><?php echo esc_html($feature); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Styles Configuration -->
                <div class="cbd-card">
                    <div class="cbd-card-header">
                        <h2><?php echo esc_html__('Container-Stile', 'container-block-designer'); ?></h2>
                    </div>
                    <div class="cbd-card-body">
                        <!-- Padding -->
                        <div class="cbd-style-section">
                            <h4><?php echo esc_html__('Innenabstand (Padding)', 'container-block-designer'); ?></h4>
                            <div class="cbd-spacing-controls">
                                <div class="cbd-spacing-field">
                                    <label><?php echo esc_html__('Oben', 'container-block-designer'); ?></label>
                                    <input type="number" name="padding-top" value="<?php echo esc_attr(get_style_value($styles, 'padding.top', 20)); ?>" min="0">
                                    <span>px</span>
                                </div>
                                <div class="cbd-spacing-field">
                                    <label><?php echo esc_html__('Rechts', 'container-block-designer'); ?></label>
                                    <input type="number" name="padding-right" value="<?php echo esc_attr(get_style_value($styles, 'padding.right', 20)); ?>" min="0">
                                    <span>px</span>
                                </div>
                                <div class="cbd-spacing-field">
                                    <label><?php echo esc_html__('Unten', 'container-block-designer'); ?></label>
                                    <input type="number" name="padding-bottom" value="<?php echo esc_attr(get_style_value($styles, 'padding.bottom', 20)); ?>" min="0">
                                    <span>px</span>
                                </div>
                                <div class="cbd-spacing-field">
                                    <label><?php echo esc_html__('Links', 'container-block-designer'); ?></label>
                                    <input type="number" name="padding-left" value="<?php echo esc_attr(get_style_value($styles, 'padding.left', 20)); ?>" min="0">
                                    <span>px</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Background -->
                        <div class="cbd-style-section">
                            <h4><?php echo esc_html__('Hintergrund', 'container-block-designer'); ?></h4>
                            <div class="cbd-form-field">
                                <label><?php echo esc_html__('Hintergrundfarbe', 'container-block-designer'); ?></label>
                                <input type="text" class="cbd-color-picker" name="background-color" value="<?php echo esc_attr(get_style_value($styles, 'background.color', '#ffffff')); ?>">
                            </div>
                        </div>
                        
                        <!-- Text -->
                        <div class="cbd-style-section">
                            <h4><?php echo esc_html__('Text', 'container-block-designer'); ?></h4>
                            <div class="cbd-form-grid">
                                <div class="cbd-form-field">
                                    <label><?php echo esc_html__('Textfarbe', 'container-block-designer'); ?></label>
                                    <input type="text" class="cbd-color-picker" name="text-color" value="<?php echo esc_attr(get_style_value($styles, 'text.color', '#000000')); ?>">
                                </div>
                                <div class="cbd-form-field">
                                    <label><?php echo esc_html__('Textausrichtung', 'container-block-designer'); ?></label>
                                    <select name="text-alignment">
                                        <option value="left" <?php selected(get_style_value($styles, 'text.alignment', 'left'), 'left'); ?>><?php echo esc_html__('Links', 'container-block-designer'); ?></option>
                                        <option value="center" <?php selected(get_style_value($styles, 'text.alignment'), 'center'); ?>><?php echo esc_html__('Zentriert', 'container-block-designer'); ?></option>
                                        <option value="right" <?php selected(get_style_value($styles, 'text.alignment'), 'right'); ?>><?php echo esc_html__('Rechts', 'container-block-designer'); ?></option>
                                        <option value="justify" <?php selected(get_style_value($styles, 'text.alignment'), 'justify'); ?>><?php echo esc_html__('Blocksatz', 'container-block-designer'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Border -->
                        <div class="cbd-style-section">
                            <h4><?php echo esc_html__('Rahmen', 'container-block-designer'); ?></h4>
                            <div class="cbd-form-grid">
                                <div class="cbd-form-field">
                                    <label><?php echo esc_html__('Rahmenbreite', 'container-block-designer'); ?></label>
                                    <div class="cbd-input-group">
                                        <input type="number" name="border-width" value="<?php echo esc_attr(get_style_value($styles, 'border.width', 0)); ?>" min="0">
                                        <span>px</span>
                                    </div>
                                </div>
                                <div class="cbd-form-field">
                                    <label><?php echo esc_html__('Rahmenfarbe', 'container-block-designer'); ?></label>
                                    <input type="text" class="cbd-color-picker" name="border-color" value="<?php echo esc_attr(get_style_value($styles, 'border.color', '#dddddd')); ?>">
                                </div>
                                <div class="cbd-form-field">
                                    <label><?php echo esc_html__('Eckenradius', 'container-block-designer'); ?></label>
                                    <div class="cbd-input-group">
                                        <input type="number" name="border-radius" value="<?php echo esc_attr(get_style_value($styles, 'border.radius', 0)); ?>" min="0">
                                        <span>px</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="cbd-form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <?php echo esc_html__('Änderungen speichern', 'container-block-designer'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=container-block-designer'); ?>" class="button button-large">
                        <?php echo esc_html__('Abbrechen', 'container-block-designer'); ?>
                    </a>
                    <span class="cbd-save-status"></span>
                </div>
            </form>
        </div>
        
        <!-- Sidebar Preview -->
        <div class="cbd-edit-sidebar">
            <div class="cbd-card">
                <div class="cbd-card-header">
                    <h3><?php echo esc_html__('Vorschau', 'container-block-designer'); ?></h3>
                </div>
                <div class="cbd-card-body">
                    <div class="cbd-preview-container">
                        <div class="cbd-preview-frame" id="cbd-preview">
                            <div class="cbd-container-preview" style="
                                padding: <?php echo esc_attr(get_style_value($styles, 'padding.top', 20)); ?>px <?php echo esc_attr(get_style_value($styles, 'padding.right', 20)); ?>px <?php echo esc_attr(get_style_value($styles, 'padding.bottom', 20)); ?>px <?php echo esc_attr(get_style_value($styles, 'padding.left', 20)); ?>px;
                                background-color: <?php echo esc_attr(get_style_value($styles, 'background.color', '#ffffff')); ?>;
                                color: <?php echo esc_attr(get_style_value($styles, 'text.color', '#000000')); ?>;
                                text-align: <?php echo esc_attr(get_style_value($styles, 'text.alignment', 'left')); ?>;
                                border: <?php echo esc_attr(get_style_value($styles, 'border.width', 0)); ?>px solid <?php echo esc_attr(get_style_value($styles, 'border.color', '#dddddd')); ?>;
                                border-radius: <?php echo esc_attr(get_style_value($styles, 'border.radius', 0)); ?>px;
                            ">
                                <?php if (!empty($features['icon']) && $features['icon']['enabled']) : ?>
                                    <div class="cbd-preview-icon">
                                        <span class="dashicons <?php echo esc_attr($features['icon']['value'] ?? 'dashicons-admin-generic'); ?>"></span>
                                    </div>
                                <?php endif; ?>
                                
                                <h3><?php echo esc_html($block->name); ?></h3>
                                <p>Dies ist eine Beispiel-Vorschau Ihres Container-Blocks.</p>
                                <p>Der Inhalt hier zeigt, wie Ihr Block aussehen wird.</p>
                                
                                <?php if (!empty($active_features)) : ?>
                                    <div class="cbd-preview-features">
                                        <?php if (!empty($features['copyText']) && $features['copyText']['enabled']) : ?>
                                            <button class="button button-small"><?php echo esc_html($features['copyText']['buttonText'] ?? 'Text kopieren'); ?></button>
                                        <?php endif; ?>
                                        <?php if (!empty($features['screenshot']) && $features['screenshot']['enabled']) : ?>
                                            <button class="button button-small"><?php echo esc_html($features['screenshot']['buttonText'] ?? 'Screenshot'); ?></button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cbd-preview-info">
                        <p><strong><?php echo esc_html__('Block-Slug:', 'container-block-designer'); ?></strong> <code><?php echo esc_html($block->slug); ?></code></p>
                        <?php if (!empty($active_features)) : ?>
                            <p><strong><?php echo esc_html__('Aktive Features:', 'container-block-designer'); ?></strong> <?php echo count($active_features); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for edit page */
.cbd-edit-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.cbd-edit-main {
    flex: 1;
    min-width: 0;
}

.cbd-edit-sidebar {
    width: 350px;
    flex-shrink: 0;
}

.cbd-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}

.cbd-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cbd-card-header h2,
.cbd-card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.cbd-card-body {
    padding: 20px;
}

.cbd-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.cbd-form-field {
    margin-bottom: 20px;
}

.cbd-form-field:last-child {
    margin-bottom: 0;
}

.cbd-form-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 13px;
}

.cbd-form-field input[type="text"],
.cbd-form-field input[type="number"],
.cbd-form-field textarea,
.cbd-form-field select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.cbd-form-field .description {
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.cbd-style-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e0e0e0;
}

.cbd-style-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.cbd-style-section h4 {
    margin: 0 0 15px 0;
    font-size: 14px;
    font-weight: 600;
}

.cbd-spacing-controls {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.cbd-spacing-field {
    display: flex;
    flex-direction: column;
}

.cbd-spacing-field label {
    font-size: 12px;
    margin-bottom: 5px;
}

.cbd-spacing-field input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.cbd-spacing-field span {
    margin-top: 5px;
    font-size: 11px;
    color: #666;
}

.cbd-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cbd-input-group input {
    flex: 1;
}

.cbd-input-group span {
    color: #666;
    font-size: 13px;
}

.cbd-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}

.cbd-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.cbd-switch-slider {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    background-color: #ccc;
    border-radius: 24px;
    transition: 0.3s;
    margin-right: 10px;
}

.cbd-switch-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.3s;
}

.cbd-switch input:checked + .cbd-switch-slider {
    background-color: #2271b1;
}

.cbd-switch input:checked + .cbd-switch-slider:before {
    transform: translateX(20px);
}

.cbd-switch-label {
    font-size: 14px;
}

.cbd-preview-container {
    background: #f0f0f1;
    border-radius: 4px;
    padding: 20px;
    min-height: 200px;
}

.cbd-container-preview {
    background: #fff;
    min-height: 150px;
    position: relative;
}

.cbd-preview-icon {
    margin-bottom: 10px;
}

.cbd-preview-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.cbd-preview-features {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.cbd-preview-info {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

.cbd-preview-info p {
    margin: 5px 0;
    font-size: 12px;
}

.cbd-preview-info code {
    background: #f0f0f1;
    padding: 2px 5px;
    border-radius: 3px;
}

.cbd-no-features {
    color: #666;
    font-style: italic;
}

.cbd-no-features small {
    display: block;
    margin-top: 5px;
    color: #999;
}

.cbd-active-features p {
    margin: 0 0 10px 0;
}

.cbd-form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.cbd-save-status {
    margin-left: 15px;
    color: #00a32a;
    font-weight: 500;
    display: none;
}

.cbd-save-status.show {
    display: inline-block;
}

@media (max-width: 1200px) {
    .cbd-edit-container {
        flex-direction: column;
    }
    
    .cbd-edit-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .cbd-form-grid {
        grid-template-columns: 1fr;
    }
    
    .cbd-spacing-controls {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize color pickers
    $('.cbd-color-picker').wpColorPicker({
        change: function(event, ui) {
            updatePreview();
        }
    });
    
    // Live preview update
    function updatePreview() {
        const $preview = $('.cbd-container-preview');
        
        // Update padding
        const paddingTop = $('input[name="padding-top"]').val();
        const paddingRight = $('input[name="padding-right"]').val();
        const paddingBottom = $('input[name="padding-bottom"]').val();
        const paddingLeft = $('input[name="padding-left"]').val();
        
        // Update colors
        const bgColor = $('input[name="background-color"]').val();
        const textColor = $('input[name="text-color"]').val();
        const textAlign = $('select[name="text-alignment"]').val();
        
        // Update border
        const borderWidth = $('input[name="border-width"]').val();
        const borderColor = $('input[name="border-color"]').val();
        const borderRadius = $('input[name="border-radius"]').val();
        
        $preview.css({
            'padding': `${paddingTop}px ${paddingRight}px ${paddingBottom}px ${paddingLeft}px`,
            'background-color': bgColor,
            'color': textColor,
            'text-align': textAlign,
            'border': `${borderWidth}px solid ${borderColor}`,
            'border-radius': `${borderRadius}px`
        });
    }
    
    // Bind preview updates
    $('input[type="number"], select').on('change input', updatePreview);
    
    // Auto-generate slug from name
    $('#block-name').on('input', function() {
        const name = $(this).val();
        const slug = name.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
        
        if ($('#block-slug').data('manual') !== true) {
            $('#block-slug').val(slug);
        }
    });
    
    $('#block-slug').on('input', function() {
        $(this).data('manual', true);
    });
    
    // Form submission
    $('#cbd-block-form').on('submit', function(e) {
        e.preventDefault();
        
        const blockId = $('#block-id').val();
        const data = {
            action: 'cbd_update_block',
            block_id: blockId,
            name: $('#block-name').val(),
            slug: $('#block-slug').val(),
            description: $('#block-description').val(),
            status: $('#block-status').prop('checked') ? 'active' : 'inactive',
            styles: {
                padding: {
                    top: parseInt($('input[name="padding-top"]').val()),
                    right: parseInt($('input[name="padding-right"]').val()),
                    bottom: parseInt($('input[name="padding-bottom"]').val()),
                    left: parseInt($('input[name="padding-left"]').val())
                },
                background: {
                    color: $('input[name="background-color"]').val()
                },
                text: {
                    color: $('input[name="text-color"]').val(),
                    alignment: $('select[name="text-alignment"]').val()
                },
                border: {
                    width: parseInt($('input[name="border-width"]').val()),
                    color: $('input[name="border-color"]').val(),
                    radius: parseInt($('input[name="border-radius"]').val())
                }
            },
            nonce: cbdAdmin.nonce
        };
        
        // Save via AJAX
        $.ajax({
            url: cbdAdmin.ajaxUrl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('.cbd-form-actions .button-primary').prop('disabled', true).text('Wird gespeichert...');
            },
            success: function(response) {
                if (response.success) {
                    $('.cbd-save-status').text('✓ Erfolgreich gespeichert').addClass('show');
                    setTimeout(function() {
                        $('.cbd-save-status').removeClass('show');
                    }, 3000);
                } else {
                    alert(response.data || 'Fehler beim Speichern');
                }
            },
            error: function() {
                alert('Fehler beim Speichern');
            },
            complete: function() {
                $('.cbd-form-actions .button-primary').prop('disabled', false).text('Änderungen speichern');
            }
        });
    });
});
</script>