
( function( $ ) {

    var tcBeaver = {

        init: function() {
            parent.jQuery( '#tc-shortcode-builder-wrap' ).attr( 'id', 'tc-shortcode-builder-beaver-wrap' );
            parent.jQuery( 'body' ).append( '<div id="tc-modal-overlay"></div>' );
            parent.jQuery( 'body' ).append( '<div id="tc-modal"></div>' );
            tcBeaver.show_hide_shortcodes( parent.jQuery( '#tc-shortcode-select' ) );
        },

        init_conditional: function() {
            parent.jQuery( '.tc_conditional' ).each( function() {
                tcBeaver.conditions( jQuery( this ) );
            } );

            parent.jQuery( 'body' ).on( 'change', '.has_conditional', function() {
                parent.jQuery( '.tc_conditional' ).each( function() {
                    tcBeaver.conditions( jQuery( this ) );
                } );
            } );
        },

        conditions: function( element ) {

            let field_name = element.attr( 'data-condition-field_name' ),
                selected_value;

            if ( ! parent.jQuery( '.' + field_name ).hasClass( 'has_conditional' ) ) {
                parent.jQuery( '.' + field_name ).addClass( 'has_conditional' );
            }

            let field_type = element.attr( 'data-condition-field_type' ),
                value = element.attr( 'data-condition-value' ),
                action = element.attr( 'data-condition-action' );

            switch ( field_type ) {

                case 'radio':
                    selected_value = parent.jQuery( '.' + field_name + ':checked' ).val();
                    break;

                case 'text':
                case 'textarea':
                    selected_value = parent.jQuery( '.' + field_name ).val();
                    break;

                case 'select':
                    selected_value = parent.jQuery( '.' + field_name + ' option:selected' ).val();
                    break;

                default:
                    selected_value = '';
            }

            if ( value == selected_value ) {

                if ( 'hide' == action ) {
                    element.hide().attr( 'disabled', true );
                    parent.jQuery( '#' + element.attr( 'id' ) + '-error' ).remove();

                } else if ( 'show' == action ) {
                    element.show( 200 ).attr( 'disabled', false );
                }

            } else {

                if ( 'hide' == action ) {
                    element.show( 200 ).attr( 'disabled', false );

                } else if ( 'show' == action ) {
                    element.hide().attr( 'disabled', true );
                    parent.jQuery( '#' + element.attr( 'id' ) + '-error' ).remove();
                }
            }
        },

        show_hide_shortcodes: function( element ) {

            if ( element.length ) {

                var table = parent.jQuery( '#' + element.val().replace( /_/g, '-' ) + '-shortcode' ),
                    tc_shortcodes_form = parent.jQuery( '#tc-shortcode-builder' );

                if ( table.length == 0 ) {
                    if ( tc_shortcodes_form.length == 0 ) {
                        tc_shortcodes_form.find( '.shortcode-table' ).hide();
                    }
                    return;
                }

                table.show().siblings( '.shortcode-table' ).hide();
            }
        }
    };

    /**
     * Retrieve builder form out from beaver frontend builder iframe.
     */
    $( document ).ready( function() {

        var breaverInterval = setInterval( function() {

            if ( $( 'iframe.fl-builder-ui-iframe' ).length > 0 ) {

                if ( window.location !== window.parent.location ) {

                    /**
                     * Move iframe assets back to the parent body.
                     */
                    tcBeaver.init();

                    /**
                     * Init Conditionals
                     */
                    tcBeaver.init_conditional();

                    /**
                     * On Shortcode Change
                     */
                    parent.jQuery( '#tc-shortcode-select' ).on( 'change', function() {
                        tcBeaver.show_hide_shortcodes( jQuery( this ) )
                    });

                    /**
                     * Open modal.
                     * Control styles via css.
                     */
                    parent.jQuery( 'body' ).on( 'click', '.tc-shortcode-builder-button', function ( e ) {
                        e.preventDefault();
                        parent.jQuery( '#tc-shortcode-builder-beaver-wrap' ).find( '#tc-shortcode-builder' ).appendTo( parent.jQuery( '#tc-modal' ) );
                        parent.jQuery( '#tc-modal, #tc-modal-overlay' ).addClass( 'tc-modal-open' );
                    });

                    /**
                     * Close modal on clicked x.
                     */
                    parent.jQuery( 'body' ).on( 'click', '#tc-modal .tc-close', function( e ) {
                        parent.jQuery( '#tc-modal-overlay, #tc-modal' ).removeClass( 'tc-modal-open' );
                    });

                    /**
                     * Submit Shortcode
                     */
                    parent.jQuery( 'body' ).on( 'submit', '#tc-modal #tc-shortcode-builder', function( e ) {
                        e.preventDefault();

                        let builderForm = jQuery( this ),
                            shortcode = '[' + builderForm.find( '[name="shortcode-select"]' ).val(),
                            atts = '';

                        builderForm.find( '.shortcode-table' ).filter( ':visible' ).find( 'input, select, textarea' ).filter( '[name]' ).each( function() {
                            let shrtcd = builderForm.find( '[name="shortcode-select"]' ).val(); // Get shortcode name

                            if ( shrtcd == 'add_to_cart' ) {
                                if ( jQuery.trim( jQuery( this ).val() ).length == 0 ) { return; }

                            } else {
                                if ( jQuery.trim( jQuery( this ).val() ).length == 0 || ( jQuery( this ).attr( 'data-default-value' ) !== undefined && jQuery( this ).attr( 'data-default-value' ) == jQuery.trim( jQuery( this ).val() ) ) ) { return; }
                            }

                            if ( jQuery( this ).is( ':radio' ) || jQuery( this ).is( ':checkbox' ) ) {
                                if ( jQuery( this ).is( ':checked' ) ) { atts += ' ' + jQuery( this ).attr( 'name' ) + '="' + jQuery( this ).val() + '"'; }

                            } else {
                                atts += ' ' + jQuery( this ).attr( 'name' ) + '="' + jQuery( this ).val() + '"';
                            }
                        } );

                        shortcode += atts + ']';
                        window.parent.send_to_editor( shortcode );
                        parent.jQuery( '#tc-modal-overlay, #tc-modal' ).removeClass( 'tc-modal-open' );
                    });
                }

                clearInterval( breaverInterval );
            }

        }, 1000 );
    });

})( jQuery );
