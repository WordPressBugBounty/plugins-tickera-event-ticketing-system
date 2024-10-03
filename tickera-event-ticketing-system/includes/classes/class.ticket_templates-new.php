<?php

namespace Tickera;

if ( !defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

if ( !class_exists( 'Tickera\TC_Ticket_Templates' ) ) {

	class TC_Ticket_Templates {

		var $form_title				 = '';
		var $valid_admin_fields_type	 = array( 'text', 'textarea', 'checkbox', 'function' );

		function __construct() {

			/**
			 * If true allows to call TCPDF methods using HTML syntax
			 * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
			 */
			if ( !defined( 'TC_K_TCPDF_CALLS_IN_HTML' ) )
				define( 'TC_K_TCPDF_CALLS_IN_HTML', true );

			$this->valid_admin_fields_type = apply_filters( 'tc_valid_admin_fields_type', $this->valid_admin_fields_type );
		}

		function col_type_to_colspan( $type = 1, $current_column = 1 ) {
			$colspan = 1;
			//5, 6, 7, 8, 10 only, for other types, columns are equal
			switch ( $type ) {
				case 5:
					if ( $current_column == 1 ) {
						$colspan = 1;
					}
					if ( $current_column == 2 ) {
						$colspan = 2;
					}
					break;
				case 6:
					if ( $current_column == 1 ) {
						$colspan = 2;
					}
					if ( $current_column == 2 ) {
						$colspan = 1;
					}
					break;
				case 7:
					if ( $current_column == 1 ) {
						$colspan = 1;
					}
					if ( $current_column == 2 ) {
						$colspan = 1;
					}
					if ( $current_column == 3 ) {
						$colspan = 2;
					}
					break;
				case 8:
					if ( $current_column == 1 ) {
						$colspan = 1;
					}
					if ( $current_column == 2 ) {
						$colspan = 2;
					}
					if ( $current_column == 3 ) {
						$colspan = 1;
					}
					break;
				case 10:
					if ( $current_column == 1 ) {
						$colspan = 1;
					}
					if ( $current_column == 2 ) {
						$colspan = 3;
					}
					if ( $current_column == 3 ) {
						$colspan = 1;
					}
					break;
				default:
					$html_code = '';
			}

			if($colspan > 0){
				return 'colspan="' . $colspan . '"';
			}
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
		function generate_preview( $ticket_instance_id = false, $force_download = false, $template_id = false,
							 $ticket_type_id = false, $string_attachment = false ) {
			global $tc, $pdf;

			// Trying to set a memory limit to a high value since some template might need more memory (when a huge background is set, etc)
			@ini_set( 'memory_limit', '1024M' );

			// Display all errors if TC_DEBUG is true
			if ( defined( 'TC_DEBUG' ) || isset( $_GET[ 'TC_DEBUG' ] ) ) {
				error_reporting( E_ALL );
				@ini_set( 'display_errors', 'On' );
			}

			// Initialize TCPDF Libraries
			if ( !class_exists( 'Tickera\TCPDF' ) )
				require_once( $tc->plugin_dir . 'includes/tcpdf/examples/tcpdf_include.php' );

			ob_start();
			$output_buffering = ini_get( 'output_buffering' );
			if ( isset( $output_buffering ) && $output_buffering > 0 ) {
				if ( !ob_get_level() ) {
					ob_end_clean();
					ob_start();
				}
			}

			$post_id = $template_id;
			##
			// Use $template_id only if you preview the ticket
			if ( $ticket_instance_id ) {

				$ticket_instance_status = get_post_status( $ticket_instance_id );
				if ( 'publish' == $ticket_instance_status ) {

					$ticket_instance = new \Tickera\TC_Ticket( $ticket_instance_id );
					$pdf_filename	 = apply_filters( 'tc_pdf_ticket_name', $ticket_instance->details->ticket_code, $ticket_instance ) . '.pdf';

					$ticket_template = get_post_meta( $ticket_instance->details->ticket_type_id, 'ticket_template', true );
					$post_id		 = (!$ticket_template ) ? get_post_meta( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ), apply_filters( 'tc_ticket_template_field_name', '_ticket_template' ), true ) : $ticket_template;
				} else {
					esc_html_e( 'Something went wrong. Ticket does not exists.', 'tickera-event-ticketing-system' );
					exit;
				}
			} else {
				$pdf_filename = __( 'preview', 'tickera-event-ticketing-system' );
			}

			// Retrieve template's post metas
			$post_content		 = get_post_field( 'post_content', $post_id );
			$template_structure	 = json_decode( $post_content, true );
			$metas				 = $template_structure[ 'settings' ];

			//$metas = ( $post_id ) ? tickera_get_post_meta_all( $post_id ) : array();
			$tc_document_orientation = ( $metas ) ? $metas[ 'document_ticket_orientation' ] : 'P';

			// For custom size, document_ticket_size value should be formatted in array.
			$tc_document_paper_size	 = ( $metas ) ? apply_filters( 'tc_document_paper_size', $metas[ 'document_ticket_size' ] ) : 'A4';
			$tc_document_paper_size	 = ( is_array( $tc_document_paper_size ) ) ? array_map( 'intval', array_values( array_filter( $tc_document_paper_size ) ) ) : $tc_document_paper_size;

			// Create new PDF document
			$pdf = new Tickera\TCPDF( $tc_document_orientation, TC_PDF_UNIT, apply_filters( 'tc_additional_ticket_document_size_output', $tc_document_paper_size ), true, apply_filters( 'tc_ticket_document_encoding', 'UTF-8' ), false );

			// Set TCPDF Defaults
			$pdf->SetCompression( true );
			$pdf->setPrintHeader( false );
			$pdf->setPrintFooter( false );

			// Set Font & Margins
			if ( $metas ) {

				$font = ( $metas[ 'document_font' ] ) ? $metas[ 'document_font' ] : 'helvetica';

				/**
				 * Retrieve custom font subset.
				 * @since 3.4.9.6
				 */
				$font_subset = ( isset( $metas[ 'document_font_subset' ] ) ) ? $metas[ 'document_font_subset' ] : 'default';

				$margin_left	 = ( $metas[ 'document_ticket_left_margin' ] ) ? $metas[ 'document_ticket_left_margin' ] : 1;
				$margin_top		 = ( $metas[ 'document_ticket_top_margin' ] ) ? $metas[ 'document_ticket_top_margin' ] : 0;
				$margin_right	 = ( $metas[ 'document_ticket_right_margin' ] ) ? $metas[ 'document_ticket_right_margin' ] : 0;

				$pdf->SetFont( $font, '', 14, '', $font_subset );
				$pdf->SetMargins( $margin_left, $margin_top, $margin_right );
			}

			$tc_general_settings			 = get_option( 'tickera_general_setting', false );
			$ticket_template_auto_pagebreak	 = ( isset( $tc_general_settings[ 'ticket_template_auto_pagebreak' ] ) && 'yes' == $tc_general_settings[ 'ticket_template_auto_pagebreak' ] ) ? true : false;

			$pdf->SetAutoPageBreak( false, 0 );
			$pdf->setJPEGQuality( 100 );
			$pdf->AddPage();

			// Set Background image
			if ( isset( $metas[ 'document_ticket_background_image' ] ) && $metas[ 'document_ticket_background_image' ] ) {
				$pdf->setImageScale( TC_PDF_IMAGE_SCALE_RATIO );

				$tc_ticket_background		 = tickera_ticket_template_image_url( $metas[ 'document_ticket_background_image' ] );
				$tc_ticket_background_values = array(
					'P'	 => array(
						'A4'	 => array( 0, 0, 210, 297 ),
						'A5'	 => array( 0, 0, 148, 210 ),
						'A6'	 => array( 0, 0, 105, 148 ),
						'A7'	 => array( 0, 0, 74, 105 ),
						'A8'	 => array( 0, 0, 52, 74 ),
						'ANSI_A' => array( 0, 0, 216, 279 )
					),
					'L'	 => array(
						'A4'	 => array( 0, 0, 297, 210 ),
						'A5'	 => array( 0, 0, 210, 148 ),
						'A6'	 => array( 0, 0, 148, 105 ),
						'A7'	 => array( 0, 0, 105, 74 ),
						'A8'	 => array( 0, 0, 74, 52 ),
						'ANSI_A' => array( 0, 0, 279, 216 )
					)
				);

				// Custom Size ( Example array format: [ 0, 0, X, Y ] )
				if ( is_array( $tc_document_paper_size ) ) {
					$tc_ticket_background_values[ $tc_document_orientation ][ 'custom' ] = array_merge( [ 0, 0 ], $tc_document_paper_size );
					$tc_document_paper_size												 = 'custom';
				}

				$tc_bg_size = $tc_ticket_background_values[ $tc_document_orientation ][ $tc_document_paper_size ];
				$pdf->Image( $tc_ticket_background, $tc_bg_size[ 0 ], $tc_bg_size[ 1 ], $tc_bg_size[ 2 ], $tc_bg_size[ 3 ], '', '', '', true, 300, '', false, false, 0, false );
			}

			$pdf->SetAutoPageBreak( $ticket_template_auto_pagebreak, PDF_MARGIN_BOTTOM );

			$current_row	 = 0;
			$current_col	 = 0;
			$current_element = 0;

			$post_content		 = get_post_field( 'post_content', $post_id );
			$template_structure	 = json_decode( $post_content, true );

			$rows = isset( $template_structure[ 'rows' ] ) ? $template_structure[ 'rows' ] : array();

			if ( count( $rows ) > 0 ) {

				$font_style_values = array(
					'B'		 => 'font-weight: bold;', // Bold
					'BI'	 => 'font-weight: bold; font-style: italic;', // Bold and Italic
					'BU'	 => 'font-weight: bold; text-decoration: underline;', // Bold and Underline
					'BIU'	 => 'font-weight: bold; bold; font-style: italic; text-decoration: underline;', // Bold, Italic and Underline
					'I'		 => 'font-style: italic;', // Italic
					'IU'	 => 'font-style: italic; text-decoration: underline;', // Italic, Underline
					'U'		 => 'text-decoration: underline;', // Underline
				);

				$template_content = '';

				foreach ( $rows as $row ) {
					$current_row++;
					$row_info = $row[ 'info' ];

					$template_content	 .= '<table style="width: 100%;">';// border: 1px;
					$template_content	 .= '<tr>';

					$current_col = 0;

					foreach ( $row[ 'cols' ] as $col ) {
						$current_col++;
						$colspan = $this->col_type_to_colspan( $row_info[ 'type' ], $current_col );

						$template_content .= '<td style="vertical-align: top;" ' . $colspan . '>'; //.$row_info[ 'type' ];// border: 1px solid;

						$current_element = 0;
						foreach ( $col[ 'elements' ] as $elements ) {
							$current_element++;

							$element_class_name	 = $elements[ 'name' ];
							$element			 = new $element_class_name();

							$element_default_values = $template_structure[ 'rows' ][ 'row_' . $current_row ][ 'cols' ][ 'col_' . $current_col ][ 'elements' ][ 'element_' . $current_element ];

							/*
							  'name' => string 'tc_event_name_element' (length=21)
							  'tc_event_name_element_font_size' => string '36' (length=2)
							  'tc_event_name_element_font_style' => string '' (length=0)
							  'tc_event_name_element_font_color' => string '#000000' (length=7)
							  'tc_event_name_element_cell_alignment' => string 'left' (length=4)
							  'tc_event_name_element_top_padding' => string '1' (length=1)
							  'tc_event_name_element_bottom_padding' => string '1' (length=1)
							 */

							$font_style_orig = isset( $element_default_values[ $element_class_name . '_font_style' ] ) ? $element_default_values[ $element_class_name . '_font_style' ] : '';
							$font_style		 = isset( $font_style_values[ $font_style_orig ] ) ? $font_style_values[ $font_style_orig ] : '';
							$font_size		 = isset( $element_default_values[ $element_class_name . '_font_size' ] ) ? $element_default_values[ $element_class_name . '_font_size' ] : 14;

							$template_content .= '<table style="width: 100%;"><tr><td ' . ( isset( $element_default_values[ $element_class_name . '_cell_alignment' ] ) ? 'align="' . esc_attr( $element_default_values[ $element_class_name . '_cell_alignment' ] ) . '"' : 'align="left"' ) . ' style="' . ( isset( $element_default_values[ $element_class_name . '_cell_alignment' ] ) ? 'text-align:' . esc_attr( $element_default_values[ $element_class_name . '_cell_alignment' ] ) . ';' : '' ) . ( ( isset( $element_default_values[ $element_class_name . '_font_size' ] ) && $element_default_values[ $element_class_name . '_font_size' ] > 0 ) ? 'font-size: ' . esc_attr( $font_size ) . ';' : '' ) . ( isset( $element_default_values[ $element_class_name . '_font_color' ] ) ? 'color:' . esc_attr( $element_default_values[ $element_class_name . '_font_color' ] ) . ';' : '' ) . ( isset( $font_style ) ? esc_attr( $font_style ) : '' ) . '">';

							//Add padding above the element
							$padding_top = isset( $element_default_values[ $element_class_name . '_top_padding' ] ) ? (int) $element_default_values[ $element_class_name . '_top_padding' ] : 0;
							for ( $s = 1; $s <= $padding_top; $s++ ) {
								$template_content .= '<br/>';
							}

							if ( method_exists( $element, 'ticket_content_v2' ) ) {
								$template_content .= $element->ticket_content_v2( $element_default_values, $ticket_instance_id, $ticket_type_id );
							} else {
								$template_content .= $element->ticket_content( $ticket_instance_id, $ticket_type_id );
							}

							//Add padding bellow the element
							$padding_bottom = isset( $element_default_values[ $element_class_name . '_bottom_padding' ] ) ? (int) $element_default_values[ $element_class_name . '_bottom_padding' ] : 0;
							for ( $s = 1; $s <= $padding_bottom; $s++ ) {
								$template_content .= '<br/>';
							}

							$template_content .= '</td></tr></table>';
						}

						$template_content .= '</td>';
					}

					$template_content	 .= '</tr>';
					$template_content	 .= '</table>';
				}
			}


			$page1 = preg_replace( "/\s\s+/", '', $template_content ); // Strip excess whitespace
			do_action( 'tc_before_pdf_write', $ticket_instance_id, $force_download, $template_id, $ticket_type_id, is_admin() );

			$pdf->writeHTML( $page1, true, 0, true, 0 ); // Write page 1
			do_action( 'tc_pdf_template', $pdf, $metas, $page1, $rows, $tc_document_paper_size, @$ticket_instance, $template_id, $force_download );

			if ( $string_attachment ) {
				return $pdf->Output( $pdf_filename, 'S' );
			} else {
				$pdf->Output( $pdf_filename, apply_filters( 'tc_change_tcpdf_save_option', ( $force_download ? 'D' : 'I' ) ) );
				if ( true == apply_filters( 'tc_exit_after_pdf_output', true ) )
					exit;
			}
		}

		function TC_Cart_Form() {
			$this->__construct();
		}

		function add_new_template_new() {

			if ( isset( $_POST[ 'template_title' ] ) ) {

				$post = array(
					'post_content'	 => sanitize_textarea_field( $_POST[ 'post_content' ] ),
					'post_status'	 => 'publish',
					'post_title'	 => sanitize_text_field( $_POST[ 'template_title' ] ),
					'post_type'		 => 'tc_templates',
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
					'post_content'	 => '',
					'post_status'	 => 'publish',
					'post_title'	 => sanitize_text_field( $_POST[ 'template_title' ] ),
					'post_type'		 => 'tc_templates',
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

						if ( preg_match( "/_post_meta/i", $key ) ) { // Every field name with sufix "_post_meta" will be saved as post meta automatically

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
					'field_name'		 => 'post_title',
					'field_title'		 => __( 'Template Name', 'tickera-event-ticketing-system' ),
					'field_type'		 => 'text',
					'field_description'	 => '',
					'post_field_type'	 => 'post_title',
					'table_visibility'	 => true,
				),
				array(
					'field_name'		 => 'post_date',
					'field_title'		 => __( 'Date', 'tickera-event-ticketing-system' ),
					'field_type'		 => 'text',
					'field_description'	 => '',
					'post_field_type'	 => 'post_date',
					'table_visibility'	 => true,
				),
			);

			return apply_filters( 'tc_template_col_fields', $default_fields );
		}

		function get_columns() {

			$fields			 = $this->get_template_col_fields();
			$results		 = tickera_search_array( $fields, 'table_visibility', true );
			$columns		 = array();
			$columns[ 'ID' ] = __( 'ID', 'tickera-event-ticketing-system' );

			foreach ( $results as $result ) {
				$columns[ $result[ 'field_name' ] ] = $result[ 'field_title' ];
			}

			$columns[ 'edit' ]	 = __( 'Edit', 'tickera-event-ticketing-system' );
			$columns[ 'delete' ] = __( 'Delete', 'tickera-event-ticketing-system' );

			// Add duplicate field
			if ( tickera_iw_is_pr() && !tets_fs()->is_free_plan() ) {
				$columns[ 'tc_duplicate' ] = __( 'Action', 'tickera-event-ticketing-system' );
			}

			return $columns;
		}

		function check_field_property( $field_name, $property ) {
			$fields	 = $this->get_template_col_fields();
			$result	 = tickera_search_array( $fields, 'field_name', $field_name );
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
