var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    innerBlockTemplate = [],
    displayType = 'table',
    showQuantity = '',
    __ = wp.i18n.__,
    supports_args;

if ( tc_event_add_to_cart_group_block_editor.since_611 ) {
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
    ToggleControl = wp.components.ToggleControl,
    BaseControl = wp.components.BaseControl;

/**
 * Parent Group
 * Event Add To Cart
 */
registerBlockType( 'tickera/event-add-to-cart-group', {
    title: __( 'Event - Add to Cart', 'tickera-event-ticketing-system' ),
    description: __( 'Event Tickets Add to Cart table', 'tickera-event-ticketing-system' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera', 'tickera-event-ticketing-system' ),
        __( 'Event', 'tickera-event-ticketing-system' ),
        __( 'Add', 'tickera-event-ticketing-system' ),
        __( 'Cart', 'tickera-event-ticketing-system' )
    ],
    supports: { html: false },
    attributes: {
        event_id: {
            type: 'string',
        },
        display_type: {
            type: 'string',
            default: 'table'
        },
        quantity: {
            type: 'boolean',
            default: false,
        },
        button_title: {
            type: 'string',
            default: __( 'Add to Cart', 'tickera-event-ticketing-system' )
        },
        link_type: {
            type: 'string',
            default: 'cart'
        },
        show_event_title: {
            type: 'boolean',
            default: false
        },
        show_price: {
            type: 'boolean',
            default: false
        },
        ticket_type_title: {
            type: 'string',
            default: __( 'Ticket Type', 'tickera-event-ticketing-system' )
        },
        price_title: {
            type: 'string',
            default: __( 'Price', 'tickera-event-ticketing-system' )
        },
        cart_title: {
            type: 'string',
            default: __( 'Cart', 'tickera-event-ticketing-system' )
        },
        quantity_title: {
            type: 'string',
            default: __( 'QTY', 'tickera-event-ticketing-system' )
        },
        soldout_message: {
            type: 'string',
            default: __( 'Tickets are sold out.', 'tickera-event-ticketing-system' )
        }
    },
    providesContext: {
        'tickera/event_id': 'event_id',
        'tickera/display_type': 'display_type',
        'tickera/quantity': 'quantity',
        'tickera/button_title': 'button_title',
        'tickera/link_type': 'link_type',
        'tickera/show_event_title': 'show_event_title',
        'tickera/show_price': 'show_price',
        'tickera/ticket_type_title': 'ticket_type_title',
        'tickera/price_title': 'price_title',
        'tickera/cart_title': 'cart_title',
        'tickera/quantity_title': 'quantity_title',
        'tickera/soldout_message': 'soldout_message',
    },
    edit: function( props ) {

        displayType = props.attributes.display_type;
        showQuantity = props.attributes.quantity;

        let blockProps = UseBlockProps( { className: 'wp-block-tc-event-add-to-cart-group' } ),
            innerBlocksProps = UseInnerBlocksProps( blockProps, {
                template: [
                    [ 'tickera/event-add-to-cart-columns' ],
                    [ 'tickera/event-add-to-cart-rows' ]
                ],
                templateLock: true,
                orientation: 'vertical'
            });

        var events = jQuery.parseJSON( tc_event_add_to_cart_group_block_editor.events );

        /**
         * Disable Event Selection on Current Event.
         * @since 3.5.1.8
         */
        if ( typeof events == 'number' ) {
            var eventControl = {
                'type': BaseControl,
                'attributes': {
                    label: __( 'Event: Current Event', 'tickera-event-ticketing-system' ),
                    className: 'tc-gb-component current-event'
                }
            };

        } else {
            var event_ids = [];
            events.forEach( function( entry ) { event_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } ); } );

            var eventControl = {
                'type': SelectControl,
                'attributes': {
                    label: __( 'Event', 'tickera-event-ticketing-system' ),
                    className: 'tc-gb-component',
                    value: props.attributes.event_id,
                    onChange: function change_val( value ) {
                        return props.setAttributes( { event_id: value } );
                    },
                    options: event_ids
                }
            };
        }

        return [
            el(
                InspectorControls,
                { key: 'controls' },
                el(
                    eventControl.type,
                    eventControl.attributes
                ),
                el(
                    SelectControl,
                    {
                        label: __( 'Display Type', 'tickera-event-ticketing-system' ),
                        className: 'tc-gb-component',
                        value: props.attributes.display_type,
                        onChange: function change_val( value ) {
                            displayType = value;
                            return props.setAttributes( { display_type: value } );
                        },
                        options: [
                            { value: 'table', label: __( 'Table (Default)', 'tickera-event-ticketing-system' ) },
                            { value: 'dropdown', label: __( 'Dropdown', 'tickera-event-ticketing-system' ) },
                        ]
                    }
                ),
                el(
                    ToggleControl,
                    {
                        label: __( 'Show Event Title', 'tickera-event-ticketing-system' ),
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
                        label: __( 'Show Price', 'tickera-event-ticketing-system' ),
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
                        label: __( 'Show Quantity Column', 'tickera-event-ticketing-system' ),
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
                        label: __( 'Add to Cart button title', 'tickera-event-ticketing-system' ),
                        className: 'tc-gb-component',
                        help: __( 'Title of the Add to Cart button', 'tickera-event-ticketing-system' ),
                        value: props.attributes.button_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { button_title: value } );
                        },
                    }
                ),
                el(
                    SelectControl,
                    {
                        label: __( 'Button Type', 'tickera-event-ticketing-system' ),
                        className: 'tc-gb-component',
                        value: props.attributes.link_type,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { link_type: value } );
                        },
                        options: [
                            { value: 'cart', label: __( 'Cart', 'tickera-event-ticketing-system' ) },
                            { value: 'buynow', label: __( 'Buy Now', 'tickera-event-ticketing-system' ) },
                        ]
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Ticket Type Column Title', 'tickera-event-ticketing-system' ),
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
                        label: __( 'Price Column Title', 'tickera-event-ticketing-system' ),
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
                        label: __( 'Cart Column Title', 'tickera-event-ticketing-system' ),
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
                        label: __( 'Quantity Column Title', 'tickera-event-ticketing-system' ),
                        className: 'tc-gb-component show-in-table show-quantity ' + displayType + ' ' + showQuantity,
                        value: props.attributes.quantity_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { quantity_title: value } );
                        },
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Soldout Message', 'tickera-event-ticketing-system' ),
                        className: 'tc-gb-component',
                        help: __( 'The message which will be shown when all tickets are sold', 'tickera-event-ticketing-system' ),
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
    },
} );

