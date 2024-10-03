<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_event_date_time_element' ) ) {

    class tc_event_date_time_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_event_date_time_element';
        var $element_title = 'Event Date & Time';
        var $font_awesome_icon = '<span class="tti-date_schedule_calendar_event_icon-1"></span>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_event_date_time_element_title', __( 'Event Date & Time', 'tickera-event-ticketing-system' ) );
        }

        function get_event_date( $event_id ) {
            $event_start_date = get_post_meta( $event_id, 'event_date_time', true );
            $event_end_date = get_post_meta( $event_id, 'event_end_date_time', true );

            $start_date = date_i18n( get_option( 'date_format' ), strtotime( $event_start_date ) );
            $start_time = date_i18n( get_option( 'time_format' ), strtotime( $event_start_date ) );

            $end_date = date_i18n( get_option( 'date_format' ), strtotime( $event_end_date ) );
            $end_time = date_i18n( get_option( 'time_format' ), strtotime( $event_end_date ) );

            if ( ! empty( $event_end_date ) ) {

                if ( $start_date == $end_date ) {

                    if ( $start_time == $end_time ) {
                        $event_date = $start_date . ' ' . $start_time;

                    } else {
                        $event_date = $start_date . ' ' . $start_time . ' - ' . $end_time;
                    }

                } else {

                    if ( $start_time == $end_time ) {
                        $event_date = $start_date . ' - ' . $end_date . ' ' . $start_time;

                    } else {
                        $event_date = $start_date . ' ' . $start_time . ' - ' . $end_date . ' ' . $end_time;
                    }
                }

            } else {
                $event_date = $start_date . ' ' . $start_time;
            }

            return $event_date;
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket( (int) $ticket_instance_id );
                $ticket = new \Tickera\TC_Ticket();
                $event_id = $ticket->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );
                $event_date = $this->get_event_date( $event_id );
                return '<br/>' . apply_filters( 'tc_event_date_time_element_ticket_type', $event_date, apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ), $ticket_instance_id );

            } else {

                if ( $ticket_type_id ) {
                    $ticket_type = new \Tickera\TC_Ticket( (int) $ticket_type_id );
                    $event_id = $ticket_type->get_ticket_event( $ticket_type_id );
                    $event_date = $this->get_event_date( $event_id );
                    return '<br/>' . $event_date;

                } else {
                    return '<br/>' . apply_filters( 'tc_event_date_time_element_default', date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), time(), false ) );
                }
            }
        }

    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_event_date_time_element', __( 'Event Date & Time', 'tickera-event-ticketing-system' ) );
}