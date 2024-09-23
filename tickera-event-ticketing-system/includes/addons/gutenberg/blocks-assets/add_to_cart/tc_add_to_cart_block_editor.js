var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    BlockControls = wp.editor.BlockControls;

if ( tc_add_to_cart_block_editor.since_611 ) {
    var InspectorControls = wp.blockEditor.InspectorControls,
        ServerSideRender = wp.serverSideRender;

} else {
    var InspectorControls = wp.editor.InspectorControls,
        ServerSideRender = wp.components.ServerSideRender;
}

var AlignmentToolbar = wp.editor.AlignmentToolbar,
    RichText = wp.editor.RichText,
    SelectControl = wp.components.SelectControl,
    RangeControl = wp.components.RangeControl,
    TextControl = wp.components.TextControl,
    ToggleControl = wp.components.ToggleControl;

var __ = wp.i18n.__;

if ( tc_add_to_cart_block_editor.tc_dev ) {
    var supports_args = {
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

} else {
    var supports_args = { html: false };
}

registerBlockType( 'tickera/add-to-cart', {
    title: __( 'Ticket Add to Cart' ),
    description: __( 'Ticket Add to Cart button' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Add' ),
        __( 'Cart' ),
        __( 'Add' )
    ],
    supports: supports_args,
    attributes: {
        ticket_type_id: {
            type: 'string',
        },

        soldout_message: {
            type: 'string',
            default: __( 'Tickets are sold out.' )
        },
        show_price: {
            type: 'boolean',
            default: false,
        },
        price_position: {
            type: 'string',
            default: 'after',
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
    edit: function( props ) {

        var ticket_types = jQuery.parseJSON( tc_add_to_cart_block_editor.ticket_types ),
            ticket_ids = [];

        ticket_types.forEach( function( entry ) {
            ticket_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } );
        } );

        return [
            el(
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
                    SelectControl,
                    {
                        label: __( 'Price Position' ),
                        className: 'tc-gb-component',
                        value: props.attributes.price_position,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { price_position: value } );
                        },
                        options: [
                            { value: 'before', label: __( 'Before' ) },
                            { value: 'after', label: __( 'After' ) },
                        ],
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

            el( ServerSideRender, {
                block: "tickera/add-to-cart",
                attributes: props.attributes
            } )
        ];
    },
    save: function( props ) {

        return null;

        var content = props.attributes.content,
            alignment = props.attributes.alignment,
            columns = props.attributes.columns;

        return el( RichText.Content, {
            className: props.className,
            style: { textAlign: alignment },
            value: content
        } );
    },
} );
