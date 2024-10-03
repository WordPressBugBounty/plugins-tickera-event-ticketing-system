<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Cart_Form' ) ) {

    class TC_Cart_Form {

        var $form_title = '';
        var $valid_admin_fields_type = [ 'text', 'textarea', 'checkbox', 'function' ];
        var $ticket_type_id = '';

        function __construct( $ticket_type_id = '' ) {
            $this->ticket_type_id = $ticket_type_id;
            $this->valid_admin_fields_type = apply_filters( 'tc_valid_admin_fields_type', $this->valid_admin_fields_type );
        }

        function TC_Cart_Form( $ticket_type_id = '' ) {
            $this->__construct( $ticket_type_id );
        }

        function get_buyer_info_fields() {

            $user_info = get_userdata( get_current_user_id() );

            $default_fields = array(
                array(
                    'field_name' => 'first_name',
                    'field_title' => __( 'First Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta',
                    'required' => true,
                    'default_value' => isset( $user_info->first_name ) ? $user_info->first_name : ''
                ),
                array(
                    'field_name' => 'last_name',
                    'field_title' => __( 'Last Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta',
                    'required' => true,
                    'default_value' => isset( $user_info->last_name ) ? $user_info->last_name : ''
                ),
                array(
                    'field_name' => 'email',
                    'field_title' => __( 'E-mail', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta',
                    'required' => true,
                    'validation_type' => 'email',
                    'default_value' => isset( $user_info->user_email ) ? $user_info->user_email : ''
                ),
                array(
                    'field_name' => 'confirm_email',
                    'field_title' => __( 'Confirm E-mail', 'tickera-event-ticketing-system' ),
                    'field_type' => 'email',
                    'field_description' => '',
                    'post_field_type' => 'post_meta',
                    'required' => true,
                    'validation_type' => 'email',
                    'default_value' => isset( $user_info->user_email ) ? $user_info->user_email : ''
                ),
            );

            return apply_filters( 'tc_buyer_info_fields', $default_fields, isset( $ticket_type_id ) ? $ticket_type_id : '' );
        }

        /**
         * Get default value for input text or text area
         *
         * @param $field
         * @return mixed|string
         */
        function get_default_value( $field ) {
            return isset( $field[ 'default_value' ] ) ? $field[ 'default_value' ] : '';
        }

        function get_owner_info_fields( $ticket_type_id = '' ) {

            $tc_general_settings = get_option( 'tickera_general_setting', false );

            $default_fields[] = [
                'field_name' => 'ticket_type_id',
                'field_title' => __( 'Ticket Type ID', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_get_ticket_type_form_field',
                'field_description' => '',
                'post_field_type' => 'post_meta',
                'form_visibility' => false,
                'required' => false
            ];

            if (
                ! isset( $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] )
                || ( isset( $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] ) && 'yes' == $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] )
            ) {
                $default_fields[] = [
                    'field_name' => 'first_name',
                    'field_title' => __( 'First Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta',
                    'form_visibility' => true,
                    'required' => ( isset( $tc_general_settings[ 'first_name_field_required' ] ) && 'no' == $tc_general_settings[ 'first_name_field_required' ] ) ? false : true
                ];
            }

            if (
                ! isset( $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] )
                || ( isset( $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] ) && 'yes' == $tc_general_settings[ 'show_attendee_first_and_last_name_fields' ] )
            ) {
                $default_fields[] = [
                    'field_name' => 'last_name',
                    'field_title' => __( 'Last Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta',
                    'form_visibility' => true,
                    'required' => ( isset( $tc_general_settings[ 'last_name_field_required' ] ) && 'no' == $tc_general_settings[ 'last_name_field_required' ] ) ? false : true
                ];
            }

            if ( isset( $tc_general_settings[ 'show_owner_email_field' ] ) && 'yes' == $tc_general_settings[ 'show_owner_email_field' ] ) {
                $default_fields[] = [
                    'field_name' => 'owner_email',
                    'field_title' => __( 'E-Mail', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_meta',
                    'form_visibility' => true,
                    'required' => true
                ];
            }

            if ( isset( $tc_general_settings[ 'email_verification_buyer_owner' ] ) && 'yes' == $tc_general_settings[ 'email_verification_buyer_owner' ] ) {
                $default_fields[] = [
                    'field_name' => 'owner_confirm_email',
                    'field_title' => __( 'Confirm E-Mail', 'tickera-event-ticketing-system' ),
                    'field_type' => 'email',
                    'field_description' => '',
                    'post_field_type' => 'post_meta',
                    'form_visibility' => true,
                    'required' => true
                ];
            }

            return apply_filters( 'tc_owner_info_fields', $default_fields, $ticket_type_id );
        }

        function get_columns() {

            $fields = $this->get_event_fields();
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
            $fields = $this->get_event_fields();
            $result = tickera_search_array( $fields, 'field_name', $field_name );
            return $result[ 0 ][ 'post_field_type' ];
        }

        function is_valid_event_field_type( $field_type ) {
            return ( in_array( $field_type, $this->valid_admin_fields_type ) ) ? true : false;
        }
    }
}
