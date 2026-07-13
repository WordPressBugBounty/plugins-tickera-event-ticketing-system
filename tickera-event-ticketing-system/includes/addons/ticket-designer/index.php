<?php
/**
 * Ticket Designer addon bootstrap.
 *
 * Ported from the Venuera "Ticket Designer" module as a bundled internal
 * Tickera module. This file defines the module constants and loads the main
 * addon class (which self-initializes at the bottom of its own file).
 *
 * @package Tickera
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Module version.
if ( ! defined( 'TC_TICKET_DESIGNER_VERSION' ) ) {
	define( 'TC_TICKET_DESIGNER_VERSION', '1.0.1' );
}

// Module directory / URL (this addon folder).
if ( ! defined( 'TC_TICKET_DESIGNER_DIR' ) ) {
	define( 'TC_TICKET_DESIGNER_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'TC_TICKET_DESIGNER_URL' ) ) {
	define( 'TC_TICKET_DESIGNER_URL', plugin_dir_url( __FILE__ ) );
}

// Parent Tickera plugin directory / URL.
// This file lives at <tickera>/includes/addons/ticket-designer/index.php,
// so the parent plugin root is four levels up.
if ( ! defined( 'TC_TICKET_DESIGNER_PARENT_DIR' ) ) {
	define( 'TC_TICKET_DESIGNER_PARENT_DIR', trailingslashit( dirname( __FILE__, 4 ) ) );
}
if ( ! defined( 'TC_TICKET_DESIGNER_PARENT_URL' ) ) {
	define( 'TC_TICKET_DESIGNER_PARENT_URL', plugins_url( '/', TC_TICKET_DESIGNER_PARENT_DIR . 'tickera.php' ) );
}

/**
 * Ensure a global `TCPDF` class is available for the designer module.
 *
 * The module was written against a Composer-installed TCPDF (global namespace).
 * Tickera instead bundles TCPDF under the `\Tickera\` namespace and loads it
 * lazily. This helper loads Tickera's bundled TCPDF (exactly the way the core
 * ticket-template renderer does) and aliases the namespaced classes to the
 * global names the module expects, so we reuse the existing TCPDF — no Composer
 * needed.
 *
 * @return bool True when a global TCPDF class is available.
 */
if ( ! function_exists( 'tickera_ticket_designer_ensure_tcpdf' ) ) {
	function tickera_ticket_designer_ensure_tcpdf() {

		global $tc;

		// Load Tickera's bundled TCPDF if it isn't loaded yet (same call the core
		// renderer uses in class.ticket_templates.php). Note: we deliberately do
		// NOT early-return when the global TCPDF already exists, because the font
		// helper classes (TCPDF_FONTS, …) still need aliasing below.
		if ( ! class_exists( '\Tickera\TCPDF', false ) && ! class_exists( 'TCPDF', false ) && isset( $tc ) && ! empty( $tc->plugin_dir ) ) {
			$inc = $tc->plugin_dir . 'includes/tcpdf/vendor/examples/tcpdf_include.php';
			if ( file_exists( $inc ) ) {
				require_once $inc;
			}
		}

		// Alias the namespaced classes to the global names the module references.
		// TCPDF_FONTS is required so the PDF generator can embed the bundled TTF
		// fonts via addTTFfont; without it, class_exists('TCPDF_FONTS') is false,
		// every custom font silently falls back to a core PDF font, and the PDF
		// no longer matches the fonts shown in the on-canvas editor.
		foreach ( array( 'TCPDF', 'TCPDFBarcode', 'TCPDF2DBarcode', 'TCPDF_FONTS' ) as $cls ) {
			if ( class_exists( '\\Tickera\\' . $cls, false ) && ! class_exists( $cls, false ) ) {
				class_alias( '\\Tickera\\' . $cls, $cls );
			}
		}

		return class_exists( 'TCPDF', false );
	}
}

// Load the main addon class (filename unchanged; the class inside is renamed).
// The class self-initializes via tc_ticket_designer() at the bottom of the file.
require_once __DIR__ . '/class-venuera-ticket-designer.php';
