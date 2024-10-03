<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Beaver_Shortcode_Builder' ) ) {

    class TC_Beaver_Shortcode_Builder {

        function __construct() {

            // add filter and action for beaver builder
            if ( ( apply_filters( 'fl_builder_activate', true ) == true ) && ( isset( $_GET[ 'fl_builder' ] ) ) ) {
                add_action( 'wp_enqueue_scripts', array( $this, 'fl_builder_enqueue_styles_scripts' ) );
                add_action( 'wp_footer', array( $this, 'show_shortcodes' ) );
            }
        }

        /**
         * Add css and js for frontend beaver builder
         */
        public function fl_builder_enqueue_styles_scripts() {
            global $tc;
            wp_enqueue_style( $tc->name . '-colorbox', $tc->plugin_url . 'css/colorbox/colorbox.css', false, $tc->version );
            wp_enqueue_script( $tc->name . '-colorbox', $tc->plugin_url . 'js/jquery.colorbox-min.js', false, $tc->version );
            wp_enqueue_script( $tc->name . '-shortcode-builders-script', $tc->plugin_url . 'js/builders/shortcode-builder.js', array( $tc->name . '-colorbox' ), $tc->version );
            wp_enqueue_style( $tc->name . '-admin', $tc->plugin_url . 'css/admin.css', array(), $tc->version );
            wp_enqueue_style( $tc->name . '-beaver-sc-front', $tc->plugin_url . 'css/builders/beaver-sc-front.css', array(), $tc->version );
            wp_enqueue_script( $tc->name . '-beaver', $tc->plugin_url . 'js/builders/beaver.js', [], $tc->version, [ 'in_footer' => true ] );
        }

        function show_shortcodes() {
            $shortcode_builder = new TC_Shortcode_Builder( false );
            echo wp_kses( $shortcode_builder->form(), wp_kses_allowed_html( 'tickera' ) );
        }
    }

    $beaver_shortcode_builder = new TC_Beaver_Shortcode_Builder();
}
