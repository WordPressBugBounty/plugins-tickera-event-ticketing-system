<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\TC_Ticket_Instance' ) ) {

    class TC_Ticket_Instance {

        var $id = '';
        var $output = 'OBJECT';
        var $ticket = array();
        var $details;

        function __construct( $id = '', $output = 'OBJECT' ) {

            $this->id = $id;
            $this->output = $output;
            $this->details = get_post( $this->id, $this->output );

            $fields = TC_Tickets_Instances::get_tickets_instances_fields();

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
            $checkins = self::get_attendance_records( $this->id );
            $checkins_num = 0;

            if ( is_array( $checkins ) && count( $checkins ) > 0 ) {
                foreach ( $checkins as $checkin ) {
                    if ( 'in' === $checkin[ 'direction' ] && strtolower( $checkin[ 'status' ] ) == $checkin_type ) {
                        $checkins_num++;
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
            $checkins = self::get_attendance_records( $this->id );
            return $checkins ? $checkins : false;
        }

        /**
         * Collects ticket check-outs object
         *
         * @return bool|mixed
         */
        function get_ticket_checkouts() {
            $checkouts = array_filter( self::get_attendance_records( $this->id ), function ( $record ) {
                return 'out' === $record[ 'direction' ];
            } );

            return $checkouts ? array_values( $checkouts ) : false;
        }

        /**
         * Return the unified, chronological attendance log and migrate legacy checkouts.
         *
         * @param int $ticket_instance_id Ticket instance ID.
         * @return array
         */
        public static function get_attendance_records( $ticket_instance_id ) {
            $records = get_post_meta( $ticket_instance_id, 'tc_checkins', true );
            $records = is_array( $records ) ? $records : array();
            $legacy_checkouts = get_post_meta( $ticket_instance_id, 'tc_checkouts', true );
            $legacy_checkouts = is_array( $legacy_checkouts ) ? $legacy_checkouts : array();
            $records_changed = false;

            if ( isset( $legacy_checkouts[ 'outs' ] ) && is_array( $legacy_checkouts[ 'outs' ] ) ) {
                $legacy_checkouts = $legacy_checkouts[ 'outs' ];
            }

            foreach ( $records as &$record ) {
                if ( is_array( $record ) && ! isset( $record[ 'direction' ] ) ) {
                    $record[ 'direction' ] = 'in';
                    $records_changed = true;
                }
            }
            unset( $record );

            foreach ( $legacy_checkouts as $checkout ) {
                if ( is_array( $checkout ) && isset( $checkout[ 'date_checked' ] ) ) {
                    $checkout[ 'direction' ] = 'out';
                    $records[] = $checkout;
                    $records_changed = true;
                }
            }

            $unsorted_records = $records;
            self::sort_attendance_records( $records );
            if ( $records !== $unsorted_records ) {
                $records_changed = true;
            }

            if ( $records_changed || metadata_exists( 'post', $ticket_instance_id, 'tc_checkouts' ) ) {
                update_post_meta( $ticket_instance_id, 'tc_checkins', $records );
            }

            if ( metadata_exists( 'post', $ticket_instance_id, 'tc_checkouts' ) ) {
                delete_post_meta( $ticket_instance_id, 'tc_checkouts' );
            }

            return $records;
        }

        /**
         * Sort attendance records from oldest to newest without losing equal timestamps.
         *
         * @param array $records Attendance records.
         * @return array
         */
        public static function sort_attendance_records( &$records ) {
            $indexed = array();
            foreach ( array_values( $records ) as $index => $record ) {
                if ( is_array( $record ) ) {
                    $indexed[] = array( 'index' => $index, 'record' => $record );
                }
            }

            usort( $indexed, function ( $left, $right ) {
                $comparison = (int) ( $left[ 'record' ][ 'date_checked' ] ?? 0 ) <=> (int) ( $right[ 'record' ][ 'date_checked' ] ?? 0 );
                return 0 !== $comparison ? $comparison : $left[ 'index' ] <=> $right[ 'index' ];
            } );

            $records = array_column( $indexed, 'record' );
            return $records;
        }

        function get_ticket_instance() {
            $order = get_post_custom( $this->id, $this->output );
            return $order;
        }

        function get_event_id() {
            $ticket_type_id = $this->details->ticket_type_id;
            $event_id = get_post_meta( $ticket_type_id, 'event_name', true );
            $alternate_event_id = get_post_meta( $ticket_type_id, tickera_apply_filters( 'tickera_event_name_field_name', 'event_name', $ticket_type_id ), true );

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
