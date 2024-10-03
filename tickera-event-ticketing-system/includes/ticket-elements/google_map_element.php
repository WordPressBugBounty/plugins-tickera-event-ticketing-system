<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_google_map_element' ) ) {

    class tc_google_map_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_google_map_element';
        var $element_title = 'Google Map';
        var $font_awesome_icon = '<i class="fa fa-map-marker"></i>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_google_map_element_title', __( 'Google Map', 'tickera-event-ticketing-system' ) );
        }

        function admin_content() {
            ob_start();
            parent::get_cell_alignment();
            parent::get_element_margins();
            $this->get_google_settings();
            return ob_get_clean();
        }

        function get_google_settings() { ?>
            <label><?php esc_html_e( 'Address or Coordinates', 'tickera-event-ticketing-system' ); ?></label>
            <input type="text" name="<?php echo esc_attr( $this->element_name ); ?>_google_map_address_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_google_map_address' ] ) ? $this->template_metas[ $this->element_name . '_google_map_address' ] : '' ); ?>"/>
            <span class="description"><?php esc_html_e( 'For instance: Grosvenor Square, Mayfair, London or 51.5122468,-0.1517072', 'tickera-event-ticketing-system' ) ?></span>
            <label><?php esc_html_e( 'Map Size', 'tickera-event-ticketing-system' ); ?></label>
            <?php esc_html_e( 'Width (px)', 'tickera-event-ticketing-system' ); ?> <input class="ticket_element_padding" type="text" name="<?php echo esc_attr( $this->element_name ); ?>_google_map_width_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_google_map_width' ] ) ? $this->template_metas[ $this->element_name . '_google_map_width' ] : 600 ); ?>"/>
            <?php esc_html_e( 'Height (px)', 'tickera-event-ticketing-system' ); ?> <input class="ticket_element_padding" type="text" name="<?php echo esc_attr( $this->element_name ); ?>_google_map_height_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_google_map_height' ] ) ? $this->template_metas[ $this->element_name . '_google_map_height' ] : 300 ); ?>"/>
            <label><?php esc_html_e( 'Zoom Level', 'tickera-event-ticketing-system' ); ?></label>
            <?php $selected_zoom = isset( $this->template_metas[ $this->element_name . '_google_map_zoom' ] ) ? $this->template_metas[ $this->element_name . '_google_map_zoom' ] : '13'; ?>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_google_map_zoom_post_meta">
                <?php for ( $i = apply_filters( 'tc_google_map_element_minimum_zoom_level', 10 ); $i <= 22; $i++ ) { ?>
                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $selected_zoom, $i, true ); ?>><?php echo esc_html( $i ); ?></option>
                <?php } ?>
            </select>
            <label><?php esc_html_e( 'Map Type', 'tickera-event-ticketing-system' ); ?></label>
            <?php $selected_map_type = isset( $this->template_metas[ $this->element_name . '_google_map_type' ] ) ? $this->template_metas[ $this->element_name . '_google_map_type' ] : 'roadmap'; ?>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_google_map_type_post_meta">
                <option value="roadmap" <?php selected( $selected_map_type, 'roadmap', true ); ?>><?php esc_html_e( 'Roadmap', 'tickera-event-ticketing-system' ); ?></option>
                <option value="terrain" <?php selected( $selected_map_type, 'terrain', true ); ?>><?php esc_html_e( 'Terrain', 'tickera-event-ticketing-system' ); ?></option>
                <option value="satellite" <?php selected( $selected_map_type, 'satellite', true ); ?>><?php esc_html_e( 'Satellite', 'tickera-event-ticketing-system' ); ?></option>
                <option value="hybrid" <?php selected( $selected_map_type, 'hybrid', true ); ?>><?php esc_html_e( 'Hybrid', 'tickera-event-ticketing-system' ); ?></option>
            </select>
            <?php
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $google_maps_api_key = isset( $tc_general_settings[ 'google_maps_api_key' ] ) && ! empty( $tc_general_settings[ 'google_maps_api_key' ] ) ? $tc_general_settings[ 'google_maps_api_key' ] : '';

            if ( ! empty( $google_maps_api_key ) ) {

                $address = isset( $this->template_metas[ $this->element_name . '_google_map_address' ] ) ? $this->template_metas[ $this->element_name . '_google_map_address' ] : '';
                $width = isset( $this->template_metas[ $this->element_name . '_google_map_width' ] ) ? $this->template_metas[ $this->element_name . '_google_map_width' ] : '600';
                $height = isset( $this->template_metas[ $this->element_name . '_google_map_height' ] ) ? $this->template_metas[ $this->element_name . '_google_map_height' ] : '300';
                $zoom = isset( $this->template_metas[ $this->element_name . '_google_map_zoom' ] ) ? $this->template_metas[ $this->element_name . '_google_map_zoom' ] : '13';
                $map_type = isset( $this->template_metas[ $this->element_name . '_google_map_type' ] ) ? $this->template_metas[ $this->element_name . '_google_map_type' ] : 'roadmap';
                $google_map_url = 'http://maps.googleapis.com/maps/api/staticmap?center=' . urlencode( $address ) . '&zoom=' . $zoom . '&scale=2&size=' . $width . 'x' . $height . '&maptype=' . $map_type . '&format=jpg&visual_refresh=false&markers=size:mid%7Ccolor:' . apply_filters( 'tc_google_map_element_marker_color', '0xff0000' ) . '%7Clabel:1%7C' . urlencode( $address ) . '&key=' . $google_maps_api_key;
                return '<br/>' . wp_kses_post( apply_filters( 'tc_google_map_image_element', '<img width="' . esc_attr( $width ) . '" src="' . esc_url( $google_map_url ) . '">' ) );

            } else {

                if ( current_user_can( 'manage_options' ) ) { // Show the message only to the administrator(s)
                    return '<br/>' . esc_html__( 'NOTE: Please set your Google Maps API Key in the Settings > General > Miscellaneous > Google Maps API Key', 'tickera-event-ticketing-system' );

                } else {
                    return '<br/>';
                }
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_google_map_element', __( 'Google Map', 'tickera-event-ticketing-system' ) );
}