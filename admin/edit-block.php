<?php
/**
 * Container Block Designer - Edit Block Page
 * Version: 2.2.1 - FIXED
 * 
 * Diese Datei ersetzt: /wp-content/plugins/container-block-designer/admin/edit-block.php
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get block ID - von verschiedenen Quellen
$block_id = 0;
if (isset($_GET['id'])) {
    $block_id = intval($_GET['id']);
} elseif (isset($_GET['block_id'])) {
    $block_id = intval($_GET['block_id']);
}

if (!$block_id) {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Fehler', 'container-block-designer'); ?></h1>
        <div class="notice notice-error">
            <p><?php echo esc_html__('Ungültige Block-ID. Bitte wählen Sie einen Block aus der Liste.', 'container-block-designer'); ?></p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=container-block-designer'); ?>" class="button">
            <?php echo esc_html__('Zurück zur Übersicht', 'container-block-designer'); ?>
        </a>
    </div>
    <?php
    return;
}

// Get block data
global $wpdb;
$block = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d",
    $block_id
));

if (!$block) {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Fehler', 'container-block-designer'); ?></h1>
        <div class="notice notice-error">
            <p><?php echo esc_html__('Block nicht gefunden.', 'container-block-designer'); ?></p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=container-block-designer'); ?>" class="button">
            <?php echo esc_html__('Zurück zur Übersicht', 'container-block-designer'); ?>
        </a>
    </div>
    <?php
    return;
}

// Parse config and features
$config = json_decode($block->config, true) ?: [];
$styles = isset($config['styles']) ? $config['styles'] : [];
$features = json_decode($block->features, true) ?: [];

// Debug output
if (WP_DEBUG) {
    error_log('CBD Edit Block - ID: ' . $block_id);
    error_log('CBD Edit Block - Name: ' . $block->name);
}
?>

<div class="wrap cbd-admin-wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Block bearbeiten:', 'container-block-designer'); ?> 
        <?php echo esc_html($block->name); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=container-block-designer'); ?>" class="page-title-action">
        <?php echo esc_html__('← Zurück zur Übersicht', 'container-block-designer'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <div class="cbd-edit-container">
        <div class="cbd-edit-main">
            <form id="cbd-block-form" method="post">
                <!-- Hidden fields -->
                <input type="hidden" id="block-id" name="block_id" value="<?php echo esc_attr($block_id); ?>">
                <?php wp_nonce_field('cbd-admin', 'cbd_nonce'); ?>
                
                <!-- Basic Information -->
                <div class="cbd-card">
                    <h2><?php echo esc_html__('Grundeinstellungen', 'container-block-designer'); ?></h2>
                    
                    <div class="cbd-form-field">
                        <label for="block-name">
                            <?php echo esc_html__('Name', 'container-block-designer'); ?> *
                        </label>
                        <input type="text" id="block-name" name="name" value="<?php echo esc_attr($block->name); ?>" required class="regular-text">
                    </div>
                    
                    <div class="cbd-form-field">
                        <label for="block-slug">
                            <?php echo esc_html__('Slug', 'container-block-designer'); ?> *
                        </label>
                        <input type="text" id="block-slug" name="slug" value="<?php echo esc_attr($block->slug); ?>" required class="regular-text">
                        <p class="description"><?php echo esc_html__('Eindeutige Kennung für den Block (nur Kleinbuchstaben und Bindestriche)', 'container-block-designer'); ?></p>
                    </div>
                    
                    <div class="cbd-form-field">
                        <label for="block-description">
                            <?php echo esc_html__('Beschreibung', 'container-block-designer'); ?>
                        </label>
                        <textarea id="block-description" name="description" rows="3" class="large-text"><?php echo esc_textarea($block->description); ?></textarea>
                    </div>
                    
                    <div class="cbd-form-field">
                        <label for="block-status">
                            <?php echo esc_html__('Status', 'container-block-designer'); ?>
                        </label>
                        <select id="block-status" name="status">
                            <option value="active" <?php selected($block->status, 'active'); ?>><?php echo esc_html__('Aktiv', 'container-block-designer'); ?></option>
                            <option value="inactive" <?php selected($block->status, 'inactive'); ?>><?php echo esc_html__('Inaktiv', 'container-block-designer'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Styles -->
                <div class="cbd-card">
                    <h2><?php echo esc_html__('Design-Einstellungen', 'container-block-designer'); ?></h2>
                    
                    <!-- Spacing -->
                    <h3><?php echo esc_html__('Innenabstände (Padding)', 'container-block-designer'); ?></h3>
                    <div class="cbd-spacing-controls">
                        <div class="cbd-form-field">
                            <label for="padding-top"><?php echo esc_html__('Oben', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-top" name="padding-top" value="<?php echo esc_attr($styles['padding']['top'] ?? 20); ?>" min="0" class="small-text">
                            <span class="description">px</span>
                        </div>
                        <div class="cbd-form-field">
                            <label for="padding-right"><?php echo esc_html__('Rechts', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-right" name="padding-right" value="<?php echo esc_attr($styles['padding']['right'] ?? 20); ?>" min="0" class="small-text">
                            <span class="description">px</span>
                        </div>
                        <div class="cbd-form-field">
                            <label for="padding-bottom"><?php echo esc_html__('Unten', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-bottom" name="padding-bottom" value="<?php echo esc_attr($styles['padding']['bottom'] ?? 20); ?>" min="0" class="small-text">
                            <span class="description">px</span>
                        </div>
                        <div class="cbd-form-field">
                            <label for="padding-left"><?php echo esc_html__('Links', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-left" name="padding-left" value="<?php echo esc_attr($styles['padding']['left'] ?? 20); ?>" min="0" class="small-text">
                            <span class="description">px</span>
                        </div>
                    </div>
                    
                    <!-- Colors -->
                    <h3><?php echo esc_html__('Farben', 'container-block-designer'); ?></h3>
                    <div class="cbd-form-grid">
                        <div class="cbd-form-field">
                            <label for="background-color"><?php echo esc_html__('Hintergrundfarbe', 'container-block-designer'); ?></label>
                            <input type="color" id="background-color" class="cbd-color-picker" value="<?php echo esc_attr($styles['background']['color'] ?? '#ffffff'); ?>">
                            <input type="text" id="background-color-text" name="background-color" value="<?php echo esc_attr($styles['background']['color'] ?? '#ffffff'); ?>" class="regular-text">
                        </div>
                        <div class="cbd-form-field">
                            <label for="text-color"><?php echo esc_html__('Textfarbe', 'container-block-designer'); ?></label>
                            <input type="color" id="text-color" class="cbd-color-picker" value="<?php echo esc_attr($styles['text']['color'] ?? '#333333'); ?>">
                            <input type="text" id="text-color-text" name="text-color" value="<?php echo esc_attr($styles['text']['color'] ?? '#333333'); ?>" class="regular-text">
                        </div>
                    </div>
                    
                    <!-- Text Alignment -->
                    <div class="cbd-form-field">
                        <label for="text-alignment"><?php echo esc_html__('Textausrichtung', 'container-block-designer'); ?></label>
                        <select id="text-alignment" name="text-alignment">
                            <option value="left" <?php selected($styles['text']['alignment'] ?? 'left', 'left'); ?>><?php echo esc_html__('Links', 'container-block-designer'); ?></option>
                            <option value="center" <?php selected($styles['text']['alignment'] ?? 'left', 'center'); ?>><?php echo esc_html__('Zentriert', 'container-block-designer'); ?></option>
                            <option value="right" <?php selected($styles['text']['alignment'] ?? 'left', 'right'); ?>><?php echo esc_html__('Rechts', 'container-block-designer'); ?></option>
                            <option value="justify" <?php selected($styles['text']['alignment'] ?? 'left', 'justify'); ?>><?php echo esc_html__('Blocksatz', 'container-block-designer'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Border -->
                    <h3><?php echo esc_html__('Rahmen', 'container-block-designer'); ?></h3>
                    <div class="cbd-form-grid">
                        <div class="cbd-form-field">
                            <label for="border-width"><?php echo esc_html__('Rahmenbreite', 'container-block-designer'); ?></label>
                            <input type="number" id="border-width" name="border-width" value="<?php echo esc_attr($styles['border']['width'] ?? 0); ?>" min="0" class="small-text">
                            <span class="description">px</span>
                        </div>
                        <div class="cbd-form-field">
                            <label for="border-color"><?php echo esc_html__('Rahmenfarbe', 'container-block-designer'); ?></label>
                            <input type="color" id="border-color" class="cbd-color-picker" value="<?php echo esc_attr($styles['border']['color'] ?? '#dddddd'); ?>">
                            <input type="text" id="border-color-text" name="border-color" value="<?php echo esc_attr($styles['border']['color'] ?? '#dddddd'); ?>" class="regular-text">
                        </div>
                        <div class="cbd-form-field">
                            <label for="border-radius"><?php echo esc_html__('Eckenradius', 'container-block-designer'); ?></label>
                            <input type="number" id="border-radius" name="border-radius" value="<?php echo esc_attr($styles['border']['radius'] ?? 0); ?>" min="0" class="small-text">
                            <span class="description">px</span>
                        </div>
                    </div>
                </div>
                
                <!-- Features -->
                <div class="cbd-card">
                    <div class="cbd-card-header">
                        <h2><?php echo esc_html__('Erweiterte Features', 'container-block-designer'); ?></h2>
                        <button type="button" class="button cbd-features-btn" data-block-id="<?php echo esc_attr($block_id); ?>" data-name="<?php echo esc_attr($block->name); ?>">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php echo esc_html__('Features konfigurieren', 'container-block-designer'); ?>
                        </button>
                    </div>
                    
                    <?php if (!empty($features) && is_array($features)): ?>
                        <div class="cbd-active-features">
                            <p><strong><?php echo esc_html__('Aktive Features:', 'container-block-designer'); ?></strong></p>
                            <ul>
                                <?php if (!empty($features['icon']['enabled'])): ?>
                                    <li>✅ Icon: <?php echo esc_html($features['icon']['value'] ?? 'dashicons-admin-generic'); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($features['collapse']['enabled'])): ?>
                                    <li>✅ Ein-/Ausklappbar (Standard: <?php echo esc_html($features['collapse']['defaultState'] ?? 'expanded'); ?>)</li>
                                <?php endif; ?>
                                <?php if (!empty($features['numbering']['enabled'])): ?>
                                    <li>✅ Nummerierung (Format: <?php echo esc_html($features['numbering']['format'] ?? 'numeric'); ?>)</li>
                                <?php endif; ?>
                                <?php if (!empty($features['copyText']['enabled'])): ?>
                                    <li>✅ Text kopieren Button</li>
                                <?php endif; ?>
                                <?php if (!empty($features['screenshot']['enabled'])): ?>
                                    <li>✅ Screenshot Button</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p class="cbd-no-features">
                            <?php echo esc_html__('Keine Features aktiviert', 'container-block-designer'); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <div class="cbd-form-actions">
                    <button type="submit" id="cbd-save-block" class="button button-primary button-large">
                        <?php echo esc_html__('Änderungen speichern', 'container-block-designer'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=container-block-designer'); ?>" class="button button-large">
                        <?php echo esc_html__('Abbrechen', 'container-block-designer'); ?>
                    </a>
                    <span class="cbd-save-status"></span>
                </div>
            </form>
        </div>
        
        <!-- Preview Sidebar -->
        <div class="cbd-edit-sidebar">
            <div class="cbd-card">
                <h3><?php echo esc_html__('Live-Vorschau', 'container-block-designer'); ?></h3>
                <div class="cbd-preview-wrapper">
                    <div class="cbd-container-preview" id="cbd-preview">
                        <p>Dies ist eine Vorschau des Container-Blocks mit Ihren aktuellen Einstellungen.</p>
                    </div>
                </div>
                
                <div class="cbd-preview-info">
                    <p><strong>Block-ID:</strong> <?php echo esc_html($block_id); ?></p>
                    <p><strong>CSS-Klasse:</strong> <code>.cbd-container-<?php echo esc_html($block_id); ?></code></p>
                    <p><strong>Erstellt:</strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($block->created))); ?></p>
                    <p><strong>Geändert:</strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($block->modified))); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('Edit Block Page loaded - Block ID: <?php echo $block_id; ?>');
    
    // Live Preview Update
    function updatePreview() {
        const $preview = $('#cbd-preview');
        
        $preview.css({
            'padding-top': $('#padding-top').val() + 'px',
            'padding-right': $('#padding-right').val() + 'px',
            'padding-bottom': $('#padding-bottom').val() + 'px',
            'padding-left': $('#padding-left').val() + 'px',
            'background-color': $('#background-color').val(),
            'color': $('#text-color').val(),
            'text-align': $('#text-alignment').val(),
            'border-width': $('#border-width').val() + 'px',
            'border-style': 'solid',
            'border-color': $('#border-color').val(),
            'border-radius': $('#border-radius').val() + 'px'
        });
    }
    
    // Bind preview updates
    $('input[type="number"], input[type="color"], input[type="text"], select').on('input change', updatePreview);
    
    // Initial preview
    updatePreview();
    
    // Color sync
    $('.cbd-color-picker').on('input', function() {
        $(this).next('input[type="text"]').val($(this).val());
        updatePreview();
    });
    
    $('input[id$="-color-text"]').on('input', function() {
        const color = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(color)) {
            $(this).prev('.cbd-color-picker').val(color);
            updatePreview();
        }
    });
});
</script>