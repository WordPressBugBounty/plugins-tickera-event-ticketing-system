<?php

/**
 * Plugin Name: Tickera
 * Plugin URI: https://tickera.com/
 * Description: Simple event ticketing system.
 * Author: Tickera.com
 * Author URI: https://tickera.com/
 * Version: 3.5.4.6
 * Text Domain: tickera-event-ticketing-system
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
namespace Tickera;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
if ( !class_exists( 'Tickera\\TC' ) ) {
    class TC {
        var $version = '3.5.4.6';

        var $title = 'Tickera';

        var $name = 'tc';

        var $dir_name = 'tickera-event-ticketing-system';

        var $location = 'plugins';

        var $plugin_dir = '';

        var $plugin_url = '';

        var $global_cart = false;

        var $checkout_error = false;

        var $admin_menu_position = 1000;

        var $session = '';

        var $language;

        function __construct() {
            $this->init_vars();
            // Load general functions
            require_once $this->plugin_dir . 'includes/general-functions.php';
            $this->maybe_set_session_path();
            $this->maybe_make_writtable_tcpdf_directory();
            // Load Session
            require_once $this->plugin_dir . 'includes/classes/class.session.php';
            $this->session = new TC_Session();
            require_once $this->plugin_dir . 'includes/classes/class.fields.php';
            require_once $this->plugin_dir . 'includes/classes/class.form_fields_api.php';
            // Load checkin api class
            require_once $this->plugin_dir . 'includes/classes/class.checkin_api.php';
            // Load sales api class
            require_once $this->plugin_dir . 'includes/classes/class.sales_api.php';
            // Load event class
            require_once $this->plugin_dir . 'includes/classes/class.cart_form.php';
            // Load event class
            require_once $this->plugin_dir . 'includes/classes/class.event.php';
            // Load events class
            require_once $this->plugin_dir . 'includes/classes/class.events.php';
            // Load events search class
            require_once $this->plugin_dir . 'includes/classes/class.events_search.php';
            // Load api key class
            require_once $this->plugin_dir . 'includes/classes/class.api_key.php';
            // Load api keys class
            require_once $this->plugin_dir . 'includes/classes/class.api_keys.php';
            // Load api keys search class
            require_once $this->plugin_dir . 'includes/classes/class.api_keys_search.php';
            // Load ticket class
            require_once $this->plugin_dir . 'includes/classes/class.ticket.php';
            // Load tickets class
            require_once $this->plugin_dir . 'includes/classes/class.tickets.php';
            // Load ticket instance class
            require_once $this->plugin_dir . 'includes/classes/class.ticket_instance.php';
            // Load tickets instances class
            require_once $this->plugin_dir . 'includes/classes/class.tickets_instances.php';
            // Load tickets instances search class
            require_once $this->plugin_dir . 'includes/classes/class.tickets_instances_search.php';
            // Load tickets search class
            require_once $this->plugin_dir . 'includes/classes/class.tickets_search.php';
            // Load order class
            require_once $this->plugin_dir . 'includes/classes/class.order.php';
            // Load orders class
            require_once $this->plugin_dir . 'includes/classes/class.orders.php';
            // Load orders search class
            require_once $this->plugin_dir . 'includes/classes/class.orders_search.php';
            // Load discount class
            require_once $this->plugin_dir . 'includes/classes/class.discount.php';
            // Load discounts class
            require_once $this->plugin_dir . 'includes/classes/class.discounts.php';
            // Load discounts search class
            require_once $this->plugin_dir . 'includes/classes/class.discounts_search.php';
            // Load template class
            require_once $this->plugin_dir . 'includes/classes/class.ticket_template.php';
            // Load templates class
            if ( defined( 'TC_DEV' ) ) {
                require_once $this->plugin_dir . 'includes/classes/class.ticket_templates-new.php';
            } else {
                require_once $this->plugin_dir . 'includes/classes/class.ticket_templates.php';
            }
            // Load templates search class
            require_once $this->plugin_dir . 'includes/classes/class.ticket_templates_search.php';
            // Load admin pagination class
            require_once $this->plugin_dir . 'includes/classes/class.pagination.php';
            // Load general functions
            require_once $this->plugin_dir . 'includes/classes/class.shortcodes.php';
            // Load general settings class
            require_once $this->plugin_dir . 'includes/classes/class.settings_general.php';
            // Load email settings class
            require_once $this->plugin_dir . 'includes/classes/class.settings_email.php';
            require_once $this->plugin_dir . 'includes/classes/class.shortcode_builder.php';
            require_once $this->plugin_dir . 'includes/classes/class.beaver_shortcodes.php';
            require_once $this->plugin_dir . 'includes/classes/class.elementor_shortcodes.php';
            require_once $this->plugin_dir . 'includes/classes/class.divi_shortcodes.php';
            // Load WP KSES Hook for Tickera
            require_once $this->plugin_dir . 'includes/classes/class.kses.php';
            // Loading config first
            if ( defined( 'TICKET_PLUGIN_TITLE' ) ) {
                $this->title = TICKET_PLUGIN_TITLE;
            }
            if ( defined( 'TICKET_PLUGIN_NAME' ) ) {
                $this->name = TICKET_PLUGIN_NAME;
            }
            if ( defined( 'TICKET_PLUGIN_DIR_NAME' ) ) {
                $this->plugin_dir = TICKET_PLUGIN_DIR_NAME;
            }
            $this->admin_menu_position = (int) apply_filters( 'tc_menu_position', 1000 );
            $this->title = apply_filters( 'tc_plugin_title', $this->title );
            $this->name = apply_filters( 'tc_plugin_name', $this->name );
            $this->plugin_dir = apply_filters( 'tc_plugin_dir', $this->plugin_dir );
            // Admin css and scripts
            add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts_styles') );
            // Add plugin admin menu
            add_action( 'admin_menu', array($this, 'add_admin_menu') );
            // Load add-ons
            add_action( 'plugins_loaded', array($this, 'load_addons'), 9 );
            // Add plugin newtork admin menu
            add_action( 'network_admin_menu', array($this, 'add_network_admin_menu') );
            // Add plugin Settings link
            add_filter(
                'plugin_action_links_' . plugin_basename( __FILE__ ),
                array($this, 'plugin_action_link'),
                10,
                2
            );
            // Localize the plugin
            add_action( 'plugins_loaded', array($this, 'localization'), 8 );
            // Payment gateway returns
            add_action( 'template_redirect', array($this, 'handle_gateway_returns'), 1 );
            // Add additional rewrite rules
            add_filter( 'rewrite_rules_array', array($this, 'add_rewrite_rules') );
            // Add additional query vars
            add_filter( 'query_vars', array($this, 'filter_query_vars') );
            // Parse requests
            add_action( 'parse_request', array($this, 'action_parse_request') );
            // Create virtual pages
            require_once $this->plugin_dir . 'includes/classes/class.virtualpage.php';
            // Include Visual Composer shortcode
            // require_once $this->plugin_dir . 'includes/classes/class.visual_composer_shortcodes.php';
            // Register post types
            add_action( 'init', array($this, 'register_custom_posts'), 0 );
            add_action( 'admin_init', array($this, 'generate_ticket_preview'), 11 );
            add_action( 'init', array($this, 'checkin_api'), 0 );
            add_action( 'init', array($this, 'sales_api'), 0 );
            add_action( 'template_redirect', array($this, 'load_cart_scripts') );
            add_action( 'template_redirect', array($this, 'non_visible_post_types_404') );
            add_action( 'template_redirect', array($this, 'maybe_cancel_order') );
            add_action( 'admin_post_tickera_cart', array($this, 'update_cart') );
            add_action( 'admin_post_nopriv_tickera_cart', array($this, 'update_cart') );
            add_action( 'wp_loaded', array($this, 'update_cart') );
            add_action( 'wp_enqueue_scripts', array($this, 'front_scripts_styles') );
            add_action( 'wp_ajax_get_ticket_type_instances', array($this, 'tc_get_ticket_type_instances') );
            add_action( 'wp_ajax_nopriv_add_to_cart', array($this, 'add_to_cart') );
            add_action( 'wp_ajax_add_to_cart', array($this, 'add_to_cart') );
            add_action( 'wp_ajax_nopriv_update_cart_widget', array(&$this, 'update_cart_widget') );
            add_action( 'wp_ajax_update_cart_widget', array($this, 'update_cart_widget') );
            add_action( 'wp_ajax_change_order_status', array($this, 'change_order_status_ajax') );
            add_action( 'wp_ajax_change_event_status', array($this, 'change_event_status') );
            add_action( 'wp_ajax_change_ticket_status', array($this, 'change_ticket_status') );
            add_action( 'wp_ajax_change_apikey_event_category', array($this, 'change_apikey_event_category') );
            add_action( 'wp_ajax_save_attendee_info', array($this, 'save_attendee_info') );
            add_action( 'wp_ajax_tc_update_widget_cart', 'tickera_update_widget_cart' );
            add_action( 'wp_ajax_nopriv_tc_update_widget_cart', 'tickera_update_widget_cart' );
            add_filter( 'tc_cart_currency_and_format', array($this, 'get_cart_currency_and_format') );
            // Common Scripts and Styles
            add_action( 'wp_enqueue_scripts', array($this, 'common_scripts_styles') );
            add_action( 'admin_enqueue_scripts', array($this, 'common_scripts_styles') );
            // Set Cookie Fallback
            add_action( 'wp_ajax_tc_remove_order_session_data', array($this, 'ajax_remove_order_session_data') );
            add_action( 'wp_ajax_nopriv_tc_remove_order_session_data', array($this, 'ajax_remove_order_session_data') );
            add_action( 'wp_ajax_tc_remove_order_session_data_only', array($this, 'ajax_remove_order_session_data_only') );
            add_action( 'wp_ajax_nopriv_tc_remove_order_session_data_only', array($this, 'ajax_remove_order_session_data_only') );
            // register_activation_hook( __FILE__, array( $this, 'activation' ) );
            add_action( 'admin_init', array($this, 'activation') );
            add_filter(
                'tc_order_confirmation_message_content',
                array($this, 'tc_order_confirmation_message_content'),
                10,
                2
            );
            add_filter( 'tc_editable_quantity_payments_page', array($this, 'tc_change_editable_qty'), 10 );
            add_action( 'admin_notices', array($this, 'admin_permalink_message') );
            add_action( 'admin_notices', array($this, 'admin_debug_notices_message') );
            add_action( 'tc_before_cart_submit', array($this, 'tc_add_age_check') );
            add_action( 'tc_before_payment', array($this, 'tc_show_summary') );
            // Display Admin notices
            add_filter(
                'tc_admin_notices',
                array($this, 'admin_dashboard_notification'),
                10,
                2
            );
            // Set Tickera Setting Default values based on active addons
            add_action( 'admin_init', array($this, 'tc_default_values') );
            // Show cart menu. Tickera > Settings > General
            add_action( 'init', array($this, 'show_cart_menu_handler') );
            add_filter(
                'comments_open',
                array($this, 'comments_open'),
                10,
                2
            );
            add_filter( 'comments_template', array($this, 'no_comments_template') );
            // Load cart widget
            require_once $this->plugin_dir . 'includes/widgets/cart-widget.php';
            require_once $this->plugin_dir . 'includes/widgets/upcoming-events-widget.php';
            add_action( 'admin_init', array($this, 'generate_pdf_ticket'), 0 );
            add_action( 'init', array($this, 'generate_pdf_ticket_front'), 11 );
            add_action( 'admin_print_styles', array($this, 'add_notices') );
            add_action( 'admin_init', array($this, 'install_actions') );
            add_filter( 'wp_get_nav_menu_items', array($this, 'remove_unnecessary_plugin_menu_items') );
            add_filter( 'wp_page_menu_args', array($this, 'remove_unnecessary_plugin_menu_items_wp_page_menu_args') );
            add_action( 'pre_get_posts', array($this, 'tc_events_front_page') );
            add_action( 'admin_init', array($this, 'add_required_capabilities') );
            add_action( 'tc_delete_plugins_data', array($this, 'tc_delete_plugins_data') );
            add_action( 'wp_ajax_tc_delete_tickets', array($this, 'tc_delete_tickets') );
            add_action( 'admin_notices', array($this, 'bridge_admin_notice') );
            add_action( 'wp_ajax_tc_remove_notification', array($this, 'tc_remove_notification') );
            add_action( 'wp_ajax_nopriv_tc_remove_notification', array($this, 'tc_remove_notification') );
            // Remove theme notification in Themes page
            add_action( 'wp_ajax_tc_remove_notification_theme', array($this, 'tc_remove_notification_theme_ajax') );
            add_action( 'wp_ajax_nopriv_tc_remove_notification_theme', array($this, 'tc_remove_notification_theme_ajax') );
            add_action( 'admin_init', array($this, 'tc_remove_notification_theme') );
            // Manage Tickera and add-ons dependency structure.
            if ( version_compare( get_bloginfo( 'version' ), '6.5', '>=' ) ) {
                add_filter( 'wp_plugin_dependencies_slug', array($this, 'set_dependencies_slug') );
            }
            add_action( 'wp_loaded', array($this, 'update_option_names') );
        }

        /**
         * Apply tickera_ prefix across Tickera options
         * @since 3.5.3.6
         */
        function update_option_names() {
            $old_prefix = 'tc_';
            $new_prefix = 'tickera_';
            $option_names = [
                'tc_settings',
                'tc_version',
                'tc_wizard_mode',
                'tc_wizard_step',
                'tc_general_setting',
                'tc_cart_page_id',
                'tc_payment_page_id',
                'tc_confirmation_page_id',
                'tc_order_page_id',
                'tc_process_payment_page_id',
                'tc_ipn_page_id',
                'tc_needs_pages',
                'tc_email_setting',
                'tc_ipn_use_virtual',
                'tc_process_payment_use_virtual',
                'tc_network_settings_admin',
                'tc_network_settings'
            ];
            foreach ( $option_names as $name ) {
                $clean = str_replace( [$old_prefix, $new_prefix], '', $name );
                $new = $new_prefix . $clean;
                $data = get_option( $new );
                if ( $data == '' || is_null( $data ) ) {
                    $deprecated = get_option( $old_prefix . $clean );
                    if ( $deprecated != '' && !is_null( $deprecated ) ) {
                        add_option( $new, tickera_sanitize_array( $deprecated, true, true ) );
                    }
                }
            }
        }

        /**
         * Set Tickera dependencies slug for free and premium versions.
         * Executes on Wordpress version 6.5 onwards.
         *
         * @param $slug
         * @return string
         * @since 3.5.3.0
         */
        function set_dependencies_slug( $slug ) {
            if ( $slug == \Tickera\tets_fs()->get_slug() ) {
                $slug = $this->dir_name;
            }
            return $slug;
        }

        function tc_remove_notification() {
            setcookie(
                'tc_bridge_notifications',
                'true',
                time() + 86400 * 30,
                "/"
            );
            exit;
        }

        function tc_remove_notification_theme_ajax() {
            setcookie(
                'tc_themes_notifications',
                'true',
                time() + 86400 * 30,
                "/"
            );
            exit;
        }

        /**
         *
         * @since 3.5.4.4
         */
        function tc_remove_notification_theme() {
            global $pagenow;
            if ( $pagenow && 'themes.php' == $pagenow ) {
                $theme = wp_get_theme();
                $author = $theme->get( 'Author' );
                $authorUri = $theme->get( 'AuthorURI' );
                if ( 'Themetick' == $author || 'https://themetick.com' == $authorUri ) {
                    setcookie(
                        'tc_themes_notifications',
                        'true',
                        time() + 86400 * 30,
                        "/"
                    );
                } else {
                    setcookie(
                        'tc_themes_notifications',
                        '',
                        time() + 86400 * 30,
                        "/"
                    );
                }
            }
        }

        /**
         * Delete directories and files
         * @param $dir
         * @throws \Exception
         */
        public static function rrmdir( $dir ) {
            if ( is_dir( $dir ) ) {
                $objects = scandir( $dir );
                $sanitized_objects = tickera_sanitize_array( $objects, false, true );
                foreach ( $sanitized_objects as $object ) {
                    if ( "." != $object && ".." != $object ) {
                        if ( "dir" == filetype( $dir . "/" . $object ) ) {
                            @rrmdir( $dir . "/" . $object );
                        } else {
                            @unlink( $dir . "/" . $object );
                        }
                    }
                }
                @reset( $sanitized_objects );
                @rmdir( $dir );
            }
        }

        /**
         * Generate admin notices
         *
         * @param null $message
         * @param string $notice_type
         * @return null
         */
        function admin_dashboard_notification( $message = null, $notice_type = 'notice-info' ) {
            if ( !$message ) {
                return null;
            }
            $notice_type = sanitize_html_class( $notice_type );
            $html = '<div class="notice ' . esc_attr( $notice_type ) . ' is-dismissible tc-admin-notice" style="display:none;">';
            $html .= '<p>' . wp_kses_post( $message ) . '</p>';
            $html .= '</div>';
            echo wp_kses_post( $html );
        }

        /**
         * Set Plugin Settings Default values on addons activation/deactivation
         */
        function tc_default_values() {
            $tc_active_plugins = get_option( 'active_plugins' );
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            // Tickera Settings: Deactivate "Show Cart Menu" & "Force Login" when "Bridge for Woocommerce" is enabled.
            if ( in_array( 'bridge-for-woocommerce/bridge-for-woocommerce.php', $tc_active_plugins ) ) {
                $show_cart_menu_item = ( isset( $tc_general_settings['show_cart_menu_item'] ) ? $tc_general_settings['show_cart_menu_item'] : '' );
                $force_login = ( isset( $tc_general_settings['force_login'] ) ? $tc_general_settings['force_login'] : '' );
                if ( $show_cart_menu_item || $force_login ) {
                    $tc_general_settings['show_cart_menu_item'] = 'no';
                    $tc_general_settings['force_login'] = 'no';
                    update_option( 'tickera_general_setting', array_map( 'sanitize_text_field', $tc_general_settings ) );
                }
            }
        }

        /**
         * Handles Show Cart Menu in frontend
         * Setting: Tickera > Settings > General > Menu
         * Mode: Tickera Standalone
         *
         * @since 3.5.2.2
         */
        function show_cart_menu_handler() {
            $tc_active_plugins = get_option( 'active_plugins' );
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            if ( !in_array( 'bridge-for-woocommerce/bridge-for-woocommerce.php', $tc_active_plugins ) ) {
                if ( isset( $tc_general_settings['show_cart_menu_item'] ) && 'yes' == $tc_general_settings['show_cart_menu_item'] ) {
                    add_filter(
                        'wp_nav_menu_objects',
                        array($this, 'main_navigation_links'),
                        10,
                        2
                    );
                    $theme_location = 'primary';
                    if ( !$theme_location ) {
                        $theme_locations = get_nav_menu_locations();
                        foreach ( (array) $theme_locations as $key => $location ) {
                            $theme_location = $key;
                            break;
                        }
                    }
                    if ( !has_nav_menu( $theme_location ) ) {
                        add_filter(
                            'wp_page_menu',
                            array(&$this, 'main_navigation_links_fallback'),
                            20,
                            2
                        );
                    }
                }
            }
        }

        /**
         * Check for a specific payment gateway alternative versions
         *
         * @param string $gateway_name
         * @param string $notice_type
         * @return null
         */
        function tc_payment_gateway_alternative( $gateway_name, $notice_type = 'notice-info' ) {
            global $tc_gateway_plugins, $tc;
            if ( !$gateway_name ) {
                return false;
            }
            $active_gateways = $tc->get_setting( 'gateways->active', array() );
            $available_gateways = array_keys( (array) $tc_gateway_plugins );
            // Check active plugins only
            if ( !in_array( $gateway_name, $active_gateways ) ) {
                return false;
            }
            // Collect Related Gateway
            $alternative_gateway = [];
            $gateway_name_root = explode( '-', $gateway_name )[0];
            foreach ( $available_gateways as $key ) {
                if ( strpos( $key, $gateway_name_root ) !== false && $gateway_name != $key ) {
                    $alternative_gateway[] = $key;
                }
            }
            $pos = array_search( $gateway_name, $alternative_gateway );
            if ( $pos ) {
                unset($alternative_gateway[$pos]);
            }
            if ( in_array( reset( $alternative_gateway ), $active_gateways ) ) {
                return null;
            }
            if ( $alternative_gateway && in_array( $gateway_name, $active_gateways ) ) {
                foreach ( $alternative_gateway as $val ) {
                    $message = sprintf( 
                        /* translators: %s: Alternative payment method name.  */
                        __( 'Tickera <b>%s</b> is now available. Please activate it via <a href="edit.php?post_type=tc_events&page=tc_settings&tab=gateways">Tickera > Settings > Payment Gateways Tab</a>', 'tickera-event-ticketing-system' ),
                        ucfirst( str_replace( '_', ' ', $val ) )
                     );
                    apply_filters( 'tc_admin_notices', $message, $notice_type );
                }
            }
        }

        /**
         * Calculate Individual Ticket Totals of an Order
         *
         * @param object $order
         * @param array $cart_contents
         * @param null $tc_ticket_discount
         * @return array|null
         */
        function tc_calculate_individual_ticket_totals( $order, $cart_contents, $tc_ticket_discount = null ) {
            global $tc;
            if ( !$order || !$cart_contents ) {
                return null;
            }
            // Collect Order Objects
            if ( !is_object( $order ) ) {
                $order = new \Tickera\TC_Order($order);
            }
            // Collect Discount Object and Metas
            $discounts = new \Tickera\TC_Discounts();
            $discount_code = $order->details->tc_discount_code;
            $discount = new \Tickera\TC_Discount();
            $discount_object = $discount->get_discount_by_code( $discount_code );
            $discount_values = false;
            if ( $discount_object ) {
                $discount_details = new \Tickera\TC_Discount($discount_object->ID);
                $discount_values = $discounts->calculate_tickets_discount( $cart_contents, $discount_details, $tc_ticket_discount );
            }
            // Identify if tax inclusive
            $tax_inclusive = tickera_is_tax_inclusive();
            // Collect Fee Metas
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $global_fees = ( isset( $tc_general_settings['use_global_fees'] ) ? $tc_general_settings['use_global_fees'] : 'no' );
            $global_fee_type = ( isset( $tc_general_settings['global_fee_type'] ) ? $tc_general_settings['global_fee_type'] : 'fixed' );
            $global_fee_scope = ( isset( $tc_general_settings['global_fee_scope'] ) ? $tc_general_settings['global_fee_scope'] : 'ticket' );
            $global_fee_value = ( isset( $tc_general_settings['global_fee_value'] ) ? $tc_general_settings['global_fee_value'] : 0 );
            $overall_ordered_count = array_sum( $cart_contents );
            $data = [];
            foreach ( $cart_contents as $ticket_type_id => $ordered_count ) {
                $ticket = new \Tickera\TC_Ticket($ticket_type_id);
                for ($x = 0; $x < $ordered_count; $x++) {
                    // Ticket Subtotal
                    $ticket_subtotal = tickera_get_ticket_price( $ticket_type_id );
                    // Ticket Discount
                    $discount_value = ( $discount_values ? $discount_values[$ticket_type_id][$x] : 0 );
                    // Ticket Fee
                    $ticket_fee = 0;
                    if ( 'yes' == $global_fees && $global_fee_value ) {
                        // Global based Ticket Fee
                        $ticket_fee = ( 'order' == $global_fee_scope ? $global_fee_value / $overall_ordered_count : $global_fee_value );
                    } elseif ( 'no' == $global_fees ) {
                        // Product based Ticket Fee
                        $ticket_fee = ( $ticket->details->ticket_fee ? $ticket->details->ticket_fee : 0 );
                    }
                    $session_cart_subtotal_pre = $this->session->get( 'cart_subtotal_pre' );
                    $ticket_fee_type = ( 'yes' == $global_fees ? $global_fee_type : $ticket->details->ticket_fee_type );
                    $ticket_subtotal_after = ( 'yes' == $global_fees && 'order' == $global_fee_scope ? $session_cart_subtotal_pre : tickera_get_ticket_price( $ticket_type_id ) );
                    if ( $ticket_fee && 'percentage' == $ticket_fee_type ) {
                        $ticket_fee = $ticket_fee / 100 * $ticket_subtotal_after;
                    }
                    // Ticket Tax
                    $tax_before_fees = ( isset( $tc_general_settings['tax_before_fees'] ) && $tc_general_settings['tax_before_fees'] ? $tc_general_settings['tax_before_fees'] : 'no' );
                    $ticket_subtotal_after = ( 'no' == $tax_before_fees ? $ticket_subtotal + $ticket_fee - $discount_value : $ticket_subtotal - $discount_value );
                    $ticket_tax = ( $tax_inclusive ? $ticket_subtotal_after - $ticket_subtotal_after / ($tc->get_tax_value() / 100 + 1) : $tc->get_tax_value() / 100 * $ticket_subtotal_after );
                    // Ticket Total
                    $ticket_total = ( 'no' == $tax_before_fees ? ( $tax_inclusive ? $ticket_subtotal_after : $ticket_subtotal_after + $ticket_tax ) : (( $tax_inclusive ? $ticket_subtotal_after : $ticket_subtotal_after + $ticket_fee + $ticket_tax )) );
                    $data['ticket_subtotal_post_meta'][$ticket_type_id][] = $ticket_subtotal;
                    $data['ticket_discount_post_meta'][$ticket_type_id][] = $discount_value;
                    $data['ticket_fee_post_meta'][$ticket_type_id][] = $ticket_fee;
                    $data['ticket_tax_post_meta'][$ticket_type_id][] = $ticket_tax;
                    $data['ticket_total_post_meta'][$ticket_type_id][] = $ticket_total;
                }
            }
            return $data;
        }

        /**
         * @return bool
         */
        function tc_change_editable_qty() {
            return false;
        }

        /**
         * Display Cart Summary Table
         *
         * @param array $cart_contents
         */
        function tc_show_summary( $cart_contents ) {
            /**
             * Initialize global variables for cart totals.
             */
            global $total_fees, $tax_value, $subtotal_value;
            $total_fees = 0;
            $tax_value = 0;
            $subtotal_value = 0;
            $discount = new \Tickera\TC_Discounts();
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $session = $this->session->get();
            if ( isset( $session['tc_cart_subtotal'] ) && isset( $session['tc_discount_code'] ) ) {
                $discount->discounted_cart_total( (float) $session['tc_cart_subtotal'], sanitize_text_field( $session['tc_discount_code'] ) );
            } elseif ( isset( $session['tc_discount_code'] ) ) {
                $discount->discounted_cart_total( false, sanitize_text_field( $session['tc_discount_code'] ) );
            }
            $tc_show_close = true;
            if ( true == apply_filters( 'tc_show_summary', true ) ) {
                ?>
                <div class="tickera-checkout">
                    <h3><?php 
                esc_html_e( 'Payment Summary', 'tickera-event-ticketing-system' );
                ?></h3>
                    <table cellspacing="0" class="tickera_table" cellpadding="10">
                        <thead>
                        <tr>
                            <?php 
                do_action( 'tc_cart_col_title_before_ticket_type' );
                ?>
                            <th><?php 
                esc_html_e( 'Ticket Type', 'tickera-event-ticketing-system' );
                ?></th>
                            <?php 
                do_action( 'tc_cart_col_title_before_ticket_price' );
                ?>
                            <th class="ticket-price-header"><?php 
                esc_html_e( 'Ticket Price', 'tickera-event-ticketing-system' );
                ?></th>
                            <?php 
                do_action( 'tc_cart_col_title_before_quantity' );
                ?>
                            <th><?php 
                esc_html_e( 'Quantity', 'tickera-event-ticketing-system' );
                ?></th>
                            <?php 
                do_action( 'tc_cart_col_title_before_total_price' );
                ?>
                            <th><?php 
                esc_html_e( 'Subtotal', 'tickera-event-ticketing-system' );
                ?></th>
                            <?php 
                do_action( 'tc_cart_col_title_after_total_price' );
                ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php 
                $cart_subtotal = 0;
                foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                    $ticket = new \Tickera\TC_Ticket($ticket_type);
                    if ( !empty( $ticket->details->post_title ) && ('tc_tickets' == get_post_type( $ticket_type ) || 'product' == get_post_type( $ticket_type )) ) {
                        // Sum of cart's tickets subtotal
                        $cart_subtotal = $cart_subtotal + tickera_get_ticket_price( $ticket->details->ID ) * $ordered_count;
                        // Used to calculate discount and individual ticket's total values
                        $this->session->set( 'cart_subtotal_pre', $cart_subtotal );
                        // Allow developer to disable quantity selector
                        $editable_qty = (bool) apply_filters(
                            'tc_editable_quantity_payments_page',
                            true,
                            $ticket_type,
                            $ordered_count
                        );
                        // Used to calculate fee and tax. Preserve the value even when tc_cart shortcode is being rendered multiple times. Currently used in internal-hooks.php
                        $subtotal_value = $cart_subtotal;
                        ?>
                                <tr>
                                    <?php 
                        do_action(
                            'tc_cart_col_value_before_ticket_type',
                            $ticket_type,
                            $ordered_count,
                            tickera_get_ticket_price( $ticket->details->ID )
                        );
                        ?>
                                    <td class="ticket-type"><?php 
                        echo esc_html( apply_filters( 'tc_cart_col_before_ticket_name', $ticket->details->post_title, $ticket->details->ID ) );
                        ?> <?php 
                        do_action( 'tc_cart_col_after_ticket_type', $ticket, $tc_show_close );
                        ?>
                                        <input type="hidden" name="ticket_cart_id[]" value="<?php 
                        echo esc_attr( (int) $ticket_type );
                        ?>">
                                    </td>
                                    <?php 
                        do_action(
                            'tc_cart_col_value_before_ticket_price',
                            $ticket_type,
                            $ordered_count,
                            tickera_get_ticket_price( $ticket->details->ID )
                        );
                        ?>
                                    <td class="ticket-price">
                                        <span class="ticket_price"><?php 
                        echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_price_per_ticket', tickera_get_ticket_price( $ticket->details->ID ), $ticket_type ) ) );
                        ?></span>
                                    </td>
                                    <?php 
                        do_action(
                            'tc_cart_col_value_before_quantity',
                            $ticket_type,
                            $ordered_count,
                            tickera_get_ticket_price( $ticket->details->ID )
                        );
                        ?>
                                    <td class="ticket-quantity ticket_quantity"><?php 
                        echo esc_html( ( $editable_qty ? '' : (int) $ordered_count ) );
                        ?>
                                        <?php 
                        if ( $editable_qty ) {
                            ?>
                                            <input class="tickera_button minus" type="button" value="-">
                                        <?php 
                        }
                        ?>
                                        <input type="<?php 
                        echo esc_attr( ( $editable_qty ? 'text' : 'hidden' ) );
                        ?>" name="ticket_quantity[]" value="<?php 
                        echo esc_attr( (int) $ordered_count );
                        ?>" class="quantity" autocomplete="off">
                                        <?php 
                        if ( $editable_qty ) {
                            ?>
                                            <input class="tickera_button plus" type="button" value="+"/>
                                        <?php 
                        }
                        ?>
                                    </td>
                                    <?php 
                        do_action(
                            'tc_cart_col_value_before_total_price',
                            $ticket_type,
                            $ordered_count,
                            tickera_get_ticket_price( $ticket->details->ID )
                        );
                        ?>
                                    <td class="ticket-total">
                                        <span class="ticket_total"><?php 
                        echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters(
                            'tc_cart_price_per_ticket_and_quantity',
                            tickera_get_ticket_price( $ticket->details->ID ) * $ordered_count,
                            $ticket_type,
                            $ordered_count
                        ) ) );
                        ?></span>
                                    </td>
                                    <?php 
                        do_action(
                            'tc_cart_col_value_after_total_price',
                            $ticket_type,
                            $ordered_count,
                            tickera_get_ticket_price( $ticket->details->ID )
                        );
                        ?>
                                </tr>
                            <?php 
                    }
                    ?>
                        <?php 
                }
                ?>
                        <tr class="last-table-row">
                            <td class="ticket-total-all" colspan="<?php 
                echo esc_attr( (int) apply_filters( 'tc_cart_table_colspan', '5' ) );
                ?>">
                                <?php 
                do_action( 'tc_cart_col_value_before_total_price_subtotal', apply_filters( 'tc_cart_subtotal', $cart_subtotal ) );
                ?>
                                <div>
                                    <span class="total_item_title"><?php 
                esc_html_e( 'SUBTOTAL: ', 'tickera-event-ticketing-system' );
                ?></span><span class="total_item_amount"><?php 
                echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_subtotal', $cart_subtotal ) ) );
                ?></span>
                                </div>
                                <?php 
                do_action( 'tc_cart_col_value_before_total_price_discount', apply_filters( 'tc_cart_discount', 0 ) );
                ?>
                                <?php 
                if ( !isset( $tc_general_settings['show_discount_field'] ) || isset( $tc_general_settings['show_discount_field'] ) && 'yes' == $tc_general_settings['show_discount_field'] ) {
                    ?>
                                <div>
                                    <span class="total_item_title"><?php 
                    esc_html_e( 'DISCOUNT: ', 'tickera-event-ticketing-system' );
                    ?></span><span class="total_item_amount"><?php 
                    echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_discount', 0 ) ) );
                    ?></span><?php 
                }
                ?>
                                </div>
                                <?php 
                do_action( 'tc_cart_col_value_before_total_price_total', apply_filters( 'tc_cart_total', $cart_subtotal ) );
                ?>
                                <div>
                                    <span class="total_item_title cart_total_price_title"><?php 
                esc_html_e( 'TOTAL: ', 'tickera-event-ticketing-system' );
                ?></span><span class="total_item_amount cart_total_price"><?php 
                echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_total', $cart_subtotal ) ) );
                ?></span>
                                </div>
                                <?php 
                do_action( 'tc_cart_col_value_after_total_price_total' );
                ?>
                            </td>
                            <?php 
                do_action( 'tc_cart_col_value_after_total_price_total' );
                ?>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <?php 
            }
        }

        /**
         * @param $submitted_data
         */
        function tc_delete_plugins_data( $submitted_data ) {
            $submitted_data = tickera_sanitize_array( $submitted_data, false, true );
            if ( array_key_exists( 'tickera', $submitted_data ) ) {
                global $wpdb;
                // Delete posts and post metas
                $post_types = [
                    'tc_events',
                    'tc_tickets',
                    'tc_api_keys',
                    'tc_tickets_instances',
                    'tc_templates',
                    'tc_orders',
                    'tc_discounts'
                ];
                $prepare_post_types_placeholder = implode( ",", array_fill( 0, count( $post_types ), '%s' ) );
                $associated_posts = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ({$prepare_post_types_placeholder})", $post_types ) );
                foreach ( $associated_posts as $post_id ) {
                    wp_delete_post( $post_id, true );
                }
                // Delete options
                $options = array(
                    'tickera_wizard_step',
                    'tickera_wizard_mode',
                    'tickera_email_setting',
                    'tickera_settings',
                    'tickera_general_setting',
                    'tickera_cart_page_id',
                    'tickera_payment_page_id',
                    'tickera_confirmation_page_id',
                    'tickera_order_page_id',
                    'tickera_process_payment_page_id',
                    'tickera_process_payment_use_virtual',
                    'tickera_ipn_page_id',
                    'tickera_ipn_use_virtual',
                    'tickera_needs_pages',
                    'tickera_version'
                );
                foreach ( $options as $option ) {
                    delete_option( $option );
                }
                // Delete directories and files
                $upload = wp_upload_dir();
                $upload_dir = $upload['basedir'];
                $upload_dir = $upload_dir . '/sessions';
                TC::rrmdir( $upload_dir );
            }
        }

        /**
         * Process Bulk Deletion of Tickets
         * Tickera > Settings > Delete Info > Bulk Delete Tickets
         * @since 3.5.2.3
         *
         * Rename "tc_dl_delete_tickets function" to "tc_delete_tickets"
         * Rename filter hook "tc_dl_post_per_page" to "tc_delete_tickets_post_per_page"
         * Applied nonce
         * @since 3.5.2.9
         */
        function tc_delete_tickets() {
            if ( $_POST && isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) && current_user_can( 'manage_options' ) ) {
                $page = ( isset( $_POST['page'] ) ? (int) $_POST['page'] : 1 );
                $post_per_page = apply_filters( 'tc_delete_tickets_post_per_page', 20 );
                $delete_orders = ( isset( $_POST['delete_orders'] ) ? sanitize_key( $_POST['delete_orders'] ) : 'no' );
                $events_ids = ( isset( $_POST['event_ids'] ) ? array_map( 'absint', $_POST['event_ids'] ) : [] );
                $prev_deleted = ( isset( $_POST['prev_deleted'] ) ? (int) $_POST['prev_deleted'] : 0 );
                if ( $events_ids ) {
                    // Collection of Attendee's Tickets
                    $ticket_instances = get_posts( [
                        'post_type'      => 'tc_tickets_instances',
                        'post_status'    => 'any',
                        'meta_query'     => [
                            'relation' => 'AND',
                            [
                                'key'     => 'event_id',
                                'value'   => $events_ids,
                                'compare' => 'IN',
                            ],
                        ],
                        'fields'         => 'ids',
                        'paged'          => 1,
                        'posts_per_page' => $post_per_page,
                    ] );
                    if ( $ticket_instances ) {
                        // Delete Attendee's Tickets
                        foreach ( $ticket_instances as $ticket_instance_id ) {
                            $order_id = wp_get_post_parent_id( $ticket_instance_id );
                            if ( 'yes' == $delete_orders ) {
                                if ( $order_id && get_post( $order_id ) ) {
                                    $associated_tickets = get_posts( [
                                        'post_type'      => 'tc_tickets_instances',
                                        'post_parent'    => $order_id,
                                        'fields'         => 'ids',
                                        'posts_per_page' => -1,
                                    ] );
                                    $prev_deleted = $prev_deleted + count( $associated_tickets );
                                    wp_delete_post( $order_id );
                                } else {
                                    if ( get_post( $ticket_instance_id ) ) {
                                        $prev_deleted++;
                                        wp_delete_post( $ticket_instance_id );
                                    }
                                }
                            } else {
                                if ( get_post( $ticket_instance_id ) ) {
                                    $prev_deleted++;
                                    wp_delete_post( $ticket_instance_id );
                                }
                            }
                        }
                        $resposne = [];
                        $resposne['page'] = $page;
                        $resposne['deleted'] = $prev_deleted;
                        wp_send_json( $resposne );
                    }
                }
            }
            wp_send_json( [] );
        }

        /**
         * Render Age Confirmation Checkbox
         */
        function tc_add_age_check() {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $tc_age_check = ( isset( $tc_general_settings['show_age_check'] ) ? $tc_general_settings['show_age_check'] : 'no' );
            if ( 'yes' == $tc_age_check ) {
                $tc_age_text = ( isset( $tc_general_settings['age_text'] ) ? $tc_general_settings['age_text'] : __( 'I hereby declare that I am 16 years or older', 'tickera-event-ticketing-system' ) );
                echo wp_kses_post( sprintf( 
                    /* translators: %s: Age */
                    __( '<label class="tc-age-check-label"><input type="checkbox" id="tc_age_check" class="tc_age_check"/> %s</label>', 'tickera-event-ticketing-system' ),
                    esc_html( $tc_age_text )
                 ) );
            }
        }

        /**
         * @param $query
         */
        function tc_events_front_page( $query ) {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $show_events_as_front_page = ( isset( $tc_general_settings['show_events_as_front_page'] ) ? $tc_general_settings['show_events_as_front_page'] : 'no' );
            if ( $show_events_as_front_page == 'no' || 'posts' !== get_option( 'show_on_front' ) ) {
                return $query;
                //do not modify the query
            }
            // Only filter the main query on the front-end
            if ( is_admin() || !$query->is_main_query() ) {
                return;
            }
            global $wp;
            $front = false;
            /*
             * If the latest posts are showing on the home page
             * Previous condition: if ( ( is_home() && empty( $wp->query_string ) ) )
             */
            if ( is_home() || is_front_page() && empty( $wp->query_string ) ) {
                $front = true;
            }
            // If a static page is set as the home page
            if ( $query->get( 'page_id' ) == get_option( 'page_on_front' ) && get_option( 'page_on_front' ) || empty( $wp->query_string ) ) {
                $front = true;
            }
            if ( $front ) {
                $query->set( 'post_type', array('tc_events', '') );
                $query->set( 'page_id', '' );
                // Set properties to match an archive
                $query->is_page = 0;
                $query->is_singular = 0;
                $query->is_post_type_archive = 1;
                $query->is_archive = 1;
            }
        }

        function maybe_make_writtable_tcpdf_directory() {
            try {
                $tcpdf_cache_directory = $this->plugin_dir . 'includes/tcpdf/cache/';
                if ( !@is_writable( $tcpdf_cache_directory ) ) {
                    if ( !is_dir( $tcpdf_cache_directory ) ) {
                        @mkdir( $tcpdf_cache_directory, 0755 );
                    } else {
                        @chmod( $tcpdf_cache_directory, 0755 );
                    }
                    $filename = '.htaccess';
                    $path = $tcpdf_cache_directory . '/' . $filename;
                    if ( !file_exists( $path ) ) {
                        $htaccess = @fopen( $path, "w" );
                        $content = "Deny from all";
                        @fwrite( $htaccess, $content );
                        @fclose( $htaccess );
                        @chmod( $path, 0644 );
                    }
                }
            } catch ( Exception $e ) {
                // TCPDF directory cannot be created or permissions cannot set to 0777
            }
        }

        function maybe_set_session_path() {
            $session_save_path = session_save_path();
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $create_and_force_new_session_path = ( isset( $tc_general_settings['create_and_force_new_session_path'] ) ? $tc_general_settings['create_and_force_new_session_path'] : 'no' );
            $create_and_force_new_session_path = ( 'no' == $create_and_force_new_session_path ? false : true );
            if ( substr( $session_save_path, 0, 6 ) == 'tcp://' ) {
                /*
                 * For memcache ( memcacheD shoult be without tcp:// )
                 * Skip the check, sessions are saved in memory and we'll assume that is properly configured
                 */
            } else {
                // Check for file-based sessions
                try {
                    if ( !@is_writable( session_save_path() ) || $create_and_force_new_session_path ) {
                        $upload = wp_upload_dir();
                        $upload_dir = $upload['basedir'];
                        $upload_dir = $upload_dir . '/sessions';
                        if ( !is_dir( $upload_dir ) ) {
                            @mkdir( $upload_dir, 0755 );
                        }
                        $filename = '.htaccess';
                        $path = $upload_dir . '/' . $filename;
                        if ( !file_exists( $path ) ) {
                            $htaccess = @fopen( $path, "w" );
                            $content = "Deny from all";
                            @fwrite( $htaccess, $content );
                            @fclose( $htaccess );
                            @chmod( $path, 0644 );
                        }
                        @ini_set( "session.save_handler", "files" );
                        @session_save_path( $upload_dir );
                    }
                } catch ( Exception $e ) {
                    // Sessions don't work, save path is not writable
                }
            }
        }

        /**
         * Recalculate Order Totals
         *
         * @param int $order_id
         * @return array|null
         */
        function tc_recalculate_order_totals( $order_id ) {
            if ( !$order_id || 'tc_orders' != get_post_type( $order_id ) ) {
                return null;
            }
            $order_id = (int) $order_id;
            $cart_contents = [];
            $tc_ticket_discount = [];
            $tc_ticket_instances_after = [];
            // Collect Order Ticket Instances
            $tc_tickets_instances = get_posts( [
                'post_parent'    => $order_id,
                'post_type'      => 'tc_tickets_instances',
                'posts_per_page' => -1,
            ] );
            foreach ( $tc_tickets_instances as $tickets ) {
                $tc_ticket_type_id = (int) get_post_meta( $tickets->ID, 'ticket_type_id', true );
                $tc_ticket_discount[$tc_ticket_type_id][] = (float) get_post_meta( $tickets->ID, 'ticket_discount', true );
                $cart_contents[$tc_ticket_type_id] = (int) @$cart_contents[$tc_ticket_type_id] + 1;
                $tc_ticket_type_first_name = get_post_meta( $tickets->ID, 'first_name', true );
                $tc_ticket_type_last_name = get_post_meta( $tickets->ID, 'last_name', true );
                $tc_ticket_type_email = get_post_meta( $tickets->ID, 'owner_email', true );
                $tc_ticket_instances_after['owner_data']['ticket_type_id_post_meta'][$tc_ticket_type_id][] = $tc_ticket_type_id;
                $tc_ticket_instances_after['owner_data']['first_name_post_meta'][$tc_ticket_type_id][] = $tc_ticket_type_first_name;
                $tc_ticket_instances_after['owner_data']['last_name_post_meta'][$tc_ticket_type_id][] = $tc_ticket_type_last_name;
                $tc_ticket_instances_after['owner_data']['owner_email_post_meta'][$tc_ticket_type_id][] = $tc_ticket_type_email;
            }
            // Update Order Cart Contents
            update_post_meta( $order_id, 'tc_cart_contents', array_map( 'absint', $cart_contents ) );
            wp_update_post( array(
                'ID'           => $order_id,
                'post_content' => serialize( $cart_contents ),
            ) );
            // Order Object
            $order = new \Tickera\TC_Order($order_id);
            // Collect Individual Ticket Totals
            $cart_totals = $this->tc_calculate_individual_ticket_totals( $order, $cart_contents, $tc_ticket_discount );
            $order_payment_info = $order->details->tc_payment_info;
            $order_payment_info['total'] = 0;
            $order_payment_info['subtotal'] = 0;
            $order_payment_info['discount'] = 0;
            $order_payment_info['fees_total'] = 0;
            $order_payment_info['tax_total'] = 0;
            // Calculate Overall Totals
            foreach ( $cart_contents as $tickety_type_id => $ordered_count ) {
                // PROCESS PAYMENT INFO
                $order_payment_info['total'] = $order_payment_info['total'] + array_sum( $cart_totals['ticket_total_post_meta'][$tickety_type_id] );
                $order_payment_info['subtotal'] = $order_payment_info['subtotal'] + array_sum( $cart_totals['ticket_subtotal_post_meta'][$tickety_type_id] );
                $order_payment_info['discount'] = $order_payment_info['discount'] + array_sum( $cart_totals['ticket_discount_post_meta'][$tickety_type_id] );
                $order_payment_info['fees_total'] = $order_payment_info['fees_total'] + array_sum( $cart_totals['ticket_fee_post_meta'][$tickety_type_id] );
                $order_payment_info['tax_total'] = $order_payment_info['tax_total'] + array_sum( $cart_totals['ticket_tax_post_meta'][$tickety_type_id] );
            }
            // Process Cart Info Object
            $order_cart_info = $order->details->tc_cart_info;
            $order_cart_info['total'] = $order_payment_info['total'];
            $order_cart_info['owner_data'] = array_replace( $order_cart_info['owner_data'], $tc_ticket_instances_after['owner_data'] );
            $order_cart_info['owner_data']['ticket_subtotal_post_meta'] = $cart_totals['ticket_subtotal_post_meta'];
            $order_cart_info['owner_data']['ticket_discount_post_meta'] = $cart_totals['ticket_discount_post_meta'];
            $order_cart_info['owner_data']['ticket_fee_post_meta'] = $cart_totals['ticket_fee_post_meta'];
            $order_cart_info['owner_data']['ticket_tax_post_meta'] = $cart_totals['ticket_tax_post_meta'];
            $order_cart_info['owner_data']['ticket_total_post_meta'] = $cart_totals['ticket_total_post_meta'];
            // Update Order Metas
            update_post_meta( $order_id, 'tc_cart_info', tickera_sanitize_array( $order_cart_info, false, true ) );
            update_post_meta( $order_id, 'tc_payment_info', tickera_sanitize_array( $order_payment_info ) );
            return $cart_totals;
        }

        /**
         * Collection of Ticket Type Instances
         *
         * @return bool|false|float|string
         */
        function tc_get_ticket_type_instances() {
            if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                $ticket_instance_id = ( isset( $_POST['tc_ticket_instance_id'] ) ? (int) $_POST['tc_ticket_instance_id'] : '' );
                // Action is not allowed on paid orders
                $order_id = wp_get_post_parent_id( $ticket_instance_id );
                if ( 'order_paid' == get_post_status( (int) $order_id ) ) {
                    wp_send_json( [
                        'error' => __( 'Action is not allowed on paid orders.', 'tickera-event-ticketing-system' ),
                    ] );
                }
                $args = array(
                    'posts_per_page' => -1,
                    'order'          => 'ASC',
                    'post_type'      => 'tc_tickets',
                    'post_status'    => 'publish',
                );
                // Filter by Seating Charts
                if ( metadata_exists( 'post', $ticket_instance_id, 'chart_id' ) ) {
                    /*
                     * Temporary Disabled
                     * $chart_id = get_post_meta($ticket_instance_id,'chart_id', true);
                     * $ticket_type_ids = get_post_meta($chart_id,'tc_ticket_types', true);
                     * $args['post__in'] = explode(',',$ticket_type_ids);
                     */
                    $response = array(
                        'error' => __( 'Seating chart update is currently not allowed.', 'tickera-event-ticketing-system' ),
                    );
                    wp_send_json( $response );
                } else {
                    /* Filter by Events */
                    $args['meta_key'] = 'event_name';
                    $args['meta_value'] = get_post_meta( $ticket_instance_id, 'event_id', true );
                }
                // Place the current ticket type at the very first line of the selection field
                $ticket_type_id = get_post_meta( $ticket_instance_id, 'ticket_type_id', true );
                $collection = [[
                    'id'   => (int) $ticket_type_id,
                    'text' => get_the_title( $ticket_type_id ),
                ]];
                foreach ( get_posts( $args ) as $key => $val ) {
                    if ( $val->ID != $ticket_type_id ) {
                        // Validate Ticket type availability
                        $ticket = new \Tickera\TC_Ticket($val->ID);
                        if ( !$ticket->is_sold_ticket_exceeded_limit_level() ) {
                            $collection[$key + 1]['id'] = $val->ID;
                            $collection[$key + 1]['text'] = $val->post_title;
                        }
                    }
                }
                wp_send_json( array_values( $collection ) );
            } else {
                wp_send_json( [
                    'error' => __( 'Invalid action. Nonce did not matched.', 'tickera-event-ticketing-system' ),
                ] );
            }
        }

        /**
         * Save Attendees Information
         */
        function save_attendee_info() {
            if ( isset( $_POST['post_id'] ) && isset( $_POST['meta_name'] ) && isset( $_POST['meta_value'] ) && isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                $post_id = (int) $_POST['post_id'];
                $meta_name = sanitize_text_field( $_POST['meta_name'] );
                $meta_value = sanitize_text_field( $_POST['meta_value'] );
                $order_id = (int) get_post( $post_id )->post_parent;
                $post_type = get_post_type( $order_id );
                // Retrieve Previous Ticket Type ID
                $previous_ticket = (int) get_post_meta( $post_id, 'ticket_type_id', true );
                // Check if Updated Ticket Type has stock
                $quantity_sold = tickera_get_tickets_count_sold( $meta_value );
                $ticket_quantity = ( 'tc_orders' == $post_type ? get_post_meta( $order_id, 'quantity_available', true ) : get_post_meta( $order_id, '_stock', true ) );
                $quantity_on_hand = (int) $ticket_quantity - (int) $quantity_sold;
                // Only when there are changes and has stocks
                if ( $previous_ticket != $meta_value && ($quantity_on_hand > 0 || empty( $ticket_quantity )) ) {
                    /*
                     * Update Ticket Type.
                     *
                     *
                     * Disable Recalculate Totals.
                     * $this->tc_recalculate_order_totals( $order_id );
                     */
                    update_post_meta( $post_id, $meta_name, sanitize_text_field( $meta_value ) );
                    do_action( 'tc_after_attendee_info_updated', $post_id, $order_id );
                } elseif ( $quantity_on_hand <= 0 ) {
                    /*
                     * Convert to JS to display error messages
                     */
                    $message = "Unable to update ticket type. No quantity left.";
                    apply_filters( 'tc_admin_notices', $message, 'notice-error' );
                }
            }
        }

        /**
         * Install actions such as installing pages when a button is clicked.
         */
        function install_actions() {
            // Install - Add pages button
            if ( !empty( $_GET['install_tickera_pages'] ) ) {
                self::create_pages();
                // Settings redirect
                tickera_redirect( admin_url( 'edit.php?post_type=tc_events&page=tc_settings' ) );
            }
        }

        function create_pages() {
            $pages = apply_filters( 'tc_create_pages', array(
                'cart'            => array(
                    'name'    => _x( 'tickets-cart', 'Page slug', 'tickera-event-ticketing-system' ),
                    'title'   => _x( 'Cart', 'Page title', 'tickera-event-ticketing-system' ),
                    'content' => '[' . apply_filters( 'tc_cart_shortcode_tag', 'tc_cart' ) . ']',
                ),
                'payment'         => array(
                    'name'    => _x( 'tickets-payment', 'Page slug', 'tickera-event-ticketing-system' ),
                    'title'   => _x( 'Payment', 'Page title', 'tickera-event-ticketing-system' ),
                    'content' => '[' . apply_filters( 'tc_payment_shortcode_tag', 'tc_payment' ) . ']',
                ),
                'confirmation'    => array(
                    'name'    => _x( 'tickets-order-confirmation', 'Page slug', 'tickera-event-ticketing-system' ),
                    'title'   => _x( 'Payment Confirmation', 'Page title', 'tickera-event-ticketing-system' ),
                    'content' => '[' . apply_filters( 'tc_order_confirmation_shortcode_tag', 'tc_order_confirmation' ) . ']',
                ),
                'order'           => array(
                    'name'    => _x( 'tickets-order-details', 'Page slug', 'tickera-event-ticketing-system' ),
                    'title'   => _x( 'Order Details', 'Page title', 'tickera-event-ticketing-system' ),
                    'content' => '[' . apply_filters( 'tc_order_details_shortcode_tag', 'tc_order_details' ) . ']',
                ),
                'process_payment' => array(
                    'name'    => _x( 'tickets-process-payment', 'Page slug', 'tickera-event-ticketing-system' ),
                    'title'   => _x( 'Process Payment', 'Page title', 'tickera-event-ticketing-system' ),
                    'content' => '[' . apply_filters( 'tc_process_payment_shortcode_tag', 'tc_process_payment' ) . ']',
                ),
                'ipn'             => array(
                    'name'    => _x( 'tickets-ipn-payment', 'Page slug', 'tickera-event-ticketing-system' ),
                    'title'   => _x( 'IPN', 'Page title', 'tickera-event-ticketing-system' ),
                    'content' => '[' . apply_filters( 'tc_ipn_shortcode_tag', 'tc_ipn' ) . ']',
                ),
            ) );
            $pages = tickera_sanitize_array( $pages, false, true );
            foreach ( $pages as $key => $page ) {
                tickera_create_page(
                    esc_sql( $page['name'] ),
                    'tickera_' . $key . '_page_id',
                    $page['title'],
                    $page['content'],
                    ''
                );
            }
            update_option( 'tickera_needs_pages', 0 );
            flush_rewrite_rules();
        }

        function add_notices() {
            if ( 1 == get_option( 'tickera_needs_pages', 1 ) && apply_filters( 'tc_bridge_for_woocommerce_is_active', false ) == false ) {
                add_action( 'admin_notices', array($this, 'install_notice') );
            }
        }

        function install_notice() {
            global $tc;
            // If we have just installed, show a message with the install pages button
            if ( get_option( 'tickera_needs_pages', 1 ) == 1 ) {
                include 'includes/install-notice.php';
            }
        }

        /**
         * Generate downloadable tickets' url in frontend.
         *
         * Only allow ticket downloads to those valid order statuses.
         * Invalid download url will redirect the user back to empty order details page.
         */
        function generate_pdf_ticket_front() {
            $nonce = ( isset( $_GET['nonce'] ) ? sanitize_key( $_GET['nonce'] ) : '' );
            $order_key = ( isset( $_GET['order_key'] ) ? sanitize_key( $_GET['order_key'] ) : '' );
            $ticket = ( isset( $_GET['download_ticket'] ) ? (int) $_GET['download_ticket'] : '' );
            /**
             * Version_compare for backward compatibility
             * Considering those download url links sent via email prior to updating to 3.5.2.5
             * @since 3.5.2.6
             *
             * Adjusted backward compatibility version to 3.5.3.0
             * @since 3.5.2.9
             *
             * Removed version_compare for backward compatibility.
             * Added $disable_download_hash condition
             * @since 3.5.3.4
             */
            if ( !empty( $order_key ) && !empty( $ticket ) ) {
                $general_settings = get_option( 'tickera_general_setting', [] );
                $disable_download_hash = ( isset( $general_settings['disable_ticket_download_hash'] ) && 'yes' == $general_settings['disable_ticket_download_hash'] ? true : false );
                if ( !empty( $nonce ) && hash_equals( wp_hash( $ticket . $order_key ), $nonce ) || $disable_download_hash ) {
                    $order_id = wp_get_post_parent_id( $ticket );
                    if ( $order_id ) {
                        $tc_general_settings = get_option( 'tickera_general_setting', false );
                        $post_author = get_post_field( 'post_author', $order_id );
                        // Require users to login first.
                        if ( isset( $tc_general_settings['force_login'] ) && $tc_general_settings['force_login'] == 'yes' && (!is_user_logged_in() || is_user_logged_in() && $post_author != get_current_user_id()) ) {
                            if ( !current_user_can( 'manage_options' ) ) {
                                $redirect_url = (( is_ssl() ? 'https' : 'http' )) . '://' . sanitize_text_field( $_SERVER['HTTP_HOST'] ) . sanitize_text_field( $_SERVER['REQUEST_URI'] );
                                $tc_force_login_url = esc_url( apply_filters( 'tc_force_login_download_url', wp_login_url( $redirect_url ), wp_login_url( $redirect_url ) ) );
                                tickera_redirect( wp_login_url( $tc_force_login_url ), false, false );
                                tickera_js_redirect( $tc_force_login_url, false );
                                exit;
                            }
                        }
                        $order = new \Tickera\TC_Order($order_id);
                        $order_status = $order->details->post_status;
                        $order_date = strtotime( $order->details->post_date );
                        $order_modified = strtotime( $order->details->post_modified );
                        $tc_order_date = $order->details->tc_order_date;
                        $alt_paid_date = $order->details->_tc_paid_date;
                        $valid_order_statuses = apply_filters( 'tc_validate_downloadable_ticket_order_status', ['order_paid'], $order );
                        if ( in_array( $order_status, $valid_order_statuses ) ) {
                            if ( $order_key == $order_date || $order_key == $order_modified || $order_key == $tc_order_date || $alt_paid_date == $order_key ) {
                                $template_id = ( isset( $_GET['template_id'] ) ? (int) $_GET['template_id'] : false );
                                $templates = new \Tickera\TC_Ticket_Templates();
                                $templates->generate_preview( (int) $_GET['download_ticket'], true, $template_id );
                            } else {
                                $order_details_page = $this->tc_order_status_url(
                                    '',
                                    '',
                                    '',
                                    false
                                );
                                // do_action( 'tc_after_invalid_downloadable_ticket', $order_id );
                                tickera_redirect( $order_details_page, true );
                            }
                        }
                    }
                }
            }
        }

        /**
         * Generate downloadable ticket pdf in Admin.
         */
        function generate_pdf_ticket() {
            if ( isset( $_GET['action'] ) && 'preview' == $_GET['action'] && isset( $_GET['page'] ) && 'tc_ticket_templates' == $_GET['page'] ) {
                if ( isset( $_GET['ID'] ) ) {
                    $templates = new \Tickera\TC_Ticket_Templates();
                    $templates->generate_preview( false, false, (int) $_GET['ID'] );
                }
                if ( isset( $_GET['ticket_type_id'] ) ) {
                    $templates = new \Tickera\TC_Ticket_Templates();
                    $templates->generate_preview(
                        false,
                        false,
                        (int) $_GET['template_id'],
                        (int) $_GET['ticket_type_id']
                    );
                }
            }
        }

        function no_comments_template( $template ) {
            global $post;
            if ( 'virtual_page' == $post->post_type ) {
                $template = $this->plugin_dir . 'includes/templates/no-comments.php';
            }
            return $template;
        }

        function comments_open( $open, $post_id ) {
            $cart_page_id = get_option( 'tickera_cart_page_id', false );
            $payment_page_id = get_option( 'tickera_payment_page_id', false );
            $confirmation_page_id = get_option( 'tickera_confirmation_page_id', false );
            $order_page_id = get_option( 'tickera_order_page_id', false );
            $current_post = get_post( $post_id );
            if ( $current_post && ($current_post->post_type == 'virtual_page' || $post_id == $cart_page_id || $post_id == $payment_page_id || $post_id == $confirmation_page_id || $post_id == $order_page_id) ) {
                $open = false;
            }
            return $open;
        }

        /**
         * Redirect to Installation Wizard upon plugin activation.
         */
        function activation() {
            global $pagenow, $wp_rewrite;
            if ( 'plugins.php' == $pagenow && !is_network_admin() ) {
                // Add caps on plugin page so other plugins can hook and add their own caps if needed
                $this->add_default_posts_and_metas();
                $this->add_required_capabilities();
                $wp_rewrite->flush_rules();
                // Show wizard only to admins
                if ( current_user_can( 'manage_options' ) ) {
                    $url_parameters = [
                        'page' => 'tc-installation-wizard',
                    ];
                    $current_step = get_option( 'tickera_wizard_step' );
                    $mode = get_option( 'tickera_wizard_mode' );
                    if ( $mode ) {
                        $url_parameters['mode'] = $mode;
                    }
                    if ( $current_step ) {
                        $url_parameters['step'] = $current_step;
                    }
                    $step_url = add_query_arg( [$url_parameters], admin_url( 'index.php' ) );
                    if ( !in_array( $current_step, ['finish', 'skipped'] ) ) {
                        tickera_redirect( $step_url, true );
                    }
                }
            }
        }

        function add_required_capabilities() {
            $admin_role = get_role( 'administrator' );
            $admin_capabilities = array_keys( $this->admin_capabilities() );
            foreach ( $admin_capabilities as $cap ) {
                if ( !isset( $admin_role->capabilities[$cap] ) ) {
                    if ( $admin_role ) {
                        $admin_role->add_cap( $cap );
                    } else {
                        // Do nothing
                    }
                }
            }
            $staff_role = get_role( 'staff' );
            if ( $staff_role == null ) {
                add_role( 'staff', 'Staff' );
            }
            foreach ( $this->staff_capabilities() as $cap => $value ) {
                if ( $value == 1 ) {
                    if ( !isset( $staff_role->capabilities[$cap] ) ) {
                        if ( $staff_role ) {
                            $staff_role->add_cap( $cap );
                        } else {
                            // Do nothing
                        }
                    }
                }
            }
        }

        function add_default_posts_and_metas() {
            global $wpdb;
            $post_type = 'tc_templates';
            $post_status = 'publish';
            $template_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s", $post_type, $post_status ) );
            // Add Default Ticket Template
            if ( 0 == $template_count ) {
                $post = apply_filters( 'tc_template_post', [
                    'post_content' => '',
                    'post_status'  => 'publish',
                    'post_title'   => __( 'Default', 'tickera-event-ticketing-system' ),
                    'post_type'    => 'tc_templates',
                ] );
                $post_id = wp_insert_post( tickera_sanitize_array( $post ) );
                // Add post metas for the template
                if ( $post_id != 0 ) {
                    update_post_meta( $post_id, 'tc_event_logo_element_cell_alignment', 'left' );
                    update_post_meta( $post_id, 'tc_event_logo_element_top_padding', '0' );
                    update_post_meta( $post_id, 'tc_event_logo_element_bottom_padding', '3' );
                    update_post_meta( $post_id, 'tc_event_terms_element_font_size', '12' );
                    update_post_meta( $post_id, 'tc_event_terms_element_font_style', '' );
                    update_post_meta( $post_id, 'tc_event_terms_element_font_color', '#7a7a7a' );
                    update_post_meta( $post_id, 'tc_event_terms_element_cell_alignment', 'left' );
                    update_post_meta( $post_id, 'tc_event_terms_element_top_padding', '1' );
                    update_post_meta( $post_id, 'tc_event_terms_element_bottom_padding', '1' );
                    update_post_meta( $post_id, 'tc_ticket_qr_code_element_qr_code_size', '50' );
                    update_post_meta( $post_id, 'tc_ticket_qr_code_element_cell_alignment', 'center' );
                    update_post_meta( $post_id, 'tc_ticket_qr_code_element_top_padding', '1' );
                    update_post_meta( $post_id, 'tc_ticket_qr_code_element_bottom_padding', '1' );
                    update_post_meta( $post_id, 'tc_event_location_element_font_size', '16' );
                    update_post_meta( $post_id, 'tc_event_location_element_font_style', '' );
                    update_post_meta( $post_id, 'tc_event_location_element_font_color', '#000000' );
                    update_post_meta( $post_id, 'tc_event_location_element_cell_alignment', 'center' );
                    update_post_meta( $post_id, 'tc_event_location_element_top_padding', '0' );
                    update_post_meta( $post_id, 'tc_event_location_element_bottom_padd', '0' );
                    update_post_meta( $post_id, 'tc_ticket_type_element_font_size', '18' );
                    update_post_meta( $post_id, 'tc_ticket_type_element_font_style', 'B' );
                    update_post_meta( $post_id, 'tc_ticket_type_element_font_color', '#e54c2d' );
                    update_post_meta( $post_id, 'tc_ticket_type_element_cell_alignment', 'right' );
                    update_post_meta( $post_id, 'tc_ticket_type_element_top_padding', '1' );
                    update_post_meta( $post_id, 'tc_ticket_type_element_bottom_padding', '3' );
                    update_post_meta( $post_id, 'rows_1', 'tc_event_logo_element,tc_ticket_type_element' );
                    update_post_meta( $post_id, 'tc_event_date_time_element_font_size', '16' );
                    update_post_meta( $post_id, 'tc_event_date_time_element_font_style', '' );
                    update_post_meta( $post_id, 'tc_event_date_time_element_font_color', '#000000' );
                    update_post_meta( $post_id, 'tc_event_date_time_element_cell_alignment', 'center' );
                    update_post_meta( $post_id, 'tc_event_date_time_element_top_padding', '2' );
                    update_post_meta( $post_id, 'tc_event_date_time_element_bottom_padding', '0' );
                    update_post_meta( $post_id, 'rows_2', 'tc_event_name_element' );
                    update_post_meta( $post_id, 'tc_event_name_element_font_size', '60' );
                    update_post_meta( $post_id, 'tc_event_name_element_font_style', '' );
                    update_post_meta( $post_id, 'tc_event_name_element_font_color', '#000000' );
                    update_post_meta( $post_id, 'tc_event_name_element_cell_alignment', 'center' );
                    update_post_meta( $post_id, 'tc_event_name_element_top_padding', '0' );
                    update_post_meta( $post_id, 'tc_event_name_element_bottom_padding', '0' );
                    update_post_meta( $post_id, 'rows_3', 'tc_event_date_time_element' );
                    update_post_meta( $post_id, 'tc_ticket_owner_name_element_font_size', '20' );
                    update_post_meta( $post_id, 'tc_ticket_owner_name_element_font_color', '#e54c2d' );
                    update_post_meta( $post_id, 'tc_ticket_owner_name_element_cell_alignment', 'center' );
                    update_post_meta( $post_id, 'tc_ticket_owner_name_element_top_padding', '3' );
                    update_post_meta( $post_id, 'tc_ticket_owner_name_element_bottom_padding', '3' );
                    update_post_meta( $post_id, 'rows_4', 'tc_event_location_element' );
                    update_post_meta( $post_id, 'rows_5', 'tc_ticket_owner_name_element' );
                    update_post_meta( $post_id, 'rows_6', 'tc_ticket_description_element' );
                    update_post_meta( $post_id, 'rows_7', 'tc_ticket_qr_code_element' );
                    update_post_meta( $post_id, 'rows_8', 'tc_event_terms_element' );
                    update_post_meta( $post_id, 'rows_9', '' );
                    update_post_meta( $post_id, 'rows_10', '' );
                    update_post_meta( $post_id, 'rows_number', '10' );
                    update_post_meta( $post_id, 'document_font', 'helvetica' );
                    update_post_meta( $post_id, 'document_ticket_size', 'A4' );
                    update_post_meta( $post_id, 'document_ticket_orientation', 'P' );
                    update_post_meta( $post_id, 'document_ticket_top_margin', '10' );
                    update_post_meta( $post_id, 'document_ticket_right_margin', '10' );
                    update_post_meta( $post_id, 'document_ticket_left_margin', '10' );
                    update_post_meta( $post_id, 'document_ticket_background_image', '' );
                    update_post_meta( $post_id, 'tc_ticket_barcode_element_barcode_type', 'C128' );
                    update_post_meta( $post_id, 'tc_ticket_barcode_element_barcode_text_visibility', 'visible' );
                    update_post_meta( $post_id, 'tc_ticket_barcode_element_1d_barcode_size', '50' );
                    update_post_meta( $post_id, 'tc_ticket_barcode_element_font_size', '8' );
                    update_post_meta( $post_id, 'tc_ticket_barcode_element_cell_alignment', 'left' );
                    update_post_meta( $post_id, 'tc_ticket_barcode_element_top_padding', '0' );
                    update_post_meta( $post_id, 'tc_ticket_barcode_element_bottom_padding', '0' );
                    update_post_meta( $post_id, 'tc_ticket_description_element_font_size', '12' );
                    update_post_meta( $post_id, 'tc_ticket_description_element_font_style', '' );
                    update_post_meta( $post_id, 'tc_ticket_description_element_font_color', '#0a0a0a' );
                    update_post_meta( $post_id, 'tc_ticket_description_element_cell_alignment', 'left' );
                    update_post_meta( $post_id, 'tc_ticket_description_element_top_padding', '0' );
                    update_post_meta( $post_id, 'tc_ticket_description_element_bottom_padding', '2' );
                    update_post_meta( $post_id, 'tc_event_location_element_bottom_padding', '0' );
                    update_post_meta( $post_id, 'tc_ticket_owner_name_element_font_style', '' );
                }
            }
            // Add random default API Key
            $post_type = 'tc_api_keys';
            $post_status = 'publish';
            $api_key_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s", $post_type, $post_status ) );
            if ( 0 == $api_key_count ) {
                $post = apply_filters( 'tc_api_key_default_post', [
                    'post_content' => '',
                    'post_status'  => 'publish',
                    'post_title'   => __( 'Default', 'tickera-event-ticketing-system' ),
                    'post_type'    => 'tc_api_keys',
                ] );
                $post_id = wp_insert_post( tickera_sanitize_array( $post ) );
                // Add post metas for the API Key
                $api_keys = new \Tickera\TC_API_Keys();
                if ( $post_id != 0 ) {
                    update_post_meta( $post_id, 'event_name', 'all' );
                    update_post_meta( $post_id, 'api_key_name', 'Default - All Events' );
                    update_post_meta( $post_id, 'api_key', $api_keys->get_rand_api_key() );
                    update_post_meta( $post_id, 'api_username', '' );
                }
            }
        }

        function admin_permalink_message() {
            if ( current_user_can( 'manage_options' ) && !get_option( 'permalink_structure' ) ) {
                echo wp_kses_post( sprintf( 
                    /* translators: %s: Tickera */
                    __( '<div class="error"><p><strong>%s</strong> is almost ready. You must <a href="options-permalink.php">update your permalink structure</a> to something other than the default for it to work.</p></div>', 'tickera-event-ticketing-system' ),
                    esc_html( $this->title )
                 ) );
            }
        }

        function is_wp_debug_enabled() {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && (defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY) || defined( 'WP_DEBUG' ) && WP_DEBUG && !defined( 'WP_DEBUG_DISPLAY' ) ) {
                return true;
            } else {
                return false;
            }
        }

        function is_tc_debug_enabled() {
            return ( defined( 'TC_DEBUG' ) ? true : false );
        }

        /**
         * Make sure that debug messages are not shown
         */
        function admin_debug_notices_message() {
            if ( current_user_can( 'manage_options' ) ) {
                if ( $this->is_tc_debug_enabled() || $this->is_wp_debug_enabled() ) {
                    echo wp_kses_post( '<div class="notice notice-warning"><p>' );
                    if ( $this->is_tc_debug_enabled() && $this->is_wp_debug_enabled() ) {
                        // Both are enabled
                        echo wp_kses_post( __( 'It is recommended to turn off both <strong>TC_DEBUG</strong> and <strong>WP_DEBUG</strong> on a production site.', 'tickera-event-ticketing-system' ) );
                        echo wp_kses_post( __( ' Remove <i><strong>define(\'TC_DEBUG\', true);</strong></i> line from wp-config.php file.', 'tickera-event-ticketing-system' ) );
                        echo wp_kses_post( __( ' Edit wp-config.php file and set the the WP_DEBUG value like this: <strong>define(\'WP_DEBUG\', false);</strong> or add additional line <strong><i>define( \'WP_DEBUG_DISPLAY\', false );</i></strong> to the wp-config.php', 'tickera-event-ticketing-system' ) );
                    } elseif ( $this->is_tc_debug_enabled() && !$this->is_wp_debug_enabled() ) {
                        //Only TC_DEBUG is enabled
                        echo wp_kses_post( __( 'It is recommended to turn off <strong>TC_DEBUG</strong> on a production site.', 'tickera-event-ticketing-system' ) );
                        echo wp_kses_post( __( ' Remove <i><strong>define(\'TC_DEBUG\', true);</strong></i> line from wp-config.php file.', 'tickera-event-ticketing-system' ) );
                    } elseif ( !$this->is_tc_debug_enabled() && $this->is_wp_debug_enabled() ) {
                        // Only WP_DEBUG is enabled
                        echo wp_kses_post( __( 'It is recommended to turn off <strong>WP_DEBUG</strong> on a production site.', 'tickera-event-ticketing-system' ) );
                        echo wp_kses_post( __( ' Edit wp-config.php file and set the the WP_DEBUG value like this: <strong>define(\'WP_DEBUG\', false);</strong> or add additional line <strong><i>define( \'WP_DEBUG_DISPLAY\', false );</i></strong> to the wp-config.php', 'tickera-event-ticketing-system' ) );
                    }
                    echo wp_kses_post( '</p></div>' );
                }
            }
        }

        /**
         * DEPRECATED, use tc_get_license_key function
         *
         * @return type
         */
        function get_license_key() {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $license_key = ( defined( 'TC_LCK' ) && TC_LCK !== '' ? TC_LCK : (( isset( $tc_general_settings['license_key'] ) && $tc_general_settings['license_key'] !== '' ? $tc_general_settings['license_key'] : '' )) );
            return $license_key;
        }

        function admin_capabilities() {
            $capabilities = array(
                'manage_events_cap'                      => 1,
                'manage_ticket_types_cap'                => 1,
                'manage_discount_codes_cap'              => 1,
                'manage_orders_cap'                      => 1,
                'manage_attendees_cap'                   => 1,
                'manage_ticket_templates_cap'            => 1,
                'delete_checkins_cap'                    => 1,
                'delete_attendees_cap'                   => 1,
                'manage_ticket_templates_cap'            => 1,
                'manage_settings_cap'                    => 1,
                'save_ticket_cap'                        => 1,
                'add_discount_cap'                       => 1,
                'publish_tc_events'                      => 1,
                'edit_tc_events'                         => 1,
                'edit_others_tc_events'                  => 1,
                'delete_tc_events'                       => 1,
                'delete_others_tc_events'                => 1,
                'read_private_tc_events'                 => 1,
                'edit_tc_event'                          => 1,
                'delete_tc_event'                        => 1,
                'read_tc_event'                          => 1,
                'edit_published_tc_events'               => 1,
                'edit_private_tc_events'                 => 1,
                'delete_private_tc_events'               => 1,
                'delete_published_tc_events'             => 1,
                'create_tc_events'                       => 1,
                'publish_tc_tickets'                     => 1,
                'edit_tc_tickets'                        => 1,
                'edit_tc_ticket'                         => 1,
                'edit_others_tc_tickets'                 => 1,
                'delete_tc_tickets'                      => 1,
                'delete_others_tc_tickets'               => 1,
                'read_private_tc_tickets'                => 1,
                'delete_tc_ticket'                       => 1,
                'read_tc_ticket'                         => 1,
                'edit_published_tc_tickets'              => 1,
                'edit_private_tc_tickets'                => 1,
                'delete_private_tc_tickets'              => 1,
                'delete_published_tc_tickets'            => 1,
                'create_tc_tickets'                      => 1,
                'edit_tc_tickets_instance'               => 1,
                'read_tc_tickets_instance'               => 1,
                'delete_tc_tickets_instance'             => 1,
                'create_tc_tickets_instances'            => 1,
                'edit_tc_tickets_instances'              => 1,
                'edit_others_posts_tc_tickets_instances' => 1,
                'publish_tc_tickets_instances'           => 1,
                'read_private_tc_tickets_instances'      => 1,
                'delete_tc_tickets_instances'            => 1,
                'delete_private_tc_tickets_instances'    => 1,
                'delete_published_tc_tickets_instances'  => 1,
                'delete_others_tc_tickets_instances'     => 1,
                'edit_private_tc_tickets_instances'      => 1,
                'edit_published_tc_tickets_instances'    => 1,
                'edit_tc_order'                          => 1,
                'read_tc_order'                          => 1,
                'delete_tc_order'                        => 1,
                'create_tc_orders'                       => 1,
                'edit_tc_orders'                         => 1,
                'edit_others_posts_tc_orders'            => 1,
                'publish_tc_orders'                      => 1,
                'read_private_tc_orders'                 => 1,
                'delete_tc_orders'                       => 1,
                'delete_private_tc_orders'               => 1,
                'delete_published_tc_orders'             => 1,
                'delete_others_tc_orders'                => 1,
                'edit_private_tc_orders'                 => 1,
                'edit_published_tc_orders'               => 1,
                'read'                                   => 1,
            );
            $role = get_role( 'administrator' );
            return apply_filters( 'tc_admin_capabilities', array_merge( $capabilities, $role->capabilities ) );
        }

        function staff_capabilities() {
            $capabilities = array(
                'manage_events_cap'                      => 0,
                'manage_ticket_types_cap'                => 0,
                'manage_discount_codes_cap'              => 0,
                'manage_orders_cap'                      => 0,
                'manage_attendees_cap'                   => 0,
                'manage_ticket_templates_cap'            => 0,
                'delete_checkins_cap'                    => 0,
                'delete_attendees_cap'                   => 0,
                'manage_ticket_templates_cap'            => 0,
                'manage_settings_cap'                    => 0,
                'save_ticket_cap'                        => 0,
                'add_discount_cap'                       => 0,
                'publish_tc_events'                      => 0,
                'edit_tc_events'                         => 1,
                'edit_others_tc_events'                  => 0,
                'delete_tc_events'                       => 0,
                'delete_others_tc_events'                => 0,
                'read_private_tc_events'                 => 0,
                'edit_tc_event'                          => 0,
                'delete_tc_event'                        => 0,
                'read_tc_event'                          => 0,
                'edit_published_tc_events'               => 0,
                'edit_private_tc_events'                 => 0,
                'delete_private_tc_events'               => 0,
                'delete_published_tc_events'             => 0,
                'create_tc_events'                       => 0,
                'publish_tc_tickets'                     => 0,
                'edit_tc_tickets'                        => 0,
                'edit_tc_ticket'                         => 0,
                'edit_others_tc_tickets'                 => 0,
                'delete_tc_tickets'                      => 0,
                'delete_others_tc_tickets'               => 0,
                'read_private_tc_tickets'                => 0,
                'delete_tc_ticket'                       => 0,
                'read_tc_ticket'                         => 0,
                'edit_published_tc_tickets'              => 0,
                'edit_private_tc_tickets'                => 0,
                'delete_private_tc_tickets'              => 0,
                'delete_published_tc_tickets'            => 0,
                'create_tc_tickets'                      => 0,
                'edit_tc_tickets_instance'               => 1,
                'read_tc_tickets_instance'               => 1,
                'delete_tc_tickets_instance'             => 0,
                'create_tc_tickets_instances'            => 1,
                'edit_tc_tickets_instances'              => 1,
                'edit_others_posts_tc_tickets_instances' => 1,
                'publish_tc_tickets_instances'           => 1,
                'read_private_tc_tickets_instances'      => 1,
                'delete_tc_tickets_instances'            => 0,
                'delete_private_tc_tickets_instances'    => 0,
                'delete_published_tc_tickets_instances'  => 0,
                'delete_others_tc_tickets_instances'     => 0,
                'edit_private_tc_tickets_instances'      => 1,
                'edit_published_tc_tickets_instances'    => 1,
                'edit_tc_order'                          => 0,
                'read_tc_order'                          => 0,
                'delete_tc_order'                        => 0,
                'create_tc_orders'                       => 0,
                'edit_tc_orders'                         => 0,
                'edit_others_posts_tc_orders'            => 0,
                'publish_tc_orders'                      => 0,
                'read_private_tc_orders'                 => 0,
                'delete_tc_orders'                       => 0,
                'delete_private_tc_orders'               => 0,
                'delete_published_tc_orders'             => 0,
                'delete_others_tc_orders'                => 0,
                'edit_private_tc_orders'                 => 0,
                'edit_published_tc_orders'               => 0,
                'edit_posts'                             => 1,
                'read'                                   => 1,
            );
            $role = get_role( 'staff' );
            return apply_filters( 'tc_staff_capabilities', array_merge( $capabilities, $role->capabilities ) );
        }

        /**
         * Adds plugin links to custom theme nav menus using wp_nav_menu()
         *
         * @param $sorted_menu_items
         * @param $args
         * @return mixed
         */
        function main_navigation_links( $sorted_menu_items, $args ) {
            if ( !is_admin() ) {
                $theme_location = 'primary';
                if ( !has_nav_menu( $theme_location ) ) {
                    $theme_locations = get_nav_menu_locations();
                    foreach ( (array) $theme_locations as $key => $location ) {
                        $theme_location = $key;
                        break;
                    }
                }
            }
            $count = count( $sorted_menu_items );
            if ( $args->theme_location == $theme_location ) {
                $new_links = array();
                $label = apply_filters( 'tc_cart_page_link_title', __( 'Cart', 'tickera-event-ticketing-system' ) );
                // Create a nav_menu_item object
                $item = array(
                    'title'            => $label,
                    'menu_item_parent' => 0,
                    'ID'               => 'tc_cart',
                    'db_id'            => '',
                    'target'           => '',
                    'xfn'              => '',
                    'current'          => '',
                    'url'              => $this->get_cart_slug( true ),
                    'classes'          => array('menu-item'),
                );
                $new_links[] = (object) $item;
                // Add the new menu item to our array
                array_splice(
                    $sorted_menu_items,
                    $count + 1,
                    0,
                    $new_links
                );
            }
            return $sorted_menu_items;
        }

        function main_navigation_links_fallback( $current_menu ) {
            if ( !is_admin() ) {
                $cart_link = new stdClass();
                $cart_link->title = apply_filters( 'tc_cart_page_link_title', __( 'Cart', 'tickera-event-ticketing-system' ) );
                $cart_link->menu_item_parent = 0;
                $cart_link->ID = 'tc_cart';
                $cart_link->db_id = '';
                $cart_link->url = $this->get_cart_slug( true );
                if ( tickera_current_url() == $cart_link->url ) {
                    $cart_link->classes[] = 'current_page_item';
                }
                $main_sorted_menu_items[] = $cart_link;
                ?>
                <div class="menu">
                    <ul class='nav-menu'>
                        <?php 
                foreach ( $main_sorted_menu_items as $menu_item ) {
                    ?>
                            <li class='menu-item-<?php 
                    echo esc_attr( $menu_item->ID );
                    ?>'><a id="<?php 
                    echo esc_attr( $menu_item->ID );
                    ?>" href="<?php 
                    echo esc_url( $menu_item->url );
                    ?>"><?php 
                    echo esc_html( $menu_item->title );
                    ?></a>
                                <?php 
                    if ( $menu_item->db_id !== '' ) {
                        ?>
                                    <ul class="sub-menu dropdown-menu">
                                        <?php 
                        foreach ( $sub_sorted_menu_items as $menu_item ) {
                            ?>
                                            <li class='menu-item-<?php 
                            echo esc_attr( $menu_item->ID );
                            ?>'>
                                                <a id="<?php 
                            echo esc_attr( $menu_item->ID );
                            ?>" href="<?php 
                            echo esc_url( $menu_item->url );
                            ?>"><?php 
                            echo esc_html( $menu_item->title );
                            ?></a>
                                            </li>
                                        <?php 
                        }
                        ?>
                                    </ul>
                                <?php 
                    }
                    ?>
                            </li>
                        <?php 
                }
                ?>
                    </ul>
                </div>
            <?php 
            }
        }

        function checkin_api() {
            if ( get_option( 'tickera_version', false ) == false ) {
                global $wp_rewrite;
                $wp_rewrite->flush_rules();
                update_option( 'tickera_version', sanitize_text_field( $this->version ) );
            }
            if ( isset( $_REQUEST['tickera'] ) && trim( $_REQUEST['tickera'] ) != '' && isset( $_REQUEST['api_key'] ) ) {
                //api is called
                $api_call = new TC_Checkin_API(sanitize_text_field( $_REQUEST['api_key'] ), sanitize_text_field( $_REQUEST['tickera'] ));
                exit;
            }
        }

        function sales_api() {
            if ( get_option( 'tickera_version', false ) == false || get_option( 'tickera_version', false ) !== $this->version ) {
                global $wp_rewrite;
                $wp_rewrite->flush_rules();
                update_option( 'tickera_version', sanitize_text_field( $this->version ) );
            }
            if ( isset( $_REQUEST['tickera_sales'] ) && trim( $_REQUEST['tickera_sales'] ) != '' && isset( $_REQUEST['api_key'] ) ) {
                //api is called
                $api_call = new TC_Sales_API(sanitize_text_field( $_REQUEST['api_key'] ), sanitize_text_field( $_REQUEST['tickera_sales'] ));
                exit;
            }
        }

        function generate_ticket_preview() {
            if ( (current_user_can( 'manage_options' ) || current_user_can( 'edit_tc_tickets_instances' )) && (isset( $_GET['tc_preview'] ) || isset( $_GET['tc_download'] )) ) {
                $templates = new \Tickera\TC_Ticket_Templates();
                $templates->generate_preview( (int) $_GET['ticket_instance_id'], ( isset( $_GET['tc_download'] ) ? true : false ) );
            }
        }

        function get_tax_value() {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            return ( isset( $tc_general_settings['tax_rate'] ) && is_numeric( $tc_general_settings['tax_rate'] ) ? (float) $tc_general_settings['tax_rate'] : 0 );
        }

        /**
         * Get Store Currency
         *
         * @return string
         */
        function get_store_currency() {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            return ( isset( $tc_general_settings['currencies'] ) ? $tc_general_settings['currencies'] : 'USD' );
        }

        /**
         * Get currency
         *
         * @return mixed|void
         */
        function get_cart_currency() {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            return apply_filters( 'tc_cart_currency', ( isset( $tc_general_settings['currency_symbol'] ) && $tc_general_settings['currency_symbol'] != '' ? $tc_general_settings['currency_symbol'] : (( isset( $tc_general_settings['currencies'] ) ? $tc_general_settings['currencies'] : 'USD' )) ) );
        }

        /**
         * Get currency and set amount format in cart form
         *
         * @param $amount
         * @return string
         */
        function get_cart_currency_and_format( $amount ) {
            if ( empty( $amount ) || !is_numeric( $amount ) ) {
                $amount = 0;
            }
            $amount = apply_filters( 'tc_cart_currency_amount', $amount );
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $int_decimals = ( (int) $amount == (float) $amount ? 0 : 2 );
            $decimals = (int) apply_filters( 'tc_cart_amount_decimals', $int_decimals );
            $price_format = ( isset( $tc_general_settings['price_format'] ) ? $tc_general_settings['price_format'] : 'us' );
            $currency_position = ( isset( $tc_general_settings['currency_position'] ) ? $tc_general_settings['currency_position'] : 'pre_nospace' );
            switch ( $price_format ) {
                case 'us':
                    $price = number_format(
                        $amount,
                        $decimals,
                        $dec_point = ".",
                        $thousands_sep = ","
                    );
                    break;
                case 'eu':
                    $price = number_format(
                        $amount,
                        $decimals,
                        $dec_point = ",",
                        $thousands_sep = "."
                    );
                    break;
                case 'french_comma':
                    $price = number_format(
                        $amount,
                        $decimals,
                        $dec_point = ",",
                        $thousands_sep = " "
                    );
                    break;
                case 'french_dot':
                    $price = number_format(
                        $amount,
                        $decimals,
                        $dec_point = ".",
                        $thousands_sep = " "
                    );
                    break;
            }
            do_action( 'tc_price_format_check' );
            switch ( $currency_position ) {
                case 'pre_space':
                    return $this->get_cart_currency() . ' ' . $price;
                    break;
                case 'pre_nospace':
                    return $this->get_cart_currency() . '' . $price;
                    break;
                case 'post_nospace':
                    return $price . '' . $this->get_cart_currency();
                    break;
                case 'post_space':
                    return $price . ' ' . $this->get_cart_currency();
                    break;
            }
            do_action( 'tc_currency_position_check' );
        }

        function save_cart_post_data() {
            if ( isset( $_POST ) ) {
                $post_data = tickera_sanitize_array( $_POST, true, true );
                $post_data = ( $post_data ? $post_data : [] );
                $buyer_data = [];
                $owner_data = [];
                $session = $this->session->get();
                $session['cart_info']['coupon_code'] = ( isset( $session['tc_discount_code'] ) ? sanitize_text_field( $session['tc_discount_code'] ) : '' );
                $session['cart_info']['total'] = (float) $session['discounted_total'];
                $session['cart_info']['currency'] = $this->get_cart_currency();
                foreach ( $post_data as $field => $value ) {
                    if ( preg_match( '/buyer_data_/', $field ) ) {
                        $buyer_data[str_replace( 'buyer_data_', '', $field )] = sanitize_text_field( $value );
                    } elseif ( preg_match( '/owner_data_/', $field ) ) {
                        $owner_data[str_replace( 'owner_data_', '', $field )] = ( is_array( $value ) ? tickera_sanitize_array( $value, false, true ) : sanitize_text_field( $value ) );
                    }
                }
                $session['cart_info']['buyer_data'] = $buyer_data;
                $session['cart_info']['owner_data'] = $owner_data;
                $this->session->set( 'cart_info', $session['cart_info'] );
                do_action( 'tc_cart_post_data_check' );
            }
        }

        /**
         * Payment gateway form
         *
         * @param bool $echo
         * @return string
         */
        function cart_payment( $echo = false ) {
            global $blog_id, $tc_gateway_active_plugins;
            $session_discounted_total = $this->session->get( 'discounted_total' );
            $session_cart_total = $this->session->get( 'tc_cart_total' );
            $cart_total = ( !is_null( $session_cart_total ) ? (float) $session_cart_total : 0 );
            $blog_id = ( is_multisite() ? $blog_id : 1 );
            $cart = $this->get_cart_cookie();
            $content = '<div class="tickera"><form id="tc_payment_form" method="post" action="' . sanitize_text_field( $this->get_process_payment_slug( true ) ) . '">';
            if ( 0 == $cart_total ) {
                $tc_gateway_active_plugins = array();
                $free_orders = new \Tickera\Gateway\TC_Gateway_Free_Orders();
                $tc_gateway_active_plugins[0] = $free_orders;
            }
            if ( !is_null( $session_discounted_total ) && !is_null( $session_cart_total ) && round( $session_discounted_total, 2 ) == round( $session_cart_total, 2 ) ) {
                /**
                 * No session error found. Continue the process.
                 * If discount total and cart total doesn't coincide, therefore a session error occured.
                 */
            } else {
                $this->session->set( 'tc_cart_errors', __( 'Sorry, something went wrong.', 'tickera-event-ticketing-system' ) );
            }
            $session_gateway_errors = $this->session->get( 'tc_gateway_error' );
            $session_cart_errors = $this->session->get( 'tc_cart_errors' );
            if ( $session_gateway_errors || $session_cart_errors ) {
                $content .= '<div class="tc_cart_errors"><ul>';
            }
            if ( is_array( $session_gateway_errors ) ) {
                $errors = array_filter( $session_gateway_errors );
                foreach ( $errors as $error ) {
                    $content .= '<li>' . wp_kses_post( $error ) . '</li>';
                }
            } elseif ( $session_gateway_errors ) {
                $content .= '<li>' . wp_kses_post( $session_gateway_errors ) . '</li>';
            }
            if ( is_array( $session_cart_errors ) ) {
                $errors = array_filter( $session_cart_errors );
                foreach ( $errors as $error ) {
                    $content .= '<li>' . wp_kses_post( $error ) . '</li>';
                }
            } elseif ( $session_cart_errors ) {
                $content .= sprintf( 
                    /* translators: 1: Session error message 2: Cart slug */
                    __( '<li>%1$s <a href="%2$s">Please try again</a>.</li>', 'tickera-event-ticketing-system' ),
                    wp_kses_post( $session_cart_errors ),
                    esc_url( $this->get_cart_slug( true ) )
                 );
            }
            if ( $session_gateway_errors || $session_cart_errors ) {
                $content .= '</ul></div>';
            }
            $content .= $this->tc_checkout_payment_form( '', $cart );
            $content .= '</form></div>';
            if ( $echo ) {
                echo wp_kses( $content, wp_kses_allowed_html( 'tickera_payment_form' ) );
            } else {
                return $content;
            }
        }

        function active_payment_gateways() {
            global $tc_gateway_active_plugins;
            return ( $tc_gateway_active_plugins !== NULL ? count( $tc_gateway_active_plugins ) : NULL );
        }

        /**
         * Render Payment Gateways Form
         *
         *
         * @param $content
         * @param $cart
         * @return string
         */
        function tc_checkout_payment_form( $content, $cart ) {
            global $tc_gateway_active_plugins, $tc_gateway_plugins;
            $settings = get_option( 'tickera_settings' );
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $skip_payment_summary = ( isset( $tc_general_settings['skip_payment_summary_page'] ) ? $tc_general_settings['skip_payment_summary_page'] : 'no' );
            $session = $this->session->get();
            $cart_total = ( isset( $session['tc_cart_total'] ) ? (float) $session['tc_cart_total'] : null );
            if ( is_null( $cart_total ) ) {
                $tc_gateway_plugins = [];
            } elseif ( 0 == $cart_total ) {
                $free_tc_gateway_plugins = [];
                $free_tc_gateway_plugins['free_orders'] = $tc_gateway_plugins['free_orders'];
                $tc_gateway_plugins = $free_tc_gateway_plugins;
            } else {
                unset($tc_gateway_plugins['free_orders']);
            }
            $key = 0;
            $active_gateways_num = 0;
            $skip_payment_screen = false;
            foreach ( (array) $tc_gateway_plugins as $code => $plugin ) {
                if ( $this->gateway_is_network_allowed( $code ) ) {
                    $gateway = new $plugin[0]();
                    $plugin_name = ( $gateway->plugin_name == 'checkout' ? '2checkout' : $gateway->plugin_name );
                    $active_gateways = ( isset( $settings['gateways']['active'] ) ? $settings['gateways']['active'] : [] );
                    $gateway_show_priority = ( isset( $settings['gateways'][$plugin_name]['gateway_show_priority'] ) && is_numeric( $settings['gateways'][$plugin_name]['gateway_show_priority'] ) ? $settings['gateways'][$plugin_name]['gateway_show_priority'] : '30' );
                    if ( $active_gateways ) {
                        if ( in_array( $code, $active_gateways ) ) {
                            $visible = true;
                            $active_gateways_num++;
                        } else {
                            $visible = false;
                        }
                    } elseif ( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) {
                        $visible = true;
                        $active_gateways_num++;
                    } else {
                        $visible = false;
                    }
                    $active_plugins = ( isset( $settings['gateways']['active'] ) ? $settings['gateways']['active'] : [] );
                    if ( 'custom_offline_payments' == $plugin_name && in_array( $code, $active_plugins ) ) {
                        $show_gateway_admin = ( 'custom_offline_payments' == $plugin_name ? $settings['gateways']['custom_offline_payments']['admin_gateway'] : '' );
                        if ( apply_filters( 'tc_change_user_role_offline_payment', current_user_can( 'administrator' ) ) && 'yes' == $show_gateway_admin ) {
                            $visible = true;
                        } else {
                            $visible = ( 'yes' == $show_gateway_admin ? false : true );
                        }
                    }
                    if ( $visible ) {
                        $current_payment_method = ( isset( $session['tc_payment_method'] ) ? $session['tc_payment_method'] : '' );
                        if ( !$current_payment_method && !$key ) {
                            $current_payment_method = $gateway->plugin_name;
                        }
                        $skip_payment_screen = $gateway->skip_payment_screen;
                        $content .= '<div class="tickera tickera-payment-gateways' . (( !$key ? ' active' : '' )) . '" data-gateway_show_priority="' . (int) $gateway_show_priority . '">' . '<div class="' . esc_attr( $gateway->plugin_name ) . ' plugin-title">' . '<label>';
                        $content .= ( count( (array) $tc_gateway_active_plugins ) <= 2 ? '<input type="radio" class="tc_choose_gateway tickera-hide-button" id="' . esc_attr( $gateway->plugin_name ) . '" name="tc_choose_gateway" value="' . esc_attr( $gateway->plugin_name ) . '" checked ' . checked( $current_payment_method, $gateway->plugin_name, false ) . '/>' : '<input type="radio" class="tc_choose_gateway" id="' . esc_attr( $gateway->plugin_name ) . '" name="tc_choose_gateway" value="' . esc_attr( $gateway->plugin_name ) . '" ' . checked( $current_payment_method, $gateway->plugin_name, false ) . '/>' );
                        $content .= $gateway->public_name . '<img src="' . esc_url( $gateway->method_img_url ) . '" class="tickera-payment-options" alt="' . esc_attr( $gateway->plugin_name ) . '" /></label>' . '</div>' . '<div class="tc_gateway_form" id="' . esc_attr( $gateway->plugin_name ) . '">';
                        $content .= '<div class="inner-wrapper">';
                        $content .= '<p class="tc_redirect_message">';
                        $content .= apply_filters( 'tc_redirect_gateway_message', sprintf( 
                            /* translators: %s: Gateway public name. */
                            __( 'Redirecting to %s payment page...', 'tickera-event-ticketing-system' ),
                            $gateway->public_name
                         ), $gateway->public_name );
                        $content .= '</p>';
                        $content .= $gateway->payment_form( $cart ) . '<div class="actions">';
                        if ( 'free_orders' == $gateway->plugin_name ) {
                            $content .= '<input type="submit" name="tc_payment_submit" id="tc_payment_confirm" class="tickera-button tc_payment_confirm" value="' . esc_attr__( 'Continue &raquo;', 'tickera-event-ticketing-system' ) . '" />';
                        } else {
                            $content .= '<input type="submit" name="tc_payment_submit" id="tc_payment_confirm" class="tickera-button tc_payment_confirm" data-tc-check-value="tc-check-' . esc_attr( $plugin_name ) . '" value="' . esc_attr__( 'Continue Checkout &raquo;', 'tickera-event-ticketing-system' ) . '" />';
                        }
                        $content .= '</div></div></div></div>';
                        // Increment only for those visible(frontend) payment methods.
                        $key++;
                    }
                }
            }
            if ( 1 == $active_gateways_num && 'yes' == $skip_payment_summary ) {
                if ( !$skip_payment_screen ) {
                    do_action( 'tc_before_payment', $cart );
                }
            } else {
                do_action( 'tc_before_payment', $cart );
            }
            /*
             * Stop the process if cart error exists.
             * Drop cart error from session. Errors session will be displayed in the cart page.
             */
            $session_cart_errors = $this->session->get( 'tc_cart_errors' );
            if ( $session_cart_errors ) {
                $this->session->drop( 'tc_cart_errors' );
                return;
            }
            $session_gateway_errors = $this->session->get( 'tc_gateway_error' );
            $session_discounted_total = $this->session->get( 'discounted_total' );
            $session_cart_total = $this->session->get( 'tc_cart_total' );
            // Validate total values
            if ( !is_null( $session_discounted_total ) && !is_null( $session_cart_total ) && round( $session_discounted_total, 2 ) == round( $session_cart_total, 2 ) ) {
                if ( 1 == $active_gateways_num && 'yes' == $skip_payment_summary ) {
                    if ( $skip_payment_screen ) {
                        ?>
                        <script>
                            jQuery( document ).ready( function( $ ) {
                                <?php 
                        global $tc;
                        if ( isset( $tc->checkout_error ) && true == $tc->checkout_error || $session_gateway_errors ) {
                            // Don't redirect, there is an error on the checkout.
                        } else {
                            /* Redirect, everything is OK */
                            ?>
                                    $( "#tc_payment_form" ).submit();
                                <?php 
                        }
                        ?>
                                $( '#tc_payment_confirm' ).css( 'display', 'none' );
                                $( '.tc_redirect_message' ).css( 'display', 'block' );
                            } );
                        </script>
                    <?php 
                    }
                }
                return $content;
            }
        }

        /**
         * Check for new TC Checkin API calls
         *
         * @param $wp
         * @throws Exception
         */
        function action_parse_request( &$wp ) {
            if ( array_key_exists( 'tickera', $wp->query_vars ) ) {
                if ( isset( $wp->query_vars['tickera'] ) && $wp->query_vars['api_key'] ) {
                    $api_call = new TC_Checkin_API(sanitize_key( $wp->query_vars['api_key'] ), sanitize_text_field( $wp->query_vars['tickera'] ));
                    exit;
                }
            }
            // Show Cart page
            if ( array_key_exists( 'page_cart', $wp->query_vars ) ) {
                $vars = [];
                $theme_file = locate_template( ['page-cart.php'] );
                if ( $theme_file != '' ) {
                    require_once $theme_file;
                    exit;
                } else {
                    $page = new TC_Virtual_Page([
                        'slug'        => $wp->request,
                        'title'       => __( 'Cart', 'tickera-event-ticketing-system' ),
                        'content'     => $this->get_template_details( $this->plugin_dir . 'includes/templates/page-cart.php', $vars ),
                        'type'        => 'virtual_page',
                        'is_page'     => TRUE,
                        'is_singular' => TRUE,
                        'is_archive'  => FALSE,
                    ]);
                }
            }
            // Show Payment Methods page
            if ( array_key_exists( 'page_payment', $wp->query_vars ) ) {
                $vars = [];
                $theme_file = locate_template( ['page-payment.php'] );
                if ( $theme_file != '' ) {
                    require_once $theme_file;
                    exit;
                } else {
                    $page = new TC_Virtual_Page([
                        'slug'        => $wp->request,
                        'title'       => __( 'Payment', 'tickera-event-ticketing-system' ),
                        'content'     => $this->get_template_details( $this->plugin_dir . 'includes/templates/page-payment.php', $vars ),
                        'type'        => 'virtual_page',
                        'is_page'     => TRUE,
                        'is_singular' => TRUE,
                        'is_archive'  => FALSE,
                    ]);
                }
                global $tc_gateway_plugins;
                $settings = get_option( 'tickera_settings' );
                // Redirect to https if force SSL is choosen
                $gateway_force_ssl = false;
                foreach ( (array) $tc_gateway_plugins as $code => $plugin ) {
                    $gateway = ( is_array( $plugin ) ? new $plugin[0]() : new $plugin() );
                    if ( isset( $settings['gateways']['active'] ) ) {
                        if ( in_array( $code, $settings['gateways']['active'] ) || isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) {
                            if ( $gateway->force_ssl ) {
                                $gateway_force_ssl = true;
                            }
                        }
                    } elseif ( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) {
                        if ( $gateway->force_ssl ) {
                            $gateway_force_ssl = true;
                        }
                    }
                }
                if ( !is_ssl() && $gateway_force_ssl ) {
                    tickera_redirect( 'https://' . sanitize_text_field( $_SERVER['HTTP_HOST'] ) . sanitize_text_field( $_SERVER['REQUEST_URI'] ) );
                }
            }
            // Process payment page
            if ( array_key_exists( 'page_process_payment', $wp->query_vars ) ) {
                $vars = [];
                $theme_file = locate_template( ['page-process-payment.php'] );
                if ( $theme_file != '' ) {
                    require_once $theme_file;
                    exit;
                } else {
                    $page = new TC_Virtual_Page([
                        'slug'        => $wp->request,
                        'title'       => __( 'Process Payment', 'tickera-event-ticketing-system' ),
                        'content'     => $this->get_template_details( $this->plugin_dir . 'includes/templates/page-process-payment.php', $vars ),
                        'type'        => 'virtual_page',
                        'is_page'     => TRUE,
                        'is_singular' => TRUE,
                        'is_archive'  => FALSE,
                    ]);
                }
            }
            // Order status page and ticket downloads
            if ( array_key_exists( 'page_order', $wp->query_vars ) && array_key_exists( 'tc_order_key', $wp->query_vars ) ) {
                $vars = [];
                $theme_file = locate_template( ['page-order.php'] );
                if ( $theme_file != '' ) {
                    require_once $theme_file;
                    exit;
                } else {
                    $page = new TC_Virtual_Page([
                        'slug'        => $wp->request,
                        'title'       => __( 'Order', 'tickera-event-ticketing-system' ),
                        'content'     => $this->get_template_details( $this->plugin_dir . 'includes/templates/page-order.php', $vars ),
                        'type'        => 'virtual_page',
                        'is_page'     => TRUE,
                        'is_singular' => TRUE,
                        'is_archive'  => FALSE,
                    ]);
                }
            }
            // Payment confirmation page
            if ( array_key_exists( 'page_confirmation', $wp->query_vars ) ) {
                $vars = [];
                $theme_file = locate_template( ['page-confirmation.php'] );
                if ( $theme_file != '' ) {
                    require_once $theme_file;
                    exit;
                } else {
                    $page = new TC_Virtual_Page([
                        'slug'        => $wp->request,
                        'title'       => __( 'Confirmation', 'tickera-event-ticketing-system' ),
                        'content'     => $this->get_template_details( $this->plugin_dir . 'includes/templates/page-confirmation.php', $vars ),
                        'type'        => 'virtual_page',
                        'is_page'     => TRUE,
                        'is_singular' => TRUE,
                        'is_archive'  => FALSE,
                    ]);
                }
            }
        }

        function get_template_details( $template, $args = array() ) {
            ob_start();
            extract( $args );
            require_once $template;
            return ob_get_clean();
        }

        function filter_query_vars( $query_vars ) {
            $query_vars[] = 'page_cart';
            $query_vars[] = 'page_payment';
            $query_vars[] = 'page_process_payment';
            $query_vars[] = 'page_confirmation';
            $query_vars[] = 'payment_gateway_return';
            $query_vars[] = 'page_order';
            $query_vars[] = 'tc_order';
            $query_vars[] = 'tc_order_return';
            $query_vars[] = 'tc_order_key';
            $query_vars[] = 'tickera';
            $query_vars[] = 'api_key';
            $query_vars[] = 'checksum';
            $query_vars[] = 'check_in';
            $query_vars[] = 'results_per_page';
            $query_vars[] = 'page_number';
            $query_vars[] = 'keyword';
            $query_vars[] = 'tickera_tickera';
            $query_vars[] = 'period';
            $query_vars[] = 'order_id';
            $query_vars[] = 'event_id';
            return $query_vars;
        }

        function add_rewrite_rules( $rules ) {
            $new_rules['^' . $this->get_payment_gateway_return_slug() . '/(.+)'] = 'index.php?page_id=-1&payment_gateway_return=$matches[1]';
            if ( !$this->get_payment_page() ) {
                $new_rules['^' . $this->get_payment_slug()] = 'index.php?page_id=-1&page_payment';
            }
            if ( !$this->get_confirmation_page() ) {
                $new_rules['^' . $this->get_confirmation_slug() . '/(.+)'] = 'index.php?page_id=-1&page_confirmation&tc_order_return=$matches[1]';
            } else {
                $page_id = get_option( 'tickera_confirmation_page_id', false );
                $page = get_post( $page_id, OBJECT );
                if ( $page ) {
                    $parent_page_id = wp_get_post_parent_id( $page_id );
                    $parent_page = get_post( $parent_page_id, OBJECT );
                    $page_slug = ( $parent_page ? $parent_page->post_name . '/' . $page->post_name : $page->post_name );
                    $new_rules['^' . $page_slug . '/(.+)'] = 'index.php?pagename=' . $page_slug . '&tc_order_return=$matches[1]';
                }
            }
            if ( !$this->get_order_page() ) {
                $new_rules['^' . $this->get_order_slug() . '/(.+)/(.+)'] = 'index.php?page_id=-1&page_order&tc_order=$matches[1]&tc_order_key=$matches[2]';
            } else {
                $page_id = get_option( 'tickera_order_page_id', false );
                $page = get_post( $page_id, OBJECT );
                if ( $page ) {
                    $parent_page_id = wp_get_post_parent_id( $page_id );
                    $parent_page = get_post( $parent_page_id, OBJECT );
                    $page_slug = ( $parent_page ? $parent_page->post_name . '/' . $page->post_name : $page->post_name );
                    $new_rules['^' . $page_slug . '/(.+)/(.+)'] = 'index.php?pagename=' . $page_slug . '&tc_order=$matches[1]&tc_order_key=$matches[2]';
                }
            }
            $new_rules['^' . $this->get_process_payment_slug()] = 'index.php?page_id=-1&page_process_payment';
            // Check-in API
            $new_rules['^tc-api/(.+)/translation'] = 'index.php?tickera=tickera_translation&api_key=$matches[1]';
            $new_rules['^tc-api/(.+)/check_credentials'] = 'index.php?tickera=tickera_check_credentials&api_key=$matches[1]';
            $new_rules['^tc-api/(.+)/event_essentials'] = 'index.php?tickera=tickera_event_essentials&api_key=$matches[1]';
            $new_rules['^tc-api/(.+)/ticket_checkins/(.+)'] = 'index.php?tickera=tickera_checkins&api_key=$matches[1]&checksum=$matches[2]';
            if ( isset( $_GET['timestamp'] ) ) {
                $new_rules['^tc-api/(.+)/check_in/(.+)'] = 'index.php?tickera=tickera_scan&api_key=$matches[1]&checksum=$matches[2]&timestamp=' . (int) $_GET['timestamp'];
            } else {
                $new_rules['^tc-api/(.+)/check_in/(.+)'] = 'index.php?tickera=tickera_scan&api_key=$matches[1]&checksum=$matches[2]';
            }
            $new_rules['^tc-api/(.+)/tickets_info/(.+)/(.+)/(.+)'] = 'index.php?tickera=tickera_tickets_info&api_key=$matches[1]&results_per_page=$matches[2]&page_number=$matches[3]&keyword=$matches[4]';
            $new_rules['^tc-api/(.+)/tickets_info/(.+)/(.+)'] = 'index.php?tickera=tickera_tickets_info&api_key=$matches[1]&results_per_page=$matches[2]&page_number=$matches[3]';
            $new_rules['^tc-api/(.+)/sales_check_credentials'] = 'index.php?tickera_sales=sales_check_credentials&api_key=$matches[1]';
            $new_rules['^tc-api/(.+)/sales_stats_general/(.+)/(.+)/(.+)'] = 'index.php?tickera_sales=sales_stats_general&api_key=$matches[1]&period=$matches[2]&results_per_page=$matches[3]&page_number=$matches[4]';
            $new_rules['^tc-api/(.+)/sales_stats_event/(.+)/(.+)/(.+)/(.+)'] = 'index.php?tickera_sales=sales_stats_event&api_key=$matches[1]&event_id=$matches[2]&period=$matches[3]&results_per_page=$matches[4]&page_number=$matches[5]';
            $new_rules['^tc-api/(.+)/sales_stats_order/(.+)'] = 'index.php?tickera_sales=sales_stats_order&api_key=$matches[1]&order_id=$matches[2]';
            return array_merge( $new_rules, $rules );
        }

        function get_cart_cookie() {
            $cart = [];
            $cookie_id = 'tc_cart_' . COOKIEHASH;
            if ( isset( $_COOKIE[$cookie_id] ) ) {
                $cart_obj = json_decode( stripslashes( sanitize_text_field( $_COOKIE[$cookie_id] ) ), true );
                foreach ( $cart_obj as $ticket_id => $qty ) {
                    if ( $qty > 0 ) {
                        $cart[(int) $ticket_id] = (int) $qty;
                    } else {
                        unset($cart[(int) $ticket_id]);
                    }
                }
            } else {
                $cart = [];
            }
            return ( isset( $cart ) ? $cart : [] );
        }

        /**
         * Saves cart array to cookie
         *
         * @param $cart
         */
        function set_cart_cookie( $cart ) {
            ob_start();
            $cookie_id = 'tc_cart_' . COOKIEHASH;
            unset($_COOKIE[$cookie_id]);
            setcookie(
                $cookie_id,
                '',
                -1,
                '/'
            );
            // Set cookie
            $cart = array_map( 'absint', $cart );
            $expire = time() + apply_filters( 'tc_cart_cookie_expiration', 172800 );
            // 72 hrs expiration by default
            setcookie(
                $cookie_id,
                json_encode( $cart ),
                $expire,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            $_COOKIE[$cookie_id] = json_encode( $cart );
            ob_end_flush();
        }

        /**
         * Add ticket and quantity to cart.
         * If set to skip add to cart, it will simply recreate the add to cart button.
         */
        function add_to_cart() {
            if ( isset( $_POST['ticket_id'] ) && isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tickera_add_to_cart_ajax' ) ) {
                $ticket_id = (int) $_POST['ticket_id'];
                $quantity = ( isset( $_POST['tc_qty'] ) && !empty( $_POST['tc_qty'] ) ? (int) $_POST['tc_qty'] : 1 );
                $quantity_selector = ( isset( $_POST['tc_qty_selector'] ) ? (bool) $_POST['tc_qty_selector'] : false );
                $type = ( isset( $_POST['tc_type'] ) ? sanitize_text_field( $_POST['tc_type'] ) : 'cart' );
                $method = ( isset( $_POST['tc_open_method'] ) ? sanitize_text_field( $_POST['tc_open_method'] ) : 'regular' );
                $title = ( isset( $_POST['tc_title'] ) ? sanitize_text_field( $_POST['tc_title'] ) : __( 'Add to Cart', 'tickera-event-ticketing-system' ) );
                $soldout_message = ( isset( $_POST['tc_soldout_message'] ) ? sanitize_text_field( $_POST['tc_soldout_message'] ) : __( 'Tickets are sold out.', 'tickera-event-ticketing-system' ) );
                $skip_add_to_cart = ( isset( $_POST['tc_skip_add_to_cart'] ) ? (bool) $_POST['tc_skip_add_to_cart'] : false );
                $cart = [];
                if ( !$skip_add_to_cart ) {
                    $prev_cart = $this->get_cart_cookie( true );
                    foreach ( $prev_cart as $id => $qty ) {
                        $cart[$id] = (int) $qty;
                    }
                    $cart[$ticket_id] = ( isset( $cart[$ticket_id] ) ? (int) $cart[$ticket_id] + $quantity : $quantity );
                    $this->set_cart_cookie( $cart );
                }
                do_action(
                    'tickera_track_added_to_cart',
                    $ticket_id,
                    $quantity,
                    $cart
                );
                if ( ob_get_length() > 0 ) {
                    ob_end_clean();
                }
                ob_start();
                echo wp_kses( TC_Shortcodes::render_ticket_cart_button( [
                    'id'              => $ticket_id,
                    'type'            => $type,
                    'open_method'     => $method,
                    'title'           => $title,
                    'soldout_message' => $soldout_message,
                    'quantity'        => $quantity_selector,
                ] ), wp_kses_allowed_html( 'tickera_add_to_cart' ) );
                ob_end_flush();
                exit;
            }
        }

        function update_cart_widget() {
            if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                $cart_contents = $this->get_cart_cookie();
                if ( !empty( $cart_contents ) ) {
                    do_action( 'tc_cart_before_ul', $cart_contents );
                    ?>
                    <ul class='tc_cart_ul'>
                        <?php 
                    foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                        ?>
                            <?php 
                        $ticket = new \Tickera\TC_Ticket($ticket_type);
                        ?>
                            <li id='tc_ticket_type_<?php 
                        echo esc_attr( (int) $ticket_type );
                        ?>'>
                                <?php 
                        echo wp_kses_post( apply_filters(
                            'tc_cart_widget_item',
                            $ordered_count . ' x ' . $ticket->details->post_title . ' (' . apply_filters( 'tc_cart_currency_and_format', tickera_get_ticket_price( $ticket->details->ID ) * $ordered_count ) . ')',
                            $ordered_count,
                            $ticket->details->post_title,
                            tickera_get_ticket_price( $ticket->details->ID )
                        ) );
                        ?>
                            </li>
                        <?php 
                    }
                    ?>
                    </ul>
                    <?php 
                    do_action( 'tc_cart_after_ul', $cart_contents );
                    ?>
                <?php 
                } else {
                    ?>
                    <?php 
                    do_action( 'tc_cart_before_empty' );
                    ?>
                    <span class='tc_empty_cart'><?php 
                    esc_html_e( 'The cart is empty', 'tickera-event-ticketing-system' );
                    ?></span>
                    <?php 
                    do_action( 'tc_cart_after_empty' );
                    ?>
                <?php 
                }
                exit;
            }
        }

        /**
         * Update Cart Contents
         * This includes discount code, empty cart and proceed checkout
         *
         * @global array $required_fields_error_count  Error Codes
         *
         * Error Code 100: Required tickets' minimum quantity per order.
         * Error Code 101: Required tickets' maximums quantity per order.
         * Error Code 102: Not enough ticket quantity left.
         * Error Code 103: No item quantity added in cart.
         * Error Code 104: Maximum number of ticket purchases has been reached.
         * Error Code 105: Ticket is not saleable.
         */
        function update_cart() {
            global $tc_cart_errors, $cart_error_number, $tc_cart_tickets_error_codes;
            ob_start();
            $session = $this->session->get();
            $cart_error_number = 0;
            $required_fields_error_count = 0;
            $cart_action = ( isset( $_POST['cart_action'] ) ? sanitize_key( $_POST['cart_action'] ) : '' );
            $valid_cart_actions = [
                'empty_cart',
                'update_cart',
                'apply_coupon',
                'proceed_to_checkout'
            ];
            if ( $cart_action && in_array( $cart_action, $valid_cart_actions ) ) {
                $cart = [];
                $updated_cart_contents = [];
                $tc_cart_errors .= '<ul>';
                if ( in_array( $cart_action, ['proceed_to_checkout', 'update_cart'] ) ) {
                    $qty_count_per_event = [];
                    $ticket_cart_ids = ( isset( $_POST['ticket_cart_id'] ) ? map_deep( $_POST['ticket_cart_id'], 'absint' ) : [] );
                    $ticket_cart_quantities = ( isset( $_POST['ticket_quantity'] ) ? map_deep( $_POST['ticket_quantity'], 'absint' ) : [] );
                    // Restructure POST data for tickets_cart_ids
                    foreach ( $ticket_cart_ids as $key => $ticket_type_id ) {
                        $updated_cart_contents[(int) $ticket_type_id] = (int) $ticket_cart_quantities[$key];
                    }
                    foreach ( $updated_cart_contents as $ticket_type_id => $qty_count ) {
                        $ticket = new \Tickera\TC_Ticket($ticket_type_id);
                        if ( $qty_count <= 0 ) {
                            /**
                             * Remove cart item if quantity is zero
                             */
                            unset($cart[$ticket_type_id]);
                            $tc_cart_tickets_error_codes[$ticket_type_id]['errors'][] = 103;
                        } elseif ( !\Tickera\TC_Ticket::is_sales_available( $ticket_type_id ) ) {
                            /**
                             * Mark the item as sold out if not saleable.
                             *
                             * Triggered by:
                             * 1. Ticket Quantity
                             * 2. Ticket Sales Availibality
                             */
                            $tc_cart_errors .= '<li>';
                            $tc_cart_errors .= sprintf( 
                                /* translators: %s: Ticket type name */
                                __( '"%s" tickets are sold out', 'tickera-event-ticketing-system' ),
                                $ticket->details->post_title
                             );
                            $tc_cart_errors .= '</li>';
                            $tc_cart_tickets_error_codes[$ticket_type_id]['errors'][] = 105;
                            $cart_error_number++;
                        } else {
                            $event_id = get_post_meta( $ticket_type_id, 'event_name', true );
                            $event_metas = get_post_meta( $event_id );
                            $limit_on_event_level = ( isset( $event_metas['limit_level'] ) && $event_metas['limit_level'][0] ? true : false );
                            if ( $limit_on_event_level ) {
                                /**
                                 * Ticket quantity limitation: Per event
                                 * Retrieve the remaining quantity of an event
                                 */
                                // Count all committed ticket quantity of an event
                                $qty_count_per_event[$event_id] = @$qty_count_per_event[$event_id] + $qty_count;
                                $limit_level_value = '';
                                // Unlimited as default
                                if ( isset( $event_metas['limit_level_value'] ) && '' != $event_metas['limit_level_value'][0] ) {
                                    $limit_level_value = (int) $event_metas['limit_level_value'][0];
                                }
                                $event_ticket_sold_count = tickera_get_event_tickets_count_sold( $event_id );
                                $quantity_left = ( '' === $limit_level_value ? 99999 : (int) $limit_level_value - (int) $event_ticket_sold_count );
                                // Retrieve the remaining available quantity of an event
                                if ( $qty_count_per_event[$event_id] >= $quantity_left ) {
                                    $quantity_left = $qty_count - ($qty_count_per_event[$event_id] - $quantity_left);
                                }
                            } else {
                                /**
                                 * Ticket quantity limitation: Per ticket type (Default)
                                 * Retrieve the remaining quantity of a ticket
                                 */
                                $quantity_left = $ticket->get_tickets_quantity_left();
                            }
                            if ( $quantity_left >= $qty_count ) {
                                $cart[$ticket_type_id] = (int) $qty_count;
                                /**
                                 * Cart item doesn't meet the minimum qty per order,
                                 * assign minimum value as quantity
                                 */
                                if ( $ticket->details->min_tickets_per_order && $qty_count < $ticket->details->min_tickets_per_order ) {
                                    $tc_cart_errors .= '<li>';
                                    $tc_cart_errors .= sprintf( 
                                        /* translators: 1: Ticket type name 2: A minimum number of tickets per order. */
                                        __( 'Minimum order quantity for "%1$s" is %2$s', 'tickera-event-ticketing-system' ),
                                        $ticket->details->post_title,
                                        $ticket->details->min_tickets_per_order
                                     );
                                    $tc_cart_errors .= '</li>';
                                    $tc_cart_tickets_error_codes[$ticket_type_id]['errors'][] = 100;
                                    $cart_error_number++;
                                }
                                /**
                                 * Cart item doesn't meet the maximum qty per order,
                                 * assign maximum value as quantity
                                 */
                                // Limit to 500 to avoid overload
                                $default_qty_limit = apply_filters( 'tc_cart_quantity_default_limit', 500 );
                                if ( !$ticket->details->max_tickets_per_order && $qty_count > $default_qty_limit ) {
                                    $max_tickets_per_order = $default_qty_limit;
                                } elseif ( $ticket->details->max_tickets_per_order && $qty_count > $ticket->details->max_tickets_per_order ) {
                                    $max_tickets_per_order = $ticket->details->max_tickets_per_order;
                                } else {
                                    $max_tickets_per_order = 0;
                                }
                                if ( $max_tickets_per_order ) {
                                    $cart[$ticket_type_id] = (int) $max_tickets_per_order;
                                    $tc_cart_errors .= '<li>';
                                    $tc_cart_errors .= sprintf( 
                                        /* translators: 1: Ticket type name 2: A maximum number of tickets per order */
                                        __( 'Maximum order quantity for "%1$s" is %2$s', 'tickera-event-ticketing-system' ),
                                        $ticket->details->post_title,
                                        $max_tickets_per_order
                                     );
                                    $tc_cart_errors .= '</li>';
                                    $tc_cart_tickets_error_codes[$ticket_type_id]['errors'][] = 101;
                                    $cart_error_number++;
                                }
                            } else {
                                if ( $quantity_left > 0 ) {
                                    $tc_cart_errors .= '<li>';
                                    $tc_cart_errors .= sprintf(
                                        /* translators: 1: A number of quantity left 2: Ticket type name 3: Singular or plural form of a string "ticket". */
                                        __( 'Only %1$s "%2$s" %3$s left', 'tickera-event-ticketing-system' ),
                                        $quantity_left,
                                        $ticket->details->post_title,
                                        ( $quantity_left > 1 ? __( 'tickets', 'tickera-event-ticketing-system' ) : __( 'ticket', 'tickera-event-ticketing-system' ) )
                                    );
                                    $tc_cart_errors .= '</li>';
                                } else {
                                    $tc_cart_errors .= '<li>';
                                    $tc_cart_errors .= sprintf( 
                                        /* translators: %s: Ticket type name. */
                                        __( '"%s" tickets are sold out', 'tickera-event-ticketing-system' ),
                                        $ticket->details->post_title
                                     );
                                    $tc_cart_errors .= '</li>';
                                }
                                $cart[$ticket_type_id] = (int) $quantity_left;
                                $tc_cart_tickets_error_codes[$ticket_type_id]['errors'][] = 102;
                                $cart_error_number++;
                            }
                        }
                        // Limit user purchases when Force Login is active
                        $tc_general_settings = get_option( 'tickera_general_setting', false );
                        $force_login = ( isset( $tc_general_settings['force_login'] ) ? $tc_general_settings['force_login'] : 'no' );
                        $user_purchased_count = tickera_get_tickets_user_purchased_count( get_current_user_id(), $ticket_type_id );
                        if ( 'yes' == $force_login && isset( $ticket->details->max_tickets_per_user ) && $ticket->details->max_tickets_per_user && $user_purchased_count + $qty_count > $ticket->details->max_tickets_per_user ) {
                            $tc_cart_errors .= '<li>';
                            $tc_cart_errors .= sprintf( 
                                /* translators: %s: Ticket type name. */
                                __( '"%s" You have reached the maximum number of purchases of this ticket', 'tickera-event-ticketing-system' ),
                                $ticket->details->post_title
                             );
                            $tc_cart_errors .= '</li>';
                            $tc_cart_tickets_error_codes[$ticket_type_id]['errors'][] = 104;
                            $cart_error_number++;
                        }
                        $tc_cart_errors = apply_filters( 'tc_add_cart_errors', $tc_cart_errors, $ticket );
                    }
                    $cart_error_number = apply_filters( 'tc_cart_error_number', $cart_error_number );
                    $this->update_cart_cookie( $cart );
                    $discount = new \Tickera\TC_Discounts();
                    /**
                     * @var float $total value is not necessary in the following discount process.
                     * @var string $session_discount_code pass to param to calculate discounted_total correctly.
                     */
                    $session_discount_code = ( isset( $session['tc_discount_code'] ) ? sanitize_text_field( $session['tc_discount_code'] ) : '' );
                    $discount->discounted_cart_total( false, $session_discount_code );
                    if ( empty( $cart ) ) {
                        $this->remove_order_session_data( false );
                    }
                } elseif ( 'empty_cart' == $cart_action ) {
                    $this->remove_order_session_data( false );
                } elseif ( 'apply_coupon' == $cart_action ) {
                    $discount = new \Tickera\TC_Discounts();
                    $discount->discounted_cart_total();
                }
                /*
                 * Additional validation when proceeding to checkout.
                 * Make sure all default fields have been filled in.
                 */
                if ( 'proceed_to_checkout' == $cart_action ) {
                    // Array of required field names
                    $required_fields = array_map( 'sanitize_text_field', $_POST['tc_cart_required'] );
                    $post_data = tickera_sanitize_array( $_POST, false, true );
                    $post_data = ( $post_data ? $post_data : [] );
                    foreach ( $post_data as $key => $value ) {
                        if ( $key !== 'tc_cart_required' ) {
                            if ( in_array( $key, $required_fields ) ) {
                                if ( !is_array( $value ) ) {
                                    if ( trim( $value ) == '' ) {
                                        $required_fields_error_count++;
                                    }
                                } else {
                                    foreach ( $post_data[$key] as $val ) {
                                        if ( !is_array( $val ) ) {
                                            if ( trim( $val ) == '' ) {
                                                $required_fields_error_count++;
                                            }
                                        } else {
                                            foreach ( $val as $val_str ) {
                                                if ( trim( $val_str ) == '' ) {
                                                    $required_fields_error_count++;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ( $required_fields_error_count > 0 ) {
                        $tc_cart_errors .= '<li>' . esc_html__( 'All fields marked with * are required.', 'tickera-event-ticketing-system' ) . '</li>';
                    }
                    do_action( 'tc_cart_before_error_pass_check', $cart_error_number, $tc_cart_errors );
                    if ( $cart_error_number == 0 && $required_fields_error_count == 0 ) {
                        $this->save_cart_post_data();
                        do_action( 'tc_cart_passed_successfully' );
                        // Redirect to payment page
                        if ( apply_filters( 'tc_can_redirect_to_payment_page', true ) ) {
                            tickera_redirect( $this->get_payment_slug( true ) );
                        }
                    }
                }
                $cart_errors = ( isset( $session['tc_cart_errors'] ) ? $session['tc_cart_errors'] : '' );
                $cart_errors .= $tc_cart_errors;
                if ( !in_array( $cart_errors, ['<ul>', '', null] ) ) {
                    $this->session->set( 'tc_cart_errors', $cart_errors );
                }
                // Redirect to cart page
                if ( in_array( $cart_action, ['empty_cart', 'apply_coupon', 'update_cart'] ) || $cart_error_number || $required_fields_error_count ) {
                    tickera_redirect( $this->get_cart_slug( true ) );
                }
            }
        }

        /**
         * Collect and Return Error Notices
         *
         * @param $errors
         * @return string
         */
        function tc_cart_errors( $errors ) {
            global $tc_cart_errors;
            $errors = $errors . $tc_cart_errors;
            return $errors;
        }

        /**
         * Generate Unique String.
         * Use to generate Order Id
         *
         * @return false|string
         */
        function create_unique_id() {
            $data = '';
            $uid = uniqid( "", true );
            $data .= ( isset( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : rand( 1, 999 ) );
            $data .= ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : rand( 1, 999 ) );
            $data .= ( isset( $_SERVER['LOCAL_ADDR'] ) ? sanitize_text_field( $_SERVER['LOCAL_ADDR'] ) : rand( 1, 999 ) );
            $data .= ( isset( $_SERVER['LOCAL_PORT'] ) ? sanitize_text_field( $_SERVER['LOCAL_PORT'] ) : rand( 1, 999 ) );
            $data .= ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : rand( 1, 999 ) );
            $data .= ( isset( $_SERVER['REMOTE_PORT'] ) ? sanitize_text_field( $_SERVER['REMOTE_PORT'] ) : rand( 1, 999 ) );
            $tuid = substr( strtoupper( hash( 'ripemd128', $uid . md5( $data ) ) ), 0, apply_filters( 'tc_unique_id_length', 10 ) );
            if ( apply_filters( 'tc_use_only_digit_order_number', false ) == true ) {
                $tuid_array = tickera_unistr_to_ords( $tuid );
                $tuid = '';
                foreach ( $tuid_array as $tuid_array_key => $val ) {
                    $tuid .= $val;
                }
            }
            return $tuid;
        }

        function maybe_skip_confirmation_screen( $gateway_class, $order ) {
            $settings = get_option( 'tickera_settings' );
            $skip_confirmation_screen = ( isset( $settings['gateways'][$gateway_class->plugin_name]['skip_confirmation_page'] ) ? $settings['gateways'][$gateway_class->plugin_name]['skip_confirmation_page'] : 'no' );
            $skip_confirmation_screen = ( isset( $gateway_class->skip_confirmation_page ) && $gateway_class->skip_confirmation_page ? true : $skip_confirmation_screen );
            // Fallback to JS redirection if headers are already sent
            if ( 'yes' == $skip_confirmation_screen ) {
                ?>
                <script type="text/javascript">
                    jQuery( document ).ready( function( $ ) {
                        jQuery( 'body' ).hide();
                    } );
                    window.location = "<?php 
                echo esc_url( $this->tc_order_status_url(
                    $order,
                    $order->details->tc_order_date,
                    '',
                    false
                ) );
                ?>";
                </script>
                <?php 
                tickera_redirect( $this->tc_order_status_url(
                    $order,
                    $order->details->tc_order_date,
                    '',
                    false
                ) );
            }
        }

        /**
         * Get order details page url.
         *
         * @param string $order
         * @param string $order_key
         * @param string $link_title
         * @param bool $link
         * @return string
         */
        function tc_order_status_url(
            $order = '',
            $order_key = '',
            $link_title = '',
            $link = true
        ) {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $use_order_details_pretty_links = ( isset( $tc_general_settings['use_order_details_pretty_links'] ) ? $tc_general_settings['use_order_details_pretty_links'] : 'yes' );
            if ( is_object( $order ) ) {
                $order_title = $order->details->post_title;
                $order_key = ( $order_key ? $order_key : $order->details->tc_order_date );
            }
            $order_details_arg = [];
            if ( isset( $order_title ) && $order_title ) {
                $order_details_arg['tc_order'] = $order_title;
            }
            if ( $order_key ) {
                $order_details_arg['tc_order_key'] = $order_key;
            }
            if ( 'no' == $use_order_details_pretty_links ) {
                $order_details_url = add_query_arg( $order_details_arg, trailingslashit( $this->get_order_slug( true ) ) );
            } else {
                $order_details_url = trailingslashit( $this->get_order_slug( true ) );
                $order_details_url .= ( isset( $order_title ) && $order_title ? $order_title . '/' : '' );
                $order_details_url .= ( $order_key ? $order_key : '' );
            }
            return ( $link ? '<a href="' . esc_url( $order_details_url ) . '">' . $link_title . '</a>' : $order_details_url );
        }

        /**
         * Generate content for order confirmation
         *
         * @param $content
         * @param $order
         * @return string
         */
        function tc_order_confirmation_message_content( $content, $order ) {
            $order_status_url = $this->tc_order_status_url( $order, $order->details->tc_order_date, __( 'here', 'tickera-event-ticketing-system' ) );
            switch ( $order->details->post_status ) {
                case 'order_received':
                case 'order_fraud':
                case 'order_cancelled':
                case 'order_refunded':
                    $content .= sprintf( 
                        /* translators: %s: A link to the order status page. */
                        __( 'You can check your order status %s.', 'tickera-event-ticketing-system' ),
                        $order_status_url
                     );
                    break;
                case 'order_paid':
                    $content .= sprintf( 
                        /* translators: %s: A link to the order status page. */
                        __( 'You can check your order status and download tickets %s.', 'tickera-event-ticketing-system' ),
                        $order_status_url
                     );
                    break;
            }
            return $content;
        }

        function generate_order_id() {
            global $wpdb;
            $count = true;
            while ( $count ) {
                $order_id = $this->create_unique_id();
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_title = %s AND post_type = 'tc_orders'", $order_id ) );
            }
            $order_id = apply_filters( 'tc_order_id', $order_id );
            $session_order = $this->session->get( 'tc_order' );
            if ( is_null( $session_order ) ) {
                $this->session->set( 'tc_order', $order_id );
            } else {
                $order_id = sanitize_text_field( $session_order );
            }
            return $order_id;
        }

        /**
         * Deprecated
         *
         * @param type $discount_code
         */
        function update_discount_code_cookie( $discount_code ) {
            $discount_code = sanitize_text_field( $discount_code );
            $cookie_id = 'tc_discount_code_' . COOKIEHASH;
            // Put discount code in a cookie
            $expire = time() + apply_filters( 'tc_discount_cookie_expiration', 172800 );
            // 72 hrs expire by default
            setcookie(
                $cookie_id,
                $discount_code,
                $expire,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
        }

        function update_cart_cookie( $cart ) {
            ob_start();
            $cart = array_map( 'absint', $cart );
            $cookie_id = 'tc_cart_' . COOKIEHASH;
            // Set cookie
            $expire = time() + apply_filters( 'tc_cart_cookie_expiration', 172800 );
            //72 hrs expire by default
            setcookie(
                $cookie_id,
                json_encode( $cart ),
                $expire,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            // Set the cookie variable as well, just in case something goes wrong ;)
            $_COOKIE[$cookie_id] = json_encode( $cart );
            ob_end_flush();
        }

        function get_front_end_invisible_post_types() {
            $post_types = array(
                'tc_templates',
                'tc_api_keys',
                'tc_tickets',
                'tc_tickets_instances',
                'tc_orders',
                'tc_forms',
                'tc_form_fields',
                'tc_custom_fonts'
            );
            return apply_filters( 'tc_get_front_end_invisible_post_types', $post_types );
        }

        function non_visible_post_types_404() {
            global $post;
            if ( is_single( $post ) && in_array( get_post_type( $post ), $this->get_front_end_invisible_post_types() ) ) {
                global $wp_query;
                $wp_query->set_404();
                status_header( 404 );
            }
        }

        /**
         * Setup proper directories
         */
        function init_vars() {
            global $tc_plugin_dir, $tc_plugin_url;
            if ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . $this->dir_name . '/' . basename( __FILE__ ) ) ) {
                $this->location = 'subfolder-plugins';
                $this->plugin_dir = WP_PLUGIN_DIR . '/' . $this->dir_name . '/';
                $this->plugin_url = plugins_url( '/', __FILE__ );
            } elseif ( defined( 'WP_PLUGIN_URL' ) && defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
                $this->location = 'plugins';
                $this->plugin_dir = WP_PLUGIN_DIR . '/';
                $this->plugin_url = plugins_url( '/', __FILE__ );
            } elseif ( is_multisite() && defined( 'WPMU_PLUGIN_URL' ) && defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/' . basename( __FILE__ ) ) ) {
                $this->location = 'mu-plugins';
                $this->plugin_dir = WPMU_PLUGIN_DIR;
                $this->plugin_url = WPMU_PLUGIN_URL;
            } else {
                wp_die( esc_html( sprintf( 
                    /* translators: %s: Tickera */
                    __( 'There was an issue determining where %s is installed. Please reinstall it.', 'tickera-event-ticketing-system' ),
                    esc_html( $this->title )
                 ) ) );
            }
            $tc_plugin_dir = $this->plugin_dir;
            $tc_plugin_url = $this->plugin_url;
        }

        /**
         * Add plugin admin menu items
         */
        function add_admin_menu() {
            global $first_tc_menu_handler;
            add_dashboard_page(
                '',
                '',
                'manage_options',
                'tc-installation-wizard',
                ''
            );
            $plugin_admin_menu_items = array(
                'events'           => __( 'Events', 'tickera-event-ticketing-system' ),
                'ticket_templates' => __( 'Ticket Templates', 'tickera-event-ticketing-system' ),
                'discount_codes'   => __( 'Discount Codes', 'tickera-event-ticketing-system' ),
                'settings'         => __( 'Settings', 'tickera-event-ticketing-system' ),
            );
            if ( $this->title == 'Tickera' ) {
                /*
                 * Do not show addons for (assumed) white-labeled plugin
                 *
                 * $plugin_admin_menu_items['addons'] = __('Add-ons', 'tickera-event-ticketing-system');
                 * add_filter('tc_fs_show_addons', '__return_true');
                 */
            } else {
                // add_filter('tc_fs_show_addons', '__return_true');
            }
            $plugin_admin_menu_items = apply_filters( 'tc_plugin_admin_menu_items', $plugin_admin_menu_items );
            $plugin_admin_menu_items = array_map( 'sanitize_text_field', $plugin_admin_menu_items );
            // Add the sub menu items
            $number_of_sub_menu_items = 0;
            $first_tc_menu_handler = '';
            foreach ( $plugin_admin_menu_items as $handler => $value ) {
                if ( $number_of_sub_menu_items == 0 ) {
                    $first_tc_menu_handler = apply_filters( 'first_tc_menu_handler', $this->name . '_' . $handler );
                    do_action( $this->name . '_add_menu_items_up' );
                } else {
                    $capability = ( 'addons' == $handler ? 'manage_options' : 'manage_' . $handler . '_cap' );
                    add_submenu_page(
                        $first_tc_menu_handler,
                        $value,
                        $value,
                        $capability,
                        $this->name . '_' . $handler,
                        'tickera_' . $handler . '_admin'
                    );
                    do_action( $this->name . '_add_menu_items_after_' . $handler );
                }
                $number_of_sub_menu_items++;
            }
            do_action( $this->name . '_add_menu_items_down' );
        }

        function add_network_admin_menu() {
            if ( !apply_filters( 'tc_add_network_admin_menu', true ) ) {
                return;
            }
            global $first_tc_network_menu_handler;
            $plugin_admin_menu_items = array(
                'network_settings' => 'Settings',
            );
            apply_filters( 'tc_plugin_network_admin_menu_items', $plugin_admin_menu_items );
            // Add the sub menu items
            $number_of_sub_menu_items = 0;
            $first_tc_network_menu_handler = '';
            foreach ( $plugin_admin_menu_items as $handler => $value ) {
                if ( $number_of_sub_menu_items == 0 ) {
                    $first_tc_network_menu_handler = $this->name . '_' . $handler;
                    $submenu_title = sprintf( 
                        /* translators: %s: Tickera submenu title */
                        __( '%s', 'tickera-event-ticketing-system' ),
                        $value
                     );
                    add_menu_page(
                        $this->name,
                        $this->title,
                        'manage_' . $handler . '_cap',
                        $this->name . '_' . $handler,
                        'tickera_' . $handler . '_admin'
                    );
                    do_action( $this->name . '_add_menu_items_up' );
                    add_submenu_page(
                        $this->name . '_' . $handler,
                        $submenu_title,
                        $submenu_title,
                        'manage_' . $handler . '_cap',
                        $this->name . '_' . $handler,
                        'tickera_' . $handler . '_admin'
                    );
                    do_action( $this->name . '_add_menu_items_after_' . $handler );
                } else {
                    $submenu_title = sprintf( 
                        /* translators: %s: Tickera submenu title */
                        __( '%s', 'tickera-event-ticketing-system' ),
                        $value
                     );
                    add_submenu_page(
                        $first_tc_network_menu_handler,
                        $submenu_title,
                        $submenu_title,
                        'manage_' . $handler . '_cap',
                        $this->name . '_' . $handler,
                        'tickera_' . $handler . '_admin'
                    );
                    do_action( $this->name . '_add_menu_items_after_' . $handler );
                }
                $number_of_sub_menu_items++;
            }
            do_action( $this->name . '_add_menu_items_down' );
        }

        /**
         * Function for adding plugin Settings link
         *
         * @param $links
         * @param $file
         * @return mixed
         */
        function plugin_action_link( $links, $file ) {
            $settings_link = '<a href = "' . esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_settings' ) ) . '">' . esc_html__( 'Settings', 'tickera-event-ticketing-system' ) . '</a>';
            // Add the link to the list
            array_unshift( $links, $settings_link );
            return $links;
        }

        /**
         * Plugin localization function
         */
        function localization() {
            /*
             * Load up the localization file if we're using WordPress in a different language
             * Place it in this plugin's "languages" folder and name it "tc-[value in wp-config].mo"
             */
            if ( $this->location == 'mu-plugins' ) {
                load_muplugin_textdomain( 'tickera-event-ticketing-system', 'languages/' );
            } elseif ( $this->location == 'subfolder-plugins' ) {
                load_plugin_textdomain( 'tickera-event-ticketing-system', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
            } elseif ( $this->location == 'plugins' ) {
                load_plugin_textdomain( 'tickera-event-ticketing-system', false, 'languages/' );
            }
            $temp_locales = explode( '_', get_locale() );
            $this->language = ( $temp_locales[0] ? $temp_locales[0] : 'en' );
        }

        /**
         * Load payment gateways
         */
        function load_addons() {
            require_once $this->plugin_dir . 'includes/classes/class.payment_gateways.php';
            $this->load_payment_gateway_addons();
            // Load Ticket Template Elements
            if ( defined( 'TC_DEV' ) ) {
                require_once $this->plugin_dir . 'includes/classes/class.ticket_template_elements_new.php';
            } else {
                require_once $this->plugin_dir . 'includes/classes/class.ticket_template_elements.php';
            }
            $this->load_ticket_template_elements();
            $this->load_tc_addons();
            do_action( 'tc_load_addons' );
            if ( !function_exists( 'activate_plugin' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
        }

        function load_ticket_template_elements() {
            if ( defined( 'TC_DEV' ) ) {
                $dir = $this->plugin_dir . 'includes/ticket-elements-new/';
            } else {
                $dir = $this->plugin_dir . 'includes/ticket-elements/';
            }
            $ticket_template_elements = [];
            if ( !is_dir( $dir ) ) {
                return;
            }
            if ( !($dh = opendir( $dir )) ) {
                return;
            }
            while ( ($plugin = readdir( $dh )) !== false ) {
                if ( substr( $plugin, -4 ) == '.php' ) {
                    $ticket_template_elements[] = $dir . '/' . $plugin;
                }
            }
            closedir( $dh );
            sort( $ticket_template_elements );
            foreach ( $ticket_template_elements as $file ) {
                include $file;
            }
            do_action( 'tc_load_ticket_template_elements' );
        }

        function load_tc_addons() {
            $dir = $this->plugin_dir . 'includes/addons/';
            if ( !is_dir( $dir ) ) {
                return;
            }
            if ( !($dh = opendir( $dir )) ) {
                return;
            }
            while ( ($plugin_dir = readdir( $dh )) !== false ) {
                if ( $plugin_dir !== '.' && $plugin_dir !== '..' && $plugin_dir !== '.DS_Store' ) {
                    include $dir . $plugin_dir . '/index.php';
                }
            }
        }

        function gateways_require_53php() {
            return apply_filters( 'tc_gateways_require_53php', array('beanstream.php', 'netbanx.php') );
        }

        function can_use_gateway( $plugin ) {
            $premium_gateways = array(
                'authorizenet-aim.php',
                'braintree-3ds2.php',
                'beanstream.php',
                'braintree.php',
                'ipay88.php',
                'komoju.php',
                'netbanx.php',
                'paygate.php',
                'paymill.php',
                'paypal-pro.php',
                'paypal-standard.php',
                'paytabs.php',
                'payu-latam.php',
                'payumoney.php',
                'pin.php',
                'simplify.php',
                'stripe.php',
                'stripe-elements-3ds.php',
                'voguepay.php'
            );
            if ( defined( 'FS_ACTIVATION' ) && !FS_ACTIVATION ) {
                // Do nothing if freemius activation is disabled
            } else {
                if ( !\Tickera\tets_fs()->is_free_plan() ) {
                    return true;
                } else {
                    return ( in_array( $plugin, $premium_gateways ) ? false : true );
                }
            }
        }

        function load_payment_gateway_addons() {
            global $tc_gateways_currencies;
            $post_data = tickera_sanitize_array( $_POST, true, true );
            $post_data = ( $post_data ? $post_data : [] );
            if ( !is_array( $tc_gateways_currencies ) ) {
                $tc_gateways_currencies = array();
            }
            if ( isset( $_post['gateway_settings'] ) ) {
                $settings = get_option( 'tickera_settings' );
                if ( isset( $_post['tc']['gateways']['active'] ) ) {
                    $settings['gateways']['active'] = $post_data['tc']['gateways']['active'];
                } else {
                    $settings['gateways']['active'] = [];
                }
                update_option( 'tickera_settings', tickera_sanitize_array( $settings, true, true ) );
            }
            $dir = $this->plugin_dir . 'includes/gateways/';
            $gateway_plugins = [];
            $gateway_plugins_originals = [];
            if ( !is_dir( $dir ) ) {
                return;
            }
            if ( !($dh = opendir( $dir )) ) {
                return;
            }
            while ( ($plugin = readdir( $dh )) !== false ) {
                if ( version_compare( phpversion(), '5.3', '<' ) ) {
                    if ( in_array( $plugin, $this->gateways_require_53php() ) ) {
                        $plugin = str_replace( '.php', '.53', $plugin );
                    }
                }
                if ( substr( $plugin, -4 ) == '.php' ) {
                    if ( $this->can_use_gateway( $plugin ) || is_network_admin() ) {
                        $gateway_plugins[] = trailingslashit( $dir ) . $plugin;
                        $gateway_plugins_originals[] = $plugin;
                    }
                }
            }
            closedir( $dh );
            $gateway_plugins = apply_filters( 'tc_gateway_plugins', $gateway_plugins, $gateway_plugins_originals );
            sort( $gateway_plugins );
            foreach ( $gateway_plugins as $file ) {
                include $file;
            }
            do_action( 'tc_load_gateway_plugins' );
            global $tc_gateway_plugins, $tc_gateway_active_plugins;
            $gateways = $this->get_setting( 'gateways' );
            foreach ( (array) $tc_gateway_plugins as $code => $plugin ) {
                $class = $plugin[0];
                if ( isset( $gateways['active'] ) && in_array( $code, (array) $gateways['active'] ) && class_exists( $class ) && !$plugin[3] ) {
                    $tc_gateway_active_plugins[] = new $class();
                }
                $gateway = new $class();
                if ( isset( $gateway->currencies ) && is_array( $gateway->currencies ) ) {
                    $tc_gateways_currencies = array_merge( $gateway->currencies, $tc_gateways_currencies );
                }
            }
            $settings = get_option( 'tickera_settings', [] );
            $settings['gateways']['currencies'] = apply_filters( 'tc_gateways_currencies', $tc_gateways_currencies );
            update_option( 'tickera_settings', tickera_sanitize_array( $settings, true, true ) );
        }

        function show_page_tab( $tab ) {
            do_action( 'tc_show_page_tab_' . $tab );
            require_once $this->plugin_dir . 'includes/admin-pages/settings-' . $tab . '.php';
        }

        function show_network_page_tab( $tab ) {
            do_action( 'tc_show_network_page_tab_' . $tab );
            require_once $this->plugin_dir . 'includes/network-admin-pages/network_settings-' . $tab . '.php';
        }

        function get_setting( $key, $default = null ) {
            $settings = get_option( 'tickera_settings' );
            $keys = explode( '->', $key );
            array_map( 'trim', $keys );
            switch ( count( $keys ) ) {
                case 1:
                    $setting = ( isset( $settings[$keys[0]] ) ? $settings[$keys[0]] : $default );
                    break;
                case 2:
                    $setting = ( isset( $settings[$keys[0]][$keys[1]] ) ? $settings[$keys[0]][$keys[1]] : $default );
                    break;
                case 3:
                    $setting = ( isset( $settings[$keys[0]][$keys[1]][$keys[2]] ) ? $settings[$keys[0]][$keys[1]][$keys[2]] : $default );
                    break;
                case 4:
                    $setting = ( isset( $settings[$keys[0]][$keys[1]][$keys[2]][$keys[3]] ) ? $settings[$keys[0]][$keys[1]][$keys[2]][$keys[3]] : $default );
                    break;
            }
            return apply_filters( "tc_setting_" . implode( '', $keys ), $setting, $default );
        }

        function get_network_setting( $key, $default = null ) {
            $settings = get_site_option( 'tickera_network_settings' );
            $keys = explode( '->', $key );
            array_map( 'trim', $keys );
            switch ( count( $keys ) ) {
                case 1:
                    $setting = ( isset( $settings[$keys[0]] ) ? $settings[$keys[0]] : $default );
                    break;
                case 2:
                    $setting = ( isset( $settings[$keys[0]][$keys[1]] ) ? $settings[$keys[0]][$keys[1]] : $default );
                    break;
                case 3:
                    $setting = ( isset( $settings[$keys[0]][$keys[1]][$keys[2]] ) ? $settings[$keys[0]][$keys[1]][$keys[2]] : $default );
                    break;
                case 4:
                    $setting = ( isset( $settings[$keys[0]][$keys[1]][$keys[2]][$keys[3]] ) ? $settings[$keys[0]][$keys[1]][$keys[2]][$keys[3]] : $default );
                    break;
            }
            return apply_filters( "tc_network_setting_" . implode( '', $keys ), $setting, $default );
        }

        function gateway_is_network_allowed( $gateway ) {
            $settings = get_site_option( 'tickera_network_settings', '' );
            if ( in_array( $gateway, $this->get_network_setting( 'gateways->active', array() ) ) || $gateway == 'free_orders' ) {
                return true;
            } else {
                return ( '' == $settings ? true : false );
            }
        }

        /**
         * Listen for gateway IPN returns and tie them in to proper gateway plugin
         *
         * @param $wp_query
         * @throws Exception
         */
        function handle_gateway_returns( $wp_query ) {
            global $wp;
            if ( is_admin() ) {
                return;
            }
            if ( isset( $wp_query->query_vars['payment_gateway_return'] ) && !empty( $wp_query->query_vars['payment_gateway_return'] ) || isset( $_GET['payment_gateway_return'] ) && !empty( $_GET['payment_gateway_return'] ) ) {
                $vars = array();
                $theme_file = locate_template( array('page-ipn.php') );
                if ( '' != $theme_file ) {
                    require_once $theme_file;
                    exit;
                } else {
                    $page = new TC_Virtual_Page([
                        'slug'        => $wp->request,
                        'title'       => __( 'IPN', 'tickera-event-ticketing-system' ),
                        'content'     => $this->get_template_details( $this->plugin_dir . 'includes/templates/page-ipn.php', $vars ),
                        'type'        => 'virtual_page',
                        'is_page'     => TRUE,
                        'is_singular' => TRUE,
                        'is_archive'  => FALSE,
                    ]);
                }
                $payment_gateway = ( isset( $wp_query->query_vars['payment_gateway_return'] ) ? sanitize_key( $wp_query->query_vars['payment_gateway_return'] ) : sanitize_key( $_GET['payment_gateway_return'] ) );
                do_action( 'tc_handle_payment_return_' . $payment_gateway );
            }
        }

        function get_order_payment_status( $order_id ) {
            $order = $this->get_order( $order_id );
            return $order->post_status;
        }

        /**
         * Called by payment gateways to update order statuses
         *
         * @param $order_id
         * @param $paid
         * @return bool
         */
        function update_order_payment_status( $order_id, $paid ) {
            $order = $this->get_order( $order_id );
            if ( !$order ) {
                return false;
            }
            if ( $paid ) {
                $current_payment_status = $this->get_order_payment_status( $order_id );
                $this->update_order_status( $order->ID, 'order_paid' );
                if ( $current_payment_status !== 'order_paid' ) {
                    $cart_contents = get_post_meta( $order->ID, 'tc_cart_contents', false );
                    $cart_info = get_post_meta( $order->ID, 'tc_cart_info', false );
                    $payment_info = get_post_meta( $order->ID, 'tc_payment_info', false );
                    do_action(
                        'tc_order_updated_status_to_paid',
                        $order->ID,
                        'order_paid',
                        $cart_contents,
                        $cart_info,
                        $payment_info
                    );
                    tickera_order_created_email(
                        $order->post_name,
                        'order_paid',
                        false,
                        false,
                        false,
                        true
                    );
                }
            }
        }

        /**
         * Returns the full order details as an object
         *
         * @param $order_id
         * @return array|bool|WP_Post|null
         */
        function get_order( $order_id ) {
            if ( is_int( $order_id ) ) {
                $id = $order_id;
            } else {
                $id = tickera_get_order_id_by_name( $order_id );
                if ( empty( $id ) ) {
                    return false;
                } else {
                    $id = $id->ID;
                }
            }
            $order = get_post( $id );
            if ( !$order ) {
                return false;
            }
            $meta = get_post_custom( $id );
            foreach ( $meta as $key => $val ) {
                $order->{$key} = maybe_unserialize( $meta[$key][0] );
            }
            return $order;
        }

        function get_cart_event_tickets( $cart_contents, $event_id ) {
            $ticket_count_global = 0;
            foreach ( $cart_contents as $ticket_type => $ticket_count ) {
                $event = get_post_meta( $ticket_type, 'event_name', true );
                if ( $event == $event_id ) {
                    $ticket_count_global = $ticket_count_global + $ticket_count;
                }
            }
            return $ticket_count_global;
        }

        /**
         * Returns all event ids based on the cart contents
         *
         * @param $cart_contents
         * @return array
         */
        function get_cart_events( $cart_contents ) {
            $event_ids = array();
            foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                $ticket = new \Tickera\TC_Ticket($ticket_type);
                $event_id = $ticket->get_ticket_event( $ticket_type );
                if ( !in_array( $event_id, $event_ids ) ) {
                    $event_ids[] = $event_id;
                }
            }
            return $event_ids;
        }

        function get_events_creators( $cart_contents ) {
            $event_ids = $this->get_cart_events( $cart_contents );
            $promoter_ids = [];
            foreach ( $event_ids as $event_id ) {
                $event = new \Tickera\TC_Event($event_id);
                $promoter_ids[] = $event->details->post_author;
            }
            return $promoter_ids;
        }

        function check_for_total_paid_fraud( $total_paid, $total_needed ) {
            if ( apply_filters( 'tc_compare_total_needed', true ) == true ) {
                return ( round( $total_paid, 2 ) == round( $total_needed, 2 ) ? false : true );
            } else {
                return false;
            }
        }

        /**
         * Called on checkout to create a new order
         *
         * @param $order_id
         * @param $cart_contents
         * @param $cart_info
         * @param $payment_info
         * @param $paid
         * @return bool
         * @throws \Exception
         */
        function create_order(
            $order_id,
            $cart_contents,
            $cart_info,
            $payment_info,
            $paid
        ) {
            global $tc;
            $cart_contents = array_map( 'absint', $cart_contents );
            $cart_info = tickera_sanitize_array( $cart_info, false, true );
            $payment_info = tickera_sanitize_array( $payment_info );
            tickera_final_cart_check( $cart_contents );
            // Make sure buyer data is available
            if ( !isset( $cart_info['buyer_data'] ) ) {
                $this->session->set( 'tc_gateway_error', __( 'Something went wrong. Cart data is not available', 'tickera-event-ticketing-system' ) );
                $this->remove_order_session_data();
                tickera_redirect( $tc->get_payment_slug( true ), true );
            }
            /*
             * Make sure the order id doesn't exists.
             * Do not continue if order exists or order_id is not supplied
             */
            if ( empty( $order_id ) || $this->get_order( $order_id ) ) {
                $this->session->set( 'tc_gateway_error', __( 'Something went wrong. The order with the same ID already exists. Please try again.', 'tickera-event-ticketing-system' ) );
                $this->remove_order_session_data();
                tickera_redirect( $tc->get_payment_slug( true ), true );
            }
            $this->set_cart_info_cookie( $cart_info );
            $this->set_order_cookie( $order_id );
            $session = $this->session->get();
            if ( !isset( $session['cart_info']['total'] ) || is_null( $session['cart_info']['total'] ) ) {
                $cart_total = (float) $session['cart_total_pre'];
                $session['cart_info']['total'] = (float) $session['tc_cart_total'];
                $cart_info = (float) $session['cart_info'];
            } else {
                $cart_total = (float) $session['cart_info']['total'];
            }
            $fraud = $this->check_for_total_paid_fraud( $payment_info['total'], $cart_total );
            $user_id = get_current_user_id();
            // Insert post type
            $status = ( $paid ? ( $fraud ? 'order_fraud' : 'order_paid' ) : 'order_received' );
            $order = array();
            $order['post_title'] = $order_id;
            $order['post_name'] = $order_id;
            $order['post_content'] = serialize( $cart_contents );
            $order['post_status'] = $status;
            $order['post_type'] = 'tc_orders';
            if ( $user_id != 0 ) {
                $order['post_author'] = $user_id;
            }
            $post_id = wp_insert_post( tickera_sanitize_array( $order, true ) );
            /**
             * Process Post Meta
             * Add Post Meta
             */
            // Cart Contents
            add_post_meta( $post_id, 'tc_cart_contents', array_map( 'absint', $cart_contents ) );
            // Discount code
            if ( isset( $session['tc_discount_code'] ) ) {
                add_post_meta( $post_id, 'tc_discount_code', sanitize_text_field( $session['tc_discount_code'] ) );
            }
            // Cart Info
            $ticket_summary['owner_data'] = $this->tc_calculate_individual_ticket_totals( $post_id, $cart_contents );
            $cart_info = array_merge_recursive( $cart_info, $ticket_summary );
            add_post_meta( $post_id, 'tc_cart_info', tickera_sanitize_array( $cart_info, false, true ) );
            // Save row data - buyer and ticket owners data, gateway, total, currency, coupon code, etc.
            // Payment Info
            add_post_meta( $post_id, 'tc_payment_info', tickera_sanitize_array( $payment_info ) );
            //transaction_id, total, currency, method
            // Order Date & Time
            add_post_meta( $post_id, 'tc_order_date', time() );
            // Order Paid Time
            add_post_meta( $post_id, 'tc_paid_date', ( $paid ? time() : '' ) );
            //empty means not yet paid
            // Event(s) - could be more events at once since customer may have tickets from more than one event in the cart
            add_post_meta( $post_id, 'tc_parent_event', array_map( 'absint', $this->get_cart_events( $cart_contents ) ) );
            add_post_meta( $post_id, 'tc_event_creators', array_map( 'absint', $this->get_events_creators( $cart_contents ) ) );
            // Save Ticket Owner(s) data
            $owner_data = $cart_info['owner_data'];
            $owner_records = [];
            $different_ticket_types = array_keys( $cart_contents );
            $n = 0;
            $i = 1;
            foreach ( $different_ticket_types as $different_ticket_type ) {
                $i = $i + 10;
                foreach ( $owner_data as $field_name => $field_values ) {
                    $inner_count = $cart_contents[$different_ticket_type];
                    /*
                     * Collect Ticket Types ID from cart contents' sessions instead from the frontend fields being passed via POST.
                     * This is to avoid client side manipulation
                     */
                    for ($y = 0; $y < $cart_contents[$different_ticket_type]; $y++) {
                        switch ( $field_name ) {
                            case 'ticket_type_id_post_meta':
                                $owner_record_value = $different_ticket_type;
                                break;
                            default:
                                $owner_record_value = ( isset( $field_values[$different_ticket_type] ) && isset( $field_values[$different_ticket_type][$y] ) ? $field_values[$different_ticket_type][$y] : '' );
                        }
                        $owner_records[$n . '-' . $inner_count . '-' . $i][$field_name] = $owner_record_value;
                        $inner_count++;
                    }
                }
                $n++;
            }
            $owner_record_num = 1;
            foreach ( $owner_records as $owner_record ) {
                if ( isset( $owner_record['ticket_type_id_post_meta'] ) ) {
                    $metas = [];
                    foreach ( $owner_record as $owner_field_name => $owner_field_value ) {
                        if ( preg_match( '/_post_title/', $owner_field_name ) ) {
                            $title = sanitize_text_field( $owner_field_value );
                        } elseif ( preg_match( '/_post_excerpt/', $owner_field_name ) ) {
                            $excerpt = wp_filter_post_kses( $owner_field_value );
                        } elseif ( preg_match( '/_post_content/', $owner_field_name ) ) {
                            $content = wp_filter_post_kses( $owner_field_value );
                        } elseif ( preg_match( '/_post_meta/', $owner_field_name ) ) {
                            $owner_field_value = maybe_unserialize( $owner_field_value );
                            $metas[str_replace( '_post_meta', '', $owner_field_name )] = ( is_array( $owner_field_value ) ? tickera_sanitize_array( $owner_field_value, false, true ) : sanitize_text_field( $owner_field_value ) );
                        }
                    }
                    if ( apply_filters( 'tc_use_only_digit_order_number', false ) == true ) {
                        $metas['ticket_code'] = apply_filters( 'tc_ticket_code', $order_id . '' . $owner_record_num, $owner_record['ticket_type_id_post_meta'] );
                    } else {
                        $metas['ticket_code'] = apply_filters( 'tc_ticket_code', $order_id . '-' . $owner_record_num, $owner_record['ticket_type_id_post_meta'] );
                    }
                    do_action( 'tc_after_owner_post_field_type_check' );
                    $arg = array(
                        'post_author'  => ( isset( $user_id ) ? (int) $user_id : '' ),
                        'post_parent'  => $post_id,
                        'post_excerpt' => ( isset( $excerpt ) ? $excerpt : '' ),
                        'post_content' => ( isset( $content ) ? $content : '' ),
                        'post_status'  => 'publish',
                        'post_title'   => ( isset( $title ) ? $title : '' ),
                        'post_type'    => 'tc_tickets_instances',
                    );
                    $owner_record_id = @wp_insert_post( tickera_sanitize_array( $arg, true ), true );
                    $ticket_type_id = 0;
                    foreach ( $metas as $meta_name => $mata_value ) {
                        update_post_meta( $owner_record_id, sanitize_key( $meta_name ), sanitize_text_field( $mata_value ) );
                        if ( $meta_name == 'ticket_type_id' ) {
                            $ticket_type_id = $mata_value;
                        }
                    }
                    if ( $ticket_type_id == 0 || empty( $ticket_type_id ) ) {
                        $ticket_type_id = get_post_meta( $owner_record_id, 'ticket_type_id', true );
                    }
                    $ticket_type = new \Tickera\TC_Ticket($ticket_type_id);
                    $event_id = $ticket_type->get_ticket_event( $ticket_type_id );
                    update_post_meta( $owner_record_id, 'event_id', (int) $event_id );
                    $owner_record_num++;
                }
            }
            // Send order status email to the customer
            $payment_class_name = sanitize_text_field( $session['cart_info']['gateway_class'] );
            $payment_class_name = ( class_exists( $payment_class_name ) ? $payment_class_name : "\\Tickera\\Gateway\\" . $payment_class_name );
            $payment_gateway = new $payment_class_name();
            do_action(
                'tc_order_created',
                $order_id,
                $status,
                $cart_contents,
                $cart_info,
                $payment_info
            );
            return $order_id;
        }

        function change_event_status() {
            if ( isset( $_POST['event_id'] ) && isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                $event_id = (int) $_POST['event_id'];
                $post_status = sanitize_key( $_POST['event_status'] );
                $post_data = array(
                    'ID'          => $event_id,
                    'post_status' => sanitize_key( $post_status ),
                );
                wp_update_post( tickera_sanitize_array( $post_data ) );
                exit;
            } else {
                echo esc_html( 'error' );
                exit;
            }
        }

        /**
         * Update Event Field Options based on selected Event Category
         * Page: Tickera > Settings > API Access
         */
        function change_apikey_event_category() {
            if ( isset( $_POST['event_term_category'] ) && isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                $event_ids = [];
                $current_term_category = (int) $_POST['event_term_category'];
                $wp_events_search = new \Tickera\TC_Events_Search('', '', -1);
                foreach ( $wp_events_search->get_results() as $event ) {
                    $event_name = get_the_title( $event->ID );
                    $event_terms = get_the_terms( $event->ID, 'event_category' );
                    if ( 'all' == $current_term_category ) {
                        $event_ids[$event->ID] = $event_name;
                    } else {
                        foreach ( (array) $event_terms as &$term ) {
                            if ( isset( $term->term_id ) && $term->term_id == $current_term_category ) {
                                $event_ids[$event->ID] = $event_name;
                                break;
                            }
                        }
                    }
                }
                wp_send_json( $event_ids );
            }
        }

        function change_ticket_status() {
            if ( isset( $_POST['ticket_id'] ) && isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                $ticket_id = (int) $_POST['ticket_id'];
                $post_status = sanitize_key( $_POST['ticket_status'] );
                $post_data = array(
                    'ID'          => $ticket_id,
                    'post_status' => sanitize_key( $post_status ),
                );
                wp_update_post( tickera_sanitize_array( $post_data ) );
                exit;
            } else {
                echo esc_html( 'error' );
                exit;
            }
        }

        function change_order_status_ajax() {
            if ( isset( $_POST['order_id'] ) && isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                $order_id = (int) $_POST['order_id'];
                $post_status = sanitize_key( $_POST['new_status'] );
                $post_data = array(
                    'ID'          => $order_id,
                    'post_status' => sanitize_key( $post_status ),
                );
                $order = new \Tickera\TC_Order($order_id);
                $old_post_status = $order->details->post_status;
                if ( 'trash' == $post_status ) {
                    $order->delete_order( false );
                } else {
                    /*
                     * Untrash attendees and tickets only if the order was in the trash
                     */
                    if ( 'trash' == $old_post_status ) {
                        $order->untrash_order();
                    }
                    echo esc_html( ( wp_update_post( tickera_sanitize_array( $post_data ) ) ? 'updated' : 'error' ) );
                }
                if ( 'order_paid' == $post_status ) {
                    /*
                     * Calling function to send an notification email for order:'.$order->post_name;
                     */
                    tickera_order_created_email(
                        $order->details->post_name,
                        $post_status,
                        false,
                        false,
                        false,
                        true
                    );
                    $payment_info = get_post_meta( $order_id, 'tc_payment_info', true );
                    do_action(
                        'tc_order_paid_change',
                        $order_id,
                        $post_status,
                        '',
                        '',
                        $payment_info
                    );
                }
                exit;
            } else {
                echo esc_html( 'error' );
                exit;
            }
        }

        /**
         * Saves cart info array to cookie
         *
         * @param $order
         */
        function set_order_cookie( $order ) {
            ob_start();
            $cookie_id = 'tc_order_' . COOKIEHASH;
            unset($_COOKIE[$cookie_id]);
            @setcookie(
                $cookie_id,
                null,
                -1,
                '/'
            );
            $order = sanitize_text_field( $order );
            $expire = time() + apply_filters( 'tc_cart_cookie_expiration', 172800 );
            // 72 hrs expire by default
            @setcookie(
                $cookie_id,
                $order,
                $expire,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            $_COOKIE[$cookie_id] = $order;
            ob_end_flush();
        }

        /**
         * Saves cart info array to cookie
         *
         * @param $cart_info
         * @throws \Exception
         */
        function set_cart_info_cookie( $cart_info ) {
            ob_start();
            $cookie_id = 'cart_info_' . COOKIEHASH;
            unset($_COOKIE[$cookie_id]);
            @setcookie(
                $cookie_id,
                null,
                -1,
                '/'
            );
            $cart_info = tickera_sanitize_array( $cart_info, false, true );
            $expire = time() + apply_filters( 'tc_cart_cookie_expiration', 172800 );
            // 72 hrs expire by default
            @setcookie(
                $cookie_id,
                json_encode( $cart_info ),
                $expire,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            $_COOKIE[$cookie_id] = json_encode( $cart_info );
            ob_end_flush();
        }

        /**
         * Collection of order variables.
         * Make sure not to duplicate ob_start from TC_Shortcodes::tc_order_confirmation_page
         *
         * @return bool|mixed|null
         */
        function get_order_cookie() {
            $cookie_id = 'tc_order_' . COOKIEHASH;
            $order = ( isset( $_COOKIE[$cookie_id] ) ? sanitize_text_field( $_COOKIE[$cookie_id] ) : null );
            return ( isset( $order ) ? $order : false );
        }

        function get_cart_info_cookie() {
            $cookie_id = 'cart_info_' . COOKIEHASH;
            if ( isset( $_COOKIE[$cookie_id] ) ) {
                $cart_obj = json_decode( stripslashes( sanitize_text_field( $_COOKIE[$cookie_id] ) ), true );
                foreach ( $cart_obj as $ticket_id => $qty ) {
                    $cart[(int) $ticket_id] = (int) $qty;
                }
            } else {
                $cart = [];
            }
            return ( isset( $cart ) ? $cart : [] );
        }

        /**
         * Remove order session data
         *
         * @param bool $js_fallback
         */
        function remove_order_session_data_only( $js_fallback = true ) {
            /**
             * ob_start() to ensure no content is printed before the final redirection.
             * Used for the following script tag.
             */
            $this->session->start();
            $this->session->drop( 'tc_order' );
            if ( $js_fallback ) {
                ?>
                <script type="text/javascript">
                    jQuery( document ).ready( function( $ ) {
                        $.post( tc_ajax.ajaxUrl, { action: 'tc_remove_order_session_data_only', nonce: tc_ajax.ajaxNonce }, function( data ) {} );
                    } );
                </script>
            <?php 
            }
            $this->session->close();
        }

        /**
         * Fallback for remove_order_session_data_only.
         * Additional steps to remove order session data in case the default method fails.
         */
        function ajax_remove_order_session_data_only() {
            if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                $this->session->drop( 'tc_order' );
            }
        }

        /**
         * Remove order's related session data.
         * E.g Seating chart session/cookie
         *
         * @param bool $js_fallback
         * @param bool $buffer
         */
        function remove_order_session_data( $js_fallback = true, $buffer = true ) {
            /**
             * ob_start() to ensure no content is printed before the final redirection.
             * Used for the following script tag.
             */
            if ( $buffer ) {
                ob_start();
                $this->session->start();
            }
            do_action( 'tc_remove_order_session_data', $js_fallback );
            $session_to_unset = [
                'tc_discount_code',
                'discounted_total',
                'tc_payment_method',
                'cart_info',
                'tc_order',
                'tc_payment_info',
                'cart_subtotal_pre',
                'tc_total_fees',
                'discount_value_total',
                'tc_cart_subtotal',
                'tc_cart_total',
                'tc_tax_value',
                'tc_gateway_error',
                'tc_cart_errors',
                'stripe_payment_gateway',
                'stripe_payment_intent',
                'stripe_session_id'
            ];
            $session = $this->session->get();
            foreach ( $session_to_unset as $_session ) {
                if ( isset( $session[$_session] ) ) {
                    unset($session[$_session]);
                }
            }
            $this->session->set( false, $session );
            /**
             * Expected Warning when triggered via shortcode.
             * Cannot modify header information - headers already sent. - Shortcode is being executed right after theme renders header file.
             *
             * Resolution:
             * 1. Suppress error notice.
             * 2. Fallback via AJAX
             */
            @setcookie(
                'tc_cart_' . COOKIEHASH,
                null,
                time() - 1,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            @setcookie(
                'tc_cart_seats_' . COOKIEHASH,
                null,
                time() - 1,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            @setcookie(
                'cart_info_' . COOKIEHASH,
                null,
                time() - 1,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            @setcookie(
                'tc_order_' . COOKIEHASH,
                null,
                time() - 1,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            if ( $js_fallback ) {
                ?>
                <script type="text/javascript">
                    jQuery( document ).ready( function( $ ) {
                        $.post( tc_ajax.ajaxUrl, { action: 'tc_remove_order_session_data', nonce: tc_ajax.ajaxNonce }, function( data ) {} );
                    } );
                </script>
            <?php 
            }
            if ( $buffer ) {
                $this->session->close();
                ob_end_flush();
            }
        }

        /**
         * Fallback for remove_order_session_data.
         * Additional steps to remove order session data in case the default method fails.
         */
        function ajax_remove_order_session_data() {
            if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {
                ob_start();
                $this->session->start();
                do_action( 'tc_remove_order_session_data_ajax' );
                $session_to_unset = [
                    'tc_discount_code',
                    'discounted_total',
                    'tc_payment_method',
                    'cart_info',
                    'tc_order',
                    'tc_payment_info',
                    'cart_subtotal_pre',
                    'tc_total_fees',
                    'discount_value_total',
                    'tc_cart_subtotal',
                    'tc_cart_total',
                    'tc_tax_value',
                    'tc_gateway_error',
                    'tc_cart_errors',
                    'stripe_payment_gateway'
                ];
                $session = $this->session->get();
                foreach ( $session_to_unset as $_session ) {
                    if ( isset( $session[$_session] ) ) {
                        unset($session[$_session]);
                    }
                }
                $this->session->set( false, $session );
                setcookie(
                    'tc_cart_' . COOKIEHASH,
                    '',
                    time() - 1,
                    COOKIEPATH,
                    COOKIE_DOMAIN
                );
                setcookie(
                    'tc_cart_seats_' . COOKIEHASH,
                    '',
                    time() - 1,
                    COOKIEPATH,
                    COOKIE_DOMAIN
                );
                setcookie(
                    'cart_info_' . COOKIEHASH,
                    '',
                    time() - 1,
                    COOKIEPATH,
                    COOKIE_DOMAIN
                );
                setcookie(
                    'tc_order_' . COOKIEHASH,
                    '',
                    time() - 1,
                    COOKIEPATH,
                    COOKIE_DOMAIN
                );
                $this->session->close();
                ob_end_flush();
            }
        }

        function update_order_status( $order_id, $new_status ) {
            $order = array(
                'ID'          => (int) $order_id,
                'post_status' => sanitize_key( $new_status ),
            );
            $order_object = new \Tickera\TC_Order($order_id);
            $old_post_status = $order_object->details->post_status;
            // Untrash order if it's in trash
            if ( 'trash' == $old_post_status ) {
                $order_object->untrash_order();
            }
            wp_update_post( tickera_sanitize_array( $order ) );
        }

        /**
         * Converts the pretty order id to an actual post ID
         *
         * @param $order_id
         * @return string|null
         */
        function order_to_post_id( $order_id ) {
            global $wpdb;
            return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s AND post_type = 'tc_orders'", $order_id ) );
        }

        function get_order_slug( $url = false ) {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $default_slug_value = ( isset( $tc_general_settings['ticket_order_slug'] ) ? $tc_general_settings['ticket_order_slug'] : 'order' );
            if ( $url ) {
                return ( $this->get_order_page() ? trailingslashit( $this->get_order_page( true ) ) : trailingslashit( home_url() ) . get_option( 'ticket_order_slug', $default_slug_value ) );
            }
            return $default_slug_value;
        }

        function cart_has_custom_url() {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            return ( isset( $tc_general_settings['ticket_custom_cart_url'] ) && $tc_general_settings['ticket_custom_cart_url'] !== '' ? true : false );
        }

        function get_cart_page( $url = false ) {
            $page = get_option( 'tickera_cart_page_id', false );
            if ( $page ) {
                return ( $url ? get_permalink( $page ) : $page );
            } else {
                return false;
            }
        }

        function get_payment_page( $url = false ) {
            $page = get_option( 'tickera_payment_page_id', false );
            if ( $page ) {
                return ( $url ? get_permalink( $page ) : $page );
            } else {
                return false;
            }
        }

        function get_process_payment_page( $url = false ) {
            $page = get_option( 'tickera_process_payment_page_id', false );
            if ( $page ) {
                return ( $url ? get_permalink( $page ) : $page );
            } else {
                return false;
            }
        }

        function get_ipn_page( $url = false ) {
            global $wp_rewrite;
            $page = get_option( 'tickera_ipn_page_id', false );
            if ( $page ) {
                return ( $url && isset( $wp_rewrite ) ? get_permalink( (int) $page ) : $page );
            } else {
                return false;
            }
        }

        function get_confirmation_page( $url = false ) {
            $page = get_option( 'tickera_confirmation_page_id', false );
            if ( $page ) {
                return ( $url ? get_permalink( $page ) : $page );
            } else {
                return false;
            }
        }

        function get_order_page( $url = false ) {
            $page = get_option( 'tickera_order_page_id', false );
            if ( $page ) {
                return ( $url ? get_permalink( $page ) : $page );
            } else {
                return false;
            }
        }

        function get_cart_slug( $url = false ) {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $default_slug_value = ( isset( $tc_general_settings['ticket_cart_slug'] ) ? $tc_general_settings['ticket_cart_slug'] : 'cart' );
            if ( $url ) {
                if ( $this->get_cart_page() ) {
                    return $this->get_cart_page( true );
                } else {
                    if ( isset( $tc_general_settings['ticket_custom_cart_url'] ) && $tc_general_settings['ticket_custom_cart_url'] !== '' ) {
                        return $tc_general_settings['ticket_custom_cart_url'];
                    } else {
                        return trailingslashit( home_url() ) . get_option( 'ticket_cart_slug', $default_slug_value );
                    }
                }
            }
            return $default_slug_value;
        }

        function get_payment_slug( $url = false ) {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $default_slug_value = ( isset( $tc_general_settings['ticket_payment_slug'] ) ? $tc_general_settings['ticket_payment_slug'] : 'payment' );
            if ( $url ) {
                if ( $this->get_payment_page() ) {
                    return $this->get_payment_page( true );
                } else {
                    return trailingslashit( home_url() ) . get_option( 'tickera_ticket_payment_slug', $default_slug_value );
                }
            }
            return $default_slug_value;
        }

        function get_process_payment_slug( $url = false ) {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $default_slug_value = ( isset( $tc_general_settings['ticket_payment_process_slug'] ) ? $tc_general_settings['ticket_payment_process_slug'] : 'process-payment' );
            if ( $url ) {
                $tc_process_payment_use_virtual = ( isset( $tc_general_settings['tc_process_payment_use_virtual'] ) ? $tc_general_settings['tc_process_payment_use_virtual'] : 'no' );
                if ( $this->get_process_payment_page() && $tc_process_payment_use_virtual == 'no' ) {
                    return trailingslashit( $this->get_process_payment_page( true ) );
                } else {
                    return trailingslashit( home_url() ) . get_option( 'tickera_ticket_payment_process_slug', $default_slug_value );
                }
            }
            return $default_slug_value;
        }

        function get_cancel_url( $order_id = false ) {
            if ( $order_id ) {
                $cancel_url = ( 1 == $this->active_payment_gateways() ? $this->get_cart_slug( true ) . '?tc_cancel_order=' . $order_id : $this->get_payment_slug( true ) . '?tc_cancel_order=' . $order_id );
            } else {
                $cancel_url = $this->get_cart_slug( true );
            }
            return $cancel_url;
        }

        function maybe_cancel_order( $redirect = false ) {
            if ( isset( $_GET['tc_cancel_order'] ) && !empty( $_GET['tc_cancel_order'] ) ) {
                $order_id = sanitize_text_field( $_GET['tc_cancel_order'] );
                $order = tickera_get_order_id_by_name( $order_id );
                $order_status = get_post_status( $order->ID );
                if ( 'order_received' == $order_status ) {
                    // Cancel order if it's received / pending only (administrator can cancel other order statuses as well)
                    $this->update_order_status( $order->ID, 'order_cancelled' );
                    \Tickera\TC_Order::add_order_note( $order->ID, __( 'Order cancelled by client.', 'tickera-event-ticketing-system' ) );
                    if ( $redirect !== false ) {
                        ob_start();
                        $this->session->set( 'tc_gateway_error', __( 'Your transaction has been canceled.', 'tickera-event-ticketing-system' ) );
                        if ( $this->active_payment_gateways() == 1 ) {
                            tickera_redirect( $this->get_cart_slug( true ), true );
                        } else {
                            tickera_redirect( $this->get_payment_slug( true ), true );
                        }
                    }
                }
            }
        }

        function get_confirmation_slug( $url = false, $order_id = false ) {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $default_slug_value = ( isset( $tc_general_settings['ticket_confirmation_slug'] ) ? $tc_general_settings['ticket_confirmation_slug'] : 'confirmation' );
            $use_order_details_pretty_links = ( isset( $tc_general_settings['use_order_details_pretty_links'] ) ? $tc_general_settings['use_order_details_pretty_links'] : 'yes' );
            if ( $url ) {
                if ( $this->get_confirmation_page() ) {
                    if ( 'yes' == $use_order_details_pretty_links ) {
                        return trailingslashit( $this->get_confirmation_page( true ) ) . trailingslashit( $order_id );
                    } else {
                        return trailingslashit( $this->get_confirmation_page( true ) ) . '?tc_order_return=' . $order_id;
                    }
                } else {
                    return trailingslashit( home_url() ) . trailingslashit( get_option( 'tickera_ticket_confirmation_slug', $default_slug_value ) ) . trailingslashit( $order_id );
                }
            }
            return $default_slug_value;
        }

        function get_payment_gateway_return_slug( $url = false ) {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $default_slug_value = ( isset( $tc_general_settings['ticket_payment_gateway_return_slug'] ) ? $tc_general_settings['ticket_payment_gateway_return_slug'] : 'payment-gateway-ipn' );
            if ( $url ) {
                $tc_ipn_use_virtual = ( isset( $tc_general_settings['tc_ipn_use_virtual'] ) ? $tc_general_settings['tc_ipn_use_virtual'] : 'no' );
                if ( $this->get_ipn_page() && $tc_ipn_use_virtual == 'no' ) {
                    return trailingslashit( $this->get_ipn_page( true ) );
                } else {
                    return trailingslashit( home_url() ) . get_option( 'tickera_ticket_payment_gateway_return_slug', $default_slug_value );
                }
            }
            return $default_slug_value;
        }

        function register_custom_posts() {
            register_post_type( 'tc_events', apply_filters( 'tc_events_post_type_args', array(
                'labels'             => array(
                    'name'               => __( 'Events', 'tickera-event-ticketing-system' ),
                    'singular_name'      => __( 'Events', 'tickera-event-ticketing-system' ),
                    'add_new'            => __( 'Create new', 'tickera-event-ticketing-system' ),
                    'add_new_item'       => __( 'Create new event', 'tickera-event-ticketing-system' ),
                    'edit_item'          => __( 'Edit events', 'tickera-event-ticketing-system' ),
                    'edit'               => __( 'Edit', 'tickera-event-ticketing-system' ),
                    'new_item'           => __( 'New event', 'tickera-event-ticketing-system' ),
                    'view_item'          => __( 'View event', 'tickera-event-ticketing-system' ),
                    'search_items'       => __( 'Search events', 'tickera-event-ticketing-system' ),
                    'not_found'          => __( 'No events found', 'tickera-event-ticketing-system' ),
                    'not_found_in_trash' => __( 'No events found in the trash', 'tickera-event-ticketing-system' ),
                    'view'               => __( 'View Event', 'tickera-event-ticketing-system' ),
                ),
                'public'             => true,
                'show_ui'            => false,
                'publicly_queryable' => true,
                'capability_type'    => 'tc_events',
                'map_meta_cap'       => true,
                'capabilities'       => array(
                    'publish_posts'          => 'publish_tc_events',
                    'edit_posts'             => 'edit_tc_events',
                    'edit_others_posts'      => 'edit_others_tc_events',
                    'delete_posts'           => 'delete_tc_events',
                    'delete_others_posts'    => 'delete_others_tc_events',
                    'read_private_posts'     => 'read_private_tc_events',
                    'edit_post'              => 'edit_tc_event',
                    'delete_post'            => 'delete_tc_event',
                    'read'                   => 'read_tc_event',
                    'edit_published_posts'   => 'edit_published_tc_events',
                    'edit_private_posts'     => 'edit_private_tc_events',
                    'delete_private_posts'   => 'delete_private_tc_events',
                    'delete_published_posts' => 'delete_published_tc_events',
                    'create_posts'           => 'create_tc_events',
                ),
                'hierarchical'       => false,
                'query_var'          => true,
                'show_in_rest'       => true,
            ) ) );
            register_post_type( 'tc_tickets', apply_filters( 'tc_ticket_type_post_type_args', array(
                'labels'             => array(
                    'name'               => __( 'Ticket Types', 'tickera-event-ticketing-system' ),
                    'singular_name'      => __( 'Ticket', 'tickera-event-ticketing-system' ),
                    'add_new'            => __( 'Create new', 'tickera-event-ticketing-system' ),
                    'add_new_item'       => __( 'Create new ticket type', 'tickera-event-ticketing-system' ),
                    'edit_item'          => __( 'Edit Ticket', 'tickera-event-ticketing-system' ),
                    'edit'               => __( 'Edit', 'tickera-event-ticketing-system' ),
                    'new_item'           => __( 'New ticket', 'tickera-event-ticketing-system' ),
                    'view_item'          => __( 'View ticket', 'tickera-event-ticketing-system' ),
                    'search_items'       => __( 'Search tickets', 'tickera-event-ticketing-system' ),
                    'not_found'          => __( 'No tickets found', 'tickera-event-ticketing-system' ),
                    'not_found_in_trash' => __( 'No tickets found in the trash', 'tickera-event-ticketing-system' ),
                    'view'               => __( 'View Ticket', 'tickera-event-ticketing-system' ),
                ),
                'public'             => false,
                'show_ui'            => false,
                'publicly_queryable' => true,
                'capability_type'    => 'tc_tickets',
                'map_meta_cap'       => true,
                'capabilities'       => array(
                    'publish_posts'          => 'publish_tc_tickets',
                    'edit_posts'             => 'edit_tc_tickets',
                    'edit_others_posts'      => 'edit_others_tc_tickets',
                    'delete_posts'           => 'delete_tc_tickets',
                    'delete_others_posts'    => 'delete_others_tc_tickets',
                    'read_private_posts'     => 'read_private_tc_tickets',
                    'edit_post'              => 'edit_tc_ticket',
                    'delete_post'            => 'delete_tc_ticket',
                    'read_post'              => 'read_tc_ticket',
                    'edit_published_posts'   => 'edit_published_tc_tickets',
                    'edit_private_posts'     => 'edit_private_tc_tickets',
                    'delete_private_posts'   => 'delete_private_tc_tickets',
                    'delete_published_posts' => 'delete_published_tc_tickets',
                    'create_posts'           => 'create_tc_tickets',
                ),
                'hierarchical'       => true,
                'query_var'          => true,
            ) ) );
            register_post_type( 'tc_api_keys', array(
                'labels'             => array(
                    'name'               => __( 'API Keys', 'tickera-event-ticketing-system' ),
                    'singular_name'      => __( 'API keys', 'tickera-event-ticketing-system' ),
                    'add_new'            => __( 'Create new', 'tickera-event-ticketing-system' ),
                    'add_new_item'       => __( 'Create new API keys', 'tickera-event-ticketing-system' ),
                    'edit_item'          => __( 'Edit API keys', 'tickera-event-ticketing-system' ),
                    'edit'               => __( 'Edit', 'tickera-event-ticketing-system' ),
                    'new_item'           => __( 'New API key', 'tickera-event-ticketing-system' ),
                    'view_item'          => __( 'View API key', 'tickera-event-ticketing-system' ),
                    'search_items'       => __( 'Search API keys', 'tickera-event-ticketing-system' ),
                    'not_found'          => __( 'No API keys found', 'tickera-event-ticketing-system' ),
                    'not_found_in_trash' => __( 'No API keys found in the trash', 'tickera-event-ticketing-system' ),
                    'view'               => __( 'View API key', 'tickera-event-ticketing-system' ),
                ),
                'public'             => true,
                'show_ui'            => false,
                'publicly_queryable' => true,
                'capability_type'    => 'page',
                'hierarchical'       => false,
                'query_var'          => true,
            ) );
            register_post_type( 'tc_tickets_instances', apply_filters( 'tc_tickets_instances_post_type_args', array(
                'labels'             => array(
                    'name'               => __( 'Attendees & Tickets', 'tickera-event-ticketing-system' ),
                    'singular_name'      => __( 'Attendee', 'tickera-event-ticketing-system' ),
                    'add_new'            => __( 'Create Attendee', 'tickera-event-ticketing-system' ),
                    'add_new_item'       => __( 'Create New Attendee', 'tickera-event-ticketing-system' ),
                    'edit_item'          => __( 'Check-in details', 'tickera-event-ticketing-system' ),
                    'edit'               => __( 'Edit', 'tickera-event-ticketing-system' ),
                    'new_item'           => __( 'New Attendee', 'tickera-event-ticketing-system' ),
                    'view_item'          => __( 'View attendee', 'tickera-event-ticketing-system' ),
                    'search_items'       => __( 'Search attendees', 'tickera-event-ticketing-system' ),
                    'not_found'          => __( 'No attendees found', 'tickera-event-ticketing-system' ),
                    'not_found_in_trash' => __( 'No attendee records found in the trash', 'tickera-event-ticketing-system' ),
                    'view'               => __( 'View attendee', 'tickera-event-ticketing-system' ),
                ),
                'public'             => false,
                'show_ui'            => false,
                'publicly_queryable' => true,
                'capability_type'    => 'tc_tickets_instances',
                'map_meta_cap'       => true,
                'capabilities'       => array(
                    'edit_post'              => 'edit_tc_tickets_instance',
                    'read_post'              => 'read_tc_tickets_instance',
                    'delete_post'            => 'delete_tc_tickets_instance',
                    'create_posts'           => 'create_tc_tickets_instances',
                    'edit_posts'             => 'edit_tc_tickets_instances',
                    'edit_others_posts'      => 'edit_others_posts_tc_tickets_instances',
                    'publish_posts'          => 'publish_tc_tickets_instances',
                    'read_private_posts'     => 'read_private_tc_tickets_instances',
                    'read'                   => 'read',
                    'delete_posts'           => 'delete_tc_tickets_instances',
                    'delete_private_posts'   => 'delete_private_tc_tickets_instances',
                    'delete_published_posts' => 'delete_published_tc_tickets_instances',
                    'delete_others_posts'    => 'delete_others_tc_tickets_instances',
                    'edit_private_posts'     => 'edit_private_tc_tickets_instances',
                    'edit_published_posts'   => 'edit_published_tc_tickets_instances',
                ),
                'hierarchical'       => true,
                'query_var'          => true,
            ) ) );
            register_post_type( 'tc_orders', apply_filters( 'tc_orders_post_type_args', array(
                'labels'          => array(
                    'name'          => __( 'Orders', 'tickera-event-ticketing-system' ),
                    'singular_name' => __( 'Order', 'tickera-event-ticketing-system' ),
                    'edit'          => __( 'Edit', 'tickera-event-ticketing-system' ),
                    'view_item'     => __( 'View order', 'tickera-event-ticketing-system' ),
                    'search_items'  => __( 'Search orders', 'tickera-event-ticketing-system' ),
                    'not_found'     => __( 'No orders found', 'tickera-event-ticketing-system' ),
                ),
                'public'          => false,
                'show_ui'         => false,
                'hierarchical'    => false,
                'rewrite'         => false,
                'query_var'       => false,
                'supports'        => array(),
                'capability_type' => 'tc_orders',
                'map_meta_cap'    => true,
                'capabilities'    => array(
                    'edit_post'              => 'edit_tc_order',
                    'read_post'              => 'read_tc_order',
                    'delete_post'            => 'delete_tc_order',
                    'create_posts'           => 'create_tc_orders',
                    'edit_posts'             => 'edit_tc_orders',
                    'edit_others_posts'      => 'edit_others_posts_tc_orders',
                    'publish_posts'          => 'publish_tc_orders',
                    'read_private_posts'     => 'read_private_tc_orders',
                    'read'                   => 'read',
                    'delete_posts'           => 'delete_tc_orders',
                    'delete_private_posts'   => 'delete_private_tc_orders',
                    'delete_published_posts' => 'delete_published_tc_orders',
                    'delete_others_posts'    => 'delete_others_tc_orders',
                    'edit_private_posts'     => 'edit_private_tc_orders',
                    'edit_published_posts'   => 'edit_published_tc_orders',
                ),
            ) ) );
            register_post_status( 'order_received', array(
                'label'       => __( 'Received', 'tickera-event-ticketing-system' ),
                'label_count' => _n_noop( 'Received <span class="count">(%s)</span>', 'Received <span class="count">(%s)</span>', 'tickera-event-ticketing-system' ),
                'post_type'   => 'tc_orders',
                'public'      => true,
            ) );
            register_post_status( 'order_paid', array(
                'label'       => __( 'Paid', 'tickera-event-ticketing-system' ),
                'label_count' => _n_noop( 'Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', 'tickera-event-ticketing-system' ),
                'post_type'   => 'tc_orders',
                'public'      => true,
            ) );
            register_post_status( 'order_cancelled', array(
                'label'       => __( 'Cancelled', 'tickera-event-ticketing-system' ),
                'label_count' => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'tickera-event-ticketing-system' ),
                'post_type'   => 'tc_orders',
                'public'      => true,
            ) );
            register_post_status( 'order_refunded', array(
                'label'       => __( 'Refunded', 'tickera-event-ticketing-system' ),
                'label_count' => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'tickera-event-ticketing-system' ),
                'post_type'   => 'tc_orders',
                'public'      => true,
            ) );
            register_post_status( 'order_fraud', array(
                'label'       => __( 'Fraud', 'tickera-event-ticketing-system' ),
                'label_count' => _n_noop( 'Fraud <span class="count">(%s)</span>', 'Fraud <span class="count">(%s)</span>', 'tickera-event-ticketing-system' ),
                'post_type'   => 'tc_orders',
                'public'      => true,
            ) );
            register_post_type( 'tc_templates', apply_filters( 'tc_templates_post_type_args', array(
                'labels'             => array(
                    'name'               => __( 'Templates', 'tickera-event-ticketing-system' ),
                    'singular_name'      => __( 'Templates', 'tickera-event-ticketing-system' ),
                    'add_new'            => __( 'Create new', 'tickera-event-ticketing-system' ),
                    'add_new_item'       => __( 'Create new template', 'tickera-event-ticketing-system' ),
                    'edit_item'          => __( 'Edit templates', 'tickera-event-ticketing-system' ),
                    'edit'               => __( 'Edit', 'tickera-event-ticketing-system' ),
                    'new_item'           => __( 'New template', 'tickera-event-ticketing-system' ),
                    'view_item'          => __( 'View template', 'tickera-event-ticketing-system' ),
                    'search_items'       => __( 'Search templates', 'tickera-event-ticketing-system' ),
                    'not_found'          => __( 'No templates found', 'tickera-event-ticketing-system' ),
                    'not_found_in_trash' => __( 'No templates found in the trash', 'tickera-event-ticketing-system' ),
                    'view'               => __( 'View Template', 'tickera-event-ticketing-system' ),
                ),
                'public'             => true,
                'show_ui'            => false,
                'publicly_queryable' => true,
                'capability_type'    => 'page',
                'hierarchical'       => false,
                'query_var'          => true,
            ) ) );
        }

        function remove_unnecessary_plugin_menu_items( $items ) {
            $i = 0;
            foreach ( $items as $item ) {
                if ( $item->url == $this->get_payment_page( true ) || $item->url == $this->get_confirmation_page( true ) || $item->url == $this->get_order_page( true ) ) {
                    unset($items[$i]);
                }
                $i++;
            }
            return $items;
        }

        function remove_unnecessary_plugin_menu_items_wp_page_menu_args( $args ) {
            $exclude_plugin_pages[] = $this->get_payment_page();
            $exclude_plugin_pages[] = $this->get_confirmation_page();
            $exclude_plugin_pages[] = $this->get_order_page();
            $args['exclude'] = implode( ',', $exclude_plugin_pages );
            return $args;
        }

        function in_pages_doesnt_require_media() {
            $page = ( isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '' );
            $pages_doesnt_requires_media = array(
                'tc_discount_codes',
                'tc_orders',
                'tc_attendees',
                'tc_addons'
            );
            return ( in_array( $page, $pages_doesnt_requires_media ) ? true : false );
        }

        /**
         * Get the current post type
         *
         * @return string|null
         */
        function get_current_post_type() {
            global $post, $typenow, $current_screen;
            if ( $post && $post->post_type && isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == $post->post_type ) {
                /*
                 * We have a post so we can just get the post type from that.
                 * In case post type is messed up. We included additional condition to validate with request
                 */
                return $post->post_type;
            } elseif ( $typenow ) {
                // Check the global $typenow - set in admin.php
                return $typenow;
            } elseif ( $current_screen && $current_screen->post_type ) {
                // Check the global $current_screen object - set in sceen.php
                return $current_screen->post_type;
            } elseif ( isset( $_REQUEST['post_type'] ) ) {
                // Lastly check the post_type querystring
                return sanitize_key( $_REQUEST['post_type'] );
            } else {
                // We do not know the post type!
                return null;
            }
        }

        /**
         * Additional logic to before loading css/js files
         *
         * @return bool
         */
        function in_admin_pages_require_admin_styles() {
            $post_type = $this->get_current_post_type();
            $post_types_require_admin_styles = array(
                'tc_forms',
                'tc_form_fields',
                'tc_custom_fonts',
                'tc_seat_charts',
                'tc_events',
                'tc_speakers',
                'tc_tickets',
                'tc_api_keys',
                'tc_tickets_instances',
                'tc_orders',
                'tc_templates',
                'tc_volume_discount',
                'product',
                'product_variation'
            );
            $tc_get_page = ( isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '' );
            $tc_pages_array = apply_filters( 'tc_pages_array', array('tc_ticket_templates') );
            return ( in_array( $post_type, $post_types_require_admin_styles ) || in_array( $tc_get_page, $tc_pages_array ) ? true : false );
        }

        function bridge_admin_notice() {
            global $pagenow;
            if ( !defined( 'TICKET_PLUGIN_TITLE' ) ) {
                $request_data = tickera_sanitize_array( $_REQUEST, false, true );
                if ( is_plugin_active( 'woocommerce/woocommerce.php' ) == true && is_plugin_active( 'bridge-for-woocommerce/bridge-for-woocommerce.php' ) == false && !isset( $_COOKIE['tc_bridge_notifications'] ) ) {
                    $allowed_post_types = [
                        'tc_events',
                        'tc_tickets',
                        'tc_speakers',
                        'shop_order',
                        'tc_orders',
                        'product',
                        'tc_seat_charts'
                    ];
                    if ( isset( $post_type ) && in_array( $post_type, $allowed_post_types ) || isset( $request_data['post_type'] ) && 'tc_events' == $request_data['post_type'] || isset( $request_data['page'] ) && preg_match( "/tc_/", $request_data['page'] ) ) {
                        echo wp_kses_post( sprintf( 
                            /* translators: %s: Plugin dir url */
                            __( '<div class="notice notice-warning tc-notice-bridge is-dismissible"><img src="%simages/bridge-for-woocommerce-logo.png"/><p>Unleash the power of seamless event ticketing with <a href="https://tickera.com/addons/bridge-for-woocommerce/" target="_blank">Bridge for WooCommerce</a> add-on for Tickera. Effortlessly sell event tickets alongside your products, offering a unified shopping experience. Enhance your site today by installing the <a href="https://tickera.com/addons/bridge-for-woocommerce/" target="_blank">Bridge for WooCommerce</a> and unlock the full potential of your event ticketing and e-commerce strategy.</p></div>', 'tickera-event-ticketing-system' ),
                            esc_html( plugin_dir_url( __FILE__ ) )
                         ) );
                    }
                }
            }
        }

        /**
         *  Load CSS and JS in Admin pages
         */
        function admin_scripts_styles() {
            global $wp_version, $post_type;
            // Menu Icon
            if ( $wp_version >= 3.8 ) {
                wp_register_style( 'tc-admin-menu-icon', $this->plugin_url . 'css/admin-icon.css' );
                wp_enqueue_style( 'tc-admin-menu-icon' );
            }
            if ( defined( 'TC_DEV' ) ) {
                wp_enqueue_style(
                    $this->name . '-admin',
                    $this->plugin_url . 'css/admin-new.css',
                    array(),
                    $this->version
                );
            } else {
                wp_enqueue_style(
                    $this->name . '-admin',
                    $this->plugin_url . 'css/admin.css',
                    array(),
                    $this->version
                );
            }
            if ( $this->in_admin_pages_require_admin_styles() ) {
                wp_enqueue_style(
                    $this->name . '-admin-jquery-ui',
                    $this->plugin_url . 'css/jquery-ui-main.css',
                    array(),
                    $this->version
                );
                wp_enqueue_style(
                    $this->name . '-chosen',
                    $this->plugin_url . 'css/chosen.min.css',
                    array(),
                    $this->version
                );
                wp_enqueue_style(
                    $this->name . '-simple-dtpicker',
                    $this->plugin_url . 'css/jquery.simple-dtpicker.css',
                    array(),
                    $this->version
                );
                wp_enqueue_style(
                    'font-awesome',
                    $this->plugin_url . 'css/font-awesome.min.css',
                    array(),
                    $this->version
                );
                if ( apply_filters( 'tc_use_admin_colors_css', true ) == true ) {
                    wp_enqueue_style(
                        $this->name . '-colors',
                        $this->plugin_url . 'css/colors.css',
                        array(),
                        $this->version
                    );
                }
            }
            if ( !$this->in_pages_doesnt_require_media() ) {
                wp_enqueue_style( 'thickbox' );
                wp_enqueue_script( 'thickbox' );
                wp_enqueue_media();
                wp_enqueue_script( 'media-upload' );
                wp_enqueue_style( 'wp-color-picker' );
            }
            global $pagenow;
            $allowed_post_types = [
                'tc_events',
                'tc_tickets_instances',
                'tc_tickets',
                'tc_speakers',
                'shop_order',
                'tc_orders',
                'product',
                'tc_seat_charts'
            ];
            $request_data = tickera_sanitize_array( $_REQUEST, false, true );
            if ( isset( $post_type ) && in_array( $post_type, $allowed_post_types ) || isset( $request_data['post_type'] ) && 'tc_events' == $request_data['post_type'] || isset( $request_data['page'] ) && preg_match( "/tc_/", $request_data['page'] ) || $pagenow == "themes.php" || $pagenow == "theme-install.php" ) {
                wp_enqueue_script(
                    'tc-jquery-validate-additional-methods',
                    $this->plugin_url . 'js/additional-methods.min.js',
                    array('tc-jquery-validate'),
                    $this->version
                );
                if ( defined( 'TC_DEV' ) ) {
                    wp_enqueue_script(
                        $this->name . '-admin',
                        $this->plugin_url . 'js/admin-new.js',
                        array(
                            'jquery',
                            'tc-jquery-validate',
                            'tc-jquery-validate-additional-methods',
                            'jquery-ui-tooltip',
                            'jquery-ui-core',
                            'jquery-ui-sortable',
                            'jquery-ui-draggable',
                            'jquery-ui-droppable',
                            'jquery-ui-accordion',
                            'wp-color-picker'
                        ),
                        rand( 1, 999999999999 ),
                        false
                    );
                } else {
                    wp_enqueue_script(
                        $this->name . '-admin',
                        $this->plugin_url . 'js/admin.js',
                        array(
                            'jquery',
                            'tc-jquery-validate',
                            'tc-jquery-validate-additional-methods',
                            'jquery-ui-tooltip',
                            'jquery-ui-core',
                            'jquery-ui-sortable',
                            'jquery-ui-draggable',
                            'jquery-ui-droppable',
                            'jquery-ui-accordion',
                            'wp-color-picker'
                        ),
                        false,
                        false
                    );
                }
                if ( isset( $_COOKIE['tc_themes_notifications'] ) || defined( 'TICKET_PLUGIN_TITLE' ) ) {
                    $tc_themes_notifications = true;
                } else {
                    $tc_themes_notifications = '';
                }
                wp_localize_script( $this->name . '-admin', 'tc_vars', array(
                    'ajaxUrl'                                    => apply_filters( 'tc_ajaxurl', admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ) ),
                    'ajaxNonce'                                  => wp_create_nonce( 'tc_ajax_nonce' ),
                    'animated_transitions'                       => apply_filters( 'tc_animated_transitions', true ),
                    'delete_confirmation_message'                => __( 'Please confirm that you want to delete it permanently?', 'tickera-event-ticketing-system' ),
                    'order_status_changed_message'               => __( 'Order status changed successfully.', 'tickera-event-ticketing-system' ),
                    'order_confirmation_email_resent_message'    => __( 'Order confirmation e-mail has been sent successfully.', 'tickera-event-ticketing-system' ),
                    'order_confirmation_email_resending_message' => __( 'Sending...', 'tickera-event-ticketing-system' ),
                    'order_confirmation_email_sent_message'      => __( 'Sent', 'tickera-event-ticketing-system' ),
                    'order_confirmation_email_failed_message'    => __( 'Failed', 'tickera-event-ticketing-system' ),
                    'single_sold_ticket_trash_message'           => __( 'Are you sure you want to delete this Ticket Type? You have %s ticket sold for some of the selected ticket types', 'tickera-event-ticketing-system' ),
                    'multi_sold_tickets_trash_message'           => __( 'Are you sure you want to delete this Ticket Type? You have %s tickets sold for some of the selected ticket types', 'tickera-event-ticketing-system' ),
                    'multi_check_tickets_trash_message'          => __( 'Are you sure you want to delete all Ticket Types? You have tickets sold for some of the selected ticket types.', 'tickera-event-ticketing-system' ),
                    'plugin_uri'                                 => plugin_dir_url( __FILE__ ),
                    'check_for_cookie'                           => $tc_themes_notifications,
                    'please_enter_at_least_3_characters'         => __( 'Please enter at least 3 characters.', 'tickera-event-ticketing-system' ),
                ) );
                wp_enqueue_script(
                    $this->name . '-chosen',
                    $this->plugin_url . 'js/chosen.jquery.min.js',
                    array($this->name . '-admin'),
                    false,
                    false
                );
            }
            wp_enqueue_script(
                $this->name . '-simple-dtpicker',
                $this->plugin_url . 'js/jquery.simple-dtpicker.js',
                array('jquery'),
                $this->version
            );
            if ( isset( $_GET['page'] ) && 'tc_settings' == $_GET['page'] ) {
                wp_enqueue_script(
                    'tc-sticky',
                    $this->plugin_url . 'js/jquery.sticky.js',
                    array('jquery'),
                    $this->version
                );
                wp_localize_script( $this->name . '-admin', 'tc_vars', array(
                    'ajaxUrl'                   => apply_filters( 'tc_ajaxurl', admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ) ),
                    'ajaxNonce'                 => wp_create_nonce( 'tc_ajax_nonce' ),
                    'tc_check_page'             => sanitize_key( $_GET['page'] ),
                    'tickets_have_been_removed' => __( 'tickets have been deleted.', 'tickera-event-ticketing-system' ),
                    'something_went_wrong'      => __( 'Something went wrong. Please try again.', 'tickera-event-ticketing-system' ),
                    'confirm_action_message'    => __( 'Please confirm if you want to proceed.', 'tickera-event-ticketing-system' ),
                ) );
            }
            if ( isset( $_GET['page'] ) && 'tc_settings' == $_GET['page'] ) {
                wp_enqueue_editor();
            }
        }

        /**
         * Load CSS and JS in Frontend pages
         */
        function front_scripts_styles() {
            if ( apply_filters( 'tc_use_default_front_css', true ) == true ) {
                wp_enqueue_style(
                    $this->name . '-front',
                    $this->plugin_url . 'css/front.css',
                    array(),
                    $this->version
                );
                wp_enqueue_style(
                    $this->name . '-elementor-sc-popup',
                    $this->plugin_url . 'css/builders/elementor-sc-popup.css',
                    array(),
                    $this->version
                );
                if ( apply_filters( 'tc-load-font-awesome', true ) == true ) {
                    wp_enqueue_style(
                        'font-awesome',
                        $this->plugin_url . 'css/font-awesome.min.css',
                        array(),
                        $this->version
                    );
                }
            }
        }

        /**
         * Load CSS and JS both in Admin and Fronted pages
         */
        function common_scripts_styles() {
            global $post_type;
            $allowed_post_types = [
                'tc_events',
                'tc_tickets',
                'tc_speakers',
                'shop_order',
                'tc_orders',
                'product',
                'tc_seat_charts'
            ];
            $request_data = tickera_sanitize_array( $_REQUEST, false, true );
            if ( apply_filters( 'tc_use_default_front_css', true ) == true || isset( $post_type ) && in_array( $post_type, $allowed_post_types ) || isset( $request_data['post_type'] ) && 'tc_events' == $request_data['post_type'] || isset( $request_data['page'] ) && preg_match( "/tc_/", $request_data['page'] ) ) {
                wp_enqueue_script(
                    'tc-jquery-validate',
                    $this->plugin_url . 'js/jquery.validate.min.js',
                    array('jquery'),
                    $this->version
                );
                wp_localize_script( 'tc-jquery-validate', 'tc_jquery_validate_library_translation', array(
                    'required'    => __( 'This field is required.', 'tickera-event-ticketing-system' ),
                    'remote'      => __( 'Please fix this field.', 'tickera-event-ticketing-system' ),
                    'email'       => __( 'Please enter a valid email address.', 'tickera-event-ticketing-system' ),
                    'url'         => __( 'Please enter a valid URL.', 'tickera-event-ticketing-system' ),
                    'date'        => __( 'Please enter a valid date.', 'tickera-event-ticketing-system' ),
                    'dateISO'     => __( 'Please enter a valid date (ISO).', 'tickera-event-ticketing-system' ),
                    'number'      => __( 'Please enter a valid number.', 'tickera-event-ticketing-system' ),
                    'digits'      => __( 'Please enter only digits.', 'tickera-event-ticketing-system' ),
                    'equalTo'     => __( 'Please enter the same value again.', 'tickera-event-ticketing-system' ),
                    'maxlength'   => __( 'Please enter no more than {0} characters.', 'tickera-event-ticketing-system' ),
                    'minlength'   => __( 'Please enter at least {0} characters.', 'tickera-event-ticketing-system' ),
                    'rangelength' => __( 'Please enter a value between {0} and {1} characters long.', 'tickera-event-ticketing-system' ),
                    'range'       => __( 'Please enter a value between {0} and {1}.', 'tickera-event-ticketing-system' ),
                    'max'         => __( 'Please enter a value less than or equal to {0}.', 'tickera-event-ticketing-system' ),
                    'min'         => __( 'Please enter a value greater than or equal to {0}.', 'tickera-event-ticketing-system' ),
                    'step'        => __( 'Please enter a multiple of {0}.', 'tickera-event-ticketing-system' ),
                ) );
            }
        }

        function load_cart_scripts() {
            if ( true == apply_filters( 'tc_use_cart_scripts', true ) ) {
                $tc_general_settings = get_option( 'tickera_general_setting', false );
                $tc_error_message = ( isset( $tc_general_settings['age_error_text'] ) ? $tc_general_settings['age_error_text'] : __( 'Only customers aged 16 or older are permitted for purchase on this website', 'tickera-event-ticketing-system' ) );
                $tc_collection_data_text = ( isset( $tc_general_settings['tc_collection_data_text'] ) ? $tc_general_settings['tc_collection_data_text'] : '' );
                $tc_gateway_collection_data = ( isset( $tc_general_settings['tc_gateway_collection_data'] ) ? $tc_general_settings['tc_gateway_collection_data'] : '' );
                $show_owner_fields = ( isset( $tc_general_settings['show_owner_fields'] ) ? $tc_general_settings['show_owner_fields'] : 'no' );
                $tc_age_checkbox = ( 'yes' == $show_owner_fields ? ( isset( $tc_general_settings['show_age_check'] ) ? $tc_general_settings['show_age_check'] : 'no' ) : 'no' );
                if ( empty( $tc_collection_data_text ) ) {
                    $tc_collection_data_text = __( 'In order to continue you need to agree to provide your details.', 'tickera-event-ticketing-system' );
                }
                wp_enqueue_script(
                    'tc-cart',
                    $this->plugin_url . 'js/cart.js',
                    array('jquery'),
                    $this->version
                );
                wp_localize_script( 'tc-cart', 'tc_ajax', array(
                    'ajaxUrl'                      => apply_filters( 'tc_ajaxurl', admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ) ),
                    'ajaxNonce'                    => wp_create_nonce( 'tc_ajax_nonce' ),
                    'empty_cart_message'           => __( 'Are you sure you want to remove all tickets from your cart?', 'tickera-event-ticketing-system' ),
                    'success_message'              => __( 'Ticket Added!', 'tickera-event-ticketing-system' ),
                    'imgUrl'                       => $this->plugin_url . 'images/ajax-loader.gif',
                    'addingMsg'                    => __( 'Adding ticket to cart...', 'tickera-event-ticketing-system' ),
                    'outMsg'                       => __( 'In Your Cart', 'tickera-event-ticketing-system' ),
                    'cart_url'                     => $this->get_cart_slug( true ),
                    'update_cart_message'          => __( 'Please update your cart before to proceed.', 'tickera-event-ticketing-system' ),
                    'empty_cart_confirmation'      => __( 'Please confirm to remove all of the items from your cart.', 'tickera-event-ticketing-system' ),
                    'tc_provide_your_details'      => $tc_collection_data_text,
                    'tc_gateway_collection_data'   => $tc_gateway_collection_data,
                    'tc_error_message'             => $tc_error_message,
                    'tc_show_age_check'            => $tc_age_checkbox,
                    'tc_field_error'               => __( 'This field is required *', 'tickera-event-ticketing-system' ),
                    'alphanumeric_characters_only' => __( 'Please use alphanumeric characters only.', 'tickera-event-ticketing-system' ),
                ) );
            }
        }

    }

    global $tc, $license_key;
    $tc = new TC();
}
/**
 * Deprecated function "tc_multiple_plugin_versions_active_check".
 * New function "tickera_multiple_plugin_versions_active_check"
 *
 * @since 3.5.3.0
 */
add_action( 'admin_notices', 'Tickera\\tickera_multiple_plugin_versions_active_check' );
if ( !function_exists( 'Tickera\\tickera_multiple_plugin_versions_active_check' ) ) {
    function tickera_multiple_plugin_versions_active_check() {
        if ( current_user_can( 'manage_options' ) ) {
            // Show warning to the admin only
            if ( is_plugin_active( 'tickera-event-ticketing-system/tickera.php' ) && is_plugin_active( 'tickera/tickera.php' ) ) {
                echo wp_kses_post( '<div class="error"><p>' );
                echo wp_kses_post( '<strong>' . wp_kses_post( __( 'You have both FREE and PREMIUM version of Tickera plugin activated. In order to avoid conflicts, please deactivate one of them. </br> Once premium version is installed, free version is no longer needed and can be removed. Leaving free version active will block premium features of Tickera!', 'tickera-event-ticketing-system' ) ) . '</strong>' );
                echo wp_kses_post( '</p></div>' );
            }
        }
    }

}
/**
 * Deprecated function "tc_is_json".
 * New function "tickera_is_json"
 *
 * @since 3.5.3.0
 */
if ( !function_exists( 'tickera_is_json' ) ) {
    function tickera_is_json(  $string  ) {
        json_decode( $string );
        return json_last_error() == JSON_ERROR_NONE;
    }

}
if ( !function_exists( 'Tickera\\tets_fs' ) ) {
    if ( $_POST && isset( $_POST['fs_activation'] ) && $_POST['fs_activation'] === 'false' || defined( 'FS_ACTIVATION' ) && !FS_ACTIVATION ) {
        // Do nothing if freemius activation is disabled
        define( 'FS_ACTIVATION', false );
    } else {
        // Create a helper function for easy SDK access.
        function tets_fs() {
            global $tets_fs, $tc;
            if ( !isset( $tets_fs ) ) {
                if ( !defined( 'WP_FS__PRODUCT_3102_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_3102_MULTISITE', true );
                }
                $tc_fs_show = ( false == tickera_iw_is_wl() ? true : false );
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $menu_options = array(
                    'slug'    => 'edit.php?post_type=tc_events',
                    'contact' => false,
                    'support' => false,
                    'pricing' => false,
                    'account' => $tc_fs_show,
                    'addons'  => $tc_fs_show,
                    'network' => true,
                );
                $network_menu_options = $menu_options;
                $network_menu_options['slug'] = 'tc_network_settings';
                if ( fs_is_network_admin() ) {
                    $network_menu_options['first-path'] = 'plugins.php';
                } else {
                    if ( get_option( 'tickera_wizard_step', false ) == false && get_option( 'tickera_general_setting', false ) == false ) {
                        $menu_options['first-path'] = '?page=tc-installation-wizard';
                    }
                }
                $tets_fs = fs_dynamic_init( array(
                    'id'             => '3102',
                    'bundle_id'      => '3192',
                    'slug'           => 'tickera-event-ticketing-system',
                    'premium_slug'   => 'tickera',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_7a38a2a075ec34d6221fe925bdc65',
                    'is_premium'     => false,
                    'premium_suffix' => '',
                    'has_addons'     => true,
                    'has_paid_plans' => true,
                    'menu'           => $menu_options,
                    'menu_network'   => $network_menu_options,
                    'is_live'        => true,
                ) );
            }
            return $tets_fs;
        }

        // Init Freemius.
        \Tickera\tets_fs();
        // Signal that SDK was initiated.
        do_action( 'tets_fs_loaded' );
        require_once $tc->plugin_dir . 'includes/fr.php';
        if ( !function_exists( 'tickera_bridge_for_woocommerce_fs_dynamically_create_network_menu' ) ) {
            function tickera_bridge_for_woocommerce_fs_dynamically_create_network_menu() {
                if ( !woo_bridge_fs()->is_network_active() ) {
                    // If the add-on is not network active, don't do anything.
                    return;
                }
                $menu_manager = \FS_Admin_Menu_Manager::instance( 3102, 'plugin', \Tickera\tets_fs()->get_unique_affix() );
                $tc_fs_show = ( false == tickera_iw_is_wl() ? true : false );
                $menu_manager->init( array(
                    'slug'    => 'dummy',
                    'contact' => false,
                    'support' => false,
                    'pricing' => false,
                    'account' => $tc_fs_show,
                    'addons'  => $tc_fs_show,
                    'network' => false,
                ), false );
            }

        }
        if ( fs_is_network_admin() ) {
            if ( class_exists( 'TC_WooCommerce_Bridge' ) ) {
                tickera_bridge_for_woocommerce_fs_dynamically_create_network_menu();
            } else {
                /**
                 * Function: tickera_bridge_for_woocommerce_fs_dynamically_create_network_menu
                 * Issue: Callback error in Network activated.
                 * @since 3.5.4.4
                 */
                add_action( 'woo_bridge_fs_loaded', function () {
                    if ( !woo_bridge_fs()->is_network_active() ) {
                        // If the add-on is not network active, don't do anything.
                        return;
                    }
                    $menu_manager = \FS_Admin_Menu_Manager::instance( 3102, 'plugin', \Tickera\tets_fs()->get_unique_affix() );
                    $tc_fs_show = ( false == tickera_iw_is_wl() ? true : false );
                    $menu_manager->init( array(
                        'slug'    => 'dummy',
                        'contact' => false,
                        'support' => false,
                        'pricing' => false,
                        'account' => $tc_fs_show,
                        'addons'  => $tc_fs_show,
                        'network' => false,
                    ), false );
                } );
            }
        }
    }
}