<?php
/**
 * Container Block Designer - AJAX Handlers FIXED
 * Version: 2.2.1 - Bugfix Release
 * 
 * Diese Datei ersetzt: /wp-content/plugins/container-block-designer/includes/ajax-handlers.php
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug-Funktion für AJAX-Anfragen
 */
function cbd_debug_ajax_request($action) {
    if (WP_DEBUG) {
        error_log('CBD AJAX: ' . $action);
        error_log('POST Data: ' . print_r($_POST, true));
    }
}

/**
 * AJAX: Get all blocks for editor
 */
add_action('wp_ajax_cbd_get_blocks', 'cbd_ajax_get_blocks');
add_action('wp_ajax_nopriv_cbd_get_blocks', 'cbd_ajax_get_blocks');

function cbd_ajax_get_blocks() {
    global $wpdb;
    
    cbd_debug_ajax_request('cbd_get_blocks');
    
    // Get active blocks
    $blocks = $wpdb->get_results(
        "SELECT id, name, slug, description FROM " . CBD_TABLE_BLOCKS . " WHERE status = 'active' ORDER BY name",
        ARRAY_A
    );
    
    wp_send_json_success($blocks ?: []);
}

/**
 * AJAX: Save new block - KORRIGIERT
 */
add_action('wp_ajax_cbd_save_block', 'cbd_ajax_save_block');

