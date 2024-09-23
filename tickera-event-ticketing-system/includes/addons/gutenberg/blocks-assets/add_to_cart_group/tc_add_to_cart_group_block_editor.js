var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    __ = wp.i18n.__,
    supports_args;

if ( tc_add_to_cart_group_block_editor.since_611 ) {
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
registerBlockType( 'tickera/add-to-cart-group', {
    title: __( 'Ticket - Add to Cart' ),
    description: __( 'Tickets Add to Cart' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Event' ),
        __( 'Add' ),
        __( 'Cart' )
    ],
    supports: { html: false },
    attributes: {
        ticket_type_id: {
            type: 'string',
        },
        show_price: {
            type: 'boolean',
            default: false
        },
        soldout_message: {
            type: 'string',
            default: __( 'Tickets are sold out.' )
        },
        quantity: {
            type: 'boolean',
            default: false,
        },
        link_type: {
            type: 'string',
            default: 'cart'
        }
    },
    providesContext: {
        'tickera/ticket_type_id': 'ticket_type_id',
        'tickera/soldout_message': 'soldout_message',
        'tickera/show_price': 'show_price',
        'tickera/quantity': 'quantity',
        'tickera/link_type': 'link_type'
    },
    edit: function( props ) {

        let blockProps = UseBlockProps( { className: 'wp-block-tc-add-to-cart-group' } ),
            innerBlocksProps = UseInnerBlocksProps( blockProps, {
                template: [
                    [ 'tickera/add-to-cart' ],
                    [ 'tickera/ticket-price' ],
                ],
                templateLock: true,
                orientation: 'horizontal'
            });

        var ticket_types = jQuery.parseJSON( tc_add_to_cart_group_block_editor.ticket_types ),
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
                        label: __( 'Ticket Type' ),
                        className: 'tc-gb-component',
                        value: props.attributes.ticket_type_id,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { ticket_type_id: value } );
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
                el(
                    SelectControl,
                    {
                        label: __( 'Button Type' ),
                        className: 'tc-gb-component',
                        value: props.attributes.link_type,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { link_type: value } );
                        },
                        options: [
                            { value: 'cart', label: __( 'Cart' ) },
                            { value: 'buynow', label: __( 'Buy Now' ) },
                        ],
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Soldout Message' ),
                        className: 'tc-gb-component',
                        help: __( 'The message which will be shown when all tickets are sold' ),
                        value: props.attributes.soldout_message,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { soldout_message: value } );
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
        background: true,
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

registerBlockType( 'tickera/add-to-cart', {
    title: __( 'Ticket - Add to Cart' ),
    description: __( 'Ticket Add to Cart button' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Add' ),
        __( 'Cart' )
    ],
    supports: supports_args,
    parent: [ 'tickera/add-to-cart-group' ],
    usesContext: [
        'tickera/ticket_type_id',
        'tickera/soldout_message',
        'tickera/quantity',
        'tickera/link_type'
    ],
    edit: function( props ) {

        const { context } = props;

        setTimeout( function() {
            props.setAttributes( {
                ticket_type_id: ( typeof context[ 'tickera/ticket_type_id' ] !== 'undefined' ) ? context[ 'tickera/ticket_type_id' ] : ( ( typeof props.attributes.ticket_type_id !== 'undefined' ) ? props.attributes.ticket_type_id : '' ),
                soldout_message: ( typeof context[ 'tickera/soldout_message' ] !== 'undefined' ) ? context[ 'tickera/soldout_message' ] : ( ( typeof props.attributes.soldout_message !== 'undefined' ) ? props.attributes.soldout_message : __( 'Tickets are sold out.' ) ),
                quantity: ( typeof context[ 'tickera/quantity' ] !== 'undefined' ) ? context[ 'tickera/quantity' ] : ( ( typeof props.attributes.quantity !== 'undefined' ) ? props.attributes.quantity : false ),
                link_type: ( typeof context[ 'tickera/link_type' ] !== 'undefined' ) ? context[ 'tickera/link_type' ] : ( ( typeof props.attributes.link_type !== 'undefined' ) ? props.attributes.link_type : 'cart' ),
            });
        }, 1000 )

        return el( ServerSideRender, {
            block: 'tickera/add-to-cart',
            attributes: props.attributes
        } );
    },
    save: function() {
        return null;
    }
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

registerBlockType( 'tickera/ticket-price', {
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
    parent: [ 'tickera/add-to-cart-group' ],
    usesContext: [
        'tickera/ticket_type_id',
        'tickera/show_price'
    ],
    edit: function( props ) {

        const { context } = props;

        let id = ( typeof context[ 'tickera/ticket_type_id' ] !== 'undefined' ) ? context[ 'tickera/ticket_type_id' ] : '',
            show_price = ( typeof context[ 'tickera/show_price' ] !== 'undefined' ) ? context[ 'tickera/show_price' ] : false,
            ticket_type_id = ( show_price ) ? id : '';

        setTimeout( function() {
            props.setAttributes( { id: ticket_type_id } );
        }, 1000 )

        return el( ServerSideRender, {
            block: 'tickera/ticket-price',
            attributes: props.attributes
        } );
    },
    save: function() {
        return null;
    }
} );
