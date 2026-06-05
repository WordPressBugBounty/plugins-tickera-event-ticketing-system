<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Heartbeat POST action only bypasses public payment processing page rendering.
if ( is_admin() || isset( $_POST[ 'action' ] ) && 'heartbeat' == sanitize_text_field( wp_unslash( $_POST[ 'action' ] ) ) ) {
    // Do nothing to allow indexing to this content.

} else {

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    global $tc, $tickera_gateway_plugins, $wp;
    $tickera_cart_contents = $tc->get_cart_cookie();

    $tickera_session = $tc->session->get();
    $tickera_cart_total = isset( $tickera_session[ 'tc_cart_total' ] ) ? (float) $tickera_session[ 'tc_cart_total' ] : null;

    if ( is_null( $tickera_cart_total ) ) {
        $tc->checkout_error = true;
        $tc->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
        tickera_redirect( $tc->get_payment_slug( true ), true );
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checkout gateway selection controls payment processing for the current cart.
    if ( ! isset( $_REQUEST[ 'tc_choose_gateway' ] ) ) {
        if ( $tickera_cart_total > 0 ) {

            // Set free orders as gateway since none is selected
            $tc->checkout_error = true;
            $tc->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
            tickera_redirect( $tc->get_payment_slug( true ), true );

        } else {

            // Set free orders since total is exactly zero
            if ( isset( $tickera_session[ 'tc_cart_total' ] ) ) {
                $tc->checkout_error = false;
                $tc->session->set( 'tc_gateway_error', '' );
                $tickera_payment_class_name = $tickera_gateway_plugins[ tickera_apply_filters( 'tickera_not_selected_default_gateway', 'free_orders' ) ][ 0 ];

            } else {
                $tc->checkout_error = true;
                $tc->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
                tickera_redirect( $tc->get_payment_slug( true ), true );
            }
        }

    } else {

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checkout gateway selection is sanitized before loading the gateway.
        $tickera_choose_gateway = isset( $_REQUEST[ 'tc_choose_gateway' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'tc_choose_gateway' ] ) ) : '';

        // Automatically expand the recently selected payment method used prior to error.
        $tc->session->set( 'tc_payment_method', sanitize_key( $tickera_choose_gateway ) );

        if ( '' !== $tickera_choose_gateway && isset( $tickera_gateway_plugins[ $tickera_choose_gateway ][ 0 ] ) && ( ( $tickera_cart_total > 0 && $tickera_choose_gateway !== 'free_orders' ) || ( $tickera_cart_total == 0 && $tickera_choose_gateway == 'free_orders' ) ) ) {
            $tc->session->set( 'tc_gateway_error', '' );
            $tc->checkout_error = false;
            $tickera_payment_class_name = $tickera_gateway_plugins[ $tickera_choose_gateway ][ 0 ];

        } else {
            $tc->checkout_error = true;
            $tc->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
            tickera_redirect( $tc->get_payment_slug( true ), true );
        }
    }

    if ( ! empty( $tickera_cart_contents ) && count( $tickera_cart_contents ) > 0 ) {

        if ( false == $tc->checkout_error ) {
            $tickera_payment_gateway = new $tickera_payment_class_name;
            $tickera_payment_gateway->process_payment( $tickera_cart_contents );
            exit;

        } else {
            tickera_redirect( $this->get_payment_slug( true ), true );
        }

    } else {
        // The cart is empty and this page shouldn't be reached
        tickera_redirect( $this->get_payment_slug( true ), true );
    }
}
