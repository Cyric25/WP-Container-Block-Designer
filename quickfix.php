<?php
/**
 * Container Block Designer - Quick Fix Script
 * Version: 1.0.0
 * 
 * Dieses Skript behebt automatisch die häufigsten Probleme
 * Speichern Sie es als quickfix.php im Plugin-Hauptverzeichnis
 * Rufen Sie es auf über: /wp-admin/admin.php?page=cbd-quickfix
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Add to admin menu
add_action('admin_menu', function() {
    add_submenu_page(
        null, // Hidden page
        'CBD Quick Fix',
        'CBD Quick Fix',
        'manage_options',
        'cbd-quickfix',
        'cbd_quickfix_page'
    );
});

/**
 * Quick Fix Page
 */
function cbd_quickfix_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung');
    }
    
    $fixes_applied = array();
    $errors = array();
    
    // Apply fixes if requested
    if (isset($_POST['apply_fixes']) && wp_verify_nonce($_POST['_wpnonce'], 'cbd_quickfix')) {
        
        // Fix 1: Ensure database table exists with correct structure
        if (isset($_POST['fix_database'])) {
            $result = cbd_fix_database_structure();
            if ($result) {
                $fixes_applied[] = '✅ Datenbank-Struktur repariert';
            } else {
                $errors[] = '❌ Fehler bei Datenbank-Reparatur';
            }
        }
        
        // Fix 2: Create sample block if none exists
        if (isset($_POST['create_sample'])) {
            $result = cbd_create_sample_block();
            if ($result) {
                $fixes_applied[] = '✅ Beispiel-Block erstellt';
            } else {
                $errors[] = '❌ Beispiel-Block konnte nicht erstellt werden';
            }
        }
        
        // Fix 3: Register block category
        if (isset($_POST['fix_category'])) {
            $result = cbd_fix_block_category();
            if ($result) {
                $fixes_applied[] = '✅ Block-Kategorie registriert';
            } else {
                $errors[] = '❌ Block-Kategorie konnte nicht registriert werden';
            }
        }
        
        // Fix 4: Clear caches
        if (isset($_POST['clear_cache'])) {
            cbd_clear_all_caches();
            $fixes_applied[] = '✅ Cache geleert';
        }
        
        // Fix 5: Re-register scripts
        if (isset($_POST['reregister_scripts'])) {
            cbd_reregister_scripts();
            $fixes_applied[] = '✅ Scripts neu registriert';
        }
    }
    
    // Get current status
    $status = cbd_get_plugin_status();
    ?>
    
    <div class="wrap">
        <h1>🔧 Container Block Designer - Quick Fix</h1>
        
        <?php if (!empty($fixes_applied)): ?>
        <div class="notice notice-success">
            <h3>Angewendete Fixes:</h3>
            <ul>
                <?php foreach ($fixes_applied as $fix): ?>
                    <li><?php echo $fix; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <h3>Fehler:</h3>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>📊 Aktueller Status</h2>
            <table class="widefat">
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?php echo PHP_VERSION; ?> <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <td><strong>WordPress Version:</strong></td>
                    <td><?php echo get_bloginfo('version'); ?> <?php echo version_compare(get_bloginfo('version'), '6.0', '>=') ? '✅' : '❌'; ?></td>
                </tr>
                <tr>
                    <td><strong>Plugin Version:</strong></td>
                    <td><?php echo defined('CBD_VERSION') ? CBD_VERSION : 'NICHT DEFINIERT'; ?></td>
                </tr>
                <tr>
                    <td><strong>Datenbank-Tabelle:</strong></td>
                    <td><?php echo $status['table_exists'] ? '✅ Existiert' : '❌ Fehlt'; ?></td>
                </tr>
                <tr>
                    <td><strong>Anzahl Blocks:</strong></td>
                    <td><?php echo $status['block_count']; ?></td>
                </tr>
                <tr>
                    <td><strong>Block im Editor registriert:</strong></td>
                    <td id="block-registered">Wird geprüft...</td>
                </tr>
                <tr>
                    <td><strong>Admin Scripts geladen:</strong></td>
                    <td><?php echo $status['admin_scripts'] ? '✅ Ja' : '❌ Nein'; ?></td>
                </tr>
                <tr>
                    <td><strong>REST API aktiv:</strong></td>
                    <td><?php echo $status['rest_api'] ? '✅ Ja' : '❌ Nein'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2>🛠️ Verfügbare Fixes</h2>
            <form method="post" action="">
                <?php wp_nonce_field('cbd_quickfix'); ?>
                
                <p>
                    <label>
                        <input type="checkbox" name="fix_database" value="1" checked>
                        <strong>Datenbank-Struktur reparieren</strong><br>
                        <span class="description">Stellt sicher, dass alle Tabellen und Spalten korrekt sind</span>
                    </label>
                </p>
                
                <?php if ($status['block_count'] == 0): ?>
                <p>
                    <label>
                        <input type="checkbox" name="create_sample" value="1" checked>
                        <strong>Beispiel-Block erstellen</strong><br>
                        <span class="description">Erstellt einen funktionierenden Test-Block</span>
                    </label>
                </p>
                <?php endif; ?>
                
                <p>
                    <label>
                        <input type="checkbox" name="fix_category" value="1" checked>
                        <strong>Block-Kategorie registrieren</strong><br>
                        <span class="description">Registriert die "Design Blocks" Kategorie im Editor</span>
                    </label>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="clear_cache" value="1" checked>
                        <strong>Cache leeren</strong><br>
                        <span class="description">Leert WordPress Cache und Transients</span>
                    </label>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="reregister_scripts" value="1" checked>
                        <strong>Scripts neu registrieren</strong><br>
                        <span class="description">Registriert alle JavaScript und CSS Dateien neu</span>
                    </label>
                </p>
                
                <p>
                    <input type="submit" name="apply_fixes" class="button button-primary button-hero" value="🚀 Fixes anwenden">
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>🔍 JavaScript Debug</h2>
            <p>Öffne die Browser-Konsole (F12) um die Ergebnisse zu sehen:</p>
            <button type="button" class="button" onclick="testBlockRegistration()">Block-Registrierung testen</button>
            <button type="button" class="button" onclick="testAjax()">AJAX testen</button>
            <button type="button" class="button" onclick="testRestApi()">REST API testen</button>
            <div id="js-test-results"></div>
        </div>
        
        <div class="card">
            <h2>📝 Debug Log (Letzte 20 Zeilen)</h2>
            <pre style="background: #f0f0f0; padding: 10px; overflow-x: auto;">
