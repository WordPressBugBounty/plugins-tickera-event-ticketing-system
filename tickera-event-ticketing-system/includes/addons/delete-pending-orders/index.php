<?php
/**
 * Cancel Pending Orders
 * Cancel pending orders Note: all pending orders will be cancelled made via all payment gateways except Free Orders and Offline Payments
 * From Tickera version 3.2.5.3 orders will be cancelled instead of deleted
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\Addons\TC_Cancel_Pending_Orders' ) ) {

    class TC_Cancel_Pending_Orders {

        var $version = '1.0';
        var $title = 'Cancel Pending Orders';
        var $name = 'tc';
        var $dir_name = 'delete-pending-orders';
        var $plugin_dir = '';
        var $plugin_url = '';

        function __construct() {
            if ( tickera_apply_filters( 'tickera_bridge_for_woocommerce_is_active', false ) == false ) {
                $this->title = __( 'Cancel Pending Orders', 'tickera-event-ticketing-system' );
                add_filter( 'tickera_general_settings_miscellaneous_fields', array( &$this, 'cancel_pending_orders_misc_settings_field' ), 10, 1 );
                add_action( 'tickera_save_tc_general_settings', array( &$this, 'schedule_cancel_pending_orders_event' ), 10, 1 );
                add_action( 'tickera_maybe_delete_pending_posts_hook', array( &$this, 'tc_maybe_cancel_pending_posts' ), 10, 1 );
            }
        }

        function cancel_pending_orders_misc_settings_field( $settings_fields ) {

            $new_default_fields = array();
            $new_default_fields[] = array(
                'field_name' => 'delete_pending_orders',
                'field_title' => __( 'Cancel Pending Orders', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_yes_no',
                'default_value' => 'no',
                'tooltip' => __( 'Cancel pending orders (which are not paid for "Cancel Pending Orders Interval" hours). Note: all pending orders will be cancelled made via all payment gateways except Free Orders and Offline Payments.', 'tickera-event-ticketing-system' ),
                'section' => 'miscellaneous_settings'
            );

            $new_default_fields[] = array(
                'field_name' => 'delete_pending_orders_interval',
                'field_title' => __( 'Cancel Pending Orders Interval', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_get_delete_pending_orders_intervals',
                'default_value' => '24',
                'tooltip' => __( 'Set after how many hours an order will be cancelled if it\'s not paid. It is good practice to use 12 or more hours (depending on a payment gateway used) since payment confirmation messages from payment processors may be delayed sometimes. Timing is not always accurate since the opperation depends on the wp-cron.', 'tickera-event-ticketing-system' ),
                'section' => 'miscellaneous_settings',
                'conditional' => array(
                    'field_name' => 'delete_pending_orders',
                    'field_type' => 'radio',
                    'value' => 'no',
                    'action' => 'hide'
                ),
                'required' => false,
                'number' => true
            );

            /**
             * Issue: Opposite functionality
             * Keeping the meta value and simply renaming the label in order not to affect existing customer's configuration.
             *
             * Previously "Remove Cancelled Orders From Stock"
             *
             * @since 3.5.2.3
             */
            $new_default_fields[] = array(
                'field_name' => 'removed_cancelled_orders_from_stock',
                'field_title' => __( 'Return Cancelled Orders in Stock', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_yes_no',
                'default_value' => 'yes',
                'tooltip' => __( 'Set to "Yes" to return the committed stocks of a cancelled order.', 'tickera-event-ticketing-system' ),
                'section' => 'miscellaneous_settings'
            );

            return array_merge( $settings_fields, $new_default_fields );
        }

        function schedule_cancel_pending_orders_event() {

            $tickera_general_settings = get_option( 'tickera_general_setting', false );
            $delete_pending_orders = isset( $tickera_general_settings[ 'delete_pending_orders' ] ) ? $tickera_general_settings[ 'delete_pending_orders' ] : 'no';

            if ( $delete_pending_orders == 'yes' ) {

                if ( ! wp_next_scheduled( 'tc_maybe_delete_pending_posts_hook' ) ) {
                    wp_schedule_event( time(), 'hourly', 'tc_maybe_delete_pending_posts_hook' );
                }
                $this->tc_maybe_cancel_pending_posts();

            } else {

                if ( tickera_apply_filters( 'tickera_delete_trash_metas', true ) == true ) {
                    $trash_status = '_wp_trash_meta_status';
                    $trash_timestamp = '_wp_trash_meta_time';
                    $trashed_posts = get_posts( [
                        'post_type' => 'any',
                        'post_status' => 'any',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'no_found_rows' => true,
                        'update_post_meta_cache' => false,
                        'update_post_term_cache' => false,
                        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to find posts with WordPress trash metadata.
                        'meta_query' => [
                            'relation' => 'OR',
                            [
                                'key' => $trash_status,
                                'compare' => 'EXISTS',
                            ],
                            [
                                'key' => $trash_timestamp,
                                'compare' => 'EXISTS',
                            ],
                        ],
                    ] );

                    foreach ( $trashed_posts as $trashed_post_id ) {
                        delete_post_meta( $trashed_post_id, $trash_status );
                        delete_post_meta( $trashed_post_id, $trash_timestamp );
                    }
                }

                // Cancel cron hook
                wp_clear_scheduled_hook( 'tc_maybe_delete_pending_posts_hook' );
            }
        }

        function tc_maybe_cancel_pending_posts() {

            global $tc;

            $tickera_general_settings = get_option( 'tickera_general_setting', false );
            $delete_pending_orders = isset( $tickera_general_settings[ 'delete_pending_orders' ] ) ? $tickera_general_settings[ 'delete_pending_orders' ] : 'no';
            $delete_pending_orders_interval = isset( $tickera_general_settings[ 'delete_pending_orders_interval' ] ) ? $tickera_general_settings[ 'delete_pending_orders_interval' ] : '24';

            if ( $delete_pending_orders == 'yes' ) {

                $pending_orders = get_posts( [
                    'post_type' => 'tc_orders',
                    'post_status' => 'order_received',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'no_found_rows' => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                    'date_query' => [
                        [
                            'column' => 'post_date',
                            'before' => wp_date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( (int) $delete_pending_orders_interval * 3600 ) ), // 3600 secs in 1hr
                        ],
                    ],
                ] );

                foreach ( $pending_orders as $pending_order ) {

                    $order = new \Tickera\TC_Order( $pending_order );
                    $ignore_default_gateway_classes = [ 'TC_Gateway_Custom_Offline_Payments', 'TC_Gateway_Free_Orders' ];
                    $ignore_additional_gateway_classes = tickera_apply_filters( 'tickera_delete_pending_orders_ignore_gateway_classes', [] );

                    if ( in_array( $order->details->tc_cart_info[ 'gateway_class' ], $ignore_default_gateway_classes ) || in_array(  $order->details->tc_cart_info[ 'gateway_class' ], $ignore_additional_gateway_classes ) ) {
                        // Do not cancel pending orders

                    } else {
                        \Tickera\TC_Order::add_order_note( $pending_order, __( 'Unpaid order cancelled - time limit reached.', 'tickera-event-ticketing-system' ) );
                        $tc->update_order_status( $pending_order, 'order_cancelled' );
                    }
                }
            }
        }
    }
}

new TC_Cancel_Pending_Orders();
