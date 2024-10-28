<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Orders' ) ) {

    class TC_Orders {

        var $form_title = '';
        var $valid_admin_fields_type = array( 'ID', 'text', 'textarea', 'image', 'function', 'separator' );

        function __construct() {
            $this->form_title = __( 'Orders', 'tickera-event-ticketing-system' );
            $this->valid_admin_fields_type = apply_filters( 'tc_valid_admin_fields_type', $this->valid_admin_fields_type );
        }

        function TC_Orders() {
            $this->__construct();
        }

        public static function get_tickets_ids( $order_id, $post_status = '', $order = 'ASC' ) {

            if ( is_array( $post_status ) ) {
                $post_status = array_filter( $post_status );
                $post_status = ( $post_status ) ? $post_status : [ 'publish' ];

            } else {
                $post_status = ( $post_status ) ? [ $post_status ] : [ 'publish' ];
            }

            $args = [
                'post_type' => 'tc_tickets_instances',
                'post_status' => $post_status,
                'post_parent' => $order_id,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => $order,
                'posts_per_page' => -1,
            ];

            return get_posts( $args );
        }

        public static function get_order_fields() {

            $default_fields = array(
                array(
                    'field_name' => 'ID',
                    'field_title' => __( 'Order ID', 'tickera-event-ticketing-system' ),
                    'field_type' => 'ID',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_title'
                ),
                array(
                    'field_name' => 'ID',
                    'field_title' => __( 'Order Link', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_front_link',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'post_title'
                ),
                array(
                    'field_name' => 'order_status',
                    'field_title' => __( 'Status', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_status_select',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_status'
                ),
                array(
                    'id' => 'order_date',
                    'field_name' => 'tc_order_date',
                    'field_title' => __( 'Order Date', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_date',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'customer',
                    'field_name' => 'tc_cart_info',
                    'field_title' => __( 'Customer', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_customer',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'customer_email',
                    'field_name' => 'tc_cart_info',
                    'field_title' => __( 'Customer E-mail', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_customer_email',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'parent_event',
                    'field_name' => 'tc_cart_contents',
                    'field_title' => __( 'Ticket(s)', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_event',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'gateway_admin_name',
                    'field_name' => 'tc_cart_info',
                    'field_title' => __( 'Gateway', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_gateway',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'discount',
                    'field_name' => 'tc_cart_info',
                    'field_title' => __( 'Discount', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_discount_info',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'subtotal',
                    'field_name' => 'tc_payment_info',
                    'field_title' => __( 'Subtotal', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_subtotal',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'fees_total',
                    'field_name' => 'tc_payment_info',
                    'field_title' => __( 'Fees', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_fees_total',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'tax_total',
                    'field_name' => 'tc_payment_info',
                    'field_title' => __( 'Tax', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_tax_total',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'total',
                    'field_name' => 'tc_cart_info',
                    'field_title' => __( 'Total', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_total',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'resend_order_confirmation_email',
                    'field_name' => 'resend_order_confirmation_email',
                    'field_title' => '',
                    'field_type' => 'function',
                    'function' => 'tickera_resend_order_confirmation_email',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'function'
                ),
                array(
                    'id' => 'order_ipn_messages',
                    'field_name' => 'order_ipn_messages',
                    'field_title' => '',
                    'field_type' => 'function',
                    'function' => 'tickera_order_ipn_messages',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'function'
                ),
            );

            return apply_filters( 'tc_order_fields', $default_fields );
        }


        /**
         * Initialize Ticket(s) Table Header.
         * This will populate Ticket(s) table headers under order page
         * @return mixed|void
         */

        function get_owner_info_fields() {

            $default_fields = array(
                array(
                    'id' => 'ID',
                    'field_name' => 'ID',
                    'field_title' => __( 'ID', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'ID'
                ),
                array(
                    'id' => 'parent_event',
                    'field_name' => 'event_id',
                    'field_title' => __( 'Event Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ticket_instance_event',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'ticket_type',
                    'field_name' => 'ticket_type_id',
                    'field_title' => __( 'Ticket Type', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ticket_instance_type',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'first_name',
                    'field_name' => 'first_name',
                    'field_title' => __( 'First Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'last_name',
                    'field_name' => 'last_name',
                    'field_title' => __( 'Last Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'owner_email',
                    'field_name' => 'owner_email',
                    'field_title' => __( 'Attendee E-Mail', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'ticket_code',
                    'field_name' => 'ticket_code',
                    'field_title' => __( 'Ticket Code', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
            );

            return apply_filters( 'tc_owner_info_orders_table_fields', $default_fields );
        }

        function get_owner_info_fields_front() {

            $tc_general_settings = get_option( 'tickera_general_setting', false );

            if ( ! isset( $tc_general_settings[ 'show_owner_fields' ] ) || ( isset( $tc_general_settings[ 'show_owner_fields' ] ) && $tc_general_settings[ 'show_owner_fields' ] == 'yes' ) ) {
                $show_owner_fields = apply_filters( 'tc_get_owner_info_fields_front_show', true );
            } else {
                $show_owner_fields = apply_filters( 'tc_get_owner_info_fields_front_show', false );
            }

            if ( ! isset( $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] ) || ( isset( $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] ) && $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] == 'yes' ) ) {
                $show_attendee_first_and_last_name_fields = apply_filters( 'tc_show_attendee_first_and_last_name_fields', true );
            } else {
                $show_attendee_first_and_last_name_fields = apply_filters( 'tc_show_attendee_first_and_last_name_fields', false );
            }

            $default_fields = array(
                array(
                    'id' => 'parent_event',
                    'field_name' => 'ticket_type_id',
                    'field_title' => __( 'Event Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => ( apply_filters( 'tc_get_ticket_instance_event_front', true ) ? 'tickera_get_ticket_instance_event_front' : 'tickera_get_ticket_instance_event' ),
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'ticket_type',
                    'field_name' => 'ticket_type_id',
                    'field_title' => __( 'Ticket Type', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ticket_instance_type',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'first_name',
                    'field_name' => 'first_name',
                    'field_title' => __( 'First Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'last_name',
                    'field_name' => 'last_name',
                    'field_title' => __( 'Last Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'ticket_code',
                    'field_name' => 'ticket_code',
                    'field_title' => __( 'Ticket', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ticket_download_link',
                    'field_description' => '',
                    'post_field_type' => 'post_meta'
                ),
            );

            if ( ! $show_owner_fields || ! $show_attendee_first_and_last_name_fields ) {
                $i = 0;
                foreach ( $default_fields as $default_field ) {
                    if ( $default_field[ 'id' ] == 'first_name' || $default_field[ 'id' ] == 'last_name' ) {
                        unset( $default_fields[ $i ] );
                    }
                    $i++;
                }
            }

            return apply_filters( 'tc_owner_info_orders_table_fields_front', $default_fields );
        }

        function get_columns() {
            $fields = \Tickera\TC_Orders::get_order_fields();
            $results = tickera_search_array( $fields, 'table_visibility', true );

            $columns = array();

            foreach ( $results as $result ) {
                if ( isset( $result[ 'id' ] ) ) {
                    $columns[][ 'id' ] = $result[ 'id' ];
                    $index = ( count( $columns ) - 1 );
                    $columns[ $index ][ 'field_name' ] = $result[ 'field_name' ];
                    $columns[ $index ][ 'field_title' ] = $result[ 'field_title' ];
                } else {
                    $columns[][ 'id' ] = $result[ 'field_name' ];
                    $index = ( count( $columns ) - 1 );
                    $columns[ $index ][ 'field_name' ] = $result[ 'field_name' ];
                    $columns[ $index ][ 'field_title' ] = $result[ 'field_title' ];
                }
            }

            $columns[][ 'id' ] = 'details';
            $index = ( count( $columns ) - 1 );
            $columns[ $index ][ 'field_name' ] = 'details';
            $columns[ $index ][ 'field_title' ] = __( 'Details', 'tickera-event-ticketing-system' );

            $columns[][ 'id' ] = 'delete';
            $index = ( count( $columns ) - 1 );
            $columns[ $index ][ 'field_name' ] = 'delete';
            $columns[ $index ][ 'field_title' ] = __( 'Delete', 'tickera-event-ticketing-system' );

            return $columns;
        }

        public static function get_user_orders( $user_id = false ) {
            $user_id = $user_id ? $user_id : get_current_user_id();
            $args = array(
                'author__in' => ( array( $user_id ) ),
                'posts_per_page' => -1,
                'orderby' => 'post_date',
                'order' => 'DESC',
                'post_type' => 'tc_orders',
                'post_status' => array_keys( tickera_get_order_statuses() )
            );
            return get_posts( $args );
        }

        function get_field_id( $field_name, $property ) {
            $fields = $this->get_order_fields();
            $result = tickera_search_array( $fields, 'field_name', $field_name );
            return $result[ 0 ][ 'id' ];
        }

        public static function check_field_property( $field_name, $property ) {
            $fields = \Tickera\TC_Orders::get_order_fields();
            $result = tickera_search_array( $fields, 'field_name', $field_name );
            return $result[ 0 ][ 'post_field_type' ];
        }

        function is_valid_order_field_type( $field_type ) {
            if ( in_array( $field_type, array( 'ID', 'text', 'textarea', 'image', 'function', 'separator' ) ) ) {
                return true;
            } else {
                return false;
            }
        }

        function add_new_order() {

            global $user_id;

            if ( isset( $_POST[ 'add_new_order' ] ) ) {

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
                        $metas[ sanitize_key( str_replace( '_post_meta', '', $field_name ) ) ] = sanitize_text_field( $field_value );
                    }

                    do_action( 'tc_after_order_post_field_type_check' );
                }

                $metas = apply_filters( 'tc_orders_metas', $metas );

                $arg = array(
                    'post_author'   => (int) $user_id,
                    'post_excerpt'  => ( isset( $excerpt ) ? $excerpt : '' ),
                    'post_content'  => ( isset( $content ) ? $content : '' ),
                    'post_status'   => 'publish',
                    'post_title'    => ( isset( $title ) ? $title : '' ),
                    'post_type'     => 'tc_orders',
                );

                if ( isset( $_POST[ 'post_id' ] ) ) {
                    $arg[ 'ID' ] = (int) $_POST[ 'post_id' ];
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
