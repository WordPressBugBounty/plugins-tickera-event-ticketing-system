<?php
/**
 * Tickera Export
 * Export attendees data in PDF
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Addons\TC_Export_Mix' ) ) {

    class TC_Export_Mix {

        var $version = '1.1';
        var $title = 'Tickera Export';
        var $name = 'tc';
        var $dir_name = 'tickera-export';
        var $plugin_dir = '';
        var $plugin_url = '';
        var $per_page = '';

        function __construct() {
            $this->title = __( 'Tickera Export', 'tickera-event-ticketing-system' );
            $this->per_page = apply_filters( 'tc_export_pdf_pagination_per_page', 100 );
            add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
            add_filter( 'tc_settings_new_menus', array( $this, 'tc_settings_new_menus_additional' ) );
            add_action( 'tc_settings_menu_tickera_export_mixed_data', array( $this, 'tc_settings_menu_tickera_export_mixed_data_show_page' ) );
            add_action( 'wp_ajax_prepare_export_data', array( $this, 'prepare_export_data' ) );
            add_action( 'admin_init', array( $this, 'tc_export_data' ), 0 );
        }

        function admin_enqueue_scripts() {
            if ( $_GET && isset( $_GET[ 'tab' ] ) && 'tickera_export_mixed_data' == $_GET[ 'tab' ] ) {
                wp_enqueue_script( 'jquery-ui-progressbar' );
            }
        }

        function tc_settings_new_menus_additional( $settings_tabs ) {
            $settings_tabs[ 'tickera_export_mixed_data' ] = __( 'Export PDF', 'tickera-event-ticketing-system' );
            return $settings_tabs;
        }

        function tc_settings_menu_tickera_export_mixed_data_show_page() {
            require_once( $this->plugin_dir . 'includes/admin-pages/settings-tickera_export_mixed_data.php' );
        }

        /**
         * Prepare Attendees Data
         */
        function prepare_export_data() {

            if ( isset( $_POST['nonce'] ) && isset( $_POST[ 'event_id' ] ) && (int) $_POST[ 'event_id' ] ) {

                if ( wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {

                    global $tc;
                    $page = isset( $_POST[ 'page' ] ) ? (int) $_POST[ 'page' ] : 1;

                    $ticket_instances = get_posts( [
                        'posts_per_page' => $this->per_page,
                        'paged' => $page,
                        'orderby' => 'ID',
                        'meta_key' => 'event_id',
                        'meta_value' => (int) $_POST[ 'event_id' ],
                        'post_type' => 'tc_tickets_instances',
                        'post_status' => 'publish',
                        'fields' => 'ids'
                    ] );

                    $success = $ticket_instances ? true : false;
                    $tc_export_pdf = $tc->session->get( 'tc_export_pdf' );

                    if ( 1 == $page ) {
                        $tc_export_pdf = [];
                    }

                    $progress = ( count( $tc_export_pdf ) > 0 )
                        ? ( count( $tc_export_pdf ) / ( $this->per_page * $page ) ) * 100
                        : 0;

                    // Progress with offset
                    $progress = ( $progress <= 80 ) ? ( $progress / 2 ) : $progress;

                    $tc_export_pdf = array_merge( $tc_export_pdf, $ticket_instances );
                    $tc->session->set( 'tc_export_pdf', $tc_export_pdf );

                    wp_send_json( [ 'success' => $success, 'page' => ( $page + 1 ), 'progress' => $progress ] );
                }
            }
        }

        /**
         * Execute export PDF Data
         */
        function tc_export_data() {

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            if ( isset( $_POST[ 'tc_export_event_data' ] ) ) {

                global $tc, $pdf;

                if ( defined( 'TC_DEBUG' ) || isset( $_GET[ 'tc_debug' ] ) ) {
                    error_reporting( E_ALL );
                    @ini_set( 'display_errors', 'On' );
                }

                if ( ! class_exists( 'Tickera\TCPDF' ) ) {
                    require_once( $tc->plugin_dir . 'includes/tcpdf/tcpdf.php' );
                    require_once( $tc->plugin_dir . 'includes/tcpdf/extensions/tcpdf_ext.php' );
                }

                /*
                 * ob_end_clean to ensure buffer is deleted.
                 * Expected error if no buffer to delete.
                 * Suppressing ob_end_clean error to avoid termination of export process.
                 */
                @ob_end_clean();
                ob_start();

                $margin_left = 10;
                $margin_top = 10;
                $margin_right = 10;

                $pdf = new \Tickera\TCPDF( sanitize_text_field( $_POST[ 'document_orientation' ] ), TC_PDF_UNIT, sanitize_text_field( $_POST[ 'document_size' ] ), true, get_bloginfo( 'charset' ), false );
                $pdf->setPrintHeader( false );
                $pdf->setPrintFooter( false );
                $pdf->SetFont( sanitize_text_field( $_POST[ 'document_font' ] ), '', sanitize_text_field( $_POST[ 'document_font_size' ] ) );
                $pdf->SetMargins( $margin_left, $margin_top, $margin_right );
                $pdf->SetAutoPageBreak( true, TC_PDF_MARGIN_BOTTOM );
                $pdf->AddPage();

                if ( $_POST[ 'document_title' ] !== '' ) {
                    $rows = '<h1 style="text-align:center;">' . esc_html( stripslashes( sanitize_text_field( $_POST[ 'document_title' ] ) ) ) . '</h1>';
                }
                $rows .= '<table width="100%" border="1" cellpadding="2"><tr>';

                if ( isset( $_POST[ 'col_checkbox' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Check', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_owner_name' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Ticket Owner', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_payment_date' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Payment Date', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_ticket_id' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Ticket ID', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_ticket_type' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Ticket Type', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_buyer_name' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Buyer Name', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_buyer_email' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Buyer Email', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_barcode' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Barcode', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_qrcode' ] ) ) {
                    $rows .= '<th align="center">' . __( 'QR Code', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_checked_in' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Checked-in', 'tickera-event-ticketing-system' ) . '</th>';
                }
                if ( isset( $_POST[ 'col_checkins' ] ) ) {
                    $rows .= '<th align="center">' . __( 'Check-ins', 'tickera-event-ticketing-system' ) . '</th>';
                }

                $rows = apply_filters( 'tc_pdf_additional_column_titles', $rows, $_POST );
                $rows .= '</tr>';

                $ticket_instances = $tc->session->get( 'tc_export_pdf' );
                foreach ( $ticket_instances as $ticket_instance_id ) {

                    $instance = new \Tickera\TC_Ticket_Instance( $ticket_instance_id );
                    $ticket_type = new \Tickera\TC_Ticket( apply_filters( 'tc_ticket_type_id', $instance->details->ticket_type_id ) );
                    $order = new \Tickera\TC_Order( $instance->details->post_parent );

                    $order_is_paid = ( 'order_paid' == $order->details->post_status ) ? true : false;
                    $order_is_paid = apply_filters( 'tc_order_is_paid', $order_is_paid, $order->details->ID );

                    if ( $order_is_paid ) {

                        $format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );
                        $date = get_date_from_gmt( date( 'Y-m-d H:i:s', apply_filters( 'tc_ticket_checkin_order_date', $order->details->tc_order_date, $order->details->ID ) ) );
                        $payment_date = date_i18n( $format, strtotime( $date ) );
                        $rows .= '<tr>';

                        if ( isset( $_POST[ 'col_checkbox' ] ) ) {
                            $rows .= '<td align="center"></td>';
                        }
                        if ( isset( $_POST[ 'col_owner_name' ] ) ) {
                            $rows .= '<td>' . esc_html( $instance->details->first_name . ' ' . $instance->details->last_name ) . '</td>';
                        }
                        if ( isset( $_POST[ 'col_payment_date' ] ) ) {

                            if ( true == apply_filters( 'tc_bridge_for_woocommerce_is_active', false ) && is_plugin_active( 'woocommerce/woocommerce.php' ) && 'shop_order' == get_post_type( $order->details->ID ) ) {
                                $wc_post = get_post( $ticket_instance_id, 'OBJECT' );
                                $format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );;
                                $rows .= '<td>' . date( $format, strtotime( $wc_post->post_date ) ) . '</td>';

                            } else {
                                $rows .= '<td>' . esc_html( $payment_date ) . '</td>';
                            }
                        }
                        if ( isset( $_POST[ 'col_ticket_id' ] ) ) {
                            $rows .= '<td>' . esc_html( $instance->details->ticket_code ) . '</td>';
                        }
                        if ( isset( $_POST[ 'col_ticket_type' ] ) ) {
                            $rows .= '<td>' . esc_html( apply_filters( 'tc_checkout_owner_info_ticket_title', $ticket_type->details->post_title, isset( $instance->details->ticket_type_id ) ? $instance->details->ticket_type_id : $ticket_type->details->ID, array(), $ticket_instance_id ) ) . '</td>';
                        }
                        if ( isset( $_POST[ 'col_buyer_name' ] ) ) {
                            $buyer_data = isset( $order->details->tc_cart_info[ 'buyer_data' ] ) ? $order->details->tc_cart_info[ 'buyer_data' ] : [];
                            $first_name = isset( $buyer_data[ 'first_name_post_meta' ]  ) ? $buyer_data[ 'first_name_post_meta' ] : '';
                            $last_name = isset( $buyer_data[ 'last_name_post_meta' ] ) ? $buyer_data[ 'last_name_post_meta' ] : '';
                            $rows .= '<td>' . esc_html( apply_filters( 'tc_ticket_checkin_buyer_full_name', $first_name . ' ' . $last_name, $order->details->ID ) ) . '</td>';
                        }
                        if ( isset( $_POST[ 'col_buyer_email' ] ) ) {
                            $buyer_data = isset( $order->details->tc_cart_info[ 'buyer_data' ] ) ? $order->details->tc_cart_info[ 'buyer_data' ] : [];
                            $buyer_email = isset( $buyer_data[ 'email_post_meta' ] ) ? $buyer_data[ 'email_post_meta' ] : '';
                            $rows .= '<td>' . esc_html( apply_filters( 'tc_ticket_checkin_buyer_email', $buyer_email, $order->details->ID ) ) . '</td>';
                        }
                        if ( isset( $_POST[ 'col_barcode' ] ) ) {
                            $rows .= '<td>' . __( 'BARCODE', 'tickera-event-ticketing-system' ) . '</td>';
                        }
                        if ( isset( $_POST[ 'col_qrcode' ] ) ) {
                            $rows .= '<td>' . __( 'QRCODE', 'tickera-event-ticketing-system' ) . '</td>';
                        }

                        if ( isset( $_POST[ 'col_checked_in' ] ) ) {
                            $checkins = get_post_meta( $ticket_instance_id, 'tc_checkins', true );
                            $checked_in = ( is_array( $checkins ) && count( $checkins ) > 0 ) ? __( 'Yes', 'tickera-event-ticketing-system' ) : __( 'No', 'tickera-event-ticketing-system' );
                            $rows .= '<td>' . esc_html( $checked_in ) . '</td>';
                        }

                        if ( isset( $_POST[ 'col_checkins' ] ) ) {

                            $checkins_list = array();
                            $checkins = get_post_meta( $ticket_instance_id, 'tc_checkins', true );

                            if ( is_array( $checkins ) && count( $checkins ) > 0 ) {

                                foreach ( $checkins as $checkin ) {
                                    $api_key = $checkin[ 'api_key_id' ];
                                    $api_key_obj = new \Tickera\TC_API_Key( (int) $api_key );
                                    $api_key_name = $api_key_obj->details->api_key_name;
                                    if ( apply_filters( 'tc_show_checkins_api_key_names', true ) == true ) {
                                        $api_key_name = ! empty( $api_key_name ) ? $api_key_name : $api_key;
                                        $api_key_title = ' (' . $api_key_name . ')';

                                    } else {
                                        $api_key_title = '';
                                    }

                                    $checkins_list[] = tickera_format_date( $checkin[ 'date_checked' ] ) . $api_key_title;
                                }

                                $checkins = implode( "\r\n", $checkins_list );

                            } else {
                                $checkins = '';
                            }

                            $rows .= '<td>' . esc_html( $checkins ) . '</td>';
                        }

                        $rows = apply_filters( 'tc_pdf_additional_column_values', $rows, $order, $instance, $_POST );
                        $rows .= '</tr>';
                    }
                }

                $rows .= '</table>';
                $rows = wp_kses_post( $rows );

                $page1 = preg_replace( "/\s\s+/", '', $rows ); //Strip excess whitespace
                $tc->session->drop( 'tc_export_pdf' );
                ob_get_clean();

                $pdf->writeHTML( $page1, true, 0, true, 0 ); //Write page 1
                $pdf->Output( ( $_POST[ 'document_title' ] !== '' ) ? sanitize_file_name( $_POST[ 'document_title' ] . '.pdf' ) : __( 'Attendee List', 'tickera-event-ticketing-system' ) . '.pdf', 'D' );
                exit;
            }
        }
    }
}

$tc_export_mix = new TC_Export_Mix();
