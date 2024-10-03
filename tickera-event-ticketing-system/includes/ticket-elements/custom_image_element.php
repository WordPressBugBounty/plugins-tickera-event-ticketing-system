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

        function admin_content() {
            ob_start();
            parent::get_cell_alignment();
            parent::get_element_margins();
            $this->get_custom_image_file_name();
            return ob_get_clean();
        }

        function get_custom_image_file_name() { ?>
            <label><?php esc_html_e( 'Custom Image / Logo URL', 'tickera-event-ticketing-system' ); ?></label>
            <div class="file_url_holder">
                <label>
                    <input class="file_url" type="text" size="36" name="<?php echo esc_attr( $this->element_name ); ?>_custom_image_url_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_custom_image_url' ] ) ? $this->template_metas[ $this->element_name . '_custom_image_url' ] : '' ); ?>"/>
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
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_custom_image_element', __( 'Custom Image / Logo', 'tickera-event-ticketing-system' ) );
}