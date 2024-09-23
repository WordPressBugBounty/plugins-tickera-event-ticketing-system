var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    __ = wp.i18n.__,
    supports_args;

if ( tc_woo_add_to_cart_group_block_editor.since_611 ) {
    var ServerSideRender = wp.serverSideRender,
        InspectorControls = wp.blockEditor.InspectorControls,
        InnerBlocks = wp.blockEditor.InnerBlocks,
        UseBlockProps = wp.blockEditor.useBlockProps,
        UseInnerBlocksProps = wp.blockEditor.useInnerBlocksProps;

} else {
    var ServerSideRender = wp.components.ServerSideRender,
        InspectorControls = wp.editor.InspectorControls,
        InnerBlocks = wp.editor.InnerBlocks,
        UseBlockProps = wp.editor.useBlockProps,
        UseInnerBlocksProps = wp.editor.useInnerBlocksProps;
}

var AlignmentToolbar = wp.editor.AlignmentToolbar,
    RichText = wp.editor.RichText,
    SelectControl = wp.components.SelectControl,
    RangeControl = wp.components.RangeControl,
    TextControl = wp.components.TextControl,
    ToggleControl = wp.components.ToggleControl;

/**
 * Parent Group
 * Add to Cart
 */
registerBlockType( 'tickera/woo-add-to-cart-group', {
    title: __( 'Ticket - Add to Cart' ),
    description: __( 'Woo Ticket Add to Cart button' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Event' ),
        __( 'Add' ),
        __( 'Cart' ),
        __( 'WooCommerce' )
    ],
    supports: { html: false },
    attributes: {
        id: {
            type: 'string'
        },
        show_price: {
            type: 'boolean',
            default: false
        },
        quantity: {
            type: 'boolean',
            default: false
        }
    },
    providesContext: {
        'tickera/woo_ticket_type_id': 'id',
        'tickera/woo_show_price': 'show_price',
        'tickera/woo_quantity': 'quantity'
    },
    edit: function( props ) {

        let blockProps = UseBlockProps( { className: 'wp-block-tc-woo-add-to-cart-group' } ),
            innerBlocksProps = UseInnerBlocksProps( blockProps, {
                template: [
                    [ 'tickera/woo-add-to-cart' ],
                    [ 'tickera/woo-ticket-price' ],
                ],
                templateLock: true,
                orientation: 'horizontal'
            });

        var ticket_types = jQuery.parseJSON( tc_woo_add_to_cart_group_block_editor.ticket_types ),
            ticket_ids = [];

        ticket_types.forEach( function( entry ) {
            ticket_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } );
        } );

        return [
            el (
                InspectorControls,
                { key: 'controls' },
                el(
                    SelectControl,
                    {
                        label: __( 'Ticket Type (Product)' ),
                        className: 'tc-gb-component',
                        value: props.attributes.id,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { id: value } );
                        },
                        options: ticket_ids,
                    }
                ),
                el(
                    ToggleControl,
                    {
                        label: __( 'Show Price' ),
                        className: 'tc-gb-component',
                        checked: props.attributes.show_price,
                        value: props.attributes.show_price,
                        onChange: function onChange( value ) {
                            return props.setAttributes( { show_price: value } );
                        },
                    }
                ),
                el(
                    ToggleControl,
                    {
                        label: __( 'Show Quantity' ),
                        className: 'tc-gb-component',
                        checked: props.attributes.quantity,
                        value: props.attributes.quantity,
                        onChange: function onChange( value ) {
                            return props.setAttributes( { quantity: value } );
                        },
                    }
                ),
            ),
            el ( 'div', innerBlocksProps )
        ];
    },
    save: function() {
        return el( InnerBlocks.Content )
    }
} );

/**
 * Inner Child
 * Add To Cart
 */
