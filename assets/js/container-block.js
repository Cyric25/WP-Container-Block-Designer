/**
 * Container Block Designer - Block Registration
 * Version: 2.2.0 - Fixed Version
 */

(function(wp) {
    'use strict';
    
    // Check if required dependencies exist
    if (!wp || !wp.blocks || !wp.element || !wp.blockEditor) {
        console.error('CBD: Required WordPress dependencies not found');
        return;
    }
    
    const { registerBlockType } = wp.blocks;
    const { InnerBlocks, InspectorControls, BlockControls } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl, Placeholder, Button } = wp.components;
    const { Fragment, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    
    console.log('CBD: Registering Container Block...');
    
    // Register the block
    registerBlockType('container-block-designer/container', {
        title: __('Container Block', 'container-block-designer'),
        description: __('Ein anpassbarer Container-Block', 'container-block-designer'),
        category: 'design',
        icon: 'layout',
        keywords: ['container', 'wrapper', 'section', 'box'],
        attributes: {
            selectedBlock: {
                type: 'string',
                default: ''
            },
            customClasses: {
                type: 'string',
                default: ''
            }
        },
        supports: {
            align: ['wide', 'full'],
            html: false,
            anchor: true,
            customClassName: true
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { selectedBlock, customClasses } = attributes;
            
            // Get available blocks from cbdData if available
            const availableBlocks = window.cbdData ? window.cbdData.blocks : [];
            
            return wp.element.createElement(
                Fragment,
                {},
                // Inspector Controls (Sidebar)
                wp.element.createElement(
                    InspectorControls,
                    {},
                    wp.element.createElement(
                        PanelBody,
                        { 
                            title: __('Container Einstellungen', 'container-block-designer'),
                            initialOpen: true
                        },
                        wp.element.createElement(
                            SelectControl,
                            {
                                label: __('Container Design', 'container-block-designer'),
                                value: selectedBlock,
                                onChange: function(value) {
                                    setAttributes({ selectedBlock: value });
                                },
                                options: [
                                    { value: '', label: __('-- Wählen --', 'container-block-designer') }
                                ].concat(
                                    availableBlocks.map(function(block) {
                                        return {
                                            value: block.slug,
                                            label: block.name
                                        };
                                    })
                                )
                            }
                        ),
                        wp.element.createElement(
                            TextControl,
                            {
                                label: __('Zusätzliche CSS-Klassen', 'container-block-designer'),
                                value: customClasses,
                                onChange: function(value) {
                                    setAttributes({ customClasses: value });
                                }
                            }
                        )
                    )
                ),
                // Block Content
                wp.element.createElement(
                    'div',
                    { 
                        className: 'cbd-container-wrapper ' + (selectedBlock ? 'cbd-' + selectedBlock : ''),
                        style: {
                            padding: '20px',
                            border: '2px dashed #ddd',
                            borderRadius: '4px',
                            minHeight: '100px'
                        }
                    },
                    !selectedBlock ? 
                        wp.element.createElement(
                            Placeholder,
                            {
                                icon: 'layout',
                                label: __('Container Block', 'container-block-designer'),
                                instructions: __('Wählen Sie ein Container-Design aus den Einstellungen rechts.', 'container-block-designer')
                            },
                            availableBlocks.length === 0 && 
                            wp.element.createElement(
                                'p',
                                { style: { color: '#cc1818' } },
                                __('Keine Container-Designs gefunden. Bitte erstellen Sie zuerst ein Design im Admin-Bereich.', 'container-block-designer')
                            )
                        )
                    : 
                        wp.element.createElement(
                            Fragment,
                            {},
                            wp.element.createElement(
                                'div',
                                { 
                                    style: { 
                                        marginBottom: '10px',
                                        padding: '5px',
                                        background: '#f0f0f0',
                                        fontSize: '12px'
                                    }
                                },
                                'Container: ' + selectedBlock
                            ),
                            wp.element.createElement(InnerBlocks)
                        )
                )
            );
        },
        
        save: function(props) {
            const { selectedBlock, customClasses } = props.attributes;
            
            return wp.element.createElement(
                'div',
                { 
                    className: 'cbd-container' + 
                              (selectedBlock ? ' cbd-' + selectedBlock : '') +
                              (customClasses ? ' ' + customClasses : '')
                },
                wp.element.createElement(InnerBlocks.Content)
            );
        }
    });
    
    console.log('CBD: Container Block registered successfully!');
    
    // Register block category if not exists
    wp.domReady(function() {
        const categories = wp.blocks.getCategories();
        const hasDesignCategory = categories.some(function(cat) {
            return cat.slug === 'design';
        });
        
        if (!hasDesignCategory) {
            wp.blocks.setCategories([
                {
                    slug: 'design',
                    title: __('Design Blocks', 'container-block-designer'),
                    icon: 'layout'
                }
            ].concat(categories));
            console.log('CBD: Design category added');
        }
        
        // Verify registration
        const blockType = wp.blocks.getBlockType('container-block-designer/container');
        if (blockType) {
            console.log('CBD: Block verification successful:', blockType.name);
        } else {
            console.error('CBD: Block verification failed - not registered!');
        }
    });
    
})(window.wp);