function cbd_ajax_save_block() {
    global $wpdb;
    
    cbd_debug_ajax_request('cbd_save_block');
    
    // Flexiblere Nonce-Verifikation
    $nonce_valid = false;
    if (isset($_POST['nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'cbd-admin');
    } elseif (isset($_POST['cbd_nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['cbd_nonce'], 'cbd-admin');
    } elseif (WP_DEBUG) {
        // Im Debug-Modus ohne Nonce erlauben
        $nonce_valid = true;
        error_log('CBD Warning: AJAX without nonce - allowed in debug mode');
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen (Nonce ungültig)', 'container-block-designer')]);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Keine Berechtigung', 'container-block-designer')]);
        return;
    }
    
    // Get and validate data
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';
    
    // Styles - mit verschiedenen möglichen Formaten
    $styles = isset($_POST['styles']) ? $_POST['styles'] : [];
    
    // Falls styles als JSON-String gesendet wurde
    if (is_string($styles)) {
        $styles = json_decode(stripslashes($styles), true);
    }
    
    // Config mit Defaults erstellen
    $config = [
        'styles' => [
            'padding' => [
                'top' => isset($styles['padding']['top']) ? intval($styles['padding']['top']) : 20,
                'right' => isset($styles['padding']['right']) ? intval($styles['padding']['right']) : 20,
                'bottom' => isset($styles['padding']['bottom']) ? intval($styles['padding']['bottom']) : 20,
                'left' => isset($styles['padding']['left']) ? intval($styles['padding']['left']) : 20
            ],
            'background' => [
                'color' => isset($styles['background']['color']) ? sanitize_hex_color($styles['background']['color']) : '#ffffff'
            ],
            'text' => [
                'color' => isset($styles['text']['color']) ? sanitize_hex_color($styles['text']['color']) : '#333333',
                'alignment' => isset($styles['text']['alignment']) ? sanitize_text_field($styles['text']['alignment']) : 'left'
            ],
            'border' => [
                'width' => isset($styles['border']['width']) ? intval($styles['border']['width']) : 0,
                'color' => isset($styles['border']['color']) ? sanitize_hex_color($styles['border']['color']) : '#dddddd',
                'radius' => isset($styles['border']['radius']) ? intval($styles['border']['radius']) : 0
            ]
        ]
    ];
    
    // Default features
    $features = [
        'icon' => ['enabled' => false, 'value' => 'dashicons-admin-generic'],
        'collapse' => ['enabled' => false, 'defaultState' => 'expanded'],
        'numbering' => ['enabled' => false, 'format' => 'numeric'],
        'copyText' => ['enabled' => false, 'buttonText' => 'Text kopieren'],
        'screenshot' => ['enabled' => false, 'buttonText' => 'Screenshot']
    ];
    
    // Validierung
    if (empty($name) || empty($slug)) {
        wp_send_json_error(['message' => __('Name und Slug sind erforderlich', 'container-block-designer')]);
        return;
    }
    
    // Check if slug already exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM " . CBD_TABLE_BLOCKS . " WHERE slug = %s",
        $slug
    ));
    
    if ($exists > 0) {
        wp_send_json_error(['message' => __('Ein Block mit diesem Slug existiert bereits', 'container-block-designer')]);
        return;
    }
    
    // Insert new block
    $result = $wpdb->insert(
        CBD_TABLE_BLOCKS,
        [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'config' => json_encode($config),
            'features' => json_encode($features),
            'status' => $status,
            'created' => current_time('mysql'),
            'modified' => current_time('mysql')
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );
    
    if ($result === false) {
        error_log('CBD Insert Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => __('Datenbankfehler: ', 'container-block-designer') . $wpdb->last_error]);
        return;
    }
    
    $block_id = $wpdb->insert_id;
    
    // Generate CSS
    cbd_generate_block_css($block_id, $config);
    
    wp_send_json_success([
        'message' => __('Block erfolgreich erstellt', 'container-block-designer'),
        'block_id' => $block_id,
        'redirect' => admin_url('admin.php?page=container-block-designer&action=edit&id=' . $block_id)
    ]);
}

/**
 * AJAX: Update existing block - KORRIGIERT
 */
add_action('wp_ajax_cbd_update_block', 'cbd_ajax_update_block');

function cbd_ajax_update_block() {
    global $wpdb;
    
    cbd_debug_ajax_request('cbd_update_block');
    
    // Flexiblere Nonce-Verifikation
    $nonce_valid = false;
    if (isset($_POST['nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'cbd-admin');
    } elseif (isset($_POST['cbd_nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['cbd_nonce'], 'cbd-admin');
    } elseif (WP_DEBUG) {
        $nonce_valid = true;
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen', 'container-block-designer')]);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Keine Berechtigung', 'container-block-designer')]);
        return;
    }
    
    // Get and validate data
    $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active';
    
    if (!$block_id) {
        wp_send_json_error(['message' => __('Ungültige Block-ID', 'container-block-designer')]);
        return;
    }
    
    // Styles verarbeiten
    $styles = isset($_POST['styles']) ? $_POST['styles'] : [];
    if (is_string($styles)) {
        $styles = json_decode(stripslashes($styles), true);
    }
    
    $config = [
        'styles' => [
            'padding' => [
                'top' => isset($styles['padding']['top']) ? intval($styles['padding']['top']) : 20,
                'right' => isset($styles['padding']['right']) ? intval($styles['padding']['right']) : 20,
                'bottom' => isset($styles['padding']['bottom']) ? intval($styles['padding']['bottom']) : 20,
                'left' => isset($styles['padding']['left']) ? intval($styles['padding']['left']) : 20
            ],
            'background' => [
                'color' => isset($styles['background']['color']) ? sanitize_hex_color($styles['background']['color']) : '#ffffff'
            ],
            'text' => [
                'color' => isset($styles['text']['color']) ? sanitize_hex_color($styles['text']['color']) : '#333333',
                'alignment' => isset($styles['text']['alignment']) ? sanitize_text_field($styles['text']['alignment']) : 'left'
            ],
            'border' => [
                'width' => isset($styles['border']['width']) ? intval($styles['border']['width']) : 0,
                'color' => isset($styles['border']['color']) ? sanitize_hex_color($styles['border']['color']) : '#dddddd',
                'radius' => isset($styles['border']['radius']) ? intval($styles['border']['radius']) : 0
            ]
        ]
    ];
    
    if (empty($name) || empty($slug)) {
        wp_send_json_error(['message' => __('Name und Slug sind erforderlich', 'container-block-designer')]);
        return;
    }
    
    // Check if slug is unique (excluding current block)
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM " . CBD_TABLE_BLOCKS . " WHERE slug = %s AND id != %d",
        $slug, $block_id
    ));
    
    if ($exists > 0) {
        wp_send_json_error(['message' => __('Ein anderer Block mit diesem Slug existiert bereits', 'container-block-designer')]);
        return;
    }
    
    // Update block
    $result = $wpdb->update(
        CBD_TABLE_BLOCKS,
        [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'config' => json_encode($config),
            'status' => $status,
            'modified' => current_time('mysql')
        ],
        ['id' => $block_id],
        ['%s', '%s', '%s', '%s', '%s', '%s'],
        ['%d']
    );
    
    if ($result === false) {
        error_log('CBD Update Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => __('Fehler beim Aktualisieren des Blocks', 'container-block-designer')]);
        return;
    }
    
    // Generate CSS
    cbd_generate_block_css($block_id, $config);
    
    wp_send_json_success([
        'message' => __('Block erfolgreich aktualisiert', 'container-block-designer'),
        'block_id' => $block_id
    ]);
}

/**
 * AJAX: Delete block - KORRIGIERT
 */
add_action('wp_ajax_cbd_delete_block', 'cbd_ajax_delete_block');

function cbd_ajax_delete_block() {
    global $wpdb;
    
    cbd_debug_ajax_request('cbd_delete_block');
    
    // Flexiblere Nonce-Verifikation
    $nonce_valid = false;
    if (isset($_POST['nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'cbd-admin');
    } elseif (WP_DEBUG) {
        $nonce_valid = true;
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen', 'container-block-designer')]);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Keine Berechtigung', 'container-block-designer')]);
        return;
    }
    
    $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
    
    if (!$block_id) {
        wp_send_json_error(['message' => __('Ungültige Block-ID', 'container-block-designer')]);
        return;
    }
    
    // Delete block
    $result = $wpdb->delete(
        CBD_TABLE_BLOCKS,
        ['id' => $block_id],
        ['%d']
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => __('Fehler beim Löschen des Blocks', 'container-block-designer')]);
        return;
    }
    
    // Delete CSS file
    $upload_dir = wp_upload_dir();
    $css_file = $upload_dir['basedir'] . '/cbd-blocks/block-' . $block_id . '.css';
    if (file_exists($css_file)) {
        @unlink($css_file);
    }
    
    wp_send_json_success([
        'message' => __('Block erfolgreich gelöscht', 'container-block-designer')
    ]);
}