<?php
            $debug_log = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($debug_log)) {
                $lines = file($debug_log);
                $last_lines = array_slice($lines, -20);
                foreach ($last_lines as $line) {
                    // Filter for CBD related messages
                    if (stripos($line, 'cbd') !== false || stripos($line, 'container-block') !== false) {
                        echo '<strong>' . htmlspecialchars($line) . '</strong>';
                    } else {
                        echo htmlspecialchars($line);
                    }
                }
            } else {
                echo "Keine debug.log gefunden. Aktiviere WP_DEBUG_LOG in wp-config.php";
            }
?>
            </pre>
        </div>
        
    </div>
    
    <script>
    function testBlockRegistration() {
        if (typeof wp !== 'undefined' && wp.blocks) {
            const blockType = wp.blocks.getBlockType('container-block-designer/container');
            const results = document.getElementById('js-test-results');
            
            if (blockType) {
                results.innerHTML = '<div class="notice notice-success"><p>✅ Block ist registriert!</p></div>';
                console.log('Block Details:', blockType);
            } else {
                results.innerHTML = '<div class="notice notice-error"><p>❌ Block ist NICHT registriert!</p></div>';
                console.log('Verfügbare Blocks:', wp.blocks.getBlockTypes().map(b => b.name));
            }
        } else {
            alert('Dieser Test funktioniert nur im Gutenberg Editor!');
        }
    }
    
    function testAjax() {
        jQuery.post(ajaxurl, {
            action: 'cbd_get_blocks'
        }, function(response) {
            const results = document.getElementById('js-test-results');
            if (response.success) {
                results.innerHTML = '<div class="notice notice-success"><p>✅ AJAX funktioniert! ' + response.data.length + ' Blocks gefunden.</p></div>';
                console.log('Blocks:', response.data);
            } else {
                results.innerHTML = '<div class="notice notice-error"><p>❌ AJAX Fehler!</p></div>';
                console.error('AJAX Error:', response);
            }
        }).fail(function(error) {
            document.getElementById('js-test-results').innerHTML = '<div class="notice notice-error"><p>❌ AJAX Request fehlgeschlagen!</p></div>';
            console.error('AJAX Failed:', error);
        });
    }
    
    function testRestApi() {
        fetch('<?php echo home_url('/wp-json/cbd/v1/blocks'); ?>', {
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            const results = document.getElementById('js-test-results');
            results.innerHTML = '<div class="notice notice-success"><p>✅ REST API funktioniert!</p></div>';
            console.log('REST API Response:', data);
        })
        .catch(error => {
            document.getElementById('js-test-results').innerHTML = '<div class="notice notice-error"><p>❌ REST API Fehler!</p></div>';
            console.error('REST API Error:', error);
        });
    }
    
    // Check block registration on page load
    document.addEventListener('DOMContentLoaded', function() {
        const statusCell = document.getElementById('block-registered');
        // This check would need to be done in the editor context
        statusCell.innerHTML = '⚠️ Kann nur im Editor geprüft werden';
    });
    </script>
    
    <?php
}

