<?php
/**
 * Delete Pending Orders
 * Delete pending orders (which are not paid for 12 hours or more). Note: all pending orders will be deleted made via all payment gateways except Free Orders and Offline Payments
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( defined( 'TC_HIDE_STATS_WIDGET' ) ) {
    return;
}

if ( ! class_exists( 'Tickera\Addons\TC_Stats_Dashboard_Widget' ) ) {

    class TC_Stats_Dashboard_Widget {

        var $version = '1.0';
        var $title = 'TC_Stats_Dashboard_Widget';
        var $name = 'tc';
        var $dir_name = 'stats-dashboard-widget';
        var $plugin_dir = '';
        var $plugin_url = '';

        function __construct() {
            $this->title = __( 'Ticketing Store at a Glance', 'tickera-event-ticketing-system' );
            add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );
            add_action( 'wp_dashboard_setup', array( &$this, 'add_tc_dashboard_widgets' ) );
        }

        function add_tc_dashboard_widgets() {
            if ( ! current_user_can( apply_filters( 'tc_can_view_dashboard_widgets_capability', 'manage_options' ) ) ) {
                return;
            }
            wp_add_dashboard_widget( 'tc_store_report', $this->title, array( &$this, 'tc_store_report_display' ) );
        }

        function enqueue_styles_scripts() {
            global $pagenow, $tc;

            if ( ! empty( $pagenow ) && ( 'index.php' === $pagenow ) ) {
                wp_enqueue_style( 'tc-dashboard-widgets', $tc->plugin_url . 'includes/addons/' . $this->dir_name . '/css/dashboard-widgets.css', false, $tc->version );
                wp_enqueue_style( 'tc-dashboard-widgets-font-awesome', $tc->plugin_url . '/css/font-awesome.min.css', array(), $tc->version );
                wp_enqueue_script( 'tc-dashboard-widgets-peity', $tc->plugin_url . '/includes/addons/' . $this->dir_name . '/js/jquery.peity.min.js', array( 'jquery' ), $tc->version );
                wp_enqueue_script( 'tc-dashboard-widgets', $tc->plugin_url . '/includes/addons/' . $this->dir_name . '/js/dashboard-widgets.js', array( 'jquery' ), $tc->version );
            }
        }

        function tc_store_report_display() {

            global $tc, $wpdb;

            $days_range = apply_filters( 'ticketing_glance_days', 30 );
            $days = $days_range * -1;
            $total_revenue = 0;
            $todays_revenue = 0;
            $count_of_paid_tickets = 0;
            $todays_date = date( "Y-m-d" );
            $totals_30 = $wpdb->get_results( $wpdb->prepare( "SELECT orders.post_date as order_date, order_meta.meta_value FROM {$wpdb->prefix}posts as orders, {$wpdb->prefix}postmeta as order_meta WHERE orders.ID = order_meta.post_id AND orders.post_type = 'tc_orders' AND orders.post_status = 'order_paid' AND order_meta.meta_key IN ( 'tc_cart_info' ) AND orders.post_date BETWEEN (NOW() - INTERVAL %d DAY) AND (NOW() + INTERVAL %d DAY)", 30, 1 ) );

            foreach ( $totals_30 as $total_record_30_init ) {

                $total_record_30 = maybe_unserialize( $total_record_30_init->meta_value );

                // Last 30 Days Earnings
                $total_record_val = isset( $total_record_30[ 'total' ] ) ? (float) $total_record_30[ 'total' ] : 0;
                $total_revenue += $total_record_val;

                // Today's Earnings
                if ( date( 'Y-m-d', strtotime( $total_record_30_init->order_date ) ) == $todays_date ) {
                    $todays_revenue += $total_record_val;
                }

                // Tickets Sold
                $owner_data = isset( $total_record_30[ 'owner_data' ] ) ? $total_record_30[ 'owner_data' ] : [];
                $tickets = isset( $owner_data[ 'ticket_type_id_post_meta' ] ) ? $owner_data[ 'ticket_type_id_post_meta' ] : [];
                $tickets = array_filter( (array) $tickets );

                foreach ( $tickets as $ticket ) {
                    $count = is_array( $ticket ) ? (int) count( $ticket ) : 1;
                    $count_of_paid_tickets += $count;
                }
            }

            $total_revenue = round( $total_revenue, 2 );
            $pending_orders_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'tc_orders' AND post_status = 'order_received' AND post_date BETWEEN (NOW() - INTERVAL %d DAY) AND (NOW() + INTERVAL 1 DAY)", (int) $days_range, 1 ) );
            $paid_orders_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'tc_orders' AND post_status = 'order_paid' AND post_date BETWEEN (NOW() - INTERVAL %d DAY) AND (NOW() + INTERVAL %d DAY)", (int) $days_range, 1 ) );
            ?>
            <ul class="tc-status-list">
                <li class="sales-this-month">
                    <a>
                        <i class="fa fa-money tc-icon tc-icon-dashboard-sales"></i>
                        <strong><span class="amount"><?php echo esc_html( $tc->get_cart_currency_and_format( $total_revenue ) ); ?></span></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php
                            echo esc_html( sprintf(
                                /* translators: %d: Number of days. */
                                _n( 'last %d day earnings', 'last %d days earnings', (int) $days_range, 'tickera-event-ticketing-system' ),
                                esc_html( (int) $days_range )
                            ) );
                        ?></span>
                    </a>
                </li>
                <li class="todays-earnings">
                    <a>
                        <i class="fa fa-money tc-icon tc-icon-dashboard-todays-earnings"></i>
                        <strong><?php echo esc_html( $tc->get_cart_currency_and_format( $todays_revenue ) ); ?></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php esc_html_e( 'today\'s earnings', 'tickera-event-ticketing-system' ); ?></span>
                    </a>
                </li>
                <li class="sold-tickets">
                    <a>
                        <i class="fa fa-ticket tc-icon tc-icon-dashboard-sold"></i>
                        <strong><?php
                            echo esc_html( sprintf(
                                /* translators: %d: The total count of paid tickets. */
                                _n( '%d ticket sold', '%d tickets sold', (int) $count_of_paid_tickets, 'tickera-event-ticketing-system' ),
                                esc_html( (int) $count_of_paid_tickets )
                            ) );
                        ?></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php
                            echo esc_html( sprintf(
                                /* translators: %d: Number of days. */
                                _n( 'in the last %d day', 'in the last %d days', (int) $days_range, 'tickera-event-ticketing-system' ),
                                esc_html( (int) $days_range )
                            ) );
                        ?></span>
                    </a>
                </li>
                <li class="completed-orders">
                    <a>
                        <i class="fa fa-shopping-cart tc-icon tc-icon-dashboard-completed"></i>
                        <strong><?php
                            echo esc_html( sprintf(
                                /* translators: %d: Total count of paid orders. */
                                _n( '%d order completed', '%d orders completed', (int) $paid_orders_count, 'tickera-event-ticketing-system' ),
                                esc_html( (int) $paid_orders_count )
                            ) );
                        ?></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php
                            echo esc_html( sprintf(
                                /* translators: %d: Number of days. */
                                _n( 'in the last %d day', 'in the last %d days', (int) $days_range, 'tickera-event-ticketing-system' ),
                                esc_html( (int) $days_range )
                            ) );
                        ?></span>
                    </a>
                </li>
                <li class="pending-orders">
                    <a>
                        <i class="fa fa-shopping-cart tc-icon tc-icon-dashboard-pending"></i>
                        <strong><?php
                            echo esc_html( sprintf(
                                /* translators: %d: Total count of pending orders. */
                                _n( '%d pending order', '%d pending orders', (int) $pending_orders_count, 'tickera-event-ticketing-system' ),
                                esc_html( (int) $pending_orders_count )
                            ) );
                        ?></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php
                            echo esc_html( sprintf(
                                /* translators: %d: Number of days. */
                                _n( 'in the last %d day', 'in the last %d days', (int) $days_range, 'tickera-event-ticketing-system' ),
                                esc_html( (int) $days_range )
                            ) );
                        ?></span>
                    </a>
                </li>
            </ul>
            <?php
        }
    }
}

if ( is_admin() ) {
    $tc_stats_dashboard_widget = new TC_Stats_Dashboard_Widget();
}
