<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( is_admin() || isset( $_POST[ 'action' ] ) && 'heartbeat' == $_POST[ 'action' ] ) {
    // Do nothing to allow indexing to this content.

} else {

    global $tc;

    $tc->remove_order_session_data_only();
    $cart_contents = $tc->get_cart_cookie();
    $settings = get_option( 'tickera_general_setting', false );

    if ( isset( $settings[ 'force_login' ] ) && 'yes' == $settings[ 'force_login' ] && ! is_user_logged_in() ) : ?>
        <div class="force_login_message"><?php echo wp_kses_post( printf( /* translators: %s: A link to Tickera checkout payment page. */ __( 'Please <a href="%s">Log In</a> to see this page', 'tickera-event-ticketing-system' ), esc_url( apply_filters( 'tc_force_login_url', wp_login_url( $tc->get_payment_slug( true ) ), $tc->get_payment_slug( true ) ) ) ) ); ?></div>
    <?php else :

        if ( empty( $cart_contents ) ) {
            tickera_redirect( $tc->get_cart_slug( true ), true );
        }

        if ( false == apply_filters( 'tc_has_cart_or_payment_errors', false, $cart_contents ) ) {
            $tc->cart_payment( true );

        } else {
            do_action( 'tc_has_cart_or_payment_errors_action', $cart_contents );
        }
    endif;
    $tc->session->set( 'tc_gateway_error', '' );
}
