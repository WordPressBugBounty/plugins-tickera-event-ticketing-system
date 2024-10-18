<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( is_admin() || isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'heartbeat' ) {
    // Do nothing to allow indexing to this content.

} else {

    global $tc, $wp;

    $tc_order_return = isset( $wp->query_vars[ 'tc_order_return' ] ) ? sanitize_text_field( $wp->query_vars[ 'tc_order_return' ] ) : '';

    if ( empty( $tc_order_return ) ) {
        $tc_order_return = isset( $_GET[ 'tc_order_return' ] ) ? sanitize_text_field( $_GET[ 'tc_order_return' ] ) : '';
    }

    if ( $tc_order_return !== '' ) {
        $order = tickera_get_order_id_by_name( $tc_order_return );
        if ( $order ) {
            $order = new \Tickera\TC_Order( $order->ID );
            $gateway_class = $order->details->tc_cart_info[ 'gateway_class' ];
            $payment_info = $order->details->tc_payment_info;
            $cart_info = $order->details->tc_cart_info;
        }
    }

    if ( isset( $gateway_class ) ) {
        $session_order = $tc->session->get( 'tc_order' );
        $cart_info_cookie = $tc->get_cart_info_cookie();
        $order_cookie = $tc->get_order_cookie();

        $payment_class_name = class_exists( $gateway_class ) ? $gateway_class : "\\Tickera\\Gateway\\" . $gateway_class;
        $payment_gateway = new $payment_class_name;

        $order_id = isset( $tc_order_return ) ? $tc_order_return : ( !is_null( $session_order ) ? sanitize_text_field( $session_order ) : ( isset( $order_cookie ) && ! empty( $order_cookie ) ? $order_cookie : '' ) );
        do_action( 'tc_track_order_confirmation', $order_id, isset( $payment_info ) ? $payment_info : '', isset( $cart_info ) ? $cart_info : '' );
        $payment_gateway->order_confirmation( $order_id, isset( $payment_info ) ? $payment_info : '', isset( $cart_info ) ? $cart_info : '' );
        do_action( 'tc_track_order_after_confirmation', $order_id );
        echo wp_kses_post( apply_filters( 'tc_after_order_confirmation_message', $payment_gateway->order_confirmation_message( $order_id, isset( $cart_info ) ? $cart_info : '' ), $order_id ) );
    }
}
