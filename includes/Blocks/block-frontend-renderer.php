<?php
/**
 * Container Block Frontend Renderer
 * Version: 2.2.0
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Block Renderer Class
 */
class CBD_Frontend_Renderer {
    
    /**
     * Initialize
     */
    public static function init() {
        add_filter('render_block', array(__CLASS__, 'render_container_block'), 10, 2);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
    }
    
    /**
     * Render container block with features
     */
    public static function render_container_block($block_content, $block) {
        // Only process our blocks
        if ($block['blockName'] !== 'container-block-designer/container') {
            return $block_content;
        }
        
        // Get block attributes
        $attributes = $block['attrs'] ?? array();
        $selected_block = $attributes['selectedBlock'] ?? '';
        
        if (empty($selected_block)) {
            return $block_content;
        }
        
        // Get block data from database
        global $wpdb;
        $block_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE slug = %s AND status = 'active'",
            $selected_block
        ));
        
        if (!$block_data) {
            return $block_content;
        }
        
        // Parse features and config
        $features = json_decode($block_data->features, true) ?: array();
        $config = json_decode($block_data->config, true) ?: array();
        
        // Add features data attributes
        $wrapper_attributes = array();
        
        // Feature 1: Icon
        if (!empty($features['icon']['enabled'])) {
            $wrapper_attributes['data-icon'] = 'true';
            $wrapper_attributes['data-icon-value'] = esc_attr($features['icon']['value'] ?? 'dashicons-admin-generic');
        }
        
        // Feature 2: Collapse
        if (!empty($features['collapse']['enabled'])) {
            $wrapper_attributes['data-collapse'] = 'true';
            $wrapper_attributes['data-collapse-default'] = esc_attr($features['collapse']['defaultState'] ?? 'expanded');
        }
        
        // Feature 3: Numbering
        if (!empty($features['numbering']['enabled'])) {
            $wrapper_attributes['data-numbering'] = 'true';
            $wrapper_attributes['data-numbering-format'] = esc_attr($features['numbering']['format'] ?? 'numeric');
        }
        
        // Feature 4: Copy Text
        if (!empty($features['copyText']['enabled'])) {
            $wrapper_attributes['data-copy'] = 'true';
            $wrapper_attributes['data-copy-text'] = esc_attr($features['copyText']['buttonText'] ?? 'Text kopieren');
        }
        
        // Feature 5: Screenshot
        if (!empty($features['screenshot']['enabled'])) {
            $wrapper_attributes['data-screenshot'] = 'true';
            $wrapper_attributes['data-screenshot-text'] = esc_attr($features['screenshot']['buttonText'] ?? 'Screenshot');
        }
        
        // Build wrapper HTML
        $wrapper_html = self::build_wrapper_html($block_content, $wrapper_attributes, $features, $config);
        
        return $wrapper_html;
    }
    
    /**
     * Build wrapper HTML with features
     */
    private static function build_wrapper_html($content, $attributes, $features, $config) {
        // Parse existing content
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);
        
        // Find the main container div
        $container = $xpath->query("//div[contains(@class, 'cbd-container')]")->item(0);
        
        if ($container) {
            // Add data attributes
            foreach ($attributes as $key => $value) {
                $container->setAttribute($key, $value);
            }
            
            // Add icon if enabled
            if (!empty($features['icon']['enabled'])) {
                $icon_html = self::render_icon($features['icon']);
                if ($icon_html) {
                    $icon_fragment = $dom->createDocumentFragment();
                    $icon_fragment->appendXML($icon_html);
                    $container->insertBefore($icon_fragment, $container->firstChild);
                }
            }
            
            // Wrap content for collapse feature
            if (!empty($features['collapse']['enabled'])) {
                // Content will be wrapped by JavaScript
                $container->setAttribute('data-collapse-ready', 'false');
            }
            
            // Add features container
            $has_buttons = !empty($features['copyText']['enabled']) || !empty($features['screenshot']['enabled']);
            if ($has_buttons) {
                $buttons_html = self::render_feature_buttons($features);
                if ($buttons_html) {
                    $buttons_fragment = $dom->createDocumentFragment();
                    $buttons_fragment->appendXML($buttons_html);
                    $container->appendChild($buttons_fragment);
                }
            }
        }
        
        // Save and return HTML
        $html = $dom->saveHTML();
        
        // Clean up encoding issues
        $html = str_replace('<?xml encoding="utf-8" ?>', '', $html);
        
        return $html;
    }
    
    /**
     * Render icon HTML
     */
    private static function render_icon($icon_config) {
        if (empty($icon_config['enabled'])) {
            return '';
        }
        
        $icon_class = esc_attr($icon_config['value'] ?? 'dashicons-admin-generic');
        
        return sprintf(
            '<div class="cbd-container-icon"><span class="dashicons %s"></span></div>',
            $icon_class
        );
    }
    
    /**
     * Render feature buttons
     */
    private static function render_feature_buttons($features) {
        $buttons = array();
        
        // Copy button
        if (!empty($features['copyText']['enabled'])) {
            $button_text = esc_html($features['copyText']['buttonText'] ?? __('Text kopieren', 'container-block-designer'));
            $buttons[] = sprintf(
                '<button class="cbd-copy-button" type="button">%s</button>',
                $button_text
            );
        }
        
        // Screenshot button
        if (!empty($features['screenshot']['enabled'])) {
            $button_text = esc_html($features['screenshot']['buttonText'] ?? __('Screenshot', 'container-block-designer'));
            $buttons[] = sprintf(
                '<button class="cbd-screenshot-button" type="button">%s</button>',
                $button_text
            );
        }
        
        if (empty($buttons)) {
            return '';
        }
        
        return sprintf(
            '<div class="cbd-features-container">%s</div>',
            implode('', $buttons)
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        if (!has_block('container-block-designer/container')) {
            return;
        }
        
        // Frontend styles
        wp_enqueue_style(
            'cbd-frontend',
            CBD_PLUGIN_URL . 'assets/css/cbd-frontend.css',
            array(),
            CBD_VERSION
        );
        
        // Advanced features styles
        wp_enqueue_style(
            'cbd-advanced-features',
            CBD_PLUGIN_URL . 'assets/css/advanced-features.css',
            array('dashicons'),
            CBD_VERSION
        );
        
        // Advanced features script
        wp_enqueue_script(
            'cbd-advanced-features',
            CBD_PLUGIN_URL . 'assets/js/advanced-features.js',
            array('jquery'),
            CBD_VERSION,
            true
        );
        
        // Html2Canvas for screenshot feature (CDN)
        wp_enqueue_script(
            'html2canvas',
            'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
            array(),
            '1.4.1',
            true
        );
        
        // Localize script
        wp_localize_script('cbd-advanced-features', 'cbdFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cbd_frontend'),
            'strings' => array(
                'copied' => __('Text kopiert!', 'container-block-designer'),
                'copyError' => __('Fehler beim Kopieren', 'container-block-designer'),
                'screenshotSaved' => __('Screenshot gespeichert!', 'container-block-designer'),
                'screenshotError' => __('Fehler beim Erstellen des Screenshots', 'container-block-designer'),
                'expand' => __('Ausklappen', 'container-block-designer'),
                'collapse' => __('Einklappen', 'container-block-designer')
            )
        ));
    }
}

