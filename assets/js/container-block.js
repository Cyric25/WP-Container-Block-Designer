/**
 * Container Block Designer - Gutenberg Block
 * Version: 2.2.0 - Complete with all 5 features
 */

(function(wp, $) {
    const { registerBlockType } = wp.blocks;
    const { InnerBlocks, InspectorControls, BlockControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl, Notice, Placeholder, Button, ToolbarGroup, ToolbarButton } = wp.components;
    const { Fragment, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const apiFetch = wp.apiFetch;
    
    // Register the block
    registerBlockType('container-block-designer/container', {
        title: __('Container Block', 'container-block-designer'),
        description: __('Ein anpassbarer Container-Block mit erweiterten Features', 'container-block-designer'),
        category: 'design',
        icon: 'layout',
        keywords: ['container', 'wrapper', 'section', 'layout', 'box'],
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
            blockFeatures: {
                type: 'object',
                default: {}
            }
        },
        supports: {
            align: ['wide', 'full'],
            html: false,
            anchor: true,
            customClassName: true
        },
        
        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { selectedBlock, customClasses, blockConfig, blockFeatures } = attributes;
            const [availableBlocks, setAvailableBlocks] = useState([]);
            const [isLoading, setIsLoading] = useState(true);
            const [selectedBlockData, setSelectedBlockData] = useState(null);
            
            // Load available blocks on mount
            useEffect(() => {
                loadAvailableBlocks();
            }, []);
            
            // Load available blocks from server
            const loadAvailableBlocks = () => {
                // Try REST API first
                apiFetch({ 
                    path: '/cbd/v1/blocks',
                    method: 'GET'
                }).then(blocks => {
                    console.log('📦 Available blocks loaded:', blocks);
                    setAvailableBlocks(blocks || []);
                    setIsLoading(false);
                    
                    // Load data for selected block if exists
                    if (selectedBlock && blocks) {
                        const block = blocks.find(b => b.slug === selectedBlock);
                        if (block) {
                            loadBlockData(block.id);
                        }
                    }
                }).catch(error => {
                    console.error('REST API Error, trying AJAX:', error);
                    // Fallback to AJAX if REST fails
                    loadBlocksViaAjax();
                });
            };
            
            // Fallback: Load blocks via AJAX
            const loadBlocksViaAjax = () => {
                if ($ && window.cbdBlockData) {
                    $.ajax({
                        url: window.cbdBlockData.ajaxUrl || '/wp-admin/admin-ajax.php',
                        method: 'POST',
                        data: {
                            action: 'cbd_get_blocks',
                            nonce: window.cbdBlockData.nonce || ''
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                setAvailableBlocks(response.data);
                                setIsLoading(false);
                                
                                if (selectedBlock && response.data) {
                                    const block = response.data.find(b => b.slug === selectedBlock);
                                    if (block) {
                                        loadBlockData(block.id);
                                    }
                                }
                            }
                        },
                        error: function() {
                            console.error('Failed to load blocks via AJAX');
                            setIsLoading(false);
                        }
                    });
                } else {
                    setIsLoading(false);
                }
            };
            
            // Load complete block data including features
            const loadBlockData = (blockId) => {
                // Try custom endpoint first
                apiFetch({
                    path: `/cbd/v1/block/${blockId}`,
                    method: 'GET'
                }).then(data => {
                    console.log('📋 Block data loaded:', data);
                    processBlockData(data);
                }).catch(error => {
                    console.error('REST Error, trying AJAX:', error);
                    // Fallback to AJAX
                    loadBlockDataViaAjax(blockId);
                });
            };
            
            // Fallback: Load block data via AJAX
            const loadBlockDataViaAjax = (blockId) => {
                if ($ && window.cbdBlockData) {
                    $.ajax({
                        url: window.cbdBlockData.ajaxUrl || '/wp-admin/admin-ajax.php',
                        method: 'POST',
                        data: {
                            action: 'cbd_get_block_data',
                            block_id: blockId,
                            nonce: window.cbdBlockData.nonce || ''
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                processBlockData(response.data);
                            }
                        },
                        error: function() {
                            console.error('Failed to load block data via AJAX');
                        }
                    });
                }
            };
            
            // Process loaded block data
            const processBlockData = (data) => {
                setSelectedBlockData(data);
                
                // Parse and set features
                if (data.features) {
                    const features = typeof data.features === 'string' 
                        ? JSON.parse(data.features) 
                        : data.features;
                    setAttributes({ blockFeatures: features });
                    console.log('✅ Features loaded:', features);
                }
                
                // Parse and set config
                if (data.config) {
                    const config = typeof data.config === 'string' 
                        ? JSON.parse(data.config) 
                        : data.config;
                    setAttributes({ blockConfig: config });
                }
            };
            
            // Handle block selection change
            const onBlockChange = (newBlockSlug) => {
                setAttributes({ selectedBlock: newBlockSlug });
                
                if (newBlockSlug) {
                    const block = availableBlocks.find(b => b.slug === newBlockSlug);
                    if (block) {
                        loadBlockData(block.id);
                    }
                } else {
                    setSelectedBlockData(null);
                    setAttributes({ 
                        blockFeatures: {},
                        blockConfig: {}
                    });
                }
            };
            
            // Get active features list
            const getActiveFeaturesList = () => {
                const features = [];
                
                if (blockFeatures?.icon?.enabled) {
                    features.push({
                        name: __('Block-Icon', 'container-block-designer'),
                        detail: blockFeatures.icon.value || 'dashicons-admin-generic'
                    });
                }
                
                if (blockFeatures?.collapse?.enabled) {
                    features.push({
                        name: __('Ein-/Ausklappbar', 'container-block-designer'),
                        detail: blockFeatures.collapse.defaultState === 'collapsed' 
                            ? __('Eingeklappt', 'container-block-designer')
                            : __('Ausgeklappt', 'container-block-designer')
                    });
                }
                
                if (blockFeatures?.numbering?.enabled) {
                    features.push({
                        name: __('Nummerierung', 'container-block-designer'),
                        detail: blockFeatures.numbering.format === 'alpha' ? 'A, B, C...' :
                                blockFeatures.numbering.format === 'roman' ? 'I, II, III...' : '1, 2, 3...'
                    });
                }
                
                if (blockFeatures?.copyText?.enabled) {
                    features.push({
                        name: __('Text kopieren', 'container-block-designer'),
                        detail: blockFeatures.copyText.buttonText || __('Text kopieren', 'container-block-designer')
                    });
                }
                
                if (blockFeatures?.screenshot?.enabled) {
                    features.push({
                        name: __('Screenshot', 'container-block-designer'),
                        detail: blockFeatures.screenshot.buttonText || __('Screenshot', 'container-block-designer')
                    });
                }
                
                return features;
            };
            
            // Generate container classes
            const containerClasses = [
                'cbd-container',
                selectedBlock ? `cbd-container-${selectedBlock}` : '',
                customClasses
            ].filter(Boolean).join(' ');
            
            // Get styles from config
            const styles = blockConfig?.styles || {};
            const cssVars = {
                '--cbd-padding-top': `${styles.padding?.top || 20}px`,
                '--cbd-padding-right': `${styles.padding?.right || 20}px`,
                '--cbd-padding-bottom': `${styles.padding?.bottom || 20}px`,
                '--cbd-padding-left': `${styles.padding?.left || 20}px`,
                '--cbd-background-color': styles.background?.color || '#ffffff',
                '--cbd-text-color': styles.text?.color || '#000000',
                '--cbd-text-alignment': styles.text?.alignment || 'left',
                '--cbd-border-width': `${styles.border?.width || 0}px`,
                '--cbd-border-color': styles.border?.color || '#dddddd',
                '--cbd-border-radius': `${styles.border?.radius || 0}px`
            };
            
            const blockProps = useBlockProps({
                className: containerClasses,
                style: cssVars
            });
            
            const activeFeatures = getActiveFeaturesList();
            
            return (
                <Fragment>
                    <BlockControls>
                        <ToolbarGroup>
                            <ToolbarButton
                                icon="admin-appearance"
                                label={__('Container-Design auswählen', 'container-block-designer')}
                                onClick={() => {
                                    document.querySelector('.edit-post-sidebar')?.classList.add('is-open');
                                }}
                            />
                        </ToolbarGroup>
                    </BlockControls>
                    
                    <InspectorControls>
                        <PanelBody 
                            title={__('Container-Einstellungen', 'container-block-designer')}
                            initialOpen={true}
                        >
                            {isLoading ? (
                                <p>{__('Lade verfügbare Blocks...', 'container-block-designer')}</p>
                            ) : (
                                <Fragment>
                                    <SelectControl
                                        label={__('Container-Design auswählen', 'container-block-designer')}
                                        value={selectedBlock}
                                        options={[
                                            { label: __('-- Kein Design --', 'container-block-designer'), value: '' },
                                            ...availableBlocks.map(block => ({
                                                label: block.name,
                                                value: block.slug
                                            }))
                                        ]}
                                        onChange={onBlockChange}
                                        help={selectedBlockData ? selectedBlockData.description : null}
                                    />
                                    
                                    <TextControl
                                        label={__('Zusätzliche CSS-Klassen', 'container-block-designer')}
                                        value={customClasses}
                                        onChange={(value) => setAttributes({ customClasses: value })}
                                        help={__('Optionale CSS-Klassen für erweiterte Anpassungen', 'container-block-designer')}
                                    />
                                    
                                    {selectedBlockData && (
                                        <Notice 
                                            status="info" 
                                            isDismissible={false}
                                        >
                                            <strong>{selectedBlockData.name}</strong>
                                            {selectedBlockData.description && (
                                                <Fragment>
                                                    <br />
                                                    <small>{selectedBlockData.description}</small>
                                                </Fragment>
                                            )}
                                        </Notice>
                                    )}
                                </Fragment>
                            )}
                        </PanelBody>
                        
                        {activeFeatures.length > 0 && (
                            <PanelBody 
                                title={`${__('Aktive Features', 'container-block-designer')} (${activeFeatures.length})`}
                                initialOpen={false}
                            >
                                <div className="cbd-features-panel">
                                    {activeFeatures.map((feature, index) => (
                                        <div key={index} className="cbd-feature-item" style={{
                                            padding: '8px 0',
                                            borderBottom: index < activeFeatures.length - 1 ? '1px solid #e0e0e0' : 'none'
                                        }}>
                                            <div style={{ display: 'flex', alignItems: 'center', marginBottom: '4px' }}>
                                                <span style={{ color: '#00a32a', marginRight: '8px' }}>✅</span>
                                                <strong style={{ fontSize: '13px' }}>{feature.name}</strong>
                                            </div>
                                            {feature.detail && (
                                                <div style={{ marginLeft: '28px', fontSize: '12px', color: '#666' }}>
                                                    {feature.detail}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                    
                                    <div style={{
                                        marginTop: '12px',
                                        padding: '8px',
                                        background: '#f0f6fc',
                                        borderRadius: '4px',
                                        fontSize: '12px',
                                        color: '#555'
                                    }}>
                                        {__('Features können im Admin-Bereich konfiguriert werden.', 'container-block-designer')}
                                    </div>
                                </div>
                            </PanelBody>
                        )}
                    </InspectorControls>
                    
                    <div {...blockProps}>
                        {!selectedBlock ? (
                            <Placeholder
                                icon="layout"
                                label={__('Container Block', 'container-block-designer')}
                                instructions={__('Wählen Sie ein Container-Design aus den Block-Einstellungen.', 'container-block-designer')}
                            >
                                {!isLoading && availableBlocks.length === 0 && (
                                    <Notice status="warning" isDismissible={false}>
                                        {__('Keine aktiven Container-Designs gefunden. Bitte erstellen Sie zuerst ein Design im Admin-Bereich.', 'container-block-designer')}
                                    </Notice>
                                )}
                            </Placeholder>
                        ) : (
                            <Fragment>
                                {/* Feature Preview Header */}
                                {(blockFeatures?.icon?.enabled || blockFeatures?.numbering?.enabled || blockFeatures?.collapse?.enabled) && (
                                    <div className="cbd-block-header" style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        marginBottom: '15px',
                                        padding: '10px',
                                        background: 'rgba(0,0,0,0.02)',
                                        borderRadius: '4px',
                                        border: '1px solid rgba(0,0,0,0.05)'
                                    }}>
                                        {blockFeatures.icon?.enabled && (
                                            <span 
                                                className={`dashicons ${blockFeatures.icon.value || 'dashicons-admin-generic'}`}
                                                style={{
                                                    fontSize: '24px',
                                                    width: '24px',
                                                    height: '24px',
                                                    marginRight: '10px',
                                                    color: styles.text?.color || '#000'
                                                }}
                                            />
                                        )}
                                        {blockFeatures.numbering?.enabled && (
                                            <span style={{
                                                fontWeight: 'bold',
                                                fontSize: '18px',
                                                marginRight: '10px',
                                                color: styles.text?.color || '#000'
                                            }}>
                                                {blockFeatures.numbering.format === 'alpha' ? 'A.' :
                                                 blockFeatures.numbering.format === 'roman' ? 'I.' : '1.'}
                                            </span>
                                        )}
                                        <span style={{ 
                                            flex: 1, 
                                            fontSize: '14px', 
                                            fontWeight: '500',
                                            color: styles.text?.color || '#000'
                                        }}>
                                            {selectedBlockData?.name || selectedBlock}
                                        </span>
                                        {blockFeatures.collapse?.enabled && (
                                            <span 
                                                className="dashicons dashicons-arrow-down-alt2"
                                                style={{
                                                    fontSize: '20px',
                                                    color: '#666',
                                                    cursor: 'pointer'
                                                }}
                                                title={__('Ein-/Ausklappbar', 'container-block-designer')}
                                            />
                                        )}
                                    </div>
                                )}
                                
                                {/* Inner Blocks Container */}
                                <div className="cbd-block-content">
                                    <InnerBlocks 
                                        renderAppender={InnerBlocks.ButtonBlockAppender}
                                        template={[
                                            ['core/paragraph', { 
                                                placeholder: __('Fügen Sie hier Ihren Inhalt ein...', 'container-block-designer')
                                            }]
                                        ]}
                                    />
                                </div>
                                
                                {/* Feature Action Buttons */}
                                {(blockFeatures?.copyText?.enabled || blockFeatures?.screenshot?.enabled) && (
                                    <div className="cbd-block-actions" style={{
                                        display: 'flex',
                                        gap: '10px',
                                        marginTop: '15px',
                                        padding: '10px',
                                        background: 'rgba(0,0,0,0.02)',
                                        borderRadius: '4px',
                                        border: '1px solid rgba(0,0,0,0.05)'
                                    }}>
                                        {blockFeatures.copyText?.enabled && (
                                            <Button
                                                variant="secondary"
                                                size="small"
                                                icon="clipboard"
                                            >
                                                {blockFeatures.copyText.buttonText || __('Text kopieren', 'container-block-designer')}
                                            </Button>
                                        )}
                                        {blockFeatures.screenshot?.enabled && (
                                            <Button
                                                variant="secondary"
                                                size="small"
                                                icon="camera"
                                            >
                                                {blockFeatures.screenshot.buttonText || __('Screenshot', 'container-block-designer')}
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </Fragment>
                        )}
                    </div>
                </Fragment>
            );
        },
        
        save: function(props) {
            const { attributes } = props;
            const { selectedBlock, customClasses, blockConfig } = attributes;
            
            // Generate container classes
            const containerClasses = [
                'cbd-container',
                selectedBlock ? `cbd-container-${selectedBlock}` : '',
                customClasses
            ].filter(Boolean).join(' ');
            
            // Save with data attributes for features
            const blockProps = wp.blockEditor.useBlockProps.save({
                className: containerClasses,
                'data-block-type': selectedBlock || undefined
            });
            
            return (
                <div {...blockProps}>
                    <InnerBlocks.Content />
                </div>
            );
        },
        
        // Deprecated versions for migration
        deprecated: [
            {
                attributes: {
                    selectedBlock: { type: 'string', default: '' },
                    customClasses: { type: 'string', default: '' }
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
                            className: `wp-block-container-block-designer-container ${containerClasses}`,
                            'data-block-type': selectedBlock
                        },
                        wp.element.createElement(InnerBlocks.Content)
                    );
                },
                migrate: function(attributes) {
                    return {
                        ...attributes,
                        blockConfig: {},
                        blockFeatures: {}
                    };
                }
            }
        ]
    });
    
    // Add AJAX handler for getting blocks
    if (!wp.data.select('core/editor')) {
        // We're in the widget editor or elsewhere, provide fallback
        wp.domReady(() => {
            console.log('✅ Container Block registered with all 5 features support');
        });
    } else {
        console.log('✅ Container Block registered with all 5 features support');
    }
    
})(window.wp, window.jQuery);