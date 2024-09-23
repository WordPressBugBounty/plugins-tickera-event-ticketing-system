var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    BlockControls = wp.editor.BlockControls,
    InspectorControls = wp.editor.InspectorControls,
    ServerSideRender = wp.components.ServerSideRender;

var AlignmentToolbar = wp.editor.AlignmentToolbar,
    RichText = wp.editor.RichText,
    SelectControl = wp.components.SelectControl,
    RangeControl = wp.components.RangeControl,
    TextControl = wp.components.TextControl,
    ToggleControl = wp.components.ToggleControl;

var __ = wp.i18n.__;

registerBlockType( 'tickera/event-calendar', {
    title: __( 'Event Calendar' ),
    description: __( 'Shows event calendar' ),
    icon: 'calendar',
    category: 'widgets',
    keywords: [
        __( 'Tickera' ),
        __( 'Event' ),
        __( 'Calendar' )
    ],
    supports: {
        html: false,
    },
    attributes: {
        color_scheme: {
            type: 'string',
            default: 'default'
        },
        lang: {
            type: 'string',
            default: 'en'
        },
        show_past_events: {
            type: 'boolean',
            default: false,
        },
    },
    edit: function( props ) {

        var color_schemes_list = jQuery.parseJSON( tc_event_calendar_block_editor.color_schemes ),
            color_schemes_ids = [];

        color_schemes_list.forEach( function( entry ) {
            color_schemes_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } );
        } );

        var languages = jQuery.parseJSON( tc_event_calendar_block_editor.languages ),
            languages_ids = [];

        languages.forEach( function( entry ) {
            languages_ids.push( { value: entry[ 0 ], label: entry[ 1 ] } );
        } );

        return [
            el(
                InspectorControls,
                { key: 'controls' },
                el(
                    SelectControl,
                    {
                        label: __( 'Color Scheme' ),
                        className: 'tc-gb-component',
                        value: props.attributes.color_scheme,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { color_scheme: value } );
                        },
                        options: color_schemes_ids
                    }
                ),
                el(
                    SelectControl,
                    {
                        label: __( 'Language' ),
                        className: 'tc-gb-component',
                        value: props.attributes.lang,
                        onChange: function change_val( value ) {
                            return props.setAttributes( { lang: value } );
                        },
                        options: languages_ids
                    }
                ),
                el(
                    ToggleControl,
                    {
                        label: __( 'Show Past Events' ),
                        className: 'tc-gb-component',
                        checked: props.attributes.show_past_events,
                        value: props.attributes.show_past_events,
                        onChange: function onChange( value ) {
                            return props.setAttributes( { show_past_events: value } );
                        },
                    }
                ),
            ),
            el( ServerSideRender, {
                block: "tickera/event-calendar",
                attributes: props.attributes
            } )
        ];
    },
    save: function( props ) {
        return null;
    },
} );
