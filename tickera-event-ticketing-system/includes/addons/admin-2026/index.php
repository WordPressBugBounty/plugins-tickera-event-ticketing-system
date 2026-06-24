<?php
/**
 * Tickera Admin 2026 — design refresh loader.
 *
 * Bundled internal module (loaded via TC::load_tc_addons). Adds a body class and
 * enqueues the 2026 design-system stylesheet on every Tickera admin screen —
 * core screens, Settings, official add-on settings (they live under the Tickera
 * CPT menu), and the onboarding wizard — aligning them with the Tickera 2026 theme.
 *
 * @package Tickera
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TC_Admin_2026' ) ) {

	class TC_Admin_2026 {

		/**
		 * Tickera custom post types. The Tickera admin menu is CPT-based
		 * (edit.php?post_type=tc_events&page=…), so every Tickera + add-on
		 * settings page carries one of these in its screen / query.
		 *
		 * @var string[]
		 */
		private static $cpts = array(
			'tc_events', 'tc_tickets', 'tc_orders', 'tc_templates',
			'tc_api_keys', 'tc_tickets_instances', 'tc_discounts',
		);

		public static function init() {
			add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
			// The onboarding wizard renders its own full-screen <head> and fires
			// this action inside it — inject the wizard stylesheet there.
			add_action( 'tickera_admin_print_styles', array( __CLASS__, 'wizard_styles' ) );
		}

		/**
		 * Print the wizard stylesheet link inside the wizard's custom head.
		 */
		public static function wizard_styles() {
			$url = plugin_dir_url( __FILE__ ) . 'assets/tickera-wizard-2026.css';
			$path = plugin_dir_path( __FILE__ ) . 'assets/tickera-wizard-2026.css';
			$ver = file_exists( $path ) ? filemtime( $path ) : '1.0.0';
			echo '<link rel="stylesheet" href="' . esc_url( $url ) . '?v=' . esc_attr( $ver ) . '" />' . "\n";
		}

		/**
		 * Is the current admin screen a Tickera (or Tickera add-on) screen?
		 *
		 * @return bool
		 */
		public static function is_tickera_screen() {
			// Query-string post type. Core Tickera CPTs are listed explicitly, but
			// any Tickera/add-on post type follows the `tc_` convention (e.g.
			// tc_speakers and its taxonomy screens), so accept those too.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
			if ( isset( $_GET['post_type'] ) ) {
				$pt = sanitize_key( wp_unslash( $_GET['post_type'] ) );
				if ( in_array( $pt, self::$cpts, true ) || 0 === strpos( $pt, 'tc_' ) ) {
					return true;
				}
			}

			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				if ( $screen && ! empty( $screen->post_type )
					&& ( in_array( $screen->post_type, self::$cpts, true ) || 0 === strpos( $screen->post_type, 'tc_' ) ) ) {
					return true;
				}
			}

			// Tickera pages that don't live under the CPT menu (e.g. the
			// onboarding wizard, the Ticket Designer, and stand-alone add-on
			// screens such as Checkinera). All Tickera/add-on admin pages follow
			// the `tc_` / `tc-` page-slug convention, so style any of them.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			$standalone_pages = array( 'tc-installation-wizard' );
			if ( $page && in_array( $page, apply_filters( 'tc_admin_2026_standalone_pages', $standalone_pages ), true ) ) {
				return true;
			}
			if ( $page && ( 0 === strpos( $page, 'tc_' ) || 0 === strpos( $page, 'tc-' ) ) ) {
				return true;
			}

			return apply_filters( 'tc_admin_2026_is_tickera_screen', false );
		}

		/**
		 * Add the scoping body class on Tickera screens.
		 *
		 * @param string $classes Space-separated class list.
		 * @return string
		 */
		public static function body_class( $classes ) {
			if ( self::is_tickera_screen() ) {
				$classes .= ' tc-admin-2026';
			}
			return $classes;
		}

		/**
		 * Enqueue the 2026 design-system stylesheet on Tickera screens.
		 */
		public static function enqueue() {
			if ( ! self::is_tickera_screen() ) {
				return;
			}
			$url  = plugin_dir_url( __FILE__ ) . 'assets/tickera-admin-2026.css';
			$path = plugin_dir_path( __FILE__ ) . 'assets/tickera-admin-2026.css';
			$ver  = file_exists( $path ) ? filemtime( $path ) : '1.0.0';
			wp_enqueue_style( 'tc-admin-2026', $url, array(), $ver );

			$js_url  = plugin_dir_url( __FILE__ ) . 'assets/tickera-admin-2026.js';
			$js_path = plugin_dir_path( __FILE__ ) . 'assets/tickera-admin-2026.js';
			$js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : '1.0.0';
			wp_enqueue_script( 'tc-admin-2026', $js_url, array(), $js_ver, true );
		}
	}

	TC_Admin_2026::init();
}
