<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Checkin_API' ) ) {

    class TC_Checkin_API {

        var $api_key = '';
        var $ticket_code = '';
        var $page_number = 1;
        var $results_per_page = 10;
        var $keyword = '';
        var $return_method = '';

        function __construct( $api_key, $request, $return_method = 'echo', $ticket_code = '', $execute_request = true ) {

            global $wp;

            if ( defined( 'TC_DEBUG' ) || isset( $_GET[ 'tc_debug' ] ) ) {
                error_reporting( E_ALL );
                @ini_set( 'display_errors', 'On' );
            }

            $this->api_key = $api_key;
            $checksum = isset( $wp->query_vars[ 'checksum' ] ) ? sanitize_key( $wp->query_vars[ 'checksum' ] ) : ( isset( $_REQUEST[ 'checksum' ] ) ? sanitize_key( $_REQUEST[ 'checksum' ] ) : '' );
            $page_number = isset( $wp->query_vars[ 'page_number' ] ) ? (int) $wp->query_vars[ 'page_number' ] : ( isset( $_REQUEST[ 'page_number' ] ) ? (int) $_REQUEST[ 'page_number' ] : apply_filters( 'tc_ticket_info_default_page_number', 1 ) );
            $results_per_page = isset( $wp->query_vars[ 'results_per_page' ] ) ? (int) $wp->query_vars[ 'results_per_page' ] : ( isset( $_REQUEST[ 'results_per_page' ] ) ? (int) $_REQUEST[ 'results_per_page' ] : apply_filters( 'tc_ticket_info_default_results_per_page', 50 ) );
            $keyword = isset( $wp->query_vars[ 'keyword' ] ) ? sanitize_text_field( $wp->query_vars[ 'keyword' ] ) : ( isset( $_REQUEST[ 'keyword' ] ) ? sanitize_text_field( $_REQUEST[ 'keyword' ] ) : '' );

            if ( $checksum !== '' ) {

                // Old QR code character
                $findme = 'checksum';

                $pos = strpos( $checksum, $findme );

                if ( $pos === false ) {
                    /*
                     * New code
                     * $checksum
                     */

                } else {

                    // Old code
                    $ticket_strings_array = explode( '%7C', $checksum );
                    $checksum = end( $ticket_strings_array );
                }
            }

            $this->ticket_code = apply_filters( 'tc_ticket_code_var_name', isset( $ticket_code ) && $ticket_code != '' ? $this->extract_checksum_from_code( $ticket_code ) : $this->extract_checksum_from_code( $checksum ) );
            $this->page_number = apply_filters( 'tc_tickets_info_page_number_var_name', $page_number );
            $this->results_per_page = apply_filters( 'tc_tickets_info_results_per_page_var_name', $results_per_page );
            $this->keyword = apply_filters( 'tc_tickets_info_keyword_var_name', $keyword );
            $this->return_method = $return_method;

            if ( $execute_request ) {

                header( "Cache-Control: no-cache, no-store, must-revalidate" ); // HTTP 1.1.
                header( "Pragma: no-cache" ); // HTTP 1.0.
                header( "Expires: 0" ); // Proxies.
                header( "Content-type: application/json;" );

                // Allow from any origin
                if ( isset( $_SERVER[ 'HTTP_ORIGIN' ] ) ) {
                    header( "Access-Control-Allow-Origin: " . sanitize_text_field( $_SERVER['HTTP_ORIGIN'] ) );
                    header( 'Access-Control-Allow-Credentials: true' );
                    header( 'Access-Control-Max-Age: 86400' );    // cache for 1 day
                }

                // Access-Control headers are received during OPTIONS requests
                if ( $_SERVER[ 'REQUEST_METHOD' ] == 'OPTIONS' ) {

                    if ( isset( $_SERVER[ 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' ] ) )
                        header( "Access-Control-Allow-Methods: GET, POST, OPTIONS" );

                    if ( isset( $_SERVER[ 'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' ] ) )
                        header( "Access-Control-Allow-Headers: " . sanitize_text_field( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ) );
                }

                try {

                    if ( (int) @ini_get( 'output_buffering' ) === 1 || strtolower( @ini_get( 'output_buffering' ) ) === 'on' ) {
                        ob_flush();
                        @ob_start( 'ob_gzhandler' );
                    }

                } catch ( Exception $e ) {
                    // Do not compress
                }

                if ( $request == apply_filters( 'tc_translation_request_name', 'tickera_translation' ) ) {
                    $this->translation();
                }

                if ( $request == apply_filters( 'tc_check_credentials_request_name', 'tickera_check_credentials' ) ) {
                    $this->check_credentials();
                }

                if ( $request == apply_filters( 'tc_event_essentials_request_name', 'tickera_event_essentials' ) ) {
                    $this->get_event_essentials();
                }

                if ( $request == apply_filters( 'tc_checkins_request_name', 'tickera_checkins' ) ) {
                    $this->ticket_checkins();
                }

                if ( $request == apply_filters( 'tc_scan_request_name', 'tickera_scan' ) ) {
                    $this->ticket_checkin( $return_method );
                }

                if ( $request == apply_filters( 'tc_tickets_info_request_name', 'tickera_tickets_info' ) ) {
                    $this->tickets_info();
                }
            }
        }

        function extract_checksum_from_code( $code ) {

            if ( $code !== '' ) {

                $findme = 'checksum'; // Old or QR code characters
                $pos = strpos( $code, $findme );

                if ( $pos === false ) {
                    /*
                     * New code
                     * $checksum
                     */

                } else {

                    // Old code
                    if ( strpos( $code, '|' ) ) {
                        $ticket_strings_array = explode( '|', $code ); // Received from barcode reader addon
                        $code = end( $ticket_strings_array );
                    }

                    if ( strpos( $code, '%7C' ) ) {
                        $ticket_strings_array = explode( '%7C', $code ); // Received from mobile app when reading a QR code or from 2D barcode reader
                        $code = end( $ticket_strings_array );
                    }

                    if ( strpos( $code, '~' ) ) {
                        $ticket_strings_array = explode( '~', $code ); // Received from 2D barcode reader like this one QR Barcode Scanner Eyoyo EY-001
                        $code = end( $ticket_strings_array );
                    }
                }
            }

            return $code;
        }

        function get_api_event() {
            return get_post_meta( $this->get_api_key_id(), 'event_name', true );
        }

        function get_api_key_id() {

            $args = array(
                'post_type' => 'tc_api_keys',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'meta_key' => 'api_key',
                'meta_value' => $this->api_key,
                'fields' => 'ids',
            );

            $post = get_posts( $args );

            if ( isset( $post[ 0 ] ) ) {
                return $post[ 0 ];

            } else {
                return false;
            }
        }

        function translation( $echo = true ) {

            if ( $this->get_api_key_id() ) {

                $data = array(
                    'WORDPRESS_INSTALLATION_URL' => 'WORDPRESS INSTALLATION URL',
                    'API_KEY' => 'API KEY',
                    'AUTO_LOGIN' => 'AUTO LOGIN',
                    'SIGN_IN' => 'SIGN IN',
                    'SOLD_TICKETS' => 'TICKETS SOLD',
                    'CHECKED_IN_TICKETS' => 'CHECKED-IN TICKETS',
                    'HOME_STATS' => 'Home - Stats',
                    'LIST' => 'LIST',
                    'SIGN_OUT' => 'SIGN OUT',
                    'CHECK_IN' => 'CHECK IN',
                    'CANCEL' => 'CANCEL',
                    'SEARCH' => 'Search',
                    'ID' => 'ID',
                    'PURCHASED' => 'PURCHASED',
                    'CHECKINS' => 'CHECK-INS',
                    'CHECK_IN' => 'CHECK IN',
                    'SUCCESS' => 'SUCCESS',
                    'SUCCESS_MESSAGE' => 'Ticket has been checked in',
                    'OK' => 'OK',
                    'ERROR' => 'ERROR',
                    'ERROR_MESSAGE' => 'Wrong ticket code',
                    'PASS' => 'Pass',
                    'FAIL' => 'Fail',
                    'ERROR_LOADING_DATA' => 'Error loading data. Please check the URL and API KEY provided',
                    'API_KEY_LOGIN_ERROR' => 'Error. Please check the URL and API KEY provided',
                    'APP_TITLE' => 'Ticket Check-in',
                    'PLEASE_WAIT' => 'Please wait...',
                    'EMPTY_LIST' => 'The list is empty',
                    'ERROR_LICENSE_KEY' => 'License key is not valid. Please contact your administrator.'
                );

            } else {
                $data = array(
                    'pass' => false // Api key is NOT valid
                );
            }

            $data = apply_filters( 'tc_translation_data_output', $data );
            $json = tickera_sanitize_array( $data );

            if ( $echo ) {
                wp_send_json( $json );
            }

            return $json;
        }

        function check_credentials( $echo = true ) {

            $time_start = microtime( true );

            if ( $this->get_api_key_id() ) {

                $data = array(
                    'pass' => true, // Api key is valid
                    'license_key' => tickera_get_license_key(),
                    'admin_email' => tickera_get_license_email(),
                    'tc_iw_is_pr' => tickera_iw_is_pr(),
                    'check_type' => apply_filters( 'tc_checkinera_check_type', 'email' )
                );

            } else {
                $data = array(
                    'pass' => false // Api key is NOT valid
                );
            }

            $time_end = microtime( true );
            $execution_time = ( $time_end - $time_start );
            $data[ 'execution_time' ] = $execution_time;

            $data = apply_filters( 'tc_check_credentials_data_output', $data );
            $json = tickera_sanitize_array( $data );

            if ( $echo ) {
                wp_send_json( $json );
            }

            return $json;
        }

        /**
         * Render Event's Collection/Essentials.
         * Intitialize Ticket Check-in app with Event's essentials
         *
         * @param bool $echo
         * @return bool|false|float|string
         */
        function get_event_essentials( $echo = true ) {

            $start = microtime( true );

            if ( $this->get_api_key_id() ) {

                global $wpdb;

                $event_ids = (array) $this->get_api_event();
                $event_ids = self::maybe_format_event_ids_array( $event_ids );

                $order_statuses = apply_filters( 'tc_paid_post_statuses', [ 'order_paid' ] );
                $prepare_order_statuses_placeholder = implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) );

                if ( in_array( 'all', $event_ids ) ) {

                    if ( ( $index = array_search( 'all', $event_ids ) ) !== false ) {
                        unset( $event_ids[ $index ] );
                    }

                    $query = $wpdb->prepare( "SELECT ID, ( SELECT post_status FROM {$wpdb->posts} wp2 WHERE wp2.ID = wp.post_parent ) as parent_status FROM {$wpdb->posts} wp, {$wpdb->postmeta} wp_pm WHERE post_type = 'tc_tickets_instances' AND wp.ID = wp_pm.post_id AND wp_pm.meta_key = 'event_id' AND post_status = 'publish' GROUP BY wp.ID HAVING (parent_status IN ($prepare_order_statuses_placeholder))", $order_statuses );

                } else {
                    $prepare_event_ids_placeholder = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
                    $prepare_arguments = array_merge( $event_ids, $order_statuses );
                    $query = $wpdb->prepare( "SELECT ID, ( SELECT post_status FROM {$wpdb->posts} wp2 WHERE wp2.ID = wp.post_parent ) as parent_status FROM {$wpdb->posts} wp, {$wpdb->postmeta} wp_pm WHERE post_type = 'tc_tickets_instances' AND wp.ID = wp_pm.post_id AND wp_pm.meta_key = 'event_id' AND wp_pm.meta_value IN ($prepare_event_ids_placeholder) AND post_status = 'publish' GROUP BY wp.ID HAVING (parent_status IN ($prepare_order_statuses_placeholder))", $prepare_arguments );
                }

                $results = $wpdb->get_results( $query, ARRAY_A );

                $event_tickets_total = 0;
                $event_checkedin_tickets = 0;

                foreach ( $results as $result ) {

                    $result_id = $result[ 'ID' ];
                    $checkins = get_post_meta( $result_id, 'tc_checkins', true );

                    if ( $checkins ) {
                        $checkedin_statuses = array_column( $checkins, 'status' );
                        if ( in_array( 'Pass', $checkedin_statuses ) ) {
                            $event_checkedin_tickets++;
                        }
                    }

                    $event_tickets_total++;
                }

                $data = [
                    'sold_tickets' => $event_tickets_total,
                    'checked_tickets' => $event_checkedin_tickets,
                    'execution_time' => microtime( true ) - $start,
                    'pass' => true
                ];

                // Multiple Events
                if ( count( $event_ids ) > 1 ) {

                    $data[ 'event_name' ] = __( 'Multiple Events', 'tickera-event-ticketing-system' );
                    $data[ 'event_date_time' ] = __( 'N/A', 'tickera-event-ticketing-system' );
                    $data[ 'event_location' ] = __( 'N/A', 'tickera-event-ticketing-system' );

                } else {

                    $data[ 'event_name' ] = html_entity_decode( stripslashes( get_the_title( reset( $event_ids ) ) ) );
                    $data[ 'event_date_time' ] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( get_post_meta( reset( $event_ids ), 'event_date_time', true ) ), false );
                    $data[ 'event_location' ] = stripslashes( get_post_meta( reset( $event_ids ), 'event_location', true ) );
                }

                $data = apply_filters( 'tc_get_event_essentials_data_output', $data, $event_ids, $this->get_api_key_id() );
                $json = tickera_sanitize_array( $data, true, true );

                if ( $echo ) {
                    wp_send_json( $json );
                }

                return $json;
            }
        }

        /**
         * Process the allowed number of checkins a ticket instance can have.
         * Filtered by per ticket type or time basis ( e.g allowed checkins per hour, days, week, month )
         *
         * @param bool $ticket_id
         * @param bool $ticket_type
         * @return int|string
         */
        public static function get_number_of_allowed_checkins_for_ticket_instance( $ticket_id = false, $ticket_type = false ) {

            // Ticket instance id and ticket type object are required so we cannot proceed without them
            if ( ! $ticket_id || ! $ticket_type || ! $ticket_type->details ) {
                return 0;
            }

            $ticket_type_id = apply_filters( 'tc_ticket_type_id', $ticket_type->details->ID );
            $checkins_data = get_post_meta( $ticket_id, 'tc_checkins', true );
            $pass_checkin_status = apply_filters( 'tc_checkin_status_title_get_number_of_allowed_checkins_for_ticket_instance', 'Pass' );

            /**
             * Available Check-ins - Variables
             */
            $valid_checkins = 0;
            $available_checkins = get_post_meta( $ticket_type_id, apply_filters( 'tc_available_checkins_per_ticket_field_name', 'available_checkins_per_ticket', $ticket_id, $ticket_type_id ), true );
            $available_checkins = ( is_numeric( $available_checkins ) ? (int) $available_checkins : 99999 ); // 99999 means unlimited check-ins but it's set for easier comparison

            /**
             * Checkins on Time Basis - Variables
             */
            $valid_time_base_checkins = 0;
            $checkins_time_basis = get_post_meta( $ticket_type_id, apply_filters( 'tc_checkins_time_basis_field_name', 'checkins_time_basis', $ticket_id ), true );
            $checkins_time_basis = ( $checkins_time_basis ) ? $checkins_time_basis : 'no';

            if ( 'no' == $checkins_time_basis ) {

                // Unlimited as default.
                $allowed_checkins_per_time_basis = 99999;

            } else {
                $allowed_checkins_per_time_basis = get_post_meta( $ticket_type_id, apply_filters( 'tc_allowed_checkins_per_time_basis_field_name', 'allowed_checkins_per_time_basis', $ticket_id ), true );
                $allowed_checkins_per_time_basis = ( is_numeric( $allowed_checkins_per_time_basis ) ) ? (int) $allowed_checkins_per_time_basis : 99999; // 99999 means unlimited check-ins but it's set for easier comparison
            }

            $basis = get_post_meta( $ticket_type_id, apply_filters( 'tc_checkins_time_basis_type_field_name', 'checkins_time_basis_type', $ticket_id ), true );
            $date_checked = isset( $_GET[ 'timestamp' ] ) ? intval( sanitize_text_field( $_GET[ 'timestamp' ] ) ) : date_i18n( 'U' );

            $calendar_basis = get_post_meta( $ticket_type_id, apply_filters( 'tc_checkins_time_calendar_basis_field_name', 'checkins_time_calendar_basis', $ticket_id ), true );
            $calendar_basis = $calendar_basis ? $calendar_basis : 'no';

            $interval = [
                'hour' => 60 * 60, // Seconds * Minutes
                'day' => 60 * 60 * 24, // Seconds * Minutes * Hours
                'week' => 60 * 60 * 24 * 7, // Seconds * Minutes * Hours * Days
                'month' => 60 * 60 * 24 * 30 // Seconds * Minutes * Hours * Days
            ];

            $calendar = [
                'day' => 'today midnight',
                'week' => 'this week midnight',
                'month' => 'first day of this month midnight'
            ];

            if ( is_array( $checkins_data ) && count( $checkins_data ) > 0 ) {

                foreach ( $checkins_data as $check_in ) {

                    if ( $check_in[ 'status' ] == $pass_checkin_status ) {

                        $valid_checkins++;

                        // Checkin Time Basis
                        if ( 'yes' == $checkins_time_basis ) {

                            if ( 'yes' == $calendar_basis && 'hour' != $basis ) {

                                if ( strtotime( $calendar[ $basis ], $check_in[ 'date_checked' ] ) == strtotime( $calendar[ $basis ], $date_checked ) ) {
                                    $valid_time_base_checkins++;
                                }

                            } else {

                                /*
                                 * Time difference between the current and previous date checked. In seconds.
                                 * If there's no previous date record, therefore the time difference would be 0.
                                 */
                                $time_difference = ( $check_in[ 'date_checked' ] ) ? ( intval( $date_checked ) - intval( $check_in[ 'date_checked' ] ) ) : 0;
                                $time_basis = $interval[ $basis ];

                                if ( $time_basis >= $time_difference ) {
                                    $valid_time_base_checkins++;
                                }
                            }
                        }
                    }
                }
            }

            /**
             * Calculate the remaining allowed checkins.
             *
             * Conditions:
             * 1. Fail the response if the checkin count per time basis reaches the limit.
             * 2. Fail the response if the overall checkin count reaches the configured available checkins limit.
             */
            $remaining_checkins = $available_checkins - $valid_checkins;
            $remaining_time_base_checkins = $allowed_checkins_per_time_basis - $valid_time_base_checkins;

            if ( $remaining_time_base_checkins > 0 && $remaining_checkins > 0 ) {
                return $remaining_checkins;

            } else {
                return 0;
            }
        }

        function ticket_checkins( $echo = true ) {

            if ( $this->get_api_key_id() ) {

                $ticket_id = tickera_ticket_code_to_id( $this->ticket_code );
                $check_ins = get_post_meta( $ticket_id, 'tc_checkins', true );

                $rows = [];
                $check_ins = apply_filters( 'tc_ticket_checkins_array', $check_ins );

                if ( isset( $check_ins ) && is_array( $check_ins ) && count( $check_ins ) > 0 ) {
                    foreach ( $check_ins as $check_in ) {
                        $r[ 'date_checked' ] = apply_filters( 'tc_check_in_date_checked', tickera_format_date( $check_in[ 'date_checked' ] ), $ticket_id, $this->get_api_key_id() );
                        $r[ 'status' ] = apply_filters( 'tc_check_in_status_title', $check_in[ 'status' ], $ticket_id, $this->get_api_key_id() );
                        $rows[] = array( 'data' => $r );
                    }
                }

                $rows = tickera_sanitize_array( $rows, false, true );
                wp_send_json( $rows );
            }
        }


        /**
         * Check if the number is from ean13 barcode
         *
         * @param type $digits
         * @return boolean
         */
        function tc_ean13_check_digit( $digits ) {

            $digits_new = $this->tc_ean13_convert( $digits );

            // First change digits to a string so that we can access individual numbers
            $digits_new = (string) $digits_new;

            // 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
            $even_sum = (int) $digits_new[ 1 ] + (int) $digits_new[ 3 ] + (int) $digits_new[ 5 ] + (int) $digits_new[ 7 ] + (int) $digits_new[ 9 ] + (int) $digits_new[ 11 ];

            // 2. Multiply this result by 3.
            $even_sum_three = $even_sum * 3;

            // 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
            $odd_sum = (int) $digits_new[ 0 ] + (int) $digits_new[ 2 ] + (int) $digits_new[ 4 ] + (int) $digits_new[ 6 ] + (int) $digits_new[ 8 ] + (int) $digits_new[ 10 ];

            // 4. Sum the results of steps 2 and 3.
            $total_sum = $even_sum_three + $odd_sum;

            // 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
            $next_ten = ( ceil( $total_sum / 10 ) ) * 10;
            $check_digit = $next_ten - $total_sum;
            $tc_new_digit = $digits_new . $check_digit;

            return ( $tc_new_digit == $digits ) ? true : false;
        }


        /**
         * Convert ean13 to normal barcode
         *
         * @param $digits
         * @return false|string
         */
        function tc_ean13_convert( $digits ) {
            $digits_count = strlen( $digits );
            $check_first = substr( $digits, 0, 1 );
            if ( $check_first !== 0 && 13 == $digits_count ) {
                $check_first = '';
            }
            $digits_new = substr( $digits, 0, -1 );
            $digits_new = $check_first . $digits_new;
            return $digits_new;
        }

        /**
         * Check if check-out is activated.
         * If attendee checkin the second time, it will count as checkout and remove the checkin entry from the collection
         * Admin Dashboard: Tickera > Settings > General > Store Settings
         *
         * @param $ticket_instance_id
         * @return array|mixed
         */
        function validate_ticket_checkout( $ticket_instance_id ) {

            if ( $ticket_instance_id !== '' ) {

                $tc_general_setting = get_option( 'tickera_general_setting' );

                if ( $tc_general_setting ) {

                    $globally_allow_ticket_checkout = isset( $tc_general_setting[ 'allow_global_ticket_checkout' ] ) ? $tc_general_setting[ 'allow_global_ticket_checkout' ] : 'no';
                    $allow_ticket_checkout_field_name = apply_filters( 'tc_allow_ticket_checkout_field_name', 'allow_ticket_checkout' );
                    $ticket_type_id = get_post_meta( $ticket_instance_id, 'ticket_type_id', true );
                    $ticket_type_id = ( 'product_variation' == get_post_type( $ticket_type_id ) ) ? wp_get_post_parent_id( $ticket_type_id ) : $ticket_type_id;
                    $allow_ticket_checkout = ( metadata_exists( 'post', $ticket_type_id, $allow_ticket_checkout_field_name ) ) ? get_post_meta( $ticket_type_id, $allow_ticket_checkout_field_name, true ) : 'no';

                    if ( 'yes' == $globally_allow_ticket_checkout || ( 'no' == $globally_allow_ticket_checkout && 'yes' == $allow_ticket_checkout ) ) {
                        $checkins = get_post_meta( $ticket_instance_id, 'tc_checkins', true );

                        if ( $checkins ) {

                            $checkouts = get_post_meta( $ticket_instance_id, 'tc_checkouts', true );
                            $checkouts = ( $checkouts ) ? $checkouts : [];

                            // Process only those with 'Pass' status
                            $passed_checkins = [];
                            foreach ( $checkins as $key => $checkin ) {
                                if ( 'Pass' == $checkin[ 'status' ] ) {
                                    $passed_checkins[ $key ] = $checkin;
                                }
                            }
                            $is_for_checkout = ( count( $passed_checkins ) % 2 ) ? false : true;

                            if ( $is_for_checkout ) {

                                /*
                                 * Remove the latest checkin entry from its collection
                                 */
                                $_keys_hash = array_keys( $passed_checkins );
                                unset( $checkins[ end( $_keys_hash ) ] );
                                array_pop( $_keys_hash );
                                unset( $checkins[ end( $_keys_hash ) ] );
                                $checkouts[ 'ins' ] = $checkins;

                                /*
                                 * Populate data onto checkouts collections
                                 */
                                $latest_checkin = end( $passed_checkins );
                                $checkouts[ 'outs' ][] = [
                                    'date_checked' => isset( $_GET[ 'timestamp' ] ) ? intval( sanitize_text_field( $_GET[ 'timestamp' ] ) ) : time(),
                                    'status' => $latest_checkin[ 'status' ],
                                    'api_key_id' => $latest_checkin[ 'api_key_id' ]
                                ];

                            } else {

                                /*
                                 * Remove the latest checkout entry
                                 */
                                $_checkouts = $checkouts;
                                array_pop( $_checkouts );
                                $checkouts[ 'ins' ] = $checkins;
                                $checkouts[ 'outs' ] = $_checkouts;
                            }

                            return $checkouts;
                        }
                    }
                }
            }
        }

        /**
         * Check-in Attendee
         *
         * @param bool $echo
         * @return array|int|mixed|void
         */
        function ticket_checkin( $echo = true ) {

            /**
             * Override Ticket Checkin Process.
             * @since 3.5.2.7
             */
            $results = apply_filters( 'tc_results_before_ticket_checkin', [], $this);

            if ( $results ) {

                $results = tickera_sanitize_array( $results, true, true );

                if ( $echo ) {
                    wp_send_json( $results );

                } else {
                    return $results;
                }
            }

            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $ean13_convert_enabled = isset( $tc_general_settings[ 'ean_13_checker' ] ) ? $tc_general_settings[ 'ean_13_checker' ] : 'no';

            if ( $this->get_api_key_id() ) {

                $api_key_id = $this->get_api_key_id();
                $tc_code_length = strlen( $this->ticket_code );
                $ticket_code = $this->ticket_code;

                if ( 'yes' == $ean13_convert_enabled ) {

                    // Ean 13 contains 12 numberic characters
                    if ( in_array( $tc_code_length, [ 12, 13 ] ) && is_numeric( $this->ticket_code ) ) {

                        $tc_check_ean13 = $this->tc_ean13_check_digit( $this->ticket_code );

                        if ( true == $tc_check_ean13 )
                            $ticket_code = $this->tc_ean13_convert( $this->ticket_code );
                    }
                }

                $ticket_id = tickera_ticket_code_to_id( $ticket_code );

                if ( $ticket_id ) {

                    $ticket_instance = new \Tickera\TC_Ticket_Instance( $ticket_id );
                    $ticket_type_id = apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id );

                    $ticket_type = new \Tickera\TC_Ticket( $ticket_type_id );
                    $order = new \Tickera\TC_Order( $ticket_instance->details->post_parent );

                    $order_is_paid = ( 'order_paid' == $order->details->post_status ) ? true : false;
                    $order_is_paid = apply_filters( 'tc_order_is_paid', $order_is_paid, $order->details->ID );

                    // Only those paid orders is eligible for checkin
                    if ( ! $order_is_paid ) {

                        if ( $echo ) {
                            wp_send_json( __( 'Ticket does not exist', 'tickera-event-ticketing-system' ) );

                        } else {
                            return 11;
                        }
                    }

                    $ticket_event_id = apply_filters( 'tc_ticket_checkin_ticket_type_event_id', $ticket_type->get_ticket_event( $ticket_type_id ), $ticket_type_id );

                } else {

                    if ( $echo ) {
                        wp_send_json( __( 'Ticket does not exist', 'tickera-event-ticketing-system' ) );

                    } else {
                        return 11;
                    }
                }

                // Only API key for the parent event can check-in this ticket
                $event_ids = (array) $this->get_api_event();
                $event_ids = self::maybe_format_event_ids_array( $event_ids );

                if ( ! in_array( $ticket_event_id, $event_ids ) && ! in_array( 'all', $event_ids ) ) {

                    if ( $echo ) {
                        wp_send_json( __( 'Insufficient permissions. This API key cannot check in this ticket.', 'tickera-event-ticketing-system' ) );

                    } else {

                        // Error code for insufficient permissions
                        return 403;
                    }
                    exit;
                }

                $check_ins = $ticket_instance->get_ticket_checkins();
                $allowed_checkins = TC_Checkin_API::get_number_of_allowed_checkins_for_ticket_instance( $ticket_id, $ticket_type );

                if ( $allowed_checkins > 0 ) {
                    $check_in_status = apply_filters( 'tc_checkin_status_name', true );
                    $check_in_status_bool = true;
                    do_action( 'tc_check_in_notification', $ticket_id, $api_key_id );

                } else {
                    $check_in_status = apply_filters( 'tc_checkin_status_name', false );
                    $check_in_status_bool = false;
                }

                if ( ! \Tickera\TC_Ticket::is_checkin_available( $ticket_type_id, $order, $ticket_id ) ) {
                    $check_in_status = apply_filters( 'tc_checkin_status_name', false );
                    $check_in_status_bool = false;
                }

                $new_checkins = array();

                if ( is_array( $check_ins ) ) {
                    foreach ( $check_ins as $check_in )
                        $new_checkins[] = $check_in;
                }

                $new_checkin = [
                    "date_checked" => isset( $_GET[ 'timestamp' ] ) ? intval( sanitize_text_field( $_GET[ 'timestamp' ] ) ) : time(),
                    "status" => $check_in_status ? apply_filters( 'tc_checkin_status_name', 'Pass' ) : apply_filters( 'tc_checkin_status_name', 'Fail' ),
                    "api_key_id" => (int) $api_key_id
                ];

                $new_checkins[] = apply_filters( 'tc_new_checkin_array', $new_checkin );
                do_action( 'tc_before_checkin_array_update', $new_checkins );
                $new_checkins = apply_filters( 'tc_all_attendee_checkin_records', $new_checkins );
                update_post_meta( (int) $ticket_id, "tc_checkins", $new_checkins );

                // When Check-out is activated, process validation.
                $_new_checkins = self::validate_ticket_checkout( $ticket_id );
                if ( 'Pass' == $new_checkin[ 'status' ] && $_new_checkins ) {
                    update_post_meta( $ticket_id, 'tc_checkins', $_new_checkins[ 'ins' ] );
                    update_post_meta( $ticket_id, 'tc_checkouts', $_new_checkins[ 'outs' ] );
                }

                do_action( 'tc_after_checkin_array_update' );

                $payment_date = apply_filters( 'tc_checkin_payment_date', tickera_format_date( apply_filters( 'tc_ticket_checkin_order_date', $order->details->tc_order_date, $order->details->ID ) ) );
                $payment_date = ( ! $payment_date ) ? 'N/A' : $payment_date;

                $name = apply_filters( 'tc_checkin_owner_name', $ticket_instance->details->first_name . ' ' . $ticket_instance->details->last_name );
                $name = ( ! trim( $name ) ) ? 'N/A' : $name;

                $address = apply_filters( 'tc_checkin_owner_address', $ticket_instance->details->address );
                $address = ( ! $address ) ? 'N/A' : $address;

                $city = apply_filters( 'tc_checkin_owner_city', $ticket_instance->details->city );
                $city = ( ! $city ) ? 'N/A' : $city;

                $state = apply_filters( 'tc_checkin_owner_state', $ticket_instance->details->state );
                $state = ( ! $state ) ? 'N/A' : $state;

                $country = apply_filters( 'tc_checkin_owner_country', $ticket_instance->details->country );
                $country = ( ! $country ) ? 'N/A' : $country;

                $data = [
                    'status' => $check_in_status_bool, // False
                    'previous_status' => '',
                    'pass' => true, // Api is valid
                    'name' => $name,
                    'payment_date' => $payment_date,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'checksum' => $this->ticket_code
                ];

                if ( isset( $_GET[ 'timestamp' ] ) ) {
                    $data[ 'timestamp' ] = intval( sanitize_text_field( $_GET[ 'timestamp' ] ) );
                }

                $buyer_full_name = isset( $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] ) ? ( $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] . ' ' . $order->details->tc_cart_info[ 'buyer_data' ][ 'last_name_post_meta' ] ) : '';
                $buyer_email = isset( $order->details->tc_cart_info[ 'buyer_data' ][ 'email_post_meta' ] ) ? $order->details->tc_cart_info[ 'buyer_data' ][ 'email_post_meta' ] : '';

                $data[ 'custom_fields' ] = [
                    array( apply_filters( 'tc_ticket_checkin_custom_field_title', 'Ticket Type' ), apply_filters( 'tc_checkout_owner_info_ticket_title', $ticket_type->details->post_title, $ticket_type->details->ID, array(), $ticket_instance->details->ID ) ),
                    array( apply_filters( 'tc_ticket_checkin_custom_field_title', 'Buyer Name' ), apply_filters( 'tc_ticket_checkin_buyer_full_name', $buyer_full_name, $order->details->ID ) ),
                    array( apply_filters( 'tc_ticket_checkin_custom_field_title', 'Buyer E-mail' ), apply_filters( 'tc_ticket_checkin_buyer_email', $buyer_email, $order->details->ID ) ),
                ];

                $data[ 'custom_fields' ] = apply_filters( 'tc_checkin_custom_fields', $data[ 'custom_fields' ], $ticket_instance->details->ID, $ticket_event_id, $order, $ticket_type );
                $data = apply_filters( 'tc_checkin_output_data', $data, $api_key_id );
                $data = tickera_sanitize_array( $data, true, true );

                if ( $echo === true || 'echo' == $echo ) {
                    wp_send_json( $data );
                }

                return $data;
            }
        }

        /**
         * Render Ticket Check-in App List of Attendees and their details
         *
         * @param bool $echo
         */
        function tickets_info( $echo = true ) {

            do_action( 'TC_Checkin_API_tickets_info', $echo, $this );

            $start = microtime( true );

            if ( $this->get_api_key_id() ) {

                global $wpdb;

                $event_ids = (array) $this->get_api_event();
                $event_ids = self::maybe_format_event_ids_array( $event_ids );

                $order_statuses = apply_filters( 'tc_paid_post_statuses', [ 'order_paid' ] );
                $prepare_order_statuses_placeholder = implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) );
                $offset = ( ( $this->page_number - 1 ) * $this->results_per_page );

                if ( in_array( 'all', $event_ids ) ) {
                    $prepare_arguments = array_merge( $order_statuses, [ $this->results_per_page ], [ $offset ] );
                    $query = $wpdb->prepare( "SELECT ID, post_parent as parent, post_status, ( SELECT post_status FROM {$wpdb->posts} wp2 WHERE wp2.ID = wp.post_parent ) as parent_status FROM {$wpdb->posts} wp, {$wpdb->postmeta} wp_pm WHERE post_type = 'tc_tickets_instances' AND wp.ID = wp_pm.post_id AND wp_pm.meta_key = 'event_id' AND post_status = 'publish' GROUP BY wp.ID HAVING (parent_status IN ($prepare_order_statuses_placeholder)) ORDER BY ID DESC LIMIT %d OFFSET %d", $prepare_arguments );

                } else {
                    $prepare_event_ids_placeholder = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
                    $prepare_arguments = array_merge( $event_ids, $order_statuses, [ $this->results_per_page ], [ $offset ] );
                    $query = $wpdb->prepare( "SELECT ID, post_parent as parent, post_status, ( SELECT post_status FROM {$wpdb->posts} wp2 WHERE wp2.ID = wp.post_parent ) as parent_status FROM {$wpdb->posts} wp, {$wpdb->postmeta} wp_pm WHERE post_type = 'tc_tickets_instances' AND wp.ID = wp_pm.post_id AND wp_pm.meta_key = 'event_id' AND wp_pm.meta_value IN ($prepare_event_ids_placeholder) AND post_status = 'publish' GROUP BY wp.ID HAVING (parent_status IN ($prepare_order_statuses_placeholder)) ORDER BY ID DESC LIMIT %d OFFSET %d", $prepare_arguments );
                }

                $results = $wpdb->get_results( $query, ARRAY_A );
                $results_count = 0;

                foreach ( $results as $result_id ) {

                    $result_id = $result_id[ 'ID' ];
                    $order_id = wp_get_post_parent_id( $result_id );

                    $ticket_code = get_post_meta( $result_id, 'ticket_code', true );
                    $ticket_first_name = get_post_meta( $result_id, 'first_name', true );
                    $ticket_last_name = get_post_meta( $result_id, 'last_name', true );
                    $attendee_email = get_post_meta( $result_id, 'owner_email', true );
                    $ticket_type_id = get_post_meta( $result_id, 'ticket_type_id', true );
                    $event_id = get_post_meta( $result_id, 'event_id', true );

                    $ticket_type = new \Tickera\TC_Ticket( $ticket_type_id );
                    $ticket_type_title = isset( $ticket_type->details->post_title ) ? $ticket_type->details->post_title : '';

                    $event = new \Tickera\TC_Event( $event_id );
                    $event_title = isset( $event->details->post_title ) ? $event->details->post_title : '';

                    $order = new \Tickera\TC_Order( $order_id );
                    $check_ins = get_post_meta( $result_id, 'tc_checkins', true );
                    $checkin_date = '';

                    if ( ! empty( $check_ins ) ) {
                        foreach ( $check_ins as $check_in )
                            $checkin_date = tickera_format_date( $check_in[ 'date_checked' ] );
                    }

                    $r[ 'date_checked' ] = $checkin_date;

                    if ( 'shop_order' == $order->details->post_type ) {
                        $format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );
                        $r[ 'payment_date' ] = get_the_date( $format, $result_id );

                    } else {
                        $r[ 'payment_date' ] = tickera_format_date( $order->details->tc_order_date );
                    }

                    $r[ 'transaction_id' ] = $ticket_code;
                    $r[ 'checksum' ] = $ticket_code;

                    $buyer_full_name = isset( $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] ) ? ( $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] . ' ' . $order->details->tc_cart_info[ 'buyer_data' ][ 'last_name_post_meta' ] ) : '';
                    $buyer_email = isset( $order->details->tc_cart_info[ 'buyer_data' ][ 'email_post_meta' ] ) ? $order->details->tc_cart_info[ 'buyer_data' ][ 'email_post_meta' ] : '';

                    if ( ! empty( $ticket_first_name ) && ! empty( $ticket_last_name ) ) {
                        $r[ 'buyer_first' ] = $ticket_first_name;
                        $r[ 'buyer_last' ] = $ticket_last_name;

                    } else {
                        $buyer_data = ( isset( $order->details->tc_cart_info ) && is_array( $order->details->tc_cart_info ) && isset( $order->details->tc_cart_info[ 'buyer_data' ] ) ) ? $order->details->tc_cart_info[ 'buyer_data' ] : [];
                        $r[ 'buyer_first' ] = apply_filters( 'tc_ticket_checkin_buyer_first_name', ( isset( $buyer_data[ 'first_name_post_meta' ] ) ? $buyer_data[ 'first_name_post_meta' ] : '' ), $order_id );
                        $r[ 'buyer_last' ] = apply_filters( 'tc_ticket_checkin_buyer_last_name', ( isset( $buyer_data[ 'last_name_post_meta' ] ) ? $buyer_data[ 'last_name_post_meta' ] : '' ), $order_id );
                    }

                    $r[ 'custom_fields' ] = array(
                        array( apply_filters( 'tc_ticket_checkin_custom_field_title', 'Ticket Type' ), apply_filters( 'tc_checkout_owner_info_ticket_title', $ticket_type_title, $ticket_type_id, array(), $result_id ) ),
                        array( apply_filters( 'tc_ticket_checkin_custom_field_title', 'Event' ), apply_filters( 'tc_checkout_owner_info_event_title', $event_title, $event_id, $result_id ) ),
                        array( apply_filters( 'tc_ticket_checkin_custom_field_title', 'Buyer Name' ), apply_filters( 'tc_ticket_checkin_buyer_full_name', $buyer_full_name, $order_id ) ),
                        array( apply_filters( 'tc_ticket_checkin_custom_field_title', 'Buyer E-mail' ), apply_filters( 'tc_ticket_checkin_buyer_email', $buyer_email, $order_id ) ),
                    );

                    if ( isset( $attendee_email ) && ! empty( $attendee_email ) ) {
                        $r[ 'custom_fields' ][] = array( apply_filters( 'tc_ticket_checkin_custom_field_title', 'Attendee E-mail' ), apply_filters( 'tc_ticket_checkin_attendee_email', $attendee_email, $result_id ) );
                    }

                    $r = apply_filters( 'tc_checkins_row', $r, $result_id, $event_ids, $order, $ticket_type );

                    $r[ 'custom_fields' ] = apply_filters( 'tc_checkin_custom_fields', $r[ 'custom_fields' ], $result_id, $event_ids, $order, $ticket_type );
                    $r[ 'custom_field_count' ] = count( $r[ 'custom_fields' ] );
                    $r[ 'allowed_checkins' ] = TC_Checkin_API::get_number_of_allowed_checkins_for_ticket_instance( $result_id, $ticket_type );
                    $r[ 'custom_ticket_info' ] = apply_filters( 'tc_checkin_custom_ticket_info', [], $r, $result_id );

                    $rows[] = [ 'data' => $r ];
                    $results_count++;
                }

                $additional[ 'results_count' ] = $results_count;
                $additional[ 'execution_time' ] = microtime( true ) - $start;
                $rows[] = [ 'additional' => $additional ];
                $rows = tickera_sanitize_array( $rows, true, true );

                wp_send_json( $rows );
            }
        }

        /**
         * Format Event IDs with backward compatibility
         *
         * @param $event_ids
         * @return int[]|mixed|WP_Post[]
         */
        function maybe_format_event_ids_array( $event_ids ) {

            /*
             * Identify dynamic field
             * Preserve previous format for backward compatibility
             */
            if ( is_array( reset( $event_ids ) ) ) {

                // Term/Category ID
                $term_id = array_keys( $event_ids )[ 0 ];

                // Update event ids format. Multidimentional Array Event IDs
                $md_event_ids = reset( $event_ids );

                if ( in_array( 'all', $md_event_ids ) ) {

                    // Filter event ids based on term/category
                    $event_args = [
                        'posts_per_page' => -1,
                        'post_type' => 'tc_events',
                        'fields' => 'ids',
                    ];

                    if ( 'all' != array_keys( $event_ids )[ 0 ] ) {
                        $event_args[ 'tax_query' ] = [
                            [ 'taxonomy' => 'event_category', 'terms' => $term_id ]
                        ];
                    }

                    $event_ids = get_posts( $event_args );

                } else {
                    $event_ids = $md_event_ids;
                }
            }

            return $event_ids;
        }
    }
}
