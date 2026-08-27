<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\TC_Divi_Shortcode_Builder' ) ) {

    class TC_Divi_Shortcode_Builder {

        var $divi5_assets_enqueued = false;

        function __construct() {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Divi theme builder page check only controls builder asset loading.
            if ( isset( $_GET[ 'page' ] ) && 'et_theme_builder' == sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) ) {
                add_action( 'admin_enqueue_scripts', array( $this, 'divi_builder_enqueue_styles_scripts' ), 20 );
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Divi frontend builder flag only controls builder asset loading and shortcode preview output.
            if ( ( isset( $_GET[ 'et_fb' ] ) && sanitize_text_field( wp_unslash( $_GET[ 'et_fb' ] ) ) ) ) {
                add_action( 'et_fb_enqueue_assets', array( $this, 'divi_builder_enqueue_styles_scripts' ), 20 );
                add_action( 'et_before_main_content', array( $this, 'show_shortcodes' ) );
                add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_divi5_assets' ), 100 );
            }
        }

        /**
         * Add css and js for frontend builder
         */
        function divi_builder_enqueue_styles_scripts() {

            if ( $this->is_divi5_builder() ) {
                $this->enqueue_divi5_assets();
                return;
            }

            global $tc;
            wp_enqueue_style( $tc->name . '-divi', $tc->plugin_url . 'css/builders/divi-sc-front.css', false, $tc->version );
            wp_enqueue_script( $tc->name . '-shortcode-builders-script', $tc->plugin_url . 'js/builders/shortcode-builder.js', array( $tc->name . '-colorbox' ), $tc->version, true );
            wp_enqueue_script( $tc->name . '-divi', $tc->plugin_url . 'js/builders/divi.js', [], $tc->version, true );
        }

        /**
         * Divi 5 no longer fires the legacy builder-assets lifecycle used by Divi 4.
         * Enqueue its adapter through the regular frontend queue in both the builder
         * top window and its preview window.
         */
        function maybe_enqueue_divi5_assets() {

            if ( $this->is_divi5_builder() ) {
                $this->enqueue_divi5_assets();
            }
        }

        /**
         * Check whether the current Visual Builder request uses Divi 5.
         *
         * @return bool
         */
        function is_divi5_builder() {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder routing flag.
            $force_divi4 = isset( $_GET[ 'et_bfb' ] ) && '1' === sanitize_text_field( wp_unslash( $_GET[ 'et_bfb' ] ) );

            return ! $force_divi4 && function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled();
        }

        /**
         * Load the Divi 5-specific shortcode-builder bridge.
         */
        function enqueue_divi5_assets() {

            global $tc;

            if ( $this->divi5_assets_enqueued ) {
                return;
            }

            $this->divi5_assets_enqueued = true;

            wp_enqueue_style( $tc->name . '-chosen', $tc->plugin_url . 'css/chosen.min.css', array(), $tc->version );
            wp_enqueue_style( $tc->name . '-divi', $tc->plugin_url . 'css/builders/divi-sc-front.css', false, $tc->version );
            wp_enqueue_style( $tc->name . '-divi5', $tc->plugin_url . 'css/builders/divi5-sc-front.css', array( $tc->name . '-divi' ), $tc->version );

            wp_enqueue_script( $tc->name . '-chosen', $tc->plugin_url . 'js/chosen.jquery.min.js', array( 'jquery' ), $tc->version, true );
            wp_enqueue_script( $tc->name . '-divi5', $tc->plugin_url . 'js/builders/divi5.js', array( 'jquery', $tc->name . '-chosen' ), $tc->version, true );

            wp_localize_script(
                $tc->name . '-divi5',
                'tc_divi5_shortcode_builder_vars',
                array(
                    'ajaxUrl'                            => tickera_apply_filters( 'tickera_ajaxurl', admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ) ),
                    'ajaxNonce'                          => wp_create_nonce( 'tc_ajax_nonce' ),
                    'buttonTitle'                        => $tc->title . ' ' . __( 'Shortcodes', 'tickera-event-ticketing-system' ),
                    'please_enter_at_least_3_characters' => __( 'Please enter at least 3 characters.', 'tickera-event-ticketing-system' ),
                )
            );
        }

        function show_shortcodes() {
            $shortcode_builder = new TC_Shortcode_Builder( false );
            echo wp_kses( $shortcode_builder->form(), wp_kses_allowed_html( 'tickera' ) );
        }
    }

    new TC_Divi_Shortcode_Builder();
}
