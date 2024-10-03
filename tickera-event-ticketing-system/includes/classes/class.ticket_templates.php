<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Ticket_Templates' ) ) {

    class TC_Ticket_Templates {

        var $form_title = '';
        var $valid_admin_fields_type = array( 'text', 'textarea', 'checkbox', 'function' );

        function __construct() {

            /**
             * If true allows to call TCPDF methods using HTML syntax
             * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
             */
            if ( ! defined( 'TC_K_TCPDF_CALLS_IN_HTML' ) )
                define( 'TC_K_TCPDF_CALLS_IN_HTML', true );

            $this->valid_admin_fields_type = apply_filters( 'tc_valid_admin_fields_type', $this->valid_admin_fields_type );
        }

        /**
         * Generate the pdf file
         *
         * @param bool $ticket_instance_id
         * @param bool $force_download
         * @param bool $template_id
         * @param bool $ticket_type_id
         * @param bool $string_attachment
         * @return string
         */
        function generate_preview( $ticket_instance_id = false, $force_download = false, $template_id = false, $ticket_type_id = false, $string_attachment = false ) {

            global $tc, $pdf;

            // Trying to set a memory limit to a high value since some template might need more memory (when a huge background is set, etc)
            @ini_set( 'memory_limit', '1024M' );

            // Display all errors if TC_DEBUG is true
            if ( defined( 'TC_DEBUG' ) || isset( $_GET[ 'TC_DEBUG' ] ) ) {
                error_reporting( E_ALL );
                @ini_set( 'display_errors', 'On' );
            }

            // Initialize TCPDF Libraries
            if ( ! class_exists( 'Tickera\TCPDF' ) ) {
                require_once( $tc->plugin_dir . 'includes/tcpdf/examples/tcpdf_include.php' );
            }

            ob_start();
            $output_buffering = ini_get( 'output_buffering' );
            if ( isset( $output_buffering ) && $output_buffering > 0 ) {
                if ( ! ob_get_level() ) {
                    ob_end_clean();
                    ob_start();
                }
            }

            $post_id = $template_id;

            if ( $ticket_instance_id ) {

                $ticket_instance_status = get_post_status( $ticket_instance_id );
                if ( 'publish' == $ticket_instance_status ) {

                    $ticket_instance = new \Tickera\TC_Ticket( $ticket_instance_id );
                    $pdf_filename = apply_filters( 'tc_pdf_ticket_name', $ticket_instance->details->ticket_code, $ticket_instance ) . '.pdf';

                    // Tickera Standalone
                    $ticket_template = get_post_meta( $ticket_instance->details->ticket_type_id, 'ticket_template', true );

                    // Tickera alongside Bridge for Woocommerce
                    $ticket_template = ( ! $ticket_template ) ? get_post_meta( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ), apply_filters( 'tc_ticket_template_field_name', '_ticket_template' ), true ) : $ticket_template;

                    // template_id specified in frontend url
                    $ticket_template = ( $template_id ) ? $template_id : $ticket_template;

                    $post_id = $ticket_template;

                } else {
                    esc_html_e( 'Something went wrong. Ticket does not exists.', 'tickera-event-ticketing-system' );
                    exit;
                }

            } else {
                $pdf_filename = __( 'preview', 'tickera-event-ticketing-system' );
            }

            // Retrieve template's post metas
            $metas = ( $post_id ) ? tickera_get_post_meta_all( $post_id ) : [];
            $tc_document_orientation = ( $metas ) ? $metas[ 'document_ticket_orientation' ] : 'P';

            // For custom size, document_ticket_size value should be formatted in array.
            $tc_document_paper_size = ( $metas ) ? apply_filters( 'tc_document_paper_size', $metas[ 'document_ticket_size' ] ): 'A4';
            $tc_document_paper_size = ( is_array( $tc_document_paper_size ) ) ? array_map( 'intval', array_values( array_filter( $tc_document_paper_size ) ) ) : $tc_document_paper_size;

            // Background Data
            $background = [
                'image' => isset( $metas[ 'document_ticket_background_image' ] ) ? tickera_ticket_template_image_url( $metas[ 'document_ticket_background_image' ] ) : '',
                'size' => $tc_document_paper_size
            ];

            // Create new PDF document
            $pdf = new \Tickera\TCPDF_EXT( $tc_document_orientation, TC_PDF_UNIT, apply_filters( 'tc_additional_ticket_document_size_output', $tc_document_paper_size ), true, apply_filters( 'tc_ticket_document_encoding', 'UTF-8' ), false, false, $background );

            // Set TCPDF Defaults
            $pdf->SetCompression( true );
            $pdf->setPrintHeader( true );
            $pdf->setPrintFooter( false );

            // Set Font & Margins
            if ( $metas ) {

                $font = ( $metas[ 'document_font' ] ) ? $metas[ 'document_font' ] : 'helvetica';

                /**
                 * Retrieve custom font subset.
                 * @since 3.4.9.6
                 */
                $font_subset = ( isset( $metas[ 'document_font_subset' ] ) ) ? $metas[ 'document_font_subset' ] : 'default';

                $margin_left = ( $metas[ 'document_ticket_left_margin' ] ) ? $metas[ 'document_ticket_left_margin' ] : 1;
                $margin_top = ( $metas[ 'document_ticket_top_margin' ] ) ? $metas[ 'document_ticket_top_margin' ] : 0;
                $margin_right = ( $metas[ 'document_ticket_right_margin' ] ) ? $metas[ 'document_ticket_right_margin' ] : 0;

                $pdf->SetFont( $font, '', 14, '', $font_subset );
                $pdf->SetMargins( $margin_left, $margin_top, $margin_right );
            }

            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $ticket_template_auto_pagebreak = ( isset( $tc_general_settings[ 'ticket_template_auto_pagebreak' ] )
                && 'yes' == $tc_general_settings[ 'ticket_template_auto_pagebreak' ] ) ? true : false;

            $pdf->SetAutoPageBreak( false, 0 );
            $pdf->setJPEGQuality( 100 );
            $pdf->AddPage();
            $pdf->SetAutoPageBreak( $ticket_template_auto_pagebreak, TC_PDF_MARGIN_BOTTOM );

            $col_1 = 'width: 100%;';
            $col_1_width = '100%';
            $col_2 = 'width: ' . ( 100/2 ) . '%;';
            $col_2_width = ( 100/2 ) . '%';
            $col_3 = 'width: ' . ( 100/3 ) . '%;';
            $col_3_width = ( 100/3 ) . '%';
            $col_4 = 'width: ' . ( 100/4 ) . '%;';
            $col_5 = 'width: ' . ( 100/5 ) . '%;';
            $col_6 = 'width: ' . ( 100/6 ) . '%;';
            $col_7 = 'width: ' . ( 100/7 ) . '%;';
            $col_8 = 'width: ' . ( 100/8 ) . '%;';
            $col_9 = 'width: ' . ( 100/9 ) . '%;';
            $col_10 = 'width: ' . ( 100/10 ) . '%;';

            $rows = '<table style="width: 100%">';

            for ( $i = 1; $i <= apply_filters( 'tc_ticket_template_row_number', 10 ); $i++ ) {

                $rows .= '<tr style="display: table; width: 100%;">';
                $rows_elements = get_post_meta( $post_id, 'rows_' . $i, true );

                if ( isset( $rows_elements ) && $rows_elements !== '' ) {

                    $element_class_names = explode( ',', $rows_elements );
                    $rows_count = count( $element_class_names );

                    foreach ( $element_class_names as $element_class_name ) {

                        $element_class_name = str_replace( 'Tickera\\Ticket\\Element\\', '', $element_class_name );
                        $element_class_namespace = '\\Tickera\\Ticket\\Element\\' . $element_class_name;

                        if ( class_exists( $element_class_namespace ) ) {

                            if ( isset( $post_id ) ) {

                                $font_style_values = array(
                                    'B' => 'font-weight: bold;', // Bold
                                    'BI' => 'font-weight: bold; font-style: italic;', // Bold and Italic
                                    'BU' => 'font-weight: bold; text-decoration: underline;', // Bold and Underline
                                    'BIU' => 'font-weight: bold; bold; font-style: italic; text-decoration: underline;', // Bold, Italic and Underline
                                    'I' => 'font-style: italic;', // Italic
                                    'IU' => 'font-style: italic; text-decoration: underline;', // Italic, Underline
                                    'U' => 'text-decoration: underline;', // Underline
                                );

                                $font_style_orig = isset( $metas[ $element_class_name . '_font_style' ] ) ? $metas[ $element_class_name . '_font_style' ] : '';
                                $font_style = isset( $font_style_values[ $font_style_orig ] ) ? $font_style_values[ $font_style_orig ] : '';
                                $font_size = isset( $metas[ $element_class_name . '_font_size' ] ) ? $metas[ $element_class_name . '_font_size' ] : 14;

                                $rows .= '<td ' . ( isset( $metas[ $element_class_name . '_cell_alignment' ] ) ? 'align="' . esc_attr( $metas[ $element_class_name . '_cell_alignment' ] ) . '"' : 'align="left"' ) . ' style="' . ${"col_" . $rows_count} . ( isset( $metas[ $element_class_name . '_cell_alignment' ] ) ? 'text-align:' . esc_attr( $metas[ $element_class_name . '_cell_alignment' ] ) . ';' : '' ) . ( ( isset( $metas[ $element_class_name . '_font_size' ] ) && $metas[ $element_class_name . '_font_size' ] > 0 ) ? 'font-size: ' . esc_attr( $font_size ) . ';' : '' ) . ( isset( $metas[ $element_class_name . '_font_color' ] ) ? 'color:' . esc_attr( $metas[ $element_class_name . '_font_color' ] ) . ';' : '' ) . ( isset( $font_style ) ? esc_attr( $font_style ) : '' ) . '">';

                                $padding_top = isset( $metas[ $element_class_name . '_top_padding' ] ) ? (int) $metas[ $element_class_name . '_top_padding' ] : 0;
                                for ( $s = 1; $s <= $padding_top; $s++ ) {
                                    $rows .= '<br/>';
                                }

                                $element = new $element_class_namespace( $post_id );
                                $rows .= $element->ticket_content( $ticket_instance_id, $ticket_type_id );

                                $padding_bottom = isset( $metas[ $element_class_name . '_bottom_padding' ] ) ? (int) $metas[ $element_class_name . '_bottom_padding' ] : 0;
                                for ( $s = 1; $s <= $padding_bottom; $s++ ) {
                                    $rows .= '<br/>';
                                }

                                $rows .= '</td>';
                            }
                        }
                    }
                }
                $rows .= '</tr>';
            }
            $rows .= '</table>';

            $rows = apply_filters( 'tc_ticket_template_html', $rows, $template_id, $ticket_instance_id, is_admin() );
            $page1 = preg_replace( "/\s\s+/", '', $rows ); // Strip excess whitespace

            do_action( 'tc_before_pdf_write', $ticket_instance_id, $force_download, $template_id, $ticket_type_id, is_admin() );

            $pdf->writeHTML( $page1, true, 0, true, 0 ); // Write page 1
            do_action( 'tc_pdf_template', $pdf, $metas, $page1, $rows, $tc_document_paper_size, @$ticket_instance, $template_id, $force_download );

            if ( $string_attachment ) {
                return $pdf->Output( $pdf_filename, 'S' );

            } else {
                $pdf->Output( $pdf_filename, apply_filters( 'tc_change_tcpdf_save_option', ( $force_download ? 'D' : 'I' ) ) );
                if ( true == apply_filters( 'tc_exit_after_pdf_output', true ) ) exit;
            }
        }

        function TC_Cart_Form() {
            $this->__construct();
        }

        function add_new_template_new() {

            if ( isset( $_POST[ 'template_title' ] ) ) {

                $post = array(
                    'post_content' =>  sanitize_textarea_field( $_POST[ 'post_content' ] ),
                    'post_status' => 'publish',
                    'post_title' => sanitize_text_field( $_POST[ 'template_title' ] ),
                    'post_type' => 'tc_templates',
                );

                $post = apply_filters( 'tc_template_post', $post );

                if ( isset( $_POST[ 'template_id' ] ) ) {
                    $post[ 'ID' ] = (int) $_POST[ 'template_id' ]; // If ID is set, wp_insert_post will do the UPDATE instead of insert
                }

                $post_id = wp_insert_post( tickera_sanitize_array( $post, true ) );

                TC_Template::delete_cache( $post_id );

                return $post_id;
            }
        }

        function add_new_template() {

            if ( check_admin_referer( 'tickera_save_template' ) && isset( $_POST[ 'template_title' ] ) ) {

                $post = array(
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_title' => sanitize_text_field( $_POST[ 'template_title' ] ),
                    'post_type' => 'tc_templates',
                );

                $post = apply_filters( 'tc_template_post', $post );

                if ( isset( $_POST[ 'template_id' ] ) ) {
                    $post[ 'ID' ] = (int) $_POST[ 'template_id' ]; // If ID is set, wp_insert_post will do the UPDATE instead of insert
                }

                $post_id = wp_insert_post( tickera_sanitize_array( $post ) );

                // Update post meta
                if ( $post_id != 0 ) {

                    $post_data = tickera_sanitize_array( $_POST, true, true );
                    $post_data = $post_data ? $post_data : [];

                    foreach ( $post_data as $key => $value ) {

                        if ( preg_match( "/_post_meta/i", $key ) )  { // Every field name with sufix "_post_meta" will be saved as post meta automatically

                            if ( is_array( $value ) ) {
                                update_post_meta( (int) $post_id, sanitize_key( str_replace( '_post_meta', '', $key ) ), array_map( 'sanitize_text_field', $value ) );

                            } else {
                                update_post_meta( (int) $post_id, sanitize_key( str_replace( '_post_meta', '', $key ) ), sanitize_text_field( $value ) );
                            }

                            do_action( 'tc_template_post_metas' );
                        }
                    }
                }

                TC_Template::delete_cache( $post_id );

                return $post_id;
            }
        }

        function get_template_col_fields() {

            $default_fields = array(
                array(
                    'field_name' => 'post_title',
                    'field_title' => __( 'Template Name', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_title',
                    'table_visibility' => true,
                ),
                array(
                    'field_name' => 'post_date',
                    'field_title' => __( 'Date', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'post_field_type' => 'post_date',
                    'table_visibility' => true,
                ),
            );

            return apply_filters( 'tc_template_col_fields', $default_fields );
        }

        function get_columns() {

            $fields = $this->get_template_col_fields();
            $results = tickera_search_array( $fields, 'table_visibility', true );
            $columns = array();
            $columns[ 'ID' ] = __( 'ID', 'tickera-event-ticketing-system' );

            foreach ( $results as $result ) {
                $columns[ $result[ 'field_name' ] ] = $result[ 'field_title' ];
            }

            $columns[ 'edit' ] = __( 'Edit', 'tickera-event-ticketing-system' );
            $columns[ 'delete' ] = __( 'Delete', 'tickera-event-ticketing-system' );

            // Add duplicate field
            if ( tickera_iw_is_pr() && ! \Tickera\tets_fs()->is_free_plan() ) {
                $columns[ 'tc_duplicate' ] = __( 'Action', 'tickera-event-ticketing-system' );
            }

            return $columns;
        }

        function check_field_property( $field_name, $property ) {
            $fields = $this->get_template_col_fields();
            $result = tickera_search_array( $fields, 'field_name', $field_name );
            return $result[ 0 ][ 'post_field_type' ];
        }

        function is_valid_template_col_field_type( $field_type ) {

            if ( in_array( $field_type, $this->valid_admin_fields_type ) ) {
                return true;

            } else {
                return false;
            }
        }
    }
}
