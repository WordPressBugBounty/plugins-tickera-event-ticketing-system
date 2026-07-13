<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Heartbeat POST action only bypasses public payment page rendering.
if ( is_admin() || isset( $_POST[ 'action' ] ) && 'heartbeat' == sanitize_text_field( wp_unslash( $_POST[ 'action' ] ) ) ) {
    // Do nothing to allow indexing to this content.

} else {

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    global $tc;

    $tc->remove_order_session_data_only();
    $tickera_cart_contents = $tc->get_cart_cookie();
    $tickera_settings = get_option( 'tickera_general_setting', false );

    if ( isset( $tickera_settings[ 'force_login' ] ) && 'yes' == $tickera_settings[ 'force_login' ] && ! is_user_logged_in() ) : ?>
        <div class="force_login_message"><?php echo wp_kses_post( printf( /* translators: %s: A link to Tickera checkout payment page. */ __( 'Please <a href="%s">Log In</a> to see this page', 'tickera-event-ticketing-system' ), esc_url( tickera_apply_filters( 'tickera_force_login_url', wp_login_url( $tc->get_payment_slug( true ) ), $tc->get_payment_slug( true ) ) ) ) ); ?></div>
    <?php else :

        if ( empty( $tickera_cart_contents ) ) {
            tickera_redirect( $tc->get_cart_slug( true ), true );
        }

        if ( false == tickera_apply_filters( 'tickera_has_cart_or_payment_errors', false, $tickera_cart_contents ) ) {
            $tc->cart_payment( true );

        } else {
            tickera_do_action( 'tickera_has_cart_or_payment_errors_action', $tickera_cart_contents );
        }
    endif;
    $tc->session->set( 'tc_gateway_error', '' );
}