supports_args = {
    html: false,
    spacing: {
        padding: true,
        margin: true,
        __experimentalDefaultControls: {
            padding: true,
            margin: true
        }
    },
    color: {
        gradients: true,
        link: true,
        text: true,
        __experimentalSkipSerialization: true,
        __experimentalDefaultControls: {
            background: true,
            gradients: true,
            link: true,
            text: true
        }
    },
    __experimentalBorder: {
        width: true,
        style: true,
        color: true,
        radius: true,
        __experimentalSkipSerialization: true,
        __experimentalDefaultControls: {
            width: true,
            style: true,
            color: true,
            radius: true,
        }
    },
    typography: {
        fontSize: true,
        lineHeight: true,
        __experimentalFontFamily: true,
        __experimentalFontWeight: true,
        __experimentalFontStyle: true,
        __experimentalTextTransform: true,
        __experimentalTextDecoration: true,
        __experimentalLetterSpacing: true,
        __experimentalDefaultControls: {
            fontSize: true
        }
    }
};

registerBlockType( 'tickera/woo-add-to-cart', {
    title: __( 'Ticket Add to Cart' ),
    description: __( 'Woo Ticket Add to Cart button' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Add' ),
        __( 'Cart' ),
        __( 'WooCommerce' ),
    ],
    supports: supports_args,
    parent: [ 'tickera/woo-add-to-cart-group' ],
    usesContext: [
        'tickera/woo_ticket_type_id',
        'tickera/woo_quantity',
    ],
    edit: function( props ) {

        const { context } = props;

        setTimeout( function() {
            props.setAttributes( {
                id: ( typeof context[ 'tickera/woo_ticket_type_id' ] !== 'undefined' ) ? context[ 'tickera/woo_ticket_type_id' ] : ( ( typeof props.attributes.id !== 'undefined' ) ? props.attributes.id : '' ),
                quantity: ( typeof context[ 'tickera/woo_quantity' ] !== 'undefined' ) ? context[ 'tickera/woo_quantity' ] : ( ( typeof props.attributes.quantity !== 'undefined' ) ? props.attributes.quantity : false ),
            });
        }, 1000 )

        return el( ServerSideRender, {
            block: 'tickera/woo-add-to-cart',
            attributes: props.attributes
        } );
    },
    save: function() {
        return null;
    },
} );

/**
 * Inner Child
 * Ticket Price
 */
supports_args = {
    html: false,
    spacing: {
        padding: true,
        margin: true,
        __experimentalDefaultControls: {
            padding: true,
            margin: true
        }
    },
    color: {
        background: true,
        gradients: true,
        link: false,
        text: true,
        __experimentalSkipSerialization: true,
        __experimentalDefaultControls: {
            background: true,
            gradients: true,
            link: false,
            text: true
        }
    },
    __experimentalBorder: {
        width: true,
        style: true,
        color: true,
        radius: true,
        __experimentalSkipSerialization: true,
        __experimentalDefaultControls: {
            width: true,
            style: true,
            color: true,
            radius: true,
        }
    },
    typography: {
        fontSize: true,
        lineHeight: true,
        __experimentalFontFamily: true,
        __experimentalFontWeight: true,
        __experimentalFontStyle: true,
        __experimentalTextTransform: true,
        __experimentalTextDecoration: true,
        __experimentalLetterSpacing: true,
        __experimentalDefaultControls: {
            fontSize: true
        }
    }
};

registerBlockType( 'tickera/woo-ticket-price', {
    title: __( 'Ticket Price' ),
    description: __( 'Ticket Price Label' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Ticket' ),
        __( 'Price' ),
        __( 'Cart' )
    ],
    supports: supports_args,
    parent: [ 'tickera/woo-add-to-cart-group' ],
    usesContext: [
        'tickera/woo_ticket_type_id',
        'tickera/woo_show_price'
    ],
    edit: function( props ) {

        const { context } = props;

        let id = ( typeof context[ 'tickera/woo_ticket_type_id' ] !== 'undefined' ) ? context[ 'tickera/woo_ticket_type_id' ] : '',
            show_price = ( typeof context[ 'tickera/woo_show_price' ] !== 'undefined' ) ? context[ 'tickera/woo_show_price' ] : false,
            ticket_type_id = ( show_price ) ? id : '';

        setTimeout( function() {
            props.setAttributes( { id: ticket_type_id } );
        }, 1000 )

        return el( ServerSideRender, {
            block: 'tickera/woo-ticket-price',
            attributes: props.attributes
        } );
    },
    save: function() {
        return null;
    }
} );
