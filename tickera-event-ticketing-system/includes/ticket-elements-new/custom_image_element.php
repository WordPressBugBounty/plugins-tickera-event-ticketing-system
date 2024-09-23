<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_custom_image_element' ) ) {

    class tc_custom_image_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_custom_image_element';
        var $element_title = 'Custom Image / Logo';
        var $font_awesome_icon = '<span class="tti-image_photograph_picture_icon"></span>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_custom_image_element_title', __( 'Custom Image / Logo', 'tickera-event-ticketing-system' ) );
        }

        function admin_content_v2($element_default_values = false) {
            parent::get_cell_alignment($element_default_values[$this->element_name.'_cell_alignment']);
            parent::get_element_margins($element_default_values[$this->element_name.'_top_padding'], $element_default_values[$this->element_name.'_bottom_padding']);
            $this->get_custom_image_file_name($element_default_values[$this->element_name.'_custom_image_url']);
        }

        function get_custom_image_file_name($custom_image_url = '') { ?>
            <label><?php esc_html_e( 'Custom Image / Logo URL', 'tickera-event-ticketing-system' ); ?></label>
            <div class="file_url_holder">
                <label>
                    <input class="file_url" type="text" size="36" name="<?php echo esc_attr( $this->element_name ); ?>_custom_image_url_post_meta" value="<?php echo esc_attr( $custom_image_url ); ?>"/>
                    <input class="file_url_button button-secondary" type="button" value="<?php esc_html_e( 'Browse', 'tickera-event-ticketing-system' ); ?>"/>
                    <span class="description"></span>
                </label>
            </div>
            <?php
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {
            $image_url = isset( $this->template_metas[ $this->element_name . '_custom_image_url' ] ) ? $this->template_metas[ $this->element_name . '_custom_image_url' ] : '';
            return '<br/>' . apply_filters( 'tc_custom_image_element', '<img src="' . esc_url( tickera_ticket_template_image_url( $image_url ) ) . '" />' );
        }

        function ticket_content_v2( $element_default_values = false, $ticket_instance_id = false, $ticket_type_id = false ) {
            $image_url = isset( $element_default_values[ $this->element_name . '_custom_image_url' ] ) ? $element_default_values[ $this->element_name . '_custom_image_url' ] : '';
            return '<br/>' . apply_filters( 'tc_custom_image_element', '<img src="' . esc_url( tickera_ticket_template_image_url( $image_url ) ) . '" />' );
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_custom_image_element', __( 'Custom Image / Logo', 'tickera-event-ticketing-system' ) );
}