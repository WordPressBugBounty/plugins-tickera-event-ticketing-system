var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    BlockControls = wp.editor.BlockControls;

if ( tc_woo_add_to_cart_block_editor.since_611 ) {
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

if ( tc_woo_add_to_cart_block_editor.tc_dev ) {
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
    attributes: {
        id: {
            type: 'string',
        },
        show_price: {
            type: 'boolean',
            default: false,
        },
    },
    edit: function( props ) {

        var ticket_types = jQuery.parseJSON( tc_woo_add_to_cart_block_editor.ticket_types ),
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
                        label: __( 'Ticket Type (product)' ),
                        className: 'tc-gb-component',
                        value: props.attributes.id,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { id: value } );
                        },
                        options: ticket_ids
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
            ),
            el( ServerSideRender, {
                block: "tickera/woo-add-to-cart",
                attributes: props.attributes
            } )
        ];
    },
    save: function( props ) {
        return null;
    },
} );
