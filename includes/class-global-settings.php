<?php
/**
 * Global Settings System für Container Block Designer
 * 
 * Ermöglicht globale Feature-Defaults mit Block-Level-Overrides
 * 
 * @package ContainerBlockDesigner  
 * @version 2.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CBD_Global_Settings {
    
    const OPTION_NAME = 'cbd_global_settings';
    
    /**
     * Initialize the settings system
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('wp_ajax_cbd_save_global_settings', array(__CLASS__, 'save_settings_ajax'));
        add_action('wp_ajax_cbd_reset_global_settings', array(__CLASS__, 'reset_settings_ajax'));
        
        // Enqueue admin assets for settings page
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    }
    
    /**
     * Add settings page to admin menu
     */
    public static function add_settings_page() {
        add_submenu_page(
            'container-block-designer',
            __('Globale Einstellungen', 'container-block-designer'),
            __('Globale Einstellungen', 'container-block-designer'),
            'manage_options',
            'cbd-global-settings',
            array(__CLASS__, 'settings_page')
        );
    }
    
    /**
     * Get default global settings
     */
    public static function get_defaults() {
        return array(
            'features' => array(
                'icon' => array(
                    'enabled_by_default' => false,
                    'default_value' => 'dashicons-admin-generic',
                    'allow_override' => true
                ),
                'collapse' => array(
                    'enabled_by_default' => false,
                    'default_state' => 'expanded',
                    'allow_override' => true
                ),
                'numbering' => array(
                    'enabled_by_default' => false,
                    'default_format' => 'numeric',
                    'start_from' => 1,
                    'prefix' => '',
                    'suffix' => '.',
                    'allow_override' => true
                ),
                'copyText' => array(
                    'enabled_by_default' => false,
                    'default_button_text' => 'Text kopieren',
                    'allow_override' => true
                ),
                'screenshot' => array(
                    'enabled_by_default' => false,
                    'default_button_text' => 'Screenshot',
                    'allow_override' => true
                )
            ),
            'display' => array(
                'show_feature_hints' => true,
                'editor_preview_mode' => 'full'
            )
        );
    }
    
    /**
     * Get current global settings
     */
    public static function get_settings() {
        $defaults = self::get_defaults();
        $saved = get_option(self::OPTION_NAME, array());
        
        return wp_parse_args($saved, $defaults);
    }
    
    /**
     * Get effective settings for a block (globals + overrides)
     */
    public static function get_effective_settings($block_attributes = array()) {
        $global = self::get_settings();
        $effective = array();
        
        foreach ($global['features'] as $feature_name => $feature_defaults) {
            $effective[$feature_name] = array();
            
            // Start with global defaults
            if ($feature_defaults['enabled_by_default']) {
                $effective[$feature_name]['enabled'] = true;
                
                // Set default values based on feature type
                switch ($feature_name) {
                    case 'icon':
                        $effective[$feature_name]['value'] = $feature_defaults['default_value'];
                        break;
                        
                    case 'collapse':
                        $effective[$feature_name]['defaultState'] = $feature_defaults['default_state'];
                        break;
                        
                    case 'numbering':
                        $effective[$feature_name]['format'] = $feature_defaults['default_format'];
                        $effective[$feature_name]['startFrom'] = $feature_defaults['start_from'];
                        $effective[$feature_name]['prefix'] = $feature_defaults['prefix'];
                        $effective[$feature_name]['suffix'] = $feature_defaults['suffix'];
                        break;
                        
                    case 'copyText':
                        $effective[$feature_name]['buttonText'] = $feature_defaults['default_button_text'];
                        break;
                        
                    case 'screenshot':
                        $effective[$feature_name]['buttonText'] = $feature_defaults['default_button_text'];
                        break;
                }
            } else {
                $effective[$feature_name]['enabled'] = false;
            }
            
            // Apply block-level overrides if allowed
            if ($feature_defaults['allow_override'] && !empty($block_attributes)) {
                $block_key = 'enable' . ucfirst($feature_name);
                
                // Check if block explicitly overrides this feature
                if (isset($block_attributes[$block_key])) {
                    $effective[$feature_name]['enabled'] = $block_attributes[$block_key];
                    
                    // Apply specific overrides
                    switch ($feature_name) {
                        case 'icon':
                            if (isset($block_attributes['iconValue'])) {
                                $effective[$feature_name]['value'] = $block_attributes['iconValue'];
                            }
                            break;
                            
                        case 'collapse':
                            if (isset($block_attributes['collapseDefault'])) {
                                $effective[$feature_name]['defaultState'] = $block_attributes['collapseDefault'];
                            }
                            break;
                            
                        case 'numbering':
                            if (isset($block_attributes['numberingFormat'])) {
                                $effective[$feature_name]['format'] = $block_attributes['numberingFormat'];
                            }
                            break;
                            
                        case 'copyText':
                            if (isset($block_attributes['copyButtonText'])) {
                                $effective[$feature_name]['buttonText'] = $block_attributes['copyButtonText'];
                            }
                            break;
                            
                        case 'screenshot':
                            if (isset($block_attributes['screenshotButtonText'])) {
                                $effective[$feature_name]['buttonText'] = $block_attributes['screenshotButtonText'];
                            }
                            break;
                    }
                }
            }
        }
        
        return $effective;
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting(
            'cbd_global_settings',
            self::OPTION_NAME,
            array(
                'sanitize_callback' => array(__CLASS__, 'sanitize_settings')
            )
        );
    }
    
    /**
     * Sanitize settings
     */
    public static function sanitize_settings($input) {
        $sanitized = array();
        $defaults = self::get_defaults();
        
        // Sanitize features
        if (isset($input['features'])) {
            foreach ($defaults['features'] as $feature_name => $feature_defaults) {
                if (isset($input['features'][$feature_name])) {
                    $feature_input = $input['features'][$feature_name];
                    $sanitized['features'][$feature_name] = array();
                    
                    // Common settings
                    $sanitized['features'][$feature_name]['enabled_by_default'] = !empty($feature_input['enabled_by_default']);
                    $sanitized['features'][$feature_name]['allow_override'] = !empty($feature_input['allow_override']);
                    
                    // Feature-specific settings
                    switch ($feature_name) {
                        case 'icon':
                            $sanitized['features'][$feature_name]['default_value'] = sanitize_text_field($feature_input['default_value'] ?? 'dashicons-admin-generic');
                            break;
                            
                        case 'collapse':
                            $sanitized['features'][$feature_name]['default_state'] = in_array($feature_input['default_state'], ['expanded', 'collapsed']) 
                                ? $feature_input['default_state'] 
                                : 'expanded';
                            break;
                            
                        case 'numbering':
                            $sanitized['features'][$feature_name]['default_format'] = in_array($feature_input['default_format'], ['numeric', 'alpha', 'roman']) 
                                ? $feature_input['default_format'] 
                                : 'numeric';
                            $sanitized['features'][$feature_name]['start_from'] = max(1, intval($feature_input['start_from'] ?? 1));
                            $sanitized['features'][$feature_name]['prefix'] = sanitize_text_field($feature_input['prefix'] ?? '');
                            $sanitized['features'][$feature_name]['suffix'] = sanitize_text_field($feature_input['suffix'] ?? '.');
                            break;
                            
                        case 'copyText':
                            $sanitized['features'][$feature_name]['default_button_text'] = sanitize_text_field($feature_input['default_button_text'] ?? 'Text kopieren');
                            break;
                            
                        case 'screenshot':
                            $sanitized['features'][$feature_name]['default_button_text'] = sanitize_text_field($feature_input['default_button_text'] ?? 'Screenshot');
                            break;
                    }
                } else {
                    $sanitized['features'][$feature_name] = $feature_defaults;
                }
            }
        }
        
        // Sanitize display settings
        if (isset($input['display'])) {
            $sanitized['display'] = array(
                'show_feature_hints' => !empty($input['display']['show_feature_hints']),
                'editor_preview_mode' => in_array($input['display']['editor_preview_mode'], ['full', 'minimal', 'none']) 
                    ? $input['display']['editor_preview_mode'] 
                    : 'full'
            );
        } else {
            $sanitized['display'] = $defaults['display'];
        }
        
        return $sanitized;
    }
    
    /**
     * Settings page HTML
     */
    public static function settings_page() {
        $settings = self::get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Container Block Designer - Globale Einstellungen', 'container-block-designer'); ?></h1>
            
            <div class="cbd-settings-container">
                <form method="post" action="options.php" id="cbd-global-settings-form">
                    <?php settings_fields('cbd_global_settings'); ?>
                    
                    <div class="cbd-settings-grid">
                        <!-- Feature Settings -->
                        <div class="cbd-settings-section">
                            <h2><?php echo esc_html__('Standard Feature-Einstellungen', 'container-block-designer'); ?></h2>
                            <p class="description">
                                <?php echo esc_html__('Diese Einstellungen werden als Standard für alle neuen Container-Blöcke verwendet. Einzelne Blöcke können diese überschreiben.', 'container-block-designer'); ?>
                            </p>
                            
                            <?php foreach ($settings['features'] as $feature_name => $feature_settings): ?>
                                <div class="cbd-feature-global-setting">
                                    <h3><?php echo esc_html(self::get_feature_title($feature_name)); ?></h3>
                                    
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php echo esc_html__('Standardmäßig aktiviert', 'container-block-designer'); ?></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" 
                                                           name="<?php echo self::OPTION_NAME; ?>[features][<?php echo $feature_name; ?>][enabled_by_default]" 
                                                           value="1" 
                                                           <?php checked($feature_settings['enabled_by_default']); ?> />
                                                    <?php echo esc_html__('Neuen Blöcken standardmäßig aktivieren', 'container-block-designer'); ?>
                                                </label>
                                            </td>
                                        </tr>
                                        
                                        <tr>
                                            <th scope="row"><?php echo esc_html__('Block-Level-Überschreibung erlauben', 'container-block-designer'); ?></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" 
                                                           name="<?php echo self::OPTION_NAME; ?>[features][<?php echo $feature_name; ?>][allow_override]" 
                                                           value="1" 
                                                           <?php checked($feature_settings['allow_override']); ?> />
                                                    <?php echo esc_html__('Einzelne Blöcke können diese Einstellung überschreiben', 'container-block-designer'); ?>
                                                </label>
                                            </td>
                                        </tr>
                                        
                                        <?php self::render_feature_specific_settings($feature_name, $feature_settings); ?>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Display Settings -->
                        <div class="cbd-settings-section">
                            <h2><?php echo esc_html__('Editor-Einstellungen', 'container-block-designer'); ?></h2>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php echo esc_html__('Feature-Hinweise anzeigen', 'container-block-designer'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" 
                                                   name="<?php echo self::OPTION_NAME; ?>[display][show_feature_hints]" 
                                                   value="1" 
                                                   <?php checked($settings['display']['show_feature_hints']); ?> />
                                            <?php echo esc_html__('Zeige Hilfe-Texte für Features im Editor', 'container-block-designer'); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php echo esc_html__('Editor-Vorschau-Modus', 'container-block-designer'); ?></th>
                                    <td>
                                        <select name="<?php echo self::OPTION_NAME; ?>[display][editor_preview_mode]">
                                            <option value="full" <?php selected($settings['display']['editor_preview_mode'], 'full'); ?>>
                                                <?php echo esc_html__('Vollständige Vorschau', 'container-block-designer'); ?>
                                            </option>
                                            <option value="minimal" <?php selected($settings['display']['editor_preview_mode'], 'minimal'); ?>>
                                                <?php echo esc_html__('Minimale Vorschau', 'container-block-designer'); ?>
                                            </option>
                                            <option value="none" <?php selected($settings['display']['editor_preview_mode'], 'none'); ?>>
                                                <?php echo esc_html__('Keine Vorschau', 'container-block-designer'); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button-primary" value="<?php echo esc_attr__('Einstellungen speichern', 'container-block-designer'); ?>" />
                        <button type="button" id="cbd-reset-global-settings" class="button"><?php echo esc_html__('Zurücksetzen', 'container-block-designer'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <style>
        .cbd-settings-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            max-width: 1200px;
        }
        
        .cbd-settings-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .cbd-feature-global-setting {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .cbd-feature-global-setting:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .cbd-feature-global-setting h3 {
            margin-top: 0;
            color: #23282d;
        }
        </style>
        <?php
    }
    
    /**
     * Get feature title for display
     */
    private static function get_feature_title($feature_name) {
        $titles = array(
            'icon' => __('Block Icon', 'container-block-designer'),
            'collapse' => __('Ein-/Ausklappbar', 'container-block-designer'),
            'numbering' => __('Nummerierung', 'container-block-designer'),
            'copyText' => __('Text kopieren', 'container-block-designer'),
            'screenshot' => __('Screenshot', 'container-block-designer')
        );
        
        return $titles[$feature_name] ?? ucfirst($feature_name);
    }
    
    /**
     * Render feature-specific settings
     */
    private static function render_feature_specific_settings($feature_name, $feature_settings) {
        switch ($feature_name) {
            case 'icon':
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Standard Icon', 'container-block-designer'); ?></th>
                    <td>
                        <select name="<?php echo self::OPTION_NAME; ?>[features][icon][default_value]">
                            <?php
                            $icons = array(
                                'dashicons-admin-generic' => __('Standard', 'container-block-designer'),
                                'dashicons-info' => __('Info', 'container-block-designer'),
                                'dashicons-warning' => __('Warnung', 'container-block-designer'),
                                'dashicons-lightbulb' => __('Glühbirne', 'container-block-designer'),
                                'dashicons-star-filled' => __('Stern', 'container-block-designer'),
                                'dashicons-heart' => __('Herz', 'container-block-designer'),
                            );
                            
                            foreach ($icons as $value => $label) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($value),
                                    selected($feature_settings['default_value'], $value, false),
                                    esc_html($label)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <?php
                break;
                
            case 'collapse':
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Standard-Zustand', 'container-block-designer'); ?></th>
                    <td>
                        <select name="<?php echo self::OPTION_NAME; ?>[features][collapse][default_state]">
                            <option value="expanded" <?php selected($feature_settings['default_state'], 'expanded'); ?>>
                                <?php echo esc_html__('Ausgeklappt', 'container-block-designer'); ?>
                            </option>
                            <option value="collapsed" <?php selected($feature_settings['default_state'], 'collapsed'); ?>>
                                <?php echo esc_html__('Eingeklappt', 'container-block-designer'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <?php
                break;
                
            case 'numbering':
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Standard-Format', 'container-block-designer'); ?></th>
                    <td>
                        <select name="<?php echo self::OPTION_NAME; ?>[features][numbering][default_format]">
                            <option value="numeric" <?php selected($feature_settings['default_format'], 'numeric'); ?>>
                                <?php echo esc_html__('Numerisch (1, 2, 3)', 'container-block-designer'); ?>
                            </option>
                            <option value="alpha" <?php selected($feature_settings['default_format'], 'alpha'); ?>>
                                <?php echo esc_html__('Alphabetisch (A, B, C)', 'container-block-designer'); ?>
                            </option>
                            <option value="roman" <?php selected($feature_settings['default_format'], 'roman'); ?>>
                                <?php echo esc_html__('Römisch (I, II, III)', 'container-block-designer'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Beginne bei', 'container-block-designer'); ?></th>
                    <td>
                        <input type="number" 
                               name="<?php echo self::OPTION_NAME; ?>[features][numbering][start_from]" 
                               value="<?php echo esc_attr($feature_settings['start_from']); ?>" 
                               min="1" max="100" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Prefix', 'container-block-designer'); ?></th>
                    <td>
                        <input type="text" 
                               name="<?php echo self::OPTION_NAME; ?>[features][numbering][prefix]" 
                               value="<?php echo esc_attr($feature_settings['prefix']); ?>" 
                               class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Suffix', 'container-block-designer'); ?></th>
                    <td>
                        <input type="text" 
                               name="<?php echo self::OPTION_NAME; ?>[features][numbering][suffix]" 
                               value="<?php echo esc_attr($feature_settings['suffix']); ?>" 
                               class="regular-text" />
                    </td>
                </tr>
                <?php
                break;
                
            case 'copyText':
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Standard Button-Text', 'container-block-designer'); ?></th>
                    <td>
                        <input type="text" 
                               name="<?php echo self::OPTION_NAME; ?>[features][copyText][default_button_text]" 
                               value="<?php echo esc_attr($feature_settings['default_button_text']); ?>" 
                               class="regular-text" />
                    </td>
                </tr>
                <?php
                break;
                
            case 'screenshot':
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Standard Button-Text', 'container-block-designer'); ?></th>
                    <td>
                        <input type="text" 
                               name="<?php echo self::OPTION_NAME; ?>[features][screenshot][default_button_text]" 
                               value="<?php echo esc_attr($feature_settings['default_button_text']); ?>" 
                               class="regular-text" />
                    </td>
                </tr>
                <?php
                break;
        }
    }
    
    /**
     * AJAX: Save settings
     */
    public static function save_settings_ajax() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'container-block-designer')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cbd_admin')) {
            wp_send_json_error(array('message' => __('Sicherheitsprüfung fehlgeschlagen', 'container-block-designer')));
        }
        
        $settings = self::sanitize_settings($_POST['settings']);
        update_option(self::OPTION_NAME, $settings);
        
        wp_send_json_success(array('message' => __('Einstellungen gespeichert', 'container-block-designer')));
    }
    
    /**
     * AJAX: Reset settings
     */
    public static function reset_settings_ajax() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'container-block-designer')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'cbd_admin')) {
            wp_send_json_error(array('message' => __('Sicherheitsprüfung fehlgeschlagen', 'container-block-designer')));
        }
        
        delete_option(self::OPTION_NAME);
        
        wp_send_json_success(array('message' => __('Einstellungen zurückgesetzt', 'container-block-designer')));
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'cbd-global-settings') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        // Add inline JavaScript for settings page
        wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            $("#cbd-reset-global-settings").on("click", function(e) {
                e.preventDefault();
                
                if (confirm("' . esc_js(__('Wirklich alle Einstellungen zurücksetzen?', 'container-block-designer')) . '")) {
                    $.post(ajaxurl, {
                        action: "cbd_reset_global_settings",
                        nonce: "' . wp_create_nonce('cbd_admin') . '"
                    }, function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || "Fehler");
                        }
                    });
                }
            });
        });
        ');
    }
}

// Initialize the global settings system
CBD_Global_Settings::init();