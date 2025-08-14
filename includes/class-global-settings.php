<?php
/**
 * Global Settings System für Container Block Designer
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
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'), 20);
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
        
        if (isset($input['features'])) {
            foreach ($defaults['features'] as $feature_name => $feature_defaults) {
                if (isset($input['features'][$feature_name])) {
                    $feature_input = $input['features'][$feature_name];
                    $sanitized['features'][$feature_name] = array();
                    
                    $sanitized['features'][$feature_name]['enabled_by_default'] = !empty($feature_input['enabled_by_default']);
                    $sanitized['features'][$feature_name]['allow_override'] = !empty($feature_input['allow_override']);
                    
                    switch ($feature_name) {
                        case 'icon':
                            $sanitized['features'][$feature_name]['default_value'] = sanitize_text_field($feature_input['default_value'] ?? 'dashicons-admin-generic');
                            break;
                            
                        case 'collapse':
                            $sanitized['features'][$feature_name]['default_state'] = in_array($feature_input['default_state'] ?? 'expanded', ['expanded', 'collapsed']) 
                                ? $feature_input['default_state'] 
                                : 'expanded';
                            break;
                            
                        case 'numbering':
                            $sanitized['features'][$feature_name]['default_format'] = in_array($feature_input['default_format'] ?? 'numeric', ['numeric', 'alpha', 'roman']) 
                                ? $feature_input['default_format'] 
                                : 'numeric';
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
        
        return $sanitized;
    }
    
    /**
     * Settings page HTML
     */
    public static function settings_page() {
        $settings = self::get_settings();
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'cbd_global_settings-options')) {
            $updated_settings = self::sanitize_settings($_POST[self::OPTION_NAME]);
            update_option(self::OPTION_NAME, $updated_settings);
            
            echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert.', 'container-block-designer') . '</p></div>';
            $settings = $updated_settings;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Container Block Designer - Globale Einstellungen', 'container-block-designer'); ?></h1>
            
            <form method="post" action="">
                <?php settings_fields('cbd_global_settings'); ?>
                <?php wp_nonce_field('cbd_global_settings-options'); ?>
                
                <h2><?php echo esc_html__('Standard Feature-Einstellungen', 'container-block-designer'); ?></h2>
                <p><?php echo esc_html__('Diese Einstellungen werden als Standard für alle neuen Container-Blöcke verwendet.', 'container-block-designer'); ?></p>
                
                <table class="form-table">
                    <?php foreach ($settings['features'] as $feature_name => $feature_settings): ?>
                        <tr>
                            <th scope="row">
                                <strong><?php echo esc_html(self::get_feature_title($feature_name)); ?></strong>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" 
                                               name="<?php echo self::OPTION_NAME; ?>[features][<?php echo $feature_name; ?>][enabled_by_default]" 
                                               value="1" 
                                               <?php checked($feature_settings['enabled_by_default']); ?> />
                                        <?php echo esc_html__('Standardmäßig aktiviert', 'container-block-designer'); ?>
                                    </label>
                                    <br><br>
                                    
                                    <label>
                                        <input type="checkbox" 
                                               name="<?php echo self::OPTION_NAME; ?>[features][<?php echo $feature_name; ?>][allow_override]" 
                                               value="1" 
                                               <?php checked($feature_settings['allow_override']); ?> />
                                        <?php echo esc_html__('Block-Level-Überschreibung erlauben', 'container-block-designer'); ?>
                                    </label>
                                    
                                    <?php self::render_feature_specific_settings($feature_name, $feature_settings); ?>
                                </fieldset>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h3><?php echo esc_html__('Debug-Informationen', 'container-block-designer'); ?></h3>
            <p>
                <strong>Plugin-Version:</strong> <?php echo CBD_VERSION; ?><br>
                <strong>Einstellungen-Speicher:</strong> <?php echo self::OPTION_NAME; ?><br>
                <strong>Gespeicherte Daten:</strong>
            </p>
            <textarea readonly style="width: 100%; height: 200px;"><?php echo esc_textarea(print_r($settings, true)); ?></textarea>
        </div>
        
        <style>
        .form-table th {
            width: 200px;
        }
        .form-table fieldset {
            max-width: 600px;
        }
        .form-table input[type="text"], 
        .form-table select {
            width: 300px;
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
        echo '<br><br>';
        
        switch ($feature_name) {
            case 'icon':
                echo '<label>' . __('Standard Icon:', 'container-block-designer') . '</label><br>';
                echo '<select name="' . self::OPTION_NAME . '[features][icon][default_value]">';
                
                $icons = array(
                    'dashicons-admin-generic' => __('Standard', 'container-block-designer'),
                    'dashicons-info' => __('Info', 'container-block-designer'),
                    'dashicons-warning' => __('Warnung', 'container-block-designer'),
                    'dashicons-lightbulb' => __('Glühbirne', 'container-block-designer'),
                    'dashicons-star-filled' => __('Stern', 'container-block-designer')
                );
                
                foreach ($icons as $value => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($value),
                        selected($feature_settings['default_value'] ?? 'dashicons-admin-generic', $value, false),
                        esc_html($label)
                    );
                }
                echo '</select>';
                break;
                
            case 'collapse':
                echo '<label>' . __('Standard-Zustand:', 'container-block-designer') . '</label><br>';
                echo '<select name="' . self::OPTION_NAME . '[features][collapse][default_state]">';
                echo '<option value="expanded" ' . selected($feature_settings['default_state'] ?? 'expanded', 'expanded', false) . '>' . __('Ausgeklappt', 'container-block-designer') . '</option>';
                echo '<option value="collapsed" ' . selected($feature_settings['default_state'] ?? 'expanded', 'collapsed', false) . '>' . __('Eingeklappt', 'container-block-designer') . '</option>';
                echo '</select>';
                break;
                
            case 'numbering':
                echo '<label>' . __('Standard-Format:', 'container-block-designer') . '</label><br>';
                echo '<select name="' . self::OPTION_NAME . '[features][numbering][default_format]">';
                echo '<option value="numeric" ' . selected($feature_settings['default_format'] ?? 'numeric', 'numeric', false) . '>' . __('Numerisch (1, 2, 3)', 'container-block-designer') . '</option>';
                echo '<option value="alpha" ' . selected($feature_settings['default_format'] ?? 'numeric', 'alpha', false) . '>' . __('Alphabetisch (A, B, C)', 'container-block-designer') . '</option>';
                echo '<option value="roman" ' . selected($feature_settings['default_format'] ?? 'numeric', 'roman', false) . '>' . __('Römisch (I, II, III)', 'container-block-designer') . '</option>';
                echo '</select>';
                break;
                
            case 'copyText':
                echo '<label>' . __('Standard Button-Text:', 'container-block-designer') . '</label><br>';
                echo '<input type="text" name="' . self::OPTION_NAME . '[features][copyText][default_button_text]" value="' . esc_attr($feature_settings['default_button_text'] ?? 'Text kopieren') . '" />';
                break;
                
            case 'screenshot':
                echo '<label>' . __('Standard Button-Text:', 'container-block-designer') . '</label><br>';
                echo '<input type="text" name="' . self::OPTION_NAME . '[features][screenshot][default_button_text]" value="' . esc_attr($feature_settings['default_button_text'] ?? 'Screenshot') . '" />';
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
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cbd_admin')) {
            wp_send_json_error(array('message' => __('Sicherheitsprüfung fehlgeschlagen', 'container-block-designer')));
        }
        
        $settings = self::sanitize_settings($_POST['settings'] ?? array());
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
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cbd_admin')) {
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
    }
}

// Initialize the global settings system
if (class_exists('CBD_Global_Settings')) {
    CBD_Global_Settings::init();
}