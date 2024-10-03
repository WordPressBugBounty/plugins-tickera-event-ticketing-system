<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Tickets_Instances' ) ) {

    class TC_Tickets_Instances {

        var $form_title = '';
        var $valid_admin_fields_type = array( 'text', 'function' );

        function __construct() {
            $this->form_title = __( 'Attendees & Tickets', 'tickera-event-ticketing-system' );
            $this->valid_admin_fields_type = apply_filters( 'tc_valid_tickets_instances_admin_fields_type', $this->valid_admin_fields_type );
        }

        function TC_Tickets_Instances() {
            $this->__construct();
        }

        public static function get_tickets_instances_fields() {

            $default_fields = array(
                /*array(
                    'id'				 => 'ticket_ID',
                    'field_name'		 => 'ticket_ID',
                    'field_title'		 => __( 'Ticket ID', 'tickera-event-ticketing-system' ),
                    'field_type'		 => 'ID',
                    'field_description'	 => '',
                    'table_visibility'	 => true,
                    'post_field_type'	 => 'ID'
                ),*/
                array(
                    'id' => 'owner_name',
                    'field_name' => 'owner_name',
                    'field_title' => __( 'Attendee', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'ticket_code',
                    'field_name' => 'ticket_code',
                    'field_title' => __( 'Ticket Code', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'id' => 'ticket_type_id',
                    'field_name' => 'ticket_type_id',
                    'field_title' => __( 'Ticket Type', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),

                array(
                    'id' => 'event',
                    'field_name' => 'event_ID',
                    'field_title' => __( 'Event', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'ID'
                ),
                array(
                    'id' => 'order',
                    'field_name' => 'post_parent',
                    'field_title' => __( 'Order', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_parent'
                ),
                array(
                    'id' => 'order_status',
                    'field_name' => 'order_status',
                    'field_title' => __( 'Order Status', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_status'
                ),//show order status column
                array(
                    'id' => 'ticket',
                    'field_name' => 'ticket_links',
                    'field_title' => __( 'Ticket', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'ID'
                ),
                array(
                    'id' => 'checkins',
                    'field_name' => 'ID',
                    'field_title' => __( 'Check-ins', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'ID'
                ),
            );

            return apply_filters( 'tc_tickets_instances_fields', $default_fields );
        }

        function get_columns() {
            $fields = \Tickera\TC_Tickets_Instances::get_tickets_instances_fields();
            $results = tickera_search_array( $fields, 'table_visibility', true );

            $columns = array();

            foreach ( $results as $result ) {
                if ( isset( $result[ 'id' ] ) ) {
                    $columns[][ 'id' ] = $result[ 'id' ];
                    $index = ( count( $columns ) - 1 );
                    $columns[ $index ][ 'field_name' ] = $result[ 'field_name' ];
                    $columns[ $index ][ 'field_title' ] = $result[ 'field_title' ];
                    //$columns[$result['id']][$result['field_name']] = $result['field_title'];
                } else {
                    $columns[][ 'id' ] = $result[ 'field_name' ];
                    $index = ( count( $columns ) - 1 );
                    $columns[ $index ][ 'field_name' ] = $result[ 'field_name' ];
                    $columns[ $index ][ 'field_title' ] = $result[ 'field_title' ];
                    //$columns[$result['field_name']][$result['field_name']] = $result['field_title'];
                }
            }

            if ( current_user_can( 'manage_options' ) || current_user_can( 'delete_attendees_cap' ) ) {
                $columns[][ 'id' ] = 'delete';
                $index = ( count( $columns ) - 1 );
                $columns[ $index ][ 'field_name' ] = 'delete';
                $columns[ $index ][ 'field_title' ] = __( 'Delete', 'tickera-event-ticketing-system' );
            }
            return $columns;
        }

        public static function check_field_property( $field_name, $property ) {
            $fields = \Tickera\TC_Tickets_Instances::get_tickets_instances_fields();
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
    }
}