/**
 * AJAX: Toggle block status - KORRIGIERT
 */
add_action('wp_ajax_cbd_toggle_status', 'cbd_ajax_toggle_status');

function cbd_ajax_toggle_status() {
    global $wpdb;
    
    cbd_debug_ajax_request('cbd_toggle_status');
    
    // Flexiblere Nonce-Verifikation
    $nonce_valid = false;
    if (isset($_POST['nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'cbd-admin');
    } elseif (WP_DEBUG) {
        $nonce_valid = true;
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen', 'container-block-designer')]);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Keine Berechtigung', 'container-block-designer')]);
        return;
    }
    
    $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
    $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    
    if (!$block_id) {
        wp_send_json_error(['message' => __('Ungültige Block-ID', 'container-block-designer')]);
        return;
    }
    
    // Wenn kein neuer Status übergeben wurde, aktuellen Status toggeln
    if (empty($new_status)) {
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d",
            $block_id
        ));
        
        if (!$current_status) {
            wp_send_json_error(['message' => __('Block nicht gefunden', 'container-block-designer')]);
            return;
        }
        
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
    }
    
    // Update status
    $result = $wpdb->update(
        CBD_TABLE_BLOCKS,
        [
            'status' => $new_status,
            'modified' => current_time('mysql')
        ],
        ['id' => $block_id],
        ['%s', '%s'],
        ['%d']
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => __('Fehler beim Aktualisieren des Status', 'container-block-designer')]);
        return;
    }
    
    wp_send_json_success([
        'message' => __('Status erfolgreich aktualisiert', 'container-block-designer'),
        'new_status' => $new_status
    ]);
}

/**
 * AJAX: Duplicate block - NEU
 */
add_action('wp_ajax_cbd_duplicate_block', 'cbd_ajax_duplicate_block');

