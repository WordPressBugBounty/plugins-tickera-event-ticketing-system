( function( $ ) {

    $( document ).ready( function( $ ) {
        tc_shortcode_builder.init();
        tc_shortcode_builder.init_colorbox();
        tc_shortcode_builder.init_chosen_event_filter();
        tc_shortcode_builder.init_chosen_ticket_type_filter();
        tc_shortcode_builder.init_conditional();

        let $tc_shortcodes_form = $( '#tc-shortcode-builder' );
        tc_shortcode_builder.show_hide_shortcodes( $tc_shortcodes_form.find( '[name="shortcode-select"]' ) );
    } );

    var tc_shortcode_builder = {

        /**
         * Delays the process
         */
        debounce: ( function() {
            let timer = 0;
            return function( callback, ms ) {
                clearTimeout( timer );
                timer = setTimeout( callback, ms );
            };
        })(),

        /**
         * Initialize Shortcode Builder
         */
        init: function() {

            var $tc_shortcodes_form = $( '#tc-shortcode-builder' );

            $tc_shortcodes_form.find( '[name="shortcode-select"]' ).change( function() {
                tc_shortcode_builder.show_hide_shortcodes( this );
                tc_shortcode_builder.window_height();
            } );

            $tc_shortcodes_form.submit( function( e ) {

                e.preventDefault();

                var shortcode = '[' + $tc_shortcodes_form.find( '[name="shortcode-select"]' ).val(),
                    atts = '';

                $tc_shortcodes_form.find( '.shortcode-table' ).filter( ':visible' ).find( 'input, select, textarea' ).filter( '[name]' ).each( function() {

                    var $this = $( this ),
                        shrtcd = $tc_shortcodes_form.find( '[name="shortcode-select"]' ).val(); // Get shortcode name

                    if ( shrtcd == 'add_to_cart' ) {

                        if ( $.trim( $this.val() ).length == 0 ) {
                            return;
                        }

                    } else {

                        if ( $.trim( $this.val() ).length == 0 || ( $this.attr( 'data-default-value' ) !== undefined && $this.attr( 'data-default-value' ) == $.trim( $this.val() ) ) ) {
                            return;
                        }
                    }

                    if ( $this.is( ':radio' ) || $this.is( ':checkbox' ) ) {

                        if ( $this.is( ':checked' ) ) {
                            atts += ' ' + $this.attr( 'name' ) + '="' + $this.val() + '"';
                        }

                    } else {
                        atts += ' ' + $this.attr( 'name' ) + '="' + $this.val() + '"';
                    }
                } );

                shortcode += atts + ']';

                window.send_to_editor( shortcode );
                $.colorbox.close();
            } );
        },

        /**
         * Initialize Colorbox
         */
        init_colorbox: function() {

            $( 'body' ).on( 'click', '.tc-shortcode-builder-button', function() {

                setTimeout( function() {
                    tc_shortcode_builder.window_height();
                }, 500 );

                var $this = $( this );

                $.colorbox( {
                    "width": '39%',
                    "maxWidth": "80%",
                    "height": "70%",
                    "inline": true,
                    "href": "#tc-shortcode-builder",
                    "opacity": 0.8,
                    "className": 'tc-shortcodes-colorbox'
                } );

                tc_shortcode_builder.window_width();
            } );

            jQuery( window ).resize( function() {
                tc_shortcode_builder.window_width();
            } );
        },

        /**
         * Initialize Conditionals
         */
        init_conditional: function() {

            $( '.tc_conditional' ).each( function() {
                tc_shortcode_builder.conditions( $( this ) );
            } );

            $( document ).on( 'change', '.has_conditional', function() {
                $( '.tc_conditional' ).each( function() {
                    tc_shortcode_builder.conditions( $( this ) );
                } );
            } );
        },

        conditions: function( obj ) {

            let field_name = $( obj ).attr( 'data-condition-field_name' ),
                selected_value;

            if ( !$( '.' + field_name ).hasClass( 'has_conditional' ) ) {
                $( '.' + field_name ).addClass( 'has_conditional' );
            }

            let field_type = $( obj ).attr( 'data-condition-field_type' ),
                value = $( obj ).attr( 'data-condition-value' ),
                action = $( obj ).attr( 'data-condition-action' );

            switch ( field_type ) {

                case 'radio':
                    selected_value = $( '.' + field_name + ':checked' ).val();
                    break;

                case 'text':
                case 'textarea':
                    selected_value = $( '.' + field_name ).val();
                    break;

                case 'select':
                    selected_value = $( '.' + field_name + ' option:selected' ).val();
                    break;

                default:
                    selected_value = '';
            }

            if ( value == selected_value ) {

                if ( 'hide' == action ) {
                    $( obj ).hide().attr( 'disabled', true );
                    $( '#' + $( obj ).attr( 'id' ) + '-error' ).remove();

                } else if ( 'show' == action ) {
                    $( obj ).show( 200 ).attr( 'disabled', false );
                }

            } else {

                if ( 'hide' == action ) {
                    $( obj ).show( 200 ).attr( 'disabled', false );

                } else if ( 'show' == action ) {
                    $( obj ).hide().attr( 'disabled', true );
                    $( '#' + $( obj ).attr( 'id' ) + '-error' ).remove();
                }
            }
        },

        show_hide_shortcodes: function( objectval ) {

            if ( $( objectval ).length ) {

                var $table = $( '#' + $( objectval ).val().replace( /_/g, '-' ) + '-shortcode' ),
                    $tc_shortcodes_form = $( '#tc-shortcode-builder' );

                if ( $table.length == 0 ) {
                    if ( $tc_shortcodes_form.length == 0 ) {
                        $tc_shortcodes_form.find( '.shortcode-table' ).hide();
                    }
                    $.colorbox.resize();
                    return;
                }

                $table.show().siblings( '.shortcode-table' ).hide();
            }
        },

        window_width: function() {

            let tc_window_width = jQuery( window ).width();

            if ( tc_window_width < 950 ) {
                jQuery( "#tc-shortcode-builder" ).colorbox.resize( { width: "90%" } );

            } else {
                jQuery( "#tc-shortcode-builder" ).colorbox.resize( { width: "39%" } );
            }
        },

        window_height: function() {
            var tc_get_height = jQuery( '.tc-shortcode-wrap' ).height();
            $.colorbox.resize( { "height": tc_get_height + 130 + "px" } );
        },

        /**
         * =======================================================================
         * Events Filter - Chosen Field with initial 10 values.
         * Search functionality to be handled by ajax request.
         * =======================================================================
         */
        init_chosen_event_filter: function() {

            $( 'select.tc-event-filter' ).chosen({ width: 'auto' });

            $( 'select.tc-event-filter ~ .chosen-container .chosen-search' ).prepend( '<div class="tc-loader"></div>' );
            $( 'select.tc-event-filter ~ .chosen-container .chosen-search input' ).attr( 'placeholder', tc_shortcode_builder_vars.please_enter_at_least_3_characters );

            window.tc_searching_event_filter = false;
            $( 'select.tc-event-filter ~ .chosen-container .chosen-search input' ).on( 'keyup', function() {
                let keyword = $( this ).val();
                if ( ! window.searching_event_filter && keyword.length >= 3 ) {
                    tc_shortcode_builder.debounce( function() {
                        $( '.tc-loader' ).show();
                        window.tc_searching_event_filter = true;
                        $.post( tc_shortcode_builder_vars.ajaxUrl, {
                            action: 'search_event_filter',
                            s: keyword,
                            nonce: tc_shortcode_builder_vars.ajaxNonce,
                            excluded: [ 0 ] // List of Event IDs. Zero indicates to 'All'
                        }, function( response ) {
                            window.tc_searching_event_filter = false;
                            if ( response.count ) {
                                $( 'select.tc-event-filter' ).empty().append( response.options_html ).trigger( 'chosen:updated' )
                            }
                            $( '.tc-loader' ).hide();
                        } );
                    }, 1000 )
                }
            } );
        },

        /**
         * =======================================================================
         * Ticket Types Filter - Chosen Field with initial 10 values.
         * Search functionality to be handled by ajax request.
         * =======================================================================
         */
        init_chosen_ticket_type_filter: function() {

            $( 'select.tc-ticket-type-filter' ).chosen({ width: 'auto' });

            $( 'select.tc-ticket-type-filter ~ .chosen-container .chosen-search' ).prepend( '<div class="tc-loader"></div>' );
            $( 'select.tc-ticket-type-filter ~ .chosen-container .chosen-search input' ).attr( 'placeholder', tc_shortcode_builder_vars.please_enter_at_least_3_characters );

            window.tc_searching_ticket_type_filter = false;
            $( 'select.tc-ticket-type-filter ~ .chosen-container .chosen-search input' ).on( 'keyup', function() {
                let keyword = $( this ).val();
                if ( ! window.searching_ticket_type_filter && keyword.length >= 3 ) {
                    tc_shortcode_builder.debounce( function() {
                        $( '.tc-loader' ).show();
                        window.tc_searching_ticket_type_filter = true;
                        $.post( tc_shortcode_builder_vars.ajaxUrl, {
                            action: 'search_ticket_type_filter',
                            s: keyword,
                            nonce: tc_shortcode_builder_vars.ajaxNonce,
                            excluded: [ 0 ] // List of Event IDs. Zero indicates to 'All'
                        }, function( response ) {
                            window.tc_searching_ticket_type_filter = false;
                            if ( response.count ) {
                                $( 'select.tc-ticket-type-filter' ).empty().append( response.options_html ).trigger( 'chosen:updated' )
                            }
                            $( '.tc-loader' ).hide();
                        } );
                    }, 1000 )
                }
            } );
        }
    };
})( jQuery );
