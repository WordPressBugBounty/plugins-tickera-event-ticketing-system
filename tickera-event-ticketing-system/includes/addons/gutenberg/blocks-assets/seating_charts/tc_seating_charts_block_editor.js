var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    BlockControls = wp.editor.BlockControls;

if ( tc_seating_charts_block_editor.since_611 ) {
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
        text: true,
        __experimentalSkipSerialization: true,
        __experimentalDefaultControls: {
            background: true,
            gradients: true,
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

registerBlockType( 'tickera/seating-charts', {
    title: __( 'Seating Chart' ),
    description: __( 'Show seating chart button.' ),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Seating' ),
        __( 'Chart' ),
    ],
    supports: supports_args,
    attributes: {
        id: {
            type: 'string',
        },
        show_legend: {
            type: 'boolean',
            default: false,
        },
        button_title: {
            type: 'string',
            default: __( 'Pick your seat(s)' )
        },
        subtotal_title: {
            type: 'string',
            default: __( 'Subtotal' )
        },
        cart_title: {
            type: 'string',
            default: __( 'Go to Cart' )
        },

    },
    edit: function( props ) {

        var seating_charts = jQuery.parseJSON( tc_seating_charts_block_editor.seating_charts ),
            seating_charts_ids = [];

        seating_charts.forEach( function( entry ) {
            seating_charts_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } );
        } );

        return [
            el(
                InspectorControls,
                { key: 'controls' },
                el(
                    SelectControl,
                    {
                        label: __( 'Seating Chart' ),
                        className: 'tc-gb-component',
                        value: props.attributes.id,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { id: value } );
                        },
                        options: seating_charts_ids
                    }
                ),
                el(
                    ToggleControl,
                    {
                        label: __( 'Show Legend' ),
                        className: 'tc-gb-component',
                        checked: props.attributes.show_legend,
                        value: props.attributes.show_legend,
                        onChange: function onChange( value ) {
                            return props.setAttributes( { show_legend: value } );
                        },
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Button Title' ),
                        className: 'tc-gb-component',
                        value: props.attributes.button_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { button_title: value } );
                        },
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Subtotal Title' ),
                        className: 'tc-gb-component',
                        value: props.attributes.subtotal_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { subtotal_title: value } );
                        },
                    }
                ),
                el(
                    TextControl,
                    {
                        label: __( 'Cart Title' ),
                        className: 'tc-gb-component',
                        value: props.attributes.cart_title,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { cart_title: value } );
                        },
                    }
                ),
            ),
            el( ServerSideRender, {
                block: "tickera/seating-charts",
                attributes: props.attributes
            } )
        ];
    },
    save: function( props ) {
        return null;
    },
} );
