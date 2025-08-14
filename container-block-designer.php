<?php
/**
 * Plugin Name:       Container Block Designer
 * Plugin URI:        https://example.com/container-block-designer
 * Description:       Visueller Designer für Container-Blöcke im Gutenberg Editor mit 5 erweiterten Features
 * Version:           2.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Container Block Designer Team
 * License:           GPL v2 or later
 * Text Domain:       container-block-designer
 * Domain Path:       /languages
 * 
 * @package ContainerBlockDesigner
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent double loading
if (defined('CBD_VERSION')) {
    return;
}

// Plugin constants
define('CBD_VERSION', '2.2.0');
define('CBD_FEATURES_VERSION', '2.0.0');
define('CBD_PLUGIN_FILE', __FILE__);
define('CBD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CBD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CBD_TABLE_BLOCKS', $GLOBALS['wpdb']->prefix . 'cbd_blocks');

// Prevent class redeclaration
if (!class_exists('ContainerBlockDesigner')) {

/**
 * Main plugin class
 */
class ContainerBlockDesigner {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Install file is in root directory, not in includes/
        if (file_exists(CBD_PLUGIN_DIR . 'install.php')) {
            require_once CBD_PLUGIN_DIR . 'install.php';
        }
        
        // AJAX handlers
        if (file_exists(CBD_PLUGIN_DIR . 'includes/ajax-handlers.php')) {
            require_once CBD_PLUGIN_DIR . 'includes/ajax-handlers.php';
        }
        
        // REST API - only if file exists
        if (file_exists(CBD_PLUGIN_DIR . 'includes/rest-api.php')) {
            require_once CBD_PLUGIN_DIR . 'includes/rest-api.php';
        }
        
        // Database Fix - NEU HINZUGEFÜGT
        if (file_exists(CBD_PLUGIN_DIR . 'includes/database-fix.php')) {
            require_once CBD_PLUGIN_DIR . 'includes/database-fix.php';
        }

        // Quick Fix Tool - NEU HINZUFÜGEN
        if (file_exists(CBD_PLUGIN_DIR . 'quickfix.php')) {
            require_once CBD_PLUGIN_DIR . 'quickfix.php';
        }
        // Nach den anderen require_once Statements:
        if (file_exists(CBD_PLUGIN_DIR . 'includes/fix-localization.php')) {
           require_once CBD_PLUGIN_DIR . 'includes/fix-localization.php';
        }

        // AJAX URL sicherstellen
        if (file_exists(CBD_PLUGIN_DIR . 'includes/ensure-ajaxurl.php')) {
            require_once CBD_PLUGIN_DIR . 'includes/ensure-ajaxurl.php';
        }

        // Admin includes
        if (is_admin()) {
            // Admin Features Class
            if (file_exists(CBD_PLUGIN_DIR . 'includes/Admin/class-admin-features.php')) {
                require_once CBD_PLUGIN_DIR . 'includes/Admin/class-admin-features.php';
            }
        }
        
