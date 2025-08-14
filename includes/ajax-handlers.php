<?php
/**
 * Container Block Designer - AJAX Handlers
 * Version: 2.2.0 - Complete with all features support
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX: Get all blocks for editor
 */
add_action('wp_ajax_cbd_get_blocks', 'cbd_ajax_get_blocks');
add_action('wp_ajax_nopriv_cbd_get_blocks', 'cbd_ajax_get_blocks');

function cbd_ajax_get_blocks() {
    global $wpdb;
    
    // Get active blocks
    $blocks = $wpdb->get_results(
        "SELECT id, name, slug, description FROM " . CBD_TABLE_BLOCKS . " WHERE status = 'active' ORDER BY name",
        ARRAY_A
    );
    
    wp_send_json_success($blocks ?: []);
}

/**
 * AJAX: Get full block data including features
 */
add_action('wp_ajax_cbd_get_block_data', 'cbd_ajax_get_block_data');
add_action('wp_ajax_nopriv_cbd_get_block_data', 'cbd_ajax_get_block_data');

function cbd_ajax_get_block_data() {
    global $wpdb;
    
    $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
    
    if (!$block_id) {
        wp_send_json_error('Invalid block ID');
        return;
    }
    
    // Get block from database
    $block = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d AND status = 'active'",
        $block_id
    ));
    
    if (!$block) {
        wp_send_json_error('Block not found');
        return;
    }
    
    // Prepare response with all data
    $response = array(
        'id' => $block->id,
        'name' => $block->name,
        'slug' => $block->slug,
        'description' => $block->description,
        'config' => json_decode($block->config, true),
        'features' => json_decode($block->features, true),
        'status' => $block->status
    );
    
    wp_send_json_success($response);
}

/**
 * AJAX: Save new block
 */
add_action('wp_ajax_cbd_save_block', 'cbd_ajax_save_block');

