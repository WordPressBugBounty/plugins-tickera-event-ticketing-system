var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    BlockControls = wp.editor.BlockControls;

if ( tc_event_location_block_editor.since_611 ) {
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
    ToggleControl = wp.components.ToggleControl,
    BaseControl = wp.components.BaseControl;

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

registerBlockType( 'tickera/event-location', {
    title: __( 'Event Location' ),
    description: __( 'Shows location of an event' ),
    icon: 'location',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Location' ),
    ],
    supports: supports_args,
    attributes: {
        event_id: {
            type: 'string',
        },
    },
    edit: function( props ) {

        var events = jQuery.parseJSON( tc_event_location_block_editor.events );

        /**
         * Disable Event Selection on Current Event.
         * @since 3.5.1.8
         */
        if ( typeof events == 'number' ) {
            var eventControl = {
                'type': BaseControl,
                'attributes': {
                    label: __( 'Event: Current Event' ),
                    className: 'tc-gb-component current-event'
                }
            };

        } else {
            var event_ids = [];
            events.forEach( function( entry ) { event_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } ); } );

            var eventControl = {
                'type': SelectControl,
                'attributes': {
                    label: __( 'Event' ),
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
            ),
            el( ServerSideRender, {
                block: "tickera/event-location",
                attributes: props.attributes
            } )
        ];
    },
    save: function( props ) {
        return null;
    },
} );
