( function ( $ ) {

    $( document ).ready( function ( $ ) {


        /**
         * Initialize Conditional Fields
         */
        tc_admin.init_conditional();

        /**
         * Initialize Dynamic Fields
         */
        tc_admin.init_dynamic_fields();

        /**
         * Initialize Chosen Fields
         */
        $( '.tc_chosen' ).each( function () {
            tc_admin.chosen( $( this ) );
            tc_admin.fix_chosen( $( this ) );
        } );

        /**
         * Process Bulk Deletion of Tickets
         * Tickera > Settings > Delete Info
         * @since 3.5.2.3
         */
        $('#tc_dl_delete_btn').on( 'click', function() {

            let confirmAction = confirm( tc_vars.confirm_action_message );

            if ( confirmAction ) {

                $( '.tc_dl_notice' ).empty().addClass( 'tc-dl-notice' ).text( 0 + ' ' + tc_vars.tickets_have_been_removed );
                tc_dl.loading( true );

                // Start deleting
                tc_dl.process_delete();
            }
        });

        var tc_element_in_edit_mode = null;

        tc_current_url = $( location ).attr( 'href' );
        var incStr = tc_current_url.includes( "add_new" );

        jQuery( '.tc-template-selection li' ).click( function () {
            jQuery( '.tc-notification-start' ).addClass( 'tc-hidden-notification' );
        } );

        if ( incStr === true ) {
            jQuery( '.tc-notification-start' ).removeClass( 'tc-hidden-notification' );
        }

        /*
         * Edit current element when changing value in the sidebar editor
         */

        $( 'body' ).on( 'change', '.tc-edit-mode select, .tc-edit-mode input', function () {

            if ( $( this ).is( "select" ) ) {
                $( '.tc_element_edit_mode_active [name="' + $( this ).attr( "name" ) + '"]' ).val( this.value ).change();
            }

            if ( $( this ).is( "input" ) ) {
                if ( $( this ).attr( 'type' ) == 'text' ) {
                    $( '.tc_element_edit_mode_active [name="' + $( this ).attr( "name" ) + '"]' ).val( this.value );
                }
                if ( $( this ).attr( 'type' ) == 'checkbox' ) {
                    //TO DO
                }
                if ( $( this ).attr( 'type' ) == 'radio' ) {
                    //TO DO
                }
                //Check tck_color_options in this file for color copies / edits
            }
        } );

        /**
         * ----------------------------------------
         */

        $( 'body' ).on( 'click', '#add_new_template', function ( e ) {
            //Stop button click propagation
            e.stopPropagation();

            //Create RFC 8259 format for template structure
            //Data will be saved as post content in the database

            var template = {
                'settings': { },
                'rows': { },
            };

            //Save template general settings
            $.each( $( '#template_document_settings [name*="_post_meta"]' ), function () {
                template['settings'][this.name.replace( "_post_meta", "" )] = this.value;
            } );

            var rows_count = 0;
            var current_row = 0;

            //Go through ROWS
            $.each( $( '.ticket-elements-drop-area .tc-inside-template' ), function () {
                rows_count++;
                current_row++;

                template['rows']['row_' + current_row] = {
                    'info': {
                        'order': current_row,
                        'type': $( this ).data( "type" ),
                        'cols': 0,
                    },
                    'cols': { }
                };

                row_cols = 0;
                //Go through COLUMNS
                $( this ).find( '> div > ul' ).each( function () {
                    row_cols++;
                    template['rows']['row_' + current_row]['cols']['col_' + row_cols] = {
                        'info': {
                            'order': row_cols,
                            'elements': 0,
                        },
                        'elements': { }
                    };

                    elements_count = 0;
                    //Go through ELEMENTS
                    $( this ).find( '> li' ).each( function () {
                        elements_count++;
                        var element_name = $( this ).data( 'class' );
                        template['rows']['row_' + current_row]['cols']['col_' + row_cols]['elements']['element_' + elements_count] = { };
                        ////Go through ELEMENT FIELDS
                        $( this ).find( '.element_content [name*="_post_meta"]' ).each( function () {
                            template['rows']['row_' + current_row]['cols']['col_' + row_cols]['elements']['element_' + elements_count]['name'] = element_name;
                            template['rows']['row_' + current_row]['cols']['col_' + row_cols]['elements']['element_' + elements_count][this.name.replace( "_post_meta", "" )] = this.value;
                        } );

                    } );
                    template['rows']['row_' + current_row]['cols']['col_' + row_cols]['info']['elements'] = elements_count;
                } );

                template['rows']['row_' + current_row]['info']['cols'] = row_cols;
            } );

            template['settings']['rows_count'] = rows_count;
            $( '#template_content' ).html( JSON.stringify( template ) );
        } );


        // sets the right holder to fixed when scrolling
        jQuery( window ).on( 'scroll', function ( event ) {
            var tc_scroll = jQuery( window ).scrollTop( );
            if ( tc_scroll > 180 ) {
                var tc_get_height = $( window ).height() - 200;
                jQuery( '.tc-right-resizable' ).addClass( 'tc-sticky-section' );
                jQuery( '.tc-elements-wrap' ).attr( 'style', 'height:' + tc_get_height + 'px;' );

            } else {
                var tc_get_height = $( window ).height() - 300;
                jQuery( '.tc-right-resizable' ).removeClass( 'tc-sticky-section' );
                jQuery( '.tc-elements-wrap' ).attr( 'style', 'height:' + tc_get_height + 'px;' );
            }
        } );

        var tc_get_height = $( window ).height() - 300;
        jQuery( '.tc-right-resizable' ).removeClass( 'tc-sticky-section' );
        jQuery( '.tc-elements-wrap' ).attr( 'style', 'height:' + tc_get_height + 'px;' );

        //add new section between the rows
        jQuery( '.tc-add-new-section' ).click( function () {
            click_selection();
        } );

        //DELETE ROW IN TICKETS TEMPLATE ON CLOSE CLICK
        //function tc_delete_row(){
        $( 'body' ).on( 'click', '.tc-delete-row', function () {
            //jQuery('.tc-delete-row').click(function(){
            var div_remember = this;

            setTimeout( function () {
                jQuery( div_remember ).closest( 'ul.ui-sortable' ).remove();
            }, 250 )

            jQuery( this ).closest( 'ul.ui-sortable' ).animate( {
                height: "0",
                opacity: "0"
            } );

        } );

        function sortable_update() {
            $( "ul.sortables" ).sortable( {
                connectWith: 'ul ul',
                forcePlaceholderSize: true,
                items: "> li",
                start: function ( event, ui ) {
                    var get_parent = $( this ).parents( '.right-holder' );
                    if ( ( get_parent ).hasClass( 'right-holder' ) ) {
                        ui.helper.before( ui.helper.clone().attr( 'style', '' ) );
                        ui.item.data( 'sorting', true );
                    }
                },
                receive: function ( template, ui ) {
                    update_li();
                    $( ".rows ul li" ).last().addClass( "last_child" );
                },
                stop: function ( template, ui ) {
                    var parent_drop_false = $( ui.item ).parent().hasClass( 'dropfalse' );

                    if ( parent_drop_false ) {
                        $( ui.item ).remove();
                    }
                    update_li();
                    ui.item.removeData( 'sorting' );

                }
            } );

            $( "ul.sortables" ).disableSelection();

            //CHILDREN SORTABLE
            jQuery( ".ticket-elements-drop-area" ).sortable( {
                containment: ".ticket-elements-drop-area",
                items: "> ul",
                handle: ".tc-move-row",
                //connectWith: 'ul.ui-sortable',
                tolerance: "pointer",
                cursor: "move",
                opacity: 0.7,
                dropOnEmpty: false,
                //placeholder: "movable-placeholder",
                start: function ( e, ui ) {
                    //ui.placeholder.height(ui.helper.outerHeight());
                }
            } );
            var handle = $( ".selector" ).sortable( "option", "handle" );

            $( ".tc-move-row" ).disableSelection();

        }


        //DISPLAY ELEMENTS SECTION INSTEAD OF GENERAL OPTIONS
        function tc_display_elements_section() {
            jQuery( '#template_document_settings' ).hide();
            jQuery( '.tc-sortables-wrap' ).show();

            //open a sidebar when an element is in edit mode
            jQuery( '.tc-right-resizable' ).removeClass( 'tc-hide-section' );
            jQuery( '.fullwidth-holder' ).removeClass( 'tc-gofullwidth' );
        }

        jQuery( '.tc-slide-button' ).click( function () {
            if ( !jQuery( '.tc-right-resizable' ).hasClass( 'tc-hide-section' ) ) {
                //Close sidebar
                jQuery( '.tc-right-resizable' ).addClass( 'tc-hide-section' );
                jQuery( '.fullwidth-holder' ).addClass( 'tc-gofullwidth' );
            } else {
                //Open sidebar
                jQuery( '.tc-right-resizable' ).removeClass( 'tc-hide-section' );
                jQuery( '.fullwidth-holder' ).removeClass( 'tc-gofullwidth' );
            }
            ;
        } );

        function element_edit_mode() {
            jQuery( '.tc_element_edit_mode_active' ).removeClass( 'tc_element_edit_mode_active' );
            tc_element_in_edit_mode.addClass( 'tc_element_edit_mode_active' );
        }

        jQuery( '.tc_wrap' ).click( function ( e ) {
            if ( e.target !== this ) {
                //return;
            } else {
                //tc_element_in_edit_mode = null;
                //jQuery('.tc_element_edit_mode_active').removeClass('tc_element_edit_mode_active');
            }

        } );

        $( 'body' ).on( 'click', '.tc-edit-block', function ( e ) {
            //jQuery( '.tc-edit-block' ).click( function ( e ) {
            e.preventDefault();

            //hide all fields
            jQuery( '.tc-elements-wrap' ).addClass( 'tc-edit-mode' );
            var get_data_class = jQuery( this ).closest( 'li' ).attr( 'data-class' );//get element class name
            tc_element_in_edit_mode = jQuery( this ).closest( 'li' );
            element_edit_mode();

            //console.log(tc_element_in_edit_mode);

            //display elements section isntead of general options
            tc_display_elements_section();

            jQuery( '.tc-elements-wrap ul li' ).each( function () {
                jQuery( this ).hide();
            } );

            get_data_class = jQuery( this ).closest( 'li' ).attr( 'data-class' );

            jQuery( '#ticket_elements .' + get_data_class ).attr( 'style', 'display: block;' );

            $( ".tc_element_edit_mode_active .element_content select, .tc_element_edit_mode_active .element_content input" ).each( function () {

                if ( $( this ).is( "select" ) ) {

                    if ( $( this ).attr( "name" ) !== undefined ) {
                        $( '.tc-edit-mode [name="' + $( this ).attr( "name" ) + '"]' ).val( this.value ).change();
                        $( '.tc-edit-mode [name="' + $( this ).attr( "name" ) + '"]' ).trigger( "chosen:updated" );
                    }
                }

                if ( $( this ).is( "input" ) ) {
                    if ( $( this ).attr( "name" ) !== undefined ) {
                        if ( $( this ).attr( 'type' ) == 'text' ) {
                            $( '.tc-edit-mode [name="' + $( this ).attr( "name" ) + '"]' ).val( this.value );
                            //Different way to set colors
                            if ( $( this ).attr( 'class' ) == 'tc-color-picker wp-color-picker' ) {
                                //console.log($( this ).attr( "name" ));

                                $( '#ticket_elements' ).find( '[name="' + $( this ).attr( "name" ) + '"]' ).wpColorPicker( 'color', this.value );
                            }
                        }
                        if ( $( this ).attr( 'type' ) == 'checkbox' ) {
                            //TO DO
                        }
                        if ( $( this ).attr( 'type' ) == 'radio' ) {
                            //TO DO
                        }
                    }
                }
            } );

        } );


        function click_selection() {
            jQuery( '.tc-template-selection li' ).click( function () {

                var check_class = jQuery( this ).parent().hasClass( 'tc-inside-template' );
                var get_main_wrap = jQuery( this ).parent();

                var get_column_number = jQuery( this ).attr( 'data-attr' );

                if ( check_class == true ) {
                    jQuery( this ).parent().empty();
                    jQuery( get_main_wrap ).closest( 'ul.tc-template-selection' ).removeClass( 'tc-template-selection' );
                    select_div = get_main_wrap;
                    jQuery()
                } else {
                    jQuery( '.ticket-elements-drop-area ul' ).each( function () {
                        jQuery( this ).removeClass( 'tc-ul-created' );
                    } );
                    jQuery( '.ticket-elements-drop-area' ).append( '<ul class="sortables droptrue ui-sortable column-' + get_column_number + ' tc-ul-created tc-inside-template" data-type="' + get_column_number + '"></ul>' );
                    select_div = '.tc-ul-created';
                }

                jQuery( '.tc-elements-wrap' ).removeClass( 'tc-edit-mode' );
                tc_display_elements_section();

                jQuery( '.tc-elements-wrap ul li' ).each( function () {
                    jQuery( this ).show();
                } );


                switch ( get_column_number ) {
                    case '1':
                        jQuery( select_div ).prepend( '<div>\
          <ul class="sortables droptrue fullwidth"></ul>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tti-close_delete_exit_remove_icon"></span>\
          </div>\
          </div>' );
                        break;

                    case '2':
                        jQuery( select_div ).prepend( '<div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>\
          ' );
                        break;

                    case '3':
                        jQuery( select_div ).prepend( '<div>\
          <ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>' );
                        break;

                    case '4':
                        jQuery( select_div ).prepend( '<div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>' );
                        break;

                    case '5':
                        jQuery( select_div ).prepend( '<div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>' );
                        break;

                    case '6':
                        jQuery( select_div ).prepend( '<div>\
          <ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>' );
                        break;

                    case '7':
                        jQuery( select_div ).prepend( '<div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>' );
                        break;

                    case '8':
                        jQuery( select_div ).prepend( '<div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>' );
                        break;

                    case '9':
                        jQuery( select_div ).prepend( '<div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>\
          ' );
                        break;

                    case '10':
                        jQuery( select_div ).prepend( '<div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>' );
                        break;

                    case '11':
                        jQuery( select_div ).prepend( '<div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div><ul class="sortables droptrue fullwidth"></ul></div>\
          <div class="tc-row-control-wrap ui-sortable-handle">\
          <span class="tc-move-row tti-arrows_direction_expand_navigation_resize_icon-1"></span>\
          <span class="tc-delete-row tti-close_delete_exit_remove_icon"></span>\
          </div>' );
                        break;


                    default:
                    // code to be executed if n is different from case 1 and 2
                }

                //use this function to update sorting to new elements
                sortable_update();
                $( 'html, body' ).animate( { scrollTop: $( document ).height() }, 300 );

                //updates the list of deletable items
                //tc_delete_row();

            } );
        }

        //tc_delete_row();
        click_selection();

        jQuery( '.tti-boxes' ).click( function () {
            jQuery( '#template_document_settings' ).hide();
            jQuery( '.tc-sortables-wrap' ).show();
            jQuery( '.tc-elements-wrap' ).removeClass( 'tc-edit-mode' );
            jQuery( '#ticket_elements > li' ).each( function () {
                jQuery( this ).show();
            } );
        } );

        jQuery( '.tti-gear_maintenance_settings_icon-1' ).click( function () {
            jQuery( '#template_document_settings' ).show();
            jQuery( '.tc-sortables-wrap' ).hide();
            tc_element_in_edit_mode = null;
        } );

        $( 'body' ).on( 'keyup change keydown', '#tc_options_search_val', function () {

            var searched_option = $( this ).val();

            if ( searched_option == '' ) {
                $( ".form-table tr" ).show();

            } else {

                try {
                    var search_key_match = new RegExp( searched_option, 'i' );

                } catch ( e ) {
                    var search_key_match = '';
                }

                $( ".form-table label" ).each( function () {

                    if ( ( $( this ).html().match( search_key_match ) ) ) {
                        $( this ).parent().parent().show();

                    } else {
                        $( this ).parent().parent().hide();
                    }
                } );
            }
        } );

        $( ".post-type-tc_tickets #post, .post-type-tc_events #post" ).validate( { ignore: '[id^="acf"], #us_portfolio_settings input' } );
        $( "#tc-general-settings, #tc_ticket_type_form, #tc_discount_code_form, .tc_form_validation_required" ).validate( {
            rules: {
                field: {
                    number: true
                },
                field: {
                    required: true
                }
            }
        } );

        if ( $( '#discount_type' ).length && $( '#discount_value' ).length ) {
            tc_check_discount_code_type();
            $( '#discount_type' ).change( function () {
                tc_check_discount_code_type();
            } );
        }

        function tc_check_discount_code_type() {

            if ( $( '#discount_type' ).length && $( '#discount_value' ).length ) {

                if ( $( "#discount_type option:selected" ).val() == '3' ) {
                    $( 'tr.discount_availability' ).hide();

                } else {
                    $( 'tr.discount_availability' ).show();
                }
            }
        }

        $( '.tc_tooltip' ).tooltip( {
            content: function () {
                return $( this ).prop( 'title' );
            },
            show: null,
            close: function ( event, ui ) {
                ui.tooltip.hover(
                    function () {
                        $( this ).stop( true ).fadeTo( 100, 1 );
                    },
                    function () {
                        $( this ).fadeOut( "100", function () {
                            $( this ).remove();
                        } )
                    }
                );
            }
        } );

        /**
         * Toggle Controls
         * @type {number}
         */
        var tc_event_id = 0;
        var tc_ticket_id = 0;
        var tc_event_status = 'publish';
        var tc_ticket_status = 'publish';

        var tc_toggle = {

            init: function () {
                $( 'body' ).addClass( 'tctgl' );
                this.attachHandlers( '.tctgl .tc-control' );
            },

            tc_controls: {

                $tc_toggle_init: function ( selector ) {

                    $( selector ).click( function () {

                        let toggleData = $( this ).find( '.tc-toggle-data' ).serialize(),
                            toggleValue = $( this ).find( '.tc-toggle-value' );

                        if ( toggleData ) {

                            $( this ).toggleClass( 'tc-on' );

                            let toggleParams = new URLSearchParams( toggleData );
                            toggleParams = Object.fromEntries( toggleParams );

                            /**
                             * Allow processing of ajax request in toggle switch.
                             * Requires:
                             *      input field
                             *          type: hidden
                             *          class: tc-toggle-data
                             *          name: action
                             *          value: {callback_function}
                             *
                             * @since 3.5.1.2
                             */
                            if ( typeof toggleParams[ 'action' ] !== 'undefined' ) {
                                $.post( tc_vars.ajaxUrl, toggleParams );
                            }

                        } else if ( toggleValue.length > 0 ) {

                            /**
                             * Update toggle switch value on change.
                             * Trigger change to ensure the event is fired.
                             * Requires:
                             *      input field
                             *          type: hidden
                             *          class: tc-toggle-value
                             *          name: {field_name}
                             *          value: {field_value}
                             *
                             * @since 3.5.1.2
                             */
                            $( this ).toggleClass( 'tc-on' );

                            if ( $( this ).hasClass( 'tc-on' ) ) {
                                toggleValue.val( 'yes' ).trigger( 'change' );

                            } else {
                                toggleValue.val( 'no' ).trigger( 'change' );
                            }

                        } else {

                            /**
                             * Default function of a toggle switch
                             *
                             * @type {jQuery|undefined}
                             */
                            tc_event_id = $( this ).attr( 'event_id' );
                            tc_ticket_id = $( this ).attr( 'ticket_id' );

                            if ( $( this ).hasClass( 'tc-on' ) ) {
                                $( this ).removeClass( 'tc-on' );
                                tc_event_status = 'private';
                                tc_ticket_status = 'private';

                            } else {
                                $( this ).addClass( 'tc-on' );
                                tc_event_status = 'publish';
                                tc_ticket_status = 'publish';
                            }

                            var attr = $( this ).attr( 'event_id' );

                            if ( typeof attr !== typeof undefined && attr !== false ) {

                                // Event toggle
                                $.post(
                                    tc_vars.ajaxUrl, {
                                        action: 'change_event_status',
                                        event_status: tc_event_status,
                                        event_id: tc_event_id,
                                        nonce: tc_vars.ajaxNonce
                                    }
                                );

                            } else {
                                $.post(
                                    tc_vars.ajaxUrl, {
                                        action: 'change_ticket_status',
                                        ticket_status: tc_ticket_status,
                                        ticket_id: tc_ticket_id,
                                        nonce: tc_vars.ajaxNonce
                                    }
                                );
                            }
                        }
                    } );
                }
            },

            attachHandlers: function ( selector ) {
                this.tc_controls.$tc_toggle_init( selector );
            }
        };

        tc_toggle.init();

        $( document ).on( 'change', 'input.tc_active_gateways', function () {

            var currently_selected_gateway_name = $( this ).val();

            if ( $( this ).is( ':checked' ) ) {
                $( '#' + currently_selected_gateway_name ).show( 200 );

            } else {
                $( '#' + currently_selected_gateway_name ).hide( 200 );
            }
        } );


        if ( tc_vars.animated_transitions ) {
            $( ".tc_wrap" ).fadeTo( 250, 1 );
            $( ".tc_wrap #message" ).delay( 2000 ).slideUp( 250 );

        } else {
            $( ".tc_wrap" ).fadeTo( 0, 1 );
        }


        $( document ).on( 'click', '.tc_delete_link', function ( event ) {
            tc_delete( event );
        } );

        function tc_delete_confirmed() {
            return confirm( tc_vars.delete_confirmation_message );
        }

        function tc_delete( event ) {

            if ( tc_delete_confirmed() ) {
                return true;

            } else {
                event.preventDefault()
                return false;
            }
        }

        $( '.file_url_button' ).click( function () {
            var target_url_field = $( this ).prevAll( ".file_url:first" );
            wp.media.editor.send.attachment = function ( props, attachment ) {
                $( target_url_field ).val( attachment.url );
                $( target_url_field ).change();//trigger change event
            };
            wp.media.editor.open( this );
            return false;
        } );

        /**
         * Ticket Tempaltes
         * @type {any[]}
         */
        var ticket_classes = new Array();
        var parent_id = 0;

        var tck_color_options = {
            change: function ( event, ui ) {
                var color = ui.color.toString();
                //Edit element colors based on sidebar editor
                $( '.tc_element_edit_mode_active [name="' + $( this ).attr( "name" ) + '"]' ).val( color );
            },
        };

        $( '.tc-color-picker' ).wpColorPicker( tck_color_options );

        //sortable function enables all the elements to be draggable
        sortable_update();

        function update_li() {

            var children_num = 0;
            var current_child_num = 0;

            $( ".rows ul" ).each( function () {

                // Empty the array
                ticket_classes.length = 0;

                children_num = $( this ).children( 'li' ).lencolorpickergth;
                $( this ).children( 'li' ).removeClass();
                $( this ).children( 'li' ).addClass( "ui-state-default" );
                $( this ).children( 'li' ).addClass( "cols cols_" + children_num );
                $( this ).children( 'li' ).last().addClass( "last_child" );
                $( this ).find( 'li' ).each( function ( index, element ) {

                    /*if ( $.inArray( $( this ).attr( 'data-class' ), ticket_classes ) == -1 ) {
                     ticket_classes.push( $( this ).attr( 'data-class' ) );
                     }*/
                } );

                //$( this ).find( '.rows_classes' ).val( ticket_classes.join() );
            } );
            tc_fix_template_elements_sizes();

            $( ".rows ul li" ).last().addClass( "last_child" );
            $( ".tc_wrap select" ).css( 'width', '25em' );
            $( ".tc_wrap select" ).css( 'display', 'block' );

            $( ".tc_wrap select" ).chosen( { disable_search_threshold: 5 } );
            $( ".tc_wrap select" ).css( 'display', 'none' );
            $( ".tc_wrap .chosen-container" ).css( 'width', '100%' );
            $( ".tc_wrap .chosen-container" ).css( 'max-width', '25em' );
            $( ".tc_wrap .chosen-container" ).css( 'min-width', '1em' );
        }

        function tc_fix_template_elements_sizes() {

            $( ".rows ul" ).each( function () {

                var maxHeight = -1;

                $( this ).find( 'li' ).each( function () {
                    $( this ).removeAttr( "style" );
                    maxHeight = maxHeight > $( this ).height() ? maxHeight : $( this ).height();
                } );

                $( this ).find( 'li' ).each( function () {
                    //$( this ).height( maxHeight );
                } );
            } );
        }

        if ( $( '#ticket_elements' ).length ) {
            update_li();
            tc_fix_template_elements_sizes();
        }

        $( '.close-this' ).click( function ( event ) {
            event.preventDefault();
            $( this ).closest( '.ui-state-default' ).appendTo( '#ticket_elements' );
            update_li();
            tc_fix_template_elements_sizes();
        } );

        $( '#tc_order_resend_confirmation_email' ).on( 'click', function ( event ) {

            event.preventDefault();

            var new_status = $( '.order_status_change' ).val();
            var order_id = $( '#order_id' ).val();

            $( this ).hide();
            $( this ).after( '<span id="tc_resending">' + tc_vars.order_confirmation_email_resending_message + '</a>' );

            $.post( tc_vars.ajaxUrl, {
                action: "change_order_status",
                order_id: order_id,
                new_status: new_status,
                nonce: tc_vars.ajaxNonce
            }, function( data ) {

                if ( data != 'error' ) {
                    $( '.tc_wrap .message_placeholder' ).html( '' );
                    $( '.tc_wrap .message_placeholder' ).append( '<div id="message" class="updated fade"><p>' + tc_vars.order_confirmation_email_resent_message + '</p></div>' );
                    $( ".tc_wrap .message_placeholder" ).show( 250 );
                    $( ".tc_wrap .message_placeholder" ).delay( 2000 ).slideUp( 250 );
                    $( '#tc_resending' ).addClass( 'tc-success' ).text( tc_vars.order_confirmation_email_resent_message );

                } else {
                    $( '#tc_resending' ).addClass( 'tc-error' ).text( tc_vars.order_confirmation_email_failed_message );
                }

                $( this ).fadeTo( "fast", 1 );
            } );
        } );

        /**
         * Payment Gateway Image Switch
         */
        $( ".tc_active_gateways" ).each( function () {

            if ( this.checked ) {
                $( this ).closest( '.image-check-wrap' ).toggleClass( 'active-gateway' );
            }

            $( this ).change( function () {

                tc_admin.fix_chosen();

                if ( this.checked ) {
                    $( this ).closest( '.image-check-wrap' ).toggleClass( 'active-gateway' );

                } else {
                    $( this ).closest( '.image-check-wrap' ).toggleClass( 'active-gateway' );
                }
            } );
        } )

        if ( $( '#tickets_limit_type' ).val() == 'event_level' ) {
            $( '#event_ticket_limit' ).parent().parent().show();

        } else {
            $( '#event_ticket_limit' ).parent().parent().hide();
        }

        $( '#tickets_limit_type' ).on( 'change', function () {
            if ( $( '#tickets_limit_type' ).val() == 'event_level' ) {
                $( '#event_ticket_limit' ).parent().parent().show();

            } else {
                $( '#event_ticket_limit' ).parent().parent().hide();
            }
        } );

        tc_admin.chosen( $( '.tc_wrap select' ) );

        /**
         * INLINE EDIT
         * @param replaceWith
         * @param connectWith
         */
        $.fn.inlineEdit = function ( replaceWith, connectWith ) {

            let clicked;

            $( this ).hover( function () {
                $( this ).addClass( 'inline_hover' );

            }, function () {
                $( this ).removeClass( 'inline_hover' );
            } );

            $( this ).click( function () {

                if ( !$( this ).hasClass( 'inline_clicked' ) ) {

                    clicked = $( this );

                    // Leave a mark on input
                    $( this ).addClass( 'inline_clicked' );

                    let orig_val = $( this ).html();
                    $( replaceWith ).val( $.trim( orig_val ) );

                    $( this ).hide();
                    $( this ).after( replaceWith );
                    replaceWith.focus();

                    /* Update Attendee Information */
                    $( replaceWith ).blur( function () {

                        // Remove a mark of an input
                        clicked.removeClass( 'inline_clicked' );

                        if ( clicked.val() != "" ) {
                            connectWith.val( $( this ).val() ).change();
                            clicked.text( $( this ).val() );
                        }

                        clicked.text( $( this ).val() );

                        var ticket_id = $( this ).parent( 'tr' ).find( '.ID' );
                        ticket_id = ticket_id.attr( 'data-id' );

                        save_attendee_info( ticket_id, $( this ).prev().attr( 'class' ), $( this ).val() );

                        $( this ).remove();
                        clicked.show();

                    } );
                }
            } );
        };

        /**
         * INLINE CHOSEN EDIT
         * @param replaceWith
         * @param connectWith
         */
        $.fn.inlineChosenEdit = function ( replaceWith, connectWith ) {

            let clicked;

            $( this ).hover( function () {
                $( this ).addClass( 'inline_hover' );
            }, function () {
                $( this ).removeClass( 'inline_hover' );
            }
            );

            $( this ).click( function () {

                if ( !$( this ).hasClass( 'inline_clicked' ) ) {

                    clicked = $( this );

                    // Leave a mark on input
                    $( this ).addClass( 'inline_clicked' );

                    // Collect Related Ticket Types
                    let ticket_instance_id = $( this ).siblings( '.ID' ).attr( 'data-id' );
                    $.post( tc_vars.ajaxUrl, {
                        action: 'get_ticket_type_instances',
                        tc_ticket_instance_id: ticket_instance_id,
                        nonce: tc_vars.ajaxNonce
                    }, function ( response ) {
                        if ( !response.error ) {
                            clicked.hide().after( replaceWith );
                            initialize_chosen( replaceWith, response );
                        } else {
                            replaceWith = '<td>' + response.error + '</td>';
                            clicked.hide().after( replaceWith );
                        }
                    } );

                    // Update Attendee Information
                    replaceWith.on( 'change', function () {

                        // Remove a mark to an input
                        clicked.removeClass( 'inline_clicked' );

                        let select_chosen = $( this ).find( ':selected' );

                        if ( select_chosen.val() != "" ) {
                            connectWith.val( select_chosen.val() ).change();
                            clicked.text( select_chosen.text() );
                        }

                        clicked.text( select_chosen.text() );

                        var ticket_id = $( this ).parent( 'tr' ).find( '.ID' );
                        ticket_id = ticket_id.attr( 'data-id' );

                        save_attendee_info( ticket_id, $( this ).prev().attr( 'class' ), select_chosen.val() );

                        $( this ).chosen( 'destroy' ).empty().remove();
                        clicked.show();
                    } );
                }
            } );
        };

        /**
         * Better Order: Update Ticket Instances Metabox
         * @type {$|HTMLElement}
         */
        let replaceWithInput = $( '<input name="temp" class="tc_temp_value" type="text" />' ),
            replaceWithOption = $( '<select class="ticket_type_id_chosen"></select>' ),
            connectWith = $( 'input[name="hiddenField"]' );

        $( '#order-details-tc-metabox-wrapper .order-details tr' ).find( 'td.ticket_type_id:first' ).inlineChosenEdit( replaceWithOption, connectWith );
        $( 'td.first_name, td.last_name, td.owner_email' ).inlineEdit( replaceWithInput, connectWith );

        /**
         * Better Order: Update Temporary fields on keyup
         */
        $( ".tc_temp_value" ).on( 'keyup', function ( e ) {
            if ( e.keyCode == 13 ) {
                $( this ).blur();
            }
            e.preventDefault();
        } );

        /**
         * Initialize Chosen
         * @param elem
         * @param dataSource
         */
        function initialize_chosen( elem, dataSource ) {

            for ( let i = 0; i < dataSource.length; i++ ) {
                elem.append( '<option value="' + dataSource[ i ].id + '">' + dataSource[ i ].text + '</option>' );
            }

            $( ".order-details select" ).chosen( {
                disable_search_threshold: 5,
                allow_single_deselect: false
            } );
        }

        function save_attendee_info( ticket_id, meta_name, meta_value ) {

            $.post( tc_vars.ajaxUrl, {
                action: 'save_attendee_info',
                post_id: ticket_id,
                meta_name: meta_name,
                meta_value: meta_value,
                nonce: tc_vars.ajaxNonce
            } );
        }

        $( document ).ready( function () {

            if ( tc_vars.tc_check_page == 'tc_settings' ) {
                $( ".nav-tab-wrapper" ).sticky( {
                    topSpacing: 30,
                    bottomSpacing: 50
                } );
            }
        } );

        $( window ).resize( function () {
            tc_page_names_width();
        } );

        // JS for trash confirmation
        $( document ).ready( function ( $ ) {

            $( document ).on( 'click', '.post-type-tc_tickets a.submitdelete', function ( e ) {

                e.preventDefault();

                var href = $( this ).attr( 'href' );
                splt_hrf = href.split( '=' );
                splt_hrf = splt_hrf[ 1 ].split( '&' );
                id = splt_hrf[ 0 ];

                $.post( tc_vars.ajaxUrl, {
                    action: "tickera_trash_post_before",
                    trash_id: id,
                    btn_action: 'trash',
                    nonce: tc_vars.ajaxNonce
                }, function ( data ) {

                    var sold = data;

                    if ( sold > 0 ) {

                        if ( sold == 1 ) {
                            var r = confirm( tc_vars.single_sold_ticket_trash_message.replace( "%s", sold ) );

                        } else {
                            var r = confirm( tc_vars.multi_sold_tickets_trash_message.replace( "%s", sold ) );
                        }

                        if ( r ) {
                            window.location = href;
                        }

                    } else {
                        window.location = href;
                    }
                } );
            } );

            $( document ).on( 'click', '#doaction', function ( e ) {

                if ( $( '#bulk-action-selector-top' ).val() == 'trash' ) {

                    e.preventDefault();

                    if ( $( 'input[name="post[]"]:checked' ).length > 0 ) {

                        var tids = [ ];

                        $.each( $( 'input[name="post[]"]:checked' ), function () {
                            tids.push( $( this ).val() );
                        } );

                        $.post( tc_vars.ajaxUrl, {
                            action: "tickera_trash_post_before",
                            multi_trash_id: tids,
                            btn_action: 'multi_trash',
                            nonce: tc_vars.ajaxNonce
                        }, function ( data ) {

                            var sold = data;

                            if ( sold > 0 ) {

                                var r = confirm( tc_vars.multi_check_tickets_trash_message );

                                if ( !r ) {
                                    e.preventDefault();

                                } else {
                                    $( '#doaction' ).unbind( e );
                                    $( '#doaction' ).click();
                                }

                            } else {
                                $( '#doaction' ).unbind( e );
                                $( '#doaction' ).click();
                            }
                        } );
                    }
                }
            } );
        } );

        function tc_page_names_width() {
            $( '.tc_wrap .nav-tab-wrapper ul' ).width( $( '.tc_wrap .nav-tab-wrapper' ).width() );
        }

        tc_page_names_width();

        /**
         * Events Filter - Chosen Field with initial 10 values.
         * Search functionality will be handled by ajax request.
         *
         * Tickera > Ticket Types
         * Tickera > Attendees & Tickets
         */
        $( 'select[name="tc_event_filter"]' ).chosen({
            width: '200px'
        });

        $( 'select[name="tc_event_filter"] ~ .chosen-container .chosen-search' ).prepend( '<div class="tc-loader"></div>' );
        $( 'select[name="tc_event_filter"] ~ .chosen-container .chosen-search input' ).attr( 'placeholder', tc_vars.please_enter_at_least_3_characters );

        window.tc_searching_event_filter = false;
        $( 'select[name="tc_event_filter"] ~ .chosen-container .chosen-search input' ).on( 'keyup', function() {
            let keyword = $( this ).val();
            if ( ! window.searching_event_filter && keyword.length >= 3 ) {
                tc_admin.debounce( function() {
                    $( '.tc-loader' ).show();
                    window.tc_searching_event_filter = true;
                    $.post( tc_vars.ajaxUrl, {
                        action: 'search_event_filter',
                        s: keyword,
                        nonce: tc_vars.ajaxNonce
                    }, function( response ) {
                        window.tc_searching_event_filter = false;
                        if ( response.count ) {
                            $( 'select[name="tc_event_filter"]' ).empty().append( response.options_html ).trigger( 'chosen:updated' )
                        }
                        $( '.tc-loader' ).hide();
                    } );
                }, 1000 )
            }
        } );

        /**
         * API Key - Event Category/Name Fields
         * Dynamically Update a Chosen Fields
         */
        $( '#tc-event-category-field' ).chosen().change( function () {

            let event_name = $( '#tc-event-name-field' ),
                category_value = this.value;

            // Clear all options
            event_name.empty();

            // Temporary Disable Event Field
            event_name.prop( 'disabled', true ).trigger( 'chosen:updated' );

            $.post( tc_vars.ajaxUrl, {
                action: 'change_apikey_event_category',
                event_term_category: this.value,
                nonce: tc_vars.ajaxNonce
            }, function ( response ) {

                if ( 'object' === typeof response ) {

                    // Update select name
                    let new_event_name = event_name.attr( 'name' ).split( '[' )[ 0 ];
                    $( '#tc-event-name-field' ).attr( 'name', new_event_name + '[' + category_value + ']' + '[]' );

                    // Insert new options
                    event_name.append( '<option value="all" selected>All Events</option>' );
                    $.each( response, function ( index, value ) {
                        event_name.append( '<option value=' + index + '>' + value + '</option>' );
                    } );

                    // Enable and update event field with chosen
                    $( '#tc-event-name-field' ).prop( 'disabled', false ).trigger( 'chosen:updated' );
                }
            } );
        } );

        /**
         * Export Attendees PDF
         * Settings > Export PDF
         */
        $( '#export_event_data' ).on( 'click', function ( event ) {
            event.preventDefault();
            tc_export_pdf.export();
        } );

        /**
         * API Access
         * Settings > API Access
         */
        tc_admin.settings_accordion( {
            form: $( '#poststuff.tc-api-form' ),
            actions: $( '#poststuff.tc-api-actions' ),
            add: $( '#poststuff.tc-api-actions #add_new_api_key' ),
            cancel: $( '#poststuff.tc-api-form #cancel_add_edit' )
        } );

        /**
         * Discount Codes
         * Tickera > Discount Codes
         */
        tc_admin.settings_accordion( {
            form: $( '#poststuff.tc-discount-form' ),
            actions: $( '#poststuff.tc-discount-actions' ),
            add: $( '#poststuff.tc-discount-actions #add_new_discount_code' ),
            cancel: $( '#poststuff.tc-discount-form #cancel_add_edit' )
        } );
    } );

    const tc_export_pdf = {

        export: async function ( page ) {

            page = ( typeof page !== 'undefined' ) ? page : 1;

            let eventId = $( 'select[name="tc_export_event_data"]' ).val(),
                exportDataButton = $( '#export_event_data' ),
                progressBar = exportDataButton.closest( 'form' ).find( '.tc-progress-bar' ),
                nonce = tc_vars.ajaxNonce;

            await $.post( tc_vars.ajaxUrl, { action: 'prepare_export_data', event_id: eventId, page: page, nonce: nonce }, function ( response ) {

                if ( typeof response !== 'undefined' && response.success ) {
                    tc_export_pdf.export( response.page );
                    progressBar.progressbar( { value: response.progress } ).show();
                    exportDataButton.hide();

                } else {
                    exportDataButton.closest( 'form' ).submit();
                    progressBar.progressbar( { value: 0 } ).hide();
                    exportDataButton.show();
                }
            } )
        }
    }

    const tc_admin = {

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

        settings_accordion: function ( attr ) {

            let form = {
                container: attr.form,
                actions: attr.actions,
                containerHeight: attr.form.outerHeight(),
                actionsHeight: attr.actions.outerHeight()
            };

            // Insert identifiers
            form.container.addClass( 'tc-accordion' );
            form.actions.addClass( 'tc-accordion tc-accordion-actions' );

            // Show action container as default. Set initial container max-height.
            if ( form.container.length && form.actions.length ) {
                form.container.css( { 'max-height': 0, 'display': 'block' } );
                form.actions.css( { 'max-height': form.actionsHeight } );
            }

            // Edit
            if ( form.container.hasClass( 'tc-edit' ) ) {
                form.container.css( { 'max-height': 'inherit' } );
                form.actions.css( 'max-height', 0 );
            }

            // Add
            attr.add.on( 'click', function ( e ) {
                e.preventDefault();
                form.container.css( { 'max-height': form.containerHeight } );
                form.actions.css( 'max-height', 0 );

                setTimeout( function () {
                    form.container.css( { 'max-height': 'inherit' } );
                }, 500 );
            } );

            // Cancel
            attr.cancel.on( 'click', function ( e ) {
                e.preventDefault();
                form.containerHeight = form.container.outerHeight();
                form.container.css( 'max-height', form.containerHeight );

                setTimeout( function () {
                    form.container.css( 'max-height', 0 );
                    form.actions.css( 'max-height', form.actionsHeight );
                }, 100 );
            } );
        },

        chosen: function ( element ) {

            element.chosen( {
                disable_search_threshold: 5,
                allow_single_deselect: false
            } );
        },

        /**
         * Fix chosen container's styles (e.g width) after values are updated.
         *
         * @param element
         * @since 3.5.1.1
         */
        fix_chosen: function ( element ) {

            if ( typeof element !== 'undefined' ) {
                element.css( { 'width': '25em', 'display': 'block' } ).chosen( { disable_search_threshold: 5, allow_single_deselect: false } );
                element.css( 'display', 'none' );
                element.next( '.chosen-container' ).css( { 'width': '100%', 'max-width': '25em', 'min-width': '1em' } );

            } else {
                $( '.tc_wrap select' ).css( { 'width': '25em', 'display': 'block' } ).chosen( { disable_search_threshold: 5, allow_single_deselect: false } );
                $( '.tc_wrap select' ).css( 'display', 'none' );
                $( '.tc_wrap .chosen-container' ).css( { 'width': '100%', 'max-width': '25em', 'min-width': '1em' } );
            }
        },

        /**
         * Initialize dynamic fields
         */
        init_dynamic_fields: function () {

            $( '.tc-dynamic-fields' ).each( function () {

                let childField = $( this ),
                    childFieldContainer = childField.closest( 'div' ),
                    childFieldParentContainer = childField.closest( '.inside' ),
                    parentField = childField.data( 'dynamic-field-name' ),
                    fieldFunction = childField.data( 'dynamic-field-function' );

                $( '[name="' + parentField + '"]' ).on( 'change', function () {

                    // Disable enter key/submit until ajax responses.
                    $( this ).on( 'keypress', function ( e ) {
                        if ( 13 === e.keyCode ) {
                            e.preventDefault();
                        }
                    } );

                    // Disable Save/Update button until ajax responses
                    $( 'input[name="save"]' ).prop( 'disabled', true );

                    // Disable target field until ajax responses
                    childFieldParentContainer.addClass( 'tc-disable' );

                    $.post( tc_vars.ajaxUrl, { action: 'get_' + fieldFunction + '_ajax', value: $( this ).val() }, function ( response ) {

                        if ( typeof response !== 'undefined' ) {

                            /**
                             * Disable field
                             */
                            if ( typeof response.disable !== 'undefined' && response.disable ) {
                                childFieldContainer.addClass( 'tc-disable' );

                            } else {
                                childFieldContainer.removeClass( 'tc-disable' );
                            }

                            /**
                             * Refresh field with new values
                             */
                            childField.html( response.value );

                        } else {
                            childField.empty();
                        }

                        // Enable Save/Update button
                        $( 'input[name="save"]' ).prop( 'disabled', false );

                        // Enable target field
                        childFieldParentContainer.removeClass( 'tc-disable' );
                    } );
                } );
            } );
        },

        /**
         * Initialize Conditionals
         */
        init_conditional: function () {

            $( '.tc_conditional' ).each( function () {
                tc_admin.conditions( $( this ) );
            } );

            $( document ).on( 'change', '.has_conditional', function () {

                $( '.tc_conditional' ).each( function () {
                    tc_admin.conditions( $( this ) );
                } );
            } );
        },

        /**
         * Check if the field has a condition and execute the action based on its values.
         *
         * @param obj
         */
        conditions: function ( obj ) {

            let field_name = obj.attr( 'data-condition-field_name' ),
                selected_value;

            if ( !$( '.' + field_name ).hasClass( 'has_conditional' ) ) {
                $( '.' + field_name ).addClass( 'has_conditional' );
            }

            let field_type = obj.attr( 'data-condition-field_type' ),
                value = obj.attr( 'data-condition-value' ),
                action = obj.attr( 'data-condition-action' );

            switch ( field_type ) {

                case 'radio':
                    selected_value = $( '.' + field_name + ':checked' ).val();
                    break;

                case 'checkbox':
                    selected_value = $( '.' + field_name ).is( ':checked' );
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
                    obj.hide().attr( 'disabled', true );
                    $( '#' + obj.attr( 'id' ) + '-error' ).remove();

                } else if ( 'show' == action ) {
                    obj.show().attr( 'disabled', false );
                }

            } else {

                if ( 'hide' == action ) {
                    obj.show().attr( 'disabled', false );

                } else if ( 'show' == action ) {
                    obj.hide().attr( 'disabled', true );
                    $( '#' + obj.attr( 'id' ) + '-error' ).remove();
                }
            }

            tc_admin.fix_chosen();
        }
    }

    /**
     * Process Bulk Deletion of Tickets
     * Tickera > Settings > Delete Info
     * @type {{process_delete: ((function(*, *): Promise<void>)|*), loading: Window.tc_dl.loading}}
     * @since 3.5.2.3
     */
    window.tc_dl = {

        loading: function( loading ) {

            switch ( loading ) {
                case true:
                    $('.tccrr-loader').css( { 'display': 'inline-block' } );
                    break;

                default:
                    $('.tccrr-loader').css( { 'display': 'none' } );
            }
        },

        process_delete: async function( page, prev_deleted ) {

            var page = ( typeof page !== 'undefined' ) ? page : 1,
                prev_deleted = ( typeof prev_deleted !== 'undefined' ) ? prev_deleted : 0;

            await $.post( tc_vars.ajaxUrl, {
                action: 'tc_dl_delete_tickets',
                event_ids: $('#tc_dl_event_filter select').chosen().val(),
                delete_orders: $( 'input[name="delete_orders"]:checked' ).val(),
                page: page,
                prev_deleted: prev_deleted

            }, function ( response ) {

                if ( typeof response !== 'undefined') {

                    if ( typeof response.page !== 'undefined' ) {
                        $( '.tc_dl_notice' ).removeClass( 'tc-dl-error' ).addClass( 'tc-dl-notice' ).text( response.deleted + ' ' + tc_vars.tickets_have_been_removed );
                        tc_dl.process_delete( ( response.page + 1 ), response.deleted );

                    } else {

                        // Process completed
                        let notice = $( '.tc_dl_notice' ).text();
                        $( '.tc_dl_notice' ).removeClass( 'tc-dl-error' ).addClass( 'tc-dl-notice' ).text( notice );
                        tc_dl.loading( false );
                    }

                } else {
                    $( '.tc_dl_notice' ).removeClass( 'tc-dl-notice' ).addClass( 'tc-dl-error' ).text( tc_vars.something_went_wrong );
                }
            });
        }
    };

  setTimeout(function(){
    jQuery('.tc-notice-bridge .notice-dismiss').click(function(){
      jQuery.ajax({
         type : "post",
         dataType : "json",
         url : tc_vars.ajaxUrl,
         data : {action: "tc_remove_notification"},
         success: function(response) {
         }
      });

            if(tc_vars.check_for_cookie == ''){
              jQuery(".themes-php .themes").prepend("<div class='theme themetick-adv'><button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button>"
              +"<div class='theme-screenshot'><img src='"+tc_vars.plugin_uri+"/images/themetick-background-themes.png' /></div>"
              +"<div class='theme-text'><h3>Unlock 40% discount on Themetick Themes!</h3>"
              +"<p>Elevate your website's aesthetics and functionality with specially crafted themes, "
              +"designed to integrate seamlessly with Tickera.<br />"
              +"<br />Use discount code: <strong>W4PTCK0</strong></p>"
              +"</div>"
          		+"<div class='theme-actions-themetick theme-id-container'>"
        			+"<a target='_blank' class='button button-primary customize load-customize hide-if-no-customize' href='https://themetick.com/'>CLAIM MY 40% OFF</a>"
              +"</div>"
              +"</div>");

              jQuery('.themetick-adv .notice-dismiss').click(function(){
                jQuery('.themetick-adv').remove();
                jQuery.ajax({
                   type : "post",
                   dataType : "json",
                   url : tc_vars.ajaxUrl,
                   data : {action: "tc_remove_notification_theme"},
                   success: function(response) {
                   }
                });
              });


            }

          },500);


} )( jQuery );
