var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    innerBlockTemplate = [],
    displayType = 'table',
    showQuantity = '',
    __ = wp.i18n.__,
    supports_args;

if ( tc_woo_event_add_to_cart_group_block_editor.since_611 ) {
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
registerBlockType( 'tickera/woo-event-add-to-cart-group', {
    title: __( 'Event - Add to Cart', 'tc' ),
    description: __( 'Event Tickets (products) Add to Cart table', 'tc' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera', 'tc' ),
        __( 'Event', 'tc' ),
        __( 'Add', 'tc' ),
        __( 'WooCommerce', 'tc' ),
    ],
    supports: { html: false },
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
            default: __( 'Ticket Type', 'tc' )
        },
        price_title: {
            type: 'string',
            default: __( 'Price', 'tc' )
        },
        cart_title: {
            type: 'string',
            default: __( 'Cart', 'tc' )
        },
        quantity_title: {
            type: 'string',
            default: __( 'Quantity', 'tc' )
        }
    },
    providesContext: {
        'tickera/id': 'id',
        'tickera/display_type': 'display_type',
        'tickera/show_event_title': 'show_event_title',
        'tickera/show_price': 'show_price',
        'tickera/quantity': 'quantity',
        'tickera/ticket_type_title': 'ticket_type_title',
        'tickera/price_title': 'price_title',
        'tickera/cart_title': 'cart_title',
        'tickera/quantity_title': 'quantity_title'
    },
    edit: function( props ) {

        displayType = props.attributes.display_type;
        showQuantity = props.attributes.quantity;

        let blockProps = UseBlockProps( { className: 'wp-block-tc-woo-event-add-to-cart-group' } ),
            innerBlocksProps = UseInnerBlocksProps( blockProps, {
                template: [
                    [ 'tickera/woo-event-add-to-cart-columns' ],
                    [ 'tickera/woo-event-add-to-cart-rows' ]
                ],
                templateLock: true,
                orientation: 'vertical'
            });

        var events = jQuery.parseJSON( tc_woo_event_add_to_cart_group_block_editor.events );

        /**
         * Disable Event Selection on Current Event.
         * @since 3.5.1.8
         */
        if ( typeof events == 'number' ) {
            var eventControl = {
                'type': BaseControl,
                'attributes': {
                    label: __( 'Event: Current Event', 'tc' ),
                    className: 'tc-gb-component current-event'
                }
            };

        } else {
            var event_ids = [];
            events.forEach( function( entry ) { event_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } ); } );

            var eventControl = {
                'type': SelectControl,
                'attributes': {
                    label: __( 'Event', 'tc' ),
                    className: 'tc-gb-component',
                    value: props.attributes.id,
                    onChange: function change_val( value ) {
                        return props.setAttributes( { id: value } );
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
                        label: __( 'Display Type', 'tc' ),
                        className: 'tc-gb-component',
                        value: props.attributes.display_type,
                        onChange: function change_val( value ) {
                            displayType = value;
                            return props.setAttributes( { display_type: value } );
                        },
                        options: [
                            { value: 'table', label: __( 'Table (Default)', 'tc' ) },
                            { value: 'dropdown', label: __( 'Dropdown', 'tc' ) },
                        ]
                    }
                ),
                el(
                    ToggleControl,
                    {
                        label: __( 'Show Event Title', 'tc' ),
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
                        label: __( 'Show Price', 'tc' ),
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
                        label: __( 'Show Quantity Column', 'tc' ),
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
                        label: __( 'Ticket Type Column Title', 'tc' ),
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
                        label: __( 'Price Column Title', 'tc' ),
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
                        label: __( 'Cart Column Title', 'tc' ),
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
                        label: __( 'Quantity Column Title', 'tc' ),
                        className: 'tc-gb-component show-in-table show-quantity ' + displayType + ' ' + showQuantity,
                        value: props.attributes.quantity_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { quantity_title: value } );
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

registerBlockType( 'tickera/woo-event-add-to-cart-columns', {
    title: __( 'Event - Add To Cart', 'tc' ),
    description: __( 'Event add to cart table column', 'tc' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [],
    supports: supports_args,
    parent: [ 'tickera/woo-event-add-to-cart-group' ],
    usesContext: [
        'tickera/id',
        'tickera/display_type',
        'tickera/show_event_title',
        'tickera/show_price',
        'tickera/quantity',
        'tickera/ticket_type_title',
        'tickera/price_title',
        'tickera/cart_title',
        'tickera/quantity_title'
    ],
    edit: function( props ) {

        const { context } = props;

        setTimeout( function() {
            props.setAttributes( {
                id: ( typeof context[ 'tickera/id' ] !== 'undefined' ) ? context[ 'tickera/id' ] : '',
                display_type: ( typeof context[ 'tickera/display_type' ] !== 'undefined' ) ? context[ 'tickera/display_type' ] : '',
                show_event_title: ( typeof context[ 'tickera/show_event_title' ] !== 'undefined' ) ? context[ 'tickera/show_event_title' ] : false,
                show_price: ( typeof context[ 'tickera/show_price' ] !== 'undefined' ) ? context[ 'tickera/show_price' ] : false,
                quantity: ( typeof context[ 'tickera/quantity' ] !== 'undefined' ) ? context[ 'tickera/quantity' ] : false,
                ticket_type_title: ( typeof context[ 'tickera/ticket_type_title' ] !== 'undefined' ) ? context[ 'tickera/ticket_type_title' ] : '',
                price_title: ( typeof context[ 'tickera/price_title' ] !== 'undefined' ) ? context[ 'tickera/price_title' ] : '',
                cart_title: ( typeof context[ 'tickera/cart_title' ] !== 'undefined' ) ? context[ 'tickera/cart_title' ] : '',
                quantity_title: ( typeof context[ 'tickera/quantity_title' ] !== 'undefined' ) ? context[ 'tickera/quantity_title' ] : ''
            });
        }, 1000 )

        return el( ServerSideRender, {
            block: 'tickera/woo-event-add-to-cart-columns',
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

registerBlockType( 'tickera/woo-event-add-to-cart-rows', {
    title: __( 'Event - Add To Cart', 'tc' ),
    description: __( 'Event add to cart table rows', 'tc' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [],
    supports: supports_args,
    parent: [ 'tickera/woo-event-add-to-cart-group' ],
    usesContext: [
        'tickera/id',
        'tickera/display_type',
        'tickera/quantity',
        'tickera/show_event_title',
        'tickera/show_price'
    ],
    edit: function( props ) {

        const { context } = props;

        setTimeout( function() {
            props.setAttributes( {
                id: ( typeof context[ 'tickera/id' ] !== 'undefined' ) ? context[ 'tickera/id' ] : '',
                display_type: ( typeof context[ 'tickera/display_type' ] !== 'undefined' ) ? context[ 'tickera/display_type' ] : '',
                quantity: ( typeof context[ 'tickera/quantity' ] !== 'undefined' ) ? context[ 'tickera/quantity' ] : false,
                show_event_title: ( typeof context[ 'tickera/show_event_title' ] !== 'undefined' ) ? context[ 'tickera/show_event_title' ] : false,
                show_price: ( typeof context[ 'tickera/show_price' ] !== 'undefined' ) ? context[ 'tickera/show_price' ] : false
            });
        }, 1000 )

        return el( ServerSideRender, {
            block: 'tickera/woo-event-add-to-cart-rows',
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
registerBlockType( 'tickera/woo-event-add-to-cart', {
    title: __( 'Event - Add to Cart', 'tc' ),
    description: __( 'Event Tickets (products) Add to Cart table', 'tc' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera', 'tc' ),
        __( 'Event', 'tc' ),
        __( 'WooCommerce', 'tc' ),
    ],
    supports: { html: false },
    parent: [ 'tickera/woo-event-add-to-cart-group' ],
    edit: function( props ) {
        return el( ServerSideRender, {
            block: "tickera/woo-event-add-to-cart",
            attributes: props.attributes
        } );
    },
    save: function( props ) {
        return null;
    },
} );
