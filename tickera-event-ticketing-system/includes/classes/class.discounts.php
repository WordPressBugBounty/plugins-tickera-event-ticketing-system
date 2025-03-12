<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Discounts' ) ) {

    class TC_Discounts {

        var $form_title = '';
        var $discount_message = '';
        var $valid_admin_fields_type = array( 'text', 'textarea', 'image', 'function', 'select' );

        function __construct() {
            $this->form_title = __( 'Discount Codes', 'tickera-event-ticketing-system' );
            $this->valid_admin_fields_type = apply_filters( 'tc_valid_admin_fields_type', $this->valid_admin_fields_type );
        }

        function TC_Discounts() {
            $this->__construct();
        }

        public static function max_discount( $value, $total ) {
            if ( $value > $total ) {
                $value = $total;
            }
            return $value;
        }

        function unset_discount() {
            global $tc;
            $tc->session->set( 'tc_discount_code', '' );
            $tc->session->set( 'discount_value_total', 0 );
        }

        /**
         * Reverse Engineering of getting tax rate value from an existing order details
         * @param array $order_details
         * @return false|float
         */
        function get_tax_rate_by_order_details( $order_details ) {
            $tax_rate = round( $order_details[ 'tc_tax_total' ] / ( $order_details[ 'tc_subtotal' ] + $order_details[ 'tc_fees_total' ] - $order_details[ 'tc_discount_total' ] ), 2 );
            return $tax_rate;
        }

        /**
         * Reverse Engineering of getting tax term from an existing order details
         * @param array $order_details
         * @return bool true if inclusive
         */
        function get_tax_term_by_order_details( $order_details ) {
            $tax_inclusive_formula = round( $order_details[ 'tc_subtotal' ] - ( $order_details[ 'tc_subtotal' ] / ( $order_details[ 'tc_tax_rate' ] + 1 ) ), 2 ); // Obtain tax value from tax inclusive formula
            return ( $tax_inclusive_formula == $order_details[ 'tc_tax_total' ] ) ? true : false;
        }

        /**
         * Reverse Engineering of getting discount total value from an existing order details
         *
         * @param int $order_id
         * @return int
         */
        function get_discount_total_by_order( $order_id ) {

            $order = new \Tickera\TC_Order( $order_id );
            $discount_total = isset( $order->details->tc_payment_info[ 'discount' ] ) ? $order->details->tc_payment_info[ 'discount' ] : 0;
            $total = isset( $order->details->tc_payment_info[ 'total' ] ) ? $order->details->tc_payment_info[ 'total' ] : 0;

            if ( ! $discount_total && $total ) {

                $fees_total = isset( $order->details->tc_payment_info[ 'fees_total' ] ) ? $order->details->tc_payment_info[ 'fees_total' ] : 0;
                $tax_total = isset( $order->details->tc_payment_info[ 'tax_total' ] ) ? $order->details->tc_payment_info[ 'tax_total' ] : 0;
                $subtotal = isset( $order->details->tc_payment_info[ 'subtotal' ] ) ? $order->details->tc_payment_info[ 'subtotal' ] : 0;

                // Get discount value based on current payment information
                $discount_total = $subtotal - ( $total - $tax_total - $fees_total );

                // Tax rate based on current payment information
                $tax_rate = $this->get_tax_rate_by_order_details( array(
                    'tc_subtotal' => $subtotal,
                    'tc_discount_total' => $discount_total,
                    'tc_fees_total' => $fees_total,
                    'tc_tax_total' => $tax_total
                ) );

                // Recalculate formula to obtain discount_total when tax term is inclusive
                $tax_inclusive = $this->get_tax_term_by_order_details( array(
                    'tc_subtotal' => $subtotal,
                    'tc_tax_rate' => $tax_rate,
                    'tc_tax_total' => $tax_total
                ) );
                $discount_total = ( $tax_inclusive ) ? $subtotal - ( $total - $fees_total ) : $discount_total;
            }

            $order_discount_code = get_post_meta( $order_id, 'tc_discount_code', true );

            if ( empty( $order_discount_code ) ) {
                return 0;

            } else {
                return $discount_total;
            }
        }

        /**
         * Retrieve the number of usage of a discount code
         * @param $discount_code
         * @return int|string
         */
        public static function discount_used_times( $discount_code, $user_id = null ) {

            $discount_per_item_used_times = 0;
            $discount_per_order_used_times = 0;

            // Initialize Variables
            $discount = ( new \Tickera\TC_Discount() )->get_discount_by_code( $discount_code );
            $discount_scope = isset( $discount->details->discount_scope ) ? $discount->details->discount_scope : 'per_item';
            $discount_availability = array_filter( explode( ',', $discount->details->discount_availability ) );

            // Retrieve the orders that are associated with the discount code
            $orders = get_posts( [
                'posts_per_page' => -1,
                'author' => 0,
                'meta_key' => 'tc_discount_code',
                'meta_value' => $discount_code,
                'post_type' => 'tc_orders',
                'post_status' => 'any'
            ] );

            foreach ( $orders as $order ) {

                $applied = false;

                $ticket_instances = get_posts( [
                    'posts_per_page' => -1,
                    'post_parent' => $order->ID,
                    'post_type' => 'tc_tickets_instances',
                ] );

                foreach ( $ticket_instances as $ticket_instance ) {

                    $ticket = new \Tickera\TC_Ticket_Instance( $ticket_instance->ID );
                    $ticket_type_id = $ticket->details->ticket_type_id;
                    $discount_amount = $ticket->details->ticket_discount;

                    if ( in_array( $ticket_type_id, $discount_availability ) || ! $discount_availability ) {
                        $discount_per_item_used_times = $discount_amount ? ( $discount_per_item_used_times + 1 ) : $discount_per_item_used_times;
                        $applied = true;
                    }
                }

                if ( $applied ) {
                    $discount_per_order_used_times++;
                }
            }

            return ( 'per_item' == $discount_scope ) ? $discount_per_item_used_times : $discount_per_order_used_times;
        }

        /**
         * Calculate discount values per ticket
         *
         * @param $cart_contents
         * @param $discount_details
         * @param array $tc_ticket_discount Ticket Types with applied discounts
         * @return array|null
         */
        function calculate_tickets_discount( $cart_contents, $discount_details, $tc_ticket_discount = [] ) {

            if ( ! $cart_contents || ! $discount_details ) {
                return null;
            }

            // Check if Discount Code is active/published
            $is_published = ( 'publish' == $discount_details->details->post_status ) ? true : false;

            // Check for usage limit
            $usage = $this->discount_used_times( $discount_details->details->post_title );
            $usage_limit = $discount_details->details->usage_limit;
            $available_usage = (int) $usage_limit - (int) $usage;

            // Check Discount Code for expiration
            $current_date = current_time( "Y-m-d H:i:s" );
            $discount_expiration_date = $discount_details->details->expiry_date;

            // Validate Discount Code
            if ( $discount_details->id && $is_published && ( $discount_expiration_date >= $current_date ) ) {

                // Check for Discount Ticket Type Filtering
                $applicable_tickets = $discount_details->details->discount_availability;
                $applicable_ticket_type = explode( ',', $applicable_tickets );
                $applicable_ticket_type = array_filter( $applicable_ticket_type );
                $applicable_ticket_type = array_values( $applicable_ticket_type );

                // Identify discount type for values segregation
                $discount_type = $discount_details->details->discount_type;

                // Overall Subtotal
                $overall_cart_subtotal = 0;
                if ( 3 == $discount_type ) { // Applicable only for Fixed Amount (per order)
                    foreach ( $cart_contents as $ticket_id => $ordered_count ) {
                        $overall_cart_subtotal = $overall_cart_subtotal + ( tickera_get_ticket_price( $ticket_id ) * $ordered_count );
                    }
                }

                $discount_values = [];
                foreach ( $cart_contents as $ticket_id => $ordered_count ) {
                    $ticket_price = tickera_get_ticket_price( $ticket_id );

                    for ( $x = 0; $x < $ordered_count; $x++ ) {

                        // Check ticket if discounted
                        $discounted = ( $tc_ticket_discount ) ? $tc_ticket_discount[ $ticket_id ][ $x ] : false;

                        if ( ! $usage_limit || $available_usage > 0 || $discounted ) {
                            switch ( $discount_type ) {
                                case '1': // Fixed Amount (per item) | Maximum discount value is equal to subtotal
                                    if ( in_array( $ticket_id, $applicable_ticket_type ) || empty( $applicable_tickets ) ) {
                                        $discount_value = ( $discount_details->details->discount_value > $ticket_price ) ? $ticket_price : $discount_details->details->discount_value;
                                    } else {
                                        $discount_value = 0;
                                    }
                                    $available_usage--;
                                    break;

                                case '2': // Percentage % | Maximum discount value is equal to subtotal
                                    if ( in_array( $ticket_id, $applicable_ticket_type ) || empty( $applicable_tickets ) ) {
                                        $discount_value = ( $discount_details->details->discount_value < 101 ) ? ( ( $ticket_price / 100 ) * $discount_details->details->discount_value ) : $ticket_price;
                                    } else {
                                        $discount_value = 0;
                                    }
                                    $available_usage--;
                                    break;

                                case '3': // Fixed Amount (per order) | Maximum discount value is equal to subtotal
                                    $discount_ratio = $ticket_price / $overall_cart_subtotal;
                                    $discount_value = ( $discount_details->details->discount_value > $overall_cart_subtotal ) ? $ticket_price : ( $discount_details->details->discount_value * $discount_ratio );
                                    break;

                                default:
                                    $discount_value = 0;
                            }
                        } else {
                            $discount_value = 0;
                        }

                        $discount_values[ $ticket_id ][] = $discount_value;

                    }
                }

                return $discount_values;

            } else {
                return null;
            }
        }

        /**
         * Calculate cart discount total on Checkout page
         *
         * @param bool $total
         * @param string $discount_code
         */
        function discounted_cart_total( $total = false, $discount_code = '' ) {

            global $tc, $discount, $discount_value_total, $new_total;
            $cart_contents = $tc->get_cart_cookie();

            $cart_subtotal = 0;
            $discount_value = 0;
            $discount_error_message = '';
            $current_date = current_time( "Y-m-d H:i:s" );

            if ( empty( $discount ) ) {
                $discount = new \Tickera\TC_Discounts();
            }

            foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                $cart_subtotal = $cart_subtotal + ( tickera_get_ticket_price( $ticket_type ) * $ordered_count );
            }

            $tc->session->set( 'tc_cart_subtotal', $cart_subtotal );

            if ( ! $discount_code ) {
                $discount_code = isset( $_POST[ 'coupon_code' ] ) ? sanitize_text_field( $_POST[ 'coupon_code' ] ) : '';
            }

            if ( $discount_code ) {

                $discount_object = new \Tickera\TC_Discount();
                $discount_object = $discount_object->get_discount_by_code( $discount_code );

                if ( $discount_object ) {

                    // Initialize User Variables
                    $current_user = wp_get_current_user();
                    $current_user_roles = $current_user->roles;

                    // Initialize Discount Variables
                    $discount_object = new \Tickera\TC_Discount( $discount_object->ID );
                    $discount_details = $discount_object->details;

                    $discount_availability = isset( $discount_details->discount_availability ) ? $discount_details->discount_availability : '';
                    $discount_availability = ( $discount_availability ) ? explode( ',', $discount_details->discount_availability ) : [];
                    $discount_availability = array_filter( $discount_availability );

                    $discount_on_user_roles = isset( $discount_details->discount_on_user_roles ) ? $discount_details->discount_on_user_roles : '';
                    $discount_on_user_roles =  ( $discount_on_user_roles ) ? explode( ',', $discount_on_user_roles ) : [];
                    $discount_on_user_roles = array_filter( $discount_on_user_roles );

                    // Calculate the number of times that a discount code was used
                    $usage_limit = ( '' != $discount_details->usage_limit ) ? $discount_details->usage_limit : 999999999;
                    $number_of_discount_uses = self::discount_used_times( $discount_code );
                    $discount_codes_available = (int) $usage_limit - (int) $number_of_discount_uses;

                    if ( $discount_object->details->post_status != 'publish' ) {

                        // Check if discount code is published
                        $discount_error_message = __( 'Discount code cannot be found', 'tickera-event-ticketing-system' );

                    } elseif ( $current_date >= $discount_object->details->expiry_date ) {

                        // Check for discount code expiration date
                        $discount_error_message = __( 'Discount code expired', 'tickera-event-ticketing-system' );

                    } elseif ( $discount_codes_available <= 0 ) {

                        // Check if the discount code reach the maximum used limit
                        $discount_error_message = __( 'Discount code invalid or expired', 'tickera-event-ticketing-system' );

                    } elseif ( $discount_on_user_roles && empty( array_intersect( $current_user_roles, $discount_on_user_roles ) ) ) {

                        // Check if the user has a valid user role
                        $discount_error_message = __( 'Discount code is not available', 'tickera-event-ticketing-system' );

                    } elseif ( array_filter( $discount_availability ) ) {

                        // If available to some tickets
                        $discount_available_per_ticket = array_intersect( array_keys( $tc->get_cart_cookie() ), array_filter( $discount_availability ) );
                    }

                    // If invalid: Unset discount
                    if ( $discount_error_message ) {
                        $this->unset_discount();
                        $discount->discount_message = $discount_error_message;

                    } else {

                        $tc_discount_code_post_validation = apply_filters( 'tc_discount_code_post_validation', array( 'validated' => true, 'message' => '' ), $discount_code );

                        // Apply discount if Post validation succeeded. Otherwise, display error message
                        if ( $tc_discount_code_post_validation[ 'validated' ] ) {

                            $cart_contents = ( isset( $discount_available_per_ticket ) ) ? $discount_availability : array_keys( $cart_contents );

                            foreach ( $cart_contents as $ticket_type_id ) {

                                $cart_contents = $tc->get_cart_cookie();

                                if ( isset( $cart_contents[ $ticket_type_id ] ) ) {

                                    $ordered_count = $cart_contents[ $ticket_type_id ];
                                    $ticket_price = tickera_get_ticket_price( $ticket_type_id );

                                    // Apply only the remaining number of discounts available
                                    if ( $ordered_count >= $discount_codes_available ) {
                                        $max_discount = $discount_codes_available;

                                        /* } elseif ( $ordered_count >= $discount_codes_available_per_user ) {
                                            $max_discount = $discount_codes_available_per_user; */

                                    } else {
                                        $max_discount = $ordered_count;
                                    }

                                    if ( 1 == $discount_details->discount_type && 'per_item' == $discount_details->discount_scope ) {

                                        // Fixed amount per item
                                        if ( $max_discount > 0 ) {
                                            $discount_value_per_each = ( $discount_object->details->discount_value > $ticket_price ) ? $ticket_price : $discount_object->details->discount_value;
                                            for ( $i = 1; $i <= (int) $max_discount; $i++ ) {
                                                $discount_value = $discount_value + $discount_value_per_each;
                                                $number_of_discount_uses++;
                                                $discount_codes_available = $usage_limit - $number_of_discount_uses;
                                            }
                                        }

                                    } elseif ( 1 == $discount_details->discount_type && 'per_order' == $discount_object->details->discount_scope ) {

                                        // Fixed amount per order
                                        if ( $max_discount > 0 ) {
                                            $discount_value = $discount_object->details->discount_value;
                                        }

                                    } elseif ( 2 == $discount_details->discount_type && 'per_item' == $discount_object->details->discount_scope ) {

                                        // Percentage per item
                                        if ( $max_discount > 0 ) {
                                            $discount_rounded_value = $ticket_price * ( $discount_object->details->discount_value / 100 );
                                            $discount_value_per_each = ( $discount_rounded_value > $ticket_price ) ? $ticket_price : $discount_rounded_value;
                                            for ( $i = 1; $i <= (int) $max_discount; $i++ ) {
                                                $discount_value = $discount_value + $discount_value_per_each;
                                                $number_of_discount_uses++;
                                                $discount_codes_available = $usage_limit - $number_of_discount_uses;
                                            }
                                        }

                                    } elseif ( 2 == $discount_details->discount_type && 'per_order' == $discount_object->details->discount_scope ) {

                                        // Percentage per order
                                        if ( $max_discount > 0 ) {
                                            $discount_rounded_value = $cart_subtotal * ( $discount_object->details->discount_value / 100 );
                                            $discount_value =  ( $discount_rounded_value > $cart_subtotal ) ? $cart_subtotal : $discount_rounded_value;
                                        }

                                    } else {

                                        // Fallback discount value
                                        $discount_value = 0;
                                    }
                                }
                            }

                        } else {

                            // Display error message
                            $this->unset_discount();
                            $discount->discount_message = $tc_discount_code_post_validation[ 'message' ];
                        }
                    }

                } else {

                    // Discount code cannot be found.
                    $this->unset_discount();
                    $discount->discount_message = __( 'Discount code cannot be found', 'tickera-event-ticketing-system' );
                }

                if ( $discount_value ) {

                    if ( count( $cart_contents ) > 1
                        && 'per_item' == $discount_details->discount_scope
                        && isset( $discount_available_per_ticket ) && ( count( $discount_available_per_ticket ) != count( $cart_contents ) ) ) {

                        // Discount code is applied for specific ticket types
                        $discount->discount_message = __( 'Discount code is partially applied.', 'tickera-event-ticketing-system' );

                    } else {
                        $discount->discount_message = __( 'Discount code applied.', 'tickera-event-ticketing-system' );
                    }

                } else {
                    $discount->discount_message = __( 'Discount code invalid or expired.', 'tickera-event-ticketing-system' );
                }
            }

            $discount_value_total = round( $discount_value, 2 );

            add_filter( 'tc_cart_discount', function() {
                global $tc, $discount_value_total;
                $session_cart_subtotal = $tc->session->get( 'tc_cart_subtotal' );
                $total = $session_cart_subtotal;
                $max_discount = TC_Discounts::max_discount( tickera_minimum_total( $discount_value_total ), $total );
                $tc->session->set( 'discount_value_total', $max_discount );
                return TC_Discounts::max_discount( $discount_value_total, $total );
            }, 10, 0 );

            add_filter( 'tc_cart_subtotal', function() {
                global $tc;
                $session_cart_subtotal = $tc->session->get( 'tc_cart_subtotal' );
                $cart_subtotal = (float) $session_cart_subtotal;
                return tickera_minimum_total( $cart_subtotal );
            } );

            $session_cart_subtotal = $tc->session->get( 'tc_cart_subtotal' );
            $new_total = ( !is_null( $session_cart_subtotal ) ? (float) $session_cart_subtotal : 0 ) - $discount_value;

            add_filter( 'tc_cart_total', function() {
                global $tc, $new_total, $subtotal_value;
                $total = tickera_minimum_total( $new_total );
                $tc->session->set( 'tc_cart_total', $total );
                $subtotal_value = $total;
                return tickera_minimum_total( $new_total );
            } );

            $tc->session->set( 'tc_discount_code', $discount_code );
            $minimum_discounted_total = tickera_minimum_total( apply_filters( 'tc_discounted_total', $new_total ) );
            $tc->session->set( 'discounted_total', $minimum_discounted_total );

            return [
                'success' => ! $discount_error_message ? true : false,
                'message' => $discount->discount_message
            ];
        }

        public static function discount_code_message( $message ) {
            global $discount;
            $message = $discount->discount_message;
            return $message;
        }

        function get_discount_fields( $bulk = false ) {

            $default_fields = array(
                array(
                    'field_name' => 'discount_type',
                    'field_title' => __( 'Discount Type', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_discount_types',
                    'field_description' => '',
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'field_name' => 'discount_scope',
                    'field_title' => __( 'Discount Scope', 'tickera-event-ticketing-system' ),
                    'field_type' => 'select',
                    'options' => [
                        'per_item' => __( 'Per Item', 'tickera-event-ticketing-system' ),
                        'per_order' => __( 'Per Order', 'tickera-event-ticketing-system' )
                    ],
                    'field_description' => '',
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'field_name' => 'discount_value',
                    'field_title' => __( 'Discount Value', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => __( 'For example: 9.99', 'tickera-event-ticketing-system' ),
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'number' => true,
                    'required' => true
                ),
                array(
                    'field_name' => 'discount_on_user_roles',
                    'field_title' => __( 'User Roles', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_user_roles',
                    'field_description' => 'Discount availability based on user role(s)',
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'field_name' => 'discount_availability',
                    'field_title' => __( 'Discount Available for', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ticket_types',
                    'field_description' => 'Select ticket type(s)',
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'conditional' => array(
                        'field_name' => 'discount_scope',
                        'field_type' => 'select',
                        'value' => 'per_item',
                        'action' => 'show'
                    ),
                ),

                /*
                array(
                    'field_name' => 'discount_per_user',
                    'field_title' => __('Usage limit per registered user', 'tickera-event-ticketing-system'),
                    'placeholder' => __('Unlimited', 'tickera-event-ticketing-system'),
                    'field_type' => 'text',
                    'field_description' => __('The number of usage per registered user, e.g. 10', 'tickera-event-ticketing-system'),
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'number' => true
                ),
                array(
                    'field_name' => 'discount_on_returning_customer',
                    'field_title' => __('Allow only to returning customers', 'tickera-event-ticketing-system'),
                    'field_type' => 'function',
                    'function' => 'tickera_extended_radio_button',
                    'values' => array('yes', 'no'),
                    'field_description' => 'Only allow discount code to those user with order history.',
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                */

                array(
                    'field_name' => 'usage_limit',
                    'field_title' => __( 'Usage Limit', 'tickera-event-ticketing-system' ),
                    'placeholder' => __( 'Unlimited', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => __( '(optional) How many times this discount code can be used before it is void, e.g. 100', 'tickera-event-ticketing-system' ),
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'number' => true
                ),
                array(
                    'field_name' => 'used_count',
                    'field_title' => __( 'Used Count', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'form_visibility' => false,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'field_name' => 'expiry_date',
                    'field_title' => __( 'Expiration Date', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => __( 'The date this discount will expire (24 hour format)', 'tickera-event-ticketing-system' ),
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                )
            );

            if ( $bulk ) {
                $first_field = array(
                    'field_name' => 'post_titles',
                    'field_title' => __( 'Discount Code', 'tickera-event-ticketing-system' ),
                    'field_type' => 'textarea',
                    'field_description' => __( 'Discount Code, e.g. ABC123. <strong>One discount code per line</strong>.', 'tickera-event-ticketing-system' ),
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_title',
                );
            } else {
                $first_field = array(
                    'field_name' => 'post_title',
                    'field_title' => __( 'Discount Code', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => __( 'Discount Code, e.g. ABC123', 'tickera-event-ticketing-system' ),
                    'form_visibility' => true,
                    'table_visibility' => true,
                    'post_field_type' => 'post_title',
                    'required' => true
                );
            }

            array_unshift( $default_fields, $first_field );

            return apply_filters( 'tc_discount_fields', $default_fields );
        }

        function get_columns() {

            $fields = $this->get_discount_fields();
            $results = tickera_search_array( $fields, 'table_visibility', true );

            $columns = array();

            $columns[ 'ID' ] = __( 'ID', 'tickera-event-ticketing-system' );

            foreach ( $results as $result ) {
                $columns[ $result[ 'field_name' ] ] = $result[ 'field_title' ];
            }

            $columns[ 'edit' ] = __( 'Edit', 'tickera-event-ticketing-system' );
            $columns[ 'delete' ] = __( 'Delete', 'tickera-event-ticketing-system' );

            return $columns;
        }

        function check_field_property( $field_name, $property ) {
            $fields = $this->get_discount_fields();
            $result = tickera_search_array( $fields, 'field_name', $field_name );
            return isset( $result[ 0 ][ 'post_field_type' ] ) ? $result[ 0 ][ 'post_field_type' ] : '';
        }

        function is_valid_discount_field_type( $field_type ) {
            if ( in_array( $field_type, $this->valid_admin_fields_type ) ) {
                return true;
            } else {
                return false;
            }
        }

        function add_new_discount() {
            global $user_id;

            if ( check_admin_referer( 'tickera_save_discount' ) && isset( $_POST[ 'add_new_discount' ] ) ) {

                $metas = [];

                $post_data = tickera_sanitize_array( $_POST, false, true );
                $post_data = $post_data ? $post_data : [];

                foreach ( $post_data as $field_name => $field_value ) {

                    if ( preg_match( '/_post_title/', $field_name ) ) {
                        $title = sanitize_text_field( $field_value );

                    } elseif ( preg_match( '/_post_excerpt/', $field_name ) ) {
                        $excerpt = wp_filter_post_kses( $field_value );

                    } elseif ( preg_match( '/_post_content/', $field_name ) ) {
                        $content = wp_filter_post_kses( $field_value );

                    } elseif ( preg_match( '/_post_meta/', $field_name ) ) {

                        if ( is_array( $field_value ) ) {
                            $field_value = implode( ',', array_filter( $field_value ) );
                        }

                        $metas[ sanitize_key( str_replace( '_post_meta', '', $field_name ) ) ] = sanitize_text_field( $field_value );
                    }

                    do_action( 'tc_after_discount_post_field_type_check' );
                }

                $metas = apply_filters( 'discount_code_metas', $metas );

                $arg = array(
                    'post_author'   => (int) $user_id,
                    'post_excerpt'  => ( isset( $excerpt ) ? $excerpt : '' ),
                    'post_content'  => ( isset( $content ) ? $content : '' ),
                    'post_status'   => 'publish',
                    'post_title'    => ( isset( $title ) ? $title : '' ),
                    'post_type'     => 'tc_discounts',
                );

                if ( isset( $_POST[ 'post_id' ] ) ) {
                    $arg[ 'ID' ] = (int) $_POST[ 'post_id' ]; //for edit
                }

                $post_id = @wp_insert_post( tickera_sanitize_array( $arg, true ), true );

                // Update post meta
                if ( $post_id !== 0 ) {
                    foreach ( $metas as $key => $value ) {
                        update_post_meta( (int) $post_id, $key, tickera_sanitize_array( $value, false, true ) );
                    }
                }

                return $post_id;
            }
        }
    }
}
