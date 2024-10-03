<?php
/**
 * Ready for new ticket template
 */

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_ticket_id_element' ) ) {

    class tc_ticket_id_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_ticket_id_element';
        var $element_title = 'Ticket ID';
        var $font_awesome_icon = '<i class="fa fa-slack"></i>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_ticket_id_element_title', __( 'Ticket ID', 'tickera-event-ticketing-system' ) );
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                return '<br/>' . apply_filters( 'tc_ticket_ticket_id_element', $ticket_instance_id );

            } else {
                return '<br/>' . apply_filters( 'tc_ticket_ticket_id_element_default', __( '123', 'tickera-event-ticketing-system' ) );
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_ticket_id_element', __( 'Ticket ID', 'tickera-event-ticketing-system' ) );
}