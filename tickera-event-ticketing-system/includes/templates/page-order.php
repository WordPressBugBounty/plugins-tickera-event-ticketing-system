<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( is_admin() || isset( $_POST[ 'action' ] ) && 'heartbeat' == $_POST[ 'action' ] ) {
    // Do nothing to allow indexing to this content.

} else {

    // Prevent search engine to index order pages for security reasons
    add_action( 'wp_head', 'tickera_no_index_no_follow' );

    global $wp, $tc;
    $order_id = ''; $order_key = '';

    // Collection of General Settings values
    $settings = get_option( 'tickera_general_setting', false );

    // Retrieve Order ID
    if ( isset( $wp->query_vars[ 'tc_order' ] ) ) {
        $order_id = sanitize_text_field( $wp->query_vars[ 'tc_order' ] );
    } elseif ( isset( $_GET[ 'tc_order' ] ) ) {
        $order_id = sanitize_text_field( $_GET[ 'tc_order' ] );
    }

    // Retrieve Order Key
    if ( isset( $wp->query_vars[ 'tc_order_key' ] ) ) {
        $order_key = sanitize_text_field( $wp->query_vars[ 'tc_order_key' ] );
    } elseif ( isset( $_GET[ 'tc_order_key' ] ) ) {
        $order_key = sanitize_text_field( $_GET[ 'tc_order_key' ] );
    }

    // Order Object
    $order = tickera_get_order_id_by_name( $order_id );

    // Remove associated order session data
    if ( $order ) {
        $tc->remove_order_session_data();
    }

    if ( $order_id && $order_key ) {

        if ( isset( $settings[ 'force_login' ] ) && 'yes' == $settings[ 'force_login' ] && ! is_user_logged_in() ) : ?>
            <div class="force_login_message"><?php echo wp_kses_post( sprintf( /* translators: %s: A link to Wordpress login page. */ __( 'Please <a href="%s">Log In</a> to see this page', 'tickera-event-ticketing-system' ), esc_url( apply_filters( 'tc_force_login_url', wp_login_url( tickera_current_url() ), tickera_current_url() ) ) ) ); ?></div>
        <?php else : ?>
            <div class="tc-container">
                <?php if ( $order ) { ?>
                    <div id="order_details" class="tickera">
                        <?php echo wp_kses( tickera_get_order_details_front( $order->ID, $order_key, true ), wp_kses_allowed_html( 'tickera' ) ); ?>
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
