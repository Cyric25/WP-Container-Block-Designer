<?php
/**
 * Container Block Designer - REST API
 * Version: 2.2.0
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes
 */
add_action('rest_api_init', function() {
    
    // Get all active blocks
    register_rest_route('cbd/v1', '/blocks', array(
        'methods' => 'GET',
        'callback' => 'cbd_rest_get_blocks',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    // Get single block with full data
    register_rest_route('cbd/v1', '/block/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'cbd_rest_get_block_data',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            )
        )
    ));
    
    // Save block features
    register_rest_route('cbd/v1', '/block/(?P<id>\d+)/features', array(
        'methods' => 'POST',
        'callback' => 'cbd_rest_save_features',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => array(
            'id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
            'features' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_array($param) || is_string($param);
                }
            )
        )
    ));
    
    // Update block config
    register_rest_route('cbd/v1', '/block/(?P<id>\d+)/config', array(
        'methods' => 'POST',
        'callback' => 'cbd_rest_update_config',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => array(
            'id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
            'config' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_array($param) || is_string($param);
                }
            )
        )
    ));
});

/**
 * REST: Get all active blocks
 */
function cbd_rest_get_blocks($request) {
    global $wpdb;
    
    $blocks = $wpdb->get_results(
        "SELECT id, name, slug, description FROM " . CBD_TABLE_BLOCKS . " WHERE status = 'active' ORDER BY name",
        ARRAY_A
    );
    
    return rest_ensure_response($blocks ?: []);
}

/**
 * REST: Get single block data
 */
function cbd_rest_get_block_data($request) {
    global $wpdb;
    
    $block_id = $request['id'];
    
    // Get block from database
    $block = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE id = %d AND status = 'active'",
        $block_id
    ));
    
    if (!$block) {
        return new WP_Error('not_found', 'Block not found', array('status' => 404));
    }
    
    // Parse JSON fields
    $config = json_decode($block->config, true);
    $features = json_decode($block->features, true);
    
    // Ensure all 5 features are present
    $default_features = array(
        'icon' => array('enabled' => false, 'value' => 'dashicons-admin-generic'),
        'collapse' => array('enabled' => false, 'defaultState' => 'expanded'),
        'numbering' => array('enabled' => false, 'format' => 'numeric'),
        'copyText' => array('enabled' => false, 'buttonText' => 'Text kopieren'),
        'screenshot' => array('enabled' => false, 'buttonText' => 'Screenshot')
    );
    
    if (!is_array($features)) {
        $features = $default_features;
    } else {
        // Merge with defaults to ensure all features exist
        foreach ($default_features as $key => $default) {
            if (!isset($features[$key])) {
                $features[$key] = $default;
            } else {
                // Ensure sub-properties exist
                $features[$key] = array_merge($default, $features[$key]);
            }
        }
    }
    
    // Prepare response with all data
    $response = array(
        'id' => $block->id,
        'name' => $block->name,
        'slug' => $block->slug,
        'description' => $block->description,
        'config' => $config,
        'features' => $features,
        'status' => $block->status
    );
    
    return rest_ensure_response($response);
}

/**
 * REST: Save block features
 */
function cbd_rest_save_features($request) {
    global $wpdb;
    
    $block_id = $request['id'];
    $features = $request['features'];
    
    // Parse features if string
    if (is_string($features)) {
        $features = json_decode($features, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON in features', array('status' => 400));
        }
    }
    
    // Sanitize all 5 features
    $sanitized_features = array(
        'icon' => array(
            'enabled' => !empty($features['icon']['enabled']),
            'value' => sanitize_text_field($features['icon']['value'] ?? 'dashicons-admin-generic')
        ),
        'collapse' => array(
            'enabled' => !empty($features['collapse']['enabled']),
            'defaultState' => in_array($features['collapse']['defaultState'] ?? '', array('expanded', 'collapsed')) 
                ? $features['collapse']['defaultState'] 
                : 'expanded'
        ),
        'numbering' => array(
            'enabled' => !empty($features['numbering']['enabled']),
            'format' => in_array($features['numbering']['format'] ?? '', array('numeric', 'alpha', 'roman')) 
                ? $features['numbering']['format'] 
                : 'numeric'
        ),
        'copyText' => array(
            'enabled' => !empty($features['copyText']['enabled']),
            'buttonText' => sanitize_text_field($features['copyText']['buttonText'] ?? 'Text kopieren')
        ),
        'screenshot' => array(
            'enabled' => !empty($features['screenshot']['enabled']),
            'buttonText' => sanitize_text_field($features['screenshot']['buttonText'] ?? 'Screenshot')
        )
    );
    
    // Update in database
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
    
    if ($result === false) {
        return new WP_Error('update_failed', 'Failed to update features', array('status' => 500));
    }
    
    return rest_ensure_response(array(
        'success' => true,
        'features' => $sanitized_features
    ));
}

/**
 * REST: Update block config
 */
function cbd_rest_update_config($request) {
    global $wpdb;
    
    $block_id = $request['id'];
    $config = $request['config'];
    
    // Parse config if string
    if (is_string($config)) {
        $config = json_decode($config, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON in config', array('status' => 400));
        }
    }
    
    // Sanitize config
    $sanitized_config = array(
        'styles' => array(
            'padding' => array(
                'top' => intval($config['styles']['padding']['top'] ?? 20),
                'right' => intval($config['styles']['padding']['right'] ?? 20),
                'bottom' => intval($config['styles']['padding']['bottom'] ?? 20),
                'left' => intval($config['styles']['padding']['left'] ?? 20)
            ),
            'background' => array(
                'color' => sanitize_hex_color($config['styles']['background']['color'] ?? '#ffffff')
            ),
            'text' => array(
                'color' => sanitize_hex_color($config['styles']['text']['color'] ?? '#000000'),
                'alignment' => sanitize_text_field($config['styles']['text']['alignment'] ?? 'left')
            ),
            'border' => array(
                'width' => intval($config['styles']['border']['width'] ?? 0),
                'color' => sanitize_hex_color($config['styles']['border']['color'] ?? '#dddddd'),
                'radius' => intval($config['styles']['border']['radius'] ?? 0)
            )
        )
    );
    
    // Update in database
    $result = $wpdb->update(
        CBD_TABLE_BLOCKS,
        array(
            'config' => json_encode($sanitized_config),
            'modified' => current_time('mysql')
        ),
        array('id' => $block_id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return new WP_Error('update_failed', 'Failed to update config', array('status' => 500));
    }
    
    // Generate new CSS
    cbd_generate_block_css($block_id, $sanitized_config);
    
    return rest_ensure_response(array(
        'success' => true,
        'config' => $sanitized_config
    ));
}