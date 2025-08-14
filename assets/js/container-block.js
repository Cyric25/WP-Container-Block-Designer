(function(wp, $) {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { InnerBlocks, InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl, ToggleControl, Notice, BaseControl, ButtonGroup, Button } = wp.components;
    const { Fragment, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    
    // Dashicon-Optionen für Icon-Auswahl
    const DASHICON_OPTIONS = [
        { label: __('Standard', 'container-block-designer'), value: 'dashicons-admin-generic' },
        { label: __('Info', 'container-block-designer'), value: 'dashicons-info' },
        { label: __('Warnung', 'container-block-designer'), value: 'dashicons-warning' },
        { label: __('Glühbirne', 'container-block-designer'), value: 'dashicons-lightbulb' },
        { label: __('Stern', 'container-block-designer'), value: 'dashicons-star-filled' },
        { label: __('Herz', 'container-block-designer'), value: 'dashicons-heart' },
        { label: __('Pfeil rechts', 'container-block-designer'), value: 'dashicons-arrow-right-alt' },
        { label: __('Pfeil unten', 'container-block-designer'), value: 'dashicons-arrow-down-alt' },
        { label: __('Kamera', 'container-block-designer'), value: 'dashicons-camera' },
        { label: __('Clipboard', 'container-block-designer'), value: 'dashicons-clipboard' },
        { label: __('Einstellungen', 'container-block-designer'), value: 'dashicons-admin-settings' },
        { label: __('Layout', 'container-block-designer'), value: 'dashicons-layout' }
    ];

    registerBlockType('container-block-designer/container', {
        title: __('Design Container', 'container-block-designer'),
        icon: 'layout',
        category: 'design',
        description: __('Ein anpassbarer Container-Block mit erweiterten Features', 'container-block-designer'),
        
        attributes: {
            selectedBlock: {
                type: 'string',
                default: ''
            },
            customClasses: {
                type: 'string', 
                default: ''
            },
            blockConfig: {
                type: 'object',
                default: {}
            },
            // Feature-Attribute (vereinfacht für bessere Kompatibilität)
            enableIcon: {
                type: 'boolean'
                // Kein default - wird von globalen Einstellungen bestimmt
            },
            iconValue: {
                type: 'string',
                default: 'dashicons-admin-generic'
            },
            enableCollapse: {
                type: 'boolean'
                // Kein default - wird von globalen Einstellungen bestimmt
            },
            collapseDefault: {
                type: 'string',
                default: 'expanded'
            },
            enableNumbering: {
                type: 'boolean'
                // Kein default - wird von globalen Einstellungen bestimmt
            },
            numberingFormat: {
                type: 'string',
                default: 'numeric'
            },
            enableCopyText: {
                type: 'boolean'
                // Kein default - wird von globalen Einstellungen bestimmt
            },
            copyButtonText: {
                type: 'string',
                default: 'Text kopieren'
            },
            enableScreenshot: {
                type: 'boolean'
                // Kein default - wird von globalen Einstellungen bestimmt
            },
            screenshotButtonText: {
                type: 'string',
                default: 'Screenshot'
            },
            // Flag um zu vermerken, ob Block bereits initialisiert wurde
            _initialized: {
                type: 'boolean',
                default: false
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { 
                selectedBlock, 
                customClasses, 
                enableIcon, 
                iconValue, 
                enableCollapse, 
                collapseDefault,
                enableNumbering,
                numberingFormat,
                enableCopyText,
                copyButtonText,
                enableScreenshot,
                screenshotButtonText,
                _initialized
            } = attributes;
            
            const [availableBlocks, setAvailableBlocks] = useState([]);
            const [blockConfig, setBlockConfig] = useState({});
            const [globalSettings, setGlobalSettings] = useState(null);
            const [isLoading, setIsLoading] = useState(true);
            
            // Load available blocks and global settings
            useEffect(() => {
                const loadData = async () => {
                    try {
                        // Load available blocks
                        if (window.cbdData && window.cbdData.blocks) {
                            setAvailableBlocks(window.cbdData.blocks);
                        }
                        
                        // Load global settings
                        if (window.cbdData && window.cbdData.apiUrl) {
                            try {
                                const response = await fetch(window.cbdData.apiUrl + 'global-settings');
                                if (response.ok) {
                                    const settings = await response.json();
                                    setGlobalSettings(settings);
                                    
                                    // Apply global defaults if block is not yet initialized
                                    if (!_initialized && settings) {
                                        applyGlobalDefaults(settings);
                                    }
                                }
                            } catch (error) {
                                console.warn('Could not load global settings:', error);
                            }
                        }
                    } catch (error) {
                        console.warn('Could not load data:', error);
                    } finally {
                        setIsLoading(false);
                    }
                };
                
                loadData();
            }, []);
            
            // Apply global defaults to new blocks
            const applyGlobalDefaults = (settings) => {
                if (_initialized || !settings || !settings.features) return;
                
                const updates = {
                    _initialized: true
                };
                
                // Apply global defaults for each feature
                if (settings.features.icon && settings.features.icon.enabled_by_default) {
                    updates.enableIcon = true;
                    updates.iconValue = settings.features.icon.default_value;
                }
                
                if (settings.features.collapse && settings.features.collapse.enabled_by_default) {
                    updates.enableCollapse = true;
                    updates.collapseDefault = settings.features.collapse.default_state;
                }
                
                if (settings.features.numbering && settings.features.numbering.enabled_by_default) {
                    updates.enableNumbering = true;
                    updates.numberingFormat = settings.features.numbering.default_format;
                }
                
                if (settings.features.copyText && settings.features.copyText.enabled_by_default) {
                    updates.enableCopyText = true;
                    updates.copyButtonText = settings.features.copyText.default_button_text;
                }
                
                if (settings.features.screenshot && settings.features.screenshot.enabled_by_default) {
                    updates.enableScreenshot = true;
                    updates.screenshotButtonText = settings.features.screenshot.default_button_text;
                }
                
                setAttributes(updates);
            };
            
            // Load block configuration when selectedBlock changes
            useEffect(() => {
                if (selectedBlock && availableBlocks.length > 0) {
                    const blockData = availableBlocks.find(block => block.slug === selectedBlock);
                    if (blockData && blockData.config) {
                        try {
                            const config = typeof blockData.config === 'string' 
                                         ? JSON.parse(blockData.config) 
                                         : blockData.config;
                            setBlockConfig(config);
                        } catch (error) {
                            console.warn('Could not parse block config:', error);
                            setBlockConfig({});
                        }
                    } else {
                        setBlockConfig({});
                    }
                }
            }, [selectedBlock, availableBlocks]);
            
            // Get styles from block config
            const styles = blockConfig.styles || {};
            
            // Generate container classes
            const containerClasses = [
                'cbd-container',
                selectedBlock ? `cbd-container-${selectedBlock}` : '',
                customClasses,
                enableIcon ? 'has-icon' : '',
                enableCollapse ? 'has-collapse' : '',
                enableNumbering ? 'has-numbering' : '',
                (enableCopyText || enableScreenshot) ? 'has-action-buttons' : ''
            ].filter(Boolean).join(' ');
            
            // Inline styles from block configuration
            let containerStyle = {};
            
            if (styles.padding) {
                containerStyle.padding = `${styles.padding.top || 20}px ${styles.padding.right || 20}px ${styles.padding.bottom || 20}px ${styles.padding.left || 20}px`;
            }
            
            if (styles.background?.color) {
                containerStyle.backgroundColor = styles.background.color;
            }
            
            if (styles.text?.color) {
                containerStyle.color = styles.text.color;
            }
            
            if (styles.border) {
                const border = styles.border;
                if (border.width && border.color) {
                    containerStyle.border = `${border.width}px solid ${border.color}`;
                }
                if (border.radius) {
                    containerStyle.borderRadius = `${border.radius}px`;
                }
            }
            
            // Check if feature can be overridden
            const canOverride = (featureName) => {
                return !globalSettings || 
                       !globalSettings.features || 
                       !globalSettings.features[featureName] || 
                       globalSettings.features[featureName].allow_override !== false;
            };
            
            return (
                wp.element.createElement(
                    Fragment,
                    null,
                    
                    // Inspector Controls
                    wp.element.createElement(
                        InspectorControls,
                        null,
                        
                        // Block Settings Panel
                        wp.element.createElement(
                            PanelBody,
                            {
                                title: __('Block-Einstellungen', 'container-block-designer'),
                                initialOpen: true
                            },
                            
                            wp.element.createElement(SelectControl, {
                                label: __('Container-Design', 'container-block-designer'),
                                value: selectedBlock,
                                options: [
                                    { label: __('Standard', 'container-block-designer'), value: '' },
                                    ...availableBlocks.map(block => ({
                                        label: block.name,
                                        value: block.slug
                                    }))
                                ],
                                onChange: (value) => setAttributes({ selectedBlock: value })
                            }),
                            
                            wp.element.createElement(TextControl, {
                                label: __('Custom CSS Classes', 'container-block-designer'),
                                value: customClasses,
                                onChange: (value) => setAttributes({ customClasses: value })
                            })
                        ),
                        
                        // Features Panel
                        wp.element.createElement(
                            PanelBody,
                            {
                                title: __('Features', 'container-block-designer'),
                                initialOpen: false
                            },
                            
                            // Global settings info
                            globalSettings && wp.element.createElement(
                                Notice,
                                { 
                                    status: 'info',
                                    isDismissible: false,
                                    className: 'cbd-global-settings-notice'
                                },
                                wp.element.createElement(
                                    'p',
                                    { style: { margin: 0, fontSize: '12px' } },
                                    __('🌐 Globale Standardwerte sind aktiv. Sie können diese hier überschreiben.', 'container-block-designer')
                                )
                            ),
                            
                            // Icon Feature
                            wp.element.createElement(
                                BaseControl,
                                { label: __('Block Icon', 'container-block-designer') },
                                
                                canOverride('icon') ? [
                                    wp.element.createElement(ToggleControl, {
                                        key: 'icon-toggle',
                                        checked: enableIcon || false,
                                        onChange: (value) => setAttributes({ enableIcon: value })
                                    }),
                                    
                                    (enableIcon || false) && wp.element.createElement(SelectControl, {
                                        key: 'icon-select',
                                        label: __('Icon auswählen', 'container-block-designer'),
                                        value: iconValue,
                                        options: DASHICON_OPTIONS,
                                        onChange: (value) => setAttributes({ iconValue: value })
                                    })
                                ] : wp.element.createElement(
                                    Notice,
                                    { status: 'warning', isDismissible: false },
                                    __('Durch globale Einstellungen gesperrt', 'container-block-designer')
                                )
                            ),
                            
                            // Collapse Feature
                            wp.element.createElement(
                                BaseControl,
                                { label: __('Ein-/Ausklappbar', 'container-block-designer') },
                                
                                canOverride('collapse') ? [
                                    wp.element.createElement(ToggleControl, {
                                        key: 'collapse-toggle',
                                        checked: enableCollapse || false,
                                        onChange: (value) => setAttributes({ enableCollapse: value })
                                    }),
                                    
                                    (enableCollapse || false) && wp.element.createElement(SelectControl, {
                                        key: 'collapse-select',
                                        label: __('Standard-Zustand', 'container-block-designer'),
                                        value: collapseDefault,
                                        options: [
                                            { label: __('Ausgeklappt', 'container-block-designer'), value: 'expanded' },
                                            { label: __('Eingeklappt', 'container-block-designer'), value: 'collapsed' }
                                        ],
                                        onChange: (value) => setAttributes({ collapseDefault: value })
                                    })
                                ] : wp.element.createElement(
                                    Notice,
                                    { status: 'warning', isDismissible: false },
                                    __('Durch globale Einstellungen gesperrt', 'container-block-designer')
                                )
                            ),
                            
                            // Numbering Feature - NEU HINZUGEFÜGT
                            wp.element.createElement(
                                BaseControl,
                                { label: __('Nummerierung', 'container-block-designer') },
                                
                                canOverride('numbering') ? [
                                    wp.element.createElement(ToggleControl, {
                                        key: 'numbering-toggle',
                                        checked: enableNumbering || false,
                                        onChange: (value) => setAttributes({ enableNumbering: value })
                                    }),
                                    
                                    (enableNumbering || false) && wp.element.createElement(SelectControl, {
                                        key: 'numbering-select',
                                        label: __('Nummerierungs-Format', 'container-block-designer'),
                                        value: numberingFormat,
                                        options: [
                                            { label: __('Numerisch (1, 2, 3)', 'container-block-designer'), value: 'numeric' },
                                            { label: __('Alphabetisch (A, B, C)', 'container-block-designer'), value: 'alpha' },
                                            { label: __('Römisch (I, II, III)', 'container-block-designer'), value: 'roman' }
                                        ],
                                        onChange: (value) => setAttributes({ numberingFormat: value })
                                    })
                                ] : wp.element.createElement(
                                    Notice,
                                    { status: 'warning', isDismissible: false },
                                    __('Durch globale Einstellungen gesperrt', 'container-block-designer')
                                )
                            ),
                            
                            // Copy Text Feature
                            wp.element.createElement(
                                BaseControl,
                                { label: __('Text kopieren', 'container-block-designer') },
                                
                                canOverride('copyText') ? [
                                    wp.element.createElement(ToggleControl, {
                                        key: 'copy-toggle',
                                        checked: enableCopyText || false,
                                        onChange: (value) => setAttributes({ enableCopyText: value })
                                    }),
                                    
                                    (enableCopyText || false) && wp.element.createElement(TextControl, {
                                        key: 'copy-text',
                                        label: __('Button-Text', 'container-block-designer'),
                                        value: copyButtonText,
                                        onChange: (value) => setAttributes({ copyButtonText: value })
                                    })
                                ] : wp.element.createElement(
                                    Notice,
                                    { status: 'warning', isDismissible: false },
                                    __('Durch globale Einstellungen gesperrt', 'container-block-designer')
                                )
                            ),
                            
                            // Screenshot Feature
                            wp.element.createElement(
                                BaseControl,
                                { label: __('Screenshot', 'container-block-designer') },
                                
                                canOverride('screenshot') ? [
                                    wp.element.createElement(ToggleControl, {
                                        key: 'screenshot-toggle',
                                        checked: enableScreenshot || false,
                                        onChange: (value) => setAttributes({ enableScreenshot: value })
                                    }),
                                    
                                    (enableScreenshot || false) && wp.element.createElement(TextControl, {
                                        key: 'screenshot-text',
                                        label: __('Button-Text', 'container-block-designer'),
                                        value: screenshotButtonText,
                                        onChange: (value) => setAttributes({ screenshotButtonText: value })
                                    })
                                ] : wp.element.createElement(
                                    Notice,
                                    { status: 'warning', isDismissible: false },
                                    __('Durch globale Einstellungen gesperrt', 'container-block-designer')
                                )
                            )
                        )
                    ),
                    
                    // Block Content
                    wp.element.createElement(
                        'div',
                        {
                            className: `wp-block-container-block-designer-container ${containerClasses}`,
                            style: containerStyle,
                            'data-block-type': selectedBlock,
                            // Add data attributes for preview
                            'data-icon': enableIcon ? 'true' : undefined,
                            'data-icon-value': enableIcon ? iconValue : undefined,
                            'data-collapse': enableCollapse ? 'true' : undefined,
                            'data-collapse-default': enableCollapse ? collapseDefault : undefined,
                            'data-numbering': enableNumbering ? 'true' : undefined,
                            'data-numbering-format': enableNumbering ? numberingFormat : undefined
                        },
                        
                        // Icon Preview
                        (enableIcon || false) && wp.element.createElement(
                            'div',
                            { 
                                className: 'cbd-container-icon',
                                style: { marginBottom: '15px' }
                            },
                            wp.element.createElement('span', {
                                className: `dashicons ${iconValue}`,
                                style: { fontSize: '24px', color: 'inherit' }
                            })
                        ),
                        
                        // Numbering Preview - NEU
                        (enableNumbering || false) && wp.element.createElement(
                            'div',
                            {
                                className: 'cbd-numbering-preview',
                                style: {
                                    position: 'absolute',
                                    top: '15px',
                                    left: '15px',
                                    width: '30px',
                                    height: '30px',
                                    background: '#2271b1',
                                    color: 'white',
                                    borderRadius: '50%',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    fontSize: '14px',
                                    fontWeight: 'bold',
                                    zIndex: '10'
                                }
                            },
                            numberingFormat === 'alpha' ? 'A' :
                            numberingFormat === 'roman' ? 'I' : '1'
                        ),
                        
                        // Collapse Header Preview
                        (enableCollapse || false) && wp.element.createElement(
                            'div',
                            {
                                className: 'cbd-collapse-header-preview',
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '10px',
                                    padding: '10px',
                                    backgroundColor: 'rgba(0,0,0,0.05)',
                                    borderRadius: '4px',
                                    marginBottom: '15px',
                                    fontSize: '14px'
                                }
                            },
                            wp.element.createElement('span', {
                                className: `dashicons dashicons-arrow-${collapseDefault === 'collapsed' ? 'right' : 'down'}-alt2`,
                                style: { fontSize: '16px' }
                            }),
                            wp.element.createElement('span', null, __('Container Inhalt', 'container-block-designer')),
                            
                            // Preview buttons in collapse header (when collapsed)
                            collapseDefault === 'collapsed' && ((enableCopyText || false) || (enableScreenshot || false)) && 
                            wp.element.createElement(
                                'div',
                                { 
                                    style: { 
                                        marginLeft: 'auto', 
                                        display: 'flex', 
                                        gap: '5px' 
                                    } 
                                },
                                (enableCopyText || false) && wp.element.createElement(Button, {
                                    variant: 'secondary',
                                    size: 'small',
                                    icon: 'clipboard',
                                    disabled: true
                                }),
                                (enableScreenshot || false) && wp.element.createElement(Button, {
                                    variant: 'secondary', 
                                    size: 'small',
                                    icon: 'camera',
                                    disabled: true
                                })
                            )
                        ),
                        
                        // Content Area (nur wenn nicht collapsed oder collapse deaktiviert)
                        (!(enableCollapse || false) || collapseDefault === 'expanded') && wp.element.createElement(
                            'div',
                            { 
                                className: 'cbd-container-content',
                                style: enableNumbering && !enableCollapse ? { paddingLeft: '40px' } : {}
                            },
                            
                            wp.element.createElement(InnerBlocks, {
                                allowedBlocks: true,
                                template: [
                                    ['core/paragraph', {
                                        placeholder: __('Fügen Sie hier Ihren Inhalt ein...', 'container-block-designer')
                                    }]
                                ]
                            }),
                            
                            // Preview Action Buttons (nur wenn nicht collapsed)
                            ((enableCopyText || false) || (enableScreenshot || false)) && wp.element.createElement(
                                'div',
                                {
                                    className: 'cbd-action-buttons-preview',
                                    style: {
                                        marginTop: '15px',
                                        display: 'flex',
                                        gap: '8px',
                                        justifyContent: 'flex-end'
                                    }
                                },
                                (enableCopyText || false) && wp.element.createElement(Button, {
                                    variant: 'secondary',
                                    size: 'small', 
                                    icon: 'clipboard',
                                    disabled: true
                                }, copyButtonText),
                                
                                (enableScreenshot || false) && wp.element.createElement(Button, {
                                    variant: 'secondary',
                                    size: 'small',
                                    icon: 'camera', 
                                    disabled: true
                                }, screenshotButtonText)
                            )
                        )
                    )
                )
            );
        },
        
        save: function(props) {
            const { attributes } = props;
            const { 
                selectedBlock, 
                customClasses,
                enableIcon,
                iconValue,
                enableCollapse,
                collapseDefault,
                enableNumbering,
                numberingFormat,
                enableCopyText,
                copyButtonText,
                enableScreenshot,
                screenshotButtonText
            } = attributes;
            
            // Generate container classes
            const containerClasses = [
                'cbd-container',
                selectedBlock ? `cbd-container-${selectedBlock}` : '',
                customClasses
            ].filter(Boolean).join(' ');
            
            // Build data attributes for JavaScript
            const dataAttributes = {
                'data-block-type': selectedBlock || undefined
            };
            
            if (enableIcon) {
                dataAttributes['data-icon'] = 'true';
                dataAttributes['data-icon-value'] = iconValue;
            }
            
            if (enableCollapse) {
                dataAttributes['data-collapse'] = 'true';
                dataAttributes['data-collapse-default'] = collapseDefault;
            }
            
            if (enableNumbering) {
                dataAttributes['data-numbering'] = 'true';
                dataAttributes['data-numbering-format'] = numberingFormat;
            }
            
            if (enableCopyText) {
                dataAttributes['data-copy'] = 'true';
                dataAttributes['data-copy-text'] = copyButtonText;
            }
            
            if (enableScreenshot) {
                dataAttributes['data-screenshot'] = 'true';
                dataAttributes['data-screenshot-text'] = screenshotButtonText;
            }
            
            return wp.element.createElement(
                'div',
                {
                    className: containerClasses,
                    ...dataAttributes
                },
                wp.element.createElement(InnerBlocks.Content)
            );
        },
        
        // Deprecated versions für Kompatibilität
        deprecated: [
            {
                attributes: {
                    selectedBlock: { type: 'string', default: '' },
                    customClasses: { type: 'string', default: '' },
                    blockConfig: { type: 'object', default: {} },
                    enableIcon: { type: 'boolean', default: false },
                    iconValue: { type: 'string', default: 'dashicons-admin-generic' },
                    enableCollapse: { type: 'boolean', default: false },
                    collapseDefault: { type: 'string', default: 'expanded' },
                    enableCopyText: { type: 'boolean', default: false },
                    copyButtonText: { type: 'string', default: 'Text kopieren' },
                    enableScreenshot: { type: 'boolean', default: false },
                    screenshotButtonText: { type: 'string', default: 'Screenshot' }
                },
                
                save: function(props) {
                    const { attributes } = props;
                    const { selectedBlock, customClasses } = attributes;
                    
                    const containerClasses = [
                        'cbd-container',
                        selectedBlock ? `cbd-container-${selectedBlock}` : '',
                        customClasses
                    ].filter(Boolean).join(' ');
                    
                    return wp.element.createElement(
                        'div',
                        {
                            className: containerClasses,
                            'data-block-type': selectedBlock
                        },
                        wp.element.createElement(InnerBlocks.Content)
                    );
                },
                
                migrate: function(attributes) {
                    return {
                        ...attributes,
                        enableNumbering: false,
                        numberingFormat: 'numeric',
                        _initialized: true
                    };
                }
            }
        ]
    });
    
    console.log('✅ Container Block registered with global settings support');
    
})(window.wp, window.jQuery);