<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_sponsors_logos_element' ) ) {

    class tc_sponsors_logos_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_sponsors_logos_element';
        var $element_title = 'Sponsors Logos';
        var $font_awesome_icon = '<span class="tti-announcement_megaphone_promotion_speaker_icon-1"></span>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_sponsors_logos_element_title', __( 'Sponsors Logos', 'tickera-event-ticketing-system' ) );
        }

        function admin_content() {
            ob_start();
            parent::get_cell_alignment();
            parent::get_element_margins();
            return ob_get_clean();
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket( (int) $ticket_instance_id );
                $ticket = new \Tickera\TC_Ticket();
                $event_id = $ticket->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );
                $sponsors_logo = apply_filters( 'tc_sponsors_logos_element', get_post_meta( $event_id, 'sponsors_logo_file_url', true ) );

                if ( $sponsors_logo ) {
                    return wp_kses_post( '<br/><img src="' . esc_url( tickera_ticket_template_image_url( $sponsors_logo ) ) . '" />' );

                } else {
                    return wp_kses_post( '<br/>' );
                }

            } else {

                if ( $ticket_type_id ) {
                    $ticket_type = new \Tickera\TC_Ticket( (int) $ticket_type_id );
                    $event_id = $ticket_type->get_ticket_event( $ticket_type_id );
                    $event = new \Tickera\TC_Event( $event_id );
                    return '<br/>' . wp_kses_post( apply_filters( 'tc_sponsors_logos_element', '<img src="' . esc_url( $event->details->sponsors_logo_file_url ) . '" />' ) );

                } else {
                    return '<br/>' . wp_kses_post( apply_filters( 'tc_sponsors_logos_element_default', esc_html__( 'Sponsor Logos', 'tickera-event-ticketing-system' ) ) );
                }
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_sponsors_logos_element', __( 'Sponsor Logos', 'tickera-event-ticketing-system' ) );
}