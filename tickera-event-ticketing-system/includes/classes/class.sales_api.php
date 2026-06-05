<?php
namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sales API response format flag is accepted without a browser nonce.
if ( isset( $_REQUEST[ 'ct_json' ] ) ) {
    header( 'Content-Type: application/json' );
}

if ( ! class_exists( '\Tickera\TC_Sales_API' ) ) {

    class TC_Sales_API {

        var $api_key = '';
        var $page_number = 1;
        var $results_per_page = 10;
        var $keyword = '';

        function __construct( $api_key, $request, $return_method = 'echo', $execute_request = true ) {
            global $wp;

            $this->api_key = $api_key;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sales API pagination is authenticated by API key, not a browser nonce.
            $page_number = isset( $wp->query_vars[ 'page_number' ] ) ? (int) $wp->query_vars[ 'page_number' ] : ( isset( $_REQUEST[ 'page_number' ] ) ? (int) $_REQUEST[ 'page_number' ] : tickera_apply_filters( 'tickera_ticket_info_default_page_number', 1 ) );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sales API pagination size is authenticated by API key, not a browser nonce.
            $results_per_page = isset( $wp->query_vars[ 'results_per_page' ] ) ? (int) $wp->query_vars[ 'results_per_page' ] : ( isset( $_REQUEST[ 'results_per_page' ] ) ? (int) $_REQUEST[ 'results_per_page' ] : tickera_apply_filters( 'tickera_ticket_info_default_results_per_page', 50 ) );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sales API keyword filter is sanitized and authenticated by API key.
            $keyword = isset( $wp->query_vars[ 'keyword' ] ) ? sanitize_text_field( wp_unslash( $wp->query_vars[ 'keyword' ] ) ) : ( isset( $_REQUEST[ 'keyword' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'keyword' ] ) ) : '' );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sales API period filter is cast and authenticated by API key.
            $period = isset( $wp->query_vars[ 'period' ] ) ? (int) $wp->query_vars[ 'period' ] : ( isset( $_REQUEST[ 'period' ] ) ? (int) $_REQUEST[ 'period' ] : -30 );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sales API period comparison filter is sanitized and authenticated by API key.
            $period_compare = isset( $wp->query_vars[ 'period_compare' ] ) ? sanitize_text_field( $wp->query_vars[ 'period_compare' ] ) : ( isset( $_REQUEST[ 'period_compare' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'period_compare' ] ) ) : '>' );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sales API order filter is cast and authenticated by API key.
            $order_id = isset( $wp->query_vars[ 'order_id' ] ) ? (int) $wp->query_vars[ 'order_id' ] : ( isset( $_REQUEST[ 'order_id' ] ) ? (int) $_REQUEST[ 'order_id' ] : '' );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sales API event filter is cast and authenticated by API key.
            $event_id = isset( $wp->query_vars[ 'event_id' ] ) ? (int) $wp->query_vars[ 'event_id' ] : ( isset( $_REQUEST[ 'event_id' ] ) ? (int) $_REQUEST[ 'event_id' ] : '' );

            $this->page_number = tickera_apply_filters( 'tickera_sales_stats_page_number_var_name', $page_number );
            $this->results_per_page = tickera_apply_filters( 'tickera_sales_stats_results_per_page_var_name', $results_per_page );
            $this->keyword = tickera_apply_filters( 'tickera_sales_stats_keyword_var_name', $keyword );
            $this->period = tickera_apply_filters( 'tickera_sales_stats_period_var_name', $period );
            $this->period_compare = tickera_apply_filters( 'tickera_sales_stats_period_compare_var_name', $period_compare );
            $this->order_id = tickera_apply_filters( 'tickera_sales_stats_order_id_var_name', $order_id );
            $this->event_id = tickera_apply_filters( 'tickera_sales_stats_event_id_var_name', $event_id );

            /*
             * $new_rules[ '^tc-api/(.+)/sales_check_credentials' ]			 = 'index.php?tickera_sales=sales_check_credentials&api_key=$matches[1]';
             * $new_rules[ '^tc-api/(.+)/sales_stats_general/(.+)/(.+)/(.+)' ] = 'index.php?tickera_sales=sales_stats_general&api_key=$matches[1]&period=$matches[2]&results_per_page=$matches[3]&page_number=$matches[4]';
             * $new_rules[ '^tc-api/(.+)/sales_stats_event/(.+)/(.+)/(.+)' ] = 'index.php?tickera_sales=sales_stats_event&api_key=$matches[1]&period=$matches[2]&results_per_page=$matches[3]&page_number=$matches[4]';
             * $new_rules[ '^tc-api/(.+)/sales_stats_order/(.+)' ] = 'index.php?tickera_sales=sales_stats_order&api_key=$matches[1]&order_id=$matches[2]';
             */

            if ( $execute_request ) {

                if ( $request == tickera_apply_filters( 'tickera_sales_credentials_request_name', 'sales_check_credentials' ) ) {
                    $this->check_credentials();
                }

                if ( $request == tickera_apply_filters( 'tickera_sales_stats_general_request_name', 'sales_stats_general' ) ) {
                    $this->get_stats_general();
                }

                if ( $request == tickera_apply_filters( 'tickera_sales_stats_event_request_name', 'sales_stats_event' ) ) {
                    $this->get_stats_event();
                }

                if ( $request == tickera_apply_filters( 'tickera_sales_stats_order_request_name', 'sales_stats_order' ) ) {
                    $this->get_stats_order();
                }
            }
        }

        function get_api_event() {
            return get_post_meta( $this->get_api_key_id(), 'event_name', true );
        }

        function get_api_key_id() {
            $args = array(
                'post_type' => 'tc_api_keys',
                'post_status' => 'any',
                'posts_per_page' => 1,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Must resolve the existing posts and meta.
                'meta_key' => 'api_key',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Must resolve the existing posts and meta.
                'meta_value' => $this->api_key
            );

            $post = get_posts( $args );

            if ( $post ) {
                return $post[ 0 ]->ID;

            } else {
                return false;
            }
        }

        function check_credentials( $echo = true ) {

            if ( $this->get_api_key_id() ) {
                $data = array(
                    'pass' => true //api key is valid
                );
            } else {
                $data = array(
                    'pass' => false //api key is NOT valid
                );
            }

            $data = tickera_apply_filters( 'tickera_sales_credentials_data_output', $data );
            $json = wp_json_encode( tickera_sanitize_array( $data ) );

            if ( $echo ) {
                wp_send_json( $json );
            }

            return $json;
        }

        function get_stats_general( $echo = true ) {
            global $tc, $wpdb;
            if ( $this->get_api_key_id() ) {
                /* //FOR PERIOD
                 * FOR EACH EVENT
                 * - event id
                 * - event name
                 * - number of sales
                 * - number of ticket types
                 *
                 */

                // Get revenue and number of orders for the selected period

                $cache_name_revenue = 'tc_stats_general_revenue_' . $this->period . '_' . $this->results_per_page . '_' . $this->page_number;
                $cache_name_number_of_orders = 'tc_stats_general_number_of_orders_' . $this->period . '_' . $this->results_per_page . '_' . $this->page_number;

                $revenue = wp_cache_get( $cache_name_revenue );
                $number_of_orders = wp_cache_get( $cache_name_number_of_orders );

                if ( false === $revenue || false === $number_of_orders ) {
                    $number_of_orders = 0;
                    $total_revenue = 0;
                    $wp_orders_search = new TC_Orders_Search( '', '', -1, array( 'order_paid', 'order_received' ), $this->period, $this->period_compare );

                    foreach ( $wp_orders_search->get_results() as $order ) {
                        $order_object = new TC_Order( $order->ID );
                        $total_revenue = $total_revenue + $order_object->details->tc_payment_info[ 'total' ];
                        $number_of_orders++;
                    }

                    $total_revenue = round( $total_revenue, 2 );
                    $revenue = $total_revenue;

                    wp_cache_set( $cache_name_revenue, $revenue );
                    wp_cache_set( $cache_name_number_of_orders, $number_of_orders );
                }

                $data = array(
                    'revenue' => stripslashes( $revenue ),
                    'currency' => stripslashes( $tc->get_cart_currency() ),
                    'number_of_orders' => $number_of_orders
                );

                $data = tickera_apply_filters( 'tickera_get_stats_general_data_output', $data );
                $json = wp_json_encode( tickera_sanitize_array( $data ) );

                if ( $echo ) {
                    wp_send_json( $json );
                }

                return $json;
            }
        }

        function get_stats_event( $echo = true ) {
            if ( $this->get_api_key_id() ) {

            }
        }

        function get_stats_order( $echo = true ) {
            if ( $this->get_api_key_id() ) {

            }
        }

        /**
         * @param bool $echo
         * @return bool|false|float|string
         */
        function get_event_essentials( $echo = true ) {

            if ( $this->get_api_key_id() ) {

                $event_id = $this->get_api_event();

                $event = new \Tickera\TC_Event( $event_id );
                $event_ticket_types = $event->get_event_ticket_types();

                $event_tickets_total = 0;
                $event_checkedin_tickets = 0;

                $meta_query = array( 'relation' => 'OR' );

                foreach ( $event_ticket_types as $event_ticket_type ) {
                    $meta_query[] = array(
                        'key' => 'ticket_type_id',
                        'value' => (string) $event_ticket_type,
                    );
                }

                $args = array(
                    'post_type' => 'tc_tickets_instances',
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Must resolve the existing posts and meta.
                    'meta_query' => $meta_query
                );

                $ticket_instances = get_posts( $args );

                $tickets_sold = 0;

                foreach ( $ticket_instances as $ticket_instance ) {
                    $order = new \Tickera\TC_Order( $ticket_instance->post_parent );

                    if ( $order->details->post_status == 'order_paid' ) {
                        $tickets_sold++;
                    }

                    $checkins = get_post_meta( $ticket_instance->ID, 'tc_checkins', true );
                    $checkins = $checkins ? $checkins : [];

                    $checkedin_statuses = array_column( $checkins, 'status' );
                    if ( in_array( 'Pass', $checkedin_statuses ) ) {
                        $event_checkedin_tickets++;
                    }
                }

                $event_tickets_total = $tickets_sold;

                $data = array(
                    'event_name' => stripslashes( $event->details->post_title ),
                    'event_date_time' => tickera_format_date( strtotime( $event->details->event_date_time ) ),
                    'event_location' => stripslashes( $event->details->event_location ),
                    'event_logo' => stripslashes( $event->details->event_logo_file_url ),
                    'event_sponsors_logos' => stripslashes( $event->details->sponsors_logo_file_url ),
                    'sold_tickets' => $event_tickets_total,
                    'checked_tickets' => $event_checkedin_tickets,
                    'pass' => true
                );

                $data = tickera_apply_filters( 'tickera_get_event_essentials_data_output', $data, $event_id );
                $json = wp_json_encode( tickera_sanitize_array( $data ) );

                if ( $echo ) {
                    wp_send_json( $json );
                }

                return $json;
            }
        }

        function ticket_checkins( $echo = true ) {

            if ( $this->get_api_key_id() ) {

                $ticket_id = tickera_ticket_code_to_id( $this->ticket_code );
                $ticket_instance = new \Tickera\TC_Ticket_Instance( $ticket_id );

                $check_ins = get_post_meta( $ticket_id, 'tc_checkins', true );
                $check_ins = tickera_apply_filters( 'tickera_ticket_checkins_array', $check_ins );

                $rows = [];
                foreach ( $check_ins as $check_in ) {
                    $r[ 'date_checked' ] = wp_date( 'Y-m-d H:i:s', $check_in[ 'date_checked' ] );
                    $r[ 'status' ] = $check_in[ 'status' ];
                    $rows[] = [ 'data' => $r ];
                }

                wp_send_json( wp_json_encode( $rows ) );
            }
        }

        function ticket_checkin( $echo = true ) {

            if ( $this->get_api_key_id() ) {

                $api_key_id = $this->get_api_key_id();
                $ticket_id = tickera_ticket_code_to_id( $this->ticket_code );

                if ( $ticket_id ) {

                    $ticket_instance = new \Tickera\TC_Ticket_Instance( $ticket_id );
                    $ticket_type_id = $ticket_instance->details->ticket_type_id;
                    $ticket_type = new \Tickera\TC_Ticket( $ticket_type_id );
                    $order = new \Tickera\TC_Order( $ticket_instance->details->post_parent );

                    if ( $order->details->post_status == 'order_paid' ) {
                        // All good, continue with check-in process

                    } else {
                        esc_html_e( 'Ticket does not exist', 'tickera-event-ticketing-system' );
                        exit;
                    }

                    $ticket_event_id = $ticket_type->get_ticket_event( $ticket_type_id );
                } else {
                    esc_html_e( 'Ticket does not exist', 'tickera-event-ticketing-system' );
                    exit;
                }

                if ( $this->get_api_event() != $ticket_event_id ) {//Only API key for the parent event can check-in this ticket
                    if ( $echo ) {
                        esc_html_e( 'Insufficient permissions. This API key cannot check-in this ticket.', 'tickera-event-ticketing-system' );
                    } else {
                        return 403; //error code for incufficient persmissions
                    }
                    exit;
                }

                $check_ins = $ticket_instance->get_ticket_checkins();

                $num_of_check_ins = tickera_apply_filters( 'tickera_num_of_checkins', ( is_array( $check_ins ) ? count( $check_ins ) : 0 ) );

                $available_checkins = ( is_numeric( $ticket_type->details->available_checkins_per_ticket ) ? $ticket_type->details->available_checkins_per_ticket : 9999 ); //9999 means unlimited check-ins but it's set for easier comparation

                if ( $available_checkins > $num_of_check_ins ) {
                    $check_in_status = tickera_apply_filters( 'tickera_checkin_status_name', true );
                    $check_in_status_bool = true;
                } else {
                    $check_in_status = tickera_apply_filters( 'tickera_checkin_status_name', false );
                    $check_in_status_bool = false;
                }

                $new_checkins = array();

                if ( is_array( $check_ins ) ) {
                    foreach ( $check_ins as $check_in ) {
                        $new_checkins[] = $check_in;
                    }
                }

                $new_checkin = array(
                    "date_checked" => time(),
                    "status" => $check_in_status ? tickera_apply_filters( 'tickera_checkin_status_name', 'Pass' ) : tickera_apply_filters( 'tickera_checkin_status_name', 'Fail' ),
                    "api_key_id" => $api_key_id
                );

                $new_checkins[] = tickera_apply_filters( 'tickera_new_checkin_array', $new_checkin );

                tickera_do_action( 'tickera_before_checkin_array_update' );
                update_post_meta( $ticket_id, "tc_checkins", tickera_sanitize_array( $new_checkins, false, true ) );
                tickera_do_action( 'tickera_after_checkin_array_update' );

                $payment_date = tickera_apply_filters( 'tickera_checkin_payment_date', tickera_format_date( $order->details->tc_order_date ) );

                if ( $payment_date == '' ) {
                    $payment_date = 'N/A';
                }

                $name = tickera_apply_filters( 'tickera_checkin_owner_name', $ticket_instance->details->first_name . ' ' . $ticket_instance->details->last_name );

                if ( trim( $name ) == '' ) {
                    $name = 'N/A';
                }

                $address = tickera_apply_filters( 'tickera_checkin_owner_address', $ticket_instance->details->address );

                if ( $address == '' ) {
                    $address = 'N/A';
                }

                $city = tickera_apply_filters( 'tickera_checkin_owner_city', $ticket_instance->details->city );

                if ( $city == '' ) {
                    $city = 'N/A';
                }

                $state = tickera_apply_filters( 'tickera_checkin_owner_state', $ticket_instance->details->state );

                if ( $state == '' ) {
                    $state = 'N/A';
                }

                $country = tickera_apply_filters( 'tickera_checkin_owner_country', $ticket_instance->details->country );

                if ( $country == '' ) {
                    $country = 'N/A';
                }

                $data = array(
                    'status' => $check_in_status_bool, //false
                    'previous_status' => '',
                    'pass' => true, //api is valid
                    'name' => $name,
                    'payment_date' => $payment_date,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'checksum' => $this->ticket_code
                );

                $data[ 'custom_fields' ] = array(
                    array( 'Ticket Type', $ticket_type->details->post_title ),
                    array( 'Buyer Name', $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] . ' ' . $order->details->tc_cart_info[ 'buyer_data' ][ 'last_name_post_meta' ] ),
                    array( 'Buyer E-mail', $order->details->tc_cart_info[ 'buyer_data' ][ 'email_post_meta' ] ),
                );

                $data[ 'custom_fields' ] = tickera_apply_filters( 'tickera_checkin_custom_fields', $data[ 'custom_fields' ], $ticket_instance->details->ID, $ticket_event_id );
                $data = tickera_apply_filters( 'tickera_checkin_output_data', $data );

                if ( $echo === true || $echo == 'echo' ) {
                    wp_send_json( $data );
                }

                return $data;
            }
        }

        function tickets_info( $echo = true ) {
            if ( $this->get_api_key_id() ) {

                $event_id = $this->get_api_event();

                $ticket_search = new \Tickera\TC_Tickets_Instances_Search( $this->keyword, $this->page_number, $this->results_per_page, false, true, 'event_id', $event_id );

                $results = $ticket_search->get_results();

                $results_count = 0;

                foreach ( $results as $result ) {
                    $ticket_instance = new \Tickera\TC_Ticket_Instance( $result->ID );
                    $ticket_type = new \Tickera\TC_Ticket( $ticket_instance->details->ticket_type_id );

                    $order = new \Tickera\TC_Order( $ticket_instance->details->post_parent );
                    if ( $order->details->post_status == 'order_paid' ) {
                        /* OLD */
                        $check_ins = get_post_meta( $ticket_instance->details->ID, 'tc_checkins', true );
                        $checkin_date = '';

                        if ( ! empty( $check_ins ) ) {
                            foreach ( $check_ins as $check_in ) {
                                $checkin_date = wp_date( 'Y-m-d H:i:s', $check_in[ 'date_checked' ] );
                            }
                        }

                        $r[ 'date_checked' ] = $checkin_date;

                        $r[ 'payment_date' ] = tickera_format_date( strtotime( $order->details->post_modified ) ); ////date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->details->post_modified ), false );
                        $r[ 'transaction_id' ] = $ticket_instance->details->ticket_code;
                        $r[ 'checksum' ] = $ticket_instance->details->ticket_code;

                        if ( ! empty( $ticket_instance->details->first_name ) && ! empty( $ticket_instance->details->last_name ) ) {
                            $r[ 'buyer_first' ] = $ticket_instance->details->first_name;
                            $r[ 'buyer_last' ] = $ticket_instance->details->last_name;
                        } else {
                            $r[ 'buyer_first' ] = $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ];
                            $r[ 'buyer_last' ] = $order->details->tc_cart_info[ 'buyer_data' ][ 'last_name_post_meta' ];
                        }

                        $r[ 'custom_fields' ] = array(
                            array( 'Ticket Type', $ticket_type->details->post_title ),
                            array( 'Buyer Name', $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] . ' ' . $order->details->tc_cart_info[ 'buyer_data' ][ 'last_name_post_meta' ] ),
                            array( 'Buyer E-mail', $order->details->tc_cart_info[ 'buyer_data' ][ 'email_post_meta' ] ),
                            //array( 'Buyer Name', $r[ 'buyer_first' ] . ' ' . $r[ 'buyer_last' ] ),
                            //array( 'Example Field 1', 'Val 1' ),
                            //array( 'Example Field 2', 'Val 2' ),
                        );

                        $r[ 'custom_fields' ] = tickera_apply_filters( 'tickera_checkin_custom_fields', $r[ 'custom_fields' ], $result->ID, $event_id );

                        $r[ 'custom_field_count' ] = count( $r[ 'custom_fields' ] );

                        $r[ 'address' ] = '';
                        if ( $r[ 'address' ] == '' ) {
                            $r[ 'address' ] = 'N/A';
                        }

                        $r[ 'city' ] = '';
                        if ( $r[ 'city' ] == '' ) {
                            $r[ 'city' ] = 'N/A';
                        }

                        $r[ 'state' ] = '';
                        if ( $r[ 'state' ] == '' ) {
                            $r[ 'state' ] = 'N/A';
                        }

                        $r[ 'country' ] = '';
                        if ( $r[ 'country' ] == '' ) {
                            $r[ 'country' ] = 'N/A';
                        }

                        $rows[] = array( 'data' => $r );
                        /* END OLD */

                        $results_count++;
                    }
                }

                $additional[ 'results_count' ] = $results_count;
                $rows[] = array( 'additional' => $additional );
                wp_send_json( wp_json_encode( $rows ) );
            }
        }
    }
}
