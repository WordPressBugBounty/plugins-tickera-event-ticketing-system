<?php
/**
 * Venuera Ticket Designer Addon
 *
 * Visual ticket template designer for creating custom ticket layouts.
 *
 * @package Venuera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.Security.NonceVerification.Recommended, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude, Squiz.PHP.DiscouragedFunctions.Discouraged, WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Venuera custom-table data access:
	// These routines access Venuera's own custom tables ($wpdb->prefix . 'venuera_*'), which have no
	// WordPress core API. Table identifiers cannot be bound via $wpdb->prepare(), so the table NAME
	// (always built from $wpdb->prefix, never user input) is interpolated, while every VALUE is bound
	// through prepare() placeholders (verified across the file). Direct queries are required, and the
	// results are transactional ticket/seat data that must not be served stale from the object cache.
	// Read-only GET filters here are public search/pagination params (sanitized, no state change), which
	// have no nonce by design.

	exit;
}

/**
 * Ticket Designer main class.
 */
class TC_Ticket_Designer {

	/**
	 * Addon version.
	 */
	const VERSION = '1.0.1';

	/**
	 * Instance.
	 *
	 * @var TC_Ticket_Designer
	 */
	private static $instance = null;

	/**
	 * Addon path.
	 *
	 * @var string
	 */
	private $addon_path;

	/**
	 * Addon URL.
	 *
	 * @var string
	 */
	private $addon_url;

	/**
	 * Get instance.
	 *
	 * @return TC_Ticket_Designer
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->addon_path = plugin_dir_path( __FILE__ );
		$this->addon_url  = plugin_dir_url( __FILE__ );

		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		// Core classes.
		require_once $this->addon_path . 'includes/class-venuera-ticket-designer-install.php';
		require_once $this->addon_path . 'includes/class-tc-ticket-designer-fields.php';
		require_once $this->addon_path . 'includes/class-venuera-ticket-fonts.php';
		require_once $this->addon_path . 'includes/class-venuera-ticket-template.php';
		require_once $this->addon_path . 'includes/class-venuera-ticket-element.php';
		require_once $this->addon_path . 'includes/class-venuera-ticket-pdf-generator.php';
		require_once $this->addon_path . 'includes/class-venuera-ticket-download.php';

		// Admin.
		if ( is_admin() ) {
			require_once $this->addon_path . 'includes/admin/class-venuera-ticket-designer-admin.php';
		}

		// Frontend.
		// Faza 0: WooCommerce-coupled frontend path deferred. Do not load yet.
		// require_once $this->addon_path . 'includes/frontend/class-venuera-ticket-designer-frontend.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Check and install tables if needed. Deferred to init because the
		// installer creates translated default templates and translations must
		// not load before the init action (WP 6.7+).
		//
		// Tickera loads its bundled addons on `wp_loaded` (priority 9), which
		// fires AFTER `init`. If init already ran, hooking it would never fire,
		// so run the installer immediately in that case; otherwise hook init.
		if ( did_action( 'init' ) ) {
			$this->maybe_install();
		} else {
			add_action( 'init', array( $this, 'maybe_install' ), 5 );
		}

		// Initialize admin.
		if ( is_admin() ) {
			TC_Ticket_Designer_Admin::init();
		}

		// Initialize frontend.
		// Faza 0: WooCommerce-coupled frontend path deferred.
		// TC_Ticket_Designer_Frontend::init();

		// Initialize secure download handler.
		// Faza 0: WooCommerce-coupled download path deferred.
		// TC_Ticket_Designer_Download::init();

		// AJAX handlers.
		add_action( 'wp_ajax_tc_designer_save_ticket_template', array( $this, 'ajax_save_ticket_template' ) );
		add_action( 'wp_ajax_tc_designer_load_ticket_template', array( $this, 'ajax_load_ticket_template' ) );
		add_action( 'wp_ajax_tc_designer_delete_ticket_template', array( $this, 'ajax_delete_ticket_template' ) );
		add_action( 'wp_ajax_tc_designer_duplicate_ticket_template', array( $this, 'ajax_duplicate_ticket_template' ) );
		add_action( 'wp_ajax_tc_designer_get_custom_fields', array( $this, 'ajax_get_custom_fields' ) );
		add_action( 'wp_ajax_tc_designer_preview_ticket', array( $this, 'ajax_preview_ticket' ) );
		add_action( 'wp_ajax_tc_designer_preview_pdf', array( $this, 'ajax_preview_pdf' ) );

		// Ready-made starter templates — list + import. Backed by .json files
		// in the templates/ folder so we can ship more without code changes.
		add_action( 'wp_ajax_tc_designer_list_ready_templates', array( $this, 'ajax_list_ready_templates' ) );
		add_action( 'wp_ajax_tc_designer_get_ready_template', array( $this, 'ajax_get_ready_template' ) );
		// Admin-only "Replace source template" — overwrites the bundled
		// .json file on disk with the editor's current canvas state. Used
		// while iterating on the ready-made template library; not exposed
		// to non-admin operators.
		add_action( 'wp_ajax_tc_designer_save_ready_template', array( $this, 'ajax_save_ready_template' ) );

		// --- Dual-engine integration with Tickera's classic template system ---
		// Render real Tickera tickets through the designer when a ticket type is
		// assigned a designer template (router lives in TC_Ticket_Templates::generate_preview).
		add_filter( 'tickera_ticket_designer_pre_generate', array( $this, 'maybe_render_ticket' ), 10, 6 );
		// Merge designer templates into the single "Ticket template" selector on
		// the ticket type edit screen (instead of a second metabox).
		if ( is_admin() ) {
			add_filter( 'tc_ticket_fields', array( $this, 'merge_ticket_type_template_field' ) );
			// Deprecate the classic Ticket Templates UI in favour of the Designer.
			add_action( 'admin_menu', array( $this, 'maybe_hide_classic_templates_menu' ), 999 );
			add_action( 'admin_init', array( $this, 'maybe_redirect_classic_templates' ) );
			add_action( 'admin_notices', array( $this, 'classic_templates_deprecation_notice' ) );
			// Hide the legacy "Multipage ticket template" general setting too, once
			// the classic Ticket Templates UI is gone (it only affects the classic
			// PDF engine; the Designer paginates on its own).
			add_filter( 'tickera_general_settings_miscellaneous_fields', array( $this, 'maybe_hide_multipage_setting' ) );
			// Warn if Bridge for WooCommerce is active but older than the version
			// required by this Tickera release (the Ticket Designer needs the newer
			// Bridge to resolve ticket data / generate the PDF correctly).
			add_action( 'admin_notices', array( $this, 'maybe_bridge_version_notice' ) );
		}
		// On save, split the unified value back into the two metas the engines read.
		// Two save paths exist: the AJAX/inline create ('tickera_tickets_metas')
		// and the full post-editor publish ('tickera_ticket_type_metas').
		add_filter( 'tickera_tickets_metas', array( $this, 'split_template_meta' ) );
		add_filter( 'tickera_ticket_type_metas', array( $this, 'split_template_meta' ) );
		// The ticket-type field HTML is filtered through wp_kses with the custom
		// 'tickera_setting' allow-list, which permits <select>/<option> but not
		// <optgroup>; allow it so the grouped template dropdown renders.
		add_filter( 'wp_kses_allowed_html', array( $this, 'allow_optgroup_in_setting' ), 999, 2 );

		// WooCommerce email integration.
		// Faza 0: WooCommerce-coupled email attachment path deferred.
		// add_filter( 'woocommerce_email_attachments', array( $this, 'attach_tickets_to_email' ), 10, 4 );
	}

	/**
	 * Attach PDF tickets to order emails.
	 *
	 * @param array    $attachments Email attachments.
	 * @param string   $email_id    Email ID.
	 * @param WC_Order $order       Order object.
	 * @param object   $email       Email object.
	 * @return array Modified attachments.
	 */
	public function attach_tickets_to_email( $attachments, $email_id, $order, $email = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameter required by the woocommerce_email_attachments filter signature.
		// Respect the core "Send ticket email after purchase" setting: when it is
		// off, no ticket content (neither the order-email section nor the PDF
		// attachment) is added to customer emails.
		if ( 'no' === get_option( 'venuera_ticket_email_enabled', 'yes' ) ) {
			return $attachments;
		}

		// Only attach the PDF for the "PDF attachment" delivery modes; the "link"
		// and "code" modes deliver tickets in the email body instead. Read via the
		// shared helper so this gate and the email body always agree on the mode.
		$delivery = class_exists( 'Venuera_WC_Integration' )
			? Venuera_WC_Integration::email_delivery_mode()
			: get_option( 'venuera_email_ticket_delivery', 'link' );
		if ( ! in_array( $delivery, array( 'attachment', 'attachment_code' ), true ) ) {
			return $attachments;
		}

		// Only attach to completed order and customer emails.
		$allowed_emails = array(
			'customer_completed_order',
			'customer_processing_order',
			'customer_on_hold_order',
		);

		if ( ! in_array( $email_id, $allowed_emails, true ) ) {
			return $attachments;
		}

		if ( ! $order instanceof WC_Order ) {
			return $attachments;
		}

		// Track temp files to clean up after email is sent.
		$temp_files = array();

		// Generate PDF tickets for each ticket item.
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			// Check if it's a ticket product.
			if ( ! in_array( $product->get_type(), array( 'event_ticket', 'event_ticket_variable', 'event_ticket_variation' ), true ) ) {
				continue;
			}

			// Determine the real tickets linked to this item. When present, generate
			// ONE PDF per real ticket using that ticket's own attendee/UID/QR data.
			$ticket_ids = $item->get_meta( '_venuera_ticket_ids' );
			$quantity   = $item->get_quantity();

			$template = null;

			if ( is_array( $ticket_ids ) && ! empty( $ticket_ids ) ) {
				// Per-ticket generation (1-based index into the linked ticket rows).
				$ticket_count = count( $ticket_ids );
				for ( $index = 1; $index <= $ticket_count; $index++ ) {
					$ticket_data = TC_Ticket_Designer_Frontend::get_ticket_data_from_order( $order, $item_id, $index );

					if ( empty( $ticket_data ) ) {
						continue;
					}

					if ( null === $template ) {
						$template = TC_Ticket_Designer_Template::get_for_ticket(
							$ticket_data['event_id'] ?? 0,
							$ticket_data['product_id'] ?? 0
						);

						if ( ! $template ) {
							// No template for this item: stop trying further indexes.
							break;
						}
					}

					$this->generate_ticket_attachment( $template, $ticket_data, $attachments, $temp_files );
				}
			} else {
				// Legacy fallback: no linked ticket rows. Reuse the whole-item dataset
				// for each quantity with a synthetic per-ticket id suffix.
				$ticket_data = \Tickera\TC_Ticket_Designer_Frontend::get_ticket_data_from_order( $order, $item_id );

				if ( empty( $ticket_data ) ) {
					continue;
				}

				$template = TC_Ticket_Designer_Template::get_for_ticket(
					$ticket_data['event_id'] ?? 0,
					$ticket_data['product_id'] ?? 0
				);

				if ( ! $template ) {
					continue;
				}

				for ( $i = 0; $i < $quantity; $i++ ) {
					$ticket_data_copy              = $ticket_data;
					$ticket_data_copy['ticket_id'] = \Tickera\TC_Ticket_Designer_Frontend::generate_ticket_id( $order->get_id(), $item_id ) . '-' . ( $i + 1 );
					$ticket_data_copy['qr_code']   = $ticket_data_copy['ticket_id'];

					$this->generate_ticket_attachment( $template, $ticket_data_copy, $attachments, $temp_files );
				}
			}
		}

