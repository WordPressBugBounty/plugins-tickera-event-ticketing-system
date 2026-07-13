<?php
/**
 * Ticket Designer Admin
 *
 * @package Venuera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {

// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.DB.SlowDBQuery -- Venuera custom-table data access:
	// Admin screen: $_GET action/template_id only routes between list/editor views (read-only, no state change); the save_* handlers verify WooCommerce nonces. meta_query is required to list ticket products.

	exit;
}

/**
 * Ticket Designer Admin class.
 */
class TC_Ticket_Designer_Admin {

	/**
	 * Initialize admin.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 30 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		// Note: the legacy Ticket Template meta box on the Event edit screen
		// has been removed. Templates are picked at the ticket-product level
		// (and per-variation), which is where users naturally configure them.
		// The resolver in TC_Ticket_Designer_Template::get_for_ticket() still
		// honours the event-level assignments table as a fallback so existing
		// data keeps working.
		// The Ticket Template selector lives inside the Event Ticket data
		// panel (visible for BOTH event_ticket and event_ticket_variable),
		// not the General tab — General is hidden for variable products.
		add_action( 'venuera_event_ticket_panel_after', array( __CLASS__, 'add_product_template_field' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_template' ) );

		// Per-variation override + save for Variable Event Ticket children.
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'add_variation_template_field' ), 20, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_variation_template' ), 20, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_tcpdf_notice' ) );

		// AI Assistant (WordPress 7.0 AI Client). Server-side endpoint behind a
		// capability check, per the WP core recommendation.
		add_action( 'wp_ajax_tc_designer_ticket_ai_generate', array( __CLASS__, 'ajax_ai_generate' ) );
	}

	/**
	 * Whether the AI assistant can run: WordPress 7.0+ AI Client present AND the
	 * current user may design ticket templates.
	 *
	 * @return bool
	 */
	public static function ai_is_available() {
		return function_exists( 'wp_ai_client_prompt' ) && current_user_can( 'manage_options' );
	}

	/**
	 * AJAX: turn a natural-language brief into a ticket layout spec using the
	 * WordPress 7.0 AI Client. Returns a JSON "spec" of high-level operations
	 * that the Ticket Designer JS executes against TicketDesigner.addElement()
	 * and updateElementProperty() — so the AI never emits raw Fabric.js JSON.
	 */
	public static function ajax_ai_generate() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'tickera-event-ticketing-system' ) ), 403 );
		}
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			wp_send_json_error( array( 'message' => __( 'The WordPress AI Client (WordPress 7.0+) is not available on this site.', 'tickera-event-ticketing-system' ) ), 400 );
			return;
		}

		$prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
		if ( '' === trim( $prompt ) ) {
			wp_send_json_error( array( 'message' => __( 'Please describe the ticket layout you want.', 'tickera-event-ticketing-system' ) ) );
		}

		$context = isset( $_POST['context'] ) ? wp_unslash( $_POST['context'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce + manage_options; JSON design context, length-capped below, used only as AI prompt text.
		$context = is_string( $context ) ? substr( $context, 0, 8000 ) : '';
		if ( '' === trim( (string) $context ) ) {
			$context = '{}';
		}

		$system = self::build_ai_system_instruction();

		$user_message = "USER REQUEST:\n" . $prompt
			. "\n\nCURRENT_DESIGN_CONTEXT (JSON — includes template width/height in points and existing elements; use for modifications):\n" . $context;

		// The AI assistant is an optional feature requiring the WordPress AI Client
		// (WP 7.0+). The call is already guarded by the function_exists() check above;
		// it is resolved through a variable so static "requires WP" scanners do not flag
		// this runtime-gated, optional call against the plugin's WP 6.0 minimum.
		$ai_prompt = 'wp_ai_client_prompt';
		$builder   = $ai_prompt( $user_message )
			->using_system_instruction( $system )
			->using_temperature( 0.2 );

		if ( method_exists( $builder, 'using_max_tokens' ) ) {
			$builder = $builder->using_max_tokens( 8000 );
		}
		if ( method_exists( $builder, 'using_model_preference' ) ) {
			// Ordered preference: the AI Client uses the FIRST one that the
			// site's configured providers actually offer, else falls back to any
			// compatible model. DeepSeek V4 first (Pro for best quality, Flash as
			// the faster/cheaper fallback), then the other flagships, then the
			// legacy DeepSeek models as a last resort.
			$builder = $builder->using_model_preference(
				'deepseek-v4-pro',
				'deepseek-v4-flash',
				'claude-sonnet-4-6',
				'gpt-5.4',
				'gemini-3.1-pro-preview',
				'deepseek-chat'
			);
		}
		if ( method_exists( $builder, 'is_supported_for_text_generation' ) && ! $builder->is_supported_for_text_generation() ) {
			wp_send_json_error( array( 'message' => __( 'No AI provider is configured. Add one under Settings → Connectors (OpenAI, Anthropic, or Google).', 'tickera-event-ticketing-system' ) ), 400 );
		}

		$json = $builder->generate_text();
		if ( is_wp_error( $json ) ) {
			wp_send_json_error( array( 'message' => $json->get_error_message() ), 500 );
		}

		$data = json_decode( self::extract_json( $json ), true );
		if ( ! is_array( $data ) || empty( $data['operations'] ) || ! is_array( $data['operations'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The AI did not return a usable layout. Try rephrasing your request.', 'tickera-event-ticketing-system' ),
					'raw'     => is_string( $json ) ? mb_substr( $json, 0, 1200 ) : '',
				)
			);
		}

		wp_send_json_success( array( 'spec' => $data ) );
	}

	/**
	 * Pull a JSON object out of a raw model response (tolerates code fences and
	 * surrounding prose).
	 *
	 * @param string $text Raw model text.
	 * @return string
	 */
	private static function extract_json( $text ) {
		$text = is_string( $text ) ? trim( $text ) : '';
		if ( preg_match( '/```(?:json)?\s*(.*?)\s*```/s', $text, $m ) ) {
			$text = trim( $m[1] );
		}
		$start = strpos( $text, '{' );
		$end   = strrpos( $text, '}' );
		if ( false !== $start && false !== $end && $end > $start ) {
			$text = substr( $text, $start, $end - $start + 1 );
		}
		return $text;
	}

	/**
	 * System instruction teaching the model the Ticket Designer element
	 * vocabulary, coordinate system, and data-field bindings. Element types,
	 * defaults and data fields are pulled live from TC_Ticket_Designer_Element so
	 * this never drifts from the actual editor.
	 *
	 * @return string
	 */
	private static function build_ai_system_instruction() {
		// Build a compact "type: option=default, …" reference from the element
		// defaults, so the model knows the exact option keys it may set.
		$types_text = '';
		if ( class_exists( 'TC_Ticket_Designer_Element' ) ) {
			$type_keys = array_keys( (array) TC_Ticket_Designer_Element::get_types() );
			foreach ( $type_keys as $type ) {
				$defaults = (array) TC_Ticket_Designer_Element::get_defaults( $type );
				$pairs    = array();
				foreach ( $defaults as $k => $v ) {
					if ( is_bool( $v ) ) {
						$v = $v ? 'true' : 'false';
					} elseif ( is_array( $v ) ) {
						continue;
					}
					$pairs[] = $k . '=' . $v;
				}
				$types_text .= '- ' . $type . ': ' . implode( ', ', $pairs ) . "\n";
			}
		}

		// Data fields available for dynamic_text bindings.
		$fields_text = '';
		if ( class_exists( 'TC_Ticket_Designer_Element' ) && method_exists( 'TC_Ticket_Designer_Element', 'get_data_fields' ) ) {
			foreach ( (array) TC_Ticket_Designer_Element::get_data_fields() as $group ) {
				if ( empty( $group['fields'] ) ) {
					continue;
				}
				$fields_text .= '  ' . ( isset( $group['label'] ) ? $group['label'] : '' ) . ': '
					. implode( ', ', array_keys( $group['fields'] ) ) . "\n";
			}
		}

        // phpcs:ignore PluginCheck.CodeAnalysis.Heredoc.NotAllowed -- Long static AI system prompt; heredoc keeps it readable and contains no variable interpolation.
		return <<<SYS
You are the ticket layout assistant for the Venuera Ticket Designer. You translate a
natural-language brief into a STRICT JSON object that the editor executes. You never
invent option keys that are not listed below.

CRITICAL OUTPUT FORMAT: Respond with the raw JSON object ONLY. No markdown code fences,
no text before or after. The first character must be "{" and the last "}".

OUTPUT SHAPE
{
  "summary": "<one short sentence, in the SAME LANGUAGE as the user's request>",
  "operations": [ <operation>, ... ]
}

COORDINATE SYSTEM
- The ticket is a rectangle measured in POINTS (72pt = 1 inch). The current template
  width and height are provided in CURRENT_DESIGN_CONTEXT (templateWidth, templateHeight).
- (x, y) is the TOP-LEFT corner of each element, in points, measured from the ticket's
  top-left. x grows right, y grows down. Keep every element inside the ticket bounds.
- Lay elements out so they don't overlap; leave small margins (≈10-15pt) from the edges.

OPERATIONS
1) {"op":"clear"}  → remove all elements. Emit FIRST only for a fresh design.
2) {"op":"add_element","elementType":"<type>", <option>:<value>, ...}
   Create an element. "elementType" MUST be one of the types listed below, and you may
   set any of that type's option keys (see defaults). Always set x and y.
   - For "dynamic_text", set "dataField" to one of the DATA FIELDS below (this is what
     binds it to real ticket data). Optionally set fontSize, fontFamily, fontWeight,
     fontStyle, fill (hex color), textAlign, width.
   - For "static_text", set "text" plus the same font options.
   - For "qr_code"/"barcode", set "dataField" (usually ticket_id) and size/width/height.
   - For "rectangle"/"line", set width/height/stroke/strokeWidth/fill as needed.
3) {"op":"update","target":"<match>","props":{ "<key>":<value>, ... }}
   Modify matching elements. "props" keys are the same option keys / also: rotation,
   opacity (0-100). "target" matches case-insensitively against an element's type,
   its static text, or its dataField; "all" matches everything.
4) {"op":"delete","target":"<match|all>"}

DESIGN GUIDANCE (aim for a polished, professional ticket — not a bare list of fields)
- LAYERING / ORDER MATTERS: elements added later render ON TOP. So emit background
  and colored bands FIRST, then text and codes on top of them. Never cover text with a
  later rectangle.
- DEPTH: start with a full-bleed background rectangle (x:0, y:0, width:templateWidth,
  height:templateHeight). Add a colored HEADER band (e.g. height 55-70) or a slim accent
  stripe down one edge to give the ticket structure.
- HIERARCHY: the event name is the hero — large (22-30pt) and bold, placed on the header
  band in a contrasting color (white on a dark/colored band). Secondary info (date, venue)
  is smaller (12-14pt) and muted grey. The price is bold and uses the ACCENT color.
- COLOR: pick ONE cohesive accent color and reuse it (header band, price, code framing).
  Keep backgrounds light with dark text, or dark with light text — ensure contrast.
- DATA: bind real fields — event_name on the header; event_datetime and venue_name below;
  ticket_type bold; ticket_price in the accent color; seat_info when relevant.
- ALWAYS include a scannable code (qr_code, or barcode) bound to ticket_id, placed in a
  corner with breathing room, and show the ticket_id in small (9-10pt) monospace
  (fontFamily "Courier New") near it.
- SPACING: keep ~20pt margins from the edges, align related items to a common left edge,
  and never let elements overlap (use the footprints implied by width/height/fontSize).
