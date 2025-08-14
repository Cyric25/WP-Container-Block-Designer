<?php
/**
 * Admin Features Class
 * 
 * @package ContainerBlockDesigner
 * @version 2.2.0
 */

namespace ContainerBlockDesigner\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Admin_Features {
    
    /**
     * Initialize the features system
     */
    public static function init() {
        // Hook into admin actions
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        add_action('admin_footer', array(__CLASS__, 'render_feature_modal'));
        
        // AJAX handlers
        add_action('wp_ajax_cbd_save_features', array(__CLASS__, 'ajax_save_features'));
        add_action('wp_ajax_cbd_get_features', array(__CLASS__, 'ajax_get_features'));
        add_action('wp_ajax_cbd_reset_features', array(__CLASS__, 'ajax_reset_features'));
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'container-block-designer') === false) {
            return;
        }
        
        // Features CSS
        wp_enqueue_style(
            'cbd-admin-features',
            CBD_PLUGIN_URL . 'assets/css/admin-features.css',
            array('wp-components'),
            CBD_VERSION
        );
        
        // Features JavaScript
        wp_enqueue_script(
            'cbd-admin-features',
            CBD_PLUGIN_URL . 'assets/js/admin-features.js',
            array('jquery'),
            CBD_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script('cbd-admin-features', 'cbdFeatures', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cbd_features_nonce'),
            'strings' => array(
                'saved' => __('Features gespeichert', 'container-block-designer'),
                'error' => __('Fehler beim Speichern', 'container-block-designer'),
                'confirmReset' => __('Wirklich alle Features zurücksetzen?', 'container-block-designer'),
                'loading' => __('Wird geladen...', 'container-block-designer'),
                'defaultCopyText' => __('Text kopieren', 'container-block-designer'),
                'defaultScreenshotText' => __('Screenshot', 'container-block-designer')
            )
        ));
    }
    
    /**
     * Render the features modal in admin footer
     */
    public static function render_feature_modal() {
        // Only render on our plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'container-block-designer') === false) {
            return;
        }
        
        // Check if template file exists, if not create modal directly
        $template_file = CBD_PLUGIN_DIR . 'templates/features-modal-template.php';
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            // Render modal directly if template doesn't exist
            ?>
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
                            
                            <!-- Feature 1: Block-Icon -->
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
                            
                            <!-- Feature 2: Ein-/Ausklappbar -->
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
                            
                            <!-- Feature 3: Nummerierung -->
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
                            
                            <!-- Feature 4: Text kopieren -->
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
                            
                            <!-- Feature 5: Screenshot -->
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
            <?php
        }
    }
    
    /**
     * Get default features configuration
     */
    private static function get_default_features() {
        return array(
            'icon' => array(
                'enabled' => false,
                'value' => 'dashicons-admin-generic'
            ),
            'collapse' => array(
                'enabled' => false,
                'defaultState' => 'expanded'
            ),
            'numbering' => array(
                'enabled' => false,
                'format' => 'numeric'
            ),
            'copyText' => array(
                'enabled' => false,
                'buttonText' => 'Text kopieren'
            ),
            'screenshot' => array(
                'enabled' => false,
                'buttonText' => 'Screenshot'
            )
        );
    }
    
    /**
     * AJAX: Get features for a block
     */
    public static function ajax_get_features() {
        // Security check
        if (!check_ajax_referer('cbd_features_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
        
        if (!$block_id) {
            wp_send_json_success(self::get_default_features());
            return;
        }
        
        global $wpdb;
        
        // Get block features from database
        $block = $wpdb->get_row($wpdb->prepare(
            "SELECT features FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d",
            $block_id
        ));
        
        if ($block && !empty($block->features)) {
            $features = json_decode($block->features, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                wp_send_json_success($features);
            } else {
                wp_send_json_success(self::get_default_features());
            }
        } else {
            wp_send_json_success(self::get_default_features());
        }
    }
    
    /**
     * AJAX: Save features for a block
     */
    public static function ajax_save_features() {
        // Security check
        if (!check_ajax_referer('cbd_features_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
        $features = isset($_POST['features']) ? $_POST['features'] : '';
        
        if (!$block_id) {
            wp_send_json_error('Invalid block ID');
            return;
        }
        
        // Decode and validate features
        $features_array = json_decode(stripslashes($features), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid features data');
            return;
        }
        
        // Sanitize features
        $sanitized_features = self::sanitize_features($features_array);
        
        global $wpdb;
        
        // Update block features in database
        $result = $wpdb->update(
            CBD_TABLE_BLOCKS,
            array(
                'features' => json_encode($sanitized_features),
                'modified' => current_time('mysql')
            ),
            array('id' => $block_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Clear any cached CSS
            self::clear_block_css_cache($block_id);
            
            wp_send_json_success(array(
                'message' => 'Features erfolgreich gespeichert',
                'features' => $sanitized_features
            ));
        } else {
            wp_send_json_error('Fehler beim Speichern der Features');
        }
    }
    
    /**
     * AJAX: Reset features to defaults
     */
    public static function ajax_reset_features() {
        // Security check
        if (!check_ajax_referer('cbd_features_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        wp_send_json_success(self::get_default_features());
    }
    
    /**
     * Sanitize features data
     */
    private static function sanitize_features($features) {
        $defaults = self::get_default_features();
        $sanitized = array();
        
        // Icon feature
        if (isset($features['icon'])) {
            $sanitized['icon'] = array(
                'enabled' => !empty($features['icon']['enabled']),
                'value' => sanitize_text_field($features['icon']['value'] ?? $defaults['icon']['value'])
            );
        } else {
            $sanitized['icon'] = $defaults['icon'];
        }
        
        // Collapse feature
        if (isset($features['collapse'])) {
            $sanitized['collapse'] = array(
                'enabled' => !empty($features['collapse']['enabled']),
                'defaultState' => in_array($features['collapse']['defaultState'], ['expanded', 'collapsed']) 
                    ? $features['collapse']['defaultState'] 
                    : $defaults['collapse']['defaultState']
            );
        } else {
            $sanitized['collapse'] = $defaults['collapse'];
        }
        
        // Numbering feature
        if (isset($features['numbering'])) {
            $sanitized['numbering'] = array(
                'enabled' => !empty($features['numbering']['enabled']),
                'format' => in_array($features['numbering']['format'], ['numeric', 'alpha', 'roman']) 
                    ? $features['numbering']['format'] 
                    : $defaults['numbering']['format']
            );
        } else {
            $sanitized['numbering'] = $defaults['numbering'];
        }
        
        // Copy text feature
        if (isset($features['copyText'])) {
            $sanitized['copyText'] = array(
                'enabled' => !empty($features['copyText']['enabled']),
                'buttonText' => sanitize_text_field($features['copyText']['buttonText'] ?? $defaults['copyText']['buttonText'])
            );
        } else {
            $sanitized['copyText'] = $defaults['copyText'];
        }
        
        // Screenshot feature
        if (isset($features['screenshot'])) {
            $sanitized['screenshot'] = array(
                'enabled' => !empty($features['screenshot']['enabled']),
                'buttonText' => sanitize_text_field($features['screenshot']['buttonText'] ?? $defaults['screenshot']['buttonText'])
            );
        } else {
            $sanitized['screenshot'] = $defaults['screenshot'];
        }
        
        return $sanitized;
    }
    
    /**
     * Clear block CSS cache
     */
    private static function clear_block_css_cache($block_id) {
        // Delete any cached CSS files
        $upload_dir = wp_upload_dir();
        $css_file = $upload_dir['basedir'] . '/cbd-blocks/block-' . $block_id . '.css';
        
        if (file_exists($css_file)) {
            @unlink($css_file);
        }
        
        // Clear any transients
        delete_transient('cbd_block_css_' . $block_id);
    }
}

// Initialize the features system
Admin_Features::init();