		// Schedule cleanup of temp files after email is sent.
		if ( ! empty( $temp_files ) ) {
			add_action(
				'woocommerce_email_sent',
				function () use ( $temp_files ) {
					foreach ( $temp_files as $file ) {
						if ( file_exists( $file ) ) {
							@unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged -- Removing a temporary local file created by this plugin; WP_Filesystem adds no benefit for transient cleanup.
						}
					}
				}
			);

			// Fallback cleanup in case email hook doesn't fire.
			add_action(
				'shutdown',
				function () use ( $temp_files ) {
					foreach ( $temp_files as $file ) {
						if ( file_exists( $file ) ) {
							@unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged -- Removing a temporary local file created by this plugin; WP_Filesystem adds no benefit for transient cleanup.
						}
					}
				}
			);
		}

		return $attachments;
	}

	/**
	 * Generate a single ticket PDF to a temp file and register it as an attachment.
	 *
	 * @param TC_Ticket_Designer_Template $template     Template object.
	 * @param array                   $ticket_data  Ticket data for this single ticket.
	 * @param array                   $attachments  Attachments array (by reference).
	 * @param array                   $temp_files   Temp files array for cleanup (by reference).
	 */
	private function generate_ticket_attachment( $template, $ticket_data, &$attachments, &$temp_files ) {
		$ticket_id = isset( $ticket_data['ticket_id'] ) ? $ticket_data['ticket_id'] : '';

		// Build a filesystem-safe filename based on the (real) ticket id/UID.
		$safe_id = sanitize_file_name( (string) $ticket_id );
		if ( '' === $safe_id ) {
			$safe_id = 'tickera-event-ticketing-system';
		}

		// wp_unique_filename()/WP_Filesystem live in wp-admin/includes/file.php,
		// which is NOT loaded on front-end / REST requests (e.g. a POS sale).
		if ( ! function_exists( 'wp_unique_filename' ) || ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Build a clean, unique ".pdf" path in the temp dir. wp_tempnam() would force
		// a ".tmp" name, which then becomes the attachment's filename in the email.
		$dir       = trailingslashit( get_temp_dir() );
		$temp_file = $dir . wp_unique_filename( $dir, 'ticket-' . $safe_id . '.pdf' );

		$pdf_content = TC_Ticket_Designer_PDF_Generator::generate( $template, $ticket_data, 'S' );
		if ( ! $pdf_content ) {
			return;
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}
		if ( empty( $wp_filesystem ) || ! $wp_filesystem->put_contents( $temp_file, $pdf_content, FS_CHMOD_FILE ) ) {
			return;
		}

		$attachments[] = $temp_file;
		$temp_files[]  = $temp_file;
	}

	/**
	 * Router callback: render a real Tickera ticket through the designer.
	 *
	 * Hooked on `tickera_ticket_designer_pre_generate` (fired at the top of
	 * TC_Ticket_Templates::generate_preview). Returns null to let Tickera's
	 * classic template engine handle it (default / backward compatible).
	 *
	 * @param mixed $pre                Short-circuit value (null = not handled).
	 * @param int   $ticket_instance_id Ticket instance id.
	 * @param mixed $template_id        Classic template id (legacy preview path).
	 * @param mixed $ticket_type_id     Ticket type id (legacy preview path).
	 * @param bool  $force_download     Download vs inline.
	 * @param bool  $string_attachment  Return PDF as a string (email attachment).
	 * @return mixed
	 */
	public function maybe_render_ticket( $pre, $ticket_instance_id, $template_id, $ticket_type_id, $force_download, $string_attachment ) {

		if ( null !== $pre ) {
			return $pre;
		}

		$ticket_instance_id = (int) $ticket_instance_id;

		// Admin "Preview" path: no ticket instance, but a ticket type id is given
		// (the Preview link on the ticket type screen). If that type uses a
		// designer template, render it with sample data; otherwise fall through to
		// the classic preview engine.
		if ( ! $ticket_instance_id && (int) $ticket_type_id ) {
			$preview_template_id = (int) get_post_meta( (int) $ticket_type_id, 'tc_designer_template_id', true );
			if ( $preview_template_id
				&& class_exists( 'TC_Ticket_Designer_Template' )
				&& class_exists( 'TC_Ticket_Designer_PDF_Generator' )
				&& class_exists( 'TC_Ticket_Designer_Fields' )
			) {
				$preview_template = new TC_Ticket_Designer_Template( $preview_template_id );
				if ( $preview_template->get_id() ) {
					if ( function_exists( 'tickera_ticket_designer_ensure_tcpdf' ) && ! tickera_ticket_designer_ensure_tcpdf() ) {
						return null;
					}
					$sample = TC_Ticket_Designer_Fields::sample_data();
					TC_Ticket_Designer_PDF_Generator::generate( $preview_template, $sample, ( $force_download ? 'D' : 'I' ), 'ticket-preview.pdf' );
					exit;
				}
			}
			return null;
		}

		// Only handle real ticket instances. Legacy template previews pass no
		// instance id — let those fall through to the classic engine.
		if ( ! $ticket_instance_id || ! class_exists( '\Tickera\TC_Ticket_Instance' ) ) {
			return null;
		}

		$instance = new \Tickera\TC_Ticket_Instance( $ticket_instance_id );
		$type_id  = isset( $instance->details->ticket_type_id ) ? (int) $instance->details->ticket_type_id : 0;
		if ( ! $type_id ) {
			return null;
		}

		// Which designer template is assigned to this ticket type? 0/empty means
		// "use the classic template" → fall through.
		$designer_template_id = (int) get_post_meta( $type_id, 'tc_designer_template_id', true );

		// Variable products: the ticket instance records the VARIATION id, but the
		// template is assigned on the PARENT ticket type. Resolve the effective
		// type id via the same filter the classic engine uses (Bridge maps a
		// variation to its parent) and retry — otherwise variable-product tickets
		// never match a designer template and render completely empty.
		if ( ! $designer_template_id && function_exists( 'tickera_apply_filters' ) ) {
			$effective_type_id = (int) tickera_apply_filters( 'tickera_ticket_type_id', $type_id );
			if ( $effective_type_id && $effective_type_id !== $type_id ) {
				$designer_template_id = (int) get_post_meta( $effective_type_id, 'tc_designer_template_id', true );
			}
		}
		if ( ! $designer_template_id ) {
			return null;
		}

		if ( ! class_exists( 'TC_Ticket_Designer_Template' ) || ! class_exists( 'TC_Ticket_Designer_PDF_Generator' ) || ! class_exists( 'TC_Ticket_Designer_Fields' ) ) {
			return null;
		}

		$template = new TC_Ticket_Designer_Template( $designer_template_id );
		if ( ! $template->get_id() ) {
			// Assigned template was deleted — fall back to the classic engine.
			return null;
		}

		// Make sure TCPDF is available; if not, let the classic engine try.
		if ( function_exists( 'tickera_ticket_designer_ensure_tcpdf' ) && ! tickera_ticket_designer_ensure_tcpdf() ) {
			return null;
		}

		$ticket_data = TC_Ticket_Designer_Fields::resolve_ticket_data( $ticket_instance_id );

		// Mirror the classic generator's output contract (class.ticket_templates.php
		// generate_preview) so non-download callers behave identically. Critically,
		// tickera_maybe_create_temporary_ticket_file() — used to build the PDF
		// e-mail attachment when an order is set to "paid" — sets these filters to
		// WRITE the ticket to a file ('F') and NOT exit. Honoring them stops the
		// designer from streaming the PDF inline + exit()-ing, which otherwise
		// hijacked the admin order-save request (PDF dumped into the browser /
		// "data already output" TCPDF error).
		$code     = isset( $ticket_data['ticket_code'] ) && '' !== $ticket_data['ticket_code'] ? $ticket_data['ticket_code'] : 'ticket';
		$filename = function_exists( 'tickera_apply_filters' )
			? tickera_apply_filters( 'tickera_pdf_ticket_name', (string) $code, $instance ) . '.pdf'
			: 'ticket-' . sanitize_file_name( (string) $code ) . '.pdf';

		if ( $string_attachment ) {
			$output = 'S';
		} elseif ( function_exists( 'tickera_apply_filters' ) ) {
			$output = (string) tickera_apply_filters( 'tickera_change_tcpdf_save_option', ( $force_download ? 'D' : 'I' ) );
		} else {
			$output = $force_download ? 'D' : 'I';
		}

		$pdf = TC_Ticket_Designer_PDF_Generator::generate( $template, $ticket_data, $output, $filename );

		if ( 'S' === $output ) {
			// Email attachment path expects the raw PDF string; on failure fall
			// back to the classic engine by returning null.
			return ( is_string( $pdf ) && '' !== $pdf ) ? $pdf : null;
		}

		// Inline / download that streamed to the browser: exit unless a caller
		// opted out (e.g. temp-file generation sets tickera_exit_after_pdf_output
		// to false).
		if ( 'I' === $output || 'D' === $output ) {
			if ( ! function_exists( 'tickera_apply_filters' ) || tickera_apply_filters( 'tickera_exit_after_pdf_output', true ) ) {
				exit;
			}
		}

		// File ('F') or any non-exiting output: return a non-null value so the
		// classic engine is skipped (the designer already produced the ticket).
		return ( null !== $pdf && '' !== $pdf ) ? $pdf : true;
	}

	/**
	 * Merge designer templates into the existing single "Ticket template" field
	 * instead of adding a second metabox. The field's renderer is swapped for one
	 * that lists both classic (tc_templates) and designer templates in opt-groups.
	 *
	 * @param array $fields Existing ticket type fields.
	 * @return array
	 */
	public function merge_ticket_type_template_field( $fields ) {
		foreach ( $fields as &$field ) {
			if ( isset( $field['field_name'] ) && 'ticket_template' === $field['field_name'] ) {
				$field['field_type'] = 'function';
				$field['function']   = 'tickera_ticket_designer_unified_template_field_select';
				$field['tooltip']    = sprintf(
					/* translators: 1: Ticket Designer URL, 2: classic Ticket Templates URL. */
					__( 'Layout of the ticket the customer downloads. Choose a modern <a href="%1$s" target="_blank">Ticket Designer</a> template or a classic <a href="%2$s" target="_blank">ticket template</a>.', 'tickera-event-ticketing-system' ),
					esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc-ticket-designer' ) ),
					esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_ticket_templates' ) )
				);
				return $fields;
			}
		}
		unset( $field );

		// Fallback: if the core field wasn't found, append our own so the designer
		// templates are still selectable.
		$fields[] = array(
			'field_name'       => 'ticket_template',
			'field_title'      => __( 'Ticket template', 'tickera-event-ticketing-system' ),
			'field_type'       => 'function',
			'function'         => 'tickera_ticket_designer_unified_template_field_select',
			'table_visibility' => false,
			'post_field_type'  => 'post_meta',
			'metabox_context'  => 'side',
		);
		return $fields;
	}

	/**
	 * Split the unified "ticket_template" value back into the two metas the render
	 * engines read: a designer choice ("d_<id>") sets tc_designer_template_id and
	 * neutralises the classic id; a classic choice sets ticket_template and clears
	 * the designer id so the router falls through to the classic engine.
	 *
	 * @param array $metas Meta key/value pairs about to be saved for the ticket type.
	 * @return array
	 */
	public function split_template_meta( $metas ) {
		if ( ! is_array( $metas ) || ! array_key_exists( 'ticket_template', $metas ) ) {
			return $metas;
		}

		$value = (string) $metas['ticket_template'];

		if ( preg_match( '/^d_(\d+)$/', $value, $m ) ) {
			// Designer template chosen.
			$metas['tc_designer_template_id'] = (int) $m[1];
			$metas['ticket_template']         = 0;
		} else {
			// Classic template (or none) chosen.
			$metas['tc_designer_template_id'] = 0;
			$metas['ticket_template']         = (int) $value;
		}

		return $metas;
	}

	/**
	 * Allow <optgroup> inside the custom 'tickera_setting' wp_kses context so the
	 * grouped (Designer / Classic) template dropdown survives sanitisation.
	 *
	 * @param array  $tags    Allowed tags/attributes for the context.
	 * @param string $context wp_kses context being requested.
	 * @return array
	 */
	public function allow_optgroup_in_setting( $tags, $context ) {
		if ( 'tickera_setting' === $context && is_array( $tags ) ) {
			$tags['optgroup'] = array(
				'label'    => true,
				'class'    => true,
				'disabled' => true,
			);
		}
		return $tags;
	}

	/**
	 * Is the classic Ticket Templates UI hidden (deprecated) for this site?
	 *
	 * Hidden when EITHER:
	 *  - a strictly-fresh install stamped tc_legacy_ticket_templates='hidden'
	 *    (see core checkin_api/sales_api), or
	 *  - there are no legacy (classic) templates left at all — once the user has
	 *    deleted them, there is nothing classic to manage, so the menu/page go
	 *    away and old links route to the Ticket Designer.
	 *
	 * Existing sites that still have classic templates default to 'visible'.
	 *
	 * @return bool
	 */
	public static function classic_templates_hidden() {
		if ( 'hidden' === get_option( 'tc_legacy_ticket_templates', 'visible' ) ) {
			return true;
		}
		return ! self::has_classic_templates();
	}

	/**
	 * Remove the legacy "Multipage ticket template" general setting
	 * (ticket_template_auto_pagebreak) once the classic Ticket Templates UI is
	 * hidden. That option only affects the classic PDF engine — the Ticket
	 * Designer handles its own pagination — so it's dead UI for Designer-only
	 * sites and is dropped alongside the classic templates menu.
	 *
	 * @param array $fields Miscellaneous settings fields.
	 * @return array
	 */
	public function maybe_hide_multipage_setting( $fields ) {
		if ( ! is_array( $fields ) || ! self::classic_templates_hidden() ) {
			return $fields;
		}
		foreach ( $fields as $i => $field ) {
			if ( isset( $field['field_name'] ) && 'ticket_template_auto_pagebreak' === $field['field_name'] ) {
				unset( $fields[ $i ] );
			}
		}
		return array_values( $fields );
	}

	/**
	 * Minimum Bridge for WooCommerce version this Tickera release needs for the
	 * Ticket Designer to resolve ticket data and generate the PDF correctly.
	 *
	 * @return string
	 */
	public static function required_bridge_version() {
		return (string) apply_filters( 'tickera_required_bridge_version', '1.7.5' );
	}

	/**
	 * Read the installed Bridge for WooCommerce plugin version from its header
	 * (matched by text domain, so it's independent of the folder name). Bridge's
	 * own $version property is a stale internal value, so the header is the
	 * reliable source.
	 *
	 * @return string Version string, or '' when not found.
	 */
	public static function get_bridge_version() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( get_plugins() as $data ) {
			if ( isset( $data['TextDomain'] ) && 'woocommerce-tickera-bridge' === $data['TextDomain'] ) {
				return isset( $data['Version'] ) ? (string) $data['Version'] : '';
			}
		}
		return '';
	}

	/**
	 * Admin notice: Bridge for WooCommerce is active but older than the version
	 * this Tickera release requires. Without it, Designer tickets bought through
	 * WooCommerce can render empty or break the order-save flow.
	 */
	public function maybe_bridge_version_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Only relevant when Bridge is actually active.
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$bridge_active = (bool) apply_filters( 'tc_bridge_for_woocommerce_is_active', false ) || class_exists( 'TC_WooCommerce_Bridge' );
		if ( ! $bridge_active ) {
			return;
		}

		$required = self::required_bridge_version();
		$current  = self::get_bridge_version();

		// Unknown version, or already up to date → nothing to warn about.
		if ( '' === $current || version_compare( $current, $required, '>=' ) ) {
			return;
		}

		$tc_version = ( isset( $GLOBALS['tc'] ) && isset( $GLOBALS['tc']->version ) ) ? (string) $GLOBALS['tc']->version : '';

		$message = sprintf(
			/* translators: 1: Tickera version, 2: required Bridge version, 3: current Bridge version. */
			__( '<strong>Tickera%1$s requires Bridge for WooCommerce %2$s or higher.</strong> You are running Bridge for WooCommerce %3$s. Please update it, otherwise tickets sold through WooCommerce may render empty or fail to generate.', 'tickera-event-ticketing-system' ),
			$tc_version ? ' ' . esc_html( $tc_version ) : '',
			esc_html( $required ),
			esc_html( $current )
		);

		$update_url = self_admin_url( 'plugins.php' );

		printf(
			'<div class="notice notice-error"><p>%s</p><p><a class="button button-primary" href="%s">%s</a></p></div>',
			wp_kses( $message, array( 'strong' => array() ) ),
			esc_url( $update_url ),
			esc_html__( 'Go to Plugins', 'tickera-event-ticketing-system' )
		);
	}

	/**
	 * Whether the site has any classic (tc_templates) ticket templates.
	 *
	 * @return bool
	 */
	public static function has_classic_templates() {
		$counts = wp_count_posts( 'tc_templates' );
		if ( ! $counts ) {
			return false;
		}
		$total = 0;
		foreach ( array( 'publish', 'private', 'draft', 'pending' ) as $status ) {
			if ( isset( $counts->$status ) ) {
				$total += (int) $counts->$status;
			}
		}
		return $total > 0;
	}

	/**
	 * Remove the classic "Ticket Templates" submenu on fresh installs.
	 */
	public function maybe_hide_classic_templates_menu() {
		if ( ! self::classic_templates_hidden() ) {
			return;
		}
		global $first_tc_menu_handler;
		if ( ! empty( $first_tc_menu_handler ) ) {
			remove_submenu_page( $first_tc_menu_handler, 'tc_ticket_templates' );
		}
	}

	/**
	 * On fresh installs, route the classic Ticket Templates list (and any old
	 * links/bookmarks/tooltips pointing at it) to the Ticket Designer. The PDF
	 * preview path (action=preview) is left alone — the designer renders through
	 * that same page.
	 */
	public function maybe_redirect_classic_templates() {
		if ( ! self::classic_templates_hidden() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'tc_ticket_templates' !== $page ) {
			return;
		}
		// Only redirect the plain list view; preserve preview/edit/delete actions
		// (preview is used by the designer renderer).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing.
		if ( ! empty( $_GET['action'] ) ) {
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=tc-ticket-designer' ) );
		exit;
	}

	/**
	 * Show a deprecation notice on the classic Ticket Templates page for existing
	 * users (the page stays available; the notice nudges them to the Designer).
	 */
	public function classic_templates_deprecation_notice() {
		if ( self::classic_templates_hidden() ) {
			return; // fresh installs are redirected away before this renders.
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		if ( 'tc_ticket_templates' !== $page || 'preview' === $action ) {
			return;
		}
		$url = admin_url( 'admin.php?page=tc-ticket-designer' );
		echo '<div class="notice notice-warning"><p><strong>'
			. esc_html__( 'Ticket Templates is now legacy.', 'tickera-event-ticketing-system' )
			. '</strong> '
			. esc_html__( 'New tickets are built with the modern Ticket Designer. Your existing templates still work, but we recommend designing new ones there.', 'tickera-event-ticketing-system' )
			. ' <a href="' . esc_url( $url ) . '">'
			. esc_html__( 'Open Ticket Designer', 'tickera-event-ticketing-system' )
			. ' &rarr;</a></p></div>';
	}

	/**
	 * Check if tables need to be installed and install them.
	 */
	public function maybe_install() {
		global $wpdb;

		// Check if main table exists.
		$table_name   = $wpdb->prefix . 'tickera_ticket_templates';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

		$version = get_option( 'tc_ticket_designer_version' );

		if ( ! $table_exists || ! $version || version_compare( $version, self::VERSION, '<' ) ) {
			TC_Ticket_Designer_Install::install();
			update_option( 'tc_ticket_designer_version', self::VERSION );
		}

		// Safety net: if the templates list is completely empty (e.g. the user
		// deleted everything), re-seed the bundled "Default Template" so there's
		// always at least one design to start from. Admin requests only.
		if ( is_admin() ) {
			TC_Ticket_Designer_Install::maybe_seed_when_empty();
		}
	}

	/**
	 * Get addon path.
	 *
	 * @param string $file Optional file to append.
	 * @return string
	 */
	public function get_path( $file = '' ) {
		return $this->addon_path . $file;
	}

	/**
	 * Get addon URL.
	 *
	 * @param string $file Optional file to append.
	 * @return string
	 */
	public function get_url( $file = '' ) {
		return $this->addon_url . $file;
	}

	/**
	 * AJAX: Save ticket template.
	 */
	public function ajax_save_ticket_template() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tickera-event-ticketing-system' ) ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		// Admin-only (manage_options) + nonce-checked structured payloads. template_data/settings are
		// JSON (validated via json_decode on use, escaped per-element on render); thumbnail is a data:
		// URL validated before any disk write. A flat-string sanitizer would corrupt the JSON/base64.
		$data      = isset( $_POST['template_data'] ) ? wp_unslash( $_POST['template_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$thumbnail = isset( $_POST['thumbnail'] ) ? wp_unslash( $_POST['thumbnail'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$settings  = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Template name is required.', 'tickera-event-ticketing-system' ) ) );
		}

		$template = new TC_Ticket_Designer_Template( $template_id );
		$template->set( 'name', $name );
		$template->set( 'template_data', $data );
		$template->set( 'settings', $settings );
		$template->set( 'status', 'active' );

		// Save thumbnail if provided.
		if ( ! empty( $thumbnail ) ) {
			$thumbnail_url = $this->save_template_thumbnail( $thumbnail, $template_id ? $template_id : 'new' );
			if ( $thumbnail_url ) {
				$template->set( 'thumbnail_url', $thumbnail_url );
			}
		}

		$saved_id = $template->save();

		if ( $saved_id ) {
			// If this was a new template, rename the thumbnail file.
			if ( ! $template_id && ! empty( $thumbnail ) ) {
				$this->rename_thumbnail( 'new', $saved_id );
				$new_thumbnail_url = $this->get_thumbnail_url( $saved_id );
				if ( $new_thumbnail_url ) {
					$template = new TC_Ticket_Designer_Template( $saved_id );
					$template->set( 'thumbnail_url', $new_thumbnail_url );
					$template->save();
				}
			}

			wp_send_json_success(
				array(
					'template_id' => $saved_id,
					'message'     => __( 'Ticket template saved successfully.', 'tickera-event-ticketing-system' ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save ticket template.', 'tickera-event-ticketing-system' ) ) );
		}
	}

	/**
	 * Save template thumbnail from base64 data.
	 *
	 * @param string $base64_data Base64 encoded image data.
	 * @param mixed  $template_id Template ID or 'new'.
	 * @return string|false URL of saved thumbnail or false on failure.
	 */
	private function save_template_thumbnail( $base64_data, $template_id ) {
		if ( strpos( $base64_data, 'data:image/' ) !== 0 ) {
			return false;
		}

		$parts = explode( ',', $base64_data );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$image_data = base64_decode( $parts[1] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding a data: URI image payload, not obfuscated code.
		if ( ! $image_data ) {
			return false;
		}

		$upload_dir   = wp_upload_dir();
		$template_dir = $upload_dir['basedir'] . '/venuera-ticket-templates';

		if ( ! file_exists( $template_dir ) ) {
			wp_mkdir_p( $template_dir );
			file_put_contents( $template_dir . '/index.php', '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a plugin-generated guard file into the uploads directory.
		}

		// Delete old thumbnails for this template.
		if ( 'new' !== $template_id ) {
			$this->delete_template_thumbnails( $template_id );
		}

		$filename = 'template-' . $template_id . '-' . time() . '.png';
		$filepath = $template_dir . '/' . $filename;

		if ( file_put_contents( $filepath, $image_data ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a plugin-generated thumbnail into the uploads directory.
			return $upload_dir['baseurl'] . '/venuera-ticket-templates/' . $filename;
		}

		return false;
	}

	/**
	 * Delete all thumbnails for a template.
	 *
	 * @param int $template_id Template ID.
	 */
	private function delete_template_thumbnails( $template_id ) {
		$upload_dir   = wp_upload_dir();
		$template_dir = $upload_dir['basedir'] . '/venuera-ticket-templates';

		$files = glob( $template_dir . '/template-' . $template_id . '-*.png' );
		if ( $files ) {
			foreach ( $files as $file ) {
				@unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged -- Removing a temporary local file created by this plugin; WP_Filesystem adds no benefit for transient cleanup.
			}
		}
	}

	/**
	 * Rename thumbnail file from 'new' to actual template ID.
	 *
	 * @param string $old_id Old ID ('new').
	 * @param int    $new_id New template ID.
	 */
	private function rename_thumbnail( $old_id, $new_id ) {
		$upload_dir   = wp_upload_dir();
		$template_dir = $upload_dir['basedir'] . '/venuera-ticket-templates';

		$files = glob( $template_dir . '/template-' . $old_id . '-*.png' );
		if ( ! empty( $files ) ) {
			$old_file = $files[0];
			$new_file = str_replace( 'template-' . $old_id . '-', 'template-' . $new_id . '-', $old_file );
			rename( $old_file, $new_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Atomic rename of a plugin-generated temporary file to its final local path.
		}
	}

	/**
	 * Get thumbnail URL for a template.
	 *
	 * @param int $template_id Template ID.
	 * @return string Thumbnail URL.
	 */
	private function get_thumbnail_url( $template_id ) {
		$upload_dir   = wp_upload_dir();
		$template_dir = $upload_dir['basedir'] . '/venuera-ticket-templates';

		$files = glob( $template_dir . '/template-' . $template_id . '-*.png' );
		if ( ! empty( $files ) ) {
			$filename = basename( $files[0] );
			return $upload_dir['baseurl'] . '/venuera-ticket-templates/' . $filename;
		}

		return '';
	}

	/**
	 * AJAX: Load ticket template.
	 */
	public function ajax_load_ticket_template() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'tickera-event-ticketing-system' ) ) );
		}

		$template = new TC_Ticket_Designer_Template( $template_id );

		if ( ! $template->get_id() ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'tickera-event-ticketing-system' ) ) );
		}

		wp_send_json_success(
			array(
				'template_id'   => $template->get_id(),
				'name'          => $template->get( 'name' ),
				'template_data' => $template->get( 'template_data' ),
				'settings'      => $template->get( 'settings' ),
			)
		);
	}

	/**
	 * AJAX: Delete ticket template.
	 */
	public function ajax_delete_ticket_template() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tickera-event-ticketing-system' ) ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'tickera-event-ticketing-system' ) ) );
		}

		$template = new TC_Ticket_Designer_Template( $template_id );

		if ( $template->delete() ) {
			$this->delete_template_thumbnails( $template_id );
			wp_send_json_success( array( 'message' => __( 'Ticket template deleted.', 'tickera-event-ticketing-system' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete ticket template.', 'tickera-event-ticketing-system' ) ) );
		}
	}

	/**
	 * AJAX: Duplicate ticket template.
	 *
	 * Clones the template's design + settings into a new "(Copy)" template and
	 * copies its thumbnail so the duplicate shows the same preview immediately.
	 */
	public function ajax_duplicate_ticket_template() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tickera-event-ticketing-system' ) ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'tickera-event-ticketing-system' ) ) );
		}

		$source = new TC_Ticket_Designer_Template( $template_id );

		if ( ! $source->get_id() ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'tickera-event-ticketing-system' ) ) );
		}

		$copy = $source->duplicate();

		if ( ! $copy || ! $copy->get_id() ) {
			wp_send_json_error( array( 'message' => __( 'Failed to duplicate ticket template.', 'tickera-event-ticketing-system' ) ) );
		}

		// Copy the source thumbnail so the duplicate card shows a preview right away.
		$source_thumb = $source->get( 'thumbnail_url' );
		if ( $source_thumb ) {
			$copied_url = $this->copy_template_thumbnail( $source_thumb, $copy->get_id() );
			if ( $copied_url ) {
				$copy->set( 'thumbnail_url', $copied_url );
				$copy->save();
			}
		}

		wp_send_json_success(
			array(
				'template_id' => $copy->get_id(),
				'message'     => __( 'Ticket template duplicated.', 'tickera-event-ticketing-system' ),
			)
		);
	}

	/**
	 * Copy an existing template thumbnail file for a duplicated template.
	 *
	 * @param string $source_url  Source thumbnail URL.
	 * @param int    $new_id      New template ID.
	 * @return string|false New thumbnail URL or false on failure.
	 */
	private function copy_template_thumbnail( $source_url, $new_id ) {
		$upload_dir   = wp_upload_dir();
		$template_dir = $upload_dir['basedir'] . '/venuera-ticket-templates';

		// Resolve the source file from its URL.
		if ( 0 !== strpos( $source_url, $upload_dir['baseurl'] ) ) {
			return false;
		}
		$source_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $source_url );
		if ( ! file_exists( $source_path ) ) {
			return false;
		}

		if ( ! file_exists( $template_dir ) ) {
			wp_mkdir_p( $template_dir );
			file_put_contents( $template_dir . '/index.php', '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a plugin-generated guard file into the uploads directory.
		}

		$filename = 'template-' . $new_id . '-' . time() . '.png';
		$dest     = $template_dir . '/' . $filename;

		if ( copy( $source_path, $dest ) ) {
			return $upload_dir['baseurl'] . '/venuera-ticket-templates/' . $filename;
		}

		return false;
	}

	/**
	 * AJAX: Get custom attendee fields for a product and/or event.
	 *
	 * Accepts `product_id` and/or `event_id` via POST.
	 *  - When `product_id` is given, returns that product's attendee fields.
	 *  - When `event_id` is given, returns the union of attendee fields across
	 *    every event_ticket product linked to that event.
	 *
	 * Each returned field's `id` equals the dataField key the renderer resolves
	 * (`attendee_field_<fieldId>`), so it matches the stored attendee data at
	 * `_venuera_attendee_data[...][<fieldId>]` => `attendee_field_<fieldId>`.
	 */
	public function ajax_get_custom_fields() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tickera-event-ticketing-system' ) ) );
		}

		// Accept product_id (preferred) and keep ticket_type_id as a back-compat alias.
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! $product_id && isset( $_POST['ticket_type_id'] ) ) {
			$product_id = absint( wp_unslash( $_POST['ticket_type_id'] ) );
		}
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! class_exists( 'Venuera_Attendee_Fields' ) ) {
			wp_send_json_success( array( 'fields' => array() ) );
		}

		// Collect the product ids whose attendee fields we should gather.
		$product_ids = array();

		if ( $product_id ) {
			$product_ids[] = $product_id;
		} elseif ( $event_id ) {
			// Union the fields of all event_ticket products linked to the event.
			$linked = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'     => '_event_id',
							'value'   => $event_id,
							'compare' => '=',
						),
					),
				)
			);

			if ( is_array( $linked ) ) {
				$product_ids = array_map( 'absint', $linked );
			}
		}

		// Gather and de-duplicate attendee fields by their field id.
		$fields = array();
		$seen   = array();

		foreach ( $product_ids as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product ) {
				continue;
			}

			$attendee_fields = Venuera_Attendee_Fields::get_product_attendee_fields( $product );

			if ( ! is_array( $attendee_fields ) ) {
				continue;
			}

			foreach ( $attendee_fields as $field ) {
				if ( ! is_array( $field ) || empty( $field['id'] ) ) {
					continue;
				}

				$field_id = (string) $field['id'];

				if ( isset( $seen[ $field_id ] ) ) {
					continue;
				}
				$seen[ $field_id ] = true;

				$label = isset( $field['label'] ) && '' !== $field['label']
					? (string) $field['label']
					: $field_id;

				$fields[] = array(
					'id'    => 'attendee_field_' . $field_id,
					'label' => $label,
				);
			}
		}

		wp_send_json_success( array( 'fields' => $fields ) );
	}

	/**
	 * AJAX: Preview ticket.
	 */
	public function ajax_preview_ticket() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );

		// Nonce-checked structured JSON used only to render a transient preview (json_decoded, escaped
		// on render, never persisted). A flat-string sanitizer would corrupt the JSON.
		$template_data = isset( $_POST['template_data'] ) ? wp_unslash( $_POST['template_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$settings      = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$preview_data  = isset( $_POST['preview_data'] ) ? wp_unslash( $_POST['preview_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Create sample data for preview (shared source so the preview matches
		// the editor canvas and the PDF output exactly).
		$sample_data = TC_Ticket_Designer_Element::get_sample_data();

		if ( ! empty( $preview_data ) ) {
			$preview_data = json_decode( $preview_data, true );
			if ( is_array( $preview_data ) ) {
				$sample_data = array_merge( $sample_data, $preview_data );
			}
		}

		wp_send_json_success(
			array(
				'preview_data' => $sample_data,
			)
		);
	}

	/**
	 * AJAX: Generate PDF preview.
	 */
	public function ajax_preview_pdf() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tickera-event-ticketing-system' ) ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		if ( ! $template_id ) {
			wp_send_json_error( array( 'message' => __( 'Template ID required.', 'tickera-event-ticketing-system' ) ) );
		}

		$template = new TC_Ticket_Designer_Template( $template_id );

		if ( ! $template->get_id() ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'tickera-event-ticketing-system' ) ) );
		}

		// Sample data for preview (shared source so the PDF matches the editor
		// canvas and the HTML preview exactly).
		$sample_data = TC_Ticket_Designer_Element::get_sample_data();

		// Generate PDF.
		$pdf_content = TC_Ticket_Designer_PDF_Generator::generate( $template, $sample_data, 'S' );

		if ( $pdf_content ) {
			// Return base64 encoded PDF.
			wp_send_json_success(
				array(
					'pdf'      => base64_encode( $pdf_content ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoding binary PDF content for a JSON response.
					'filename' => 'ticket-preview.pdf',
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to generate PDF. Make sure TCPDF is installed (run composer install).', 'tickera-event-ticketing-system' ) ) );
		}
	}

	/**
	 * AJAX: return the list of bundled ready-made starter templates.
	 *
	 * Each entry: { slug, name, description, niche, width, height,
	 *               thumbnail_url } — the editor's right sidebar uses this
	 * to render a thumbnail gallery.
	 */
	public function ajax_list_ready_templates() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tickera-event-ticketing-system' ) ) );
		}
		wp_send_json_success(
			array(
				'templates' => self::get_ready_made_templates(),
			)
		);
	}

	/**
	 * AJAX: return the full JSON definition of a single ready-made template
	 * (so the editor can load its width/height/background + elements).
	 */
	public function ajax_get_ready_template() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tickera-event-ticketing-system' ) ) );
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$data = self::load_ready_made_template( $slug );

		if ( ! $data ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'tickera-event-ticketing-system' ) ) );
		}
		wp_send_json_success( $data );
	}

	/**
	 * AJAX: overwrite a ready-made template's .json file on disk with the
	 * editor's current canvas state. Admin-only — this is an iteration tool
	 * used while building the bundled template library, not exposed to
	 * non-admin operators.
	 *
	 * Re-tokenises every image src that points at the bundled assets folder
	 * back to the literal {{ASSETS}} placeholder so the saved file stays
	 * installation-independent (the plugin can live at any URL).
	 */
	public function ajax_save_ready_template() {
		check_ajax_referer( 'tc_ticket_designer_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tickera-event-ticketing-system' ) ) );
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		if ( ! $slug ) {
			wp_send_json_error( array( 'message' => __( 'Missing template slug.', 'tickera-event-ticketing-system' ) ) );
		}

		// A source definition MUST already exist (either the bundled, read-only
		// copy in the plugin folder OR a previously user-saved copy in uploads).
		// We never create brand-new ready-made templates from this endpoint, only
		// iterate on existing ones. Writes always target the uploads directory —
		// the bundled plugin folder is never written to (wp.org compliance).
		$source_path = self::ready_template_source_path( $slug );
		if ( ! $source_path ) {
			wp_send_json_error( array( 'message' => __( 'Template file not found on disk.', 'tickera-event-ticketing-system' ) ) );
		}

		// Destination is always the writable uploads directory.
		$dest_dir = self::ready_templates_upload_dir();
		if ( ! $dest_dir ) {
			wp_send_json_error( array( 'message' => __( 'Could not prepare the templates directory.', 'tickera-event-ticketing-system' ) ) );
		}
		$path = $dest_dir . '/' . $slug . '.json';

		$payload_raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce-checked JSON payload, type-checked and json_decoded below.
		if ( ! is_string( $payload_raw ) || '' === $payload_raw ) {
			wp_send_json_error( array( 'message' => __( 'Missing payload.', 'tickera-event-ticketing-system' ) ) );
		}

		$payload = json_decode( $payload_raw, true );
		if ( ! is_array( $payload ) || empty( $payload['templateData'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid payload — expected wrapped templateData.', 'tickera-event-ticketing-system' ) ) );
		}

		// Preserve the original file's identity fields (name, slug, niche,
		// description) so the user can iterate on visuals without losing
		// metadata. The payload's values win only when explicitly provided.
		$existing_raw = @file_get_contents( $source_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local plugin-bundled/uploaded JSON file; failure is handled on the next line.
		$existing     = is_string( $existing_raw ) ? json_decode( $existing_raw, true ) : null;
		if ( ! is_array( $existing ) ) {
			$existing = array(); }

		$out = array(
			'version'      => isset( $payload['version'] ) ? $payload['version'] : ( isset( $existing['version'] ) ? $existing['version'] : '1.0' ),
			'name'         => isset( $payload['name'] ) && '' !== $payload['name']
								? sanitize_text_field( $payload['name'] )
								: ( isset( $existing['name'] ) ? $existing['name'] : $slug ),
			'slug'         => $slug,
			'description'  => isset( $payload['description'] ) ? sanitize_text_field( $payload['description'] ) : ( isset( $existing['description'] ) ? $existing['description'] : '' ),
			'niche'        => isset( $payload['niche'] ) ? sanitize_text_field( $payload['niche'] ) : ( isset( $existing['niche'] ) ? $existing['niche'] : '' ),
			'templateData' => $payload['templateData'],
			'settings'     => isset( $payload['settings'] ) && is_array( $payload['settings'] ) ? $payload['settings'] : ( isset( $existing['settings'] ) && is_array( $existing['settings'] ) ? $existing['settings'] : array(
				'orientation' => isset( $payload['templateData']['width'], $payload['templateData']['height'] ) && $payload['templateData']['width'] > $payload['templateData']['height'] ? 'landscape' : 'portrait',
				'size'        => 'custom',
				'width'       => isset( $payload['templateData']['width'] ) ? floatval( $payload['templateData']['width'] ) : 0,
				'height'      => isset( $payload['templateData']['height'] ) ? floatval( $payload['templateData']['height'] ) : 0,
				'unit'        => 'pt',
			) ),
		);

		// Encode to pretty JSON for human-readable diffs.
		$json = wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $json ) {
			wp_send_json_error( array( 'message' => __( 'Failed to encode JSON.', 'tickera-event-ticketing-system' ) ) );
		}

		// Re-tokenise image src URLs back to {{ASSETS}} so the saved file
		// is portable across installs. We do this in the JSON string (not
		// the array) so it covers nested src/background fields uniformly.
		$assets_url = rtrim( TC_TICKET_DESIGNER_PARENT_URL, '/' ) . '/includes/addons/ticket-designer/templates';
		$variants   = array_unique( array( $assets_url, esc_url_raw( $assets_url ) ) );
		foreach ( $variants as $variant ) {
			// Escape forward slashes the same way wp_json_encode did NOT
			// (we passed UNESCAPED_SLASHES) so a plain str_replace catches it.
			$json = str_replace( $variant, '{{ASSETS}}', $json );
		}

		// Atomic write: stage to a temp file in the same directory then
		// rename, so a crashed write doesn't half-corrupt the source.
		$tmp = $path . '.tmp-' . wp_generate_password( 6, false );
		if ( false === file_put_contents( $tmp, $json ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Staging a plugin-generated temp file for an atomic rename; failure is handled here.
			wp_send_json_error( array( 'message' => __( 'Failed to write file (permissions?).', 'tickera-event-ticketing-system' ) ) );
		}
		if ( ! @rename( $tmp, $path ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename, WordPress.PHP.NoSilencedErrors.Discouraged -- Atomic rename of plugin-generated temp file.
			@unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged -- Removing a temporary local file created by this plugin; WP_Filesystem adds no benefit for transient cleanup.
			wp_send_json_error( array( 'message' => __( 'Failed to replace template file.', 'tickera-event-ticketing-system' ) ) );
		}

		wp_send_json_success(
			array(
				/* translators: placeholders are dynamic values. */
				'message' => sprintf( __( 'Replaced %s.json on disk.', 'tickera-event-ticketing-system' ), $slug ),
				'slug'    => $slug,
				'bytes'   => strlen( $json ),
			)
		);
	}

	/**
	 * Scan the templates/ folder for ready-made starter templates and
	 * return a metadata list (without the heavy elements array).
	 *
	 * @return array
	 */
	public static function get_ready_made_templates() {
		// Read from BOTH the bundled (shipped, read-only) plugin folder and the
		// writable uploads folder where user-iterated copies are saved. When a
		// slug exists in both, the uploads copy wins (it is the user's edit).
		$files = array();
		foreach ( array( self::ready_templates_dir(), self::ready_templates_upload_dir( false ) ) as $dir ) {
			if ( ! $dir || ! is_dir( $dir ) ) {
				continue;
			}
			$found = glob( $dir . '/*.json' );
			if ( $found ) {
				foreach ( $found as $file ) {
					$slug_key           = pathinfo( $file, PATHINFO_FILENAME );
					$files[ $slug_key ] = $file; // Later dir (uploads) overrides earlier (bundled).
				}
			}
		}
		if ( ! $files ) {
			return array();
		}
		$assets_url = rtrim( TC_TICKET_DESIGNER_PARENT_URL, '/' ) . '/includes/addons/ticket-designer/templates';
		$out        = array();
		foreach ( $files as $file ) {
			$raw = @file_get_contents( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local plugin-bundled JSON file; failure is handled on the next line.
			if ( ! $raw ) {
				continue; }
			// Resolve {{ASSETS}} → plugin URL so the sidebar previews load
			// bundled images instead of attempting to fetch the literal
			// token (which would 404 + ruin the preview render).
			$raw  = str_replace( '{{ASSETS}}', esc_url_raw( $assets_url ), $raw );
			$json = json_decode( $raw, true );
			if ( ! is_array( $json ) ) {
				continue; }
			$slug  = isset( $json['slug'] ) ? sanitize_key( $json['slug'] ) : sanitize_key( pathinfo( $file, PATHINFO_FILENAME ) );
			$tdata = isset( $json['templateData'] ) && is_array( $json['templateData'] ) ? $json['templateData'] : array();
			$out[] = array(
				'slug'          => $slug,
				'name'          => isset( $json['name'] ) ? sanitize_text_field( $json['name'] ) : $slug,
				'description'   => isset( $json['description'] ) ? sanitize_text_field( $json['description'] ) : '',
				'niche'         => isset( $json['niche'] ) ? sanitize_text_field( $json['niche'] ) : '',
				'width'         => isset( $tdata['width'] ) ? floatval( $tdata['width'] ) : 0,
				'height'        => isset( $tdata['height'] ) ? floatval( $tdata['height'] ) : 0,
				'background'    => isset( $tdata['background'] ) ? sanitize_text_field( $tdata['background'] ) : '#ffffff',
				// Full elements array so the sidebar can render a live SVG
				// preview of the template — fast since only 4-6 templates
				// ship by default and each .json is < 6 KB.
				'elements'      => isset( $tdata['elements'] ) && is_array( $tdata['elements'] ) ? $tdata['elements'] : array(),
				'thumbnail_url' => self::ready_template_thumbnail_url( $slug ),
			);
		}
		// Sort by name for stable order in the UI.
		usort(
			$out,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);
		return $out;
	}

	/**
	 * Load a single ready-made template's full JSON (including the
	 * elements array) by slug. Returns null if not found.
	 *
	 * Resolves the {{ASSETS}} placeholder in element src strings to the
	 * plugin's templates folder URL so bundled images (pool backgrounds,
	 * etc.) load via real http(s) URLs without hard-coding the install
	 * path in the JSON.
	 *
	 * @param string $slug Template slug.
	 * @return array|null
	 */
	public static function load_ready_made_template( $slug ) {
		$slug = sanitize_key( $slug );
		if ( ! $slug ) {
			return null; }
		$path = self::ready_template_source_path( $slug );
		if ( ! $path ) {
			return null; }
		$raw = @file_get_contents( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local plugin-bundled/uploaded JSON file; failure is handled on the next line.
		if ( ! $raw ) {
			return null; }
		// Rewrite the {{ASSETS}} token to the actual plugin URL before
		// json_decode so any string field (image src, background image, …)
		// ends up pointing at the bundled asset.
		$assets_url = rtrim( TC_TICKET_DESIGNER_PARENT_URL, '/' ) . '/includes/addons/ticket-designer/templates';
		$raw        = str_replace( '{{ASSETS}}', esc_url_raw( $assets_url ), $raw );
		$json       = json_decode( $raw, true );
		if ( ! is_array( $json ) ) {
			return null; }
		return $json;
	}

	/**
	 * Absolute filesystem path to the ready-made templates folder.
	 */
	private static function ready_templates_dir() {
		return TC_TICKET_DESIGNER_PARENT_DIR . 'includes/addons/ticket-designer/templates';
	}

	/**
	 * Absolute filesystem path to the writable uploads folder used for
	 * user-saved/iterated ready-made templates. The bundled plugin folder is
	 * read-only (wp.org forbids writing into the plugin directory), so all
	 * writes go here — the same uploads folder used for template thumbnails.
	 *
	 * @param bool $create When true (default) the directory (and its index.php
	 *                     guard) is created if missing. Pass false to merely
	 *                     resolve the path for reading.
	 * @return string|false Absolute path, or false if it could not be prepared.
	 */
	private static function ready_templates_upload_dir( $create = true ) {
		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['basedir'] ) ) {
			return false;
		}
		$dir = trailingslashit( $upload_dir['basedir'] ) . 'venuera-ticket-templates';

		if ( $create && ! is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return false;
			}
			// Silence directory listing, matching the thumbnail folder guard.
			$index = $dir . '/index.php';
			if ( ! file_exists( $index ) ) {
				file_put_contents( $index, '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a plugin-generated guard file into the uploads directory.
			}
		}

		return $dir;
	}

	/**
	 * Resolve the source .json path for a ready-made template slug, preferring a
	 * user-saved copy in uploads over the bundled (shipped) plugin copy.
	 *
	 * @param string $slug Template slug.
	 * @return string|false Absolute path to an existing file, or false.
	 */
	private static function ready_template_source_path( $slug ) {
		$slug = sanitize_key( $slug );
		if ( ! $slug ) {
			return false;
		}
		$upload_dir = self::ready_templates_upload_dir( false );
		if ( $upload_dir ) {
			$candidate = $upload_dir . '/' . $slug . '.json';
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}
		$bundled = self::ready_templates_dir() . '/' . $slug . '.json';
		if ( file_exists( $bundled ) ) {
			return $bundled;
		}
		return false;
	}

	/**
	 * Thumbnail URL for a ready-made template, or empty string when there
	 * isn't one bundled (the JS renderer will draw an on-the-fly preview
	 * from the template's elements instead).
	 *
	 * @param string $slug Template slug.
	 * @return string
	 */
	private static function ready_template_thumbnail_url( $slug ) {
		$thumb_rel = 'includes/addons/ticket-designer/templates/' . $slug . '.png';
		$thumb_abs = TC_TICKET_DESIGNER_PARENT_DIR . $thumb_rel;
		if ( file_exists( $thumb_abs ) ) {
			return TC_TICKET_DESIGNER_PARENT_URL . $thumb_rel;
		}
		return '';
	}

	/**
	 * Get available element types for the ticket designer.
	 *
	 * @return array
	 */
	public static function get_element_types() {
		return TC_Ticket_Designer_Element::get_types();
	}

	/**
	 * Get available fonts for ticket designer.
	 *
	 * @return array
	 */
	public static function get_available_fonts() {
		// Core PDF fonts + the bundled Google font library (embedded in the PDF
		// via TCPDF, loaded in the editor via @font-face — same files, so the PDF
		// matches the editor 1:1).
		return TC_Ticket_Designer_Fonts::get_choices();
	}
}

/**
 * Initialize the addon.
 *
 * @return TC_Ticket_Designer
 */
if ( ! function_exists( 'tickera_ticket_designer' ) ) {

  function tickera_ticket_designer() {
    // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed -- Bootstrap accessor function intentionally colocated with the addon class.
    return TC_Ticket_Designer::instance();
  }
}

/**
 * Render the ticket-type "Ticket Designer Template" <select> (field callback).
 *
 * Called by Tickera's ticket-type field API ('field_type' => 'function'). The
 * stored value is the row id in {prefix}tickera_ticket_templates; 0 = classic.
 *
 * @param string $field_name     Meta field name (tc_designer_template_id).
 * @param int    $ticket_type_id Ticket type post id currently being edited.
 * @return void
 */
function tickera_ticket_designer_template_field_select( $field_name, $ticket_type_id = 0 ) {
	global $wpdb;

	$selected = $ticket_type_id ? (int) get_post_meta( $ticket_type_id, $field_name, true ) : 0;

	$rows  = array();
	$table = $wpdb->prefix . 'tickera_ticket_templates';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name built from $wpdb->prefix.
		$rows = $wpdb->get_results( "SELECT id, name FROM {$table} WHERE status = 'active' ORDER BY name ASC" );
	}

	echo '<select name="' . esc_attr( $field_name ) . '_post_meta">';
	echo '<option value="0"' . selected( 0, $selected, false ) . '>' . esc_html__( 'Classic template', 'tickera-event-ticketing-system' ) . '</option>';
	if ( $rows ) {
		foreach ( $rows as $row ) {
			echo '<option value="' . (int) $row->id . '"' . selected( (int) $row->id, $selected, false ) . '>' . esc_html( $row->name ) . '</option>';
		}
	}
	echo '</select>';
}

/**
 * Render the unified ticket-type "Ticket template" <select>: classic templates
 * (tc_templates CPT) and Ticket Designer templates (custom table) in one control,
 * grouped by opt-group. Designer options carry a "d_" prefix so the save handler
 * (split_template_meta) can route the value to the correct meta key.
 *
 * @param string $field_name     Meta field name (ticket_template).
 * @param int    $ticket_type_id Ticket type post id currently being edited.
 * @return void
 */
function tickera_ticket_designer_unified_template_field_select( $field_name, $ticket_type_id = 0 ) {
	global $wpdb;

	$designer_id = $ticket_type_id ? (int) get_post_meta( $ticket_type_id, 'tc_designer_template_id', true ) : 0;
	$classic_id  = $ticket_type_id ? (int) get_post_meta( $ticket_type_id, 'ticket_template', true ) : 0;
	$selected    = $designer_id > 0 ? 'd_' . $designer_id : (string) $classic_id;

	// Designer templates (custom table).
	$designer_rows = array();
	$table         = $wpdb->prefix . 'tickera_ticket_templates';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name built from $wpdb->prefix.
		$designer_rows = $wpdb->get_results( "SELECT id, name FROM {$table} WHERE status = 'active' ORDER BY name ASC" );
	}

	// Classic templates (tc_templates CPT).
	$classic = array();
	if ( class_exists( '\Tickera\TC_Templates_Search' ) ) {
		$search = new \Tickera\TC_Templates_Search( '', '', -1 );
		foreach ( $search->get_results() as $tpl ) {
			$classic[ (int) $tpl->ID ] = $tpl->post_title;
		}
	}

	echo '<select name="' . esc_attr( $field_name ) . '_post_meta" class="tc-template-select">';
	echo '<option value="0"' . selected( '0', $selected, false ) . '>' . esc_html__( '— None —', 'tickera-event-ticketing-system' ) . '</option>';

	if ( $designer_rows ) {
		echo '<optgroup label="' . esc_attr__( 'Ticket Designer', 'tickera-event-ticketing-system' ) . '">';
		foreach ( $designer_rows as $row ) {
			$val = 'd_' . (int) $row->id;
			echo '<option value="' . esc_attr( $val ) . '"' . selected( $val, $selected, false ) . '>' . esc_html( $row->name ) . '</option>';
		}
		echo '</optgroup>';
	}

	// Legacy (classic) templates — only shown when the site actually has some.
	if ( $classic ) {
		echo '<optgroup label="' . esc_attr__( 'Legacy Templates', 'tickera-event-ticketing-system' ) . '">';
		foreach ( $classic as $id => $title ) {
			echo '<option value="' . (int) $id . '"' . selected( (string) $id, $selected, false ) . '>' . esc_html( $title ) . '</option>';
		}
		echo '</optgroup>';
	}
	echo '</select>';

	// Preview link for an existing ticket type. The dispatcher (generate_pdf_ticket)
	// requires BOTH template_id and ticket_type_id; for a designer template we pass
	// template_id=0 (the router resolves the designer template from the type),
	// otherwise the classic template id so the classic engine renders it.
	$tid = (int) $ticket_type_id;
	if ( $tid ) {
		$preview_template_id = $designer_id > 0 ? 0 : (int) $classic_id;
		$preview             = admin_url( 'edit.php?post_type=tc_events&page=tc_ticket_templates&action=preview&ticket_type_id=' . $tid . '&template_id=' . $preview_template_id );
		echo ' <a class="ticket_preview_link" target="_blank" href="' . esc_url( tickera_apply_filters( 'tickera_ticket_preview_link', $preview ) ) . '">' . esc_html__( 'Preview', 'tickera-event-ticketing-system' ) . '</a>';
	}
}

