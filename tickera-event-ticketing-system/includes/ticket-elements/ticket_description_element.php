<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\Ticket\Element\tc_ticket_description_element' ) ) {

    class tc_ticket_description_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_ticket_description_element';
        var $element_title = 'Ticket Description';
        var $font_awesome_icon = '<i class="fa fa-file-text-o"></i>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_ticket_description_element_title', __( 'Ticket Description', 'tickera-event-ticketing-system' ) );
        }

        function admin_content() {
            ob_start();
            $this->get_font_sizes();
            $this->get_font_style();
            $this->get_font_colors();
            $this->get_cell_alignment();
            $this->new_line_field();
            $this->get_element_margins();
            return ob_get_clean();
        }

        function new_line_field() { ?>
            <label><?php esc_html_e( 'Enable line breaks', 'tickera-event-ticketing-system' ); ?></label>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_enable_line_breaks_post_meta">
                <option value="no" <?php selected( isset( $this->template_metas[ $this->element_name . '_enable_line_breaks' ] ) ? $this->template_metas[ $this->element_name . '_enable_line_breaks' ] : 'no', 'no', true ); ?>><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?></option>
                <option value="yes" <?php selected( isset( $this->template_metas[ $this->element_name . '_enable_line_breaks' ] ) ? $this->template_metas[ $this->element_name . '_enable_line_breaks' ] : 'no', 'yes', true ); ?>><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?></option>
            </select>
            <?php
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket( (int) $ticket_instance_id );
                $ticket = new \Tickera\TC_Ticket( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );

                $line_breaks = isset( $this->template_metas[ $this->element_name . '_enable_line_breaks' ] ) ? $this->template_metas[ $this->element_name . '_enable_line_breaks' ] : 'no';
                return apply_filters( 'tc_ticket_description_element', ( ( 'yes' == $line_breaks ) ? tickera_the_content( $ticket->details->post_content ) : $ticket->details->post_content ), $ticket_instance );

            } else {

                if ( $ticket_type_id ) {
                    $ticket_type = new \Tickera\TC_Ticket( (int) $ticket_type_id );
                    return apply_filters( 'tc_ticket_description_element', apply_filters( 'tc_the_content', $ticket_type->details->post_content ) );

                } else {
                    return apply_filters( 'tc_ticket_description_element_default', '<ul>
				<li>AGES 21+ (with valid state-issued photo ID)</li>
				<li>Includes transportation via Ferry or Shuttle Bus (you choose during purchase process)</li>
				<li>Express Festival Entry</li>
				<li>VIP Lounge Access with plush furniture, premium food and cash bar</li>
				</ul>' );
                }
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_ticket_description_element', __( 'Ticket Description', 'tickera-event-ticketing-system' ) );
}