// Initialize frontend renderer
add_action('init', array('CBD_Frontend_Renderer', 'init'));

/**
 * Alternative render function for classic themes
 */
function cbd_render_container($slug, $content = '', $custom_classes = '') {
    global $wpdb;
    
    // Get block data
    $block_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE slug = %s AND status = 'active'",
        $slug
    ));
    
    if (!$block_data) {
        return $content;
    }
    
    // Parse features and config
    $features = json_decode($block_data->features, true) ?: array();
    $config = json_decode($block_data->config, true) ?: array();
    $styles = $config['styles'] ?? array();
    
    // Build container classes
    $container_classes = array(
        'cbd-container',
        'cbd-container-' . esc_attr($slug)
    );
    
    if ($custom_classes) {
        $container_classes[] = esc_attr($custom_classes);
    }
    
    // Build data attributes
    $data_attributes = array();
    
    if (!empty($features['icon']['enabled'])) {
        $data_attributes[] = 'data-icon="true"';
        $data_attributes[] = 'data-icon-value="' . esc_attr($features['icon']['value'] ?? 'dashicons-admin-generic') . '"';
    }
    
    if (!empty($features['collapse']['enabled'])) {
        $data_attributes[] = 'data-collapse="true"';
        $data_attributes[] = 'data-collapse-default="' . esc_attr($features['collapse']['defaultState'] ?? 'expanded') . '"';
    }
    
    if (!empty($features['numbering']['enabled'])) {
        $data_attributes[] = 'data-numbering="true"';
        $data_attributes[] = 'data-numbering-format="' . esc_attr($features['numbering']['format'] ?? 'numeric') . '"';
    }
    
    if (!empty($features['copyText']['enabled'])) {
        $data_attributes[] = 'data-copy="true"';
        $data_attributes[] = 'data-copy-text="' . esc_attr($features['copyText']['buttonText'] ?? 'Text kopieren') . '"';
    }
    
    if (!empty($features['screenshot']['enabled'])) {
        $data_attributes[] = 'data-screenshot="true"';
        $data_attributes[] = 'data-screenshot-text="' . esc_attr($features['screenshot']['buttonText'] ?? 'Screenshot') . '"';
    }
    
    // Build inline styles
    $inline_styles = array();
    
    if (isset($styles['padding'])) {
        $inline_styles[] = sprintf(
            'padding: %dpx %dpx %dpx %dpx',
            $styles['padding']['top'] ?? 20,
            $styles['padding']['right'] ?? 20,
            $styles['padding']['bottom'] ?? 20,
            $styles['padding']['left'] ?? 20
        );
    }
    
    if (isset($styles['background']['color'])) {
        $inline_styles[] = 'background-color: ' . $styles['background']['color'];
    }
    
    if (isset($styles['text']['color'])) {
        $inline_styles[] = 'color: ' . $styles['text']['color'];
    }
    
    if (isset($styles['text']['alignment'])) {
        $inline_styles[] = 'text-align: ' . $styles['text']['alignment'];
    }
    
    if (isset($styles['border']) && $styles['border']['width'] > 0) {
        $inline_styles[] = sprintf(
            'border: %dpx solid %s',
            $styles['border']['width'],
            $styles['border']['color'] ?? '#dddddd'
        );
    }
    
    if (isset($styles['border']['radius']) && $styles['border']['radius'] > 0) {
        $inline_styles[] = 'border-radius: ' . $styles['border']['radius'] . 'px';
    }
    
    // Build HTML
    $html = sprintf(
        '<div class="%s" %s style="%s">',
        implode(' ', $container_classes),
        implode(' ', $data_attributes),
        implode('; ', $inline_styles)
    );
    
    // Add icon
    if (!empty($features['icon']['enabled'])) {
        $html .= sprintf(
            '<div class="cbd-container-icon"><span class="dashicons %s"></span></div>',
            esc_attr($features['icon']['value'] ?? 'dashicons-admin-generic')
        );
    }
    
    // Add content
    $html .= $content;
    
    // Add feature buttons
    if (!empty($features['copyText']['enabled']) || !empty($features['screenshot']['enabled'])) {
        $html .= '<div class="cbd-features-container">';
        
        if (!empty($features['copyText']['enabled'])) {
            $html .= sprintf(
                '<button class="cbd-copy-button" type="button">%s</button>',
                esc_html($features['copyText']['buttonText'] ?? 'Text kopieren')
            );
        }
        
        if (!empty($features['screenshot']['enabled'])) {
            $html .= sprintf(
                '<button class="cbd-screenshot-button" type="button">%s</button>',
                esc_html($features['screenshot']['buttonText'] ?? 'Screenshot')
            );
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}