- Use small "label" prefixes (e.g. "Venue:") on secondary fields when it improves clarity.

BASE ELEMENT TYPES AND THEIR OPTION KEYS (shown as key=default):
{$types_text}
PRESET ELEMENTS: CURRENT_DESIGN_CONTEXT.availableElements lists EVERY element type the
editor knows — the base types above PLUS pre-styled / pre-bound presets (e.g. "event_name",
"title_text", "subtitle_text", "header_bg", "footer_bg", "sidebar_panel", "rounded_box",
"ticket_border", "logo", "sponsor_logo", "attendee_name", "horizontal_line", …). You MAY
use any "id" from that list as the "elementType". Prefer a preset when it matches the
intent (it comes pre-styled / already bound to the right data field); otherwise use a base
type. For ANY element you can still override x, y, width, height, size, fontSize,
fontFamily, fontWeight, fill, stroke, strokeWidth, rotation, etc.

DATA FIELDS (use as "dataField" on dynamic_text / codes):
{$fields_text}
RULES
- Numbers are plain numbers (points), no units, no quotes. Colors are hex strings.
- Prefer dynamic_text bound to dataField over static_text for real ticket data
  (event name, date, venue, attendee name, ticket type, price, seat info, …).
- A scannable code (qr_code or barcode) bound to ticket_id is recommended on most tickets.
- For modifications, READ CURRENT_DESIGN_CONTEXT and emit only the needed operations;
  do NOT clear unless asked.