/**
 * Flat [value => label] list of all ticket templates — Ticket Designer templates
 * (value "d_<id>") and classic/legacy templates (value "<post_id>"). Used by
 * plain selects that can't render opt-groups (e.g. WooCommerce's
 * woocommerce_wp_select on the Bridge product panel).
 *
 * @return array
 */
function tickera_ticket_designer_merged_templates_array() {
	global $wpdb;

	$out = array( '' => __( '— None —', 'tickera-event-ticketing-system' ) );

	$table = $wpdb->prefix . 'tickera_ticket_templates';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
		$designer = $wpdb->get_results( "SELECT id, name FROM {$table} WHERE status = 'active' ORDER BY name ASC" );
		foreach ( (array) $designer as $row ) {
			$out[ 'd_' . (int) $row->id ] = $row->name;
		}
	}

	if ( class_exists( '\Tickera\TC_Templates_Search' ) ) {
		$search = new \Tickera\TC_Templates_Search( '', '', -1 );
		foreach ( $search->get_results() as $tpl ) {
			$out[ (int) $tpl->ID ] = $tpl->post_title . ' (' . __( 'Legacy', 'tickera-event-ticketing-system' ) . ')';
		}
	}

	return $out;
}

/**
 * Resolve the currently-selected unified template value for a post that stores
 * the split metas (tc_designer_template_id + a classic-template meta key).
 *
 * @param int    $post_id          Post/product id.
 * @param string $classic_meta_key Meta key holding the classic template id.
 * @return string "d_<id>" for a designer template, the classic id, or ''.
 */
