<?php
/**
 * Gutenberg blocks for Tickera
 * Adds Tickera Gutenberg blocks
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Addons\TC_tc_gutentick' ) ) {

    class TC_tc_gutentick {

        var $version = '0.1';
        var $title = 'Gutentick';
        var $name = 'tc_gutentick';
        var $dir_name = 'tc-gutentick';
        var $location = 'plugins';
        var $plugin_dir = '';
        var $plugin_url = '';

        function __construct() {
            global $pagenow;

            if ( function_exists( 'register_block_type' ) && 'widgets.php' != $pagenow ) {
                add_action( 'init', array( $this, 'register_gutenberg_blocks' ) );
                add_action( 'enqueue_block_editor_assets', array( $this, 'register_extra_scripts' ) );
            }

            add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_styles' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts_styles' ) );
        }

        /**
         *  Load CSS and JS in Admin pages
         */
        function admin_scripts_styles() {
            global $tc;
            wp_enqueue_style( $tc->name . '-common-admin', $tc->plugin_url . 'includes/addons/gutenberg/assets/blocks.css', array(), $tc->version );
        }

        /**
         * Load CSS and JS in Frontend pages
         */
        function front_scripts_styles() {
            global $tc;
            wp_enqueue_style( $tc->name . '-common-front', $tc->plugin_url . 'includes/addons/gutenberg/assets/blocks.css', array(), $tc->version );
        }

        function register_gutenberg_blocks() {

            // Only if Bridge is not active
            if ( apply_filters( 'tc_bridge_for_woocommerce_is_active', false ) == false ) {

                // Add to cart group
                register_block_type( 'tickera/add-to-cart-group', array(
                    'editor_script' => 'tc_add_to_cart_group_block_editor',
                    'editor_style' => 'tc_add_to_cart_group_block_editor'
                ) );

                // Add to cart group - Inner Add to cart
                register_block_type( 'tickera/add-to-cart', array(
                    'render_callback' => array( $this, 'render_add_to_cart_shortcode' ),
                    'attributes' => array(
                        'ticket_type_id' => array( 'type' => 'string' ),
                        'show_price' => array( 'type' => 'boolean' ),
                        'price_position' => array( 'type' => 'string' ),
                        'soldout_message' => array( 'type' => 'string' ),
                        'quantity' => array( 'type' => 'boolean' ),
                        'link_type' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' ),
                    )
                ) );

                // Add to cart group - Inner Ticket Price
                register_block_type( 'tickera/ticket-price', array(
                    'render_callback' => array( $this, 'render_ticket_price_shortcode' ),
                    'attributes' => array(
                        'id' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                // Event add to Cart Group
                register_block_type( 'tickera/event-add-to-cart-group', array(
                    'editor_script' => 'tc_event_add_to_cart_group_block_editor',
                    'editor_style' => 'tc_event_add_to_cart_group_block_editor'
                ) );

                // Event add to Cart Group - Inner Table Column
                register_block_type( 'tickera/event-add-to-cart-columns', array(
                    'render_callback' => array( $this, 'render_event_add_to_cart_columns_content' ),
                    'attributes' => array(
                        'event_id' => array( 'type' => 'string' ),
                        'display_type' => array( 'type' => 'string' ),
                        'quantity' => array( 'type' => 'boolean' ),
                        'button_title' => array( 'type' => 'string' ),
                        'link_type' => array( 'type' => 'string' ),
                        'show_event_title' => array( 'type' => 'boolean' ),
                        'show_price' => array( 'type' => 'boolean' ),
                        'ticket_type_title' => array( 'type' => 'string' ),
                        'price_title' => array( 'type' => 'string' ),
                        'cart_title' => array( 'type' => 'string' ),
                        'quantity_title' => array( 'type' => 'string' ),
                        'soldout_message' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                // Event add to Cart Group - Inner Table Rows
                register_block_type( 'tickera/event-add-to-cart-rows', array(
                    'render_callback' => array( $this, 'render_event_add_to_cart_rows_content' ),
                    'attributes' => array(
                        'event_id' => array( 'type' => 'string' ),
                        'display_type' => array( 'type' => 'string' ),
                        'quantity' => array( 'type' => 'boolean' ),
                        'link_type' => array( 'type' => 'string' ),
                        'button_title' => array( 'type' => 'string' ),
                        'soldout_message' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                register_block_type( 'tickera/event-add-to-cart', array(
                    'render_callback' => array( $this, 'render_event_add_to_cart_shortcode' ),
                    'attributes' => array(
                        'event_id' => array( 'type' => 'string' ),
                        'display_type' => array( 'type' => 'string' ),
                        'button_title' => array( 'type' => 'string' ),
                        'link_type' => array( 'type' => 'string' ),
                        'ticket_type_title' => array( 'type' => 'string' ),
                        'price_title' => array( 'type' => 'string' ),
                        'cart_title' => array( 'type' => 'string' ),
                        'show_event_title' => array( 'type' => 'boolean' ),
                        'show_price' => array( 'type' => 'boolean' ),
                        'quantity' => array( 'type' => 'boolean' ),
                        'quantity_title' => array( 'type' => 'string' ),
                        'soldout_message' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                register_block_type( 'tickera/event-tickets-sold', array(
                    'editor_script' => 'tc_event_tickets_sold_block_editor',
                    'editor_style' => 'tc_event_tickets_sold_block_editor',
                    'render_callback' => array( $this, 'render_event_tickets_sold_shortcode' ),
                    'attributes' => array(
                        'event_id' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                register_block_type( 'tickera/event-tickets-left', array(
                    'editor_script' => 'tc_event_tickets_left_block_editor',
                    'editor_style' => 'tc_event_tickets_left_block_editor',
                    'render_callback' => array( $this, 'render_event_tickets_left_shortcode' ),
                    'attributes' => array(
                        'event_id' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                register_block_type( 'tickera/tickets-sold', array(
                    'editor_script' => 'tc_tickets_sold_block_editor',
                    'editor_style' => 'tc_tickets_sold_block_editor',
                    'render_callback' => array( $this, 'render_tickets_sold_shortcode' ),
                    'attributes' => array(
                        'ticket_type_id' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                register_block_type( 'tickera/tickets-left', array(
                    'editor_script' => 'tc_tickets_left_block_editor',
                    'editor_style' => 'tc_tickets_left_block_editor',
                    'render_callback' => array( $this, 'render_tickets_left_shortcode' ),
                    'attributes' => array(
                        'ticket_type_id' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                register_block_type( 'tickera/order-history', array(
                    'editor_script' => 'tc_order_history_block_editor',
                    'editor_style' => 'tc_order_history_block_editor',
                    'render_callback' => array( $this, 'render_order_history_shortcode' ),
                    'attributes' => array(
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

            } else {

                // Add to Cart Group
                register_block_type( 'tickera/woo-add-to-cart-group', array(
                    'editor_script' => 'tc_woo_add_to_cart_group_block_editor',
                    'editor_style' => 'tc_woo_add_to_cart_group_block_editor'
                ) );


                // Add to Cart Group - Inner Add to Cart
                register_block_type( 'tickera/woo-add-to-cart', array(
                    'render_callback' => array( $this, 'render_woo_add_to_cart_shortcode' ),
                    'attributes' => array(
                        'id' => array( 'type' => 'string' ),
                        'show_price' => array( 'type' => 'boolean' ),
                        'quantity' => array( 'type' => 'boolean' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                // Add to Cart Group - Inner Ticket Price
                register_block_type( 'tickera/woo-ticket-price', array(
                    'render_callback' => array( $this, 'render_woo_ticket_price_shortcode' ),
                    'attributes' => array(
                        'id' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                // Event add to Cart Group
                register_block_type( 'tickera/woo-event-add-to-cart-group', array(
                    'editor_script' => 'tc_woo_event_add_to_cart_group_block_editor',
                    'editor_style' => 'tc_woo_event_add_to_cart_group_block_editor'
                ) );

                // Event add to Cart Group - Inner Table Column
                register_block_type( 'tickera/woo-event-add-to-cart-columns', array(
                    'render_callback' => array( $this, 'render_woo_event_add_to_cart_columns_content' ),
                    'attributes' => array(
                        'id' => array( 'type' => 'string' ),
                        'display_type' => array( 'type' => 'string' ),
                        'show_event_title' => array( 'type' => 'boolean' ),
                        'show_price' => array( 'type' => 'boolean' ),
                        'quantity' => array( 'type' => 'boolean' ),
                        'ticket_type_title' => array( 'type' => 'string' ),
                        'price_title' => array( 'type' => 'string' ),
                        'cart_title' => array( 'type' => 'string' ),
                        'quantity_title' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                // Event add to Cart Group - Inner Table Rows
                register_block_type( 'tickera/woo-event-add-to-cart-rows', array(
                    'render_callback' => array( $this, 'render_woo_event_add_to_cart_rows_content' ),
                    'attributes' => array(
                        'id' => array( 'type' => 'string' ),
                        'display_type' => array( 'type' => 'string' ),
                        'quantity' => array( 'type' => 'boolean' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );

                register_block_type( 'tickera/woo-event-add-to-cart', array(
                    'render_callback' => array( $this, 'render_woo_event_add_to_cart_shortcode' ),
                    'attributes' => array(
                        'id' => array( 'type' => 'string' ),
                        'display_type' => array( 'type' => 'string' ),
                        'show_event_title' => array( 'type' => 'boolean' ),
                        'show_price' => array( 'type' => 'boolean' ),
                        'ticket_type_title' => array( 'type' => 'string' ),
                        'price_title' => array( 'type' => 'string' ),
                        'cart_title' => array( 'type' => 'string' ),
                        'quantity' => array( 'type' => 'boolean' ),
                        'quantity_title' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );
            }

            register_block_type( 'tickera/event-date', array(
                'editor_script' => 'tc_event_date_block_editor',
                'editor_style' => 'tc_event_date_block_editor',
                'render_callback' => array( $this, 'render_event_date_shortcode' ),
                'attributes' => array(
                    'event_id' => array( 'type' => 'string' ),
                    'className' => array( 'type' => 'string' ),
                    'textColor' => array( 'type' => 'string' ),
                    'backgroundColor' => array( 'type' => 'string' ),
                    'gradient' => array( 'type' => 'string' ),
                    'style' => array( 'type' => 'object' ),
                    'borderColor' => array( 'type' => 'string' ),
                    'fontSize' => array( 'type' => 'string' ),
                    'fontFamily' => array( 'type' => 'string' )
                )
            ) );

            register_block_type( 'tickera/event-location', array(
                'editor_script' => 'tc_event_location_block_editor',
                'editor_style' => 'tc_event_location_block_editor',
                'render_callback' => array( $this, 'render_event_location_shortcode' ),
                'attributes' => array(
                    'event_id' => array( 'type' => 'string' ),
                    'className' => array( 'type' => 'string' ),
                    'textColor' => array( 'type' => 'string' ),
                    'backgroundColor' => array( 'type' => 'string' ),
                    'gradient' => array( 'type' => 'string' ),
                    'style' => array( 'type' => 'object' ),
                    'borderColor' => array( 'type' => 'string' ),
                    'fontSize' => array( 'type' => 'string' ),
                    'fontFamily' => array( 'type' => 'string' )
                )
            ) );

            register_block_type( 'tickera/event-terms', array(
                'editor_script' => 'tc_event_terms_block_editor',
                'editor_style' => 'tc_event_terms_block_editor',
                'render_callback' => array( $this, 'render_event_terms_shortcode' ),
                'attributes' => array(
                    'event_id' => array( 'type' => 'string' ),
                    'className' => array( 'type' => 'string' ),
                    'textColor' => array( 'type' => 'string' ),
                    'backgroundColor' => array( 'type' => 'string' ),
                    'gradient' => array( 'type' => 'string' ),
                    'style' => array( 'type' => 'object' ),
                    'borderColor' => array( 'type' => 'string' ),
                    'fontSize' => array( 'type' => 'string' ),
                    'fontFamily' => array( 'type' => 'string' )
                )
            ) );

            register_block_type( 'tickera/event-logo', array(
                'editor_script' => 'tc_event_logo_block_editor',
                'editor_style' => 'tc_event_logo_block_editor',
                'render_callback' => array( $this, 'render_event_logo_shortcode' ),
                'attributes' => array(
                    'event_id' => array( 'type' => 'string' ),
                    'className' => array( 'type' => 'string' )
                )
            ) );

            register_block_type( 'tickera/event-sponsors-logo', array(
                'editor_script' => 'tc_event_sponsors_logo_block_editor',
                'editor_style' => 'tc_event_sponsors_logo_block_editor',
                'render_callback' => array( $this, 'render_event_sponsors_logo_shortcode' ),
                'attributes' => array(
                    'event_id' => array( 'type' => 'string' ),
                    'className' => array( 'type' => 'string' )
                )
            ) );

            if ( class_exists( 'TC_Seat_Chart' ) ) {
                register_block_type( 'tickera/seating-charts', array(
                    'editor_script' => 'tc_seating_charts_block_editor',
                    'editor_style' => 'tc_seating_charts_block_editor',
                    'render_callback' => array( $this, 'render_seating_charts_shortcode' ),
                    'attributes' => array(
                        'id' => array( 'type' => 'string' ),
                        'show_legend' => array( 'type' => 'string' ),
                        'button_title' => array( 'type' => 'string' ),
                        'subtotal_title' => array( 'type' => 'string' ),
                        'cart_title' => array( 'type' => 'string' ),
                        'className' => array( 'type' => 'string' ),
                        'textColor' => array( 'type' => 'string' ),
                        'backgroundColor' => array( 'type' => 'string' ),
                        'gradient' => array( 'type' => 'string' ),
                        'style' => array( 'type' => 'object' ),
                        'borderColor' => array( 'type' => 'string' ),
                        'fontSize' => array( 'type' => 'string' ),
                        'fontFamily' => array( 'type' => 'string' )
                    )
                ) );
            }
        }

        /**
         * Register extra scripts needed.
         */
        function register_extra_scripts() {

            global $post, $wp_version;

            $wp_tickets_search = new \Tickera\TC_Tickets_Search( '', '', -1 );
            $ticket_types = array();
            $ticket_types[] = array( 0, '' );

            foreach ( $wp_tickets_search->get_results() as $ticket_type ) {
                $ticket = new \Tickera\TC_Ticket( $ticket_type->ID );
                $ticket_types[] = array( $ticket_type->ID, $ticket->details->post_title, apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_price_per_ticket', $ticket->details->price_per_ticket, $ticket_type ) ) );
            }

            /*
             * Return single event id if under current event (tc_event post_type)
             * @since 3.5.1.8
             */
            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $events = (int) $post->ID;

            } else {
                $events = [ [ 0, '' ] ];
                $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 );
                foreach ( $wp_events_search->get_results() as $event_item ) {
                    $event = new \Tickera\TC_Ticket( $event_item->ID );
                    $events[] = [ $event_item->ID, $event->details->post_title ];
                }
            }

            $event = new \Tickera\TC_Event( (int) $post->ID );
            $event_ticket_types = $event->get_event_ticket_types();

            /*
             * Show notices in event gutenberg.
             * @since 3.5.1.2
             */
            if ( $post && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {

                $creation_messages = TC_Better_Events::get_creation_messages();

                unset( $creation_messages[2] );
                unset( $creation_messages[3] );
                unset( $creation_messages[4] );
                unset( $creation_messages[7] );
                $creation_messages = array_values( $creation_messages );

                $ticket_type_admin_url = apply_filters( 'tc_ticket_type_admin_url', admin_url( 'edit.php?post_type=tc_tickets' ) );
                $random_creation_message = $creation_messages[ rand( 0, count( $creation_messages ) - 1 ) ];

                wp_enqueue_script( 'tc_gutenberg_controls', plugins_url( 'assets/controls.js', __FILE__ ), array( 'wp-data', 'utils', 'wp-edit-post' ) );
                wp_enqueue_script( 'tc_gutenberg_notices', plugins_url( 'assets/notices.js', __FILE__ ), array( 'wp-data', 'utils', 'wp-edit-post' ) );
                wp_localize_script( 'tc_gutenberg_notices', 'tc_gutenberg', [
                    'no_ticket_types' => ( count( $event_ticket_types ) == 0 ) ? true : false,
                    'no_ticket_types_message' => strip_tags( $random_creation_message ),
                    'no_ticket_types_action_message' => __( 'Click here to create ticket types.', 'tickera-event-ticketing-system' ),
                    'no_ticket_types_action_url' => esc_url( $ticket_type_admin_url ),
                ] );
            }

            /**
             * wp_editor currently not working properly in post's meta box if gutenberg is enabled.
             * Issue: wp_editor TinyMce dependencies sometimes loaded late.
             *
             * Wordpress bug.
             * @version 6.2
             *
             * @since 3.5.1.2
             */
            $wp_scripts = wp_scripts();
            $wp_scripts->remove( 'wp-tinymce' );
            wp_register_tinymce_scripts( $wp_scripts, true );

            if ( apply_filters( 'tc_bridge_for_woocommerce_is_active', false ) == false ) {

                // Ticket add to cart group block
                wp_register_script( 'tc_add_to_cart_group_block_editor', plugins_url( 'assets/add_to_cart_group/tc_add_to_cart_group_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_add_to_cart_group_block_editor', 'tc_add_to_cart_group_block_editor', array( 'ticket_types' => wp_json_encode( $ticket_types ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_add_to_cart_group_block_editor' );
                wp_enqueue_style( 'tc_add_to_cart_group_block_editor', plugins_url( 'assets/add_to_cart_group/tc_add_to_cart_group_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

                // Event tickets cart group block
                wp_register_script( 'tc_event_add_to_cart_group_block_editor', plugins_url( 'assets/event_add_to_cart_group/tc_event_add_to_cart_group_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_event_add_to_cart_group_block_editor', 'tc_event_add_to_cart_group_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_event_add_to_cart_group_block_editor' );
                wp_enqueue_style( 'tc_event_add_to_cart_group_block_editor', plugins_url( 'assets/event_add_to_cart_group/tc_event_add_to_cart_group_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

                // Event Tickets Sold block
                wp_register_script( 'tc_event_ti ckets_sold_block_editor', plugins_url( 'assets/event_tickets_sold/tc_event_tickets_sold_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_event_tickets_sold_block_editor', 'tc_event_tickets_sold_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_event_tickets_sold_block_editor' );
                wp_enqueue_style( 'tc_event_event_tickets_sold_block_editor', plugins_url( 'assets/event_tickets_sold/tc_event_tickets_sold_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

                // Event Tickets Left block
                wp_register_script( 'tc_event_tickets_left_block_editor', plugins_url( 'assets/event_tickets_left/tc_event_tickets_left_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_event_tickets_left_block_editor', 'tc_event_tickets_left_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_event_tickets_left_block_editor' );
                wp_enqueue_style( 'tc_event_event_tickets_left_block_editor', plugins_url( 'assets/event_tickets_left/tc_event_tickets_left_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

                // Tickets Sold block
                wp_register_script( 'tc_tickets_sold_block_editor', plugins_url( 'assets/tickets_sold/tc_tickets_sold_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_tickets_sold_block_editor', 'tc_tickets_sold_block_editor', array( 'ticket_types' => wp_json_encode( $ticket_types ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_tickets_sold_block_editor' );
                wp_enqueue_style( 'tc_tickets_sold_block_editor', plugins_url( 'assets/tickets_sold/tc_tickets_sold_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

                // Tickets Left block
                wp_register_script( 'tc_tickets_left_block_editor', plugins_url( 'assets/tickets_left/tc_tickets_left_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_tickets_left_block_editor', 'tc_tickets_left_block_editor', array( 'ticket_types' => wp_json_encode( $ticket_types ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_tickets_left_block_editor' );
                wp_enqueue_style( 'tc_tickets_left_block_editor', plugins_url( 'assets/tickets_left/tc_tickets_left_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

                // Order History block
                wp_register_script( 'tc_order_history_block_editor', plugins_url( 'assets/order_history/tc_order_history_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_tickets_sold_block_editor', 'tc_order_history_block_editor', array( 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_order_history_block_editor' );
                wp_enqueue_style( 'tc_order_history_block_editor', plugins_url( 'assets/order_history/tc_order_history_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

            } else {

                // Show bridge blocks
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => '_tc_is_ticket',
                            'compare' => '=',
                            'value' => 'yes'
                        ),
                    ),
                    'fields' => 'ids'
                );

                $product_ids = array();
                $product_ids[] = array( 0, '' );
                $products = get_posts( $args );

                foreach ( $products as $ticket_type_key => $ticket_type_id ) {
                    $post_title = get_the_title( $ticket_type_id );
                    $product_ids[] = array( $ticket_type_id, $post_title );
                }

                // Ticket add to cart group block
                wp_register_script( 'tc_woo_add_to_cart_group_block_editor', plugins_url( 'assets/woo_add_to_cart_group/tc_woo_add_to_cart_group_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_woo_add_to_cart_group_block_editor', 'tc_woo_add_to_cart_group_block_editor', array( 'ticket_types' => wp_json_encode( $product_ids ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_woo_add_to_cart_group_block_editor' );
                wp_enqueue_style( 'tc_woo_add_to_cart_group_block_editor', plugins_url( 'assets/woo_add_to_cart_group/tc_woo_add_to_cart_group_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

                // Event tickets cart group block
                wp_register_script( 'tc_woo_event_add_to_cart_group_block_editor', plugins_url( 'assets/woo_event_add_to_cart_group/tc_woo_event_add_to_cart_group_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_woo_event_add_to_cart_group_block_editor', 'tc_woo_event_add_to_cart_group_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_woo_event_add_to_cart_group_block_editor' );
                wp_enqueue_style( 'tc_woo_event_add_to_cart_group_block_editor', plugins_url( 'assets/woo_event_add_to_cart_group/tc_woo_event_add_to_cart_group_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );
            }

            // Event Date block
            wp_register_script( 'tc_event_date_block_editor', plugins_url( 'assets/event_date/tc_event_date_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
            wp_localize_script( 'tc_event_date_block_editor', 'tc_event_date_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
            wp_enqueue_script( 'tc_event_date_block_editor' );
            wp_enqueue_style( 'tc_event_event_date_block_editor', plugins_url( 'assets/event_date/tc_event_date_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

            // Event Location block
            wp_register_script( 'tc_event_location_block_editor', plugins_url( 'assets/event_location/tc_event_location_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
            wp_localize_script( 'tc_event_location_block_editor', 'tc_event_location_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
            wp_enqueue_script( 'tc_event_location_block_editor' );
            wp_enqueue_style( 'tc_event_event_location_block_editor', plugins_url( 'assets/event_location/tc_event_location_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

            // Event Terms & Conditions block
            wp_register_script( 'tc_event_terms_block_editor', plugins_url( 'assets/event_terms/tc_event_terms_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
            wp_localize_script( 'tc_event_terms_block_editor', 'tc_event_terms_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
            wp_enqueue_script( 'tc_event_terms_block_editor' );
            wp_enqueue_style( 'tc_event_event_terms_block_editor', plugins_url( 'assets/event_terms/tc_event_terms_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

            // Event Logo block
            wp_register_script( 'tc_event_logo_block_editor', plugins_url( 'assets/event_logo/tc_event_logo_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
            wp_localize_script( 'tc_event_logo_block_editor', 'tc_event_logo_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
            wp_enqueue_script( 'tc_event_logo_block_editor' );
            wp_enqueue_style( 'tc_event_event_logo_block_editor', plugins_url( 'assets/event_logo/tc_event_logo_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

            // Event Sponsors Logo block
            wp_register_script( 'tc_event_sponsors_logo_block_editor', plugins_url( 'assets/event_sponsors_logo/tc_event_sponsors_logo_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
            wp_localize_script( 'tc_event_sponsors_logo_block_editor', 'tc_event_sponsors_logo_block_editor', array( 'events' => wp_json_encode( $events ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
            wp_enqueue_script( 'tc_event_sponsors_logo_block_editor' );
            wp_enqueue_style( 'tc_event_event_sponsors_logo_block_editor', plugins_url( 'assets/event_sponsors_logo/tc_event_sponsors_logo_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );

            if ( class_exists( 'TC_Seat_Chart' ) ) {

                // Seating chart block
                $seating_charts_ids = array();
                $seating_charts_ids[] = array( 0, '' );

                $args = array(
                    'post_type' => 'tc_seat_charts',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'no_found_rows' => true
                );

                $seat_charts = get_posts( $args );

                foreach ( $seat_charts as $seat_chart ) {
                    $seating_charts_ids[] = array( $seat_chart->ID, $seat_chart->post_title );
                }

                wp_register_script( 'tc_seating_charts_block_editor', plugins_url( 'assets/seating_charts/tc_seating_charts_block_editor.js', __FILE__ ), array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery' ), $this->version );
                wp_localize_script( 'tc_seating_charts_block_editor', 'tc_seating_charts_block_editor', array( 'seating_charts' => wp_json_encode( $seating_charts_ids ), 'since_611' => ( version_compare( $wp_version, '6.1.1', '>=' ) ? true : false ) ) );
                wp_enqueue_script( 'tc_seating_charts_block_editor' );
                wp_enqueue_style( 'tc_seating_charts_block_editor', plugins_url( 'assets/seating_charts/tc_seating_charts_block_editor.css', __FILE__ ), array( 'wp-edit-blocks' ), $this->version );
            }
        }

        /**
         * Gutenberg Block - Server Side Renderer
         * tickera/add-to-cart block
         *
         * Element tc-add-to-cart-group-wrap serve as a dummy class for frontend styling.
         *
         * @param $attributes
         * @return false|string
         */
        function render_add_to_cart_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-add-to-cart-group-wrap"></div>
            <div class="tc-add-to-cart-wrap tickera<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
                <?php
                $show_price = ( isset( $attributes[ 'show_price' ] ) && ( true == $attributes[ 'show_price' ] || 1 == $attributes[ 'show_price' ] ) ) ? 'show_price="true"' : '';
                $price_position = isset( $attributes[ 'price_position' ] ) ? 'price_position="' . sanitize_text_field( $attributes[ 'price_position' ] ) . '"' : '';
                $quantity = ( isset( $attributes[ 'quantity' ] ) && ( true == $attributes[ 'quantity' ] || 1 == $attributes[ 'quantity' ] ) ) ? 'quantity="true"' : '';
                $soldout_message = ( isset( $attributes[ 'soldout_message' ] ) && $attributes[ 'soldout_message' ] ) ? 'soldout_message="' . sanitize_text_field( $attributes[ 'soldout_message' ] ) .'"' : '';
                $link_type = isset( $attributes[ 'link_type' ] ) ? 'type="' . $attributes[ 'link_type' ] . '"' : '';

                if ( isset( $attributes[ 'ticket_type_id' ] ) && $attributes[ 'ticket_type_id' ] ) {
                    echo wp_kses( do_shortcode( '[tc_ticket id="' . (int) $attributes[ 'ticket_type_id' ] . '" ' . $show_price . ' ' . $price_position . ' ' . $soldout_message . ' ' . $quantity . ' ' . $link_type . ']' ), wp_kses_allowed_html( 'tickera_add_to_cart' ) );

                } else {
                    esc_html_e( 'Please select a ticket type in the block settings box', 'tickera-event-ticketing-system' );
                }
                ?>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Gutenberg Block - Server Side Renderer
         * tickera/ticket-price
         *
         * Element tc-add-to-cart-group-wrap serve as a dummy class for frontend styling.
         *
         * @param $attributes
         * @return false|string
         * @since 3.5.1.5
         */
        function render_ticket_price_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            if ( isset( $attributes[ 'id' ] ) && $attributes[ 'id' ] ) : ?>
                <div class="tc-add-to-cart-group-wrap"></div>
                    <div class="tc-ticket-price-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                        <div class="tc-block-inner-wrapper" style="<?php
                    echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                    echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                    echo esc_attr( self::convert_inline_to_string( $inline, 'margin' ) );
                    echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) );
                    echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) );
                    ?>">
                        <?php echo esc_html( do_shortcode( '[ticket_price id="' . (int) $attributes[ 'id' ] . '"]' ) ); ?>
                    </div>
                </div>
            <?php endif;
            $content = trim( ob_get_clean() );
            return $content ? $content : '&nbsp;';
        }

        /**
         * Render Event Tickets Table's Columns.
         * Support Block Styles.
         *
         * @param $attributes
         * @return false|string
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_add_to_cart_columns_content( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();

            // No event selected
            if ( ! $event_id ) {
                esc_html_e( 'Please select an event in the block settings box.', 'tickera-event-ticketing-system' );
                return ob_get_clean();
            }

            // Attribute Values
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }

            /**
             * Display Type - Dropdown
             */
            $display_type = isset( $attributes[ 'display_type' ] ) ? $attributes[ 'display_type' ] : '';
            if ( 'dropdown' == $display_type ) {

                $show_event_title = ( isset( $attributes[ 'show_event_title' ] ) && ( $attributes[ 'show_event_title' ] == true || $attributes[ 'show_event_title' ] == 1 ) ) ? 'show_event_title="true"' : '';
                $show_price = ( isset( $attributes[ 'show_price' ] ) && ( $attributes[ 'show_price' ] == true || $attributes[ 'show_price' ] == 1 ) ) ? 'show_price="true"' : '';
                $quantity = ( isset( $attributes[ 'quantity' ] ) && ( $attributes[ 'quantity' ] == true || $attributes[ 'quantity' ] == 1 ) ) ? 'quantity="true"' : '';
                $soldout_message = isset( $attributes[ 'soldout_message' ] ) ? 'soldout_message="' . $attributes[ 'soldout_message' ] . '"': '';
                $button_title = isset( $attributes[ 'button_title' ] ) ? 'button_title="' . $attributes[ 'button_title' ] . '"': '';
                $type = isset( $attributes[ 'link_type' ] ) ? 'link_type="' . $attributes[ 'link_type' ] . '"' : '';

                $html = '<div class="tc-event-add-to-cart-group-wrap tc-event-add-to-cart-dropdown' . esc_attr( $additional_classes ) . esc_attr( $classes ) . '" style="' . esc_attr( $styles ) . '">';
                $content = trim( do_shortcode( '[tc_event id="' . (int) $event_id . '" display_type="dropdown" ' . $show_event_title . ' ' . $show_price . ' ' . $button_title . ' ' . $soldout_message . '  ' . $type . ' ' . $quantity . ']' ) );

                if ( $content ) {
                    $html .= $content;
                    $html .= '</div>';
                    echo wp_kses_post( $html );

                } else {
                    esc_html_e( 'No associated ticket types found. Try selecting another event.', 'tickera-event-ticketing-system' );
                }
                return ob_get_clean();
            }

            /**
             * Display Type - Table
             */
            $ticket_type_title = isset( $attributes[ 'ticket_type_title' ] ) ? sanitize_text_field( $attributes[ 'ticket_type_title' ] ) : '';
            $price_title = isset( $attributes[ 'price_title' ] ) ? sanitize_text_field( $attributes[ 'price_title' ] ) : '';
            $cart_title = isset( $attributes[ 'cart_title' ]  ) ? sanitize_text_field( $attributes[ 'cart_title' ] ) : '';
            $quantity = isset( $attributes[ 'quantity' ]  ) ? $attributes[ 'quantity' ] : '';
            $quantity_title = isset( $attributes[ 'quantity_title' ] ) ? sanitize_text_field( $attributes[ 'quantity_title' ] ) : '';

            $event = new \Tickera\TC_Event( $event_id );
            $event_tickets = $event->get_event_ticket_types( 'publish' );
            ?>
            <div class="tc-event-add-to-cart-group-wrap tc-event-add-to-cart-columns<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                <?php if ( count( $event_tickets ) > 0 ) : ?>
                    <table cellspacing="0" class="event_tickets tickera" style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) ); ?>">
                        <tr style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) ); ?>">
                            <?php do_action( 'tc_event_col_title_before_ticket_title' ); ?>
                            <th style="<?php
                                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                                ?>"><?php echo esc_html( $ticket_type_title ); ?></th>
                            <?php do_action( 'tc_event_col_title_before_ticket_price' ); ?>
                            <th style="<?php
                                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                                ?>"><?php echo esc_html( $price_title ); ?></th>
                            <?php if ( $quantity ) : ?>
                                <?php do_action( 'tc_event_col_title_before_quantity' ); ?>
                                <th style="<?php
                                    echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                                    echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                                    ?>"><?php echo esc_html( $quantity_title ); ?></th>
                            <?php endif; ?>
                            <?php do_action( 'tc_event_col_title_before_cart_title' ); ?>
                            <th style="<?php
                                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                                ?>"><?php echo esc_html( $cart_title ); ?></th>
                        </tr>
                    </table>
                <?php else : ?>
                    <div><?php esc_html_e( 'No associated ticket types found. Try selecting another event.', 'tickera-event-ticketing-system' ); ?></div>
                <?php endif; ?>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Event Tickets Table's Rows.
         * Support Block Styles.
         *
         * @param $attributes
         * @return false|string
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_add_to_cart_rows_content( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();

            // No event selected
            if ( ! $event_id ) {
                echo wp_kses_post( '<div></div>' ); // Echo empty string to hide "Empty block" notice
                return ob_get_clean();
            }

            /**
             * Display Type - Dropdown
             * Hide Table
             */
            $display_type = isset( $attributes[ 'display_type' ] ) ? $attributes[ 'display_type' ] : '';
            if ( 'dropdown' == $display_type ) {
                echo wp_kses_post( '<div></div>' );
                return ob_get_clean();
            }

            /**
             * Display Type - Table
             */
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            $quantity = isset( $attributes[ 'quantity' ] ) ? $attributes[ 'quantity' ] : '';
            $link_type = isset( $attributes[ 'link_type' ] ) ? $attributes[ 'link_type' ] : '';
            $button_title = isset( $attributes[ 'button_title' ] ) ? $attributes[ 'button_title' ] : '';
            $soldout_message = isset( $attributes[ 'soldout_message' ] ) ? $attributes[ 'soldout_message' ] : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }

            $event = new \Tickera\TC_Event( $event_id );
            $event_tickets = $event->get_event_ticket_types( 'publish', false, true, false );
            if ( count( $event_tickets ) > 0 ) {
                if ( 'publish' == $event->details->post_status ) : ?>
                    <div class="tc-event-add-to-cart-group-wrap tc-event-add-to-cart-rows event_tickets tickera<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
                        <table cellspacing="0" class="event_tickets tickera" style="<?php
                        echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                        echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) );
                        ?>">
                            <tr></tr>
                            <?php foreach ( $event_tickets as $event_ticket_id ) :
                                $event_ticket = new \Tickera\TC_Ticket( (int) $event_ticket_id );
                                if ( \Tickera\TC_Ticket::is_sales_available( (int) $event_ticket_id ) ) : ?>
                                <tr style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) ); ?>">
                                    <?php do_action( 'tc_event_col_value_before_ticket_type', (int) $event_ticket_id ); ?>
                                    <td data-column="<?php esc_attr_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?>" style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) ); ?>"><?php echo esc_html( apply_filters( 'tc_tickets_table_title', $event_ticket->details->post_title, $event_ticket_id ) ); ?></td>
                                    <?php do_action( 'tc_event_col_value_before_ticket_price', (int) $event_ticket_id ); ?>
                                    <td data-column="<?php esc_attr_e( 'Price', 'tickera-event-ticketing-system' ); ?>" style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) ); ?>"><?php echo esc_html( do_shortcode( '[ticket_price id="' . (int) $event_ticket->details->ID . '"]' ) ); ?></td>
                                    <?php if ( $quantity ) { ?>
                                        <?php do_action( 'tc_event_col_value_before_quantity', (int) $event_ticket_id ); ?>
                                        <td data-column="<?php esc_attr_e( 'Quantity', 'tickera-event-ticketing-system' ); ?>" style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) ); ?>"><?php echo wp_kses( tickera_quantity_selector( (int) $event_ticket->details->ID, true ), wp_kses_allowed_html( 'tickera_quantity_selector' ) ); ?></td>
                                    <?php } ?>
                                    <?php do_action( 'tc_event_col_value_before_cart_title', (int) $event_ticket_id ); ?>
                                    <td data-column="<?php esc_attr_e( 'Cart', 'tickera-event-ticketing-system' ); ?>" style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) ); ?>"><?php echo wp_kses( do_shortcode( '[ticket id="' . (int) $event_ticket->details->ID . '" type="' . sanitize_text_field( $link_type ) . '" title="' . sanitize_text_field( $button_title ) . '" soldout_message="' . sanitize_text_field( $soldout_message ) . '" open_method="regular"]' ), wp_kses_allowed_html( 'tickera_add_to_cart' ) ); ?></td>
                                    </tr><?php
                                endif;
                            endforeach; ?>
                        </table>
                    </div><?php
                endif;
            } else {
                echo wp_kses_post( '<div style="display:none;">' . esc_html__( 'No associated ticket types found. Try selecting another event.', 'tickera-event-ticketing-system' ) . '</div>' ); // Echo empty string to hide "Empty block" notice
            }
            return ob_get_clean();
        }

        /**
         * Render Event Add To Cart.
         * Excluded in block inserter at v3.5.1.5. Replaced by Event Add To Cart Group
         * Backward Compatibility
         *
         * @param $attributes
         * @return false|string
         */
        function render_event_add_to_cart_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';
            $show_event_title = ( isset( $attributes[ 'show_event_title' ] ) && ( $attributes[ 'show_event_title' ] == true || $attributes[ 'show_event_title' ] == 1 ) ) ? 'show_event_title="true"' : '';
            $show_price = ( isset( $attributes[ 'show_price' ] ) && ( $attributes[ 'show_price' ] == true || $attributes[ 'show_price' ] == 1 ) ) ? 'show_price="true"' : '';
            $quantity = ( isset( $attributes[ 'quantity' ] ) && ( $attributes[ 'quantity' ] == true || $attributes[ 'quantity' ] == 1 ) ) ? 'quantity="true"' : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-event-add-to-cart-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
            <?php
            if ( isset( $attributes[ 'event_id' ] ) && $attributes[ 'event_id' ] ) {

                $display_type = ( ! empty( $attributes[ 'display_type' ] ) ) ? 'display_type="' . sanitize_text_field( $attributes[ 'display_type' ] ) . '"' : '';
                $button_title = ( ! empty( $attributes[ 'button_title' ] ) ) ? 'title="' . sanitize_text_field( $attributes[ 'button_title' ] ) . '"' : '';
                $ticket_type_title = ( ! empty( $attributes[ 'ticket_type_title' ] ) ) ? 'ticket_type_title="' . sanitize_text_field( $attributes[ 'ticket_type_title' ] ) . '"' : '';
                $price_title = ( ! empty( $attributes[ 'price_title' ] ) ) ? 'price_title="' . sanitize_text_field( $attributes[ 'price_title' ] ) . '"' : '';
                $cart_title = ( ! empty( $attributes[ 'cart_title' ] ) ) ? 'cart_title="' . sanitize_text_field( $attributes[ 'cart_title' ] ) . '"' : '';
                $quantity_title = ( ! empty( $attributes[ 'quantity_title' ] ) ) ? 'quantity_title="' . sanitize_text_field( $attributes[ 'quantity_title' ] ) . '"' : '';
                $soldout_message = ( ! empty( $attributes[ 'soldout_message' ] ) ) ? 'soldout_message="' . sanitize_text_field( $attributes[ 'soldout_message' ] ) . '"' : '';
                $type = ( ! empty( $attributes[ 'link_type' ] ) ) ? 'type="' . sanitize_text_field( $attributes[ 'link_type' ] ) . '"' : '';

                $content = trim( do_shortcode( '[tc_event id="' . (int) $attributes[ 'event_id' ] . '" ' . $display_type . ' ' . $show_event_title . ' ' . $show_price . ' ' . $button_title . ' ' . $ticket_type_title . ' ' . $price_title . ' ' . $cart_title . ' ' . $quantity_title . ' ' . $soldout_message . '  ' . $type . ' ' . $quantity . ']' ) );

                if ( $content ) {
                    echo wp_kses_post( $content );

                } else {
                    esc_html_e( 'No associated ticket types found. Try selecting another event.', 'tickera-event-ticketing-system' );
                }

            } else {
                esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
            }
            ?>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Event's Date
         *
         * @param $attributes
         * @return false|string
         *
         * Support Block styles
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_date_shortcode( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-event-date-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                <div class="tc-block-inner-wrapper" style="<?php
                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'margin' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) );
                ?>">
                <?php
                if ( $event_id ) {

                    $content = trim( do_shortcode( '[tc_event_date event_id="' . (int) $event_id . '"]' ) );

                    if ( $content ) {
                        echo wp_kses_post( $content );

                    } else {
                        esc_html_e( 'Event date is not set.', 'tickera-event-ticketing-system' );
                    }

                } else {
                    esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
                }
                ?>
                </div>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Event's Location
         *
         * @param $attributes
         * @return false|string
         *
         * Support Block Styles
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_location_shortcode( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-event-location-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                <div class="tc-block-inner-wrapper" style="<?php
                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'margin' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) );
                ?>">
                <?php

                if ( $event_id ) {

                    $content = trim( do_shortcode( '[tc_event_location id="' . (int) $event_id . '"]' ) );

                    if ( $content ) {
                        echo esc_html( $content );

                    } else {
                        esc_html_e( 'Event location is not set.', 'tickera-event-ticketing-system' );
                    }

                } else {
                    esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
                }
                ?>
                </div>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Event's Terms and Conditions
         *
         * @param $attributes
         * @return false|string
         *
         * Support Block Styles
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_terms_shortcode( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-event-terms-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
                <div class="tc-block-inner-wrapper">
                <?php
                if ( $event_id ) {

                    $content = trim( do_shortcode( '[tc_event_terms id="' . (int) $event_id . '"]' ) );

                    if ( $content ) {
                        echo wp_kses_post( $content );

                    } else {
                        esc_html_e( 'Event Terms & Conditions are not set.', 'tickera-event-ticketing-system' );
                    }

                } else {
                    esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
                }
                ?>
                </div>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Event's Logo
         *
         * @param $attributes
         * @return false|string
         *
         * Support Block Styles
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_logo_shortcode( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-event-logo-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
            <?php

            if ( $event_id ) {

                $content = trim( do_shortcode( '[tc_event_logo id="' . (int) $event_id . '"]' ) );

                if ( $content ) {
                    echo wp_kses_post( $content );

                } else {
                    esc_html_e( 'Logo is not set for this event.', 'tickera-event-ticketing-system' );
                }

            } else {
                esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
            }
            ?>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Event's Sponsors Logo
         *
         * @param $attributes
         * @return false|string
         *
         * Support Block Styles
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_sponsors_logo_shortcode( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-event-sponsors-logo-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
            <?php

            if ( $event_id ) {

                $content = trim( do_shortcode( '[tc_event_sponsors_logo id="' . (int) $event_id . '"]' ) );

                if ( $content ) {
                    echo wp_kses_post( $content );

                } else {
                    esc_html_e( 'Sponsors logo / image is not set for this event.', 'tickera-event-ticketing-system' );
                }

            } else {
                esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
            }
            ?>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Event's Tickets Sold
         *
         * @param $attributes
         * @return false|string
         *
         * Support Block Styles
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_tickets_sold_shortcode( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-event-tickets-sold-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                <div class="tc-block-inner-wrapper" style="<?php
                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'margin' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) );
                ?>">
                <?php

                if ( $event_id ) {

                    $content = trim( do_shortcode( '[event_tickets_sold event_id="' . (int) $event_id . '"]' ) );

                    if ( $content ) {
                        echo esc_html( $content );

                    } else {
                        echo esc_html( 0 );
                    }

                } else {
                    esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
                }
                ?>
                </div>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * * Render Event's Tickets Left
         *
         * @param $attributes
         * @return false|string
         *
         * Support Block Styles
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_event_tickets_left_shortcode( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'event_id' ]  ) ? (int) $attributes[ 'event_id' ] : '';
            }

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-event-tickets-left-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                <div class="tc-block-inner-wrapper" style="<?php
                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'margin' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) );
                ?>">
                <?php

                if ( $event_id ) {

                    $content = trim( do_shortcode( '[event_tickets_left event_id="' . $event_id . '"]' ) );

                    if ( $content ) {
                        echo esc_html( $content );

                    } else {
                        echo esc_html( 0 );
                    }

                } else {
                    esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
                }
                ?>
                </div>
            </div>
            <?php return ob_get_clean();
        }

        function render_tickets_sold_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-tickets-sold-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                <div class="tc-block-inner-wrapper" style="<?php
                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'margin' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) );
                ?>">
                <?php
                if ( isset( $attributes[ 'ticket_type_id' ] ) && $attributes[ 'ticket_type_id' ] ) {

                    $content = trim( do_shortcode( '[tickets_sold ticket_type_id="' . (int) $attributes[ 'ticket_type_id' ] . '"]' ) );

                    if ( $content ) {
                        echo esc_html( $content );

                    } else {
                        echo esc_html( 0 );
                    }

                } else {
                    esc_html_e( 'Please select a ticket type in the block settings box', 'tickera-event-ticketing-system' );
                }
                ?>
                </div>
            </div>
            <?php return ob_get_clean();
        }

        function render_tickets_left_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-tickets-left-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                <div class="tc-block-inner-wrapper" style="<?php
                echo esc_attr( self::convert_inline_to_string( $inline, 'font'  ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'padding'  ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'margin'  ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'border'  ) );
                echo esc_attr( self::convert_inline_to_string( $inline, 'background'  ) );
                ?>">
                <?php
                if ( isset( $attributes[ 'ticket_type_id' ] ) && $attributes[ 'ticket_type_id' ] ) {

                    $content = trim( do_shortcode( '[tickets_left ticket_type_id="' . (int) $attributes[ 'ticket_type_id' ] . '"]' ) );

                    if ( $content ) {
                        echo esc_html( $content );

                    } else {
                        echo esc_html( 0 );
                    }

                } else {
                    esc_html_e( 'Please select a ticket type in the block settings box', 'tickera-event-ticketing-system' );
                }
                ?>
                </div>
            </div>
            <?php return ob_get_clean();
        }

        function render_order_history_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-order-history-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
            <?php

            $content = trim( do_shortcode( '[tc_order_history]' ) );

            if ( $content ) {
                echo wp_kses_post( $content );

            } else {
                esc_html_e( 'Oops! You personally don\'t have anything in the order history so we can\'t show a preview here. Sorry :/', 'tickera-event-ticketing-system' );
            }
            ?>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Bridge for Woocommerce Shortcodes
         *
         * @param $attributes
         * @return false|string|void
         */
        function render_woo_add_to_cart_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-woo-add-to-cart-group-wrap"></div>
            <div class="tc-woo-add-to-cart-wrap<?php echo esc_attr( $classes ) . esc_attr( $additional_classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
            <?php
            if ( isset( $attributes[ 'id' ] ) && $attributes[ 'id' ] ) {
                $show_price = isset( $attributes[ 'show_price' ] ) ? $attributes[ 'show_price' ] : false;
                $quantity = ( isset( $attributes[ 'quantity' ] ) && $attributes[ 'quantity' ] ) ? 'true' : 'false';
                echo wp_kses_post( do_shortcode( '[add_to_cart id="' . (int) $attributes[ 'id' ] . '" ' . 'show_price="' . $show_price . '"' . ' quantity="' . $quantity .  '" style="border:none;"]' ) );
            } else {
                esc_html_e( 'Please select a ticket type (product) in the block settings box', 'tickera-event-ticketing-system' );
            }
            ?>
            </div>
            <?php return ob_get_clean();
        }

        function render_woo_ticket_price_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <?php if ( isset( $attributes[ 'id' ] ) && $attributes[ 'id' ] ) : ?>
                <div class="tc-woo-add-to-cart-group-wrap"></div>
                <div class="tc-woo-ticket-price-wrap<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                    <div class="tc-block-inner-wrapper" style="<?php
                        echo esc_attr( self::convert_inline_to_string( $inline, 'font'  ) );
                        echo esc_attr( self::convert_inline_to_string( $inline, 'padding'  ) );
                        echo esc_attr( self::convert_inline_to_string( $inline, 'margin'  ) );
                        echo esc_attr( self::convert_inline_to_string( $inline, 'border'  ) );
                        echo esc_attr( self::convert_inline_to_string( $inline, 'background'  ) );
                        ?>">
                        <?php
                        $product = wc_get_product( (int) $attributes[ 'id' ] );
                        if ( $product ) echo wp_kses_post( $product->get_price_html() );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php return ob_get_clean();
        }

        /**
         * Render Event Tickets Table's Columns.
         * Support Block Styles
         *
         * @param $attributes
         * @return false|string
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_woo_event_add_to_cart_columns_content( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'id' ]  ) ? (int) $attributes[ 'id' ] : '';
            }

            ob_start();

            // No event selected
            if ( ! $event_id ) {
                esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
                return ob_get_clean();
            }

            // Attribute Values
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }

            /**
             * Display Type - Dropdown
             */
            $display_type = isset( $attributes[ 'display_type' ] ) ? $attributes[ 'display_type' ] : '';
            if ( 'dropdown' == $display_type ) {

                $show_event_title = ( isset( $attributes[ 'show_event_title' ] ) && ( $attributes[ 'show_event_title' ] == true || $attributes[ 'show_event_title' ] == 1 ) ) ? 'show_event_title="true"' : '';
                $show_price = ( isset( $attributes[ 'show_price' ] ) && ( $attributes[ 'show_price' ] == true || $attributes[ 'show_price' ] == 1 ) ) ? 'show_price="true"' : '';
                $quantity = ( isset( $attributes[ 'quantity' ] ) && ( $attributes[ 'quantity' ] == true || $attributes[ 'quantity' ] == 1 ) ) ? 'quantity="true"' : '';

                $html = '<div class="tc-woo-event-add-to-cart-group-wrap tc-woo-event-add-to-cart-dropdown' . esc_attr( $additional_classes ) . esc_attr( $classes ) . '" style="' . esc_attr( $styles ) . '">';
                $content = trim( do_shortcode( '[tc_wb_event id="' . (int) $event_id . '" display_type="dropdown" ' . $show_event_title . ' ' . $show_price . ' ' . $quantity . ']' ) );
                if ( $content ) {
                    $html .= $content;
                    $html .= '</div>';
                    echo wp_kses_post( $html );

                } else {
                    esc_html_e( 'No associated ticket types (products) found. Try selecting another event.', 'tickera-event-ticketing-system' );
                }

                return ob_get_clean();
            }

            /**
             * Display Type - Table
             */
            $ticket_type_title = isset( $attributes[ 'ticket_type_title' ] ) ? sanitize_text_field( $attributes[ 'ticket_type_title' ] ) : '';
            $price_title = isset( $attributes[ 'price_title' ] ) ? sanitize_text_field( $attributes[ 'price_title' ] ) : '';
            $cart_title = isset( $attributes[ 'cart_title' ]  ) ? sanitize_text_field( $attributes[ 'cart_title' ] ) : '';
            $quantity = isset( $attributes[ 'quantity' ]  ) ? $attributes[ 'quantity' ] : '';
            $quantity_title = isset( $attributes[ 'quantity_title' ] ) ? sanitize_text_field( $attributes[ 'quantity_title' ] ) : '';

            $event_tickets = get_posts( [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'meta_query' => [
                    'relation' => 'AND',
                    [ 'key' => '_tc_is_ticket', 'compare' => '=', 'value' => 'yes' ],
                    [ 'key' => '_event_name', 'compare' => '=', 'value' => (int) $event_id ],
                ]
            ] );
            ?>
            <div class="tc-woo-event-add-to-cart-group-wrap tc-woo-event-add-to-cart-columns<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>">
                <?php if ( count( $event_tickets ) > 0 ) : ?>
                    <table cellspacing="0" class="event_tickets tickera" style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) ); ?>">
                        <tr style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) ); ?>">
                            <?php do_action( 'tc_wb_event_col_title_before_ticket_title' ); ?>
                            <th style="<?php
                                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                                ?>"><?php echo esc_html( $ticket_type_title ); ?></th>
                            <?php do_action( 'tc_wb_event_col_title_before_ticket_price' ); ?>
                            <th style="<?php
                                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                                ?>"><?php echo esc_html( $price_title ); ?></th>
                            <?php if ( $quantity ) : ?>
                                <?php do_action( 'tc_wb_event_col_title_before_quantity' ); ?>
                                <th style="<?php
                                    echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                                    echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                                    ?>"><?php echo esc_html( $quantity_title ); ?></th>
                            <?php endif; ?>
                            <?php do_action( 'tc_wb_event_col_title_before_cart_title' ); ?>
                            <th style="<?php
                                echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) );
                                echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                                ?>"><?php echo esc_html( $cart_title ); ?></th>
                        </tr>
                    </table>
                <?php else : ?>
                    <div><?php esc_html_e( 'No associated ticket types (products) found. Try selecting another event.', 'tickera-event-ticketing-system' ); ?></div>
                <?php endif; ?>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Event Tickets Table's Rows.
         * Supports Block Styles
         *
         * @param $attributes
         * @return false|string
         * @since 3.5.1.5
         *
         * Render current event if under tc_events post_type.
         * Disable event's select control on current event.
         * @since 3.5.1.8
         */
        function render_woo_event_add_to_cart_rows_content( $attributes ) {

            global $post;

            if ( $post && isset( $post->ID ) && isset( $post->post_type ) && 'tc_events' == $post->post_type ) {
                $event_id = (int) $post->ID;

            } else {
                $event_id = isset( $attributes[ 'id' ]  ) ? (int) $attributes[ 'id' ] : '';
            }

            ob_start();
            $_tc_used_for_seatings_count = 0;

            // No event selected
            if ( ! $event_id ) {
                echo wp_kses_post( '<div></div>' ); // Echo empty string to hide "Empty block" notice
                return ob_get_clean();
            }

            /**
             * Display Type - Dropdown
             * Hide Table
             */
            $display_type = isset( $attributes[ 'display_type' ] ) ? $attributes[ 'display_type' ] : '';
            if ( 'dropdown' == $display_type ) {
                echo wp_kses_post( '<div></div>' );
                return ob_get_clean();
            }

            /**
             * Display Type - Table
             */
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $inline = isset( $styles_values[ 'inline' ] ) ? $styles_values[ 'inline' ] : [];
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';
            $quantity = isset( $attributes[ 'quantity' ] ) ? $attributes[ 'quantity' ] : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }

            $event = new \Tickera\TC_Event( $event_id );
            $event_tickets = get_posts( [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'meta_query' => [
                    'relation' => 'AND',
                    [ 'key' => '_tc_is_ticket', 'compare' => '=', 'value' => 'yes' ],
                    [ 'key' => '_event_name', 'compare' => '=', 'value' => (int) $event_id ],
                ]
            ] );

            if ( count( $event_tickets ) > 0 ) {
                if ( 'publish' == $event->details->post_status ) : ?>
                    <div class="tc-woo-event-add-to-cart-group-wrap tc-woo-event-add-to-cart-rows event_tickets tickera<?php echo esc_attr( $additional_classes ) . esc_attr( $classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
                        <table cellspacing="0" class="event_tickets tickera" style="<?php
                        echo esc_attr( self::convert_inline_to_string( $inline, 'font' ) );
                        echo esc_attr( self::convert_inline_to_string( $inline, 'border' ) );
                        ?>">
                            <tr></tr>
                            <?php foreach ( $event_tickets as $ticket_type ) :

                                $_tc_used_for_seatings = get_post_meta( $ticket_type->ID, '_tc_used_for_seatings', true );
                                $_tc_used_for_seatings = $_tc_used_for_seatings == 'yes' ? true : false;

                                $product = wc_get_product( $ticket_type->ID );
                                if ( $product ) {

                                    $wc_catalog_visibility = $product->get_catalog_visibility();
                                    if ( ! in_array( $wc_catalog_visibility, [ 'hidden', 'search' ] ) && \Tickera\TC_Ticket::is_sales_available( $ticket_type->ID ) ) : ?>
                                        <?php if ( ! $_tc_used_for_seatings ) : ?>
                                        <tr style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'background' ) ); ?>">
                                            <?php do_action( 'tc_wb_event_col_value_before_ticket_type', (int) $ticket_type->ID ); ?>
                                            <td data-column="<?php esc_attr_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?>"
                                                style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) ); ?>"><?php echo esc_html( apply_filters( 'wc_product_tickets_table_title', $ticket_type->post_title, $ticket_type->ID ) ); ?></td>
                                            <?php do_action( 'tc_wb_event_col_value_before_ticket_price', (int) $ticket_type->ID ); ?>
                                            <td data-column="<?php esc_attr_e( 'Price', 'tickera-event-ticketing-system' ); ?>"
                                                style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) ); ?>"><?php echo wp_kses_post( apply_filters( 'wc_product_tickets_table_price', $product->get_price_html(), $ticket_type->ID ) ); ?></td>
                                            <?php if ( $quantity ) {

                                                if ( ! $product->is_type( 'variable' ) ) {
                                                    do_action( 'tc_wb_event_col_value_before_quantity', (int) $ticket_type->ID );
                                                    $quantity_selector_field = woocommerce_quantity_input(
                                                        array(
                                                            'min_value' => $product->get_min_purchase_quantity(),
                                                            'max_value' => $product->get_max_purchase_quantity(),
                                                            'input_value' => $product->get_min_purchase_quantity(),
                                                            'classes' => 'tc-wb-quantity-selector'
                                                        ),
                                                        $product,
                                                        false
                                                    );

                                                    echo wp_kses( '<td data-column="' . esc_attr__( 'Quantity', 'tickera-event-ticketing-system' ) . '">' . $quantity_selector_field . '</td>', wp_kses_allowed_html( 'tickera_quantity_selector' ) );

                                                } else {
                                                    echo wp_kses( '<td data-column="' . esc_attr__( 'Quantity', 'tickera-event-ticketing-system' ) . '"></td>', wp_kses_allowed_html( 'tickera' ) );
                                                }
                                            }
                                            do_action( 'tc_wb_event_col_value_before_cart', (int) $ticket_type->ID ); ?>
                                            <td data-column="<?php esc_attr_e( 'Cart', 'tickera-event-ticketing-system' ); ?>"
                                                style="<?php echo esc_attr( self::convert_inline_to_string( $inline, 'padding' ) ); ?>"><?php echo wp_kses_post( do_shortcode( '[add_to_cart id="' . (int) $ticket_type->ID . '" style="" show_price="false" class="tc-wb-add-to-cart"]' ) ); ?></td>
                                            </tr><?php
                                        else :
                                            $_tc_used_for_seatings_count++;
                                        endif;
                                    endif;
                                }
                            endforeach; ?>
                        </table>
                    </div><?php
                endif;
                if ( $_tc_used_for_seatings_count ) : ?>
                    <div class="tc_warning_ticket_types_needed"><?php
                        echo wp_kses_post( sprintf(
                            /* translators: %d: The number of ticket types configured as seats. */
                            __( '<strong>ADMIN NOTICE:</strong> <u>%d</u> ticket types are hidden because they are used for seating and can be added to a cart that way only.', 'tickera-event-ticketing-system' ),
                            esc_html( (int) $_tc_used_for_seatings_count )
                        ) );
                    ?></div>
                <?php endif;
            } else {
                echo wp_kses_post( '<div style="display:none;">' . esc_html__( 'No associated ticket types (products) found. Try selecting another event.', 'tickera-event-ticketing-system' ) . '</div>' ); // Echo empty string to hide "Empty block" notice
            }

            return ob_get_clean();
        }

        function render_woo_event_add_to_cart_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? ' ' . sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-woo-event-add-to-cart-wrap<?php echo esc_attr( $classes ) . esc_attr( $additional_classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
            <?php
            if ( isset( $attributes[ 'id' ] ) && $attributes[ 'id' ] ) {

                $display_type = ( ! empty( $attributes[ 'display_type' ] ) ) ? 'display_type="' . sanitize_text_field( $attributes[ 'display_type' ] ) . '"' : '';
                $ticket_type_title = ( ! empty( $attributes[ 'ticket_type_title' ] ) ) ? 'ticket_type_title="' . sanitize_text_field( $attributes[ 'ticket_type_title' ] ) . '"' : '';
                $price_title = ( ! empty( $attributes[ 'price_title' ] ) ) ? 'price_title="' . sanitize_text_field( $attributes[ 'price_title' ] ) . '"' : '';
                $cart_title = ( ! empty( $attributes[ 'cart_title' ] ) ) ? 'cart_title="' . sanitize_text_field( $attributes[ 'cart_title' ] ) . '"' : '';
                $quantity_title = ( ! empty( $attributes[ 'quantity_title' ] ) ) ? 'quantity_title="' . sanitize_text_field( $attributes[ 'quantity_title' ] ) . '"' : '';

                $show_event_title = ( isset( $attributes[ 'show_event_title' ] ) && ( $attributes[ 'show_event_title' ] == true || $attributes[ 'show_event_title' ] == 1 ) ) ? 'show_event_title="true"' : '';
                $show_price = ( isset( $attributes[ 'show_price' ] ) && ( $attributes[ 'show_price' ] == true || $attributes[ 'show_price' ] == 1 ) ) ? 'show_price="true"' : '';
                $quantity = ( isset( $attributes[ 'quantity' ] ) && ( true == $attributes[ 'quantity' ] || 1 == $attributes[ 'quantity' ] ) ) ? 'quantity="true"' : '';

                $content = trim( do_shortcode( '[tc_wb_event  id="' . (int) $attributes[ 'id' ] . '" ' . $display_type . ' ' . $show_event_title . ' ' . $show_price . ' ' . $quantity . ' ' . $ticket_type_title . ' ' . $price_title . ' ' . $cart_title . ' ' . $quantity_title . ']' ) );

                if ( $content ) {
                    echo wp_kses_post( $content );

                } else {
                    esc_html_e( 'No associated ticket types (products) found. Try selecting another event.', 'tickera-event-ticketing-system' );
                }

            } else {
                esc_html_e( 'Please select an event in the block settings box', 'tickera-event-ticketing-system' );
            }
            ?>
            </div>
            <?php return ob_get_clean();
        }

        /**
         * Render Seating Chart Shortcode
         *
         * @param $attributes
         * @return false|string|void
         */
        function render_seating_charts_shortcode( $attributes ) {

            ob_start();
            $styles_values = self::get_style_values( $attributes );
            $classes = isset( $styles_values[ 'classes' ] ) ? ' ' . sanitize_text_field( $styles_values[ 'classes' ] ) : '';
            $styles = isset( $styles_values[ 'styles' ] ) ? sanitize_text_field( $styles_values[ 'styles' ] ) : '';
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }
            ?>
            <div class="tc-seating-charts-wrap<?php echo esc_attr( $classes ) . esc_attr( $additional_classes ); ?>" style="<?php echo esc_attr( $styles ); ?>">
            <?php

            if ( isset( $attributes[ 'id' ] ) && $attributes[ 'id' ] ) {

                $show_legend = ( isset( $attributes[ 'show_legend' ] ) && ( true == $attributes[ 'show_legend' ] || 1 == $attributes[ 'show_legend' ] ) ) ? 'show_legend="true"' : '';
                $button_title = ( isset( $attributes[ 'button_title' ] ) ) ? 'button_title="' . sanitize_text_field( $attributes[ 'button_title' ] ) . '"' : '';
                $subtotal_title = ( isset( $attributes[ 'subtotal_title' ] ) ) ? 'subtotal_title="' . sanitize_text_field( $attributes[ 'subtotal_title' ] ) . '"' : '';
                $cart_title = ( isset( $attributes[ 'cart_title' ] ) ) ? 'cart_title="' . sanitize_text_field( $attributes[ 'cart_title' ] ) . '"' : '';

                $content = trim( do_shortcode( '[tc_seat_chart id="' . (int) $attributes[ 'id' ] . '" ' . $button_title . ' ' . $subtotal_title . ' ' . $cart_title . ' ' . $show_legend . ']' ) );

                if ( $content ) {
                    echo wp_kses_post( $content );

                } else {
                    esc_html_e( 'No seating charts found. Sorry :(', 'tickera-event-ticketing-system' );
                }

            } else {
                esc_html_e( 'Please select a seating chart block settings box', 'tickera-event-ticketing-system' );
            }
            ?>
            </div>
            <?php return ob_get_clean();
        }

        function render_event_calendar_shortcode( $attributes ) {

            ob_start();
            $additional_classes = isset( $attributes[ 'className' ] ) ? sanitize_text_field( $attributes[ 'className' ] ) : '';

            // Collect custom classes from extensions.
            $classes = '';
            foreach ( $attributes as $key => $value ) {
                if ( strpos( $key, '_class' ) ) {
                    $classes .= ' ' . $value;
                }
            }

            // Wrap the content inside a div container if additional classes exists
            echo wp_kses_post( ( $additional_classes || $classes ) ? '<div class="' . esc_attr( $additional_classes ) . esc_attr( $classes ) . '">' : '' );

            $show_past_events = ( $attributes[ 'show_past_events' ] == true || $attributes[ 'show_past_events' ] == 1 ) ? 'show_past_events="true"' : '';
            $color_scheme = ( ! empty( $attributes[ 'color_scheme' ] ) )
                ? 'color_scheme="' . sanitize_text_field( $attributes[ 'color_scheme' ] ) . '"'
                : 'color_scheme="default"';

            $lang = ( isset( $attributes[ 'lang' ] ) && $attributes[ 'lang' ] )
                ? 'lang="' . sanitize_text_field( $attributes[ 'lang' ] ) . '"'
                : 'lang="en"';

            $content = trim( do_shortcode( '[tc_calendar color_scheme="' . $color_scheme . '" lang="' . $lang . '" ' . $show_past_events . ']' ) );

            if ( $content ) {
                echo wp_kses_post( $content );

            } else {
                esc_html_e( 'Something went wrong and we don\'t have a clue why the content is not shown :( Please contact support for further assistance.', 'tickera-event-ticketing-system' );
            }

            // Wrap the content inside a div container if additional classes exists
            echo wp_kses_post( ( $additional_classes || $classes ) ? '</div>' : '' );

            return ob_get_clean();
        }

        /**
         * Collect all styles from the gutenberg attributes.
         *
         * @param $attribute
         * @return array
         *
         * @since 3.5.1.3
         */
        function get_style_values( $attribute ) {

            // $referrer  = wp_get_referer();
            $styles = '';
            $inline = [];
            $classes = '';
            $attribute_styles = isset( $attribute[ 'style' ] ) ? $attribute[ 'style' ] : [];

            // Text Color
            if ( isset( $attribute[ 'textColor' ] ) ) {
                $classes .= ' tc-has-text-color';
                $styles .= '--tc-block-text-color:var(--wp--preset--color--' . $attribute[ 'textColor' ] . ');';
                $inline[ 'font' ][ 'color' ] = 'var(--wp--preset--color--' . $attribute[ 'textColor' ] . ')';
            }

            // Link Color
            $link_color = ( isset( $attribute_styles[ 'elements' ] ) && isset( $attribute_styles[ 'elements' ][ 'link' ] ) && isset( $attribute_styles[ 'elements' ][ 'link' ][ 'color' ] ) && isset( $attribute_styles[ 'elements' ][ 'link' ][ 'color' ][ 'text' ] ) ) ? $attribute_styles[ 'elements' ][ 'link' ][ 'color' ][ 'text' ] : '';
            if ( $link_color ) {
                if ( is_string( $link_color ) && str_contains( $link_color, 'var:preset|color|' ) ) {
                    $classes .= ' tc-has-link-color';
                    $value = str_replace( 'var:preset|color|', '', $link_color );
                    $styles .= '--tc-block-link-color:var(--wp--preset--color--' . $value . ');';
                    $inline[ 'link' ][ 'color' ] = 'var(--wp--preset--color--' . $value . ')';
                }
            }

            // Link Hover
            $link_hover = ( isset( $attribute_styles[ 'elements' ] ) && isset( $attribute_styles[ 'elements' ][ 'link' ] ) && isset( $attribute_styles[ 'elements' ][ 'link' ][ ':hover' ] ) && isset( $attribute_styles[ 'elements' ][ 'link' ][ ':hover' ][ 'color' ] ) && isset( $attribute_styles[ 'elements' ][ 'link' ][ ':hover' ][ 'color' ][ 'text' ] ) ) ? $attribute_styles[ 'elements' ][ 'link' ][ ':hover' ][ 'color' ][ 'text' ] : '';
            if ( $link_hover ) {
                if ( is_string( $link_hover ) && str_contains( $link_hover, 'var:preset|color|' ) ) {
                    $classes .= ' tc-has-link-hover-color';
                    $value = str_replace( 'var:preset|color|', '', $link_hover );
                    $styles .= '--tc-block-link-hover-color:var(--wp--preset--color--' . $value . ');';
                }
            }

            // Typography: Font family
            if ( isset( $attribute[ 'fontFamily' ] ) ) {
                $classes .= ' tc-has-font-family';
                $styles .= sprintf( '--tc-block-font-family:%s;', $attribute[ 'fontFamily' ] );
                $inline[ 'font' ][ 'font-family' ] = 'var(--wp--preset--font-family--' . $attribute[ 'fontFamily' ] . ')';
            }

            // Typography: Font size
            if ( isset( $attribute[ 'fontSize' ] ) ) {
                $classes .= ' tc-has-font-size';
                $styles .= sprintf( '--tc-block-font-size:var(--wp--preset--font-size--%s);', $attribute[ 'fontSize' ] );
                $inline[ 'font' ][ 'font-size' ] = 'var(--wp--preset--font-size--' . $attribute[ 'fontSize' ] . ')';
            }

            // Typography: Font style | Font weight
            $typography = isset( $attribute_styles[ 'typography' ] ) ? $attribute_styles[ 'typography' ] : [];
            foreach ( $typography as $key => $value ) {
                $classes .= sprintf( ' tc-has-%s', strtolower( $key ) );
                $styles .= sprintf( '--tc-block-%s:%s;', strtolower( $key ), $value );

                // camelCase to spinal-case
                $key = strtolower( preg_replace( ["/([A-Z]+)/", "/_([A-Z]+)([A-Z][a-z])/"], ["-$1", "-$1-$2"], lcfirst( $key ) ) );
                switch( $key ) {
                    case 'fontFamily':
                        $inline[ 'font' ][ $key ] = 'var(--wp--preset--font-family--' . $value . ')';
                        break;

                    default:
                        $inline[ 'font' ][ $key ] = $value;
                }
            }

            // Spacing Margin | Padding
            $spacing = isset( $attribute_styles[ 'spacing' ] ) ? $attribute_styles[ 'spacing' ] : [];
            foreach ( $spacing as $key => $directions ) {
                if ( is_array( $directions ) ) {
                    foreach ( $directions as $direction => $value ) {
                        if ( is_string( $value ) && str_contains( $value, 'var:preset|spacing|' ) ) {
                            $value = str_replace( 'var:preset|spacing|', '', $value );
                            $value = sprintf( 'var(--wp--preset--spacing--%s)', $value );
                        }

                        $classes .= sprintf( ' tc-has-%s-%s', strtolower( $key ), strtolower( $direction ) );
                        $styles .= sprintf( '--tc-block-%s-%s:%s;', $key, $direction, $value );
                        $inline[ 'spacing' ][ $key ][ $key . '-' . $direction ] = $value;
                    }
                }
            }

            // Border Width | Radius | Color | Style
            $border = isset( $attribute_styles[ 'border' ] ) ? $attribute_styles[ 'border' ] : [];
            foreach ( $border as $key => $value ) {
                if ( is_array( $value ) ) {
                    foreach ( $value as $_key => $_val ) {

                        if ( is_array( $_val ) ) {
                            foreach ( $_val as $__key => $__val ) {
                                $classes .= sprintf( ' tc-has-border-%s-%s-%s', strtolower( $key ), strtolower( $_key ), strtolower( $__key ) );
                                $styles .= '--tc-block-border-' . strtolower( $key ) . '-' . strtolower( $_key ) . '-' . strtolower( $__key ) . ':' . $__val . ';';

                                // camelCase to spinal-case
                                $__key = strtolower( preg_replace( ["/([A-Z]+)/", "/_([A-Z]+)([A-Z][a-z])/"], ["-$1", "-$1-$2"], lcfirst( $__key ) ) );
                                switch ( $_key ) {
                                    case 'radius':
                                        $inline[ 'border' ][ 'border-' . strtolower( $__key ) . '-' . strtolower( $_key ) ] = $__val;
                                        break;
                                    default:
                                        $inline[ 'border' ][ 'border-' . strtolower( $_key ) . '-' . strtolower( $__key ) ] = $__val;
                                }
                            }

                        } elseif ( is_string( $_val ) && str_contains( $_val, 'var:preset|' . $_key . '|' )  ) {
                            $classes .= sprintf( ' tc-has-border-%s-%s', strtolower( $key ), strtolower( $_key ) );
                            $_val = str_replace( 'var:preset|' . $_key . '|', '', $_val );
                            $styles .= '--tc-block-border-' . strtolower( $key ) . '-' . strtolower( $_key ) . ':' . sprintf( 'var(--wp--preset--%s--%s);', $_key, $_val ) . ';';

                            // camelCase to spinal-case
                            $_val = sprintf( 'var(--wp--preset--%s--%s)', $_key, $_val );
                            $_key = strtolower( preg_replace( ["/([A-Z]+)/", "/_([A-Z]+)([A-Z][a-z])/"], ["-$1", "-$1-$2"], lcfirst( $_key ) ) );
                            switch ( $key ) {
                                case 'radius':
                                    $inline[ 'border' ][ 'border-' . strtolower( $_key ) . '-' . strtolower( $key ) ] = $_val;
                                    break;
                                default:
                                    $inline[ 'border' ][ 'border-' . strtolower( $key ) . '-' . strtolower( $_key ) ] = $_val;
                            }

                        } else {
                            $classes .= sprintf( ' tc-has-border-%s-%s', strtolower( $key ), strtolower( $_key ) );
                            $styles .= '--tc-block-border-' . strtolower( $key ) . '-' . strtolower( $_key ) . ':' . $_val . ';';

                            // camelCase to spinal-case
                            $_key = strtolower( preg_replace( ["/([A-Z]+)/", "/_([A-Z]+)([A-Z][a-z])/"], ["-$1", "-$1-$2"], lcfirst( $_key ) ) );
                            switch ( $key ) {
                                case 'radius':
                                    $inline[ 'border' ][ 'border-' . strtolower( $_key ) . '-' . strtolower( $key ) ] = $_val;
                                    break;
                                default:
                                    $inline[ 'border' ][ 'border-' . strtolower( $key ) . '-' . strtolower( $_key ) ] = $_val;
                            }
                        }
                    }

                } else {
                    $classes .= sprintf( ' tc-has-border-%s', strtolower( $key ) );
                    $styles .= '--tc-block-border-' . strtolower( $key ) . ':' . $value . ';';
                    $inline[ 'border' ][ 'border-' . strtolower( $key ) ] = $value;
                }
            }

            // Border Color
            if ( isset( $attribute[ 'borderColor' ] ) ) {
                $classes .= ' tc-has-border-color';
                $styles .= '--tc-block-border-color:var(--wp--preset--color--' . $attribute[ 'borderColor' ] . ');';
                $inline[ 'border' ][ 'border-color' ] = 'var(--wp--preset--color--' . $attribute[ 'borderColor' ] . ')';
            }

            // Background Color | Mixed
            if ( isset( $attribute[ 'backgroundColor' ] ) ) {
                $classes .= ' tc-has-background-color';
                $styles .= '--tc-block-background-color:var(--wp--preset--color--' . $attribute[ 'backgroundColor' ] .');';
                $inline[ 'background' ][ 'background-color' ] = 'var(--wp--preset--color--' . $attribute[ 'backgroundColor' ] . ')';

            } elseif ( isset( $attribute_styles[ 'color' ] ) && isset( $attribute_styles[ 'color' ][ 'background' ] ) ) {
                $classes .= ' tc-has-background-color';
                $styles .= '--tc-block-background-color:' . $attribute_styles[ 'color' ][ 'background' ] . ';';
                $inline[ 'background' ][ 'background-color' ] = $attribute_styles[ 'color' ][ 'background' ];
            }

            // Background Gradient | Mixed
            if ( isset( $attribute[ 'gradient' ] ) ) {
                $classes .= ' tc-has-background-gradient';
                $styles .= '--tc-block-background-gradient:var(--wp--preset--gradient--' . $attribute[ 'gradient' ] . ');';
                $inline[ 'background' ][ 'background' ] = 'var(--wp--preset--gradient--' . $attribute[ 'gradient' ] . ')';

            } elseif ( isset( $attribute_styles[ 'color' ] ) && isset( $attribute_styles[ 'color' ][ 'gradient' ] ) ) {
                $classes .= ' tc-has-background-gradient';
                $styles .= '--tc-block-background-gradient:' . $attribute_styles[ 'color' ][ 'gradient' ] . ';';
                $inline[ 'background' ][ 'background' ] = $attribute_styles[ 'color' ][ 'gradient' ];
            }

            return [ 'classes' => $classes, 'styles' => $styles, 'inline' => $inline ];
        }

        /**
         * Convert Inline Styles from array to string
         *
         * @param $inline
         * @param string $format
         * @return string|string[]
         * @since 3.5.1.5
         */
        function convert_inline_to_string( $inline, $format = 'font' ) {

            switch ( $format ) {

                case 'padding':
                case 'margin':
                    return ( isset( $inline[ 'spacing' ] ) && isset( $inline[ 'spacing' ][ $format ] ) ) ? str_replace( '=', ':', urldecode( http_build_query( $inline[ 'spacing' ][ $format ], '', '; ' ) ) . ';' ) : '';
                    break;

                default:
                    return isset( $inline[ $format ] ) ? str_replace( '=', ':', urldecode( http_build_query( $inline[ $format ], '', ';' ) ) . ';' ) : '';
            }
        }
    }
}

$TC_tc_gutentick = new TC_tc_gutentick();
