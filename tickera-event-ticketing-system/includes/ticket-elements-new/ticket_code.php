<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_ticket_code_element' ) ) {

    class tc_ticket_code_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_ticket_code_element';
        var $element_title = 'Ticket Code';
        var $font_awesome_icon = '<i class="fa fa-bars"></i>';


        function on_creation() {
            $this->element_title = apply_filters( 'tc_ticket_code_element_title', __( 'Ticket Code', 'tickera-event-ticketing-system' ) );
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket_Instance( (int) $ticket_instance_id );
                $ticket_code = $ticket_instance->details->ticket_code;
                return '<br/>' . wp_kses_post( apply_filters( 'tc_ticket_ticket_code_element', $ticket_code ) );

            } else {
                return '<br/>' . wp_kses_post( apply_filters( 'tc_ticket_ticket_code_element_default', __( '123456-1', 'tickera-event-ticketing-system' ) ) );
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_ticket_code_element', __( 'Ticket Code', 'tickera-event-ticketing-system' ) );
}