<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Tickets' ) ) {

    class TC_Tickets {

        var $form_title = '';
        var $valid_admin_fields_type = array( 'text', 'textarea', 'textarea_editor', 'image', 'function' );

        function __construct() {
            $this->form_title = __( 'Tickets', 'tickera-event-ticketing-system' );
            $this->valid_admin_fields_type = apply_filters( 'tc_valid_admin_fields_type', $this->valid_admin_fields_type );
        }

        function TC_Tickets() {
            $this->__construct();
        }

        public static function get_ticket_fields() {

            $tc_general_settings = get_option( 'tickera_general_setting', false );

            $default_fields = array(
                array(
                    'field_name' => 'ID',
                    'field_title' => __( 'ID', 'tickera-event-ticketing-system' ),
                    'field_type' => 'ID',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'ID'
                ),
                array(
                    'field_name' => 'event_name',
                    'field_title' => __( 'Event', 'tickera-event-ticketing-system' ),
                    'placeholder' => '',
                    'field_type' => 'function',
                    'function' => 'tickera_get_events',
                    'tooltip' => sprintf(
                        /* translators: %s: A link to Tickera > Events */
                        __( 'Select an event which you want to assign this ticket type to. You can create new events <a href="%s" target="_blank">here</a>.', 'tickera-event-ticketing-system' ),
                        esc_url( admin_url( 'edit.php?post_type=tc_events' ) )
                    ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'metabox_context' => 'side'
                ),
                array(
                    'field_name' => 'ticket_type_name',
                    'field_title' => __( 'Ticket type', 'tickera-event-ticketing-system' ),
                    'placeholder' => '',
                    'field_type' => 'text',
                    'tooltip' => __( 'Example: Standard ticket, VIP, Early Bird, Student, Regular Admission, etc.', 'tickera-event-ticketing-system' ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_title',
                    'required' => true,
                ),
                /* array(
                  'field_name'		 => 'ticket_description',
                  'field_title'		 => __( 'Ticket Description', 'tickera-event-ticketing-system' ),
                  'placeholder'		 => '',
                  'field_type'		 => 'textarea_editor',
                  'field_description'	 => __( 'Example: Access to the whole Congress, all business networking lounges excluding the Platinum Lounge and the Official Dinner.', 'tickera-event-ticketing-system' ),
                  'table_visibility'	 => false,
                  'post_field_type'	 => 'post_content'
                  ), */
                array(
                    'field_name' => 'price_per_ticket',
                    'field_title' => __( 'Price', 'tickera-event-ticketing-system' ),
                    'placeholder' => '',
                    'field_type' => 'text',
                    'tooltip' => __( 'Price per ticket. <br/>Use only numeric values (do not use currency symbols)', 'tickera-event-ticketing-system' ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'required' => true,
                    'number' => true,
                    'metabox_context' => 'side'
                ),
                array(
                    'field_name' => 'quantity_available',
                    'field_title' => __( 'Ticket quantity', 'tickera-event-ticketing-system' ),
                    'placeholder' => __( 'Unlimited', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'tooltip' => __( 'Number of available tickets', 'tickera-event-ticketing-system' ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'number' => true,
                    'metabox_context' => 'side'
                ),
                array(
                    'field_name' => 'quantity_sold',
                    'field_title' => __( 'Sold', 'tickera-event-ticketing-system' ),
                    'placeholder' => '',
                    'field_type' => 'function',
                    'function' => 'tickera_get_quantity_sold',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'table_edit_invisible' => true
                ),
                array(
                    'field_name' => 'min_tickets_per_order',
                    'field_title' => __( 'Minimum tickets per order', 'tickera-event-ticketing-system' ),
                    'placeholder' => __( 'No minimum', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'tooltip' => __( 'Minimum number of tickets a customer has to add to their cart to be able to finish the purchase', 'tickera-event-ticketing-system' ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'number' => true
                ),
                array(
                    'field_name' => 'max_tickets_per_order',
                    'field_title' => __( 'Maximum tickets per order', 'tickera-event-ticketing-system' ),
                    'placeholder' => __( 'No maximum', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'tooltip' => __( 'Maximum number of tickets that customer can purchase per single order', 'tickera-event-ticketing-system' ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'number' => true
                ),
                array(
                    'field_name' => 'max_tickets_per_user',
                    'field_title' => __( 'Maximum tickets per user', 'tickera-event-ticketing-system' ),
                    'placeholder' => __( 'No Minimum', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'tooltip' => __( 'Maximum number of tickets that a single registered user is allowed to purchase', 'tickera-event-ticketing-system' ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'number' => true
                ),
                array(
                    'field_name' => 'ticket_template',
                    'field_title' => __( 'Ticket template', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ticket_templates',
                    'tooltip' => sprintf(
                        /* translators: %s: Url that links to Tickera > Ticket Templates page. */
                        __( 'Layout of the ticket that the customer will be downloading. <br/>You can create new and manage existing ticket templates <a href="%s" target="_blank">here</a>', 'tickera-event-ticketing-system' ),
                        esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_ticket_templates' ) )
                    ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'metabox_context' => 'side'
                ),
            );

            $use_global_fees = isset( $tc_general_settings[ 'use_global_fees' ] ) ? $tc_general_settings[ 'use_global_fees' ] : 'no';

            if ( $use_global_fees == 'no' ) {
                $default_fields[] = array(
                    'field_name' => 'ticket_fee',
                    'field_title' => __( 'Ticket fee', 'tickera-event-ticketing-system' ),
                    'placeholder' => __( 'No Fees', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'tooltip' => __( 'Additional fee you want to charge for this ticket type (eg. to cover payment gateway fees, to charge a service fee, etc.)', 'tickera-event-ticketing-system' ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'number' => true
                );

                $default_fields[] = array(
                    'field_name' => 'ticket_fee_type',
                    'field_title' => __( 'Ticket fee type', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ticket_fee_type',
                    'field_description' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta'
                );
            }

            $default_fields[] = array(
                'field_name' => '_ticket_availability',
                'field_title' => __( 'Ticket sales availability', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_get_ticket_availability_dates',
                'tooltip' => __( 'Choose if you want to limit ticket sales for this ticket type for certain date range or leave it as open-ended', 'tickera-event-ticketing-system' ),
                'table_visibility' => false,
                'post_field_type' => 'post_meta',
            );

            $default_fields[] = array(
                'field_name' => 'available_checkins_per_ticket',
                'field_title' => __( 'Check-ins per ticket', 'tickera-event-ticketing-system' ),
                'placeholder' => __( 'Unlimited', 'tickera-event-ticketing-system' ),
                'field_type' => 'text',
                'tooltip' => __( 'Number of allowed check-ins for this ticket type', 'tickera-event-ticketing-system' ),
                'table_visibility' => true,
                'post_field_type' => 'post_meta',
                'number' => true,
            );

            $default_fields[] = array(
                'field_name' => 'checkins_time_basis',
                'field_title' => __( 'Limit check-ins on time basis', 'tickera-event-ticketing-system' ),
                'placeholder' => '',
                'field_type' => 'function',
                'function' => 'tickera_get_limit_checkins_fields',
                'tooltip' => '',
                'table_visibility' => true,
                'post_field_type' => 'post_meta',
                'number' => true,
            );

            $default_fields[] = array(
                'field_name' => '_ticket_checkin_availability',
                'field_title' => __( 'Check-in availability', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_get_ticket_checkin_availability_dates',
                'tooltip' => __( 'Select whether you want the tickets of this ticket type to be available for check-ins at any point (open-ended) or you want to use some of the available limitations', 'tickera-event-ticketing-system' ),
                'table_visibility' => false,
                'post_field_type' => 'post_meta',
            );

            $use_global_ticket_checkouts = isset( $tc_general_settings[ 'allow_global_ticket_checkout' ] ) ? $tc_general_settings[ 'allow_global_ticket_checkout' ] : 'no';

            if ( 'no' == $use_global_ticket_checkouts ) {

                $default_fields[] = array(
                    'field_name' => 'allow_ticket_checkout',
                    'field_title' => __( 'Allow ticket check-out', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ticket_allow_checkouts',
                    'tooltip' => __( 'If enabled, when attendee initially checks their ticket in, it will be recorded as check-in. Scanning it for the second time will mark the ticket as checked-out.<br/>Useful if you need to keep track of how many attendees are currently at the event but want to allow attendees to leave and re-enter the event', 'tickera-event-ticketing-system' ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta'
                );
            }

            if ( current_user_can( apply_filters( 'tc_ticket_type_activation_capability', 'edit_others_ticket_types' ) ) || current_user_can( 'manage_options' ) ) {
                $default_fields[] = array(
                    'field_name' => 'ticket_active',
                    'field_title' => __( 'Active', 'tickera-event-ticketing-system' ),
                    'placeholder' => '',
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'read-only',
                    'table_edit_invisible' => true
                );
            }

            return apply_filters( 'tc_ticket_fields', $default_fields );
        }

        function get_columns() {
            $fields = $this->get_ticket_fields();
            $results = tickera_search_array( $fields, 'table_visibility', true );

            $columns = array();

            foreach ( $results as $result ) {
                $columns[ $result[ 'field_name' ] ] = $result[ 'field_title' ];
            }

            $columns[ 'edit' ] = __( 'Edit', 'tickera-event-ticketing-system' );
            $columns[ 'delete' ] = __( 'Delete', 'tickera-event-ticketing-system' );

            return $columns;
        }

        function check_field_property( $field_name, $property ) {
            $fields = $this->get_ticket_fields();
            $result = tickera_search_array( $fields, 'field_name', $field_name );
            return $result[ 0 ][ 'post_field_type' ];
        }

        function is_valid_ticket_field_type( $field_type ) {
            if ( in_array( $field_type, $this->valid_admin_fields_type ) ) {
                return true;
            } else {
                return false;
            }
        }

        function restore_all_ticket_types() {
            $args = array(
                'posts_per_page' => -1,
                'post_type' => 'tc_tickets',
                'post_status' => 'trash'
            );

            $ticket_types = get_posts( $args );

            foreach ( $ticket_types as $ticket_type ) {
                wp_untrash_post( $ticket_type->ID );
            }
        }

        function add_new_ticket() {

            global $user_id;

            if ( isset( $_POST[ 'add_new_ticket' ] ) ) {

                $metas = [];

                $post_data = tickera_sanitize_array( $_POST, true, true );
                $post_data = $post_data ? $post_data : [];

                foreach ( $post_data as $field_name => $field_value ) {

                    if ( preg_match( '/_post_title/', $field_name ) ) {
                        $title = sanitize_text_field( $field_value );
                    }

                    if ( preg_match( '/_post_excerpt/', $field_name ) ) {
                        $excerpt = wp_filter_post_kses( $field_value );
                    }

                    if ( preg_match( '/_post_content/', $field_name ) ) {
                        $content = wp_filter_post_kses( $field_value );
                    }

                    if ( preg_match( '/_post_meta/', $field_name ) ) {
                        $metas[ sanitize_key( str_replace( '_post_meta', '', $field_name ) ) ] = sanitize_text_field( $field_value );
                    }

                    do_action( 'tc_after_ticket_post_field_type_check' );
                }

                $metas = apply_filters( 'tickets_metas', $metas );

                $arg = array(
                    'post_author'   => (int) $user_id,
                    'post_excerpt'  => ( isset( $excerpt ) ? $excerpt : '' ),
                    'post_content'  => ( isset( $content ) ? $content : '' ),
                    'post_status'   => 'publish',
                    'post_title'    => ( isset( $title ) ? $title : '' ),
                    'post_type'     => 'tc_tickets',
                );

                if ( isset( $_POST[ 'post_id' ] ) ) {
                    $arg[ 'ID' ] = (int) $_POST[ 'post_id' ];
                }

                $post_id = @wp_insert_post( tickera_sanitize_array( $arg, true ), true );

                // Update post meta
                if ( $post_id !== 0 ) {
                    foreach ( $metas as $key => $value ) {
                        update_post_meta( (int) $post_id, $key, tickera_sanitize_array( $value, true, true ) );
                    }
                }

                return $post_id;
            }
        }
    }
}
