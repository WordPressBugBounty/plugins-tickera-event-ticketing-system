<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_event_logo_element' ) ) {

    class tc_event_logo_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_event_logo_element';
        var $element_title = 'Event Logo';
        var $font_awesome_icon = '<i class="fa fa-picture-o"></i>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_event_logo_element_title', __( 'Event Logo', 'tickera-event-ticketing-system' ) );
        }

        function admin_content() {
            ob_start();
            parent::get_cell_alignment();
            parent::get_element_margins();
            return ob_get_clean();
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            global $tc;

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket( (int) $ticket_instance_id );
                $ticket = new \Tickera\TC_Ticket();
                $event_id = $ticket->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );
                $event_logo = apply_filters( 'tc_event_logo_element', get_post_meta( $event_id, 'event_logo_file_url', true ) );

                if ( $event_logo ) {
                    return '<br/><img src="' . esc_url( tickera_ticket_template_image_url( $event_logo ) ) . '" />';

                } else {
                    return '<br/>';
                }

            } else {

                if ( $ticket_type_id ) {
                    $ticket_type = new \Tickera\TC_Ticket( (int) $ticket_type_id );
                    $event_id = $ticket_type->get_ticket_event( $ticket_type_id );
                    $event = new \Tickera\TC_Event( $event_id );
                    return '<br/>' . apply_filters( 'tc_event_logo_element', '<img src="' . esc_url( $event->details->event_logo_file_url ) . '" />' );

                } else {
                    return '<br/>' . apply_filters( 'tc_event_logo_element_default', '<img src="' . esc_url( $tc->plugin_dir . 'images/tickera_logo.png' ) . '" />' );
                }
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_event_logo_element', __( 'Event Logo', 'tickera-event-ticketing-system' ) );
}