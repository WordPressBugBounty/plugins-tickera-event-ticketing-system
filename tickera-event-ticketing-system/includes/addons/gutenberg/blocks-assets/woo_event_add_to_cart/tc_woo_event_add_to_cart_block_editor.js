var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    BlockControls = wp.editor.BlockControls;

if ( tc_woo_event_add_to_cart_block_editor.since_611 ) {
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

var __ = wp.i18n.__,
    displayType = 'table',
    showQuantity = '';

if ( tc_woo_event_add_to_cart_block_editor.tc_dev ) {
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

registerBlockType( 'tickera/woo-event-add-to-cart', {
    title: __( 'Event - Add to Cart' ),
    description: __( 'Event Tickets (products) Add to Cart table' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Event' ),
        __( 'WooCommerce' ),
    ],
    supports: supports_args,
    attributes: {
        id: {
            type: 'string',
        },
        display_type: {
            type: 'string',
            default: 'table'
        },
        show_event_title: {
            type: 'boolean',
            default: false
        },
        show_price: {
            type: 'boolean',
            default: false
        },
        quantity: {
            type: 'boolean',
            default: false,
        },
        ticket_type_title: {
            type: 'string',
            default: __( 'Ticket Type' )
        },
        price_title: {
            type: 'string',
            default: __( 'Price' )
        },
        cart_title: {
            type: 'string',
            default: __( 'Cart' )
        },
        quantity_title: {
            type: 'string',
            default: __( 'Quantity' )
        },
    },
    edit: function( props ) {

        displayType = props.attributes.display_type;
        showQuantity = props.attributes.quantity;

        var events = jQuery.parseJSON( tc_woo_event_add_to_cart_block_editor.events ),
            event_ids = [];

        events.forEach( function( entry ) {
            event_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } );
        } );

        return [
            el(
                InspectorControls,
                { key: 'controls' },
                el(
                    SelectControl,
                    {
                        label: __( 'Event' ),
                        className: 'tc-gb-component',
                        value: props.attributes.id,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { id: value } );
                        },
                        options: event_ids
                    }
                ),
                el(
                    SelectControl,
                    {
                        label: __( 'Display Type' ),
                        className: 'tc-gb-component',
                        value: props.attributes.display_type,
                        onChange: function change_val( value ) {
                            displayType = value;
                            return props.setAttributes( { display_type: value } );
                        },
                        options: [
                            { value: 'table', label: __( 'Table (Default)' ) },
                            { value: 'dropdown', label: __( 'Dropdown' ) },
                        ]
                    }
                ),
                el(
                    ToggleControl,
                    {
                        label: __( 'Show Event Title' ),
                        className: 'tc-gb-component show-in-dropdown ' + displayType,
                        checked: props.attributes.show_event_title,
                        value: props.attributes.show_event_title,
                        onChange: function onChange( value ) {
                            return props.setAttributes( { show_event_title: value } );
                        },
                    }
                ),
                el(
                    ToggleControl,
                    {
                        label: __( 'Show Price' ),
                        className: 'tc-gb-component show-in-dropdown ' + displayType,
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
                        label: __( 'Show Quantity Column' ),
                        className: 'tc-gb-component',
                        checked: props.attributes.quantity,
                        value: props.attributes.quantity,
                        onChange: function onChange( value ) {
                            showQuantity = value;
                            return props.setAttributes( { quantity: value } );
                        },
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Ticket Type Column Title' ),
                        className: 'tc-gb-component show-in-table ' + displayType,
                        value: props.attributes.ticket_type_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { ticket_type_title: value } );
                        },
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Price Column Title' ),
                        className: 'tc-gb-component show-in-table ' + displayType,
                        value: props.attributes.price_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { price_title: value } );
                        },
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Cart Column Title' ),
                        className: 'tc-gb-component show-in-table ' + displayType,
                        value: props.attributes.cart_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { cart_title: value } );
                        },
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Quantity Column Title' ),
                        className: 'tc-gb-component show-in-table show-quantity ' + displayType + ' ' + showQuantity,
                        value: props.attributes.quantity_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { quantity_title: value } );
                        },
                    }
                ),
            ),
            el( ServerSideRender, {
                block: "tickera/woo-event-add-to-cart",
                attributes: props.attributes
            } )
        ];
    },
    save: function( props ) {
        return null;
    },
} );
