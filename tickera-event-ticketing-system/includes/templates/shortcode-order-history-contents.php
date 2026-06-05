<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
global $tc;

if ( ! is_user_logged_in() ) {
    echo wp_kses_post( sprintf(
        /* translators: %s: Admin login url */
        __( 'Please <a href="%s">log in</a> to see your order history.', 'tickera-event-ticketing-system' ),
        esc_url( tickera_apply_filters( 'tickera_force_login_url', wp_login_url(), wp_login_url() ) )
    ) );

} else {
    $tickera_user_orders = \Tickera\TC_Orders::get_user_orders( wp_get_current_user() ); ?>
    <div class="tc-container">
    <?php
    if ( count( $tickera_user_orders ) == 0 ) {
        esc_html_e( 'No Orders Found', 'tickera-event-ticketing-system' );

    } else {
        ?>
        <table cellspacing="0" class="tickera_table" cellpadding="10">
            <tr>
                <th><?php esc_html_e( 'Status', 'tickera-event-ticketing-system' ); ?></th>
                <?php tickera_do_action( 'tickera_order_history_col_after_status' ); ?>
                <th><?php esc_html_e( 'Date', 'tickera-event-ticketing-system' ); ?></th>
                <?php tickera_do_action( 'tickera_order_history_col_after_date' ); ?>
                <th><?php esc_html_e( 'Total', 'tickera-event-ticketing-system' ); ?></th>
                <?php tickera_do_action( 'tickera_order_history_col_after_total' ); ?>
                <th><?php esc_html_e( 'Details', 'tickera-event-ticketing-system' ); ?></th>
                <?php tickera_do_action( 'tickera_order_history_col_after_details' ); ?>
            </tr>
            <?php
            foreach ( $tickera_user_orders as $tickera_user_order ) {
                $tickera_order = new \Tickera\TC_Order( $tickera_user_order->ID );
                ?>
                <tr>
                    <td>
                        <?php
                        $tickera_post_status = $tickera_order->details->post_status;
                        $tickera_init_post_status = $tickera_post_status;

                        $tickera_order_status_color = array(
                            'order_fraud' => 'tc_order_fraud',
                            'order_received' => 'tc_order_received',
                            'order_paid' => 'tc_order_paid',
                            'order_cancelled' => 'tc_order_cancelled',
                            'order_refunded' => 'tc_order_fraud'
                        );

                        $tickera_color = isset( $tickera_order_status_color[ $tickera_post_status ] ) ? $tickera_order_status_color[ $tickera_post_status ] : 'tc_order_received';

                        if ( 'order_fraud' == $tickera_post_status ) {
                            $tickera_post_status = __( 'Held for Review', 'tickera-event-ticketing-system' );
                        }

                        $tickera_post_status = ucwords( str_replace( '_', ' ', $tickera_post_status ) );
                        echo wp_kses_post( sprintf(
                                /* translators: 1: An order status key 2: Order status */
                                __( '<span class="%1$s">%2$s</span>', 'tickera-event-ticketing-system' ),
                                esc_attr( tickera_apply_filters( 'tickera_order_history_color', $tickera_color, $tickera_init_post_status ) ),
                                ucwords( $tickera_post_status )
                            ) );
                        ?>
                    </td>
                    <?php tickera_do_action( 'tickera_order_history_td_after_status', $tickera_user_order ); ?>
                    <td>
                        <?php
                        echo esc_html( tickera_format_date( $tickera_order->details->tc_order_date, true ) );
                        ?>
                    </td>
                    <?php tickera_do_action( 'tickera_order_history_td_after_date', $tickera_user_order ); ?>
                    <td>
                        <?php echo esc_html( apply_filters( 'tickera_cart_currency_and_format', $tickera_order->details->tc_payment_info[ 'total' ] ) ); ?>
                    </td>
                    <?php tickera_do_action( 'tickera_order_history_td_after_total', $tickera_user_order ); ?>
                    <td>
                        <?php $tickera_order_status_url = $tc->tc_order_status_url( $tickera_order, $tickera_order->details->tc_order_date, '', false ); ?>
                        <a href="<?php echo esc_url( $tickera_order_status_url ); ?>"><?php esc_html_e( 'Order Details', 'tickera-event-ticketing-system' ); ?></a>
                    </td>
                    <?php tickera_do_action( 'tickera_order_history_td_after_details', $tickera_user_order ); ?>
                </tr>
                <?php
            } ?>
        </table>
        </div>
        <?php
    }
}
