<?php

namespace Tickera\Ticket\Element;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Ticket\Element\tc_ticket_qr_code_element' ) ) {

    class tc_ticket_qr_code_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_ticket_qr_code_element';
        var $element_title = 'QR Code';
        var $font_awesome_icon = '<i class="fa fa-qrcode"></i>';

        function on_creation() {
            $this->element_title = apply_filters( 'tc_ticket_qr_code_element_title', __( 'QR Code', 'tickera-event-ticketing-system' ) );
        }

        function admin_content() {
            ob_start();
            $this->get_qr_code_size();
            parent::get_cell_alignment();
            parent::get_element_margins();
            return ob_get_clean();
        }

        function admin_content_v2($element_default_values = false) {
            ob_start();
            $this->get_qr_code_size_new($element_default_values[$this->element_name.'_qr_code_size']);
            $this->get_qr_code_padding_code($element_default_values[$this->element_name.'_qr_code_padding_code']);
            parent::get_cell_alignment($element_default_values[$this->element_name.'_cell_alignment']);
            parent::get_element_margins($element_default_values[$this->element_name.'_top_padding'], $element_default_values[$this->element_name.'_bottom_padding']);
            return ob_get_clean();
        }

        /**
         * Deprecated method
         */
        function get_qr_code_size() {
            ?>
            <label><?php esc_html_e( 'QR Code Size', 'tickera-event-ticketing-system' ); ?>
                <input class="ticket_element_padding" type="text" name="<?php echo esc_attr( $this->element_name ); ?>_qr_code_size_post_meta" value="<?php echo esc_attr( isset( $this->template_metas[ $this->element_name . '_qr_code_size' ] ) ? $this->template_metas[ $this->element_name . '_qr_code_size' ] : '50' ); ?>"/>
            </label>
            <?php
        }

        function get_qr_code_size_new($qr_code_size = '50') {
            ?>
            <label><?php esc_html_e( 'QR Code Size', 'tickera-event-ticketing-system' ); ?>
                <input class="ticket_element_padding" type="text" name="<?php echo esc_attr( $this->element_name ); ?>_qr_code_size_post_meta" value="<?php echo esc_attr( $qr_code_size ); ?>"/>
            </label>
            <?php
        }

        function get_qr_code_padding_code($padding = '1') {
            ?>
            <label><?php esc_html_e( 'QR Code Padding', 'tickera-event-ticketing-system' ); ?>
                <input class="ticket_element_padding_code" type="text" name="<?php echo esc_attr( $this->element_name ); ?>_qr_code_padding_code_post_meta" value="<?php echo esc_attr( $padding ); ?>"/>
            </label>
            <?php
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            global $tc, $pdf;

            if ( $ticket_instance_id ) {

                $ticket_instance = new \Tickera\TC_Ticket_Instance( (int) $ticket_instance_id );
                $order = new \Tickera\TC_Order( $ticket_instance->details->post_parent );

                if ( apply_filters( 'tc_qr_code_quick_scan_info', true ) ) {
                    $qrstring = apply_filters( 'tc_qr_code_info', $ticket_instance->details->ticket_code, $ticket_instance, $order );

                } else {
                    $qrstring = apply_filters( 'tc_qr_code_info', 'id|' . $ticket_instance_id . '|name|' . $ticket_instance->details->first_name . ' ' . $ticket_instance->details->last_name . '|city|' . ( $ticket_instance->details->city ? $ticket_instance->details->city : '' ) . '|address|' . ( $ticket_instance->details->address ? $ticket_instance->details->address : '' ) . '|country|' . ( $ticket_instance->details->country ? $ticket_instance->details->country : '' ) . '|state|' . ( $ticket_instance->details->state ? $ticket_instance->details->state : '' ) . '|payment_date|' . $order->details->post_date . '|checksum|' . $ticket_instance->details->ticket_code, $ticket_instance, $order );
                }
            }

            $cell_alignment = $this->template_metas[ $this->element_name . '_cell_alignment' ];
            $code_size = $this->template_metas[ $this->element_name . '_qr_code_size' ];

            if ( isset( $cell_alignment ) && $cell_alignment == 'right' ) {
                $cell_alignment = 'R';

            } elseif ( isset( $cell_alignment ) && $cell_alignment == 'left' ) {
                $cell_alignment = 'L';

            } elseif ( isset( $cell_alignment ) && $cell_alignment == 'center' ) {
                $cell_alignment = 'N';

            } else {
                $cell_alignment = 'N'; // Default alignment
            }

            $style = array(
                'position' => apply_filters( 'tc_qr_code_cell_alignment', $cell_alignment ),
                'border' => apply_filters( 'tc_show_qr_code_border', true ),
                'padding' => apply_filters( 'tc_qr_code_padding', 1 ),
                'fgcolor' => tickera_hex2rgb( apply_filters( 'tc_qr_code_fg_color', '#000000' ) ),
                'bgcolor' => tickera_hex2rgb( apply_filters( 'tc_qr_code_bg_color', '#FFFFFF' ) ),
            );

            $params_array = array(
                isset( $qrstring ) ? apply_filters( 'tc_qr_string', $qrstring ) : $tc->create_unique_id(),
                'QRCODE,H',
                '',
                '',
                $code_size,
                $code_size,
                $style,
                'N'
            );

            $params_array = apply_filters( 'tc_2d_code_params', $params_array, isset( $qrstring ) ? apply_filters( 'tc_qr_string', $qrstring ) : $tc->create_unique_id(), 'QRCODE,H', '', '', $code_size, $code_size, $style, 'N' );
            $pars = $pdf->serializeTCPDFtagParameters( $params_array );

            return '<div><tcpdf method="write2DBarcode" params="' . esc_attr( $pars ) . '" /></div>';
        }

        function ticket_content_v2( $element_default_values = false, $ticket_instance_id = false, $ticket_type_id = false ) {

            global $tc, $pdf;

            if ( $ticket_instance_id ) {

                $ticket_instance = new \Tickera\TC_Ticket_Instance( (int) $ticket_instance_id );
                $order = new \Tickera\TC_Order( $ticket_instance->details->post_parent );

                if ( apply_filters( 'tc_qr_code_quick_scan_info', true ) ) {
                    $qrstring = apply_filters( 'tc_qr_code_info', $ticket_instance->details->ticket_code, $ticket_instance, $order );

                } else {
                    $qrstring = apply_filters( 'tc_qr_code_info', 'id|' . $ticket_instance_id . '|name|' . $ticket_instance->details->first_name . ' ' . $ticket_instance->details->last_name . '|city|' . ( $ticket_instance->details->city ? $ticket_instance->details->city : '' ) . '|address|' . ( $ticket_instance->details->address ? $ticket_instance->details->address : '' ) . '|country|' . ( $ticket_instance->details->country ? $ticket_instance->details->country : '' ) . '|state|' . ( $ticket_instance->details->state ? $ticket_instance->details->state : '' ) . '|payment_date|' . $order->details->post_date . '|checksum|' . $ticket_instance->details->ticket_code, $ticket_instance, $order );
                }
            }

            $cell_alignment = $element_default_values[ $this->element_name . '_cell_alignment' ];
            $code_size = $element_default_values[ $this->element_name . '_qr_code_size' ];
            $code_padding = isset($element_default_values[ $this->element_name . '_qr_code_padding_code' ]) ? $element_default_values[ $this->element_name . '_qr_code_padding_code' ] : 1;

            if ( isset( $cell_alignment ) && $cell_alignment == 'right' ) {
                $cell_alignment = 'R';

            } elseif ( isset( $cell_alignment ) && $cell_alignment == 'left' ) {
                $cell_alignment = 'L';

            } elseif ( isset( $cell_alignment ) && $cell_alignment == 'center' ) {
                $cell_alignment = 'N';

            } else {
                $cell_alignment = 'N'; // Default alignment
            }

            $style = array(
                'position' => apply_filters( 'tc_qr_code_cell_alignment', $cell_alignment ),
                'border' => apply_filters( 'tc_show_qr_code_border', true ),
                'padding' => apply_filters( 'tc_qr_code_padding', $code_padding ),
                'fgcolor' => tickera_hex2rgb( apply_filters( 'tc_qr_code_fg_color', '#000000' ) ),
                'bgcolor' => tickera_hex2rgb( apply_filters( 'tc_qr_code_bg_color', '#FFFFFF' ) ),

            );

            $params_array = array(
                isset( $qrstring ) ? apply_filters( 'tc_qr_string', $qrstring ) : $tc->create_unique_id(),
                'QRCODE,H',
                '',
                '',
                $code_size,
                $code_size,
                $style,
                'N',

            );

            $params_array = apply_filters( 'tc_2d_code_params', $params_array, isset( $qrstring ) ? apply_filters( 'tc_qr_string', $qrstring ) : $tc->create_unique_id(), 'QRCODE,H', '', '', $code_size, $code_size, $style, 'N' );
            $pars = $pdf->serializeTCPDFtagParameters( $params_array );

            return '<div><tcpdf method="write2DBarcode" params="' . esc_attr( $pars ) . '" /></div>';
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_ticket_qr_code_element', __( 'QR Code', 'tickera-event-ticketing-system' ) );
}