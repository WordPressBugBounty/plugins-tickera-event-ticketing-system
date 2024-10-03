<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( is_admin() || isset( $_POST[ 'action' ] ) && 'heartbeat' == $_POST[ 'action' ] ) {
    // Do nothing to allow indexing to this content.

} else {

    global $tc, $tc_gateway_plugins, $wp;
    $cart_contents = $tc->get_cart_cookie();

    $session = $tc->session->get();
    $cart_total = isset( $session[ 'tc_cart_total' ] ) ? (float) $session[ 'tc_cart_total' ] : null;

    if ( is_null( $cart_total ) ) {
        $tc->checkout_error = true;
        $tc->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
        tickera_redirect( $tc->get_payment_slug( true ), true );
    }

    if ( ! isset( $_REQUEST[ 'tc_choose_gateway' ] ) ) {
        if ( $cart_total > 0 ) {

            // Set free orders as gateway since none is selected
            $tc->checkout_error = true;
            $tc->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
            tickera_redirect( $tc->get_payment_slug( true ), true );

        } else {

            // Set free orders since total is exactly zero
            if ( isset( $session[ 'tc_cart_total' ] ) ) {
                $tc->checkout_error = false;
                $tc->session->set( 'tc_gateway_error', '' );
                $payment_class_name = $tc_gateway_plugins[ apply_filters( 'tc_not_selected_default_gateway', 'free_orders' ) ][ 0 ];

            } else {
                $tc->checkout_error = true;
                $tc->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
                tickera_redirect( $tc->get_payment_slug( true ), true );
            }
        }

    } else {

        // Automatically expand the recently selected payment method used prior to error.
        $tc->session->set( 'tc_payment_method', sanitize_key( $_REQUEST[ 'tc_choose_gateway' ] ) );

        if ( ( $cart_total > 0 && $_REQUEST[ 'tc_choose_gateway' ] !== 'free_orders' ) || ( $cart_total == 0 && $_REQUEST[ 'tc_choose_gateway' ] == 'free_orders' ) ) {
            $tc->session->set( 'tc_gateway_error', '' );
            $tc->checkout_error = false;
            $payment_class_name = $tc_gateway_plugins[ sanitize_text_field( $_REQUEST[ 'tc_choose_gateway' ] ) ][ 0 ];

        } else {
            $tc->checkout_error = true;
            $tc->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
            tickera_redirect( $tc->get_payment_slug( true ), true );
        }
    }

    if ( ! empty( $cart_contents ) && count( $cart_contents ) > 0 ) {

        if ( false == $tc->checkout_error ) {
            $payment_gateway = new $payment_class_name;
            $payment_gateway->process_payment( $cart_contents );
            exit;

        } else {
            tickera_redirect( $this->get_payment_slug( true ), true );
        }

    } else {
        // The cart is empty and this page shouldn't be reached
        tickera_redirect( $this->get_payment_slug( true ), true );
    }
}
