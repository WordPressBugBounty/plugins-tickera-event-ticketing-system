<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_event_categories_element' ) ) {

    class tc_event_categories_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_event_categories_element';
        var $element_title = 'Event Category';
        var $font_awesome_icon = '<span class="tti-category_menu_options_icon"></span>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_event_categories_element_title', __( 'Event Category', 'tickera-event-ticketing-system' ) );
        }

        function get_event_categories( $event_id ) {

            $cats_name = '';
            $terms = get_the_terms( $event_id, 'event_category' );

            if ( $terms ) {

                if ( count( $terms ) > 1 ) {

                    foreach ( $terms as $term ) {
                        $cats_name .= ucfirst( $term->name ) . ', ';
                    }

                } else {
                    $cats_name = ucfirst( $terms[ 0 ]->name );
                }
            }

            return rtrim( $cats_name, ', ' );
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket( (int) $ticket_instance_id );
                $ticket = new \Tickera\TC_Ticket();
                $event_id = $ticket->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );
                $event_category = $this->get_event_categories( $event_id );
                return '<br/>' . apply_filters( 'tc_event_categories_element', $event_category );

            } else {

                if ( $ticket_type_id ) {
                    $ticket_type = new \Tickera\TC_Ticket( (int) $ticket_type_id );
                    $event_id = $ticket_type->get_ticket_event( $ticket_type_id );
                    $event_category = $this->get_event_categories( $event_id );
                    return '<br/>' . apply_filters( 'tc_event_categories_element', $event_category );

                } else {
                    return '<br/>' . apply_filters( 'tc_event_categories_element', __( 'Category', 'tickera-event-ticketing-system' ) );
                }
            }
        }

    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_event_categories_element', __( 'Event Category', 'tickera-event-ticketing-system' ) );
}
