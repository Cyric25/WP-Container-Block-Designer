<?php
/**
 * Block Frontend Renderer - Final Version mit Nummerierung
 * 
 * @package ContainerBlockDesigner
 * @version 2.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CBD_Frontend_Renderer {
    
    /**
     * Initialize the renderer
     */
    public static function init() {
        add_filter('render_block', array(__CLASS__, 'render_container_block'), 10, 2);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
        
        // Add CSS for numbering reset
        add_action('wp_head', array(__CLASS__, 'add_numbering_css'), 999);
    }
    
    /**
     * Add CSS for numbering counter reset
     */
    public static function add_numbering_css() {
        if (!has_block('container-block-designer/container')) {
            return;
        }
        
        echo '<style>
        body, .entry-content, .wp-block-group, .cbd-container-group {
            counter-reset: cbd-numbering;
        }
        .cbd-container[data-numbering="true"] {
            counter-increment: cbd-numbering;
        }
        </style>';
    }
    
    /**
     * Render container block
     */
    public static function render_container_block($block_content, $block) {
        // Only process our container blocks
        if ($block['blockName'] !== 'container-block-designer/container') {
            return $block_content;
        }
        
        // Extract block attributes
        $attributes = $block['attrs'] ?? array();
        
        // Get effective features (considering global settings)
        $features = self::get_effective_features($attributes);
        
        // Get block configuration if selectedBlock is set
        $selectedBlock = $attributes['selectedBlock'] ?? '';
        $config = array();
        
        if ($selectedBlock) {
            global $wpdb;
            $block_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . CBD_TABLE_BLOCKS . " WHERE slug = %s AND status = 'active'",
                $selectedBlock
            ));
            
            if ($block_data) {
                $config = json_decode($block_data->config, true) ?: array();
            }
        }
        
        // Build data attributes for JavaScript
        $data_attributes = self::build_data_attributes($features);
        
        // Process the HTML content
        $enhanced_content = self::enhance_block_html($block_content, $data_attributes, $features, $config);
        
        return $enhanced_content;
    }
    
    /**
     * Get effective features (global settings + block overrides)
     */
    private static function get_effective_features($attributes) {
        // Load global settings if available
        if (class_exists('CBD_Global_Settings')) {
            return CBD_Global_Settings::get_effective_settings($attributes);
        }
        
        // Fallback: Extract features from block attributes directly
        return array(
            'icon' => array(
                'enabled' => !empty($attributes['enableIcon']),
                'value' => $attributes['iconValue'] ?? 'dashicons-admin-generic'
            ),
            'collapse' => array(
                'enabled' => !empty($attributes['enableCollapse']),
                'defaultState' => $attributes['collapseDefault'] ?? 'expanded'
            ),
            'numbering' => array(
                'enabled' => !empty($attributes['enableNumbering']),
                'format' => $attributes['numberingFormat'] ?? 'numeric'
            ),
            'copyText' => array(
                'enabled' => !empty($attributes['enableCopyText']),
                'buttonText' => $attributes['copyButtonText'] ?? __('Text kopieren', 'container-block-designer')
            ),
            'screenshot' => array(
                'enabled' => !empty($attributes['enableScreenshot']),
                'buttonText' => $attributes['screenshotButtonText'] ?? __('Screenshot', 'container-block-designer')
            )
        );
    }
    
    /**
     * Build data attributes for features
     */
    private static function build_data_attributes($features) {
        $attributes = array();
        
        // Icon feature
        if ($features['icon']['enabled']) {
            $attributes['data-icon'] = 'true';
            $attributes['data-icon-value'] = esc_attr($features['icon']['value']);
        }
        
        // Collapse feature  
        if ($features['collapse']['enabled']) {
            $attributes['data-collapse'] = 'true';
            $attributes['data-collapse-default'] = esc_attr($features['collapse']['defaultState']);
        }
        
        // Numbering feature - HINZUGEFÜGT
        if ($features['numbering']['enabled']) {
            $attributes['data-numbering'] = 'true';
            $attributes['data-numbering-format'] = esc_attr($features['numbering']['format']);
        }
        
        // Copy feature
        if ($features['copyText']['enabled']) {
            $attributes['data-copy'] = 'true';
            $attributes['data-copy-text'] = esc_attr($features['copyText']['buttonText']);
        }
        
        // Screenshot feature
        if ($features['screenshot']['enabled']) {
            $attributes['data-screenshot'] = 'true'; 
            $attributes['data-screenshot-text'] = esc_attr($features['screenshot']['buttonText']);
        }
        
        return $attributes;
    }
    
    /**
     * Enhance block HTML with features
     */
    private static function enhance_block_html($content, $data_attributes, $features, $config) {
        if (empty($content)) {
            return $content;
        }
        
        // Load HTML into DOMDocument
        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';
        
        // Suppress errors and load content  
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Find the main container
        $containers = $xpath->query("//div[contains(@class, 'cbd-container')]");
        
        if ($containers->length === 0) {
            return $content; // Return original if no container found
        }
        
        $container = $containers->item(0);
        
        // Add data attributes to container
        foreach ($data_attributes as $attr => $value) {
            $container->setAttribute($attr, $value);
        }
        
        // Handle different feature combinations
        if ($features['collapse']['enabled']) {
            // Collapse feature is enabled - setup collapse structure
            self::setup_collapse_structure($dom, $container, $features);
        } else {
            // No collapse - add features directly to container
            
            // Add icon if enabled
            if ($features['icon']['enabled']) {
                self::add_icon_to_container($dom, $container, $features['icon']);
            }
            
            // Add action buttons if enabled
            if ($features['copyText']['enabled'] || $features['screenshot']['enabled']) {
                self::add_action_buttons($dom, $container, $features, 'default');
            }
        }
        
        // Apply custom styles if available
        if (!empty($config['styles'])) {
            self::apply_inline_styles($container, $config['styles']);
        }
        
        // Return the modified HTML
        $html = $dom->saveHTML($container);
        
        // Clean up encoding
        $html = str_replace('<?xml encoding="UTF-8"?>', '', $html);
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $html;
    }
    
    /**
     * Setup collapse structure
     */
    private static function setup_collapse_structure($dom, $container, $features) {
        // Create collapse header
        $header = $dom->createElement('div');
        $header->setAttribute('class', 'cbd-collapse-header');
        
        // Toggle icon
        $toggle_icon = $dom->createElement('span');
        $toggle_icon->setAttribute('class', 'dashicons dashicons-arrow-down-alt2 cbd-collapse-toggle');
        $header->appendChild($toggle_icon);
        
        // Icon in header if enabled
        if ($features['icon']['enabled']) {
            $icon_element = self::create_icon_element($dom, $features['icon']);
            $header->appendChild($icon_element);
        }
        
        // Title
        $title = $dom->createElement('span');
        $title->setAttribute('class', 'cbd-collapse-title');
        $title->textContent = __('Container Inhalt', 'container-block-designer');
        $header->appendChild($title);
        
        // Action buttons in header (for collapsed state)
        if ($features['copyText']['enabled'] || $features['screenshot']['enabled']) {
            $header_buttons = self::create_action_buttons($dom, $features, 'header');
            $header->appendChild($header_buttons);
        }
        
        // Wrap existing content
        $content_wrapper = $dom->createElement('div');
        $content_wrapper->setAttribute('class', 'cbd-collapse-content');
        
        // Move all existing child nodes to the content wrapper
        $children = array();
        foreach ($container->childNodes as $child) {
            $children[] = $child;
        }
        
        foreach ($children as $child) {
            $content_wrapper->appendChild($child);
        }
        
        // Add action buttons to content (for expanded state)
        if ($features['copyText']['enabled'] || $features['screenshot']['enabled']) {
            $content_buttons = self::create_action_buttons($dom, $features, 'content');
            $content_wrapper->appendChild($content_buttons);
        }
        
        // Clear container and add new structure
        $container->textContent = '';
        $container->appendChild($header);
        $container->appendChild($content_wrapper);
        
        // Add CSS class for styling
        $existing_classes = $container->getAttribute('class');
        $container->setAttribute('class', $existing_classes . ' cbd-collapsible');
        
        // Add CSS class for initial state
        if ($features['collapse']['defaultState'] === 'collapsed') {
            $container->setAttribute('class', $container->getAttribute('class') . ' cbd-collapsed');
        }
    }
    
    /**
     * Add icon to container (when no collapse)
     */
    private static function add_icon_to_container($dom, $container, $icon_config) {
        $icon_element = self::create_icon_element($dom, $icon_config);
        
        // Insert icon at the beginning
        if ($container->firstChild) {
            $container->insertBefore($icon_element, $container->firstChild);
        } else {
            $container->appendChild($icon_element);
        }
    }
    
    /**
     * Create icon element
     */
    private static function create_icon_element($dom, $icon_config) {
        $icon_div = $dom->createElement('div');
        $icon_div->setAttribute('class', 'cbd-container-icon');
        
        $icon_span = $dom->createElement('span');
        $icon_span->setAttribute('class', 'dashicons ' . esc_attr($icon_config['value']));
        
        $icon_div->appendChild($icon_span);
        
        return $icon_div;
    }
    
    /**
     * Create action buttons
     */
    private static function create_action_buttons($dom, $features, $context) {
        $buttons_container = $dom->createElement('div');
        $buttons_container->setAttribute('class', 'cbd-action-buttons cbd-action-buttons-' . $context);
        
        // Copy button
        if ($features['copyText']['enabled']) {
            $copy_button = $dom->createElement('button');
            $copy_button->setAttribute('type', 'button');
            $copy_button->setAttribute('class', 'cbd-copy-button');
            
            $copy_icon = $dom->createElement('span');
            $copy_icon->setAttribute('class', 'dashicons dashicons-clipboard');
            $copy_button->appendChild($copy_icon);
            
            $copy_text = $dom->createElement('span');
            $copy_text->textContent = $features['copyText']['buttonText'];
            $copy_button->appendChild($copy_text);
            
            $buttons_container->appendChild($copy_button);
        }
        
        // Screenshot button
        if ($features['screenshot']['enabled']) {
            $screenshot_button = $dom->createElement('button');
            $screenshot_button->setAttribute('type', 'button');
            $screenshot_button->setAttribute('class', 'cbd-screenshot-button');
            
            $screenshot_icon = $dom->createElement('span');
            $screenshot_icon->setAttribute('class', 'dashicons dashicons-camera');
            $screenshot_button->appendChild($screenshot_icon);
            
            $screenshot_text = $dom->createElement('span');
            $screenshot_text->textContent = $features['screenshot']['buttonText'];
            $screenshot_button->appendChild($screenshot_text);
            
            $buttons_container->appendChild($screenshot_button);
        }
        
        return $buttons_container;
    }
    
    /**
     * Add action buttons (when no collapse)
     */
    private static function add_action_buttons($dom, $container, $features, $context) {
        $buttons = self::create_action_buttons($dom, $features, $context);
        $container->appendChild($buttons);
    }
    
    /**
     * Apply inline styles from configuration
     */
    private static function apply_inline_styles($container, $styles) {
        $inline_styles = array();
        
        // Padding
        if (isset($styles['padding'])) {
            $padding = $styles['padding'];
            $inline_styles[] = sprintf(
                'padding: %dpx %dpx %dpx %dpx',
                $padding['top'] ?? 20,
                $padding['right'] ?? 20,
                $padding['bottom'] ?? 20,
                $padding['left'] ?? 20
            );
        }
        
        // Background Color
        if (!empty($styles['background']['color'])) {
            $inline_styles[] = 'background-color: ' . esc_attr($styles['background']['color']);
        }
        
        // Text Color
        if (!empty($styles['text']['color'])) {
            $inline_styles[] = 'color: ' . esc_attr($styles['text']['color']);
        }
        
        // Border
        if (isset($styles['border'])) {
            $border = $styles['border'];
            if (!empty($border['width']) && !empty($border['color'])) {
                $inline_styles[] = sprintf(
                    'border: %dpx solid %s',
                    intval($border['width']),
                    esc_attr($border['color'])
                );
            }
            
            if (!empty($border['radius'])) {
                $inline_styles[] = 'border-radius: ' . intval($border['radius']) . 'px';
            }
        }
        
        // Apply styles
        if (!empty($inline_styles)) {
            $existing_style = $container->getAttribute('style');
            $new_style = $existing_style . '; ' . implode('; ', $inline_styles);
            $container->setAttribute('style', trim($new_style, '; '));
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        // Only load if our block is used on this page
        if (!has_block('container-block-designer/container')) {
            return;
        }
        
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
        
        // Html2Canvas for screenshot feature
        wp_enqueue_script(
            'html2canvas',
            'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
            array(),
            '1.4.1',
            true
        );
        
        // Localize script for translations
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

// Initialize the renderer
CBD_Frontend_Renderer::init();