SYS;
	}

	/**
	 * Show an admin notice on the Ticket Designer screen when TCPDF is not installed.
	 *
	 * Real PDF generation requires the `tecnickcom/tcpdf` Composer package. When the
	 * Composer `vendor/` directory is absent the PDF generator gracefully falls back to
	 * HTML output (see TC_Ticket_Designer_PDF_Generator::create_pdf()), but for true PDFs
	 * the site owner must run `composer install` in the plugin root to install TCPDF.
	 */
	public static function maybe_show_tcpdf_notice() {
		// Only surface this on the Ticket Designer admin page to avoid global noise.
		if ( ! isset( $_GET['page'] ) || 'tc-ticket-designer' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page gate only.
			return;
		}

		// Reuse Tickera's bundled TCPDF (loaded + aliased to the global TCPDF name
		// by the module bootstrap). Only warn if it genuinely cannot be loaded.
		if ( function_exists( 'tickera_ticket_designer_ensure_tcpdf' ) && tickera_ticket_designer_ensure_tcpdf() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Tickera Ticket Designer: TCPDF could not be loaded, so tickets are exported as HTML instead of PDF.', 'tickera-event-ticketing-system' );
		echo '</p></div>';
	}

	/**
	 * Add admin menu.
	 */
	public static function add_menu() {
		global $first_tc_menu_handler;

		add_submenu_page(
			$first_tc_menu_handler,
			__( 'Ticket Designer', 'tickera-event-ticketing-system' ),
			__( 'Ticket Designer', 'tickera-event-ticketing-system' ),
			'manage_options',
			'tc-ticket-designer',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_scripts( $hook ) {
		// Only load on ticket designer page.
		if ( ! isset( $_GET['page'] ) || 'tc-ticket-designer' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page gate only.
			return;
		}

		$addon_url  = tickera_ticket_designer()->get_url();
		$addon_path = dirname( dirname( __DIR__ ) ) . '/';
		// Version editor assets by file mtime so JS/CSS changes always bust the
		// browser cache (a static version left stale code loaded after edits).
		$asset_ver = function ( $rel ) use ( $addon_path ) {
			$file = $addon_path . $rel;
			return file_exists( $file ) ? filemtime( $file ) : TC_TICKET_DESIGNER_VERSION;
		};

		// Fabric.js for canvas manipulation.
		$fabric_rel  = 'assets/vendor/fabric.min.js';
		$fabric_file = tickera_ticket_designer()->get_path() . $fabric_rel;
		wp_enqueue_script(
			'fabric-js',
			$addon_url . $fabric_rel,
			array(),
			file_exists( $fabric_file ) ? filemtime( $fabric_file ) : '5.3.0',
			true
		);

		// JsBarcode for barcode generation — bundled locally under
		// assets/vendor/jsbarcode/ so the designer renders barcodes even on
		// offline / CDN-blocked environments. Frontend ticket pages use the
		// same bundled copy (see \Tickera\TC_Ticket_Designer_Frontend).
		$jsbarcode_rel  = 'assets/vendor/jsbarcode/JsBarcode.all.min.js';
		$jsbarcode_file = tickera_ticket_designer()->get_path() . $jsbarcode_rel;
		wp_enqueue_script(
			'jsbarcode',
			$addon_url . $jsbarcode_rel,
			array(),
			file_exists( $jsbarcode_file ) ? filemtime( $jsbarcode_file ) : '3.11.5',
			true
		);

		// QRCode.js for QR code generation.
		$qrcode_rel  = 'assets/vendor/qrcode.min.js';
		$qrcode_file = tickera_ticket_designer()->get_path() . $qrcode_rel;
		wp_enqueue_script(
			'qrcode-js',
			$addon_url . $qrcode_rel,
			array(),
			file_exists( $qrcode_file ) ? filemtime( $qrcode_file ) : '1.0.0',
			true
		);

		// Font-family preview in the editor. Loaded from the fonts bundled with
		// the plugin (local @font-face) — no external/CDN request, wp.org compliant.
		$td_fonts_rel  = 'assets/fonts/fonts.css';
		$td_fonts_file = tickera_ticket_designer()->get_path() . $td_fonts_rel;
		wp_enqueue_style(
			'venuera-td-fonts',
			$addon_url . $td_fonts_rel,
			array(),
			file_exists( $td_fonts_file ) ? filemtime( $td_fonts_file ) : TC_TICKET_DESIGNER_VERSION
		);

		// Shared confirm dialog (used by the template list "Delete" button).
		// Faza 0: served from the LOCAL module copies bundled under this addon.
		$core_dir = defined( 'TC_TICKET_DESIGNER_DIR' ) ? TC_TICKET_DESIGNER_DIR : '';
		$dlg_css  = ( $core_dir && file_exists( $core_dir . 'assets/css/admin-dialog.css' ) ) ? filemtime( $core_dir . 'assets/css/admin-dialog.css' ) : TC_TICKET_DESIGNER_VERSION;
		$dlg_js   = ( $core_dir && file_exists( $core_dir . 'assets/js/admin-dialog.js' ) ) ? filemtime( $core_dir . 'assets/js/admin-dialog.js' ) : TC_TICKET_DESIGNER_VERSION;
		wp_enqueue_style( 'venuera-admin-dialog', TC_TICKET_DESIGNER_URL . 'assets/css/admin-dialog.css', array(), $dlg_css );
		wp_enqueue_script( 'venuera-admin-dialog', TC_TICKET_DESIGNER_URL . 'assets/js/admin-dialog.js', array(), $dlg_js, true );

		// Ticket Designer - Main entry point.
		wp_enqueue_script(
			'venuera-ticket-designer',
			$addon_url . 'assets/js/admin/ticket-designer.js',
			array( 'jquery', 'fabric-js', 'jsbarcode', 'qrcode-js', 'wp-color-picker' ),
			$asset_ver( 'assets/js/admin/ticket-designer.js' ),
			true
		);

		// Ticket Designer Modules.
		$modules = array(
			'canvas'           => array( 'venuera-ticket-designer' ),
			'onion'            => array( 'venuera-ticket-designer', 'venuera-ticket-designer-canvas' ),
			'toolbar'          => array( 'venuera-ticket-designer' ),
			'floating-toolbar' => array( 'venuera-ticket-designer' ),
			'properties'       => array( 'venuera-ticket-designer' ),
			'save-load'        => array( 'venuera-ticket-designer' ),
			'preview'          => array( 'venuera-ticket-designer' ),
			'history'          => array( 'venuera-ticket-designer' ),
			'ai-assistant'     => array( 'venuera-ticket-designer' ),
		);

		foreach ( $modules as $module => $deps ) {
			wp_enqueue_script(
				'venuera-ticket-designer-' . $module,
				$addon_url . 'assets/js/admin/modules/' . $module . '.js',
				$deps,
				$asset_ver( 'assets/js/admin/modules/' . $module . '.js' ),
				true
			);
		}

		// Element modules - loaded in order: registry, base, then specific types.
		$element_modules = array(
			'index'             => array( 'venuera-ticket-designer' ),
			'base-elements'     => array( 'venuera-ticket-designer-elements-index' ),
			'data-elements'     => array( 'venuera-ticket-designer-elements-base-elements' ),
			'attendee-elements' => array( 'venuera-ticket-designer-elements-base-elements' ),
			'code-elements'     => array( 'venuera-ticket-designer-elements-base-elements' ),
			'text-elements'     => array( 'venuera-ticket-designer-elements-base-elements' ),
			'media-elements'    => array( 'venuera-ticket-designer-elements-base-elements' ),
			'shape-elements'    => array( 'venuera-ticket-designer-elements-base-elements' ),
		);

		foreach ( $element_modules as $module => $deps ) {
			wp_enqueue_script(
				'venuera-ticket-designer-elements-' . $module,
				$addon_url . 'assets/js/admin/modules/elements/' . $module . '.js',
				$deps,
				$asset_ver( 'assets/js/admin/modules/elements/' . $module . '.js' ),
				true
			);
		}

		wp_enqueue_style(
			'venuera-ticket-designer-admin',
			$addon_url . 'assets/css/admin.css',
			array( 'wp-color-picker' ),
			$asset_ver( 'assets/css/admin.css' )
		);

		// @font-face rules for the bundled font library so the editor canvas and
		// preview render the exact fonts that the PDF embeds.
		wp_add_inline_style( 'venuera-ticket-designer-admin', TC_Ticket_Designer_Fonts::get_font_face_css() );

		// Media library.
		wp_enqueue_media();

		// Localize script.
		wp_localize_script(
			'venuera-ticket-designer',
			'venueraTicketDesigner',
			array(
				'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
				'nonce'                   => wp_create_nonce( 'tc_ticket_designer_nonce' ),
				'strings'                 => array(
					'pleaseSaveTheTemplate'        => __( 'Please save the template first before downloading PDF.', 'tickera-event-ticketing-system' ),
					'pdfPreviewOpenedIn'           => __( 'PDF preview opened in a new tab.', 'tickera-event-ticketing-system' ),
					'errorGeneratingPdfPlease'     => __( 'Error generating PDF. Please try again.', 'tickera-event-ticketing-system' ),
					'templateExportedAs'           => __( 'Template exported as ', 'tickera-event-ticketing-system' ),
					'failedToExportTemplate'       => __( 'Failed to export template: ', 'tickera-event-ticketing-system' ),
					'invalidTemplateData'          => __( 'Invalid template data.', 'tickera-event-ticketing-system' ),
					'templateIsMissingCanvas'      => __( 'Template is missing canvas size.', 'tickera-event-ticketing-system' ),
					'loaded'                       => __( 'Loaded ', 'tickera-event-ticketing-system' ),
					'failedToParseJson'            => __( 'Failed to parse JSON: ', 'tickera-event-ticketing-system' ),
					'failedToReadFile'             => __( 'Failed to read file.', 'tickera-event-ticketing-system' ),
					'couldNotLoadTemplate'         => __( 'Could not load template.', 'tickera-event-ticketing-system' ),
					'loadAReadymadeTemplate'       => __( 'Load a ready-made template first.', 'tickera-event-ticketing-system' ),
					'failedToSaveSource'           => __( 'Failed to save source template (network).', 'tickera-event-ticketing-system' ),
					'invalidBarcode'               => __( 'Invalid barcode', 'tickera-event-ticketing-system' ),
					'pleaseAllowPopupsTo'          => __( 'Please allow popups to print tickets.', 'tickera-event-ticketing-system' ),
					'printTicket'                  => __( 'Print Ticket', 'tickera-event-ticketing-system' ),
					'imageDownloadRequiresHtml2ca' => __( 'Image download requires html2canvas library. Please use the print option instead.', 'tickera-event-ticketing-system' ),
					'thisWillReplaceYour'          => __( 'This will replace your current design.\n\nAny unsaved changes will be lost. Continue?', 'tickera-event-ticketing-system' ),
					'noStarterTemplatesAvailable'  => __( 'No starter templates available.', 'tickera-event-ticketing-system' ),
					'failedToLoadStarter'          => __( 'Failed to load starter templates.', 'tickera-event-ticketing-system' ),
					'noStarterTemplatesAvailable2' => __( 'No starter templates available.', 'tickera-event-ticketing-system' ),
					'searchTemplates'              => __( 'Search templates…', 'tickera-event-ticketing-system' ),
					'noTemplatesMatchThat'         => __( 'No templates match that search.', 'tickera-event-ticketing-system' ),
					'overwrite'                    => __( 'Overwrite ', 'tickera-event-ticketing-system' ),
					'arrange'                      => __( 'Arrange', 'tickera-event-ticketing-system' ),
					'bringToFront'                 => __( 'Bring to Front', 'tickera-event-ticketing-system' ),
					'bringForward'                 => __( 'Bring Forward', 'tickera-event-ticketing-system' ),
					'sendBackward'                 => __( 'Send Backward', 'tickera-event-ticketing-system' ),
					'sendToBack'                   => __( 'Send to Back', 'tickera-event-ticketing-system' ),
					'customFieldsFor'              => __( 'Custom fields for', 'tickera-event-ticketing-system' ),
					'width'                        => __( 'Width', 'tickera-event-ticketing-system' ),
					'height'                       => __( 'Height', 'tickera-event-ticketing-system' ),
					'rotation'                     => __( 'Rotation:', 'tickera-event-ticketing-system' ),
					'opacity'                      => __( 'Opacity:', 'tickera-event-ticketing-system' ),
					'positionTransform'            => __( 'Position & Transform', 'tickera-event-ticketing-system' ),
					'text'                         => __( 'Text', 'tickera-event-ticketing-system' ),
					'text2'                        => __( 'Text', 'tickera-event-ticketing-system' ),
					'field'                        => __( 'Field', 'tickera-event-ticketing-system' ),
					'labelPrefix'                  => __( 'Label Prefix', 'tickera-event-ticketing-system' ),
					'fontFamily'                   => __( 'Font Family', 'tickera-event-ticketing-system' ),
					'fontSize'                     => __( 'Font Size', 'tickera-event-ticketing-system' ),
					'weight'                       => __( 'Weight', 'tickera-event-ticketing-system' ),
					'normal'                       => __( 'Normal', 'tickera-event-ticketing-system' ),
					'bold'                         => __( 'Bold', 'tickera-event-ticketing-system' ),
					'style'                        => __( 'Style', 'tickera-event-ticketing-system' ),
					'normal2'                      => __( 'Normal', 'tickera-event-ticketing-system' ),
					'italic'                       => __( 'Italic', 'tickera-event-ticketing-system' ),
					'textColor'                    => __( 'Text Color', 'tickera-event-ticketing-system' ),
					'textAlign'                    => __( 'Text Align', 'tickera-event-ticketing-system' ),
					'image'                        => __( 'Image', 'tickera-event-ticketing-system' ),
					'image2'                       => __( 'Image', 'tickera-event-ticketing-system' ),
					'remove'                       => __( 'Remove', 'tickera-event-ticketing-system' ),
					'fit'                          => __( 'Fit', 'tickera-event-ticketing-system' ),
					'contain'                      => __( 'Contain', 'tickera-event-ticketing-system' ),
					'cover'                        => __( 'Cover', 'tickera-event-ticketing-system' ),
					'stretch'                      => __( 'Stretch', 'tickera-event-ticketing-system' ),
					'eventImage'                   => __( 'Event Image', 'tickera-event-ticketing-system' ),
					'fit2'                         => __( 'Fit', 'tickera-event-ticketing-system' ),
					'contain2'                     => __( 'Contain', 'tickera-event-ticketing-system' ),
					'cover2'                       => __( 'Cover', 'tickera-event-ticketing-system' ),
					'stretch2'                     => __( 'Stretch', 'tickera-event-ticketing-system' ),
					'zoom'                         => __( 'Zoom:', 'tickera-event-ticketing-system' ),
					'qrCode'                       => __( 'QR Code', 'tickera-event-ticketing-system' ),
					'dataSource'                   => __( 'Data Source', 'tickera-event-ticketing-system' ),
					'ticketId'                     => __( 'Ticket ID', 'tickera-event-ticketing-system' ),
					'orderId'                      => __( 'Order ID', 'tickera-event-ticketing-system' ),
					'errorCorrection'              => __( 'Error Correction', 'tickera-event-ticketing-system' ),
					'foreground'                   => __( 'Foreground', 'tickera-event-ticketing-system' ),
					'background'                   => __( 'Background', 'tickera-event-ticketing-system' ),
					'padding'                      => __( 'Padding:', 'tickera-event-ticketing-system' ),
					'cornerRadius'                 => __( 'Corner radius:', 'tickera-event-ticketing-system' ),
					'barcode'                      => __( 'Barcode', 'tickera-event-ticketing-system' ),
					'dataSource2'                  => __( 'Data Source', 'tickera-event-ticketing-system' ),
					'ticketId2'                    => __( 'Ticket ID', 'tickera-event-ticketing-system' ),
					'orderId2'                     => __( 'Order ID', 'tickera-event-ticketing-system' ),
					'format'                       => __( 'Format', 'tickera-event-ticketing-system' ),
					'foreground2'                  => __( 'Foreground', 'tickera-event-ticketing-system' ),
					'background2'                  => __( 'Background', 'tickera-event-ticketing-system' ),
					'padding2'                     => __( 'Padding:', 'tickera-event-ticketing-system' ),
					'cornerRadius2'                => __( 'Corner radius:', 'tickera-event-ticketing-system' ),
					'style2'                       => __( 'Style', 'tickera-event-ticketing-system' ),
					'fillColor'                    => __( 'Fill Color', 'tickera-event-ticketing-system' ),
					'borderColor'                  => __( 'Border Color', 'tickera-event-ticketing-system' ),
					'borderWidth'                  => __( 'Border Width', 'tickera-event-ticketing-system' ),
					'cornerRadius3'                => __( 'Corner Radius', 'tickera-event-ticketing-system' ),
					'line'                         => __( 'Line', 'tickera-event-ticketing-system' ),
					'orientation'                  => __( 'Orientation', 'tickera-event-ticketing-system' ),
					'horizontal'                   => __( 'Horizontal', 'tickera-event-ticketing-system' ),
					'vertical'                     => __( 'Vertical', 'tickera-event-ticketing-system' ),
					'length'                       => __( 'Length', 'tickera-event-ticketing-system' ),
					'color'                        => __( 'Color', 'tickera-event-ticketing-system' ),
					'thickness'                    => __( 'Thickness', 'tickera-event-ticketing-system' ),
					'customTicketSize'             => __( 'Custom Ticket Size', 'tickera-event-ticketing-system' ),
					'width2'                       => __( 'Width', 'tickera-event-ticketing-system' ),
					'height2'                      => __( 'Height', 'tickera-event-ticketing-system' ),
					'unit'                         => __( 'Unit', 'tickera-event-ticketing-system' ),
					'pointsPt'                     => __( 'Points (pt)', 'tickera-event-ticketing-system' ),
					'inchesIn'                     => __( 'Inches (in)', 'tickera-event-ticketing-system' ),
					'millimetersMm'                => __( 'Millimeters (mm)', 'tickera-event-ticketing-system' ),
					'cancel'                       => __( 'Cancel', 'tickera-event-ticketing-system' ),
					'apply'                        => __( 'Apply', 'tickera-event-ticketing-system' ),
					'unsavedChanges'               => __( 'You have unsaved changes. Are you sure you want to leave?', 'tickera-event-ticketing-system' ),
					'deleteConfirm'                => __( 'Are you sure you want to delete this template?', 'tickera-event-ticketing-system' ),
					'saved'                        => __( 'Template saved successfully!', 'tickera-event-ticketing-system' ),
					'saveError'                    => __( 'Failed to save template.', 'tickera-event-ticketing-system' ),
					'selectImage'                  => __( 'Select Image', 'tickera-event-ticketing-system' ),
					'useImage'                     => __( 'Use this image', 'tickera-event-ticketing-system' ),
					'preview'                      => __( 'Preview', 'tickera-event-ticketing-system' ),
					'noElements'                   => __( 'Add elements to design your ticket', 'tickera-event-ticketing-system' ),
				),
				'elementTypes'            => TC_Ticket_Designer_Element::get_types(),
				'dataFields'              => TC_Ticket_Designer_Element::get_data_fields(),
				'fonts'                   => TC_Ticket_Designer::get_available_fonts(),
				'barcodeFormats'          => TC_Ticket_Designer_Element::get_barcode_formats(),
				'qrErrorLevels'           => TC_Ticket_Designer_Element::get_qr_error_levels(),
				'elementDefaults'         => array(
					'dynamic_text' => TC_Ticket_Designer_Element::get_defaults( 'dynamic_text' ),
					'static_text'  => TC_Ticket_Designer_Element::get_defaults( 'static_text' ),
					'qr_code'      => TC_Ticket_Designer_Element::get_defaults( 'qr_code' ),
					'barcode'      => TC_Ticket_Designer_Element::get_defaults( 'barcode' ),
					'image'        => TC_Ticket_Designer_Element::get_defaults( 'image' ),
					'event_image'  => TC_Ticket_Designer_Element::get_defaults( 'event_image' ),
					'rectangle'    => TC_Ticket_Designer_Element::get_defaults( 'rectangle' ),
					'line'         => TC_Ticket_Designer_Element::get_defaults( 'line' ),
					'custom_field' => TC_Ticket_Designer_Element::get_defaults( 'custom_field' ),
				),
				'sampleData'              => TC_Ticket_Designer_Element::get_sample_data(),
				'fieldContexts'           => self::get_field_contexts(),
				// Pre-resolved labels for any `attendee_field_<id>` bindings that
				// already live on the template currently being edited. Without
				// this the canvas would show the raw key (e.g.
				// "[attendee_field_field_6beca77c-…]") on every reload, because
				// the customFields cache only fills after the user picks an
				// event/product context.
				'preResolvedCustomFields' => self::pre_resolve_custom_field_labels( self::get_editing_template_data() ),
				'ai'                      => array(
					'enabled' => self::ai_is_available(),
					'action'  => 'tc_designer_ticket_ai_generate',
					'strings' => array(
						'title'         => __( 'AI Ticket Assistant', 'tickera-event-ticketing-system' ),
						'subtitle'      => __( 'Describe the ticket layout and the AI will build it on the canvas. You can also ask it to change the current design.', 'tickera-event-ticketing-system' ),
						'placeholder'   => __( 'e.g. Design a premium concert ticket: full-bleed background, a bold indigo header band with the event name in large white text, date/time and venue beneath with small grey labels, ticket type bold and price in the accent colour on the lower left, a QR code bound to the ticket ID on the right with the ID in small monospace under it, and a thin divider under the header.', 'tickera-event-ticketing-system' ),
						'generate'      => __( 'Generate', 'tickera-event-ticketing-system' ),
						'generating'    => __( 'Thinking…', 'tickera-event-ticketing-system' ),
						'clearFirst'    => __( 'Clear the current design before generating', 'tickera-event-ticketing-system' ),
						'cancel'        => __( 'Cancel', 'tickera-event-ticketing-system' ),
						'empty'         => __( 'Please describe what you want.', 'tickera-event-ticketing-system' ),
						'done'          => __( 'Done', 'tickera-event-ticketing-system' ),
						'error'         => __( 'Something went wrong. Please try again.', 'tickera-event-ticketing-system' ),
						'seeConsole'    => __( '(open the browser console — press F12 — for the full reason)', 'tickera-event-ticketing-system' ),
						'unavailable'   => __( 'AI is unavailable. Add a provider under Settings → Connectors (OpenAI, Anthropic, or Google).', 'tickera-event-ticketing-system' ),
						'examplesTitle' => __( 'Try:', 'tickera-event-ticketing-system' ),
						'examples'      => array(
							__( 'Design a premium concert ticket. Add a full-bleed background, then a bold indigo (#3659E3) header band across the top with the event name in large white bold text. Under the header put the date & time and the venue with small grey labels. On the lower left show the ticket type in bold and the price in indigo, larger. On the right add a QR code bound to the ticket ID, with the ticket ID in small grey monospace centered beneath it. Add a thin divider line under the header. Tidy 20pt margins, clear hierarchy.', 'tickera-event-ticketing-system' ),
							__( 'Design an elegant VIP gala pass with a dark charcoal background, gold (#C9A227) accents, the event name in large gold serif text, attendee name and ticket type beneath, a gold divider, and a QR code bottom-right bound to the ticket ID.', 'tickera-event-ticketing-system' ),
							__( 'A clean minimal festival ticket: event name and date on the left, ticket type bold below, a barcode along the bottom bound to the ticket ID, and a slim accent stripe down the left edge.', 'tickera-event-ticketing-system' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Read the template_data JSON for the template currently being edited,
	 * if any. Returns an empty string for the templates-list screen or when
	 * the template ID is missing/invalid.
	 *
	 * @return string Template data JSON (possibly empty).
	 */
	private static function get_editing_template_data() {
		$action      = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;

		if ( 'edit' !== $action || ! $template_id || ! class_exists( 'TC_Ticket_Designer_Template' ) ) {
			return '';
		}

		$template = new TC_Ticket_Designer_Template( $template_id );
		$data     = $template->get( 'template_data' );

		return is_string( $data ) ? $data : '';
	}

	/**
	 * Pre-resolve labels for any `attendee_field_<id>` keys already bound on
	 * the given template. The resulting list seeds the editor's customFields
	 * cache so the canvas can display the friendly label (e.g. "Iskustvo")
	 * for existing bindings without the user having to re-pick a context.
	 *
	 * @param string $template_data_json Raw template_data JSON.
	 * @return array<int,array{id:string,label:string}>
	 */
	private static function pre_resolve_custom_field_labels( $template_data_json ) {
		if ( ! $template_data_json || ! class_exists( 'Venuera_Attendee_Fields' ) ) {
			return array();
		}

		$decoded = json_decode( $template_data_json, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		// Collect every `attendee_field_<id>` referenced anywhere in the tree.
		$needed = array();
		$walk   = function ( $node ) use ( &$walk, &$needed ) {
			if ( ! is_array( $node ) ) {
				return;
			}
			if ( isset( $node['dataField'] ) && is_string( $node['dataField'] )
				&& 0 === strpos( $node['dataField'], 'attendee_field_' ) ) {
				$field_id = substr( $node['dataField'], 15 ); // strip prefix.
				if ( '' !== $field_id ) {
					$needed[ $field_id ] = true;
				}
			}
			foreach ( $node as $child ) {
				if ( is_array( $child ) ) {
					$walk( $child );
				}
			}
		};
		$walk( $decoded );

		if ( empty( $needed ) ) {
			return array();
		}

		// Scan every ticket product's attendee fields meta and pick out the
		// labels for the IDs we actually need. Attendee field IDs are UUIDs
		// so they're globally unique; the first product that owns the ID
		// wins.
		$products = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_venuera_attendee_fields',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$resolved = array();
		foreach ( $products as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product ) {
				continue;
			}
			$fields = Venuera_Attendee_Fields::get_product_attendee_fields( $product );
			if ( ! is_array( $fields ) ) {
				continue;
			}
			foreach ( $fields as $field ) {
				if ( ! is_array( $field ) || empty( $field['id'] ) ) {
					continue;
				}
				$field_id = (string) $field['id'];
				if ( ! isset( $needed[ $field_id ] ) || isset( $resolved[ $field_id ] ) ) {
					continue;
				}
				$label                 = isset( $field['label'] ) && '' !== $field['label']
					? (string) $field['label']
					: $field_id;
				$resolved[ $field_id ] = array(
					'id'    => 'attendee_field_' . $field_id,
					'label' => $label,
				);
			}
			if ( count( $resolved ) === count( $needed ) ) {
				break; // Found them all.
			}
		}

		return array_values( $resolved );
	}

	/**
	 * Build the list of event/product contexts used by the editor's
	 * "Custom fields for" selector. Each entry lets the field picker fetch that
	 * context's attendee fields via the tc_designer_get_custom_fields AJAX action.
	 *
	 * @return array List of { type, id, label } context descriptors.
	 */
	private static function get_field_contexts() {
		$contexts = array();

		// Events (the primary context for templates / occurrences).
		$events = get_posts(
			array(
				'post_type'        => 'venuera_event',
				'post_status'      => array( 'publish', 'draft', 'private' ),
				'numberposts'      => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- Bounded admin-side dropdown of events; intentional limit.
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		foreach ( $events as $event ) {
			$contexts[] = array(
				'type'  => 'event',
				'id'    => (int) $event->ID,
				/* translators: placeholders are dynamic values. */
				'label' => sprintf( /* translators: %s: event title */ __( 'Event: %s', 'tickera-event-ticketing-system' ), $event->post_title ),
			);
		}

		// Event-ticket products (attendee fields are configured per product).
		if ( function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products(
				array(
					'type'    => array( 'event_ticket', 'event_ticket_variable' ),
					'status'  => 'publish',
					'limit'   => 200,
					'orderby' => 'title',
					'order'   => 'ASC',
					'return'  => 'objects',
				)
			);

			foreach ( $products as $product ) {
				$contexts[] = array(
					'type'  => 'product',
					'id'    => (int) $product->get_id(),
					/* translators: placeholders are dynamic values. */
					'label' => sprintf( /* translators: %s: ticket type name */ __( 'Ticket type: %s', 'tickera-event-ticketing-system' ), $product->get_name() ),
				);
			}
		}

		return $contexts;
	}

	/**
	 * Render admin page.
	 */
	public static function render_page() {
		$action      = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;

		if ( 'edit' === $action || 'new' === $action ) {
			self::render_editor( $template_id );
		} else {
			self::render_list();
		}
	}

	/**
	 * Render templates list.
	 */
	private static function render_list() {
		$templates = TC_Ticket_Designer_Template::get_all();
		?>
		<div class="wrap venuera-ticket-designer-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Ticket Designer', 'tickera-event-ticketing-system' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=tc-ticket-designer&action=new' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'tickera-event-ticketing-system' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( empty( $templates ) ) : ?>
				<div class="venuera-empty-state">
					<div class="venuera-empty-state-icon">
						<span class="dashicons dashicons-tickets-alt"></span>
					</div>
					<h2><?php esc_html_e( 'No ticket templates yet', 'tickera-event-ticketing-system' ); ?></h2>
					<p><?php esc_html_e( 'Create your first ticket template to customize how your tickets look.', 'tickera-event-ticketing-system' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=tc-ticket-designer&action=new' ) ); ?>" class="button button-primary button-hero">
						<?php esc_html_e( 'Create Ticket Template', 'tickera-event-ticketing-system' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="venuera-templates-grid">
					<?php foreach ( $templates as $template ) : ?>
						<div class="venuera-template-card" data-template-id="<?php echo esc_attr( $template->get_id() ); ?>">
							<div class="venuera-template-thumbnail">
								<?php if ( $template->get( 'thumbnail_url' ) ) : ?>
									<img src="<?php echo esc_url( $template->get( 'thumbnail_url' ) ); ?>" alt="">
								<?php else : ?>
									<div class="venuera-template-placeholder">
										<span class="dashicons dashicons-tickets-alt"></span>
										<span><?php esc_html_e( 'No preview', 'tickera-event-ticketing-system' ); ?></span>
									</div>
								<?php endif; ?>
								<?php if ( $template->get( 'is_default' ) ) : ?>
									<span class="venuera-template-badge"><?php esc_html_e( 'Default', 'tickera-event-ticketing-system' ); ?></span>
								<?php endif; ?>
							</div>
							<div class="venuera-template-info">
								<h3><?php echo esc_html( $template->get( 'name' ) ); ?></h3>
								<p class="venuera-template-meta">
									<?php
									$events = $template->get_assigned_events();
									// Count any ticket type assigned this template. Standalone
									// Tickera uses 'tc_tickets'; with Bridge for WooCommerce the
									// ticket type is a WooCommerce 'product' (and a variable
									// product stores the assignment on the parent product, with
									// per-variation overrides on 'product_variation'). Limiting to
									// 'tc_tickets' made Bridge/variable-product assignments show
									// as "Not assigned".
									$assigned_type_ids = get_posts(
										array(
											'fields'         => 'ids',
											'meta_key'       => 'tc_designer_template_id',
											'meta_value'     => $template->get_id(),
											'no_found_rows'  => true,
											'posts_per_page' => -1,
											'post_status'    => 'any',
											'post_type'      => array( 'tc_tickets', 'product', 'product_variation' ),
										)
									);
									$assigned_types = count( $assigned_type_ids );
									if ( $assigned_types > 0 ) {
										echo esc_html(
											sprintf(
												/* translators: %d: number of ticket types */
												_n( 'Assigned to %d ticket type', 'Assigned to %d ticket types', $assigned_types, 'tickera-event-ticketing-system' ),
												$assigned_types
											)
										);
									} elseif ( ! empty( $events ) ) {
										echo esc_html(
											sprintf(
												/* translators: %d: number of events */
												_n( 'Assigned to %d event', 'Assigned to %d events', count( $events ), 'tickera-event-ticketing-system' ),
												count( $events )
											)
										);
									} else {
										esc_html_e( 'Not assigned', 'tickera-event-ticketing-system' );
									}
									?>
								</p>
							</div>
							<div class="venuera-template-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=tc-ticket-designer&action=edit&template_id=' . $template->get_id() ) ); ?>" class="button">
									<?php esc_html_e( 'Edit', 'tickera-event-ticketing-system' ); ?>
								</a>
								<button type="button" class="button venuera-duplicate-template" data-template-id="<?php echo esc_attr( $template->get_id() ); ?>">
									<?php esc_html_e( 'Duplicate', 'tickera-event-ticketing-system' ); ?>
								</button>
								<button type="button" class="button venuera-delete-template" data-template-id="<?php echo esc_attr( $template->get_id() ); ?>">
									<?php esc_html_e( 'Delete', 'tickera-event-ticketing-system' ); ?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.venuera-delete-template').on('click', function() {
				var $button = $(this);
				var templateId = $button.data('template-id');
				var $card = $button.closest('.venuera-template-card');

				function doDelete() {
					$button.prop('disabled', true).text('<?php echo esc_js( __( 'Deleting...', 'tickera-event-ticketing-system' ) ); ?>');
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'tc_designer_delete_ticket_template',
							nonce: '<?php echo esc_js( wp_create_nonce( 'tc_ticket_designer_nonce' ) ); ?>',
							template_id: templateId
						},
						success: function(response) {
							if (response.success) {
								$card.fadeOut(300, function() {
									$(this).remove();
									if ($('.venuera-template-card').length === 0) {
										location.reload();
									}
								});
							} else {
								alert(response.data.message || '<?php echo esc_js( __( 'Failed to delete template.', 'tickera-event-ticketing-system' ) ); ?>');
								$button.prop('disabled', false).text('<?php echo esc_js( __( 'Delete', 'tickera-event-ticketing-system' ) ); ?>');
							}
						},
						error: function() {
							alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'tickera-event-ticketing-system' ) ); ?>');
							$button.prop('disabled', false).text('<?php echo esc_js( __( 'Delete', 'tickera-event-ticketing-system' ) ); ?>');
						}
					});
				}

				if (typeof window.VenueraDialog !== 'undefined') {
					window.VenueraDialog.confirm({
						title: '<?php echo esc_js( __( 'Delete ticket design?', 'tickera-event-ticketing-system' ) ); ?>',
						message: '<?php echo esc_js( __( 'This permanently deletes this ticket design. This cannot be undone.', 'tickera-event-ticketing-system' ) ); ?>',
						confirmText: '<?php echo esc_js( __( 'Delete', 'tickera-event-ticketing-system' ) ); ?>',
						cancelText: '<?php echo esc_js( __( 'Keep', 'tickera-event-ticketing-system' ) ); ?>',
						type: 'danger'
					}).then(function(ok) { if (ok) { doDelete(); } });
				} else if (confirm('<?php echo esc_js( __( 'Are you sure you want to delete this template?', 'tickera-event-ticketing-system' ) ); ?>')) {
					doDelete();
				}
			});

			$('.venuera-duplicate-template').on('click', function() {
				var $button = $(this);
				var templateId = $button.data('template-id');

				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Duplicating...', 'tickera-event-ticketing-system' ) ); ?>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'tc_designer_duplicate_ticket_template',
						nonce: '<?php echo esc_js( wp_create_nonce( 'tc_ticket_designer_nonce' ) ); ?>',
						template_id: templateId
					},
					success: function(response) {
						if (response.success) {
							// Reload so the new "(Copy)" template appears in the grid.
							location.reload();
						} else {
							alert((response.data && response.data.message) || '<?php echo esc_js( __( 'Failed to duplicate template.', 'tickera-event-ticketing-system' ) ); ?>');
							$button.prop('disabled', false).text('<?php echo esc_js( __( 'Duplicate', 'tickera-event-ticketing-system' ) ); ?>');
						}
					},
					error: function() {
						alert('<?php echo esc_js( __( 'An error occurred. Please try again.', 'tickera-event-ticketing-system' ) ); ?>');
						$button.prop('disabled', false).text('<?php echo esc_js( __( 'Duplicate', 'tickera-event-ticketing-system' ) ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render template editor.
	 *
	 * @param int $template_id Template ID (0 for new).
	 */
	private static function render_editor( $template_id = 0 ) {
		$template      = $template_id ? new TC_Ticket_Designer_Template( $template_id ) : null;
		$template_name = $template ? $template->get( 'name' ) : '';
		$template_data = $template ? $template->get( 'template_data' ) : '';
		$settings      = $template ? $template->get( 'settings' ) : '';
		?>
		<div class="wrap venuera-ticket-designer-editor-wrap">
			<div class="venuera-editor-header">
				<div class="venuera-editor-header-left">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=tc-ticket-designer' ) ); ?>" class="venuera-back-link">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<?php esc_html_e( 'Back to Templates', 'tickera-event-ticketing-system' ); ?>
					</a>
					<input type="text" id="venuera-template-name" class="venuera-template-name-input" 
							value="<?php echo esc_attr( $template_name ); ?>" 
							placeholder="<?php esc_attr_e( 'Untitled Template', 'tickera-event-ticketing-system' ); ?>">
				</div>
				<div class="venuera-editor-header-right">
					<?php
					/*
					 * The "Custom fields for: <select>" picker used to live here
					 * permanently. It's been moved into the properties panel —
					 * shown only when a data-bound element is selected — so the
					 * editor header stays focused on global actions.
					 */
					?>
					<?php
					/*
					 * HTML "Preview" button intentionally removed. The editor
					 * canvas is the live preview, and "Open PDF" renders the
					 * exact deliverable. We render in only two contexts —
					 * canvas (edit) and PDF (output) — and keep them 1:1
					 * pixel-perfect, rather than maintaining a third HTML/CSS
					 * preview renderer that drifts from both.
					 */
					?>
					<button type="button" id="venuera-download-pdf" class="button" <?php echo $template_id ? '' : 'disabled title="' . esc_attr__( 'Save template first', 'tickera-event-ticketing-system' ) . '"'; ?>>
						<svg class="venuera-btn-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">
							<path d="M5 3h9l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm9 1.5V8h3.5L14 4.5zM7 13h10v1.5H7V13zm0 3h10v1.5H7V16zm0-6h6v1.5H7V10z" fill="currentColor"/>
						</svg>
						<?php esc_html_e( 'Open PDF', 'tickera-event-ticketing-system' ); ?>
					</button>
					<button type="button" id="venuera-save-template" class="button button-primary button-large">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Template', 'tickera-event-ticketing-system' ); ?>
					</button>
				</div>
			</div>

			<div class="venuera-editor-container">
				<!-- Floating Toolbar -->
				<div class="venuera-floating-toolbar" id="venuera-floating-toolbar" data-position="left">
					<!-- Drag Handle -->
					<div class="venuera-toolbar-drag-handle" title="<?php esc_attr_e( 'Drag to move', 'tickera-event-ticketing-system' ); ?>">
						<svg viewBox="0 0 24 24" width="14" height="14"><path d="M11 18c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zm-2-8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0-6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm6 4c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" fill="currentColor"/></svg>
					</div>

					<!-- Tool Groups -->
					<div class="venuera-toolbar-group" data-group="tools">
						<button type="button" class="venuera-ftool-btn active" data-tool="select" title="<?php esc_attr_e( 'Select (V)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M13.64 21.97C13.14 22.21 12.54 22 12.31 21.5L10.13 16.76L7.62 18.78C7.45 18.92 7.24 19 7.02 19C6.55 19 6.13 18.64 6.04 18.18L4 7L21 13.27L14.1 15.13L16.28 19.87C16.52 20.38 16.31 20.98 15.81 21.21L13.64 21.97Z" fill="currentColor"/></svg>
						</button>
						<button type="button" class="venuera-ftool-btn" data-tool="pan" title="<?php esc_attr_e( 'Pan (H)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M23 5.5V20c0 2.2-1.8 4-4 4h-7.3c-1.08 0-2.1-.43-2.85-1.19L1 14.83s1.26-1.23 1.3-1.25c.22-.19.49-.29.79-.29.22 0 .42.06.6.16.04.01 4.31 2.46 4.31 2.46V4c0-.83.67-1.5 1.5-1.5S11 3.17 11 4v7h1V1.5c0-.83.67-1.5 1.5-1.5S15 .67 15 1.5V11h1V2.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5V11h1V5.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5z" fill="currentColor"/></svg>
						</button>
					</div>

					<div class="venuera-toolbar-divider"></div>

					<!-- Elements Dropdown -->
					<div class="venuera-toolbar-group" data-group="elements">
						<div class="venuera-ftool-dropdown">
							<button type="button" class="venuera-ftool-btn venuera-dropdown-trigger" title="<?php esc_attr_e( 'Add Elements', 'tickera-event-ticketing-system' ); ?>">
								<svg viewBox="0 0 24 24"><path d="M17 14h-4v4h-2v-4H7v-2h4V8h2v4h4v2zM3 5v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2zm16 14H5V5h14v14z" fill="currentColor"/></svg>
							</button>
							<div class="venuera-dropdown-menu venuera-elements-menu">
								<!-- Populated from the element registry (all categories) by toolbar.js buildElementPanel(). The buttons below are a fallback if JS fails. -->
								<button type="button" class="venuera-dropdown-item venuera-element-btn" data-element="dynamic_text">
									<svg viewBox="0 0 24 24"><path d="M5 4v3h5.5v12h3V7H19V4H5z" fill="currentColor"/></svg>
									<?php esc_html_e( 'Dynamic Text', 'tickera-event-ticketing-system' ); ?>
								</button>
								<button type="button" class="venuera-dropdown-item venuera-element-btn" data-element="static_text">
									<svg viewBox="0 0 24 24"><path d="M5 4V7H10.5V19H13.5V7H19V4H5Z" fill="currentColor"/></svg>
									<?php esc_html_e( 'Static Text', 'tickera-event-ticketing-system' ); ?>
								</button>
								<button type="button" class="venuera-dropdown-item venuera-element-btn" data-element="qr_code">
									<svg viewBox="0 0 24 24"><path d="M3 11h2V9H3v2m10 0v2h2v-2h-2m-4 0v2h2v-2H9m4 4v2h2v-2h-2m-4 0v2h2v-2H9m6 0v2h2v-2h-2m2-4v2h2v-2h-2m0-4v2h2V7h-2m0 12v2h2v-2h-2m-4 0v2h2v-2h-2m0-4v2h2v-2h-2m-4 4v2h2v-2H9m-6 0v2h2v-2H3m0-4v2h2v-2H3m0-4v2h2v-2H3m0-4v2h2V7H3m8 0v2h2V7h-2m4 0v2h2V7h-2M7 11V9h2v2H7m-4 4v-2h2v2H3m8 0v-2h2v2h-2z" fill="currentColor"/></svg>
									<?php esc_html_e( 'QR Code', 'tickera-event-ticketing-system' ); ?>
								</button>
								<button type="button" class="venuera-dropdown-item venuera-element-btn" data-element="barcode">
									<svg viewBox="0 0 24 24"><path d="M2 6h2v12H2V6m3 0h1v12H5V6m2 0h3v12H7V6m4 0h1v12h-1V6m3 0h2v12h-2V6m3 0h3v12h-3V6m4 0h1v12h-1V6z" fill="currentColor"/></svg>
									<?php esc_html_e( 'Barcode', 'tickera-event-ticketing-system' ); ?>
								</button>
								<div class="venuera-dropdown-divider"></div>
								<button type="button" class="venuera-dropdown-item venuera-element-btn" data-element="image">
									<svg viewBox="0 0 24 24"><path d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-1 2-2V5c0-1-1-2-2-2m0 15.92c-.02.03-.06.06-.08.08H3V5.08L3.08 5H21v13.92M11 12.5L8 17h8l-4.5-6L8 15.5l3-3.5z" fill="currentColor"/></svg>
									<?php esc_html_e( 'Image', 'tickera-event-ticketing-system' ); ?>
								</button>
								<button type="button" class="venuera-dropdown-item venuera-element-btn" data-element="event_image">
									<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2m0 16H5V5h14v14m-5.04-6.71l-2.75 3.54-1.96-2.36L6.5 17h11l-3.54-4.71z" fill="currentColor"/></svg>
									<?php esc_html_e( 'Event Image', 'tickera-event-ticketing-system' ); ?>
								</button>
								<div class="venuera-dropdown-divider"></div>
								<button type="button" class="venuera-dropdown-item venuera-element-btn" data-element="rectangle">
									<svg viewBox="0 0 24 24"><path d="M4 6v12h16V6H4m14 10H6V8h12v8z" fill="currentColor"/></svg>
									<?php esc_html_e( 'Rectangle', 'tickera-event-ticketing-system' ); ?>
								</button>
								<button type="button" class="venuera-dropdown-item venuera-element-btn" data-element="line">
									<svg viewBox="0 0 24 24"><path d="M19 13H5v-2h14v2z" fill="currentColor"/></svg>
									<?php esc_html_e( 'Line', 'tickera-event-ticketing-system' ); ?>
								</button>
							</div>
						</div>
					</div>

					<div class="venuera-toolbar-divider"></div>

					<!-- AI Assistant -->
					<div class="venuera-toolbar-group" data-group="ai" id="venuera-td-ai-group" style="display:none;">
						<button type="button" class="venuera-ftool-btn venuera-ftool-ai" id="venuera-td-ai-open" title="<?php esc_attr_e( 'AI Ticket Assistant', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M12 2l1.9 4.7L18.6 8.6 13.9 10.5 12 15.2 10.1 10.5 5.4 8.6 10.1 6.7 12 2zm6.5 11l.9 2.2 2.2.9-2.2.9-.9 2.2-.9-2.2-2.2-.9 2.2-.9.9-2.2zM5 14l.8 1.9L7.7 16.7l-1.9.8L5 19.4l-.8-1.9L2.3 16.7l1.9-.8L5 14z" fill="currentColor"/></svg>
						</button>
					</div>

					<div class="venuera-toolbar-divider"></div>

					<!-- Ticket Size Dropdown -->
					<div class="venuera-toolbar-group" data-group="size">
						<div class="venuera-ftool-dropdown">
							<button type="button" class="venuera-ftool-btn venuera-dropdown-trigger" title="<?php esc_attr_e( 'Ticket Size', 'tickera-event-ticketing-system' ); ?>">
								<svg viewBox="0 0 24 24"><path d="M19 12h-2v3h-3v2h5v-5zM7 9h3V7H5v5h2V9zm14-6H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16.01H3V4.99h18v14.02z" fill="currentColor"/></svg>
							</button>
							<div class="venuera-dropdown-menu venuera-size-dropdown">
								<div class="venuera-dropdown-item venuera-size-option" data-size="standard"><?php esc_html_e( 'Standard (6" × 2.5")', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="concert"><?php esc_html_e( 'Concert (8" × 3")', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="compact"><?php esc_html_e( 'Compact (5" × 2")', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="large"><?php esc_html_e( 'Large (8.5" × 3.5")', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="pass"><?php esc_html_e( 'Pass (3.5" × 5")', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="badge"><?php esc_html_e( 'Badge (3.5" × 4.5")', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="a6_landscape"><?php esc_html_e( 'A6 Landscape', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="a6_portrait"><?php esc_html_e( 'A6 Portrait', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="a5_landscape"><?php esc_html_e( 'A5 Landscape', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="a5_portrait"><?php esc_html_e( 'A5 Portrait', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="a4_landscape"><?php esc_html_e( 'A4 Landscape', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="a4_portrait"><?php esc_html_e( 'A4 Portrait', 'tickera-event-ticketing-system' ); ?></div>
								<div class="venuera-dropdown-divider"></div>
								<div class="venuera-dropdown-item venuera-size-option" data-size="custom"><?php esc_html_e( 'Custom Size...', 'tickera-event-ticketing-system' ); ?></div>
							</div>
						</div>
						<span class="venuera-size-display" id="venuera-size-display" title="<?php esc_attr_e( 'Print size', 'tickera-event-ticketing-system' ); ?>">6" × 2.5"</span>
					</div>

					<div class="venuera-toolbar-divider"></div>

					<!-- View Controls -->
					<div class="venuera-toolbar-group" data-group="view">
						<button type="button" class="venuera-ftool-btn" id="venuera-zoom-out" title="<?php esc_attr_e( 'Zoom Out (-)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14zM7 9h5v1H7V9z" fill="currentColor"/></svg>
						</button>
						<span class="venuera-zoom-display" id="venuera-zoom-level" title="<?php esc_attr_e( 'Click to reset', 'tickera-event-ticketing-system' ); ?>">100%</span>
						<button type="button" class="venuera-ftool-btn" id="venuera-zoom-in" title="<?php esc_attr_e( 'Zoom In (+)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14zM12 10h-2v2H9v-2H7V9h2V7h1v2h2v1z" fill="currentColor"/></svg>
						</button>
						<button type="button" class="venuera-ftool-btn" id="venuera-zoom-fit" title="<?php esc_attr_e( 'Fit to Screen (0)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M17 4H20V7H22V2H17V4M2 7V2H7V4H4V7H2M7 20H4V17H2V22H7V20M22 17V22H17V20H20V17H22M12 8C9.79 8 8 9.79 8 12S9.79 16 12 16 16 14.21 16 12 14.21 8 12 8Z" fill="currentColor"/></svg>
						</button>
					</div>

					<div class="venuera-toolbar-divider"></div>

					<!-- Grid & Snap -->
					<div class="venuera-toolbar-group" data-group="grid">
						<button type="button" class="venuera-ftool-btn" id="venuera-toggle-grid" title="<?php esc_attr_e( 'Toggle Grid (G)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M20 2H4C2.9 2 2 2.9 2 4V20C2 21.1 2.9 22 4 22H20C21.1 22 22 21.1 22 20V4C22 2.9 21.1 2 20 2ZM8 20H4V16H8V20ZM8 14H4V10H8V14ZM8 8H4V4H8V8ZM14 20H10V16H14V20ZM14 14H10V10H14V14ZM14 8H10V4H14V8ZM20 20H16V16H20V20ZM20 14H16V10H20V14ZM20 8H16V4H20V8Z" fill="currentColor"/></svg>
						</button>
						<button type="button" class="venuera-ftool-btn" id="venuera-toggle-snap" title="<?php esc_attr_e( 'Snap to Grid (S)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M3 5V3h2v2H3zm4 0V3h2v2H7zm4 0V3h2v2h-2zm4 0V3h2v2h-2zm4 0V3h2v2h-2zM3 9V7h2v2H3zm18 0V7h2v2h-2zM3 13v-2h2v2H3zm18 0v-2h2v2h-2zM3 17v-2h2v2H3zm18 0v-2h2v2h-2zM3 21v-2h2v2H3zm4 0v-2h2v2H7zm4 0v-2h2v2h-2zm4 0v-2h2v2h-2zm4 0v-2h2v2h-2z" fill="currentColor"/><circle cx="12" cy="12" r="3" fill="currentColor"/></svg>
						</button>
						<button type="button" class="venuera-ftool-btn" id="venuera-toggle-onion" title="<?php esc_attr_e( 'Show elements outside the ticket (O)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5c-1.7-4.4-6-7.5-11-7.5zm0 12.5a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z" fill="currentColor"/></svg>
						</button>
					</div>

					<div class="venuera-toolbar-divider"></div>

					<!-- History -->
					<div class="venuera-toolbar-group" data-group="history">
						<button type="button" class="venuera-ftool-btn" id="venuera-undo" title="<?php esc_attr_e( 'Undo (Ctrl+Z)', 'tickera-event-ticketing-system' ); ?>" disabled>
							<svg viewBox="0 0 24 24"><path d="M12.5 8C9.85 8 7.45 9 5.6 10.6L2 7V16H11L7.38 12.38C8.77 11.22 10.54 10.5 12.5 10.5C16.04 10.5 19.05 12.81 20.1 16L22.47 15.22C21.08 11.03 17.15 8 12.5 8Z" fill="currentColor"/></svg>
						</button>
						<button type="button" class="venuera-ftool-btn" id="venuera-redo" title="<?php esc_attr_e( 'Redo (Ctrl+Y)', 'tickera-event-ticketing-system' ); ?>" disabled>
							<svg viewBox="0 0 24 24"><path d="M18.4 10.6C16.55 9 14.15 8 11.5 8C6.85 8 2.92 11.03 1.54 15.22L3.9 16C4.95 12.81 7.95 10.5 11.5 10.5C13.45 10.5 15.23 11.22 16.62 12.38L13 16H22V7L18.4 10.6Z" fill="currentColor"/></svg>
						</button>
					</div>

					<div class="venuera-toolbar-divider"></div>

					<!-- File: Export / Import JSON. Mirrors the Venue Designer's
						File Operations group so users get a consistent place
						(and identical icons) for these tools across both
						editors. -->
					<div class="venuera-toolbar-group" data-group="file">
						<button type="button" class="venuera-ftool-btn" id="venuera-export-template" title="<?php esc_attr_e( 'Export Ticket Template', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M14 2H6C4.89 2 4 2.9 4 4V20C4 21.11 4.89 22 6 22H18C19.11 22 20 21.11 20 20V8L14 2M18 20H6V4H13V9H18V20M12 12L16 16H13.5V19H10.5V16H8L12 12Z" fill="currentColor"/></svg>
						</button>
						<button type="button" class="venuera-ftool-btn" id="venuera-import-template" title="<?php esc_attr_e( 'Import Ticket Template', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M14 2H6C4.89 2 4 2.9 4 4V20C4 21.11 4.89 22 6 22H18C19.11 22 20 21.11 20 20V8L14 2M18 20H6V4H13V9H18V20M12 19L8 15H10.5V12H13.5V15H16L12 19Z" fill="currentColor"/></svg>
						</button>
						<input type="file" id="venuera-import-template-file" accept=".json,application/json" style="display:none;">
					</div>

					<div class="venuera-toolbar-divider"></div>

					<!-- Layer / z-order controls now live in the properties sidebar
						(TicketDesigner.renderArrangeSection). -->

					<!-- Delete -->
					<div class="venuera-toolbar-group" data-group="actions">
						<button type="button" class="venuera-ftool-btn venuera-ftool-danger" id="venuera-delete-element" title="<?php esc_attr_e( 'Delete (Del)', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24"><path d="M19 4H15.5L14.5 3H9.5L8.5 4H5V6H19M6 19A2 2 0 0 0 8 21H16A2 2 0 0 0 18 19V7H6V19Z" fill="currentColor"/></svg>
						</button>
					</div>

					<!-- Toolbar Controls -->
					<div class="venuera-toolbar-controls">
						<button type="button" class="venuera-toolbar-control" id="venuera-toolbar-collapse" title="<?php esc_attr_e( 'Collapse toolbar', 'tickera-event-ticketing-system' ); ?>">
							<svg viewBox="0 0 24 24" width="12" height="12"><path d="M19 13H5v-2h14v2z" fill="currentColor"/></svg>
						</button>
					</div>
				</div>

				<!-- Collapsed Mini Toolbar -->
				<div class="venuera-mini-toolbar" id="venuera-mini-toolbar" style="display: none;">
					<button type="button" class="venuera-mini-expand" id="venuera-toolbar-expand" title="<?php esc_attr_e( 'Expand toolbar', 'tickera-event-ticketing-system' ); ?>">
						<svg viewBox="0 0 24 24" width="16" height="16"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z" fill="currentColor"/></svg>
					</button>
				</div>

				<!-- Hidden inputs for size (backwards compatibility) -->
				<select id="venuera-ticket-size" style="display: none;">
					<option value="standard">Standard (6" × 2.5")</option>
					<option value="concert">Concert (8" × 3")</option>
					<option value="compact">Compact (5" × 2")</option>
					<option value="large">Large (8.5" × 3.5")</option>
					<option value="pass">Pass (3.5" × 5")</option>
					<option value="badge">Badge (3.5" × 4.5")</option>
					<option value="a6_landscape">A6 Landscape</option>
					<option value="a6_portrait">A6 Portrait</option>
					<option value="a5_landscape">A5 Landscape</option>
					<option value="a5_portrait">A5 Portrait</option>
					<option value="a4_landscape">A4 Landscape</option>
					<option value="a4_portrait">A4 Portrait</option>
					<option value="custom">Custom</option>
				</select>
				<div class="venuera-custom-size" style="display: none;">
					<input type="number" id="venuera-ticket-width" min="72" max="900" step="1">
					<input type="number" id="venuera-ticket-height" min="72" max="900" step="1">
					<select id="venuera-ticket-unit">
						<option value="pt" selected>pt</option>
						<option value="mm">mm</option>
						<option value="in">in</option>
					</select>
				</div>

				<!-- Canvas Area -->
				<div class="venuera-canvas-wrapper">
					<div class="venuera-canvas-container" id="venuera-canvas-container">
						<canvas id="venuera-ticket-canvas"></canvas>
					</div>

					<!-- Status Bar -->
					<div class="venuera-status-bar">
						<div class="venuera-status-left">
							<span class="venuera-status-hint"><?php esc_html_e( 'Space+Drag to pan • Scroll to zoom', 'tickera-event-ticketing-system' ); ?></span>
						</div>
						<div class="venuera-status-right">
							<span class="venuera-status-info" id="venuera-status-info"></span>
						</div>
					</div>
				</div>

				<!-- Right Sidebar - Properties -->
				<div class="venuera-properties-panel" id="venuera-properties-panel">
					<div class="venuera-panel-header">
						<h3><?php esc_html_e( 'Properties', 'tickera-event-ticketing-system' ); ?></h3>
					</div>
					<div class="venuera-panel-section venuera-ticket-settings">
						<div class="venuera-prop-header"><?php esc_html_e( 'Ticket', 'tickera-event-ticketing-system' ); ?></div>
						<div class="venuera-prop-group">
							<label for="venuera-bg-color"><?php esc_html_e( 'Background Color', 'tickera-event-ticketing-system' ); ?></label>
							<input type="text" id="venuera-bg-color" class="venuera-bg-color-picker" value="#ffffff">
						</div>
					</div>

					<!--
						Default-state panel — shown when nothing is selected. It
						holds the ready-made templates gallery + the export /
						import buttons. The runtime swaps it for the element
						properties UI as soon as a fabric object is clicked.
					-->
					<div class="venuera-panel-section venuera-template-tools" id="venuera-template-tools">
						<div class="venuera-prop-header"><?php esc_html_e( 'Ready-made templates', 'tickera-event-ticketing-system' ); ?></div>
						<p class="venuera-prop-hint"><?php esc_html_e( 'Click a template to replace your current design.', 'tickera-event-ticketing-system' ); ?></p>
						<div class="venuera-ready-templates" id="venuera-ready-templates">
							<div class="venuera-ready-templates-loading"><?php esc_html_e( 'Loading templates…', 'tickera-event-ticketing-system' ); ?></div>
						</div>

						<?php
						/*
						 * The admin-only "Replace source template" iteration tool
						 * was removed — the bundled templates are finalised, so the
						 * editor no longer exposes a way to overwrite the source
						 * .json files from the canvas.
						 */
						?>
						<!-- Export / Import JSON moved to the floating toolbar
							(File group) for parity with the Venue Designer. -->
					</div>

					<div class="venuera-panel-content" id="venuera-properties-content" style="display:none;">
						<p class="venuera-no-selection"><?php esc_html_e( 'Select an element to edit its properties.', 'tickera-event-ticketing-system' ); ?></p>
					</div>
				</div>
			</div>

			<input type="hidden" id="venuera-template-id" value="<?php echo esc_attr( $template_id ); ?>">
			<input type="hidden" id="venuera-template-data" value="<?php echo esc_attr( $template_data ); ?>">
			<input type="hidden" id="venuera-template-settings" value="<?php echo esc_attr( $settings ); ?>">
		</div>

		<!-- Element Properties Templates -->
		<?php self::render_property_templates(); ?>

		<!-- Preview Modal -->
		<div id="venuera-preview-modal" class="venuera-modal" style="display: none;">
			<div class="venuera-modal-content venuera-preview-modal-content">
				<div class="venuera-modal-header">
					<h3><?php esc_html_e( 'Ticket Preview', 'tickera-event-ticketing-system' ); ?></h3>
					<button type="button" class="venuera-modal-close">&times;</button>
				</div>
				<div class="venuera-modal-body">
					<div id="venuera-preview-container"></div>
				</div>
				<div class="venuera-modal-footer">
					<button type="button" class="button venuera-modal-close"><?php esc_html_e( 'Close', 'tickera-event-ticketing-system' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render property templates for JavaScript.
	 */
	private static function render_property_templates() {
		?>
		<!-- Dynamic Text Properties -->
		<script type="text/template" id="tmpl-venuera-dynamic-text-props">
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Data Field', 'tickera-event-ticketing-system' ); ?></label>
				<select name="dataField" class="venuera-prop-select">
					<?php foreach ( TC_Ticket_Designer_Element::get_data_fields() as $group_key => $group ) : ?>
						<optgroup label="<?php echo esc_attr( $group['label'] ); ?>">
							<?php foreach ( $group['fields'] as $field_key => $field_label ) : ?>
								<option value="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $field_label ); ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Label', 'tickera-event-ticketing-system' ); ?></label>
				<input type="text" name="label" class="venuera-prop-input" placeholder="<?php esc_attr_e( 'e.g., Date:', 'tickera-event-ticketing-system' ); ?>">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Label Position', 'tickera-event-ticketing-system' ); ?></label>
				<select name="labelPosition" class="venuera-prop-select">
					<option value="before"><?php esc_html_e( 'Before value', 'tickera-event-ticketing-system' ); ?></option>
					<option value="after"><?php esc_html_e( 'After value', 'tickera-event-ticketing-system' ); ?></option>
				</select>
			</div>
			<?php self::render_text_property_fields(); ?>
			<div class="venuera-prop-group">
				<label>
					<input type="checkbox" name="conditional">
					<?php esc_html_e( 'Hide if empty', 'tickera-event-ticketing-system' ); ?>
				</label>
			</div>
		</script>

		<!-- Static Text Properties -->
		<script type="text/template" id="tmpl-venuera-static-text-props">
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Text', 'tickera-event-ticketing-system' ); ?></label>
				<textarea name="text" class="venuera-prop-input" rows="3"></textarea>
			</div>
			<?php self::render_text_property_fields(); ?>
		</script>

		<!-- QR Code Properties -->
		<script type="text/template" id="tmpl-venuera-qr-code-props">
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Data Source', 'tickera-event-ticketing-system' ); ?></label>
				<select name="dataField" class="venuera-prop-select">
					<option value="ticket_id"><?php esc_html_e( 'Ticket ID', 'tickera-event-ticketing-system' ); ?></option>
					<option value="order_id"><?php esc_html_e( 'Order ID', 'tickera-event-ticketing-system' ); ?></option>
					<option value="custom"><?php esc_html_e( 'Custom URL', 'tickera-event-ticketing-system' ); ?></option>
				</select>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Size', 'tickera-event-ticketing-system' ); ?></label>
				<input type="range" name="size" min="50" max="200" step="10" class="venuera-prop-range">
				<span class="venuera-range-value"></span>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Error Correction', 'tickera-event-ticketing-system' ); ?></label>
				<select name="errorCorrectionLevel" class="venuera-prop-select">
					<?php foreach ( TC_Ticket_Designer_Element::get_qr_error_levels() as $level => $label ) : ?>
						<option value="<?php echo esc_attr( $level ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Foreground', 'tickera-event-ticketing-system' ); ?></label>
				<input type="text" name="foreground" class="venuera-color-picker" value="#000000">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Background', 'tickera-event-ticketing-system' ); ?></label>
				<input type="text" name="background" class="venuera-color-picker" value="#ffffff">
			</div>
		</script>

		<!-- Barcode Properties -->
		<script type="text/template" id="tmpl-venuera-barcode-props">
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Data Source', 'tickera-event-ticketing-system' ); ?></label>
				<select name="dataField" class="venuera-prop-select">
					<option value="ticket_id"><?php esc_html_e( 'Ticket ID', 'tickera-event-ticketing-system' ); ?></option>
					<option value="order_id"><?php esc_html_e( 'Order ID', 'tickera-event-ticketing-system' ); ?></option>
				</select>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Format', 'tickera-event-ticketing-system' ); ?></label>
				<select name="format" class="venuera-prop-select">
					<?php foreach ( TC_Ticket_Designer_Element::get_barcode_formats() as $format => $label ) : ?>
						<option value="<?php echo esc_attr( $format ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Width', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="width" min="80" max="300" class="venuera-prop-input">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Height', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="height" min="30" max="100" class="venuera-prop-input">
			</div>
			<div class="venuera-prop-group">
				<label>
					<input type="checkbox" name="showText">
					<?php esc_html_e( 'Show text below', 'tickera-event-ticketing-system' ); ?>
				</label>
			</div>
		</script>

		<!-- Image Properties -->
		<script type="text/template" id="tmpl-venuera-image-props">
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Image', 'tickera-event-ticketing-system' ); ?></label>
				<div class="venuera-image-upload">
					<input type="hidden" name="src" class="venuera-image-src">
					<div class="venuera-image-preview"></div>
					<button type="button" class="button venuera-select-image"><?php esc_html_e( 'Select Image', 'tickera-event-ticketing-system' ); ?></button>
					<button type="button" class="button venuera-remove-image" style="display: none;"><?php esc_html_e( 'Remove', 'tickera-event-ticketing-system' ); ?></button>
				</div>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Width', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="width" min="20" max="500" class="venuera-prop-input">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Height', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="height" min="20" max="500" class="venuera-prop-input">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Fit', 'tickera-event-ticketing-system' ); ?></label>
				<select name="fit" class="venuera-prop-select">
					<option value="contain"><?php esc_html_e( 'Contain', 'tickera-event-ticketing-system' ); ?></option>
					<option value="cover"><?php esc_html_e( 'Cover', 'tickera-event-ticketing-system' ); ?></option>
					<option value="fill"><?php esc_html_e( 'Stretch', 'tickera-event-ticketing-system' ); ?></option>
				</select>
			</div>
		</script>

		<!-- Rectangle Properties -->
		<script type="text/template" id="tmpl-venuera-rectangle-props">
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Width', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="width" min="10" max="1000" class="venuera-prop-input">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Height', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="height" min="10" max="800" class="venuera-prop-input">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Fill Color', 'tickera-event-ticketing-system' ); ?></label>
				<input type="text" name="fill" class="venuera-color-picker" value="#f0f0f0">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Border Color', 'tickera-event-ticketing-system' ); ?></label>
				<input type="text" name="stroke" class="venuera-color-picker" value="#cccccc">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Border Width', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="strokeWidth" min="0" max="10" class="venuera-prop-input">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Corner Radius', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="rx" min="0" max="50" class="venuera-prop-input">
			</div>
		</script>

		<!-- Line Properties -->
		<script type="text/template" id="tmpl-venuera-line-props">
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Orientation', 'tickera-event-ticketing-system' ); ?></label>
				<select name="orientation" class="venuera-prop-select">
					<option value="horizontal"><?php esc_html_e( 'Horizontal', 'tickera-event-ticketing-system' ); ?></option>
					<option value="vertical"><?php esc_html_e( 'Vertical', 'tickera-event-ticketing-system' ); ?></option>
				</select>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Length', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="width" min="10" max="1000" class="venuera-prop-input">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Color', 'tickera-event-ticketing-system' ); ?></label>
				<input type="text" name="stroke" class="venuera-color-picker" value="#cccccc">
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Thickness', 'tickera-event-ticketing-system' ); ?></label>
				<input type="number" name="strokeWidth" min="1" max="10" class="venuera-prop-input">
			</div>
		</script>

		<!-- Custom Field Properties -->
		<script type="text/template" id="tmpl-venuera-custom-field-props">
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Field Source', 'tickera-event-ticketing-system' ); ?></label>
				<select name="fieldSource" class="venuera-prop-select venuera-field-source">
					<option value="attendee"><?php esc_html_e( 'Attendee Field', 'tickera-event-ticketing-system' ); ?></option>
					<option value="event"><?php esc_html_e( 'Event Field', 'tickera-event-ticketing-system' ); ?></option>
				</select>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Select Field', 'tickera-event-ticketing-system' ); ?></label>
				<select name="fieldId" class="venuera-prop-select venuera-field-id">
					<option value=""><?php esc_html_e( '-- Select --', 'tickera-event-ticketing-system' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Filter by event or ticket type to see available fields.', 'tickera-event-ticketing-system' ); ?></p>
			</div>
			<div class="venuera-prop-group">
				<label><?php esc_html_e( 'Label', 'tickera-event-ticketing-system' ); ?></label>
				<input type="text" name="label" class="venuera-prop-input">
			</div>
			<?php self::render_text_property_fields(); ?>
		</script>

		<!-- Position Properties (common to all) -->
		<script type="text/template" id="tmpl-venuera-position-props">
			<div class="venuera-prop-section">
				<div class="venuera-prop-section-header"><?php esc_html_e( 'Position', 'tickera-event-ticketing-system' ); ?></div>
				<div class="venuera-prop-row">
					<div class="venuera-prop-group venuera-prop-half">
						<label><?php esc_html_e( 'X', 'tickera-event-ticketing-system' ); ?></label>
						<input type="number" name="x" class="venuera-prop-input">
					</div>
					<div class="venuera-prop-group venuera-prop-half">
						<label><?php esc_html_e( 'Y', 'tickera-event-ticketing-system' ); ?></label>
						<input type="number" name="y" class="venuera-prop-input">
					</div>
				</div>
			</div>
		</script>
		<?php
	}

	/**
	 * Render text property fields (shared between text elements).
	 */
	private static function render_text_property_fields() {
		?>
		<div class="venuera-prop-group">
			<label><?php esc_html_e( 'Font Family', 'tickera-event-ticketing-system' ); ?></label>
			<select name="fontFamily" class="venuera-prop-select venuera-font-select">
				<?php foreach ( TC_Ticket_Designer::get_available_fonts() as $font_key => $font_name ) : ?>
					<option value="<?php echo esc_attr( $font_key ); ?>" style="font-family: <?php echo esc_attr( $font_key ); ?>;"><?php echo esc_html( $font_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="venuera-prop-group">
			<label><?php esc_html_e( 'Font Size', 'tickera-event-ticketing-system' ); ?></label>
			<input type="number" name="fontSize" min="8" max="72" class="venuera-prop-input">
		</div>
		<div class="venuera-prop-row">
			<div class="venuera-prop-group venuera-prop-half">
				<label><?php esc_html_e( 'Weight', 'tickera-event-ticketing-system' ); ?></label>
				<select name="fontWeight" class="venuera-prop-select">
					<option value="normal"><?php esc_html_e( 'Normal', 'tickera-event-ticketing-system' ); ?></option>
					<option value="bold"><?php esc_html_e( 'Bold', 'tickera-event-ticketing-system' ); ?></option>
				</select>
			</div>
			<div class="venuera-prop-group venuera-prop-half">
				<label><?php esc_html_e( 'Style', 'tickera-event-ticketing-system' ); ?></label>
				<select name="fontStyle" class="venuera-prop-select">
					<option value="normal"><?php esc_html_e( 'Normal', 'tickera-event-ticketing-system' ); ?></option>
					<option value="italic"><?php esc_html_e( 'Italic', 'tickera-event-ticketing-system' ); ?></option>
				</select>
			</div>
		</div>
		<div class="venuera-prop-group">
			<label><?php esc_html_e( 'Text Color', 'tickera-event-ticketing-system' ); ?></label>
			<input type="text" name="fill" class="venuera-color-picker" value="#333333">
		</div>
		<div class="venuera-prop-group">
			<label><?php esc_html_e( 'Text Align', 'tickera-event-ticketing-system' ); ?></label>
			<div class="venuera-button-group">
				<button type="button" class="venuera-align-btn" data-align="left" title="<?php esc_attr_e( 'Left', 'tickera-event-ticketing-system' ); ?>">
					<span class="dashicons dashicons-editor-alignleft"></span>
				</button>
				<button type="button" class="venuera-align-btn" data-align="center" title="<?php esc_attr_e( 'Center', 'tickera-event-ticketing-system' ); ?>">
					<span class="dashicons dashicons-editor-aligncenter"></span>
				</button>
				<button type="button" class="venuera-align-btn" data-align="right" title="<?php esc_attr_e( 'Right', 'tickera-event-ticketing-system' ); ?>">
					<span class="dashicons dashicons-editor-alignright"></span>
				</button>
			</div>
			<input type="hidden" name="textAlign" value="left">
		</div>
		<?php
	}

	/**
	 * Render the Ticket Template selector inside the Event Ticket data
	 * panel. The panel is visible for both `event_ticket` and
	 * `event_ticket_variable` types, so the same hook now serves both.
	 *
	 * @param WC_Product|false|null $product Current product (passed by the
	 *                                       venuera_event_ticket_panel_after
	 *                                       action). Falls back to the global
	 *                                       $post for safety.
	 */
	public static function add_product_template_field( $product = null ) {
		if ( ! $product ) {
			global $post;
			if ( ! $post ) {
				return;
			}
			$product = wc_get_product( $post->ID );
		}

		// Do NOT gate on the product TYPE here. This field lives inside the
		// always-rendered "Event Ticket" data panel, which WooCommerce shows/hides
		// live (via the show_if_event_ticket* classes) as the product-type dropdown
		// changes. Gating on get_type() hid the field on a brand-new, unsaved
		// product (still "simple") until its first save — so the selector only
		// appeared after saving. Rendering it unconditionally lets it show the
		// moment the Event Ticket type is selected, like the panel's other fields.
		if ( ! $product ) {
			return;
		}

		$product_id  = $product->get_id();
		$template_id = get_post_meta( $product_id, '_ticket_template_id', true );
		$templates   = TC_Ticket_Designer_Template::get_all();

		// Render inside its own options group so the bottom rule visually
		// separates it from the rest of the Event Ticket fields.
		echo '<div class="options_group">';
		woocommerce_wp_select(
			array(
				'id'          => '_ticket_template_id',
				'label'       => __( 'Ticket Template', 'tickera-event-ticketing-system' ),
				'options'     => array_reduce(
					$templates,
					function ( $carry, $template ) {
						$carry[ $template->get_id() ] = $template->get( 'name' );
						return $carry;
					},
					array( '' => __( '-- Default Template --', 'tickera-event-ticketing-system' ) )
				),
				'value'       => $template_id,
				'desc_tip'    => true,
				'description' => 'event_ticket_variable' === $product->get_type()
					? __( 'Default template for every variation of this product. Individual variations can override this in their own Ticket Template field.', 'tickera-event-ticketing-system' )
					: __( 'Select a specific ticket template for this ticket type.', 'tickera-event-ticketing-system' ),
			)
		);
		echo '</div>';
	}

	/**
	 * Render the per-variation Ticket Template selector. Defaults to
	 * "Inherit from parent" so variations transparently follow the parent
	 * Variable Event Ticket unless the user picks otherwise.
	 *
	 * @param int     $loop           Variation index in the loop.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post.
	 */
	public static function add_variation_template_field( $loop, $variation_data, $variation ) {
		$parent_id = wp_get_post_parent_id( $variation->ID );
		if ( ! $parent_id ) {
			return;
		}

		$parent_product = wc_get_product( $parent_id );
		if ( ! $parent_product || ! $parent_product->is_type( 'event_ticket_variable' ) ) {
			return;
		}

		$template_id = get_post_meta( $variation->ID, '_ticket_template_id', true );
		$templates   = TC_Ticket_Designer_Template::get_all();

		$options = array( '' => __( '-- Inherit from parent --', 'tickera-event-ticketing-system' ) );
		foreach ( $templates as $template ) {
			$options[ $template->get_id() ] = $template->get( 'name' );
		}
		?>
		<p class="form-row form-row-full">
			<label for="ticket_template_id_<?php echo esc_attr( $loop ); ?>">
				<?php esc_html_e( 'Ticket Template', 'tickera-event-ticketing-system' ); ?>
				<span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e( 'Pick a template just for this variation, or inherit from the parent product.', 'tickera-event-ticketing-system' ); ?>" data-tip="<?php esc_attr_e( 'Pick a template just for this variation, or inherit from the parent product.', 'tickera-event-ticketing-system' ); ?>"></span>
			</label>
			<select name="variation_ticket_template_id[<?php echo esc_attr( $loop ); ?>]" id="ticket_template_id_<?php echo esc_attr( $loop ); ?>" class="select short">
				<?php foreach ( $options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $template_id, (string) $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save per-variation Ticket Template override. Empty value means "Inherit
	 * from parent" — we delete the variation meta in that case so the
	 * resolver falls back to the parent's setting.
	 *
	 * @param int $variation_id Variation post ID.
	 * @param int $loop         Variation loop index.
	 */
	public static function save_variation_template( $variation_id, $loop ) {
		// WooCommerce verifies this nonce (action 'save-variations') before firing
		// woocommerce_save_product_variation; re-verify so the handler is self-contained.
		if ( ! isset( $_POST['security'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['security'] ) ), 'save-variations' )
		) {
			return;
		}

		if ( ! isset( $_POST['variation_ticket_template_id'][ $loop ] ) ) {
			return;
		}

		$template_id = absint( wp_unslash( $_POST['variation_ticket_template_id'][ $loop ] ) );

		if ( $template_id ) {
			update_post_meta( $variation_id, '_ticket_template_id', $template_id );
			$template = new TC_Ticket_Designer_Template( $template_id );
			$template->assign( null, $variation_id, 30 ); // Priority higher than parent (20).
		} else {
			// "Inherit from parent" — drop the variation-level override.
			delete_post_meta( $variation_id, '_ticket_template_id' );
		}
	}

	/**
	 * Save product template.
	 *
	 * @param int $post_id Product ID.
	 */
	public static function save_product_template( $post_id ) {
		// WooCommerce verifies this nonce before firing woocommerce_process_product_meta.
		if ( ! isset( $_POST['woocommerce_meta_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' )
		) {
			return;
		}

		$template_id = isset( $_POST['_ticket_template_id'] ) ? absint( wp_unslash( $_POST['_ticket_template_id'] ) ) : 0;

		if ( $template_id ) {
			update_post_meta( $post_id, '_ticket_template_id', $template_id );

			$template = new TC_Ticket_Designer_Template( $template_id );
			$template->assign( null, $post_id, 20 );
		} else {
			delete_post_meta( $post_id, '_ticket_template_id' );
		}
	}
}
