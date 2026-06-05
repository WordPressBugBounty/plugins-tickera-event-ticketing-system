<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Heartbeat POST action only bypasses public order page rendering.
if ( is_admin() || isset( $_POST[ 'action' ] ) && 'heartbeat' == sanitize_text_field( wp_unslash( $_POST[ 'action' ] ) ) ) {
    // Do nothing to allow indexing to this content.

} else {

    // Prevent search engine to index order pages for security reasons
    add_action( 'wp_head', 'tickera_no_index_no_follow' );

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    global $wp, $tc;

    $tickera_order_id = ''; $tickera_order_key = '';

    // Collection of General Settings values
    $tickera_settings = get_option( 'tickera_general_setting', false );

    // Retrieve Order ID
    if ( isset( $wp->query_vars[ 'tc_order' ] ) ) {
        $tickera_order_id = sanitize_text_field( $wp->query_vars[ 'tc_order' ] );

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public order ID is sanitized before loading order details.
    } elseif ( isset( $_GET[ 'tc_order' ] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public order ID is sanitized before loading order details.
        $tickera_order_id = sanitize_text_field( wp_unslash( $_GET[ 'tc_order' ] ) );
    }

    // Retrieve Order Key
    if ( isset( $wp->query_vars[ 'tc_order_key' ] ) ) {
        $tickera_order_key = sanitize_text_field( $wp->query_vars[ 'tc_order_key' ] );

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public order key is sanitized before validating order access.
    } elseif ( isset( $_GET[ 'tc_order_key' ] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public order key is sanitized before validating order access.
        $tickera_order_key = sanitize_text_field( wp_unslash( $_GET[ 'tc_order_key' ] ) );
    }

    // Order Object
    $tickera_order = tickera_get_order_id_by_name( $tickera_order_id );

    // Remove associated order session data
    if ( $tickera_order ) {
        $tc->remove_order_session_data();
    }

    if ( $tickera_order_id && $tickera_order_key ) {

        if ( isset( $tickera_settings[ 'force_login' ] ) && 'yes' == $tickera_settings[ 'force_login' ] && ( ! is_user_logged_in() || ( $tickera_order && get_current_user_id() != $tickera_order->post_author ) ) ) : ?>
            <div class="force_login_message"><?php echo wp_kses_post( sprintf( /* translators: %s: A link to Wordpress login page. */ __( 'Please <a href="%s">Log In</a> to see this page', 'tickera-event-ticketing-system' ), esc_url( tickera_apply_filters( 'tickera_force_login_url', wp_login_url( tickera_current_url() ), tickera_current_url() ) ) ) ); ?></div>
        <?php else : ?>
            <div class="tc-container">
                <?php if ( $tickera_order ) { ?>
                    <div id="order_details" class="tickera">
                        <?php echo wp_kses( tickera_get_order_details_front( $tickera_order->ID, $tickera_order_key, true ), wp_kses_allowed_html( 'tickera' ) ); ?>
                    </div><!-- tickera --><?php
                } else {
                    esc_html_e( 'Order cannot be found.', 'tickera-event-ticketing-system' );
                } ?>
            </div>
        <?php endif;

    } else {
        esc_html_e( 'Order cannot be found.', 'tickera-event-ticketing-system' );
    }
}
