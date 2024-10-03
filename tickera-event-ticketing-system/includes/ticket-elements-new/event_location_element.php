<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_event_location_element' ) ) {

    class tc_event_location_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_event_location_element';
        var $element_title = 'Event Location';
        var $font_awesome_icon = '<i class="fa fa-map-marker"></i>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_event_location_element_title', __( 'Event Location', 'tickera-event-ticketing-system' ) );
        }

        function advanced_admin_element_settings() {
            ob_start();
            $this->get_att_fonts();
            $this->get_font_colors();
            $this->get_font_sizes();
            $this->get_font_style();
            $this->get_default_text_value( __( 'Grosvenor Square, Mayfair, London', 'tickera-event-ticketing-system' ) );
            return ob_get_clean();
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {
            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket( (int) $ticket_instance_id );
                $ticket = new \Tickera\TC_Ticket();
                $event_id = $ticket->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );
                return '<br/>' . apply_filters( 'tc_event_location_element', get_post_meta( $event_id, 'event_location', true ) );

            } else {

                if ( $ticket_type_id ) {
                    $ticket_type = new \Tickera\TC_Ticket( (int) $ticket_type_id );
                    $event_id = $ticket_type->get_ticket_event( $ticket_type_id );
                    $event = new \Tickera\TC_Event( $event_id );
                    return '<br/>' . apply_filters( 'tc_event_location_element', $event->details->event_location );

                } else {
                    return '<br/>' . apply_filters( 'tc_event_location_element_default', esc_html__( 'Grosvenor Square, Mayfair, London', 'tickera-event-ticketing-system' ) );
                }
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_event_location_element', __( 'Event Location', 'tickera-event-ticketing-system' ) );
}