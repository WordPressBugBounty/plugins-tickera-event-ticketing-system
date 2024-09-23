var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    BlockControls = wp.editor.BlockControls;

if ( tc_tickets_left_block_editor.since_611 ) {
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

registerBlockType( 'tickera/tickets-left', {
    title: __( 'Tickets Left' ),
    description: __( 'Shows number of available tickets for a ticket type' ),
    icon: 'info',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Tickets' ),
        __( 'Left' )
    ],
    supports: supports_args,
    attributes: {
        ticket_type_id: {
            type: 'string',
        },
    },
    edit: function( props ) {

        var ticket_types = jQuery.parseJSON( tc_tickets_left_block_editor.ticket_types ),
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
                        options: ticket_ids
                    }
                ),
            ),
            el( ServerSideRender, {
                block: "tickera/tickets-left",
                attributes: props.attributes
            } )
        ];
    },
    save: function( props ) {
        return null;
    },
} );
