var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    BlockControls = wp.editor.BlockControls;

if ( tc_event_logo_block_editor.since_611 ) {
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

registerBlockType( 'tickera/event-logo', {
    title: __( 'Event Logo', 'tc' ),
    description: __( 'Shows logo of an event', 'tc' ),
    icon: 'format-image',
    category: 'widgets',
    keywords: [
        __( 'Tickera', 'tc' ),
        __( 'Logo', 'tc' ),
    ],
    supports: {
        html: false
    },
    attributes: {
        event_id: {
            type: 'string',
        },
    },
    edit: function( props ) {

        var events = jQuery.parseJSON( tc_event_logo_block_editor.events );

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
                block: "tickera/event-logo",
                attributes: props.attributes
            } )
        ];
    },
    save: function( props ) {
        return null;
    },
} );
