<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Divi_Shortcode_Builder' ) ) {

    class TC_Divi_Shortcode_Builder {

        function __construct() {

            if ( isset( $_GET[ 'page' ] ) && 'et_theme_builder' == $_GET[ 'page' ] ) {
                add_action( 'admin_enqueue_scripts', array( $this, 'divi_builder_enqueue_styles_scripts' ), 20 );
            }

            if ( ( isset( $_GET[ 'et_fb' ] ) && $_GET[ 'et_fb' ] ) ) {
                add_action( 'et_fb_enqueue_assets', array( $this, 'divi_builder_enqueue_styles_scripts' ), 20 );
                add_action( 'et_before_main_content', array( $this, 'show_shortcodes' ) );
            }
        }

        /**
         * Add css and js for frontend builder
         */
        function divi_builder_enqueue_styles_scripts() {
            global $tc;
            wp_enqueue_style( $tc->name . '-divi', $tc->plugin_url . 'css/builders/divi-sc-front.css', false, $tc->version );
            wp_enqueue_script( $tc->name . '-shortcode-builders-script', $tc->plugin_url . 'js/builders/shortcode-builder.js', array( $tc->name . '-colorbox' ), $tc->version, true );
            wp_enqueue_script( $tc->name . '-divi', $tc->plugin_url . 'js/builders/divi.js', [], $tc->version, true );
        }

        function show_shortcodes() {
            $shortcode_builder = new TC_Shortcode_Builder( false );
            echo wp_kses( $shortcode_builder->form(), wp_kses_allowed_html( 'tickera' ) );
        }
    }

    $divi_shortcode_builder = new TC_Divi_Shortcode_Builder();
}