(function(wp) {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { InnerBlocks, InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, BaseControl, ButtonGroup, Button } = wp.components;
    const { Fragment, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const { select } = wp.data;
    
    // WordPress Dashicons für Icon-Auswahl
    const DASHICON_OPTIONS = [
        { label: __('Standard', 'container-block-designer'), value: 'dashicons-admin-generic' },
        { label: __('Info', 'container-block-designer'), value: 'dashicons-info' },
        { label: __('Warnung', 'container-block-designer'), value: 'dashicons-warning' },
        { label: __('Glühbirne', 'container-block-designer'), value: 'dashicons-lightbulb' },
        { label: __('Stern', 'container-block-designer'), value: 'dashicons-star-filled' },
        { label: __('Herz', 'container-block-designer'), value: 'dashicons-heart' },
        { label: __('Pfeil rechts', 'container-block-designer'), value: 'dashicons-arrow-right-alt' },
        { label: __('Pfeil down', 'container-block-designer'), value: 'dashicons-arrow-down-alt' },
        { label: __('Kamera', 'container-block-designer'), value: 'dashicons-camera' },
        { label: __('Clipboard', 'container-block-designer'), value: 'dashicons-clipboard' },
        { label: __('Einstellungen', 'container-block-designer'), value: 'dashicons-admin-settings' },
        { label: __('Layout', 'container-block-designer'), value: 'dashicons-layout' },
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
            // Feature-spezifische Attribute
            features: {
                type: 'object',
                default: {
                    icon: {
                        enabled: false,
                        value: 'dashicons-admin-generic'
                    },
                    collapse: {
                        enabled: false,
                        defaultState: 'expanded', // 'expanded' oder 'collapsed'
                        showInEditor: false // Neu: Zeige Collapse-Zustand im Editor
                    },
                    numbering: {
                        enabled: false,
                        format: 'numeric'
                    },
                    copyText: {
                        enabled: false,
                        buttonText: __('Text kopieren', 'container-block-designer')
                    },
                    screenshot: {
                        enabled: false,
                        buttonText: __('Screenshot', 'container-block-designer')
                    }
                }
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { selectedBlock, customClasses, features } = attributes;
            const [availableBlocks, setAvailableBlocks] = useState([]);
            const [isCollapsedInEditor, setIsCollapsedInEditor] = useState(features.collapse.defaultState === 'collapsed');
            
            // Lade verfügbare Blöcke
            useEffect(() => {
                if (window.cbdData && window.cbdData.blocks) {
                    setAvailableBlocks(window.cbdData.blocks);
                }
            }, []);
            
            // Update features helper
            const updateFeature = (featureName, updates) => {
                const newFeatures = {
                    ...features,
                    [featureName]: {
                        ...features[featureName],
                        ...updates
                    }
                };
                setAttributes({ features: newFeatures });
            };
            
            // Toggle Editor-Collapse-Zustand
            const toggleEditorCollapse = () => {
                const newState = !isCollapsedInEditor;
                setIsCollapsedInEditor(newState);
                updateFeature('collapse', { 
                    defaultState: newState ? 'collapsed' : 'expanded' 
                });
            };
            
            // Container-Klassen generieren
            const containerClasses = [
                'cbd-container',
                selectedBlock ? `cbd-container-${selectedBlock}` : '',
                customClasses,
                // Feature-Klassen
                features.icon.enabled ? 'has-icon' : '',
                features.collapse.enabled ? 'has-collapse' : '',
                features.numbering.enabled ? 'has-numbering' : '',
                (features.copyText.enabled || features.screenshot.enabled) ? 'has-action-buttons' : ''
            ].filter(Boolean).join(' ');
            
            const blockProps = useBlockProps({
                className: containerClasses
            });
            
            // Render Feature Buttons (nur wenn nicht collapsed oder wenn Editor-Preview)
            const renderFeatureButtons = () => {
                if (features.collapse.enabled && isCollapsedInEditor) {
                    return null; // Keine Buttons bei eingeklapptem Zustand
                }
                
                const buttons = [];
                
                if (features.copyText.enabled) {
                    buttons.push(
                        <Button
                            key="copy"
                            variant="secondary"
                            size="small"
                            icon="clipboard"
                            disabled
                            className="cbd-preview-button"
                        >
                            {features.copyText.buttonText}
                        </Button>
                    );
                }
                
                if (features.screenshot.enabled) {
                    buttons.push(
                        <Button
                            key="screenshot"
                            variant="secondary"
                            size="small"
                            icon="camera"
                            disabled
                            className="cbd-preview-button"
                        >
                            {features.screenshot.buttonText}
                        </Button>
                    );
                }
                
                return buttons.length > 0 ? (
                    <div className="cbd-feature-buttons-preview">
                        {buttons}
                    </div>
                ) : null;
            };
            
            return (
                <Fragment>
                    <InspectorControls>
                        {/* Block-Auswahl Panel */}
                        <PanelBody title={__('Block-Einstellungen', 'container-block-designer')} initialOpen={true}>
                            <SelectControl
                                label={__('Container-Style', 'container-block-designer')}
                                value={selectedBlock}
                                options={[
                                    { label: __('Standard', 'container-block-designer'), value: '' },
                                    ...availableBlocks.map(block => ({
                                        label: block.name,
                                        value: block.slug
                                    }))
                                ]}
                                onChange={(value) => setAttributes({ selectedBlock: value })}
                            />
                            <TextControl
                                label={__('Custom CSS Classes', 'container-block-designer')}
                                value={customClasses}
                                onChange={(value) => setAttributes({ customClasses: value })}
                            />
                        </PanelBody>
                        
                        {/* Features Panel */}
                        <PanelBody title={__('Features', 'container-block-designer')} initialOpen={false}>
                            
                            {/* Icon Feature */}
                            <BaseControl label={__('Block Icon', 'container-block-designer')}>
                                <ToggleControl
                                    checked={features.icon.enabled}
                                    onChange={(enabled) => updateFeature('icon', { enabled })}
                                />
                                {features.icon.enabled && (
                                    <SelectControl
                                        label={__('Icon auswählen', 'container-block-designer')}
                                        value={features.icon.value}
                                        options={DASHICON_OPTIONS}
                                        onChange={(value) => updateFeature('icon', { value })}
                                    />
                                )}
                            </BaseControl>
                            
                            {/* Collapse Feature */}
                            <BaseControl label={__('Ein-/Ausklappbar', 'container-block-designer')}>
                                <ToggleControl
                                    checked={features.collapse.enabled}
                                    onChange={(enabled) => updateFeature('collapse', { enabled })}
                                />
                                {features.collapse.enabled && (
                                    <Fragment>
                                        <SelectControl
                                            label={__('Standard-Zustand', 'container-block-designer')}
                                            value={features.collapse.defaultState}
                                            options={[
                                                { label: __('Ausgeklappt', 'container-block-designer'), value: 'expanded' },
                                                { label: __('Eingeklappt', 'container-block-designer'), value: 'collapsed' }
                                            ]}
                                            onChange={(value) => {
                                                updateFeature('collapse', { defaultState: value });
                                                setIsCollapsedInEditor(value === 'collapsed');
                                            }}
                                        />
                                        <BaseControl label={__('Editor-Vorschau', 'container-block-designer')}>
                                            <ButtonGroup>
                                                <Button
                                                    isPressed={!isCollapsedInEditor}
                                                    onClick={() => {
                                                        setIsCollapsedInEditor(false);
                                                        updateFeature('collapse', { defaultState: 'expanded' });
                                                    }}
                                                >
                                                    {__('Ausgeklappt', 'container-block-designer')}
                                                </Button>
                                                <Button
                                                    isPressed={isCollapsedInEditor}
                                                    onClick={() => {
                                                        setIsCollapsedInEditor(true);
                                                        updateFeature('collapse', { defaultState: 'collapsed' });
                                                    }}
                                                >
                                                    {__('Eingeklappt', 'container-block-designer')}
                                                </Button>
                                            </ButtonGroup>
                                        </BaseControl>
                                    </Fragment>
                                )}
                            </BaseControl>
                            
                            {/* Numbering Feature */}
                            <BaseControl label={__('Nummerierung', 'container-block-designer')}>
                                <ToggleControl
                                    checked={features.numbering.enabled}
                                    onChange={(enabled) => updateFeature('numbering', { enabled })}
                                />
                                {features.numbering.enabled && (
                                    <SelectControl
                                        label={__('Nummerierungs-Format', 'container-block-designer')}
                                        value={features.numbering.format}
                                        options={[
                                            { label: __('Numerisch (1, 2, 3)', 'container-block-designer'), value: 'numeric' },
                                            { label: __('Alphabetisch (A, B, C)', 'container-block-designer'), value: 'alpha' },
                                            { label: __('Römisch (I, II, III)', 'container-block-designer'), value: 'roman' }
                                        ]}
                                        onChange={(value) => updateFeature('numbering', { format: value })}
                                    />
                                )}
                            </BaseControl>
                            
                            {/* Copy Text Feature */}
                            <BaseControl label={__('Text kopieren', 'container-block-designer')}>
                                <ToggleControl
                                    checked={features.copyText.enabled}
                                    onChange={(enabled) => updateFeature('copyText', { enabled })}
                                />
                                {features.copyText.enabled && (
                                    <TextControl
                                        label={__('Button-Text', 'container-block-designer')}
                                        value={features.copyText.buttonText}
                                        onChange={(buttonText) => updateFeature('copyText', { buttonText })}
                                    />
                                )}
                            </BaseControl>
                            
                            {/* Screenshot Feature */}
                            <BaseControl label={__('Screenshot', 'container-block-designer')}>
                                <ToggleControl
                                    checked={features.screenshot.enabled}
                                    onChange={(enabled) => updateFeature('screenshot', { enabled })}
                                />
                                {features.screenshot.enabled && (
                                    <TextControl
                                        label={__('Button-Text', 'container-block-designer')}
                                        value={features.screenshot.buttonText}
                                        onChange={(buttonText) => updateFeature('screenshot', { buttonText })}
                                    />
                                )}
                            </BaseControl>
                            
                        </PanelBody>
                    </InspectorControls>
                    
                    <div {...blockProps}>
                        {/* Icon */}
                        {features.icon.enabled && (
                            <div className="cbd-container-icon">
                                <span className={`dashicons ${features.icon.value}`}></span>
                            </div>
                        )}
                        
                        {/* Collapse Header (nur wenn Feature aktiv) */}
                        {features.collapse.enabled && (
                            <div className="cbd-collapse-header" onClick={toggleEditorCollapse}>
                                <span className={`dashicons dashicons-arrow-${isCollapsedInEditor ? 'right' : 'down'}-alt2`}></span>
                                <span className="cbd-collapse-title">
                                    {__('Container Inhalt', 'container-block-designer')}
                                </span>
                                {/* Action Buttons im Collapse Header (nur bei collapsed) */}
                                {isCollapsedInEditor && (
                                    <div className="cbd-collapse-buttons">
                                        {features.copyText.enabled && (
                                            <Button 
                                                variant="secondary" 
                                                size="small" 
                                                icon="clipboard"
                                                disabled
                                                className="cbd-preview-button"
                                            />
                                        )}
                                        {features.screenshot.enabled && (
                                            <Button 
                                                variant="secondary" 
                                                size="small" 
                                                icon="camera"
                                                disabled
                                                className="cbd-preview-button"
                                            />
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                        
                        {/* Content Area (nur anzeigen wenn nicht collapsed oder collapse deaktiviert) */}
                        {(!features.collapse.enabled || !isCollapsedInEditor) && (
                            <div className="cbd-container-content">
                                <InnerBlocks
                                    allowedBlocks={true}
                                    template={[
                                        ['core/paragraph', {
                                            placeholder: __('Fügen Sie hier Ihren Inhalt ein...', 'container-block-designer')
                                        }]
                                    ]}
                                />
                                
                                {/* Feature Buttons (nur bei nicht-collapsed) */}
                                {renderFeatureButtons()}
                            </div>
                        )}
                    </div>
                </Fragment>
            );
        },
        
        save: function(props) {
            const { attributes } = props;
            const { selectedBlock, customClasses, features } = attributes;
            
            // Container classes
            const containerClasses = [
                'cbd-container',
                selectedBlock ? `cbd-container-${selectedBlock}` : '',
                customClasses
            ].filter(Boolean).join(' ');
            
            // Data attributes für Features
            const dataAttributes = {};
            
            if (features.icon.enabled) {
                dataAttributes['data-icon'] = 'true';
                dataAttributes['data-icon-value'] = features.icon.value;
            }
            
            if (features.collapse.enabled) {
                dataAttributes['data-collapse'] = 'true';
                dataAttributes['data-collapse-default'] = features.collapse.defaultState;
            }
            
            if (features.numbering.enabled) {
                dataAttributes['data-numbering'] = 'true';
                dataAttributes['data-numbering-format'] = features.numbering.format;
            }
            
            if (features.copyText.enabled) {
                dataAttributes['data-copy'] = 'true';
                dataAttributes['data-copy-text'] = features.copyText.buttonText;
            }
            
            if (features.screenshot.enabled) {
                dataAttributes['data-screenshot'] = 'true';
                dataAttributes['data-screenshot-text'] = features.screenshot.buttonText;
            }
            
            const blockProps = useBlockProps.save({
                className: containerClasses,
                ...dataAttributes
            });
            
            return (
                <div {...blockProps}>
                    <InnerBlocks.Content />
                </div>
            );
        }
    });
    
})(window.wp);