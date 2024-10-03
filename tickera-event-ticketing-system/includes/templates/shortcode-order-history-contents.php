<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc;

if ( ! is_user_logged_in() ) {
    echo wp_kses_post( sprintf(
        /* translators: %s: Admin login url */
        __( 'Please <a href="%s">log in</a> to see your order history.', 'tickera-event-ticketing-system' ),
        esc_url( apply_filters( 'tc_force_login_url', wp_login_url(), wp_login_url() ) )
    ) );

} else {
    $user_orders = \Tickera\TC_Orders::get_user_orders();
    ?>
    <div class="tc-container">
    <?php
    if ( count( $user_orders ) == 0 ) {
        esc_html_e( 'No Orders Found', 'tickera-event-ticketing-system' );

    } else {
        ?>
        <table cellspacing="0" class="tickera_table" cellpadding="10">
            <tr>
                <th><?php esc_html_e( 'Status', 'tickera-event-ticketing-system' ); ?></th>
                <?php do_action( 'tc_order_history_col_after_status' ); ?>
                <th><?php esc_html_e( 'Date', 'tickera-event-ticketing-system' ); ?></th>
                <?php do_action( 'tc_order_history_col_after_date' ); ?>
                <th><?php esc_html_e( 'Total', 'tickera-event-ticketing-system' ); ?></th>
                <?php do_action( 'tc_order_history_col_after_total' ); ?>
                <th><?php esc_html_e( 'Details', 'tickera-event-ticketing-system' ); ?></th>
                <?php do_action( 'tc_order_history_col_after_details' ); ?>
            </tr>
            <?php
            foreach ( $user_orders as $user_order ) {
                $order = new \Tickera\TC_Order( $user_order->ID );
                ?>
                <tr>
                    <td>
                        <?php
                        $post_status = $order->details->post_status;
                        $init_post_status = $post_status;

                        $tc_order_status_color = array(
                            'order_fraud' => 'tc_order_fraud',
                            'order_received' => 'tc_order_received',
                            'order_paid' => 'tc_order_paid',
                            'order_cancelled' => 'tc_order_cancelled',
                            'order_refunded' => 'tc_order_fraud'
                        );

                        $color = isset( $tc_order_status_color[ $post_status ] ) ? $tc_order_status_color[ $post_status ] : 'tc_order_received';

                        if ( 'order_fraud' == $post_status ) {
                            $post_status = __( 'Held for Review', 'tickera-event-ticketing-system' );
                        }

                        $post_status = ucwords( str_replace( '_', ' ', $post_status ) );
                        echo wp_kses_post( sprintf(
                                /* translators: 1: An order status key 2: Order status */
                                __( '<span class="%1$s">%2$s</span>', 'tickera-event-ticketing-system' ),
                                esc_attr( apply_filters( 'tc_order_history_color', $color, $init_post_status ) ),
                                ucwords( $post_status )
                            ) );
                        ?>
                    </td>
                    <?php do_action( 'tc_order_history_td_after_status', $user_order ); ?>
                    <td>
                        <?php
                        echo esc_html( tickera_format_date( $order->details->tc_order_date, true ) );
                        ?>
                    </td>
                    <?php do_action( 'tc_order_history_td_after_date', $user_order ); ?>
                    <td>
                        <?php echo esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) ); ?>
                    </td>
                    <?php do_action( 'tc_order_history_td_after_total', $user_order ); ?>
                    <td>
                        <?php $order_status_url = $tc->tc_order_status_url( $order, $order->details->tc_order_date, '', false ); ?>
                        <a href="<?php echo esc_url( $order_status_url ); ?>"><?php esc_html_e( 'Order Details', 'tickera-event-ticketing-system' ); ?></a>
                    </td>
                    <?php do_action( 'tc_order_history_td_after_details', $user_order ); ?>
                </tr>
                <?php
            } ?>
        </table>
        </div>
        <?php
    }
}
