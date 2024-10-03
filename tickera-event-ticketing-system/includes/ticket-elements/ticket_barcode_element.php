<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_ticket_barcode_element_core' ) ) {

    class tc_ticket_barcode_element_core extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_ticket_barcode_element_core';
        var $element_title = 'Barcode';
        var $font_awesome_icon = '<span class="tti-barcode_e-commerce_scanning_shopping_icon"></span>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_ticket_barcode_element_title', __( 'Barcode', 'tickera-event-ticketing-system' ) );
        }

        function admin_content() {
            ob_start();
            $this->get_1d_barcode_types();
            $this->get_1d_barcode_text_visibility();
            $this->get_1d_barcode_size();
            parent::get_font_sizes( 'Barcode Text Font Size (if visible)', 8 );
            parent::get_cell_alignment();
            parent::get_element_margins();
            return ob_get_clean();
        }

        function get_1d_barcode_size() {
            ?>
            <label><?php esc_html_e( 'Barcode Size', 'tickera-event-ticketing-system' ); ?>
                <input class="ticket_element_padding" type="text" name="<?php echo esc_attr( $this->element_name ); ?>_1d_barcode_size_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_1d_barcode_size' ] ) ? $this->template_metas[ $this->element_name . '_1d_barcode_size' ] : '50' ); ?>"/>
            </label>
            <?php
        }

        function get_1d_barcode_text_visibility() {
            $text_visibility = ( isset( $this->template_metas[ $this->element_name . '_barcode_text_visibility' ] ) ? $this->template_metas[ $this->element_name . '_barcode_text_visibility' ] : 'visible' );
            ?>
            <label><?php esc_html_e( 'Barcode Text Visibility', 'tickera-event-ticketing-system' ); ?></label>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_barcode_text_visibility_post_meta">
                <option value="visible" <?php selected( $text_visibility, 'visible', true ); ?>><?php esc_html_e( 'Visible', 'tickera-event-ticketing-system' ); ?></option>
                <option value="invisible" <?php selected( $text_visibility, 'invisible', true ); ?>><?php esc_html_e( 'Invisible', 'tickera-event-ticketing-system' ); ?></option>
            </select>
            <?php
        }

        function get_1d_barcode_types() {
            ?>
            <label><?php esc_html_e( 'Barcode Type', 'tickera-event-ticketing-system' ); ?></label>
            <?php $barcode_type = isset( $this->template_metas[ $this->element_name . '_barcode_type' ] ) ? $this->template_metas[ $this->element_name . '_barcode_type' ] : 'C39'; ?>
            <select name="<?php echo esc_attr( $this->element_name ); ?>_barcode_type_post_meta">
                <option value="C39" <?php selected( $barcode_type, 'C39', true ); ?>><?php esc_html_e( 'C39', 'tickera-event-ticketing-system' ); ?></option>
                <option value="C39E" <?php selected( $barcode_type, 'C39E', true ); ?>><?php esc_html_e( 'C39E', 'tickera-event-ticketing-system' ); ?></option>
                <option value="C93" <?php selected( $barcode_type, 'C93', true ); ?>><?php esc_html_e( 'C93', 'tickera-event-ticketing-system' ); ?></option>
                <option value="C128" <?php selected( $barcode_type, 'C128', true ); ?>><?php esc_html_e( 'C128', 'tickera-event-ticketing-system' ); ?></option>
                <option value="C128A" <?php selected( $barcode_type, 'C128A', true ); ?>><?php esc_html_e( 'C128A', 'tickera-event-ticketing-system' ); ?></option>
                <option value="C128B" <?php selected( $barcode_type, 'C128B', true ); ?>><?php esc_html_e( 'C128B', 'tickera-event-ticketing-system' ); ?></option>
                <option value="EAN2" <?php selected( $barcode_type, 'EAN2', true ); ?>><?php esc_html_e( 'EAN2', 'tickera-event-ticketing-system' ); ?></option>
                <option value="EAN5" <?php selected( $barcode_type, 'EAN5', true ); ?>><?php esc_html_e( 'EAN5', 'tickera-event-ticketing-system' ); ?></option>
                <option value="EAN13" <?php selected( $barcode_type, 'EAN13', true ); ?>><?php esc_html_e( 'EAN-13', 'tickera-event-ticketing-system' ); ?></option>
                <option value="UPCA" <?php selected( $barcode_type, 'UPCA', true ); ?>><?php esc_html_e( 'UPCA', 'tickera-event-ticketing-system' ); ?></option>
                <option value="UPCE" <?php selected( $barcode_type, 'UPCE', true ); ?>><?php esc_html_e( 'UPCE', 'tickera-event-ticketing-system' ); ?></option>
                <option value="MSI" <?php selected( $barcode_type, 'MSI', true ); ?>><?php esc_html_e( 'MSI', 'tickera-event-ticketing-system' ); ?></option>
                <option value="MSI+" <?php selected( $barcode_type, 'MSI+', true ); ?>><?php esc_html_e( 'MSI+', 'tickera-event-ticketing-system' ); ?></option>
                <option value="RMS4CC" <?php selected( $barcode_type, 'RMS4CC', true ); ?>><?php esc_html_e( 'RMS4CC', 'tickera-event-ticketing-system' ); ?></option>
                <option value="IMB" <?php selected( $barcode_type, 'IMB', true ); ?>><?php esc_html_e( 'IMB', 'tickera-event-ticketing-system' ); ?></option>
                <?php do_action( 'tc_ticket_barcode_element_after_types_options', $barcode_type ); ?>
            </select>
            <span class="description"><?php echo wp_kses_post( __( 'Following Barcode types are supported by the iOS check-in app: EAN-13, UPCA, C93, C128 </br><hr><strong>IMPORTANT:</strong> EAN-13 barcode type supports numeric characters only!</br>If you intend on using this barcode type, you must utilize <strong><a href="https://tickera.com/addons/serial-ticket-codes/">Serial Ticket Codes</a></strong> add-on and set ticket codes with maximum of 12 characters, without prefix and suffix.</br>For more information, please read <strong><a href="https://tickera.com/tickera-documentation/barcode-reader/">documentation</a></strong> on Barcode Reader add-on and always test ticket scanning prior going live with ticket sales.<hr>', 'tickera-event-ticketing-system' ) ); ?></span>
            <?php
        }

        function ticket_content( $ticket_instance_id = false ) {

            global $tc, $pdf;
            $cell_alignment = isset( $this->template_metas[ $this->element_name . '_cell_alignment' ] ) ? $this->template_metas[ $this->element_name . '_cell_alignment' ] : 'N';

            switch ( $cell_alignment ) {

                case 'right':
                    $cell_alignment = 'R';
                    break;

                case 'left':
                    $cell_alignment = 'L';
                    break;

                case 'center':
                    $cell_alignment = 'N';
                    break;
            }

            $type = isset( $this->template_metas[ $this->element_name . '_barcode_type' ] ) ? $this->template_metas[ $this->element_name . '_barcode_type' ] : 'C39';

            switch( $type ) {

                case 'EAN5':
                    $preview = '50020';
                    break;

                case 'EAN2':
                    $preview = '25';
                    break;

                case 'EAN13':
                    $preview = '5012345678900';
                    break;

                default:
                    $preview = $tc->create_unique_id();
            }

            $text_visibility = ( isset( $this->template_metas[ $this->element_name . '_barcode_text_visibility' ] ) ? $this->template_metas[ $this->element_name . '_barcode_text_visibility' ] : 'visible' );
            $text_visibility = ( 'visible' == $text_visibility ) ? true : false;
            $ticket_instance = $ticket_instance_id ? new \Tickera\TC_Ticket_Instance( $ticket_instance_id ) : [];
            $ticket_code = ( $ticket_instance ) ? $ticket_instance->details->ticket_code : $preview;

            $barcode_params = $pdf->serializeTCPDFtagParameters([
                $ticket_code,
                ( isset( $this->template_metas[ $this->element_name . '_barcode_type' ] ) ? $this->template_metas[ $this->element_name . '_barcode_type' ] : 'C128' ), // Type
                apply_filters( 'tc_barcode_element_x', '' ), // X
                apply_filters( 'tc_barcode_element_y', '' ), // Y
                isset( $this->template_metas[ $this->element_name . '_1d_barcode_size' ] ) ? $this->template_metas[ $this->element_name . '_1d_barcode_size' ] : 50, // W
                apply_filters( 'tc_barcode_element_h', 0 ), // H
                apply_filters( 'tc_barcode_element_xres', 0.4 ), // Xres
                [
                    'position' => apply_filters( 'tc_barcode_element_cell_alignment', $cell_alignment ),
                    'border' => apply_filters( 'tc_show_barcode_border', true ),
                    'padding' => apply_filters( 'tc_barcode_padding', 2 ),
                    'fgcolor' => tickera_hex2rgb( '#000000' ), // Black (don't change it or won't be readable by the barcode reader)
                    'bgcolor' => tickera_hex2rgb( '#ffffff' ), // White (don't change it or won't be readable by the barcode reader)
                    'text' => $text_visibility,
                    'font' => apply_filters( 'tc_1d_barcode_font', 'helvetica' ),
                    'fontsize' => isset( $this->template_metas[ $this->element_name . '_font_size' ] ) ? $this->template_metas[ $this->element_name . '_font_size' ] : 8,
                    'cellfitalign' => apply_filters( 'tc_barcode_element_cellfitalign', true ),
                    'stretchtext' => apply_filters( 'tc_barcode_element_stretchtext', 0 ),
                    'label' => apply_filters( 'tc_barcode_element_label', $ticket_code, $ticket_instance )
                ],
                'N'
            ]);

            return '<div><tcpdf method="write1DBarcode" params="' . esc_attr( apply_filters( 'tc_barcode_element_params', $barcode_params ) ) . '" /></div>';
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_ticket_barcode_element_core', __( 'Barcode', 'tickera-event-ticketing-system' ) );
}