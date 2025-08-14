<?php
/**
 * Container Block Designer - Edit Block Template
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get block ID from URL
$block_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$block_id) {
    wp_die(__('Block ID fehlt.', 'container-block-designer'));
}

// Get block data
global $wpdb;
$block = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d", $block_id));

if (!$block) {
    wp_die(__('Block nicht gefunden.', 'container-block-designer'));
}

// Parse config
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
?>

<div class="cbd-block-editor">
    <div class="cbd-editor-main">
        <h2>
            <?php echo esc_html__('Block bearbeiten:', 'container-block-designer'); ?>
            <code><?php echo esc_html($block->name); ?></code>
        </h2>
        
        <form id="cbd-block-form" method="post">
            <input type="hidden" id="block-id" value="<?php echo esc_attr($block->id); ?>">
            
            <!-- Basic Information -->
            <div class="cbd-control-group">
                <h4><?php echo esc_html__('Grundinformationen', 'container-block-designer'); ?></h4>
                
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
                
                <div class="cbd-form-field">
                    <label for="block-description"><?php echo esc_html__('Beschreibung', 'container-block-designer'); ?></label>
                    <textarea id="block-description" name="block-description" rows="3"><?php echo esc_textarea($block->description); ?></textarea>
                    <p class="description"><?php echo esc_html__('Kurze Beschreibung des Block-Zwecks', 'container-block-designer'); ?></p>
                </div>
                
                <div class="cbd-form-field">
                    <label for="block-status"><?php echo esc_html__('Status', 'container-block-designer'); ?></label>
                    <select id="block-status" name="block-status">
                        <option value="active" <?php selected($block->status, 'active'); ?>><?php echo esc_html__('Aktiv', 'container-block-designer'); ?></option>
                        <option value="inactive" <?php selected($block->status, 'inactive'); ?>><?php echo esc_html__('Inaktiv', 'container-block-designer'); ?></option>
                        <option value="draft" <?php selected($block->status, 'draft'); ?>><?php echo esc_html__('Entwurf', 'container-block-designer'); ?></option>
                    </select>
                </div>
            </div>
            
            <!-- Gestaltung Section -->
            <div class="cbd-control-group">
                <h4><?php echo esc_html__('Gestaltung', 'container-block-designer'); ?></h4>
                
                <!-- Padding -->
                <div class="cbd-style-section">
                    <h5><?php echo esc_html__('Innenabstand (Padding)', 'container-block-designer'); ?></h5>
                    <div class="cbd-spacing-controls">
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Oben', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-top" value="<?php echo esc_attr(get_style_value($styles, 'padding.top', 20)); ?>" min="0" max="200">
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Rechts', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-right" value="<?php echo esc_attr(get_style_value($styles, 'padding.right', 20)); ?>" min="0" max="200">
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Unten', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-bottom" value="<?php echo esc_attr(get_style_value($styles, 'padding.bottom', 20)); ?>" min="0" max="200">
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Links', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-left" value="<?php echo esc_attr(get_style_value($styles, 'padding.left', 20)); ?>" min="0" max="200">
                        </div>
                    </div>
                </div>
                
                <!-- Margin -->
                <div class="cbd-style-section">
                    <h5><?php echo esc_html__('Außenabstand (Margin)', 'container-block-designer'); ?></h5>
                    <div class="cbd-spacing-controls">
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Oben', 'container-block-designer'); ?></label>
                            <input type="number" id="margin-top" value="<?php echo esc_attr(get_style_value($styles, 'margin.top', 0)); ?>" min="0" max="200">
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Rechts', 'container-block-designer'); ?></label>
                            <input type="number" id="margin-right" value="<?php echo esc_attr(get_style_value($styles, 'margin.right', 0)); ?>" min="0" max="200">
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Unten', 'container-block-designer'); ?></label>
                            <input type="number" id="margin-bottom" value="<?php echo esc_attr(get_style_value($styles, 'margin.bottom', 0)); ?>" min="0" max="200">
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Links', 'container-block-designer'); ?></label>
                            <input type="number" id="margin-left" value="<?php echo esc_attr(get_style_value($styles, 'margin.left', 0)); ?>" min="0" max="200">
                        </div>
                    </div>
                </div>
                
                <!-- Colors -->
                <div class="cbd-style-section">
                    <h5><?php echo esc_html__('Farben', 'container-block-designer'); ?></h5>
                    <div class="cbd-color-controls">
                        <div class="cbd-color-field">
                            <label for="background-color"><?php echo esc_html__('Hintergrundfarbe', 'container-block-designer'); ?></label>
                            <div class="cbd-color-input-group">
                                <input type="color" class="cbd-color-picker" id="background-color" value="<?php echo esc_attr(get_style_value($styles, 'background.color', '#ffffff')); ?>">
                                <input type="text" id="background-color-text" value="<?php echo esc_attr(get_style_value($styles, 'background.color', '#ffffff')); ?>" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                        <div class="cbd-color-field">
                            <label for="text-color"><?php echo esc_html__('Textfarbe', 'container-block-designer'); ?></label>
                            <div class="cbd-color-input-group">
                                <input type="color" class="cbd-color-picker" id="text-color" value="<?php echo esc_attr(get_style_value($styles, 'text.color', '#000000')); ?>">
                                <input type="text" id="text-color-text" value="<?php echo esc_attr(get_style_value($styles, 'text.color', '#000000')); ?>" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Text Alignment -->
                <div class="cbd-style-section">
                    <h5><?php echo esc_html__('Textausrichtung', 'container-block-designer'); ?></h5>
                    <select id="text-alignment">
                        <option value="left" <?php selected(get_style_value($styles, 'text.alignment', 'left'), 'left'); ?>><?php echo esc_html__('Links', 'container-block-designer'); ?></option>
                        <option value="center" <?php selected(get_style_value($styles, 'text.alignment', 'left'), 'center'); ?>><?php echo esc_html__('Zentriert', 'container-block-designer'); ?></option>
                        <option value="right" <?php selected(get_style_value($styles, 'text.alignment', 'left'), 'right'); ?>><?php echo esc_html__('Rechts', 'container-block-designer'); ?></option>
                        <option value="justify" <?php selected(get_style_value($styles, 'text.alignment', 'left'), 'justify'); ?>><?php echo esc_html__('Blocksatz', 'container-block-designer'); ?></option>
                    </select>
                </div>
                
                <!-- Border -->
                <div class="cbd-style-section">
                    <h5><?php echo esc_html__('Rahmen', 'container-block-designer'); ?></h5>
                    <div class="cbd-border-controls">
                        <div class="cbd-border-field">
                            <label for="border-width"><?php echo esc_html__('Rahmenbreite (px)', 'container-block-designer'); ?></label>
                            <input type="number" id="border-width" value="<?php echo esc_attr(get_style_value($styles, 'border.width', 0)); ?>" min="0" max="20">
                        </div>
                        <div class="cbd-border-field">
                            <label for="border-color"><?php echo esc_html__('Rahmenfarbe', 'container-block-designer'); ?></label>
                            <div class="cbd-color-input-group">
                                <input type="color" class="cbd-color-picker" id="border-color" value="<?php echo esc_attr(get_style_value($styles, 'border.color', '#dddddd')); ?>">
                                <input type="text" id="border-color-text" value="<?php echo esc_attr(get_style_value($styles, 'border.color', '#dddddd')); ?>" pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                        <div class="cbd-border-field">
                            <label for="border-radius"><?php echo esc_html__('Eckenradius (px)', 'container-block-designer'); ?></label>
                            <input type="number" id="border-radius" value="<?php echo esc_attr(get_style_value($styles, 'border.radius', 0)); ?>" min="0" max="50">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Features Section -->
            <div class="cbd-control-group">
                <h4><?php echo esc_html__('Container-Features', 'container-block-designer'); ?></h4>
                <div class="cbd-features-info">
                    <p><?php echo esc_html__('Konfigurieren Sie erweiterte Features für diesen Container-Block.', 'container-block-designer'); ?></p>
                    <button type="button" class="button cbd-features-btn" data-block-id="<?php echo esc_attr($block->id); ?>">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php echo esc_html__('Features konfigurieren', 'container-block-designer'); ?>
                    </button>
                </div>
                
                <?php if (!empty($features)): ?>
                <div class="cbd-active-features">
                    <h5><?php echo esc_html__('Aktive Features:', 'container-block-designer'); ?></h5>
                    <ul>
                        <?php if (!empty($features['icon']['enabled'])): ?>
                            <li>✅ <?php echo esc_html__('Block-Icon', 'container-block-designer'); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($features['collapse']['enabled'])): ?>
                            <li>✅ <?php echo esc_html__('Ein-/Ausklappbar', 'container-block-designer'); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($features['numbering']['enabled'])): ?>
                            <li>✅ <?php echo esc_html__('Nummerierung', 'container-block-designer'); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($features['copyText']['enabled'])): ?>
                            <li>✅ <?php echo esc_html__('Text kopieren', 'container-block-designer'); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($features['screenshot']['enabled'])): ?>
                            <li>✅ <?php echo esc_html__('Screenshot', 'container-block-designer'); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div class="cbd-form-actions">
                <button type="submit" id="cbd-save-block" class="button button-primary">
                    <?php echo esc_html__('Änderungen speichern', 'container-block-designer'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=container-block-designer'); ?>" class="button">
                    <?php echo esc_html__('Abbrechen', 'container-block-designer'); ?>
                </a>
            </div>
        </form>
    </div>
    
    <!-- Live Preview -->
    <div class="cbd-editor-sidebar">
        <div class="cbd-preview-wrapper">
            <h4><?php echo esc_html__('Live-Vorschau', 'container-block-designer'); ?></h4>
            
            <div class="cbd-preview-modes">
                <button class="cbd-preview-mode active" data-mode="desktop">
                    <span class="dashicons dashicons-desktop"></span>
                </button>
                <button class="cbd-preview-mode" data-mode="tablet">
                    <span class="dashicons dashicons-tablet"></span>
                </button>
                <button class="cbd-preview-mode" data-mode="mobile">
                    <span class="dashicons dashicons-smartphone"></span>
                </button>
            </div>
            
            <div class="cbd-preview-frame">
                <div class="cbd-preview-container" data-preview-mode="desktop">
                    <div class="cbd-preview-content">
                        <h3 class="cbd-preview-name"><?php echo esc_html($block->name); ?></h3>
                        <p>Dies ist eine Beispiel-Vorschau Ihres Container-Blocks.</p>
                        <p>Der Inhalt hier zeigt, wie Ihr Block aussehen wird.</p>
                    </div>
                </div>
                <div class="cbd-preview-meta">
                    <span class="cbd-preview-slug">Slug: <code><?php echo esc_html($block->slug); ?></code></span>
                </div>
            </div>
        </div>
        
        <!-- Block Info -->
        <div class="cbd-block-info">
            <h4><?php echo esc_html__('Block-Verwendung', 'container-block-designer'); ?></h4>
            <p style="font-size: 13px; color: #646970; margin: 0;">
                <?php echo esc_html__('Dieser Block ist unter folgendem Namen im Gutenberg Editor verfügbar:', 'container-block-designer'); ?>
            </p>
            <code style="display: block; margin: 10px 0; padding: 8px; background: #f1f1f1; border-radius: 4px; font-size: 12px;">
                <?php echo esc_html($block->name); ?>
            </code>
            <p style="font-size: 13px; color: #646970; margin: 0;">
                <strong><?php echo esc_html__('Slug:', 'container-block-designer'); ?></strong> 
                <code><?php echo esc_html($block->slug); ?></code>
            </p>
        </div>
    </div>
</div>

<!-- Features Modal (wird von admin-features.js verwaltet) -->
<div id="cbd-features-modal" class="cbd-modal" style="display: none;">
    <div class="cbd-modal-content">
        <div class="cbd-modal-header">
            <h2><?php echo esc_html__('Container-Features konfigurieren', 'container-block-designer'); ?></h2>
            <button type="button" class="cbd-modal-close" aria-label="<?php echo esc_attr__('Schließen', 'container-block-designer'); ?>">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        
        <div class="cbd-modal-body">
            <form id="cbd-features-form">
                <input type="hidden" id="cbd-block-id" value="<?php echo esc_attr($block->id); ?>">
                
                <!-- Icon Feature -->
                <div class="cbd-feature-item" data-feature="icon">
                    <label>
                        <div class="cbd-toggle-wrapper">
                            <input type="checkbox" id="feature-icon-enabled" <?php checked(!empty($features['icon']['enabled'])); ?>>
                        </div>
                        <?php echo esc_html__('Block-Icon', 'container-block-designer'); ?>
                    </label>
                    <div class="cbd-feature-description">
                        <?php echo esc_html__('Zeigt ein Icon im Container-Header an', 'container-block-designer'); ?>
                    </div>
                    <div class="cbd-feature-options" data-feature="icon">
                        <div class="cbd-feature-group">
                            <label><?php echo esc_html__('Icon auswählen', 'container-block-designer'); ?></label>
                            <div class="cbd-current-icon">
                                <span class="dashicons <?php echo esc_attr($features['icon']['value'] ?? 'dashicons-admin-generic'); ?>"></span>
                                <button type="button" class="cbd-choose-icon"><?php echo esc_html__('Icon wählen', 'container-block-designer'); ?></button>
                            </div>
                            <input type="hidden" id="block-icon-value" value="<?php echo esc_attr($features['icon']['value'] ?? 'dashicons-admin-generic'); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Collapse Feature -->
                <div class="cbd-feature-item" data-feature="collapse">
                    <label>
                        <div class="cbd-toggle-wrapper">
                            <input type="checkbox" id="feature-collapse-enabled" <?php checked(!empty($features['collapse']['enabled'])); ?>>
                        </div>
                        <?php echo esc_html__('Ein-/Ausklappbar', 'container-block-designer'); ?>
                    </label>
                    <div class="cbd-feature-description">
                        <?php echo esc_html__('Container kann ein- und ausgeklappt werden', 'container-block-designer'); ?>
                    </div>
                    <div class="cbd-feature-options" data-feature="collapse">
                        <div class="cbd-feature-group">
                            <label for="collapse-default-state"><?php echo esc_html__('Standard-Zustand', 'container-block-designer'); ?></label>
                            <select id="collapse-default-state">
                                <option value="expanded" <?php selected($features['collapse']['defaultState'] ?? 'expanded', 'expanded'); ?>><?php echo esc_html__('Ausgeklappt', 'container-block-designer'); ?></option>
                                <option value="collapsed" <?php selected($features['collapse']['defaultState'] ?? 'expanded', 'collapsed'); ?>><?php echo esc_html__('Eingeklappt', 'container-block-designer'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- More features can be added here -->
            </form>
        </div>
        
        <div class="cbd-modal-footer">
            <button type="button" id="cbd-save-features" class="button button-primary">
                <?php echo esc_html__('Speichern', 'container-block-designer'); ?>
            </button>
            <button type="button" id="cbd-modal-cancel" class="button">
                <?php echo esc_html__('Abbrechen', 'container-block-designer'); ?>
            </button>
        </div>
    </div>
</div>

<script>
// Sync color inputs and initialize preview
jQuery(document).ready(function($) {
    // Sync color picker with text input
    $('.cbd-color-picker').on('input', function() {
        const color = $(this).val();
        $(this).next('input[type="text"]').val(color);
        if (typeof CBDAdmin !== 'undefined') {
            CBDAdmin.updatePreview();
        }
    });
    
    // Sync text input with color picker
    $('input[id$="-color-text"]').on('input', function() {
        const color = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(color)) {
            $(this).prev('.cbd-color-picker').val(color);
            $(this).prev('.cbd-color-picker').css('backgroundColor', color);
            if (typeof CBDAdmin !== 'undefined') {
                CBDAdmin.updatePreview();
            }
        }
    });
    
    // Set initial color picker backgrounds
    $('.cbd-color-picker').each(function() {
        $(this).css('backgroundColor', $(this).val());
    });
    
    // Override save handler for update
    $('#cbd-save-block').off('click').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const blockId = $('#block-id').val();
        
        // Form validation
        const name = $('#block-name').val().trim();
        const slug = $('#block-slug').val().trim();
        const status = $('#block-status').val() || 'active';
        
        if (!name || !slug) {
            alert('Bitte füllen Sie alle Pflichtfelder aus.');
            return false;
        }
        
        // Use CBDAdmin save function if available
        if (typeof CBDAdmin !== 'undefined' && CBDAdmin.saveBlock) {
            CBDAdmin.saveBlock(e);
        } else {
            // Fallback save
            $btn.prop('disabled', true).text('Speichern...');
            
            // Collect form data and submit via AJAX
            const data = {
                action: 'cbd_update_block',
                block_id: blockId,
                name: name,
                slug: slug,
                description: $('#block-description').val(),
                status: status,
                nonce: cbdAdmin.nonce
            };
            
            $.post(cbdAdmin.ajaxUrl, data, function(response) {
                if (response.success) {
                    alert('Block wurde gespeichert!');
                    window.location.href = cbdAdmin.blocksListUrl;
                } else {
                    alert('Fehler beim Speichern: ' + (response.data.message || 'Unbekannter Fehler'));
                }
                $btn.prop('disabled', false).text('Änderungen speichern');
            });
        }
    });
});
</script>