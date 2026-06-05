<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Heartbeat POST action only bypasses public confirmation page rendering.
if ( is_admin() || isset( $_POST[ 'action' ] ) && sanitize_text_field( wp_unslash( $_POST[ 'action' ] ) ) == 'heartbeat' ) {
    // Do nothing to allow indexing to this content.

} else {

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    global $tc, $wp;

    $tickera_order_return = isset( $wp->query_vars[ 'tc_order_return' ] ) ? sanitize_text_field( $wp->query_vars[ 'tc_order_return' ] ) : '';

    if ( empty( $tickera_order_return ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public order return reference is sanitized before loading confirmation details.
        $tickera_order_return = isset( $_GET[ 'tc_order_return' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'tc_order_return' ] ) ) : '';
    }

    if ( $tickera_order_return !== '' ) {
        $tickera_order = tickera_get_order_id_by_name( $tickera_order_return );
        if ( $tickera_order ) {
            $tickera_order = new \Tickera\TC_Order( $tickera_order->ID );
            $tickera_gateway_class = $tickera_order->details->tc_cart_info[ 'gateway_class' ];
            $tickera_payment_info = $tickera_order->details->tc_payment_info;
            $tickera_cart_info = $tickera_order->details->tc_cart_info;
        }
    }

    if ( isset( $tickera_gateway_class ) ) {
        $tickera_session_order = $tc->session->get( 'tc_order' );
        $tickera_cart_info_cookie = $tc->get_cart_info_cookie();
        $tickera_order_cookie = $tc->get_order_cookie();

        $tickera_payment_class_name = class_exists( $tickera_gateway_class ) ? $tickera_gateway_class : "\\Tickera\\Gateway\\" . $tickera_gateway_class;
        $tickera_payment_gateway = new $tickera_payment_class_name;

        $tickera_order_id = isset( $tickera_order_return ) ? $tickera_order_return : ( !is_null( $tickera_session_order ) ? sanitize_text_field( $tickera_session_order ) : ( isset( $tickera_order_cookie ) && ! empty( $tickera_order_cookie ) ? $tickera_order_cookie : '' ) );
        tickera_do_action( 'tickera_track_order_confirmation', $tickera_order_id, isset( $tickera_payment_info ) ? $tickera_payment_info : '', isset( $tickera_cart_info ) ? $tickera_cart_info : '' );
        $tickera_payment_gateway->order_confirmation( $tickera_order_id, isset( $tickera_payment_info ) ? $tickera_payment_info : '', isset( $tickera_cart_info ) ? $tickera_cart_info : '' );
        tickera_do_action( 'tickera_track_order_after_confirmation', $tickera_order_id );
        echo wp_kses_post( tickera_apply_filters( 'tickera_after_order_confirmation_message', $tickera_payment_gateway->order_confirmation_message( $tickera_order_id, isset( $tickera_cart_info ) ? $tickera_cart_info : '' ), $tickera_order_id ) );
    }
}