        // Frontend includes
        if (!is_admin()) {
            // Frontend Renderer
            if (file_exists(CBD_PLUGIN_DIR . 'includes/Blocks/block-frontend-renderer.php')) {
                require_once CBD_PLUGIN_DIR . 'includes/Blocks/block-frontend-renderer.php';
            }
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(CBD_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(CBD_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Init
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Fix database on admin init
        add_action('admin_init', array($this, 'fix_database_structure'));
        
        // Block registration
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Dynamic CSS
        add_action('wp_head', array($this, 'output_dynamic_css'), 100);
        add_action('admin_head', array($this, 'output_admin_dynamic_css'), 100);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Fix database structure first
        $this->fix_database_structure();
        
        // Call install function if exists
        if (function_exists('cbd_install')) {
            cbd_install();
        }
        
        // Run database fix if function exists
        if (function_exists('cbd_run_database_fix')) {
            cbd_run_database_fix();
        }
        
        flush_rewrite_rules();
    }
    
    /**
     * Fix database structure - Add missing columns
     */
    public function fix_database_structure() {
        global $wpdb;
        $table_name = CBD_TABLE_BLOCKS;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if ($table_exists) {
            // Check for missing columns
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
            $existing_columns = array();
            
            foreach ($columns as $column) {
                $existing_columns[] = $column->Field;
            }
            
            // Add 'created' column if missing
            if (!in_array('created', $existing_columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN created datetime DEFAULT CURRENT_TIMESTAMP");
            }
            
            // Add 'modified' column if missing
            if (!in_array('modified', $existing_columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
            
            // Add 'features' column if missing
            if (!in_array('features', $existing_columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN features longtext AFTER config");
            }
        } else {
            // Create table if it doesn't exist
            if (function_exists('cbd_create_tables')) {
                cbd_create_tables();
            }
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize features system if class exists
        if (class_exists('ContainerBlockDesigner\Admin\Admin_Features')) {
            ContainerBlockDesigner\Admin\Admin_Features::init();
        }
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'container-block-designer',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $table_name = CBD_TABLE_BLOCKS;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            config longtext,
            features longtext,
            css_variables text,
            status varchar(20) DEFAULT 'active',
            created_by bigint(20) DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('cbd_db_version', CBD_VERSION);
    }
    
    /**
     * Check database and update if needed
     */
    private function check_database() {
        $installed_version = get_option('cbd_db_version');
        
        if ($installed_version !== CBD_VERSION) {
            $this->create_database_tables();
            
            // Run features migration if needed
            $migration_file = CBD_PLUGIN_DIR . 'includes/Database/cbd-migration-features.php';
            if (file_exists($migration_file)) {
                require_once $migration_file;
                if (class_exists('\ContainerBlockDesigner\Database\Migration_Features')) {
                    if (\ContainerBlockDesigner\Database\Migration_Features::is_needed()) {
                        \ContainerBlockDesigner\Database\Migration_Features::up();
                    }
                }
            }
        }
    }
    
    /**
     * Register blocks
     */
    public function register_blocks() {
        register_block_type('container-block-designer/container', [
            'editor_script' => 'cbd-container-block',
            'editor_style' => 'cbd-container-block',
            'style' => 'cbd-container-block',
            'render_callback' => [$this, 'render_container_block'],
            'attributes' => [
                'selectedBlock' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'customClasses' => [
                    'type' => 'string',
                    'default' => ''
                ]
            ]
        ]);
    }
    
    /**
     * Render container block
     */
    public function render_container_block($attributes, $content) {
        $selected_block = isset($attributes['selectedBlock']) ? $attributes['selectedBlock'] : '';
        $custom_classes = $attributes['customClasses'] ?? '';
        
        if (empty($selected_block)) {
            return '<div class="cbd-container-placeholder">' . __('Bitte wählen Sie einen Container Block aus.', 'container-block-designer') . '</div>';
        }
        
        // Get block data
        global $wpdb;
        $block = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE slug = %s AND status = 'active'",
            $selected_block
        ));
        
        if (!$block) {
            return '<div class="cbd-container-error">' . __('Container Block nicht gefunden.', 'container-block-designer') . '</div>';
        }
        
        $config = json_decode($block->config, true);
        $features = json_decode($block->features, true);
        
        // Build container classes
        $container_classes = array('cbd-container', 'cbd-' . esc_attr($selected_block));
        if (!empty($custom_classes)) {
            $container_classes[] = esc_attr($custom_classes);
        }
        
        // Start output
        $output = '<div class="' . implode(' ', $container_classes) . '" data-block-id="' . esc_attr($block->id) . '">';
        
        // Add features wrapper if any features are enabled
        if ($features && is_array($features)) {
            $has_features = false;
            foreach ($features as $feature) {
                if (isset($feature['enabled']) && $feature['enabled']) {
                    $has_features = true;
                    break;
                }
            }
            
            if ($has_features) {
                $output .= '<div class="cbd-features-wrapper" data-features="' . esc_attr(json_encode($features)) . '">';
            }
        }
        
        // Add content
        $output .= '<div class="cbd-content">' . $content . '</div>';
        
        // Close features wrapper if needed
        if (isset($has_features) && $has_features) {
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        // Container block script
        wp_enqueue_script(
            'cbd-container-block',
            CBD_PLUGIN_URL . 'assets/js/container-block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch'),
            CBD_VERSION,
            true
        );
        
        // Container block styles
        wp_enqueue_style(
            'cbd-container-block',
            CBD_PLUGIN_URL . 'assets/css/container-block.css',
            array('wp-edit-blocks'),
            CBD_VERSION
        );
        
        // Pass data to JavaScript
        wp_localize_script('cbd-container-block', 'cbdData', array(
            'apiUrl' => home_url('/wp-json/cbd/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'blocks' => $this->get_active_blocks_for_editor(),
            'pluginUrl' => CBD_PLUGIN_URL
        ));
    }
    
    /**
     * Enqueue block assets (frontend + editor)
     */
    public function enqueue_block_assets() {
        // Features styles
        wp_enqueue_style(
            'cbd-advanced-features',
            CBD_PLUGIN_URL . 'assets/css/advanced-features.css',
            array(),
            CBD_VERSION
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Advanced features script
        wp_enqueue_script(
            'cbd-advanced-features',
            CBD_PLUGIN_URL . 'assets/js/advanced-features.js',
            array('jquery'),
            CBD_VERSION,
            true
        );
        
        // Html2canvas for screenshot feature
        wp_enqueue_script(
            'html2canvas',
            'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
            array(),
            '1.4.1',
            true
        );
        
        // Localize script
        wp_localize_script('cbd-advanced-features', 'cbdFeatures', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cbd-features'),
            'i18n' => array(
                'copySuccess' => __('Text erfolgreich kopiert!', 'container-block-designer'),
                'copyError' => __('Fehler beim Kopieren', 'container-block-designer'),
                'screenshotSaved' => __('Screenshot gespeichert!', 'container-block-designer'),
                'screenshotError' => __('Fehler beim Erstellen des Screenshots', 'container-block-designer')
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on our admin pages
        if (strpos($hook, 'container-block-designer') === false) {
            return;
        }
        
        // Admin styles
        wp_enqueue_style(
            'cbd-admin',
            CBD_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            CBD_VERSION
        );
        
        // Admin features styles
        wp_enqueue_style(
            'cbd-admin-features',
            CBD_PLUGIN_URL . 'assets/css/admin-features.css',
            array(),
            CBD_VERSION
        );
        
        // Admin script
        wp_enqueue_script(
            'cbd-admin',
            CBD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            CBD_VERSION,
            true
        );
        
        // Admin features script
        wp_enqueue_script(
            'cbd-admin-features',
            CBD_PLUGIN_URL . 'assets/js/admin-features.js',
            array('jquery', 'jquery-ui-sortable'),
            CBD_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('cbd-admin', 'cbdAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cbd-admin'),
            'i18n' => array(
                'confirmDelete' => __('Sind Sie sicher, dass Sie diesen Block löschen möchten?', 'container-block-designer'),
                'saving' => __('Speichern...', 'container-block-designer'),
                'saved' => __('Gespeichert!', 'container-block-designer'),
                'error' => __('Ein Fehler ist aufgetreten.', 'container-block-designer')
            )
        ));
        
        // Features specific localization
        wp_localize_script('cbd-admin-features', 'cbdAdminFeatures', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cbd-admin-features'),
            'i18n' => array(
                'saving' => __('Speichern...', 'container-block-designer'),
                'saved' => __('Gespeichert!', 'container-block-designer'),
                'error' => __('Fehler beim Speichern', 'container-block-designer')
            )
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Container Block Designer', 'container-block-designer'),
            __('Container Blocks', 'container-block-designer'),
            'manage_options',
            'container-block-designer',
            array($this, 'render_admin_page'),
            'dashicons-layout',
            30
        );
        
        add_submenu_page(
            'container-block-designer',
            __('Alle Blocks', 'container-block-designer'),
            __('Alle Blocks', 'container-block-designer'),
            'manage_options',
            'container-block-designer',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'container-block-designer',
            __('Neuer Block', 'container-block-designer'),
            __('Neuer Block', 'container-block-designer'),
            'manage_options',
            'container-block-designer-new',
            array($this, 'render_new_block_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        switch ($action) {
            case 'edit':
                if ($id > 0) {
                    $this->render_edit_block_page();
                } else {
                    $this->render_blocks_list_page();
                }
                break;
            case 'new':
                $this->render_new_block_page();
                break;
            default:
                $this->render_blocks_list_page();
                break;
        }
    }
    
    /**
     * Render blocks list page
     */
    public function render_blocks_list_page() {
        $file = CBD_PLUGIN_DIR . 'admin/blocks-list.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            echo '<div class="wrap"><h1>Container Blocks</h1><p>Blocks list file not found.</p></div>';
        }
    }
    
    /**
     * Render edit block page
     */
    public function render_edit_block_page() {
        // Try multiple locations
        $files = array(
            CBD_PLUGIN_DIR . 'admin/views/edit-block.php',
            CBD_PLUGIN_DIR . 'admin/edit-block.php'
        );
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
        
        echo '<div class="wrap"><h1>Block bearbeiten</h1><p>Edit block file not found.</p></div>';
    }
    
    /**
     * Render new block page
     */
    public function render_new_block_page() {
        $file = CBD_PLUGIN_DIR . 'admin/new-block.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            echo '<div class="wrap"><h1>Neuer Block</h1><p>New block file not found.</p></div>';
        }
    }
    
    /**
     * Output dynamic CSS
     */
    public function output_dynamic_css() {
        global $wpdb;
        $blocks = $wpdb->get_results("SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE status = 'active'");
        
        if (empty($blocks)) {
            return;
        }
        
        echo '<style id="cbd-dynamic-styles">';
        
        foreach ($blocks as $block) {
            $config = json_decode($block->config, true);
            $styles = isset($config['styles']) ? $config['styles'] : array();
            
            echo '.cbd-' . esc_attr($block->slug) . ' {';
            
            // Padding
            if (isset($styles['padding'])) {
                echo 'padding: ';
                echo intval($styles['padding']['top']) . 'px ';
                echo intval($styles['padding']['right']) . 'px ';
                echo intval($styles['padding']['bottom']) . 'px ';
                echo intval($styles['padding']['left']) . 'px;';
            }
            
            // Margin  
            if (isset($styles['margin'])) {
                echo 'margin: ';
                echo intval($styles['margin']['top']) . 'px ';
                echo intval($styles['margin']['right']) . 'px ';
                echo intval($styles['margin']['bottom']) . 'px ';
                echo intval($styles['margin']['left']) . 'px;';
            }
            
            if (isset($styles['background']['color'])) {
                echo 'background-color: ' . esc_attr($styles['background']['color']) . ';';
            }
            
            if (isset($styles['text']['color'])) {
                echo 'color: ' . esc_attr($styles['text']['color']) . ';';
            }
            
            if (isset($styles['text']['alignment'])) {
                echo 'text-align: ' . esc_attr($styles['text']['alignment']) . ';';
            }
            
            if (isset($styles['border'])) {
                if (isset($styles['border']['width']) && $styles['border']['width'] > 0) {
                    echo 'border: ' . intval($styles['border']['width']) . 'px solid ';
                    echo esc_attr($styles['border']['color']) . ';';
                }
                if (isset($styles['border']['radius']) && $styles['border']['radius'] > 0) {
                    echo 'border-radius: ' . intval($styles['border']['radius']) . 'px;';
                }
            }
            
            echo '}';
        }
        
        echo '</style>';
    }
    
    /**
     * Output admin dynamic CSS
     */
    public function output_admin_dynamic_css() {
        $this->output_dynamic_css();
    }
    
    /**
     * Get active blocks for editor
     */
    private function get_active_blocks_for_editor() {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . CBD_TABLE_BLOCKS . "'");
        if (!$table_exists) {
            return array();
        }
        
        $blocks = $wpdb->get_results(
            "SELECT id, name, slug, description FROM " . CBD_TABLE_BLOCKS . " WHERE status = 'active' ORDER BY name",
            ARRAY_A
        );
        
        return $blocks ? $blocks : array();
    }
}

} // End if class_exists check

// Initialize plugin
ContainerBlockDesigner::get_instance();