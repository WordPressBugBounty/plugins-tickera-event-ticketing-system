<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Ticket_Instance' ) ) {

    class TC_Ticket_Instance {

        var $id = '';
        var $output = 'OBJECT';
        var $ticket = array();
        var $details;

        function __construct( $id = '', $output = 'OBJECT' ) {

            $this->id = $id;
            $this->output = $output;
            $this->details = get_post( $this->id, $this->output );

            $fields = \Tickera\TC_Tickets_Instances::get_tickets_instances_fields();

            foreach ( $fields as $field ) {

                if ( $this->details && ! isset( $this->details->{$field[ 'field_name' ]} ) ) {

                    @$this->details->{$field[ 'field_name' ]} = get_post_meta( $this->id, $field[ 'field_name' ], true );

                    // Get default meta key value
                    if ( ! $this->details->{$field[ 'field_name' ]} ) {
                        @$this->details->{$field[ 'field_name' ]} = get_post_meta( $this->id, strtolower( $field[ 'field_name' ] ), true );
                    }
                }
            }
        }

        function TC_Ticket_Instance( $id = '', $output = 'OBJECT' ) {
            $this->__construct( $id, $output );
        }

        function get_number_of_checkins( $checkin_type = 'pass' ) {
            $checkins = get_post_meta( $this->id, 'tc_checkins', false );
            $checkins_num = 0;

            if ( is_array( $checkins ) && count( $checkins ) > 0 ) {

                if ( isset( $checkins[ 0 ] ) ) {
                    if ( is_array( $checkins ) && count( $checkins ) > 0 && $checkins[ 0 ] != '' ) {
                        $checkins = $checkins[ 0 ];
                        foreach ( $checkins as $checkin ) {
                            if ( strtolower( $checkin[ 'status' ] ) == $checkin_type ) {
                                $checkins_num++;
                            }
                        }
                    }
                }
                return $checkins_num;
            } else {
                return 0;
            }
        }

        /**
         * Collects ticket check-ins object
         *
         * @return bool|mixed
         */
        function get_ticket_checkins() {

            $checkins = get_post_meta( $this->id, 'tc_checkins', false );

            if ( is_array( $checkins ) && count( $checkins ) > 0 ) {
                $checkins = $checkins[ 0 ];
                return $checkins;

            } else {
                return false;
            }
        }

        /**
         * Collects ticket check-outs object
         *
         * @return bool|mixed
         */
        function get_ticket_checkouts() {
            $checkouts = get_post_meta( $this->id, 'tc_checkouts', false );

            if ( is_array( $checkouts ) && count( $checkouts ) > 0 ) {
                $checkouts = $checkouts[ 0 ];
                return $checkouts;

            } else {
                return false;
            }
        }

        function get_ticket_instance() {
            $order = get_post_custom( $this->id, $this->output );
            return $order;
        }

        function get_event_id() {
            $ticket_type_id = $this->details->ticket_type_id;
            $event_id = get_post_meta( $ticket_type_id, 'event_name', true );
            $alternate_event_id = get_post_meta( $ticket_type_id, apply_filters( 'tc_event_name_field_name', 'event_name' ), true );

            $event_id = ! empty( $event_id ) ? $event_id : $event_id;
            return $event_id;
        }

        function delete_ticket_instance( $force_delete = true ) {

            if ( $force_delete ) {
                wp_delete_post( $this->id );

            } else {
                wp_trash_post( $this->id );
            }
        }
    }
}