function tickera_ticket_designer_selected_template_value( $post_id, $classic_meta_key = 'ticket_template' ) {
	$designer_id = (int) get_post_meta( $post_id, 'tc_designer_template_id', true );
	if ( $designer_id > 0 ) {
		return 'd_' . $designer_id;
	}
	$classic_id = (int) get_post_meta( $post_id, $classic_meta_key, true );
	return $classic_id ? (string) $classic_id : '';
}

/**
 * Split a unified template value into the two metas the engines read, for a
 * given post/product. Mirrors split_template_meta() but writes directly.
 *
 * @param int    $post_id          Post/product id.
 * @param string $value            Submitted value ("d_<id>" or a classic id).
 * @param string $classic_meta_key Meta key for the classic template id.
 * @return void
 */
function tickera_ticket_designer_save_template_value( $post_id, $value, $classic_meta_key = 'ticket_template' ) {
	$value = (string) $value;
	if ( preg_match( '/^d_(\d+)$/', $value, $m ) ) {
		update_post_meta( $post_id, 'tc_designer_template_id', (int) $m[1] );
		update_post_meta( $post_id, $classic_meta_key, 0 );
	} else {
		update_post_meta( $post_id, 'tc_designer_template_id', 0 );
		update_post_meta( $post_id, $classic_meta_key, (int) $value );
	}
}

// Initialize.
tickera_ticket_designer();
