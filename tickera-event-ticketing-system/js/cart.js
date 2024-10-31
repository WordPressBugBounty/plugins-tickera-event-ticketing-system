( function( $ ) {

    /**
     * Hook to $( window ).load() to correctly capture the rendered attribute values.
     * @since 3.5.1.2
     */
    $( window ).ready( function() {

        /**
         * Payment Method selection.
         * Expand the first Payment Method in Payment Page.
         */
        setTimeout( function() {
            let selected_method = $( 'input.tc_choose_gateway:checked' ).val(),
                max_height = $( 'div#' + selected_method + ' > .inner-wrapper' ).outerHeight();
            $( 'div#' + selected_method ).css( { 'max-height': max_height + 'px' } );
        }, 500 );
    });

    $( document ).ready( function() {

        const tc_cart = {

            quantity: function() {

                var quantity = 0;
                $( 'input[name="ticket_quantity[]"]' ).each( function() {
                    quantity = quantity + parseInt( $( this ).val() );
                } );

                return quantity;
            },

            /**
             * Initialize Listeners
             */
            init: function() {
                tc_cart.tc_empty_cart();
                tc_cart.tc_cart_listeners();
            },

            /**
             * Listeners for add item to cart
             *
             * @returns {undefined}
             */
            tc_cart_listeners: function() {

                $( 'body' ).on( 'click', 'input.tc_button_addcart', function() {

                    var input = $( this ),
                        formElm = $( input ).parents( 'form.tc_buy_form' ),
                        tempHtml = formElm.html(),
                        serializedForm = formElm.serialize();

                    formElm.html( '<img src="' + tc_ajax.imgUrl + '" alt="' + tc_ajax.addingMsg + '" />' );

                    $.post( tc_ajax.ajaxUrl, serializedForm, function( data ) {

                        var result = data.split( '||', 2 );

                        if ( 'error' == result[ 0 ] ) {

                            alert( result[ 1 ] );
                            formElm.html( tempHtml );
                            tc_cart.tc_cart_listeners();

                        } else {

                            formElm.html( '<span class="tc_adding_to_cart">' + tc_ajax.successMsg + '</span>' );
                            $( 'div.tc_cart_widget_content' ).html( result[ 1 ] );

                            if ( result[ 0 ] > 0 ) {

                                formElm.fadeOut( 2000, function() {
                                    formElm.html( tempHtml ).fadeIn( 'fast' );
                                    tc_cart.tc_cart_listeners();
                                } );

                            } else {
                                formElm.fadeOut( 2000, function() {
                                    formElm.html( '<span class="tc_no_stock">' + tc_ajax.outMsg + '</span>' ).fadeIn( 'fast' );
                                    tc_cart.tc_cart_listeners();
                                } );
                            }

                            tc_cart.tc_empty_cart(); // Re-init empty script as the widget was reloaded
                        }
                    } );
                    return false;
                } );
            },

            /**
             * Empty Cart
             *
             * @returns {undefined}
             */
            tc_empty_cart: function() {

                if ( $( 'a.tc_empty_cart' ).attr( 'onClick' ) != undefined ) {
                    return;
                }

                $( 'body' ).on( 'click', 'a.tc_empty_cart', function() {

                    var answer = confirm( tc_ajax.empty_cart_message );

                    if ( answer ) {

                        $( this ).html( '<img src="' + tc_ajax.imgUrl + '" />' );

                        $.post( tc_ajax.ajaxUrl, { action: 'mp-update-cart', empty_cart: 1 }, function( data ) {
                            $( 'div.tc_cart_widget_content' ).html( data );
                        } );
                    }

                    return false;
                } );
            }
        };

        /**
         * Check age restriction.
         * Woocommerce + Bridge for Woocommerce
         */
        $( 'form.checkout' ).on( 'checkout_place_order', function( event ) {
            if ( $( '#tc_age_check' ).length !== 0 ) {
                if ( false == $( '#tc_age_check' ).is( ':checked' ) ) {
                    $( '.tc-age-check-error' ).remove();
                    $( '.tc-age-check-label' ).append( '<span class="tc-age-check-error">' + tc_ajax.tc_error_message + '</span>' );
                    $( 'html, body' ).stop().animate( { 'scrollTop': ( $( '#tc_age_check' ).offset().top ) - 100 }, 350, 'swing', function() { window.location.hash = target; });
                    return false;
                }
            }
        } );


        if ( $( '.tc_cart_widget' ).length > 0 ) {

            function tc_update_cart_ajax() {

                $( '.tc_cart_ul' ).css( 'opacity', '0.5' );

                // Since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                $.post( tc_ajax.ajaxUrl, {
                    action: 'tickera_update_widget_cart',
                    nonce: tc_ajax.ajaxNonce
                }, function( response ) {
                    $( '.tc_cart_ul' ).css( 'opacity', '1' );
                    $( '.tc_cart_ul' ).html( '' );
                    $( '.tc_cart_ul' ).html( response );
                } );
            }

            // Listen DOM changes
            $( '.event_tickets, .cart_form' ).bind( 'DOMSubtreeModified', tc_update_cart_ajax );
        }

        $( document ).on( 'submit', '#tc_payment_form', function() {
            $( '#tc_payment_confirm' ).attr( 'disabled', 'disabled' );
        } );

        /**
         * Increase the quantity
         */
        $( 'body' ).on( 'click', 'input.tickera_button.plus', function() {
            let parentContainer = $( this ).closest( 'td' ),
                quantity = parentContainer.find( '.quantity' ).val();
            parentContainer.find( '.quantity' ).val( parseInt( quantity ) + 1 );
        } );

        /**
         * Decrease the quantity
         */
        $( 'body' ).on( 'click', 'input.tickera_button.minus', function() {
            let parentContainer = $( this ).closest( 'td' ),
                quantity = parentContainer.find( '.quantity' ).val();
            if ( quantity >= 1 ) {
                parentContainer.find( '.quantity' ).val( parseInt( quantity ) - 1 );
            }
        } );

        /**
         * When user clicks on the empty cart button
         */
        $( 'body' ).on( 'click', '#empty_cart', function( event ) {

            let proceed = confirm( tc_ajax.empty_cart_confirmation );

            if ( proceed ) {
                $( 'input[name="cart_action"]' ).val( 'empty_cart' );

            } else {
                event.preventDefault();
            }
        } );

        /**
         * When user clicks on the update button
         */
        $( 'body' ).on( 'click', '#update_cart', function() {
            $( 'input[name="cart_action"]' ).val( 'update_cart' );
        } );

        /**
         * Toggle Customer Age Checkbox
         */
        $( document ).on( 'change', '#tc_age_check', function() {

            if ( $( this ).is( ':checked' ) ) {
                $( this ).removeClass( 'has-error' );
                $( '.tc-age-check-error' ).remove();

            } else {
                $( this ).addClass( 'has-error' );
                $( '.tc-age-check-error' ).remove();
                $( this ).parent().append( '<span class="tc-age-check-error">' + tc_ajax.tc_error_message + '</span>' );
            }
        } );

        /**
         * Tickera Standalone
         * When user click on the proceed to checkout button,
         */
        var current_quantity = tc_cart.quantity();
        $( document ).on( 'click', '#proceed_to_checkout', function( event ) {

            // Make sure to update the cart if there's some changes before moving to checkout page.
            let input_quantity = tc_cart.quantity();
            if ( typeof tc_cart.quantity() === 'undefined' || 0 == tc_cart.quantity() || current_quantity != input_quantity ) {
                event.preventDefault();

                let target = $( '.tc_cart_errors' );
                if ( target.find( 'ul' ).length ) {
                    target.find( 'ul' ).append( '<li>' + tc_ajax.update_cart_message + '</li>' );

                } else {
                    target.html( '<ul><li>' + tc_ajax.update_cart_message + '</li></ul>' );
                }

                $( 'html, body' ).stop().animate( { 'scrollTop': ( target.offset().top ) - 40 }, 350, 'swing', function() {
                    window.location.hash = target;
                } );
            }

            // Make sure confirm age before proceeding to checkout
            if ( $( '#tc_age_check' ).length ) {
                let age_confirmation_field = $( '#tc_age_check' );
                if ( age_confirmation_field.is( ':checked' ) ) {
                    age_confirmation_field.removeClass( 'has-error' );
                    $( '.tc-age-check-error' ).remove();

                } else {
                    event.preventDefault();
                    $( '.tc-age-check-error' ).remove();
                    age_confirmation_field.addClass( 'has-error' );
                    age_confirmation_field.closest( 'label' ).append( '<span class="tc-age-check-error">' + tc_ajax.tc_error_message + '</span>' );
                }
            }
        } );

        /**
         * When user click on the proceed to checkout button
         */
        $( 'body' ).on( 'click', '#apply_coupon', function() {
            $( 'input[name="cart_action"]' ).val( 'apply_coupon' );
        } );

        /**
         * Add to cart button
         */
        $( 'body' ).on( 'click', 'a.add_to_cart', function( event ) {

            event.preventDefault();

            $( this ).fadeOut( 'fast' ).fadeIn( 'fast' );

            var button_type = $( this ).attr( 'data-button-type' ),
                open_method = $( this ).attr( 'data-open-method' ),
                current_form = $( this ).parents( 'form.cart_form' ),
                parent_container = current_form.parent(),
                ticket_id = current_form.find( '.ticket_id' ).val(),
                qty = $( this ).closest( 'tr' ).find( '.tc_quantity_selector' ).val(),
                nonce = $( this ).closest( 'form.cart_form' ).find( '[name="nonce"]' ).val();

            qty = ( typeof qty === 'undefined' ) ? $( this ).closest( '.cart_form' ).find( '.tc_quantity_selector' ).val() : qty;

            $.post( tc_ajax.ajaxUrl, { action: 'add_to_cart', ticket_id: ticket_id, tc_qty: qty, nonce: nonce }, function( data ) {

                if ( 'error' != data ) {

                    parent_container.html( data );

                    if ( $( '.tc_cart_contents' ).length > 0 ) {
                        $.post( tc_ajax.ajaxUrl, { action: 'update_cart_widget', nonce: nonce }, function( widget_data ) {
                            $( '.tc_cart_contents' ).html( widget_data );
                        } );
                    }

                    if ( 'new' == open_method && 'buynow' == button_type ) {
                        window.open( tc_ajax.cart_url, '_blank' );
                    }

                    if ( 'buynow' == button_type && 'new' !== open_method ) {
                        window.location = tc_ajax.cart_url;
                    }

                } else {
                    parent_container.html( data );
                }
            } );
        } );

        /**
         * Cart Widget
         */
        $( 'body' ).on( 'click', '.tc_widget_cart_button', function() {
            window.location.href = $( this ).data( 'url' );
        } );

        /**
         * Proceed to checkout button
         */
        $( 'body' ).on( 'click', '#proceed_to_checkout', function() {
            $( 'input[name="cart_action"]' ).val( 'proceed_to_checkout' );
        } );

        /**
         * Check email-verification for owner field with Woocommerce
         */
        $( 'form.checkout' ).on( 'click', 'button[type="submit"][name="woocommerce_checkout_place_order"]', function() {

            // Disable "Place Order" button
            var owner_email = $( '.tc_owner_email' ).val(),
                owner_confirm_email = $( '.tc_owner_confirm_email' ).val();

            if ( ( owner_email && owner_confirm_email ) ) {

                if ( ( owner_email !== owner_confirm_email ) || owner_email === "" || owner_confirm_email === "" ) {
                    $( '.tc_owner_email,.tc_owner_confirm_email' ).css( 'border-left', '2px solid #ff0000' );

                } else {
                    $( '.tc_owner_email,.tc_owner_confirm_email' ).css( 'border-left', '2px solid #09a10f' );
                }
            }
        } );

        /**
         * Payment Method selection.
         * Accordion effect in Payment Page.
         */
        $( document ).on( 'change', '.tickera-payment-gateways input.tc_choose_gateway', function() {

            let selected_method = $( 'input.tc_choose_gateway:checked' ).val(),
                parent_container = $( this ).closest( '.tickera-payment-gateways' ),
                max_height = parent_container.find( 'div#' + selected_method + ' > .inner-wrapper' ).outerHeight();

            $( '.tickera-payment-gateways' ).removeClass( 'active' );
            parent_container.addClass( 'active' );

            parent_container.siblings().find( '.tc_gateway_form' ).removeAttr( 'style' );
            $( 'div#' + selected_method ).css( { 'max-height': max_height + 'px' } );
        });

        /**
         * Tickera Cart: Preventing Default button to trigger on Enter Key
         */
        $( document ).on( 'keypress', '#tickera_cart input', function( e ) {
            if ( 13 === e.keyCode ) {
                e.preventDefault();
                $( '#proceed_to_checkout' ).trigger( 'click' );
            }
        } );

        /**
         * Payment Gateway Page - Frontend
         */
        $( document ).on( 'keypress', '.tc-numbers-only', function( e ) {
            if ( e.which != 8 && e.which != 0 && ( e.which < 48 || e.which > 57 ) ) {
                return false;
            }
        } );

        /**
         * Update add to cart link value/ticket type id
         * Event Tickets - Shortcode
         * Display Type: Dropdown
         */
        $( document ).on( 'change', '.tc-event-dropdown-wrap select.ticket-type-id', function() {

            let wrapper = $( this ).closest( '.tc-event-dropdown-wrap' ),
                ticketId = $( this ).val(),
                actionsContainer = wrapper.find( '.actions' ),
                addToCartContainer = actionsContainer.find( '[id="ticket-type-' + ticketId + '"]' );

            addToCartContainer.prependTo( actionsContainer );
        });
    } );

    /**
     * Tickera Standalone, Woocommerce + Bridge for Woocommerce
     * Check custom form error notification
     */
    $( document ).ready( function() {

        /**
         * Cart/Checkout Form validation
         */
        if ( $( 'form#tickera_cart' ).length || $( 'form.checkout' ).length ) {

            $.validator.addMethod( 'alphanumericOnly', function( value, element ) {
                if ( ! value ) return true; // Valid if empty value
                let regex = new RegExp( "^[^<?=^>]+$" );
                return ( !regex.test( value ) ) ? false : true;
            }, tc_ajax.alphanumeric_characters_only );

            $( 'form#tickera_cart, form.checkout' ).validate( {
                debug: false,
                errorClass: 'has-error',
                validClass: 'valid',
                highlight: function( element, errorClass, validClass ) {
                    $( element ).addClass( errorClass ).removeClass( validClass );
                },
                unhighlight: function( element, errorClass, validClass ) {
                    $( element ).removeClass( errorClass ).addClass( validClass );
                }
            } );

            $( '.tickera-input-field' ).each( function() {

                let field = $( this ),
                    field_type = field.attr( 'type' ),
                    field_name = field.attr( 'name' );

                if ( ( ( 'text' == field_type && ! field.hasClass( 'checkbox_values' ) ) // Include Text but not checkbox_values
                    || field.is( 'textarea' ) ) // Include Textarea
                    && 'coupon_code' != field_name ) { // Don't include discount field
                    // $( this ).rules( 'add', {
                    //     alphanumericOnly: true
                    // } );
                }
            } );

            $( 'input[name="tc_cart_required[]"]' ).each( function() {
                let field = $( this ).closest( 'div' ).find( '.tickera-input-field:not( input[type="checkbox"] ):not( input[type="radio"] )' );
                field.rules( 'add', {
                    required: {
                        depends: function() {

                            let trimmedValue = $( this ).val().trim();

                            if ( ! trimmedValue ) {
                                $( this ).val( $.trim( $( this ).val() ) );
                                return true;

                            } else {
                                return false;
                            }
                        }
                    },
                    messages: {
                        required: tc_jquery_validate_library_translation.required
                    }
                } );
            } );

            $( '.tc_validate_field_type_email' ).each( function() {
                $( this ).rules( 'add', {
                    email: true,
                    messages: {
                        email: tc_jquery_validate_library_translation.email
                    }
                } );
            } );

            $( '.tc_validate_field_type_confirm_email' ).each( function() {
                $( this ).rules( 'add', {
                    email: true,
                    equalTo: '.tc_validate_field_type_email',
                    messages: {
                        email: tc_jquery_validate_library_translation.email,
                        equalTo: tc_jquery_validate_library_translation.equalTo
                    }
                } );
            } );

            $( '.tc_owner_email' ).each( function() {
                $( this ).rules( 'add', {
                    email: true,
                    messages: {
                        email: tc_jquery_validate_library_translation.email
                    }
                } );
            } );

            $( '.tc_owner_confirm_email' ).each( function() {

                let owner_email_name = $( this ).attr( 'name' );
                owner_email_name = owner_email_name.replace( '_confirm', '' );

                $( this ).rules( 'add', {
                    email: true,
                    equalTo: 'input[name="' + owner_email_name + '"]',
                    messages: {
                        email: tc_jquery_validate_library_translation.email,
                        equalTo: tc_jquery_validate_library_translation.equalTo
                    }
                } );
            } );

            /**
             * Update checkbox values on field change.
             */
            $( document ).on( 'change', '.buyer-field-checkbox, .owner-field-checkbox', function( e ) {

                var field_values = $( this ).closest( 'div' ).find( '.checkbox_values' ),
                    values = field_values.val().split( ',' );

                if ( $( this ).is( ':checked' ) ) {
                    values[ values.length ] = $( this ).val();
                    field_values.removeClass( 'has-error' ).addClass( 'valid' );

                } else {
                    var toRemove = $( this ).val();
                    values = $.grep( values, function( value ) {
                        return value != toRemove;
                    } )
                }

                field_values.val( values.filter( e => e ).join() ).focus().blur();
            } );

            /**
             * Update radio validation field
             */
            $( document ).on( 'change', '.buyer-field-radio, .owner-field-radio', function( e ) {

                var fieldWrapper = $( this ).closest( 'div' ),
                    validationField = fieldWrapper.find( '.validation' );

                if ( fieldWrapper.find( 'input[type="radio"]:checked' ).length > 0 ) {
                    validationField.val( true );
                    validationField.removeClass( 'has-error' ).addClass( 'valid' );
                    validationField.next( '.has-error' ).remove();
                }
            } );
        }
    } );
} )( jQuery );
