<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_ticket_owner_name_element' ) ) {

    class tc_ticket_owner_name_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_ticket_owner_name_element';
        var $element_title = 'Ticket Owner Name';
        var $font_awesome_icon = '<i class="fa fa-users"></i>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_ticket_owner_name_element_title', __( 'Ticket Owner Name', 'tickera-event-ticketing-system' ) );
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket_Instance( (int) $ticket_instance_id );
                $owner_name = $ticket_instance->details->first_name . '&nbsp;' . $ticket_instance->details->last_name;
                return '<br/>' . apply_filters( 'tc_ticket_owner_name_element', $owner_name );

            } else {
                return '<br/>' . apply_filters( 'tc_ticket_owner_name_element_default', __( 'John Smith', 'tickera-event-ticketing-system' ) );
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_ticket_owner_name_element', __( 'Ticket Owner Name', 'tickera-event-ticketing-system' ) );
}