/**
 * Inner Child
 * Event Table Columns
 */
supports_args = {
    html: false,
    spacing: {
        padding: true,
        margin: false,
        __experimentalDefaultControls: {
            padding: true,
            margin: false
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

registerBlockType( 'tickera/event-add-to-cart-columns', {
    title: __( 'Event - Add To Cart Columns', 'tickera-event-ticketing-system' ),
    description: __( 'Event add to cart table column names', 'tickera-event-ticketing-system' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [],
    supports: supports_args,
    parent: [ 'tickera/event-add-to-cart-group' ],
    usesContext: [
        'tickera/event_id',
        'tickera/display_type',
        'tickera/quantity',
        'tickera/button_title',
        'tickera/link_type',
        'tickera/show_event_title',
        'tickera/show_price',
        'tickera/ticket_type_title',
        'tickera/price_title',
        'tickera/cart_title',
        'tickera/quantity_title',
        'tickera/soldout_message'
    ],
    edit: function( props ) {

        const { context } = props;

        setTimeout( function() {
            props.setAttributes( {
                event_id: ( typeof context[ 'tickera/event_id' ] !== 'undefined' ) ? context[ 'tickera/event_id' ] : '',
                display_type: ( typeof context[ 'tickera/display_type' ] !== 'undefined' ) ? context[ 'tickera/display_type' ] : '',
                quantity: ( typeof context[ 'tickera/quantity' ] !== 'undefined' ) ? context[ 'tickera/quantity' ] : false,
                button_title: ( typeof context[ 'tickera/button_title' ] !== 'undefined' ) ? context[ 'tickera/button_title' ] : '',
                link_type: ( typeof context[ 'tickera/link_type' ] !== 'undefined' ) ? context[ 'tickera/link_type' ] : '',
                show_event_title: ( typeof context[ 'tickera/show_event_title' ] !== 'undefined' ) ? context[ 'tickera/show_event_title' ] : false,
                show_price: ( typeof context[ 'tickera/show_price' ] !== 'undefined' ) ? context[ 'tickera/show_price' ] : false,
                ticket_type_title: ( typeof context[ 'tickera/ticket_type_title' ] !== 'undefined' ) ? context[ 'tickera/ticket_type_title' ] : '',
                price_title: ( typeof context[ 'tickera/price_title' ] !== 'undefined' ) ? context[ 'tickera/price_title' ] : '',
                cart_title: ( typeof context[ 'tickera/cart_title' ] !== 'undefined' ) ? context[ 'tickera/cart_title' ] : '',
                quantity_title: ( typeof context[ 'tickera/quantity_title' ] !== 'undefined' ) ? context[ 'tickera/quantity_title' ] : '',
                soldout_message: ( typeof context[ 'tickera/soldout_message' ] !== 'undefined' ) ? context[ 'tickera/soldout_message' ] : ''
            });
        }, 1000 )

        return el( ServerSideRender, {
            block: 'tickera/event-add-to-cart-columns',
            attributes: props.attributes
        } );
    },
    save: function() {
        return null;
    }
} );

/**
 * Inner Child
 * Event Table Rows
 */
supports_args = {
    html: false,
    spacing: {
        padding: true,
        margin: false,
        __experimentalDefaultControls: {
            padding: true,
            margin: false
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

registerBlockType( 'tickera/event-add-to-cart-rows', {
    title: __( 'Event - Add To Cart Values', 'tickera-event-ticketing-system' ),
    description: __( 'Event add to cart table values', 'tickera-event-ticketing-system' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [],
    supports: supports_args,
    parent: [ 'tickera/event-add-to-cart-group' ],
    usesContext: [
        'tickera/event_id',
        'tickera/display_type',
        'tickera/quantity',
        'tickera/link_type',
        'tickera/button_title',
        'tickera/soldout_message'
    ],
    edit: function( props ) {

        const { context } = props;

        setTimeout( function() {
            props.setAttributes( {
                event_id: ( typeof context[ 'tickera/event_id' ] !== 'undefined' ) ? context[ 'tickera/event_id' ] : '',
                display_type: ( typeof context[ 'tickera/display_type' ] !== 'undefined' ) ? context[ 'tickera/display_type' ] : '',
                quantity: ( typeof context[ 'tickera/quantity' ] !== 'undefined' ) ? context[ 'tickera/quantity' ] : false,
                link_type: ( typeof context[ 'tickera/link_type' ] !== 'undefined' ) ? context[ 'tickera/link_type' ] : '',
                button_title: ( typeof context[ 'tickera/button_title' ] !== 'undefined' ) ? context[ 'tickera/button_title' ] : '',
                soldout_message: ( typeof context[ 'tickera/soldout_message' ] !== 'undefined' ) ? context[ 'tickera/soldout_message' ] : '',
            });
        }, 1000 )

        return el( ServerSideRender, {
            block: 'tickera/event-add-to-cart-rows',
            attributes: props.attributes
        } );
    },
    save: function() {
        return null;
    }
} );

/**
 * Event Add To Cart Table
 * Backward Compatibility
 */
registerBlockType( 'tickera/event-add-to-cart', {
    title: __( 'Event - Add to Cart', 'tickera-event-ticketing-system' ),
    description: __( 'Event Tickets Add to Cart table', 'tickera-event-ticketing-system' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera', 'tickera-event-ticketing-system' ),
        __( 'Event', 'tickera-event-ticketing-system' ),
        __( 'Cart', 'tickera-event-ticketing-system' )
    ],
    supports: { html: false },
    parent: [ 'tickera/event-add-to-cart-group' ],
    edit: function( props ) {

        return el( ServerSideRender, {
            block: "tickera/event-add-to-cart",
            attributes: props.attributes
        } );
    },
    save: function( props ) {
        return null;
    },
} );