function cbd_ajax_save_block() {
    global $wpdb;
    
    // Nonce verification
    if (!check_ajax_referer('cbd_admin', 'nonce', false)) {
        wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen', 'container-block-designer')]);
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
    
    // Styles configuration
    $styles = isset($_POST['styles']) ? $_POST['styles'] : [];
    $config = [
        'styles' => [
            'padding' => [
                'top' => intval($styles['padding']['top'] ?? 20),
                'right' => intval($styles['padding']['right'] ?? 20),
                'bottom' => intval($styles['padding']['bottom'] ?? 20),
                'left' => intval($styles['padding']['left'] ?? 20)
            ],
            'background' => [
                'color' => sanitize_hex_color($styles['background']['color'] ?? '#ffffff')
            ],
            'text' => [
                'color' => sanitize_hex_color($styles['text']['color'] ?? '#000000'),
                'alignment' => sanitize_text_field($styles['text']['alignment'] ?? 'left')
            ],
            'border' => [
                'width' => intval($styles['border']['width'] ?? 0),
                'color' => sanitize_hex_color($styles['border']['color'] ?? '#dddddd'),
                'radius' => intval($styles['border']['radius'] ?? 0)
            ]
        ]
    ];
    
    // Default features for new blocks
    $features = [
        'icon' => ['enabled' => false, 'value' => 'dashicons-admin-generic'],
        'collapse' => ['enabled' => false, 'defaultState' => 'expanded'],
        'numbering' => ['enabled' => false, 'format' => 'numeric'],
        'copyText' => ['enabled' => false, 'buttonText' => 'Text kopieren'],
        'screenshot' => ['enabled' => false, 'buttonText' => 'Screenshot']
    ];
    
    // Validation
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
        wp_send_json_error(['message' => __('Fehler beim Speichern des Blocks', 'container-block-designer')]);
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
 * AJAX: Update existing block
 */
add_action('wp_ajax_cbd_update_block', 'cbd_ajax_update_block');

function cbd_ajax_update_block() {
    global $wpdb;
    
    // Nonce verification
    if (!check_ajax_referer('cbd_admin', 'nonce', false)) {
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
    
    // Styles configuration
    $styles = isset($_POST['styles']) ? $_POST['styles'] : [];
    $config = [
        'styles' => [
            'padding' => [
                'top' => intval($styles['padding']['top'] ?? 20),
                'right' => intval($styles['padding']['right'] ?? 20),
                'bottom' => intval($styles['padding']['bottom'] ?? 20),
                'left' => intval($styles['padding']['left'] ?? 20)
            ],
            'background' => [
                'color' => sanitize_hex_color($styles['background']['color'] ?? '#ffffff')
            ],
            'text' => [
                'color' => sanitize_hex_color($styles['text']['color'] ?? '#000000'),
                'alignment' => sanitize_text_field($styles['text']['alignment'] ?? 'left')
            ],
            'border' => [
                'width' => intval($styles['border']['width'] ?? 0),
                'color' => sanitize_hex_color($styles['border']['color'] ?? '#dddddd'),
                'radius' => intval($styles['border']['radius'] ?? 0)
            ]
        ]
    ];
    
    if (!$block_id || empty($name) || empty($slug)) {
        wp_send_json_error(['message' => __('Ungültige Daten', 'container-block-designer')]);
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
 * AJAX: Delete block
 */
add_action('wp_ajax_cbd_delete_block', 'cbd_ajax_delete_block');

function cbd_ajax_delete_block() {
    global $wpdb;
    
    // Nonce verification
    if (!check_ajax_referer('cbd_admin', 'nonce', false)) {
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
 * AJAX: Toggle block status
 */
add_action('wp_ajax_cbd_toggle_status', 'cbd_ajax_toggle_status');

function cbd_ajax_toggle_status() {
    global $wpdb;
    
    // Nonce verification
    if (!check_ajax_referer('cbd_admin', 'nonce', false)) {
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
    
    // Get current status
    $current_status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d",
        $block_id
    ));
    
    if (!$current_status) {
        wp_send_json_error(['message' => __('Block nicht gefunden', 'container-block-designer')]);
        return;
    }
    
    // Toggle status
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';
    
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
        wp_send_json_error(['message' => __('Fehler beim Ändern des Status', 'container-block-designer')]);
        return;
    }
    
    wp_send_json_success([
        'message' => __('Status erfolgreich geändert', 'container-block-designer'),
        'new_status' => $new_status
    ]);
}

/**
 * AJAX: Get features for a block
 */
add_action('wp_ajax_cbd_get_features', 'cbd_ajax_get_features');

function cbd_ajax_get_features() {
    global $wpdb;
    
    // Nonce verification (accept both nonces for compatibility)
    $nonce_valid = check_ajax_referer('cbd_features_nonce', 'nonce', false) || 
                   check_ajax_referer('cbd_admin', 'nonce', false);
    
    if (!$nonce_valid) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;
    
    if (!$block_id) {
        // Return default features for new blocks
        wp_send_json_success([
            'icon' => ['enabled' => false, 'value' => 'dashicons-admin-generic'],
            'collapse' => ['enabled' => false, 'defaultState' => 'expanded'],
            'numbering' => ['enabled' => false, 'format' => 'numeric'],
            'copyText' => ['enabled' => false, 'buttonText' => 'Text kopieren'],
            'screenshot' => ['enabled' => false, 'buttonText' => 'Screenshot']
        ]);
        return;
    }
    
    // Get block features from database
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
        
        // Merge with defaults to ensure all features exist
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

/**
 * AJAX: Save features for a block
 */
add_action('wp_ajax_cbd_save_features', 'cbd_ajax_save_features');

function cbd_ajax_save_features() {
    global $wpdb;
    
    // Nonce verification (accept both nonces for compatibility)
    $nonce_valid = check_ajax_referer('cbd_features_nonce', 'nonce', false) || 
                   check_ajax_referer('cbd_admin', 'nonce', false);
    
    if (!$nonce_valid) {
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
    
    // Sanitize all 5 features
    $sanitized_features = [
        'icon' => [
            'enabled' => !empty($features_array['icon']['enabled']),
            'value' => sanitize_text_field($features_array['icon']['value'] ?? 'dashicons-admin-generic')
        ],
        'collapse' => [
            'enabled' => !empty($features_array['collapse']['enabled']),
            'defaultState' => in_array($features_array['collapse']['defaultState'] ?? '', ['expanded', 'collapsed']) 
                ? $features_array['collapse']['defaultState'] 
                : 'expanded'
        ],
        'numbering' => [
            'enabled' => !empty($features_array['numbering']['enabled']),
            'format' => in_array($features_array['numbering']['format'] ?? '', ['numeric', 'alpha', 'roman']) 
                ? $features_array['numbering']['format'] 
                : 'numeric'
        ],
        'copyText' => [
            'enabled' => !empty($features_array['copyText']['enabled']),
            'buttonText' => sanitize_text_field($features_array['copyText']['buttonText'] ?? 'Text kopieren')
        ],
        'screenshot' => [
            'enabled' => !empty($features_array['screenshot']['enabled']),
            'buttonText' => sanitize_text_field($features_array['screenshot']['buttonText'] ?? 'Screenshot')
        ]
    ];
    
    // Update block features in database
    $result = $wpdb->update(
        CBD_TABLE_BLOCKS,
        [
            'features' => json_encode($sanitized_features),
            'modified' => current_time('mysql')
        ],
        ['id' => $block_id],
        ['%s', '%s'],
        ['%d']
    );
    
    if ($result !== false) {
        // Clear any cached CSS
        $upload_dir = wp_upload_dir();
        $css_file = $upload_dir['basedir'] . '/cbd-blocks/block-' . $block_id . '.css';
        if (file_exists($css_file)) {
            @unlink($css_file);
        }
        
        wp_send_json_success([
            'message' => 'Features erfolgreich gespeichert',
            'features' => $sanitized_features
        ]);
    } else {
        wp_send_json_error('Fehler beim Speichern der Features');
    }
}

/**
 * Generate block CSS
 */
function cbd_generate_block_css($block_id, $config) {
    $styles = $config['styles'] ?? [];
    
    $css = "
/* Container Block {$block_id} */
.cbd-container-{$block_id} {
    padding: {$styles['padding']['top']}px {$styles['padding']['right']}px {$styles['padding']['bottom']}px {$styles['padding']['left']}px;
    background-color: {$styles['background']['color']};
    color: {$styles['text']['color']};
    text-align: {$styles['text']['alignment']};
    border: {$styles['border']['width']}px solid {$styles['border']['color']};
    border-radius: {$styles['border']['radius']}px;
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