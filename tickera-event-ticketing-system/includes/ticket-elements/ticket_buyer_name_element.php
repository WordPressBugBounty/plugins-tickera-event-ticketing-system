<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_ticket_buyer_name_element' ) ) {

    class tc_ticket_buyer_name_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_ticket_buyer_name_element';
        var $element_title = 'Ticket Buyer Name';
        var $font_awesome_icon = '<i class="fa fa-user"></i>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_ticket_buyer_name_element_title', __( 'Ticket Buyer Name', 'tickera-event-ticketing-system' ) );
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket_Instance( (int) $ticket_instance_id );
                $order = new \Tickera\TC_Order( $ticket_instance->details->post_parent );
                $buyer_data = ( isset( $order->details->tc_cart_info[ 'buyer_data' ] ) ) ? $order->details->tc_cart_info[ 'buyer_data' ] : [];
                $first_name = ( isset( $buyer_data[ 'first_name_post_meta' ] ) ) ? $buyer_data[ 'first_name_post_meta' ] : '';
                $last_name = ( isset( $buyer_data[ 'last_name_post_meta' ] ) ) ? $buyer_data[ 'last_name_post_meta' ] : '';
                $buyer_name = $first_name . ' ' . $last_name;
                return '<br/>' . wp_kses_post( apply_filters( 'tc_ticket_buyer_name_element', $buyer_name, $order->details->ID ) );

            } else {
                return '<br/>' . wp_kses_post( apply_filters( 'tc_ticket_buyer_name_element_default', __( 'John Smith', 'tickera-event-ticketing-system' ) ) );
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_ticket_buyer_name_element', __( 'Ticket Buyer Name', 'tickera-event-ticketing-system' ) );
}