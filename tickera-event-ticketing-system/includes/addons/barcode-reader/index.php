<?php
/**
 * Tickera - Barcode Reader Add-on
 * Add Barcode Reader support to Tickera plugin
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Addons\TC_Barcode_Reader_Core' ) ) {

    class TC_Barcode_Reader_Core {

        var $version = '1.3';
        var $title = 'Barcode Reader';
        var $name = 'tc_barcode_reader';
        var $dir_name = 'barcode-reader';
        var $location = 'plugins';
        var $plugin_dir = '';
        var $plugin_url = '';

        function __construct() {

            // Check if Tickera plugin is active / main Tickera class exists
            if ( class_exists( 'Tickera\TC' ) ) {

                global $tc;
                $this->plugin_dir = $tc->plugin_dir . 'includes/addons/' . $this->dir_name . '/';
                $this->plugin_url = plugins_url( '/', __FILE__ );
                add_filter( 'tc_admin_capabilities', array( $this, 'append_capabilities' ) );
                add_filter( 'tc_staff_capabilities', array( $this, 'append_capabilities' ) );
                add_action( 'tc_add_menu_items_after_ticket_templates', array( $this, 'add_admin_menu_item_to_tc' ) );
                add_action( 'admin_enqueue_scripts', array( $this, 'admin_header' ) );
                add_action( 'wp_ajax_check_in_barcode', array( $this, 'check_in_barcode' ) );
                add_action( 'wp_ajax_nopriv_check_in_barcode', array( $this, 'check_in_barcode' ) );
            }
        }

        /**
         * Waiting for ajax calls to check barcode
         */
        function check_in_barcode() {

            if ( isset( $_POST[ 'api_key' ] ) && isset( $_POST[ 'barcode' ] ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                $api_key = new \Tickera\TC_API_Key( sanitize_text_field( $_POST[ 'api_key' ] ) );
                $checkin = new \Tickera\TC_Checkin_API( $api_key->details->api_key, apply_filters( 'tc_checkin_request_name', 'tickera_scan' ), 'return', sanitize_text_field( $_POST[ 'barcode' ] ), false );
                $checkin_result = $checkin->ticket_checkin( false );

                if ( is_numeric( $checkin_result ) && $checkin_result == 403 ) {

                    // Permissions issue
                    wp_send_json( (int) $checkin_result );

                } else {

                    if ( isset( $checkin_result[ 'status' ] ) && $checkin_result[ 'status' ] == 1 ) {

                        // Success
                        wp_send_json( 1 );

                    } else {

                        // Fail
                        wp_send_json( 2 );
                    }
                }
            }
        }

        /**
         * Add additional capabilities to staff and admins
         *
         * @param $capabilities
         * @return mixed
         */
        function append_capabilities( $capabilities ) {
            $capabilities[ 'manage_' . $this->name . '_cap' ] = 1;
            return $capabilities;
        }

        /**
         * Add additional menu item under Tickera admin menu
         */
        function add_admin_menu_item_to_tc() {

            global $first_tc_menu_handler;
            $handler = 'ticket_templates';

            $admin_page_func = function () {
                require_once( $this->plugin_dir . "/includes/admin-pages/" . $this->name . ".php" );
            };

            $title = sprintf(
                /* translators: %s: Barcode Reader label */
                __( '%s', 'tickera-event-ticketing-system' ),
                $this->title
            );

            add_submenu_page(
                $first_tc_menu_handler,
                $title,
                $title,
                'manage_' . $this->name . '_cap',
                $this->name,
                $admin_page_func
            );

            do_action( $this->name . '_add_menu_items_after_' . $handler );
        }

        /**
         * Add scripts and CSS for the plugin
         */
        function admin_header() {
            wp_enqueue_script( $this->name . '-admin', $this->plugin_url . 'js/admin.js', array( 'jquery' ), false, false );
            wp_localize_script( $this->name . '-admin', 'tc_barcode_reader_vars', array(
                'admin_ajax_url' => admin_url( 'admin-ajax.php' ),
                'message_barcode_default' => __( 'Select input field and scan a barcode located on the ticket.', 'tickera-event-ticketing-system' ),
                'message_checking_in' => __( 'Checking in...', 'tickera-event-ticketing-system' ),
                'message_insufficient_permissions' => __( 'Insufficient permissions. This API key cannot check in this ticket.', 'tickera-event-ticketing-system' ),
                'message_barcode_status_error' => __( 'Ticket code is wrong or expired.', 'tickera-event-ticketing-system' ),
                'message_barcode_status_success' => __( 'Ticket has been successfully checked in.', 'tickera-event-ticketing-system' ),
                'message_barcode_status_error_exists' => __( 'Ticket does not exist.', 'tickera-event-ticketing-system' ),
                'message_barcode_api_key_not_selected' => sprintf(
                    /* translators: %s: A link to the Tickera > Setings > API Access */
                    __( 'Please create and select an <a href="%s">API Key</a> in order to check in the ticket.', 'tickera-event-ticketing-system' ),
                    esc_url( admin_url( 'admin.php?page=tc_settings&tab=api' ) ),
                ),
                'message_barcode_cannot_be_empty' => __( 'Ticket code cannot be empty', 'tickera-event-ticketing-system' ),
            ) );
            wp_enqueue_style( $this->name . '-admin', $this->plugin_url . 'css/admin.css', array(), $this->version );
        }
    }
}

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

$tc_barcode_reader_core = new TC_Barcode_Reader_Core();