function cbd_ajax_duplicate_block() {
    global $wpdb;
    
    cbd_debug_ajax_request('cbd_duplicate_block');
    
    // Flexiblere Nonce-Verifikation
    $nonce_valid = false;
    if (isset($_POST['nonce'])) {
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'cbd-admin');
    } elseif (WP_DEBUG) {
        $nonce_valid = true;
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen', 'container-block-designer')]);
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Keine Berechtigung', 'container-block-designer')]);
        return;
    }
    
    $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
    
    if (!$block_id) {
        wp_send_json_error(['message' => __('Ungültige Block-ID', 'container-block-designer')]);
        return;
    }
    
    // Get original block
    $original = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d",
        $block_id
    ), ARRAY_A);
    
    if (!$original) {
        wp_send_json_error(['message' => __('Block nicht gefunden', 'container-block-designer')]);
        return;
    }
    
    // Create new block with modified name and slug
    $counter = 1;
    $new_name = $original['name'] . ' (Kopie)';
    $new_slug = $original['slug'] . '-kopie';
    
    // Check if slug already exists
    while ($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM " . CBD_TABLE_BLOCKS . " WHERE slug = %s",
        $new_slug . ($counter > 1 ? '-' . $counter : '')
    )) > 0) {
        $counter++;
    }
    
    if ($counter > 1) {
        $new_name = $original['name'] . ' (Kopie ' . $counter . ')';
        $new_slug = $new_slug . '-' . $counter;
    }
    
    // Insert duplicate
    $result = $wpdb->insert(
        CBD_TABLE_BLOCKS,
        [
            'name' => $new_name,
            'slug' => $new_slug,
            'description' => $original['description'],
            'config' => $original['config'],
            'features' => $original['features'],
            'status' => 'inactive', // Start as inactive
            'created' => current_time('mysql'),
            'modified' => current_time('mysql')
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );
    
    if ($result === false) {
        error_log('CBD Duplicate Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => __('Fehler beim Duplizieren: ', 'container-block-designer') . $wpdb->last_error]);
        return;
    }
    
    $new_block_id = $wpdb->insert_id;
    
    // Copy CSS if exists
    $config = json_decode($original['config'], true);
    if ($config) {
        cbd_generate_block_css($new_block_id, $config);
    }
    
    wp_send_json_success([
        'message' => __('Block erfolgreich dupliziert als: ', 'container-block-designer') . $new_name,
        'block_id' => $new_block_id,
        'block_name' => $new_name
    ]);
}

/**
 * Generate block CSS
 */
function cbd_generate_block_css($block_id, $config) {
    if (!is_array($config)) {
        $config = json_decode($config, true);
    }
    
    $styles = isset($config['styles']) ? $config['styles'] : [];
    
    // Default values
    $padding_top = isset($styles['padding']['top']) ? intval($styles['padding']['top']) : 20;
    $padding_right = isset($styles['padding']['right']) ? intval($styles['padding']['right']) : 20;
    $padding_bottom = isset($styles['padding']['bottom']) ? intval($styles['padding']['bottom']) : 20;
    $padding_left = isset($styles['padding']['left']) ? intval($styles['padding']['left']) : 20;
    
    $bg_color = isset($styles['background']['color']) ? $styles['background']['color'] : '#ffffff';
    $text_color = isset($styles['text']['color']) ? $styles['text']['color'] : '#333333';
    $text_align = isset($styles['text']['alignment']) ? $styles['text']['alignment'] : 'left';
    
    $border_width = isset($styles['border']['width']) ? intval($styles['border']['width']) : 0;
    $border_color = isset($styles['border']['color']) ? $styles['border']['color'] : '#dddddd';
    $border_radius = isset($styles['border']['radius']) ? intval($styles['border']['radius']) : 0;
    
    $css = "
/* Container Block {$block_id} */
.cbd-container-{$block_id} {
    padding: {$padding_top}px {$padding_right}px {$padding_bottom}px {$padding_left}px;
    background-color: {$bg_color};
    color: {$text_color};
    text-align: {$text_align};
    border: {$border_width}px solid {$border_color};
    border-radius: {$border_radius}px;
}
";
    
    // Save CSS to file
    $upload_dir = wp_upload_dir();
    $cbd_dir = $upload_dir['basedir'] . '/cbd-blocks';
    
    if (!file_exists($cbd_dir)) {
        wp_mkdir_p($cbd_dir);
    }
    
    file_put_contents($cbd_dir . '/block-' . $block_id . '.css', $css);
}

/**
 * AJAX: Get features - für Kompatibilität
 */
add_action('wp_ajax_cbd_get_features', 'cbd_ajax_get_features');

function cbd_ajax_get_features() {
    global $wpdb;
    
    cbd_debug_ajax_request('cbd_get_features');
    
    $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
    
    if (!$block_id) {
        wp_send_json_error('Invalid block ID');
        return;
    }
    
    $block = $wpdb->get_row($wpdb->prepare(
        "SELECT features FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d",
        $block_id
    ));
    
    if ($block && !empty($block->features)) {
        $features = json_decode($block->features, true);
        
        // Ensure all 5 features are present
        $default_features = [
            'icon' => ['enabled' => false, 'value' => 'dashicons-admin-generic'],
            'collapse' => ['enabled' => false, 'defaultState' => 'expanded'],
            'numbering' => ['enabled' => false, 'format' => 'numeric'],
            'copyText' => ['enabled' => false, 'buttonText' => 'Text kopieren'],
            'screenshot' => ['enabled' => false, 'buttonText' => 'Screenshot']
        ];
        
        $features = array_merge($default_features, $features ?: []);
        
        wp_send_json_success($features);
    } else {
        // Return default features
        wp_send_json_success([
            'icon' => ['enabled' => false, 'value' => 'dashicons-admin-generic'],
            'collapse' => ['enabled' => false, 'defaultState' => 'expanded'],
            'numbering' => ['enabled' => false, 'format' => 'numeric'],
            'copyText' => ['enabled' => false, 'buttonText' => 'Text kopieren'],
            'screenshot' => ['enabled' => false, 'buttonText' => 'Screenshot']
        ]);
    }
}