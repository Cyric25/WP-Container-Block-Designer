<?php
/**
 * Container Block Designer - New Block Template
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cbd-block-editor">
    <div class="cbd-editor-main">
        <h2><?php echo esc_html__('Neuen Container-Block erstellen', 'container-block-designer'); ?></h2>
        
        <form id="cbd-block-form" class="cbd-form">
            <!-- Basic Information -->
            <div class="cbd-control-group">
                <h3><?php echo esc_html__('Grundinformationen', 'container-block-designer'); ?></h3>
                
                <div class="cbd-form-field">
                    <label for="block-name"><?php echo esc_html__('Block-Name', 'container-block-designer'); ?> <span class="required">*</span></label>
                    <input type="text" id="block-name" name="name" required placeholder="<?php echo esc_attr__('z.B. Hero Section', 'container-block-designer'); ?>">
                </div>
                
                <div class="cbd-form-field">
                    <label for="block-slug"><?php echo esc_html__('Slug', 'container-block-designer'); ?> <span class="required">*</span></label>
                    <input type="text" id="block-slug" name="slug" required placeholder="<?php echo esc_attr__('automatisch generiert', 'container-block-designer'); ?>">
                    <small><?php echo esc_html__('Wird automatisch aus dem Namen generiert', 'container-block-designer'); ?></small>
                </div>
                
                <div class="cbd-form-field">
                    <label for="block-description"><?php echo esc_html__('Beschreibung', 'container-block-designer'); ?></label>
                    <textarea id="block-description" name="description" rows="3" placeholder="<?php echo esc_attr__('Optionale Beschreibung des Blocks', 'container-block-designer'); ?>"></textarea>
                </div>
                
                <div class="cbd-form-field">
                    <label for="block-status"><?php echo esc_html__('Status', 'container-block-designer'); ?></label>
                    <select id="block-status" name="status">
                        <option value="active"><?php echo esc_html__('Aktiv', 'container-block-designer'); ?></option>
                        <option value="inactive"><?php echo esc_html__('Inaktiv', 'container-block-designer'); ?></option>
                        <option value="draft"><?php echo esc_html__('Entwurf', 'container-block-designer'); ?></option>
                    </select>
                </div>
            </div>
            
            <!-- Style Settings -->
            <div class="cbd-control-group">
                <h3><?php echo esc_html__('Style-Einstellungen', 'container-block-designer'); ?></h3>
                
                <!-- Padding -->
                <div class="cbd-style-section">
                    <h4><?php echo esc_html__('Innenabstand (Padding)', 'container-block-designer'); ?></h4>
                    <div class="cbd-spacing-controls">
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Oben', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-top" value="20" min="0" max="200">
                            <span class="unit">px</span>
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Rechts', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-right" value="20" min="0" max="200">
                            <span class="unit">px</span>
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Unten', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-bottom" value="20" min="0" max="200">
                            <span class="unit">px</span>
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Links', 'container-block-designer'); ?></label>
                            <input type="number" id="padding-left" value="20" min="0" max="200">
                            <span class="unit">px</span>
                        </div>
                    </div>
                </div>
                
                <!-- Margin -->
                <div class="cbd-style-section">
                    <h4><?php echo esc_html__('Außenabstand (Margin)', 'container-block-designer'); ?></h4>
                    <div class="cbd-spacing-controls">
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Oben', 'container-block-designer'); ?></label>
                            <input type="number" id="margin-top" value="0" min="0" max="200">
                            <span class="unit">px</span>
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Rechts', 'container-block-designer'); ?></label>
                            <input type="number" id="margin-right" value="0" min="0" max="200">
                            <span class="unit">px</span>
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Unten', 'container-block-designer'); ?></label>
                            <input type="number" id="margin-bottom" value="0" min="0" max="200">
                            <span class="unit">px</span>
                        </div>
                        <div class="cbd-spacing-field">
                            <label><?php echo esc_html__('Links', 'container-block-designer'); ?></label>
                            <input type="number" id="margin-left" value="0" min="0" max="200">
                            <span class="unit">px</span>
                        </div>
                    </div>
                </div>
                
                <!-- Colors -->
                <div class="cbd-style-section">
                    <h4><?php echo esc_html__('Farben', 'container-block-designer'); ?></h4>
                    <div class="cbd-color-controls">
                        <div class="cbd-color-field">
                            <label><?php echo esc_html__('Hintergrundfarbe', 'container-block-designer'); ?></label>
                            <input type="color" id="background-color" class="cbd-color-picker" value="#ffffff">
                            <input type="text" id="background-color-text" value="#ffffff" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <div class="cbd-color-field">
                            <label><?php echo esc_html__('Textfarbe', 'container-block-designer'); ?></label>
                            <input type="color" id="text-color" class="cbd-color-picker" value="#333333">
                            <input type="text" id="text-color-text" value="#333333" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                    </div>
                </div>
                
                <!-- Text Alignment -->
                <div class="cbd-style-section">
                    <h4><?php echo esc_html__('Text-Ausrichtung', 'container-block-designer'); ?></h4>
                    <select id="text-alignment">
                        <option value="left"><?php echo esc_html__('Links', 'container-block-designer'); ?></option>
                        <option value="center"><?php echo esc_html__('Zentriert', 'container-block-designer'); ?></option>
                        <option value="right"><?php echo esc_html__('Rechts', 'container-block-designer'); ?></option>
                        <option value="justify"><?php echo esc_html__('Blocksatz', 'container-block-designer'); ?></option>
                    </select>
                </div>
                
                <!-- Border -->
                <div class="cbd-style-section">
                    <h4><?php echo esc_html__('Rahmen', 'container-block-designer'); ?></h4>
                    <div class="cbd-border-controls">
                        <div class="cbd-border-field">
                            <label><?php echo esc_html__('Breite', 'container-block-designer'); ?></label>
                            <input type="number" id="border-width" value="0" min="0" max="20">
                            <span class="unit">px</span>
                        </div>
                        <div class="cbd-border-field">
                            <label><?php echo esc_html__('Farbe', 'container-block-designer'); ?></label>
                            <input type="color" id="border-color" class="cbd-color-picker" value="#dddddd">
                            <input type="text" id="border-color-text" value="#dddddd" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <div class="cbd-border-field">
                            <label><?php echo esc_html__('Radius', 'container-block-designer'); ?></label>
                            <input type="number" id="border-radius" value="0" min="0" max="50">
                            <span class="unit">px</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Templates -->
            <div class="cbd-control-group">
                <h3><?php echo esc_html__('Schnellvorlagen', 'container-block-designer'); ?></h3>
                <p><?php echo esc_html__('Verwenden Sie eine Vorlage als Ausgangspunkt:', 'container-block-designer'); ?></p>
                <div class="cbd-template-buttons">
                    <button type="button" class="button cbd-apply-template" data-template="hero">
                        <?php echo esc_html__('Hero Section', 'container-block-designer'); ?>
                    </button>
                    <button type="button" class="button cbd-apply-template" data-template="content">
                        <?php echo esc_html__('Content Section', 'container-block-designer'); ?>
                    </button>
                    <button type="button" class="button cbd-apply-template" data-template="cta">
                        <?php echo esc_html__('Call to Action', 'container-block-designer'); ?>
                    </button>
                    <button type="button" class="button cbd-apply-template" data-template="minimal">
                        <?php echo esc_html__('Minimal', 'container-block-designer'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="cbd-form-actions">
                <button type="submit" id="cbd-save-block" class="button button-primary">
                    <?php echo esc_html__('Block erstellen', 'container-block-designer'); ?>
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
                        <h3 class="cbd-preview-name"><?php echo esc_html__('Neuer Block', 'container-block-designer'); ?></h3>
                        <p><?php echo esc_html__('Dies ist eine Beispiel-Vorschau Ihres Container-Blocks.', 'container-block-designer'); ?></p>
                        <p><?php echo esc_html__('Der Inhalt hier zeigt, wie Ihr Block aussehen wird.', 'container-block-designer'); ?></p>
                    </div>
                </div>
                <div class="cbd-preview-meta">
                    <span class="cbd-preview-slug">Slug: <code>noch-nicht-definiert</code></span>
                </div>
            </div>
        </div>
        
        <!-- Help Info -->
        <div class="cbd-help-info">
            <h4><?php echo esc_html__('Hilfe', 'container-block-designer'); ?></h4>
            <ul>
                <li><?php echo esc_html__('Der Block-Name wird im Gutenberg Editor angezeigt', 'container-block-designer'); ?></li>
                <li><?php echo esc_html__('Der Slug wird automatisch aus dem Namen generiert', 'container-block-designer'); ?></li>
                <li><?php echo esc_html__('Nutzen Sie die Schnellvorlagen für vordefinierte Styles', 'container-block-designer'); ?></li>
                <li><?php echo esc_html__('Die Vorschau zeigt Ihre Änderungen in Echtzeit', 'container-block-designer'); ?></li>
                <li><strong><?php echo esc_html__('Nach dem Erstellen können Sie weitere Features hinzufügen', 'container-block-designer'); ?></strong></li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Template Application
    $('.cbd-apply-template').on('click', function() {
        const template = $(this).data('template');
        
        switch(template) {
            case 'hero':
                $('#padding-top, #padding-bottom').val(60);
                $('#padding-left, #padding-right').val(20);
                $('#background-color, #background-color-text').val('#007cba');
                $('#text-color, #text-color-text').val('#ffffff');
                $('#text-alignment').val('center');
                break;
            
            case 'content':
                $('#padding-top, #padding-bottom').val(40);
                $('#padding-left, #padding-right').val(20);
                $('#background-color, #background-color-text').val('#ffffff');
                $('#text-color, #text-color-text').val('#333333');
                $('#text-alignment').val('left');
                break;
            
            case 'cta':
                $('#padding-top, #padding-bottom').val(50);
                $('#padding-left, #padding-right').val(30);
                $('#background-color, #background-color-text').val('#00a32a');
                $('#text-color, #text-color-text').val('#ffffff');
                $('#text-alignment').val('center');
                $('#border-radius').val(8);
                break;
            
            case 'minimal':
                $('#padding-top, #padding-bottom, #padding-left, #padding-right').val(15);
                $('#margin-top, #margin-bottom').val(10);
                $('#background-color, #background-color-text').val('#f9f9f9');
                $('#text-color, #text-color-text').val('#1e1e1e');
                $('#text-alignment').val('left');
                $('#border-width').val(1);
                $('#border-color, #border-color-text').val('#e0e0e0');
                break;
        }
        
        // Trigger preview update
        if (typeof CBDAdmin !== 'undefined') {
            CBDAdmin.updatePreview();
        }
    });
    
    // Color sync
    $('.cbd-color-picker').on('input', function() {
        const color = $(this).val();
        $(this).next('input[type="text"]').val(color);
        if (typeof CBDAdmin !== 'undefined') {
            CBDAdmin.updatePreview();
        }
    });
    
    $('input[id$="-color-text"]').on('input', function() {
        const color = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(color)) {
            $(this).prev('.cbd-color-picker').val(color);
            if (typeof CBDAdmin !== 'undefined') {
                CBDAdmin.updatePreview();
            }
        }
    });
});
</script>