<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\TC_Divi_Shortcode_Builder' ) ) {

    class TC_Divi_Shortcode_Builder {

        function __construct() {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Divi theme builder page check only controls builder asset loading.
            if ( isset( $_GET[ 'page' ] ) && 'et_theme_builder' == sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) ) {
                add_action( 'admin_enqueue_scripts', array( $this, 'divi_builder_enqueue_styles_scripts' ), 20 );
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Divi frontend builder flag only controls builder asset loading and shortcode preview output.
            if ( ( isset( $_GET[ 'et_fb' ] ) && sanitize_text_field( wp_unslash( $_GET[ 'et_fb' ] ) ) ) ) {
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

    new TC_Divi_Shortcode_Builder();
}
