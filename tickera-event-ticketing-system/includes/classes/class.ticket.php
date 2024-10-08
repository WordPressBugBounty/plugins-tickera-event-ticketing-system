<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Ticket' ) ) {

    class TC_Ticket {

        var $id = '';
        var $output = 'OBJECT';
        var $ticket = array();
        var $details;

        function __construct( $id = '', $status = 'any', $output = 'OBJECT' ) {
            $continue = true;

            if ( $status !== 'any' ) {
                $continue = ( get_post_status( $id ) == $status ) ? true : false;
            }

            if ( $continue ) {
                $this->id = $id;
                $this->output = $output;
                $this->details = get_post( $this->id, $this->output );

                $tickets = new TC_Tickets();
                $fields = $tickets->get_ticket_fields();

                if ( isset( $this->details ) ) {
                    if ( ! empty( $fields ) ) {
                        foreach ( $fields as $field ) {
                            if ( ! isset( $this->details->{$field[ 'field_name' ]} ) ) {
                                $this->details->{$field[ 'field_name' ]} = get_post_meta( $this->id, $field[ 'field_name' ], true );
                            }
                        }
                    }
                }

            } else {
                $this->id = null;
            }
        }

        function TC_Ticket( $id = '', $output = 'OBJECT' ) {
            $this->__construct( $id, $output );
        }

        /**
         * Ticket Sales Availability
         *
         * @param bool $ticket_type_id
         * @return bool|mixed|void
         */
         public static function is_sales_available( $ticket_type_id = false ) {

            if ( ! $ticket_type_id  ) {
                return false;
            }

            // Not available if ticket type doesn't exist
            if ( ! get_post( $ticket_type_id )
                || ! in_array( get_post_type( $ticket_type_id ), [ 'tc_tickets', 'product' ] ) ) {
                return false;
            }

            $is_sales_available = true;
            $metas = get_post_meta( $ticket_type_id );

            // Ticket Sales Availability
            $ticket_availability = isset( $metas[ '_ticket_availability' ] ) ? $metas[ '_ticket_availability' ][ 0 ] : 'open_ended';
            if ( 'range' == $ticket_availability ) {

                $from_date = isset( $metas[ '_ticket_availability_from_date' ] ) ? $metas[ '_ticket_availability_from_date' ] [ 0 ] : '';
                $to_date = isset( $metas[ '_ticket_availability_to_date' ] ) ? $metas[ '_ticket_availability_to_date' ][ 0 ] : '';

                if ( ( date( 'U', current_time( 'timestamp', false ) ) >= date( 'U', strtotime( $from_date ) ) ) && ( date( 'U', current_time( 'timestamp', false ) ) <= date( 'U', strtotime( $to_date ) ) ) ) {
                    // Ticket is saleable
                } else {
                    $is_sales_available = false;
                }
            }

            return apply_filters( 'tc_is_ticket_type_sales_available', $is_sales_available, $ticket_type_id );
        }

        public static function is_checkin_available( $ticket_type_id = false, $order = false, $ticket_id = false ) {

            if ( ! $ticket_type_id ) {
                return false;

            } else {

                $ticket_checkin_availability = get_post_meta( $ticket_type_id, '_ticket_checkin_availability', true );
                $ticket_checkin_availability = ( ! empty( $ticket_checkin_availability ) ) ? $ticket_checkin_availability : 'open_ended';

                if ( 'range' == $ticket_checkin_availability ) {

                    $from_date = get_post_meta( $ticket_type_id, '_ticket_checkin_availability_from_date', true );
                    $to_date = get_post_meta( $ticket_type_id, '_ticket_checkin_availability_to_date', true );

                    return ( ( date( 'U', current_time( 'timestamp', false ) ) >= date( 'U', strtotime( $from_date ) ) ) && ( date( 'U', current_time( 'timestamp', false ) ) <= date( 'U', strtotime( $to_date ) ) ) )
                        ? true
                        : false;

                } elseif ( 'time_after_order' == $ticket_checkin_availability ) {

                    $days_selected = get_post_meta( $ticket_type_id, '_time_after_order_days', true );
                    $hours_selected = get_post_meta( $ticket_type_id, '_time_after_order_hours', true );
                    $minutes_selected = get_post_meta( $ticket_type_id, '_time_after_order_minutes', true );

                    $total_seconds = (int) ( (int) $days_selected * 24 * 60 * 60 ) + ( (int) $hours_selected * 60 * 60 ) + ( (int) $minutes_selected * 60 );
                    $order_date = $order->details->post_date;

                    $order_limit_timestamp = strtotime( $order_date ) + $total_seconds;
                    $current_site_timestamp = current_time( 'timestamp', false );

                    return ( $order_limit_timestamp > $current_site_timestamp ) ? true : false;

                } elseif ( 'time_after_first_checkin' == $ticket_checkin_availability ) {

                    $days_selected = get_post_meta( $ticket_type_id, '_time_after_first_checkin_days', true );
                    $hours_selected = get_post_meta( $ticket_type_id, '_time_after_first_checkin_hours', true );
                    $minutes_selected = get_post_meta( $ticket_type_id, '_time_after_first_checkin_minutes', true );

                    $total_seconds = (int) ( $days_selected * 24 * 60 * 60 ) + ( $hours_selected * 60 * 60 ) + ( $minutes_selected * 60 );

                    $ticket_instance = new \Tickera\TC_Ticket_Instance( (int) $ticket_id );
                    $ticket_checkins = $ticket_instance->get_ticket_checkins();

                    if ( $ticket_checkins ) {

                        foreach ( $ticket_checkins as $ticket_key => $ticket_checkin ) {
                            if ( 'Pass' == $ticket_checkin[ 'status' ] ) {
                                $first_checkin_date = $ticket_checkin[ 'date_checked' ];
                                break;

                            } else {
                            } // Continue finding valid value
                        }

                    } else {
                        return true; // There is no a single check-in so we'll allow the first checkin to happens
                    }

                    if ( empty( $first_checkin_date ) ) {
                        return true;
                    }

                    $first_checkin_limit_timestamp = ( $first_checkin_date ) + $total_seconds;
                    $current_site_timestamp = current_time( 'timestamp', 1 );

                    return ( $first_checkin_limit_timestamp > $current_site_timestamp ) ? true : false;

                } elseif ( 'upon_event_starts' == $ticket_checkin_availability ) {

                    $ticket_type = new \Tickera\TC_Ticket( $ticket_type_id );
                    $event_id = $ticket_type->get_ticket_event();
                    $event_date = get_post_meta( $event_id, 'event_date_time', true );

                    /*
                     * True = Event starts already
                     * False = Event didn't start yet
                     */
                    return ( date( 'U', current_time( 'timestamp', false ) ) >= date( 'U', strtotime( $event_date ) ) ) ? true : false;

                } else {
                    return true; // open-ended
                }
            }
        }

        function get_ticket() {
            return get_post_custom( $this->id, $this->output );
        }

        /**
         * Get the total number of sold tickets
         * @return int
         */
        function get_number_of_sold_tickets() {
            $ticket_search = new \Tickera\TC_Tickets_Instances_Search( '', '', -1, false, false, 'ticket_type_id', $this->id );
            return ( is_array( $ticket_search->get_results() ) ) ? count( $ticket_search->get_results() ) : 0;
        }

        /**
         * Get the number of remaining ticket quantity
         * @return int|mixed
         */
        function get_tickets_quantity_left() {

            $max_quantity = $this->details->quantity_available;
            $sold_quantity = tickera_get_tickets_count_sold( $this->id );

            if ( '' == $max_quantity ) {
                return 9999; // Means no limit

            } else {
                return ( $max_quantity - $sold_quantity );
            }
        }

        /**
         * Determine the availability of a ticket
         * @return bool
         */
        function is_ticket_exceeded_quantity_limit() {

            $max_quantity = $this->details->quantity_available;

            if ( '' == $max_quantity ) {
                return false;

            } else {

                $sold_quantity = tickera_get_tickets_count_sold( $this->id );
                if ( $sold_quantity < $max_quantity ) {
                    return false;

                } else {
                    return true;
                }
            }
        }

        /**
         * Determine the Ticket or Event Limit Level availability
         * @return bool
         */
        function is_sold_ticket_exceeded_limit_level() {

            $event_id = self::get_ticket_event( $this->id );
            $event_obj = get_post_meta( $event_id );
            $limit_level = isset( $event_obj[ 'limit_level' ] ) ? $event_obj[ 'limit_level' ][ 0 ] : 0;

            if ( $limit_level ) {

                $event_ticket_sold_count = tickera_get_event_tickets_count_sold( $event_id );

                $max_limit_value = ''; // Unlimited as default
                if ( isset( $event_obj[ 'limit_level_value' ] ) && '' != $event_obj[ 'limit_level_value' ][0] ) {
                    $max_limit_value = (int) $event_obj[ 'limit_level_value' ][0];
                }

                return ( '' === $max_limit_value || $max_limit_value > $event_ticket_sold_count ) ? false : true;

            } else {
                return self::is_ticket_exceeded_quantity_limit();
            }
        }

        /**
         * Delete Ticket Type ID
         * @param bool $force_delete
         */
        function delete_ticket( $force_delete = false ) {
            if ( $force_delete ) {
                wp_delete_post( $this->id );

            } else {
                wp_trash_post( $this->id );
            }
        }

        /**
         * Get the Ticket Type Event ID
         * @param bool $ticket_type_id
         * @return mixed
         */
        function get_ticket_event( $ticket_type_id = false ) {
            $ticket_type_id = ! $ticket_type_id ? $this->id : $ticket_type_id;
            return get_post_meta( $ticket_type_id, apply_filters( 'tc_event_name_field_name', 'event_name' ), true );
        }

        /**
         * Get the Ticket Type ID by post_name
         * @param $slug
         * @return bool|int
         */
        function get_ticket_id_by_name( $slug ) {
            $post = get_posts( array(
                    'name' => $slug,
                    'post_type' => 'tc_tickets',
                    'post_status' => 'any',
                    'posts_per_page' => 1
                )
            );
            return ( $post ) ? $post[ 0 ]->ID : false;
        }
    }
}