/**
 * Helper Functions
 */

function cbd_get_plugin_status() {
    global $wpdb;
    
    $status = array();
    
    // Check table
    $table_name = $wpdb->prefix . 'cbd_blocks';
    $status['table_exists'] = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // Count blocks
    if ($status['table_exists']) {
        $status['block_count'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    } else {
        $status['block_count'] = 0;
    }
    
    // Check if scripts are enqueued
    $status['admin_scripts'] = wp_script_is('cbd-admin', 'enqueued') || wp_script_is('cbd-admin', 'registered');
    
    // Check REST API
    $status['rest_api'] = rest_get_server() !== null;
    
    return $status;
}

function cbd_fix_database_structure() {
    // Use the existing database fix function
    if (function_exists('cbd_run_database_fix')) {
        cbd_run_database_fix();
        return true;
    }
    return false;
}

function cbd_create_sample_block() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cbd_blocks';
    
    // Check if sample exists
    $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE slug = 'sample-container'");
    
    if ($exists > 0) {
        return true; // Already exists
    }
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => 'Sample Container',
            'slug' => 'sample-container',
            'description' => 'Ein Beispiel Container Block zum Testen',
            'config' => json_encode(array(
                'styles' => array(
                    'padding' => array('top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20),
                    'margin' => array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0),
                    'background' => array('color' => '#f0f0f0'),
                    'text' => array('color' => '#333333', 'alignment' => 'left'),
                    'border' => array('width' => 1, 'color' => '#dddddd', 'radius' => 4)
                )
            )),
            'features' => json_encode(array(
                'icon' => array('enabled' => true, 'value' => 'dashicons-welcome-widgets-menus'),
                'collapse' => array('enabled' => false, 'defaultState' => 'expanded'),
                'numbering' => array('enabled' => false, 'format' => 'numeric'),
                'copyText' => array('enabled' => true, 'buttonText' => 'Text kopieren'),
                'screenshot' => array('enabled' => true, 'buttonText' => 'Screenshot')
            )),
            'status' => 'active',
            'created' => current_time('mysql'),
            'modified' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    return $result !== false;
}

function cbd_fix_block_category() {
    // This will be applied via filter
    add_filter('block_categories_all', function($categories) {
        $has_design = false;
        foreach ($categories as $category) {
            if ($category['slug'] === 'design') {
                $has_design = true;
                break;
            }
        }
        
        if (!$has_design) {
            $categories[] = array(
                'slug'  => 'design',
                'title' => __('Design Blocks', 'container-block-designer'),
                'icon'  => 'layout'
            );
        }
        
        return $categories;
    }, 10, 1);
    
    return true;
}

function cbd_clear_all_caches() {
    // Clear WordPress cache
    wp_cache_flush();
    
    // Clear rewrite rules
    flush_rewrite_rules();
    
    // Clear transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cbd_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cbd_%'");
    
    // Clear object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

function cbd_reregister_scripts() {
    // Remove existing scripts
    wp_deregister_script('cbd-container-block');
    wp_deregister_script('cbd-admin');
    wp_deregister_script('cbd-admin-features');
    wp_deregister_script('cbd-advanced-features');
    
    // Re-run the plugin's script registration
    if (class_exists('ContainerBlockDesigner')) {
        $plugin = ContainerBlockDesigner::get_instance();
        if (method_exists($plugin, 'enqueue_block_editor_assets')) {
            $plugin->enqueue_block_editor_assets();
        }
        if (method_exists($plugin, 'enqueue_admin_assets')) {
            $plugin->enqueue_admin_assets('container-block-designer');
        }
    }
}

// Initialize only if main plugin is active
if (defined('CBD_VERSION')) {
    // The menu hook is already added at the top
}