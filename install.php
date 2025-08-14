<?php
/**
 * Container Block Designer - Installation
 * Version: 2.2.0
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Run installation
 */
function cbd_install() {
    cbd_create_tables();
    cbd_create_default_blocks();
    cbd_create_directories();
    cbd_migrate_existing_data();
    
    // Set version
    update_option('cbd_version', CBD_VERSION);
    update_option('cbd_features_version', '2.0.0');
}

/**
 * Create database tables
 */
function cbd_create_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cbd_blocks';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        description text,
        config longtext,
        features longtext,
        status varchar(20) DEFAULT 'active',
        created datetime DEFAULT CURRENT_TIMESTAMP,
        modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Check if features column exists and add if missing
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    $has_features_column = false;
    
    foreach ($columns as $column) {
        if ($column->Field === 'features') {
            $has_features_column = true;
            break;
        }
    }
    
    if (!$has_features_column) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN features longtext AFTER config");
    }
}

/**
 * Create default blocks
 */
function cbd_create_default_blocks() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cbd_blocks';
    
    // Check if table is empty
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    if ($count == 0) {
        // Default blocks with all 5 features
        $default_blocks = array(
            array(
                'name' => 'Hero Section',
                'slug' => 'hero-section',
                'description' => 'Großer Header-Bereich für wichtige Inhalte',
                'config' => json_encode(array(
                    'styles' => array(
                        'padding' => array('top' => 60, 'right' => 30, 'bottom' => 60, 'left' => 30),
                        'background' => array('color' => '#2271b1'),
                        'text' => array('color' => '#ffffff', 'alignment' => 'center'),
                        'border' => array('width' => 0, 'radius' => 0)
                    )
                )),
                'features' => json_encode(array(
                    'icon' => array('enabled' => true, 'value' => 'dashicons-megaphone'),
                    'collapse' => array('enabled' => false, 'defaultState' => 'expanded'),
                    'numbering' => array('enabled' => false, 'format' => 'numeric'),
                    'copyText' => array('enabled' => false, 'buttonText' => 'Text kopieren'),
                    'screenshot' => array('enabled' => false, 'buttonText' => 'Screenshot')
                )),
                'status' => 'active',
                'created' => current_time('mysql'),
                'modified' => current_time('mysql')
            ),
            array(
                'name' => 'Content Section',
                'slug' => 'content-section',
                'description' => 'Standard-Inhaltsbereich',
                'config' => json_encode(array(
                    'styles' => array(
                        'padding' => array('top' => 40, 'right' => 20, 'bottom' => 40, 'left' => 20),
                        'background' => array('color' => '#f8f9fa'),
                        'text' => array('color' => '#333333', 'alignment' => 'left'),
                        'border' => array('width' => 1, 'color' => '#e0e0e0', 'radius' => 4)
                    )
                )),
                'features' => json_encode(array(
                    'icon' => array('enabled' => false, 'value' => 'dashicons-admin-page'),
                    'collapse' => array('enabled' => true, 'defaultState' => 'expanded'),
                    'numbering' => array('enabled' => true, 'format' => 'numeric'),
                    'copyText' => array('enabled' => true, 'buttonText' => 'Text kopieren'),
                    'screenshot' => array('enabled' => false, 'buttonText' => 'Screenshot')
                )),
                'status' => 'active',
                'created' => current_time('mysql'),
                'modified' => current_time('mysql')
            ),
            array(
                'name' => 'Call to Action',
                'slug' => 'cta-section',
                'description' => 'Bereich für Handlungsaufforderungen',
                'config' => json_encode(array(
                    'styles' => array(
                        'padding' => array('top' => 50, 'right' => 30, 'bottom' => 50, 'left' => 30),
                        'background' => array('color' => '#00a32a'),
                        'text' => array('color' => '#ffffff', 'alignment' => 'center'),
                        'border' => array('width' => 0, 'radius' => 8)
                    )
                )),
                'features' => json_encode(array(
                    'icon' => array('enabled' => true, 'value' => 'dashicons-megaphone'),
                    'collapse' => array('enabled' => false, 'defaultState' => 'expanded'),
                    'numbering' => array('enabled' => false, 'format' => 'numeric'),
                    'copyText' => array('enabled' => false, 'buttonText' => 'Text kopieren'),
                    'screenshot' => array('enabled' => true, 'buttonText' => 'Als Bild speichern')
                )),
                'status' => 'active',
                'created' => current_time('mysql'),
                'modified' => current_time('mysql')
            )
        );
        
        foreach ($default_blocks as $block) {
            $wpdb->insert($table_name, $block);
        }
    }
}

/**
 * Create necessary directories
 */
function cbd_create_directories() {
    $upload_dir = wp_upload_dir();
    $cbd_dir = $upload_dir['basedir'] . '/cbd-blocks';
    
    if (!file_exists($cbd_dir)) {
        wp_mkdir_p($cbd_dir);
        
        // Create .htaccess for security
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "<FilesMatch '\.(php|php3|php4|php5|php7|phtml|pl|py|jsp|asp|sh|cgi)$'>\n";
        $htaccess_content .= "    Order Deny,Allow\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        
        file_put_contents($cbd_dir . '/.htaccess', $htaccess_content);
    }
}

/**
 * Migrate existing data to include all 5 features
 */
function cbd_migrate_existing_data() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cbd_blocks';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        return;
    }
    
    // Get all blocks
    $blocks = $wpdb->get_results("SELECT id, features FROM $table_name");
    
    foreach ($blocks as $block) {
        $features = json_decode($block->features, true);
        
        // If features is empty or incomplete, add missing features
        if (empty($features) || count($features) < 5) {
            $default_features = array(
                'icon' => array('enabled' => false, 'value' => 'dashicons-admin-generic'),
                'collapse' => array('enabled' => false, 'defaultState' => 'expanded'),
                'numbering' => array('enabled' => false, 'format' => 'numeric'),
                'copyText' => array('enabled' => false, 'buttonText' => 'Text kopieren'),
                'screenshot' => array('enabled' => false, 'buttonText' => 'Screenshot')
            );
            
            if (is_array($features)) {
                // Keep existing features and add missing ones
                $updated_features = array_merge($default_features, $features);
            } else {
                // Use defaults if features is not valid
                $updated_features = $default_features;
            }
            
            // Update the block with all 5 features
            $wpdb->update(
                $table_name,
                array('features' => json_encode($updated_features)),
                array('id' => $block->id),
                array('%s'),
                array('%d')
            );
        }
    }
}

/**
 * Run updates if needed
 */
function cbd_check_updates() {
    $current_version = get_option('cbd_version', '1.0.0');
    $features_version = get_option('cbd_features_version', '1.0.0');
    
    // Update to 2.2.0 - Add all 5 features
    if (version_compare($features_version, '2.0.0', '<')) {
        cbd_migrate_existing_data();
        update_option('cbd_features_version', '2.0.0');
    }
    
    // Update main version
    if (version_compare($current_version, CBD_VERSION, '<')) {
        cbd_create_tables();
        cbd_migrate_existing_data();
        update_option('cbd_version', CBD_VERSION);
    }
}

// Hook into activation
register_activation_hook(CBD_PLUGIN_FILE, 'cbd_install');