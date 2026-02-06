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

        /**
         * Provides additional focus functionality.
         * This method can be used to enhance or customize the behavior of the
         * focus event on selected elements in the DOM.
         */
        $.fn.tcFocus = function () {
            return this.each(function () {
                var $element = $(this);
                $( '.tc-focus' ).removeClass( 'tc-focus' );
                $element.addClass( 'tc-focus' );
            });
        };

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
         * =======================================================================
         * Check age restriction.
         * Woocommerce + Bridge for Woocommerce
         * =======================================================================
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
         * =======================================================================
         * Handles cart page and Events - Add to cart quantity field incrementation.
         * =======================================================================
         */
        $( 'body' ).on( 'click', 'input.tickera_button.plus', function() {

            let parentContainer = $( this ).parent(),
                quantitySelector = parentContainer.find( '.tc_quantity_selector' ),
                quantity = parseInt( quantitySelector.val() );

            let min = typeof quantitySelector.attr( 'min' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'min' ) ) : 1,
                max = typeof quantitySelector.attr( 'max' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'max' ) ) : 999,
                step = typeof quantitySelector.attr( 'step' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'step' ) ) : 1;

            quantity += step;

            if ( quantity < min ) {
                quantity = min;

            } else if ( quantity > max ) {
                quantity = max;

            } else if ( quantity < 0) {
                quantity = 0
            }

            parentContainer.find( '.tc_quantity_selector' ).val( quantity );
        } );

        /**
         * =======================================================================
         * Handles cart page and Events - Add to cart quantity field decrementation.
         * =======================================================================
         */
        $( 'body' ).on( 'click', 'input.tickera_button.minus', function() {

            let parentContainer = $( this ).parent(),
                quantitySelector = parentContainer.find( '.tc_quantity_selector' ),
                quantity = parseInt( quantitySelector.val() );

            let min = typeof quantitySelector.attr( 'min' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'min' ) ) : 1,
                max = typeof quantitySelector.attr( 'max' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'max' ) ) : 999,
                step = typeof quantitySelector.attr( 'step' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'step' ) ) : 1;

            quantity -= step;

            if ( quantity < min ) {
                quantity = min;

            } else if ( quantity > max ) {
                quantity = max;

            } else if ( quantity < 0) {
                quantity = 0
            }

            parentContainer.find( '.tc_quantity_selector' ).val( quantity );
        } );

        $( 'body' ).on( 'input', 'form#tickera_cart .tc_quantity_selector, form#tickera_cart .tickera-input-field[inputmode="numeric"]', function() {
            this.value = this.value.replace(/\D/g, '');
        } );


        /**
         * =======================================================================
         * Handles quantity field incrementation/decrementation on focus.
         * =======================================================================
         */
        $( 'body' ).on( 'keydown', 'form#tickera_cart .tickera-input-field[inputmode="numeric"], .tc_quantity_selector', function( e ) {

            if ( e.keyCode === 38 || e.keyCode === 40 ) {

                e.preventDefault();

                let min = typeof $( this ).attr( 'min' ) !== 'undefined' ? parseInt( $( this ).attr( 'min' ) ) : 1,
                    max = typeof $( this ).attr( 'max' ) !== 'undefined' ? parseInt( $( this ).attr( 'max' ) ) : 999,
                    step = typeof $( this ).attr( 'step' ) !== 'undefined' ? parseInt( $( this ).attr( 'step' ) ) : 1;

                this.value = ( isNaN( this.value ) || this.value === '' ) ? min : parseInt( this.value );

                switch( e.keyCode ) {

                    case 38: // Arrow key up
                        e.preventDefault();
                        this.value = parseInt( this.value ) + step;
                        break;

                    case 40: // Arrow key down
                        e.preventDefault();
                        this.value = parseInt( this.value ) - step;
                        break;
                }

                if ( this.value < min ) {
                    this.value = min;

                } else if ( this.value > max ) {
                    this.value = max;

                } else if ( this.value < 0) {
                    this.value = 0
                }
            }
        } );

        /**
         * =======================================================================
         * Handles quantity field value to trim leading zero value (e.g 01 to 1)
         * =======================================================================
         */
        $( 'body' ).on( 'focusout', 'form#tickera_cart .tickera-input-field[inputmode="numeric"], .tc_quantity_selector', function( e ) {
            this.value = Number( this.value );
        } );

        /**
         * =======================================================================
         * When user clicks on the empty cart button
         * =======================================================================
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
         * =======================================================================
         * When user clicks on the update button
         * =======================================================================
         */
        $( 'body' ).on( 'click', '#update_cart', function() {
            $( 'input[name="cart_action"]' ).val( 'update_cart' );
        } );

        /**
         * =======================================================================
         * Toggle Customer Age Checkbox
         * =======================================================================
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
         * =======================================================================
         * Tickera Standalone
         * When user click on the proceed to checkout button
         * =======================================================================
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
         * =======================================================================
         * When user click on the proceed to checkout button
         * =======================================================================
         */
        $( 'body' ).on( 'click', '#apply_coupon', function() {
            $( 'input[name="cart_action"]' ).val( 'apply_coupon' );
        } );

        /**
         * =======================================================================
         * Add to cart button
         * =======================================================================
         */
        $( 'body' ).on( 'click', 'a.add_to_cart', function( event ) {

            event.preventDefault();

            let btn = $( this );
            btn.fadeOut( 'fast' ).fadeIn( 'fast' );

            var button_type = btn.attr( 'data-button-type' ),
                open_method = btn.attr( 'data-open-method' ),
                current_form = btn.closest( 'form.cart_form' ),
                parent_container = current_form.parent(),
                ticket_id = current_form.find( '.ticket_id' ).val(),
                qtySelector = btn.closest( 'tr' ).find( '.tc_quantity_selector' );

            // Dropdown quantity selector
            if ( qtySelector.length === 0 ) {
                qtySelector = current_form.find( '.tc_quantity_selector' );
            }

            let qty = qtySelector.val(),
                nonce = btn.closest( 'form.cart_form' ).find( '[name="nonce"]' ).val();

            qty = ( typeof qty === 'undefined' ) ? btn.closest( '.cart_form' ).find( '.tc_quantity_selector' ).val() : qty;

            $.post( tc_ajax.ajaxUrl, { action: 'add_to_cart', ticket_id: ticket_id, tc_qty: qty, nonce: nonce }, function( data ) {

                // btn.tc_tooltip( { 'detach': true } );

                if ( 'error' != data ) {

                    parent_container.html( data );

                    let cart = parent_container.find( '.tc_in_cart a');
                    // cart.tc_tooltip();

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
         * =======================================================================
         * Cart Widget
         * =======================================================================
         */
        $( 'body' ).on( 'click', '.tc_widget_cart_button', function() {
            window.location.href = $( this ).data( 'url' );
        } );

        /**
         * =======================================================================
         * Proceed to checkout button
         * =======================================================================
         */
        $( 'body' ).on( 'click', '#proceed_to_checkout', function() {
            $( 'input[name="cart_action"]' ).val( 'proceed_to_checkout' );
        } );

        /**
         * =======================================================================
         * Check email-verification for owner field with Woocommerce
         * =======================================================================
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
         * =======================================================================
         * Payment Method selection.
         * Accordion effect in Payment Page.
         * =======================================================================
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
         * =======================================================================
         * Handles keypress 'enter' key in cart page.
         * =======================================================================
         */
        $( document ).on( 'keypress', '#tickera_cart input', function( e ) {

            if ( 13 === e.keyCode ) {

                e.preventDefault();

                if ( $( this ).hasClass( 'quantity' ) ) {
                    $( '#update_cart' ).trigger( 'click' );

                } else if ( $( this ).hasClass( 'coupon_code' ) ) {
                    $( '#apply_coupon' ).trigger( 'click' );

                } else if ( $( this ).hasClass( 'tickera_empty' ) ) {
                    $( '#empty_cart' ).trigger( 'click' );
                }
            }
        } );

        /**
         * =======================================================================
         * Handles keypress 'enter' key in event add-to-cart table.
         * =======================================================================
         */
        $( document ).on( 'keypress', '.event_tickets.tickera .tc_quantity_selector', function( e ) {
            if ( 13 === e.keyCode ) {
                e.preventDefault();
                let ticketRow = $( this ).closest( 'tr' ),
                    addToCart = ticketRow.find( '.add_to_cart' );
                addToCart.trigger( 'click' );
            }
        } );

        /**
         * =======================================================================
         * Handles keypress 'enter' key in event add-to-cart dropdown.
         * =======================================================================
         */
        $( document ).on( 'keypress', '.tc-event-dropdown-wrap .tc_quantity_selector, .tc-add-to-cart-wrap .tc_quantity_selector', function( e ) {
            if ( 13 === e.keyCode ) {
                e.preventDefault();
                let ticketRow = $( this ).closest( 'form.cart_form' ),
                    addToCart = ticketRow.find( 'a.add_to_cart' );
                addToCart.trigger( 'click' );
            }
        } );

        /**
         * =======================================================================
         * Handles left and right arrow keys to move within the cart form fields.
         * =======================================================================
         */
        $( document ).on( 'keydown', 'form#tickera_cart, table.event_tickets.tickera, .tc-event-dropdown-wrap, .tc-add-to-cart-group-wrap ~ .tc-add-to-cart-wrap', function( e ) {

            let fields = $( this ).find('input, select, textarea, a').filter(':visible:not([disabled]):not([type="hidden"]):not(.tc-hidden-important)'),
                currentField = $( this ).find( ':focus' );

            let currentFieldElementType = $( currentField ).prop( 'tagName' ).toLowerCase(),
                currentFieldType = $( currentField ).attr( 'type' ),
                index = $( fields ).index( currentField );

            if ( ( currentFieldElementType === 'input' && currentFieldType.includes( 'text' ) ) || currentFieldElementType === 'textarea' ) {

                let caretPlacement = $(currentField).prop('selectionStart'),
                    fieldValueLength = $(currentField).val().length;

                if (e.keyCode == 37 && caretPlacement === 0) {
                    $(fields[index - 1]).focus();

                } else if (e.keyCode == 39 && caretPlacement === fieldValueLength) {
                    $(fields[index + 1]).focus();

                } else if (e.keyCode == 13) {
                    $('#proceed_to_checkout').trigger('click');
                }

            } else if ( currentFieldElementType === 'input' && currentFieldType.includes( 'checkbox' ) && e.keyCode == 13 ) {

                if (currentField.prop('checked')) {
                    currentField.prop('checked', false).trigger('change');

                } else {
                    currentField.prop('checked', true).trigger('change');
                }

            } else if ( currentFieldElementType === 'input' && currentFieldType.includes( 'radio' ) ) {

                e.preventDefault();

                switch (e.keyCode) {

                    case 37: // Left arrow key
                        $(fields[index - 1]).focus();
                        break;

                    case 39: // Right arrow key
                        $(fields[index + 1]).focus();
                        break;

                    case 13: // Enter key
                        if (currentField.prop('checked')) {
                            currentField.prop('checked', false).trigger('change');
                        } else {
                            currentField.prop('checked', true).trigger('change');
                        }
                        break;
                }

            } else if ( currentFieldElementType === 'input' && currentFieldType.includes( 'button' ) && currentField.hasClass( 'tickera_button' ) ) {

                e.preventDefault();

                let action = currentField.data( 'action' ),
                    quantitySelector = currentField.parent().find( '.tc_quantity_selector' ),
                    quantitySelectorValue = parseInt( quantitySelector.val() ),
                    min = typeof quantitySelector.attr( 'min' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'min' ) ) : 1,
                    max = typeof quantitySelector.attr( 'max' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'max' ) ) : 999,
                    step = typeof quantitySelector.attr( 'step' ) !== 'undefined' ? parseInt( quantitySelector.attr( 'step' ) ) : 1;

                switch ( e.keyCode ) {

                    case 37: // Left arrow key
                        $( fields[ index - 1 ] ).focus();
                        break;

                    case 39: // Right arrow key
                    case 9: // Since e.preventDefault(), this is to handle conflict between cart and event add to cart quantity selectors
                        $( fields[ index + 1 ] ).focus();
                        break;

                    case 13: // Enter key

                        if ( currentField.hasClass( 'plus' ) ) {
                            quantitySelectorValue = quantitySelectorValue + step;

                        } else {
                            quantitySelectorValue = quantitySelectorValue - step;
                        }

                        if ( quantitySelectorValue < min ) {
                            quantitySelectorValue = min;

                        } else if ( quantitySelectorValue > max ) {
                            quantitySelectorValue = max;

                        } else if ( quantitySelectorValue < 1) {
                            quantitySelectorValue = 1
                        }

                        quantitySelector.val( quantitySelectorValue );
                        break;
                }

            } else {

                switch ( e.keyCode ) {

                    case 37: // Left arrow key
                        $( fields[ index - 1 ] ).focus();
                        break;

                    case 39: // Right arrow key
                        $( fields[ index + 1 ] ).focus();
                        break;
                }
            }
        } );

        /**
         * =======================================================================
         * Handles payment page's gateway selection.
         * =======================================================================
         */
        $( document ).on( 'keydown', 'form#tc_payment_form .tickera-payment-gateways.active', function( e ) {

            let fields = $( this ).find('input, select, textarea').filter(':visible:not([disabled])'),
                currentField = $( this ).find( ':focus' );

            let currentFieldElementType = $( currentField ).prop( 'tagName' ).toLowerCase(),
                currentFieldType = $( currentField ).attr( 'type' ),
                index = $( fields ).index( currentField );

            if ( currentFieldElementType === 'input' && currentFieldType.includes( 'text' ) ) {

                let caretPlacement = $( currentField ).prop( 'selectionStart' ),
                    fieldValueLength = $( currentField ).val().length;

                if ( e.keyCode == 37 && caretPlacement === 0 ) {
                    $( fields[ index - 1 ] ).focus();

                } else if ( e.keyCode == 39 && caretPlacement === fieldValueLength ) {
                    $( fields[ index + 1 ] ).focus();
                }


            } else {

                switch ( e.keyCode ) {

                    case 37: // Left arrow key
                        $( fields[ index - 1 ] ).focus();
                        break;

                    case 39: // Right arrow key
                        $( fields[ index + 1 ] ).focus();
                        break;

                    case 9:
                        if ( ! $( fields[ index + 1 ] ).length ) {
                            $( this ).removeClass( 'active' );
                            $( this ).find( '.tc_gateway_form' ).css( 'max-height', '0' );

                            let nextGateway = $( this ).next();
                            nextGateway.addClass( 'active' ).find( '.tc_choose_gateway' ).prop( 'checked', true );
                            nextGateway.find( '.tc_gateway_form' ).css( 'max-height', nextGateway.find( '.inner-wrapper' ).outerHeight() + 'px' );
                        }
                        break;
                }
            }
        } );

        /**
         * =======================================================================
         * Handles cart page's discount field.
         * =======================================================================
         */
        let coupon_code_value = $( '.tickera-checkout .coupon_code' ).val();
        $( document ).on( 'keyup', '.tickera-checkout .coupon_code', function() {

            if ( coupon_code_value !== $( this ).val() || ! $( this ).val() ) {
                $( this ).parent().find( '.message').hide();
                $( this ).parent().find( '.apply_coupon').show();

            } else {
                $( this ).parent().find( '.message').show();
                $( this ).parent().find( '.apply_coupon').hide();
            }
        });

        /**
         * =======================================================================
         * Payment Gateway Page - Frontend
         * =======================================================================
         */
        $( document ).on( 'keypress', '.tc-numbers-only', function( e ) {
            if ( e.which != 8 && e.which != 0 && ( e.which < 48 || e.which > 57 ) ) {
                return false;
            }
        } );

        /**
         * =======================================================================
         * Update add to cart link value/ticket type id
         * Event Tickets - Shortcode
         * Display Type: Dropdown
         * =======================================================================
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
                ignore: ".tc_quantity_selector",
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
             * =======================================================================
             * Update checkbox values on field change.
             * =======================================================================
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
             * =======================================================================
             * Update radio validation field
             * =======================================================================
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
