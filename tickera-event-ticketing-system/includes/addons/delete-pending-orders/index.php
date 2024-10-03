<?php
/**
 * Cancel Pending Orders
 * Cancel pending orders Note: all pending orders will be cancelled made via all payment gateways except Free Orders and Offline Payments
 * From Tickera version 3.2.5.3 orders will be cancelled instead of deleted
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Addons\TC_Cancel_Pending_Orders' ) ) {

    class TC_Cancel_Pending_Orders {

        var $version = '1.0';
        var $title = 'Cancel Pending Orders';
        var $name = 'tc';
        var $dir_name = 'delete-pending-orders';
        var $plugin_dir = '';
        var $plugin_url = '';

        function __construct() {
            if ( apply_filters( 'tc_bridge_for_woocommerce_is_active', false ) == false ) {
                $this->title = __( 'Cancel Pending Orders', 'tickera-event-ticketing-system' );
                add_filter( 'tc_general_settings_miscellaneous_fields', array( &$this, 'cancel_pending_orders_misc_settings_field' ) );
                add_action( 'tc_save_tc_general_settings', array( &$this, 'schedule_cancel_pending_orders_event' ) );
                add_action( 'tc_maybe_delete_pending_posts_hook', array( &$this, 'tc_maybe_cancel_pending_posts' ) );
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

            global $wpdb;
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $delete_pending_orders = isset( $tc_general_settings[ 'delete_pending_orders' ] ) ? $tc_general_settings[ 'delete_pending_orders' ] : 'no';

            if ( $delete_pending_orders == 'yes' ) {

                if ( ! wp_next_scheduled( 'tc_maybe_delete_pending_posts_hook' ) ) {
                    wp_schedule_event( time(), 'hourly', 'tc_maybe_delete_pending_posts_hook' );
                }
                $this->tc_maybe_cancel_pending_posts();

            } else {

                if ( apply_filters( 'tc_delete_trash_metas', true ) == true ) {
                    $trash_status = '_wp_trash_meta_status';
                    $trash_timestamp = '_wp_trash_meta_time';
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key=%s OR meta_key=%s", $trash_status, $trash_timestamp ) );
                }

                // Cancel cron hook
                wp_clear_scheduled_hook( 'tc_maybe_delete_pending_posts_hook' );
            }
        }

        function tc_maybe_cancel_pending_posts() {

            global $wpdb, $tc;

            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $delete_pending_orders = isset( $tc_general_settings[ 'delete_pending_orders' ] ) ? $tc_general_settings[ 'delete_pending_orders' ] : 'no';
            $delete_pending_orders_interval = isset( $tc_general_settings[ 'delete_pending_orders_interval' ] ) ? $tc_general_settings[ 'delete_pending_orders_interval' ] : '24';

            if ( $delete_pending_orders == 'yes' ) {

                $pending_orders = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts}  WHERE post_date < (NOW() - INTERVAL %d HOUR) AND post_type = 'tc_orders' AND post_status = 'order_received'", (int) $delete_pending_orders_interval ), OBJECT );

                foreach ( $pending_orders as $pending_order ) {

                    $order = new \Tickera\TC_Order( $pending_order->ID );
                    $ignore_default_gateway_classes = [ 'TC_Gateway_Custom_Offline_Payments', 'TC_Gateway_Free_Orders' ];
                    $ignore_additional_gateway_classes = apply_filters( 'tc_delete_pending_orders_ignore_gateway_classes', [] );

                    if ( in_array( $order->details->tc_cart_info[ 'gateway_class' ], $ignore_default_gateway_classes ) || in_array(  $order->details->tc_cart_info[ 'gateway_class' ], $ignore_additional_gateway_classes ) ) {
                        // Do not cancel pending orders

                    } else {
                        \Tickera\TC_Order::add_order_note( $pending_order->ID, __( 'Unpaid order cancelled - time limit reached.', 'tickera-event-ticketing-system' ) );
                        $tc->update_order_status( $pending_order->ID, 'order_cancelled' );
                    }
                }
            }
        }
    }
}

$tc_cancel_pending_orders = new TC_Cancel_Pending_Orders();
