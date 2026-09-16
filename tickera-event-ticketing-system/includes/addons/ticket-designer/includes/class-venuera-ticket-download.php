<?php
/**
 * Secure Ticket Download Handler
 *
 * Handles secure PDF ticket generation and download.
 * PDFs are generated on-the-fly, not stored on disk.
 *
 * @package Venuera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {

// phpcs:disable Squiz.PHP.DiscouragedFunctions.Discouraged, WordPress.Security.NonceVerification.Recommended -- Venuera custom-table data access:
	// ini_set('display_errors','0') is used defensively so a stray PHP notice cannot corrupt the binary PDF stream; the download link is nonce-protected and the read-only $_GET flags only toggle inline/attachment.

	exit;
}

/**
 * Ticket Download class.
 */
class TC_Ticket_Designer_Download {

	/**
	 * Token expiration time in seconds (24 hours).
	 */
	const TOKEN_EXPIRY = 86400;

	/**
	 * Whether a product is a Venuera ticket product (simple, variable, or a
	 * variation of a variable ticket). Order line items for variable tickets
	 * carry the variation product (type `event_ticket_variation`), so all three
	 * types must be recognised for download buttons to appear.
	 *
	 * @param WC_Product|null $product Product object.
	 * @return bool
	 */
	private static function is_ticket_product( $product ) {
		if ( ! $product ) {
			return false;
		}

		return in_array(
			$product->get_type(),
			array( 'event_ticket', 'event_ticket_variable', 'event_ticket_variation' ),
			true
		);
	}

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		// Public download endpoint (no login required, but token verified).
		add_action( 'init', array( __CLASS__, 'handle_download_request' ) );

		// POS integration: supply printable ticket links for an order.
		add_filter( 'venuera_pos_order_tickets', array( __CLASS__, 'pos_order_tickets' ), 10, 2 );
		// POS integration: a single combined PDF with ALL tickets of the order.
		add_filter( 'venuera_pos_order_tickets_pdf', array( __CLASS__, 'pos_order_tickets_pdf' ), 10, 2 );

		// POS receipt integration — injects each purchased ticket's base data
		// (barcode, QR, event name, date/time, attendee name/email/phone) into
		// the receipt payload. Seat data and custom attendee fields are added by
		// their owning add-ons (Venue Designer / Attendee Fields) via the
		// `venuera_pos_receipt_ticket_data` filter applied here.
		// When Venuera is inactive none of this is hooked, so the POS stays a
		// pure retail receipt engine.
		add_filter( 'venuera_pos_receipt_payload', array( __CLASS__, 'pos_receipt_payload' ), 10, 2 );

		// The "Tickets" receipt block itself is owned by Venuera (not the POS):
		// register the block type, its renderer, the code (QR/barcode) generators
		// and the example preview data through the POS extension filters.
		add_filter( 'venuera_pos_receipt_block_types', array( __CLASS__, 'register_tickets_block' ) );
		add_filter( 'venuera_pos_receipt_default_template', array( __CLASS__, 'inject_tickets_into_default_template' ) );
		add_filter( 'venuera_pos_receipt_qr_svg', array( __CLASS__, 'filter_receipt_qr_svg' ), 10, 2 );
		add_filter( 'venuera_pos_receipt_barcode_svg', array( __CLASS__, 'filter_receipt_barcode_svg' ), 10, 2 );
		add_filter( 'venuera_pos_receipt_example_payload', array( __CLASS__, 'pos_receipt_example_payload' ) );

		// Order details page - after order table. This consolidated "Your Tickets"
		// section is the single per-ticket delivery UI (download button or QR per the
		// venuera_order_ticket_delivery setting); the old per-item link is no longer
		// registered to avoid duplicating it under each line item.
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_order_tickets_section' ) );

		// Admin order page - download link.
		add_action( 'woocommerce_admin_order_item_headers', array( __CLASS__, 'admin_order_item_header' ) );
		add_action( 'woocommerce_admin_order_item_values', array( __CLASS__, 'admin_order_item_value' ), 10, 3 );
	}

	/**
	 * Generate secure download token.
	 *
	 * Token = base64(ticket_id:timestamp:signature)
	 * Signature = hash(ticket_id + timestamp + order_id + secret_key)
	 *
	 * @param int $ticket_id   Ticket ID.
	 * @param int $order_id    Order ID.
	 * @param int $order_item_id Order item ID.
	 * @return string Secure token.
	 */
	public static function generate_token( $ticket_id, $order_id, $order_item_id ) {
		$timestamp = time();
		$secret    = self::get_secret_key();

		// Create signature.
		$data      = $ticket_id . ':' . $order_id . ':' . $order_item_id . ':' . $timestamp;
		$signature = hash_hmac( 'sha256', $data, $secret );

		// Encode token.
		$token = base64_encode( $ticket_id . ':' . $order_id . ':' . $order_item_id . ':' . $timestamp . ':' . $signature ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoding an HMAC-signed download token for use in a URL.

		return $token;
	}

	/**
	 * Verify and decode download token.
	 *
	 * @param string $token Token to verify.
	 * @return array|false Array with ticket_id, order_id, order_item_id or false if invalid.
	 */
	public static function verify_token( $token ) {
		$decoded = base64_decode( $token ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding an HMAC-signed download token, not obfuscated code.
		if ( ! $decoded ) {
			return false;
		}

		$parts = explode( ':', $decoded );
		if ( count( $parts ) !== 5 ) {
			return false;
		}

		list( $ticket_id, $order_id, $order_item_id, $timestamp, $signature ) = $parts;

		// Check expiration.
		if ( time() - intval( $timestamp ) > self::TOKEN_EXPIRY ) {
			return false;
		}

		// Verify signature.
		$secret             = self::get_secret_key();
		$data               = $ticket_id . ':' . $order_id . ':' . $order_item_id . ':' . $timestamp;
		$expected_signature = hash_hmac( 'sha256', $data, $secret );

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return false;
		}

		// Keep ticket_id as-is: real tickets use a non-numeric UID string as the
		// identifier, while legacy orders use a synthetic "TKT-..." string. Casting
		// to int would destroy both, so only order ids are coerced to integers.
		return array(
			'ticket_id'     => $ticket_id,
			'order_id'      => intval( $order_id ),
			'order_item_id' => intval( $order_item_id ),
		);
	}

	/**
	 * Get or generate secret key.
	 *
	 * @return string Secret key.
	 */
	private static function get_secret_key() {
		$secret = get_option( 'venuera_download_secret' );

		if ( ! $secret ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( 'venuera_download_secret', $secret );
		}

		return $secret;
	}

	/**
	 * Generate secure download URL.
	 *
	 * @param int $ticket_id    Ticket ID.
	 * @param int $order_id     Order ID.
	 * @param int $order_item_id Order item ID.
	 * @return string Download URL.
	 */
	/**
	 * Build the list of printable tickets for a POS order (for the register's
	 * "Print tickets" action). Returns array of { label, name, url }.
	 *
	 * @param array           $tickets Incoming (default empty).
	 * @param WC_Order|object $order   Order object.
	 * @return array
	 */
	public static function pos_order_tickets( $tickets, $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
			return $tickets;
		}
		$out = is_array( $tickets ) ? $tickets : array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			// A line is a ticket line when the product (or its parent, for variable
			// event tickets whose variation reports type "variation"), is a ticket
			// product — OR when the line already has generated ticket rows.
			$is_ticket = self::is_ticket_product( $product );
			if ( ! $is_ticket && $product->get_parent_id() ) {
				$is_ticket = self::is_ticket_product( wc_get_product( $product->get_parent_id() ) );
			}
			if ( ! $is_ticket && $item->get_meta( '_venuera_ticket_ids' ) ) {
				$is_ticket = true;
			}
			if ( ! $is_ticket ) {
				continue;
			}
			$item_tickets = self::get_tickets_for_order_item( $order, $item_id );
			foreach ( (array) $item_tickets as $t ) {
				if ( empty( $t['id'] ) ) {
					continue;
				}
				$sub   = ( isset( $t['label'] ) && '' !== $t['label'] && 'Download Ticket' !== $t['label'] ) ? (string) $t['label'] : '';
				$out[] = array(
					'label' => $item->get_name(),
					'name'  => $sub,
					// vp_print=1 → PDF opens inline (ready to print), not downloaded.
					'url'   => add_query_arg( 'vp_print', '1', self::get_download_url( $t['id'], $order->get_id(), $item_id ) ),
				);
			}
		}
		return $out;
	}

	/**
	 * Build the tokenised public download URL for a single ticket.
	 *
	 * @param int $ticket_id     Ticket ID.
	 * @param int $order_id      Order ID.
	 * @param int $order_item_id Order item ID.
	 * @return string
	 */
	public static function get_download_url( $ticket_id, $order_id, $order_item_id ) {
		$token = self::generate_token( $ticket_id, $order_id, $order_item_id );

		return add_query_arg(
			array(
				'venuera_download' => 'ticket',
				'token'            => $token,
			),
			home_url( '/' )
		);
	}

	/**
	 * Combined "all tickets in one PDF" download URL for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public static function get_order_tickets_url( $order_id ) {
		$token = self::generate_token( 'ALL', $order_id, 0 );
		return add_query_arg(
			array(
				'venuera_download' => 'order_tickets',
				'token'            => $token,
			),
			home_url( '/' )
		);
	}

	/**
	 * POS filter: the combined-PDF URL for an order (vp_print=1 → opens inline),
	 * or '' when the order has no printable tickets.
	 *
	 * @param string          $url   Incoming (default '').
	 * @param WC_Order|object $order Order object.
	 * @return string
	 */
	public static function pos_order_tickets_pdf( $url, $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
			return $url;
		}
		$tickets = self::pos_order_tickets( array(), $order );
		if ( empty( $tickets ) ) {
			return $url;
		}
		return add_query_arg( 'vp_print', '1', self::get_order_tickets_url( $order->get_id() ) );
	}

	/**
	 * Inject the order's tickets (full per-ticket data) into the POS receipt
	 * payload. Hooked on `venuera_pos_receipt_payload`. Always injects all data
	 * when ticket line items are present — visibility is controlled by the Tickets
	 * block config in the POS receipt builder.
	 *
	 * Each entry in $payload['tickets']:
	 * {
	 *   label        : product/ticket name,
	 *   code         : ticket UID / QR value,
	 *   barcode      : inline SVG barcode (always generated),
	 *   qr           : inline SVG QR code (always generated),
	 *   event_name   : event post title ('' when none),
	 *   datetime     : occurrence label ('' when none),
	 *   seat         : formatted seat/zone/table string ('' when none),
	 *   attendee     : { name, email, phone } — standard attendee fields,
	 *   fields       : [ { label, value } ] — custom attendee fields for this ticket,
	 * }
	 *
	 * @param array    $payload Receipt payload.
	 * @param WC_Order $order   Order.
	 * @return array
	 */
	public static function pos_receipt_payload( $payload, $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
			return $payload;
		}

		$tickets          = array();
		$ticket_index_map = array(); // item_id => running 1-based index counter.

		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			$is_ticket = self::is_ticket_product( $product );
			if ( ! $is_ticket && $product->get_parent_id() ) {
				$is_ticket = self::is_ticket_product( wc_get_product( $product->get_parent_id() ) );
			}
			if ( ! $is_ticket && $item->get_meta( '_venuera_ticket_ids' ) ) {
				$is_ticket = true;
			}
			if ( ! $is_ticket ) {
				continue;
			}

			// ---- Event / occurrence data (shared across all tickets in this item) ----
			$event_id = $item->get_meta( '_event_id' );
			if ( ! $event_id && $product ) {
				$event_id = $product->get_meta( '_event_id' );
				if ( ! $event_id && $product->get_parent_id() ) {
					$event_id = get_post_meta( $product->get_parent_id(), '_event_id', true );
				}
			}
			$event_name = $event_id ? (string) get_the_title( (int) $event_id ) : '';

			// Resolve the event object once (used for single-date fallback).
			$event_obj = ( $event_id && class_exists( 'Venuera_Event' ) )
				? Venuera_Event::get( (int) $event_id )
				: null;

			// Event date & time label.
			// - Recurring: the booked occurrence's start_datetime (DB id or
			// "virtual:<datetime>" pseudo-id).
			// - Single-date: the event's own start datetime.
			$occurrence_id    = $item->get_meta( '_venuera_occurrence_id' );
			$occurrence_label = '';
			$start_raw        = '';

			if ( $occurrence_id ) {
				if ( is_string( $occurrence_id ) && 0 === strpos( $occurrence_id, 'virtual:' ) ) {
					// Virtual (not-yet-materialised) occurrence — datetime is inline.
					$start_raw = substr( $occurrence_id, strlen( 'virtual:' ) );
				} else {
					// Materialised occurrence — ask the recurrence engine (via filter).
					$occ = apply_filters( 'tickera_get_occurrence', null, $occurrence_id );
					if ( is_object( $occ ) && ! empty( $occ->start_datetime ) ) {
						$start_raw = (string) $occ->start_datetime;
					} elseif ( is_array( $occ ) && ! empty( $occ['start_datetime'] ) ) {
						$start_raw = (string) $occ['start_datetime'];
					}
				}
			}

			// Fallback: a plain (single-date) event uses its own start datetime.
			if ( '' === $start_raw && $event_obj && method_exists( $event_obj, 'get_start_datetime' ) ) {
				$start_raw = (string) $event_obj->get_start_datetime();
			}

			// Legacy fallback: occurrence stored as a post with a start-datetime meta.
			if ( '' === $start_raw && $occurrence_id && is_numeric( $occurrence_id ) ) {
				$start_raw = (string) get_post_meta( (int) $occurrence_id, '_event_start_datetime', true );
			}

			if ( '' !== $start_raw ) {
				$fmt              = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				$ts               = strtotime( $start_raw );
				$occurrence_label = $ts ? date_i18n( $fmt, $ts ) : $start_raw;
			}

			// Seat / zone / table is owned by the Venue Designer add-on, which
			// attaches it per ticket via the venuera_pos_receipt_ticket_data filter.

			// Attendee data — keyed by 1-based ticket index.
			$attendee_data_all = $item->get_meta( '_venuera_attendee_data' );
			if ( ! is_array( $attendee_data_all ) ) {
				$attendee_data_all = array();
			}

			$ticket_index_map[ $item_id ] = 0;

			foreach ( (array) self::get_tickets_for_order_item( $order, $item_id ) as $t ) {
				if ( empty( $t['id'] ) ) {
					continue;
				}

				++$ticket_index_map[ $item_id ];
				$ticket_pos = $ticket_index_map[ $item_id ]; // 1-based

				// ---- Code / barcode / QR ----
				$ticket_data = self::get_ticket_data( $order, $item_id, $t['id'] );
				if ( empty( $ticket_data ) || ! is_array( $ticket_data ) ) {
					continue;
				}
				$template = self::get_template_for_ticket( $ticket_data );
				$tarr     = ( $template && method_exists( $template, 'get_template_array' ) ) ? $template->get_template_array() : array();

				$qr_field = self::find_code_field( $tarr, array( 'qr_code', 'qrcode', 'qr' ) );
				$bc_field = self::find_code_field( $tarr, array( 'barcode' ) );
				if ( '' === $qr_field ) {
					$qr_field = 'ticket_id'; }
				if ( '' === $bc_field ) {
					$bc_field = 'ticket_id'; }

				$qr_value = (string) ( $ticket_data[ $qr_field ] ?? $ticket_data['ticket_id'] ?? '' );
				$bc_value = (string) ( $ticket_data[ $bc_field ] ?? $ticket_data['ticket_id'] ?? '' );
				if ( '' === $qr_value && '' === $bc_value ) {
					continue;
				}

				$code = '' !== $qr_value ? $qr_value : $bc_value;

				// ---- Attendee data for this specific ticket ----
				$attendee_name  = '';
				$attendee_email = '';
				$attendee_phone = '';

				// _venuera_attendee_data is keyed 1..N (int or string).
				$attendee_entry = null;
				if ( isset( $attendee_data_all[ $ticket_pos ] ) ) {
					$attendee_entry = $attendee_data_all[ $ticket_pos ];
				} elseif ( isset( $attendee_data_all[ (string) $ticket_pos ] ) ) {
					$attendee_entry = $attendee_data_all[ (string) $ticket_pos ];
				} elseif ( ! empty( $attendee_data_all ) ) {
					// Fallback: first available entry (e.g. qty=1 stored at key 0 or 1).
					$attendee_entry = reset( $attendee_data_all );
				}

				if ( is_array( $attendee_entry ) ) {
					foreach ( $attendee_entry as $field_id => $field ) {
						$label = '';
						$value = '';
						if ( is_array( $field ) ) {
							$label = isset( $field['label'] ) ? (string) $field['label'] : '';
							$raw   = isset( $field['value'] ) ? $field['value'] : '';
							$value = is_array( $raw ) ? implode( ', ', array_map( 'strval', $raw ) ) : (string) $raw;
						} else {
							$value = (string) $field;
						}
						if ( '' === $value ) {
							continue;
						}
						// Only the standard attendee fields are handled here.
						// Custom fields are owned by the Attendee Fields add-on.
						if ( 'attendee_name' === $field_id ) {
							$attendee_name = $value;
						} elseif ( 'attendee_email' === $field_id ) {
							$attendee_email = $value;
						} elseif ( 'attendee_phone' === $field_id ) {
							$attendee_phone = $value;
						}
					}
				}

				$ticket = array(
					'label'      => $item->get_name(),
					'code'       => $code,
					'barcode'    => '' !== $bc_value ? self::receipt_barcode_svg( $bc_value ) : '',
					'qr'         => '' !== $qr_value ? self::receipt_qr_svg( $qr_value ) : '',
					'event_name' => $event_name,
					'datetime'   => $occurrence_label,
					'attendee'   => array(
						'name'  => $attendee_name,
						'email' => $attendee_email,
						'phone' => $attendee_phone,
					),
				);

				/**
				 * Let owning add-ons attach their own per-ticket data, e.g. the
				 * Venue Designer adds `seat`, the Attendee Fields add-on adds
				 * `fields`. Keeps each concern in its own plugin.
				 *
				 * @param array    $ticket  The base ticket data.
				 * @param array    $context item / attendee_entry / position / event id.
				 * @param WC_Order $order   The order.
				 */
				$tickets[] = apply_filters(
					'tickera_pos_receipt_ticket_data',
					$ticket,
					array(
						'item'           => $item,
						'attendee_entry' => $attendee_entry,
						'ticket_pos'     => $ticket_pos,
						'event_id'       => $event_id,
						'is_example'     => false,
					),
					$order
				);
			}
		}

		if ( ! empty( $tickets ) ) {
			$payload['tickets'] = $tickets;
		}
		return $payload;
	}

	// -------------------------------------------------------------------------
	// POS receipt "Tickets" block (owned by Venuera, registered into the POS)
	// -------------------------------------------------------------------------

	/**
	 * Register the "Tickets" block type into the POS receipt builder.
	 *
	 * Base fields live here; add-ons (Venue Designer, Attendee Fields) add their
	 * own toggles via the `venuera_pos_receipt_tickets_fields` filter.
	 *
	 * @param array $types Existing block types.
	 * @return array
	 */
	public static function register_tickets_block( $types ) {
		$fields = array(
			'codes'               => array(
				'type'    => 'select',
				'label'   => __( 'Ticket codes', 'tickera-event-ticketing-system' ),
				'options' => array(
					'none'    => __( 'None', 'tickera-event-ticketing-system' ),
					'qr'      => __( 'QR code only', 'tickera-event-ticketing-system' ),
					'barcode' => __( 'Barcode only', 'tickera-event-ticketing-system' ),
					'both'    => __( 'Barcode + QR code', 'tickera-event-ticketing-system' ),
				),
				'default' => 'both',
				'help'    => __( 'Which ticket code(s) to print on the receipt.', 'tickera-event-ticketing-system' ),
			),
			'show_code'           => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show ticket code string', 'tickera-event-ticketing-system' ),
				'default' => false,
				'help'    => __( 'Print the raw ticket UID below the code graphic.', 'tickera-event-ticketing-system' ),
			),
			'show_event_name'     => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show event name', 'tickera-event-ticketing-system' ),
				'default' => false,
			),
			'show_ticket_type'    => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show ticket type / product name', 'tickera-event-ticketing-system' ),
				'default' => true,
			),
			'show_datetime'       => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show event date & time', 'tickera-event-ticketing-system' ),
				'default' => false,
			),
			'show_attendee_name'  => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show attendee name', 'tickera-event-ticketing-system' ),
				'default' => false,
			),
			'show_attendee_email' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show attendee email', 'tickera-event-ticketing-system' ),
				'default' => false,
			),
			'show_attendee_phone' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show attendee phone', 'tickera-event-ticketing-system' ),
				'default' => false,
			),
		);

		/**
		 * Allow add-ons to register extra per-ticket option fields (e.g. the
		 * Venue Designer's "Show seat / zone / table", the Attendee Fields
		 * add-on's "Show attendee custom fields").
		 *
		 * @param array $fields Field definitions keyed by config key.
		 */
		$fields = apply_filters( 'tickera_pos_receipt_tickets_fields', $fields );

		$types['tickets'] = array(
			'label'  => __( 'Tickets', 'tickera-event-ticketing-system' ),
			'group'  => __( 'Tickets', 'tickera-event-ticketing-system' ),
			'fields' => $fields,
			'render' => array( __CLASS__, 'render_tickets_block' ),
		);

		return $types;
	}

	/**
	 * Render the "Tickets" block from the enriched $ctx['tickets'] payload.
	 *
	 * @param array $b   Block (with 'config').
	 * @param array $ctx Render context.
	 * @return string
	 */
	public static function render_tickets_block( $b, $ctx ) {
		$tickets = isset( $ctx['tickets'] ) ? $ctx['tickets'] : array();
		if ( empty( $tickets ) || ! is_array( $tickets ) ) {
			return '';
		}

		$cfg = isset( $b['config'] ) && is_array( $b['config'] ) ? $b['config'] : array();

		$codes_mode          = isset( $cfg['codes'] ) ? (string) $cfg['codes'] : 'both';
		$show_code           = ! empty( $cfg['show_code'] );
		$show_event_name     = ! empty( $cfg['show_event_name'] );
		$show_ticket_type    = ! isset( $cfg['show_ticket_type'] ) || ! empty( $cfg['show_ticket_type'] );
		$show_datetime       = ! empty( $cfg['show_datetime'] );
		$show_attendee_name  = ! empty( $cfg['show_attendee_name'] );
		$show_attendee_email = ! empty( $cfg['show_attendee_email'] );
		$show_attendee_phone = ! empty( $cfg['show_attendee_phone'] );

		$want_qr = in_array( $codes_mode, array( 'qr', 'both' ), true );
		$want_bc = in_array( $codes_mode, array( 'barcode', 'both' ), true );

		$out = '<div class="vpr-sep"></div><div class="vpr-tickets">';
		$i   = 0;

		foreach ( $tickets as $t ) {
			if ( $i > 0 ) {
				$out .= '<div class="vpr-ticket-divider"></div>'; }
			$out .= '<div class="vpr-ticket">';

			if ( $show_event_name && ! empty( $t['event_name'] ) ) {
				$out .= '<div class="vpr-ticket-label">' . esc_html( $t['event_name'] ) . '</div>';
			}
			if ( $show_ticket_type && ! empty( $t['label'] ) ) {
				$out .= '<div class="vpr-ticket-label">' . esc_html( $t['label'] ) . '</div>';
			}
			if ( $show_datetime && ! empty( $t['datetime'] ) ) {
				$out .= '<div class="vpr-meta-line">' . esc_html( $t['datetime'] ) . '</div>';
			}

			/**
			 * After the date/time — used by the Venue Designer to print the
			 * seat / zone / table line (it owns that data + toggle).
			 *
			 * @param string $html  Accumulated HTML (start empty).
			 * @param array  $t     The ticket data.
			 * @param array  $cfg   The block config.
			 * @param array  $ctx   The render context.
			 */
			$out .= (string) apply_filters( 'tickera_pos_receipt_ticket_after_datetime', '', $t, $cfg, $ctx );

			$attendee = isset( $t['attendee'] ) && is_array( $t['attendee'] ) ? $t['attendee'] : array();
			if ( $show_attendee_name && ! empty( $attendee['name'] ) ) {
				$out .= '<div class="vpr-meta-line">' . esc_html__( 'Attendee', 'tickera-event-ticketing-system' ) . ': ' . esc_html( $attendee['name'] ) . '</div>';
			}
			if ( $show_attendee_email && ! empty( $attendee['email'] ) ) {
				$out .= '<div class="vpr-meta-line">' . esc_html__( 'Email', 'tickera-event-ticketing-system' ) . ': ' . esc_html( $attendee['email'] ) . '</div>';
			}
			if ( $show_attendee_phone && ! empty( $attendee['phone'] ) ) {
				$out .= '<div class="vpr-meta-line">' . esc_html__( 'Phone', 'tickera-event-ticketing-system' ) . ': ' . esc_html( $attendee['phone'] ) . '</div>';
			}

			/**
			 * After the attendee block — used by the Attendee Fields add-on to
			 * print custom attendee fields (it owns that data + toggle).
			 */
			$out .= (string) apply_filters( 'tickera_pos_receipt_ticket_after_attendee', '', $t, $cfg, $ctx );

			if ( 'none' !== $codes_mode ) {
				$codes_html = '';
				if ( $want_bc && ! empty( $t['barcode'] ) ) {
					$codes_html .= '<div class="vpr-ticket-bc">' . $t['barcode'] . '</div>';
				}
				if ( $want_qr && ! empty( $t['qr'] ) ) {
					$codes_html .= '<div class="vpr-ticket-qr">' . $t['qr'] . '</div>';
				}
				if ( '' !== $codes_html ) {
					$out .= '<div class="vpr-ticket-codes">' . $codes_html . '</div>';
				}
			}

			if ( $show_code && ! empty( $t['code'] ) ) {
				$out .= '<div class="vpr-ticket-code">' . esc_html( $t['code'] ) . '</div>';
			}

			$out .= '</div>';
			++$i;
		}

		return $out . '</div>';
	}

	/**
	 * Insert a "tickets" block into the POS default template (after order_qr).
	 *
	 * @param array $blocks Default template blocks.
	 * @return array
	 */
	public static function inject_tickets_into_default_template( $blocks ) {
		if ( ! is_array( $blocks ) ) {
			return $blocks;
		}
		// Idempotent: never add a second tickets block.
		foreach ( $blocks as $block ) {
			if ( isset( $block['type'] ) && 'tickets' === $block['type'] ) {
				return $blocks;
			}
		}
		$ticket_block = array(
			'type'   => 'tickets',
			'config' => array(
				'codes'         => 'both',
				'show_datetime' => true,
				'show_seat'     => false,
			),
		);
		// Insert after 'payment' (fallback: after 'order_qr', else append).
		$anchors = array( 'payment', 'order_qr' );
		foreach ( $anchors as $anchor ) {
			$idx = null;
			foreach ( $blocks as $i => $block ) {
				if ( isset( $block['type'] ) && $anchor === $block['type'] ) {
					$idx = $i;
				}
			}
			if ( null !== $idx ) {
				array_splice( $blocks, $idx + 1, 0, array( $ticket_block ) );
				return $blocks;
			}
		}
		$blocks[] = $ticket_block;
		return $blocks;
	}

	/**
	 * Provide the QR-code SVG for the POS receipt engine (order codes etc.),
	 * so the POS never has to know about the bundled barcode library.
	 *
	 * @param string $svg  Existing SVG (empty unless another add-on set it).
	 * @param string $data Value to encode.
	 * @return string
	 */
	public static function filter_receipt_qr_svg( $svg, $data ) {
		if ( is_string( $svg ) && '' !== $svg ) {
			return $svg;
		}
		return self::receipt_qr_svg( (string) $data );
	}

	/**
	 * Provide the Code-128 barcode SVG for the POS receipt engine.
	 *
	 * @param string $svg  Existing SVG.
	 * @param string $data Value to encode.
	 * @return string
	 */
	public static function filter_receipt_barcode_svg( $svg, $data ) {
		if ( is_string( $svg ) && '' !== $svg ) {
			return $svg;
		}
		return self::receipt_barcode_svg( (string) $data );
	}

	/**
	 * Attach example ticket data to the builder preview payload, so the preview
	 * is complete even with no real orders. Add-ons enrich each example ticket
	 * (seat, custom fields) through the same venuera_pos_receipt_ticket_data filter.
	 *
	 * @param array $payload Example payload from the POS.
	 * @return array
	 */
	public static function pos_receipt_example_payload( $payload ) {
		if ( ! is_array( $payload ) ) {
			return $payload;
		}

		$fmt   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$when  = date_i18n( $fmt, strtotime( '+21 days 20:00' ) );
		$label = __( 'Summer Music Festival – GA', 'tickera-event-ticketing-system' );
		$event = __( 'Summer Music Festival', 'tickera-event-ticketing-system' );

		$make = function ( $code, $name, $email, $phone ) use ( $label, $event, $when ) {
			return array(
				'label'      => $label,
				'code'       => $code,
				'barcode'    => self::receipt_barcode_svg( $code ),
				'qr'         => self::receipt_qr_svg( $code ),
				'event_name' => $event,
				'datetime'   => $when,
				'attendee'   => array(
					'name'  => $name,
					'email' => $email,
					'phone' => $phone,
				),
			);
		};

		$examples = array(
			$make( 'TWP-EXAMPLE01', 'Jane Doe', 'jane@example.com', '+1 555 0142' ),
			$make( 'TWP-EXAMPLE02', 'John Smith', 'john@example.com', '+1 555 0177' ),
		);

		$tickets = array();
		$pos     = 0;
		foreach ( $examples as $ex ) {
			++$pos;
			$tickets[] = apply_filters(
				'tickera_pos_receipt_ticket_data',
				$ex,
				array(
					'item'           => null,
					'attendee_entry' => null,
					'ticket_pos'     => $pos,
					'event_id'       => 0,
					'is_example'     => true,
				),
				null
			);
		}

		$payload['tickets'] = $tickets;
		return $payload;
	}

	/**
	 * Walk a template array (recursively, including grouped elements) and return
	 * the `dataField` of the first element whose type/baseType matches one of the
	 * given kinds (e.g. a QR or barcode element). Empty string if none.
	 *
	 * @param mixed    $node  Template array node.
	 * @param string[] $kinds Lower-case element kinds to match.
	 * @return string
	 */
	private static function find_code_field( $node, $kinds ) {
		if ( ! is_array( $node ) ) {
			return '';
		}
		$type = strtolower( (string) ( $node['type'] ?? '' ) );
		$base = strtolower( (string) ( $node['baseType'] ?? '' ) );
		if ( ( in_array( $type, $kinds, true ) || in_array( $base, $kinds, true ) ) && ! empty( $node['dataField'] ) ) {
			return (string) $node['dataField'];
		}
		foreach ( $node as $child ) {
			if ( is_array( $child ) ) {
				$found = self::find_code_field( $child, $kinds );
				if ( '' !== $found ) {
					return $found;
				}
			}
		}
		return '';
	}

	/** Ensure TCPDF's standalone barcode classes are loaded. */
	private static function load_barcode_classes() {
		if ( class_exists( 'TCPDF2DBarcode' ) && class_exists( 'TCPDFBarcode' ) ) {
			return true;
		}
		if ( ! defined( 'TC_TICKET_DESIGNER_PARENT_DIR' ) ) {
			return false;
		}
		$base = TC_TICKET_DESIGNER_PARENT_DIR . 'vendor/tecnickcom/tcpdf/';
		if ( ! class_exists( 'TCPDF2DBarcode' ) && file_exists( $base . 'tcpdf_barcodes_2d.php' ) ) {
			require_once $base . 'tcpdf_barcodes_2d.php';
		}
		if ( ! class_exists( 'TCPDFBarcode' ) && file_exists( $base . 'tcpdf_barcodes_1d.php' ) ) {
			require_once $base . 'tcpdf_barcodes_1d.php';
		}
		return class_exists( 'TCPDF2DBarcode' ) && class_exists( 'TCPDFBarcode' );
	}

	/**
	 * Strip the XML prolog/doctype so the SVG embeds inline in HTML.
	 *
	 * @param string $svg Raw SVG markup.
	 * @return string
	 */
	private static function clean_inline_svg( $svg ) {
		if ( ! is_string( $svg ) || '' === $svg ) {
			return '';
		}
		$svg = preg_replace( '/<\?xml.*?\?>/is', '', $svg );
		$svg = preg_replace( '/<!DOCTYPE.*?>/is', '', $svg );
		return trim( (string) $svg );
	}

	/**
	 * Generate a QR code as inline SVG for the given payload string.
	 *
	 * @param string $code QR code payload string.
	 * @return string
	 */
	private static function receipt_qr_svg( $code ) {
		if ( ! self::load_barcode_classes() ) {
			return '';
		}
		try {
			$bc = new TCPDF2DBarcode( $code, 'QRCODE,M' );
			return self::clean_inline_svg( $bc->getBarcodeSVGcode( 4, 4, 'black' ) );
		} catch ( \Throwable $e ) {
			return '';
		}
	}

	/**
	 * Generate a Code-128 barcode as inline SVG for the given payload string.
	 *
	 * @param string $code Barcode payload string.
	 * @return string
	 */
	private static function receipt_barcode_svg( $code ) {
		if ( ! self::load_barcode_classes() ) {
			return '';
		}
		try {
			$bc  = new TCPDFBarcode( $code, 'C128' );
			$svg = self::clean_inline_svg( $bc->getBarcodeSVGcode( 1, 30, 'black' ) );

			// TCPDF emits a fixed-size <svg width=".." height="30"> WITHOUT a
			// viewBox, so the browser locks the (very wide) aspect ratio and the
			// barcode prints as a thin sliver. Give it a viewBox + non-uniform
			// scaling so the receipt CSS can set the bar height freely.
			if ( $svg && preg_match( '/<svg\b[^>]*\bwidth="([\d.]+)"[^>]*\bheight="([\d.]+)"/i', $svg, $m ) ) {
				$svg = preg_replace(
					'/<svg\b[^>]*>/i',
					'<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 ' . $m[1] . ' ' . $m[2] . '" preserveAspectRatio="none">',
					$svg,
					1
				);
			}
			return $svg;
		} catch ( \Throwable $e ) {
			return '';
		}
	}

	/**
	 * Build [ {template, ticket_data}, ... ] for every ticket in an order, in
	 * order — used to render the combined multi-page PDF.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private static function collect_order_ticket_payloads( $order ) {
		$payloads = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			$is_ticket = self::is_ticket_product( $product );
			if ( ! $is_ticket && $product->get_parent_id() ) {
				$is_ticket = self::is_ticket_product( wc_get_product( $product->get_parent_id() ) );
			}
			if ( ! $is_ticket && $item->get_meta( '_venuera_ticket_ids' ) ) {
				$is_ticket = true;
			}
			if ( ! $is_ticket ) {
				continue;
			}
			foreach ( (array) self::get_tickets_for_order_item( $order, $item_id ) as $t ) {
				if ( empty( $t['id'] ) ) {
					continue;
				}
				$ticket_data = self::get_ticket_data( $order, $item_id, $t['id'] );
				if ( empty( $ticket_data ) ) {
					continue;
				}
				$template = self::get_template_for_ticket( $ticket_data );
				if ( ! $template ) {
					continue;
				}
				$payloads[] = array(
					'template'    => $template,
					'ticket_data' => $ticket_data,
				);
			}
		}
		return $payloads;
	}

	/**
	 * Stream a single combined PDF with every ticket in the order.
	 *
	 * @param WC_Order $order Order object.
	 */
	private static function handle_order_download( $order ) {
		$payloads = self::collect_order_ticket_payloads( $order );
		if ( empty( $payloads ) ) {
			wp_die( esc_html__( 'No tickets found for this order.', 'tickera-event-ticketing-system' ), esc_html__( 'Error', 'tickera-event-ticketing-system' ), 404 );
		}

		$filename    = sprintf( 'tickets-order-%d.pdf', $order->get_id() );
		$disposition = ! empty( $_GET['vp_print'] ) ? 'inline' : 'attachment';

		// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed, WordPress.PHP.NoSilencedErrors.Discouraged, PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveDisplay_errors -- Temporarily disable error display so stray notices cannot corrupt the binary PDF stream; restored after generation.
		$display_errors = @ini_set( 'display_errors', '0' );

		ob_start();
		$pdf = TC_Ticket_Designer_PDF_Generator::generate_multi( $payloads, 'S' );
		ob_end_clean();
		if ( false !== $display_errors ) {
			// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed, WordPress.PHP.NoSilencedErrors.Discouraged, PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveDisplay_errors -- Restoring the previous display_errors value.
			@ini_set( 'display_errors', $display_errors );
		}

		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: ' . $disposition . '; filename="' . $filename . '"' );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw PDF binary stream.
		exit;
	}

	/**
	 * Handle download request.
	 */
	public static function handle_download_request() {
		if ( ! isset( $_GET['venuera_download'] ) || ! in_array( isset( $_GET['venuera_download'] ) ? sanitize_text_field( wp_unslash( $_GET['venuera_download'] ) ) : '', array( 'ticket', 'order_tickets' ), true ) ) {
			return;
		}
		$download_mode = sanitize_key( wp_unslash( $_GET['venuera_download'] ) );

		if ( ! isset( $_GET['token'] ) ) {
			wp_die( esc_html__( 'Invalid download link.', 'tickera-event-ticketing-system' ), esc_html__( 'Error', 'tickera-event-ticketing-system' ), 403 );
		}

		$token_data = self::verify_token( sanitize_text_field( wp_unslash( $_GET['token'] ) ) );

		if ( ! $token_data ) {
			wp_die( esc_html__( 'This download link has expired or is invalid. Please go to your orders page to get a new link.', 'tickera-event-ticketing-system' ), esc_html__( 'Link Expired', 'tickera-event-ticketing-system' ), 403 );
		}

		// Verify order exists and has this item.
		$order = wc_get_order( $token_data['order_id'] );
		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'tickera-event-ticketing-system' ), esc_html__( 'Error', 'tickera-event-ticketing-system' ), 404 );
		}

		// Additional security: if user is logged in, verify they own this order.
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id();
			$order_user_id   = $order->get_customer_id();

			// Allow if user owns the order OR is admin.
			if ( $order_user_id && $order_user_id !== $current_user_id && ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- manage_woocommerce is a WooCommerce core capability.
				wp_die( esc_html__( 'You do not have permission to download this ticket.', 'tickera-event-ticketing-system' ), esc_html__( 'Access Denied', 'tickera-event-ticketing-system' ), 403 );
			}
		}

		// Combined "all tickets in one PDF" for the whole order.
		if ( 'order_tickets' === $download_mode ) {
			self::handle_order_download( $order );
			// handle_order_download() exits.
		}

		// Get ticket data.
		$ticket_data = self::get_ticket_data( $order, $token_data['order_item_id'], $token_data['ticket_id'] );

		if ( ! $ticket_data ) {
			wp_die( esc_html__( 'Ticket not found.', 'tickera-event-ticketing-system' ), esc_html__( 'Error', 'tickera-event-ticketing-system' ), 404 );
		}

		// Get template.
		$template = self::get_template_for_ticket( $ticket_data );

		if ( ! $template ) {
			wp_die( esc_html__( 'Ticket template not found.', 'tickera-event-ticketing-system' ), esc_html__( 'Error', 'tickera-event-ticketing-system' ), 404 );
		}

		// Generate PDF on-the-fly. The ticket id is the real UID where available.
		$filename = sprintf( 'ticket-%s.pdf', sanitize_file_name( (string) $ticket_data['ticket_id'] ) );

		// Generate FIRST, with output buffered, so any stray PHP notice/deprecation
		// printed during generation (e.g. TCPDF's imagedestroy() deprecation on
		// PHP 8.x when display_errors is on) cannot leak into and corrupt the binary
		// PDF stream. Errors are still logged; we only stop them being echoed here.
		
		// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed, WordPress.PHP.NoSilencedErrors.Discouraged, PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveDisplay_errors -- Temporarily disable error display so stray notices cannot corrupt the binary PDF stream; restored after generation.
		$display_errors = @ini_set( 'display_errors', '0' );
		
		ob_start();
		$pdf = TC_Ticket_Designer_PDF_Generator::generate( $template, $ticket_data, 'S' );
		ob_end_clean();
		if ( false !== $display_errors ) {
			// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed, WordPress.PHP.NoSilencedErrors.Discouraged, PluginCheck.CodeAnalysis.PHPErrorReporting.IniDirectiveDisplay_errors -- Restoring the previous display_errors value.
			@ini_set( 'display_errors', $display_errors );
		}

		// Set headers for PDF. "vp_print=1" serves the PDF inline (opens in the
		// browser's PDF viewer ready to print) instead of forcing a download —
		// used by the POS "Print tickets" action.
		$disposition = ! empty( $_GET['vp_print'] ) ? 'inline' : 'attachment';
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: ' . $disposition . '; filename="' . $filename . '"' );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $pdf ) );

		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw PDF binary stream.
		exit;
	}

	/**
	 * Get ticket data for PDF generation.
	 *
	 * Resolves the requested ticket to its 1-based index within the order item so
	 * the streamed PDF carries that specific ticket's attendee data, UID and QR.
	 *
	 * @param WC_Order $order         Order object.
	 * @param int      $order_item_id Order item ID.
	 * @param mixed    $ticket_id     Requested ticket identifier (real ticket UID or
	 *                                numeric ticket-row id for real tickets; a synthetic
	 *                                "TKT-..." string for legacy orders).
	 * @return array|false Ticket data or false.
	 */
	private static function get_ticket_data( $order, $order_item_id, $ticket_id ) {
		$item = $order->get_item( $order_item_id );

		if ( ! $item ) {
			return false;
		}

		// Resolve the requested ticket to its 1-based index in `_venuera_ticket_ids`.
		$ticket_index = self::resolve_ticket_index( $item, $ticket_id );

		$data = \Tickera\TC_Ticket_Designer_Frontend::get_ticket_data_from_order( $order, $order_item_id, $ticket_index );

		// Legacy synthetic fallback: orders without real ticket rows encode the index
		// in a "TKT-<order>-<item>-<n>" id. Preserve that exact id on the streamed PDF.
		if ( 0 === $ticket_index && ! empty( $data ) && is_string( $ticket_id ) && '' !== $ticket_id ) {
			$data['ticket_id'] = $ticket_id;
			$data['qr_code']   = $ticket_id;
		}

		return $data;
	}

	/**
	 * Resolve a requested ticket identifier to its 1-based index within an order item.
	 *
	 * @param WC_Order_Item $item      Order item.
	 * @param mixed         $ticket_id Requested ticket UID or ticket-row id.
	 * @return int 1-based index, or 0 when it cannot be resolved (legacy behavior).
	 */
	private static function resolve_ticket_index( $item, $ticket_id ) {
		$ticket_ids = $item->get_meta( '_venuera_ticket_ids' );

		if ( ! is_array( $ticket_ids ) || empty( $ticket_ids ) || ! class_exists( 'Venuera_Ticket' ) ) {
			return 0;
		}

		$requested = (string) $ticket_id;

		foreach ( $ticket_ids as $i => $row_id ) {
			// Match on the real ticket-row id directly.
			if ( (string) $row_id === $requested ) {
				return $i + 1;
			}

			// Match on the ticket UID (download links use the UID as the id).
			$ticket = Venuera_Ticket::get( (int) $row_id );
			if ( $ticket && (string) $ticket->get_ticket_uid() === $requested ) {
				return $i + 1;
			}
		}

		return 0;
	}

	/**
	 * Get template for ticket.
	 *
	 * @param array $ticket_data Ticket data.
	 * @return TC_Ticket_Designer_Template|null
	 */
	private static function get_template_for_ticket( $ticket_data ) {
		$event_id = $ticket_data['event_id'] ?? 0;

		// The ticket binding (get_ticket_data_from_order) sets `product_id`, which
		// is the ticket-type product. Resolve the assigned per-product/per-event
		// template the same way the email and frontend paths do.
		$product_id = $ticket_data['product_id'] ?? 0;

		return TC_Ticket_Designer_Template::get_for_ticket( $event_id, $product_id );
	}

	/**
	 * Display ticket download link in order items (My Account → Orders → View Order).
	 *
	 * @param int           $item_id Item ID.
	 * @param WC_Order_Item $item    Item object.
	 * @param WC_Order      $order   Order object.
	 * @param bool          $plain_text Plain text email.
	 */
	public static function display_ticket_download_link( $item_id, $item, $order, $plain_text = false ) {
		if ( $plain_text ) {
			return;
		}

		// Only for line items (products).
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}

		// Check if this is a ticket product (simple, variable, or variation).
		$product = $item->get_product();
		if ( ! self::is_ticket_product( $product ) ) {
			return;
		}

		// Check order status - only show for completed/processing orders.
		$create_on        = get_option( 'venuera_reduce_stock_on', 'order_processing' );
		$allowed_statuses = ( 'order_completed' === $create_on ) ? array( 'completed' ) : array( 'processing', 'completed' );
		if ( ! in_array( $order->get_status(), $allowed_statuses, true ) ) {
			return;
		}

		// Get ticket IDs for this item.
		$tickets = self::get_tickets_for_order_item( $order, $item_id );

		if ( empty( $tickets ) ) {
			return;
		}

		echo '<div class="venuera-download-tickets" style="margin-top: 10px;">';

		foreach ( $tickets as $index => $ticket ) {
			$download_url = self::get_download_url( $ticket['id'], $order->get_id(), $item_id );
			/* translators: placeholders are dynamic values. */
			$ticket_label = $ticket['label'] ?? sprintf( __( 'Ticket #%d', 'tickera-event-ticketing-system' ), $index + 1 );

			printf(
				'<a href="%s" class="button venuera-download-ticket-btn" style="margin-right: 5px; margin-bottom: 5px;">
                    <span class="dashicons dashicons-pdf" style="vertical-align: middle;"></span> %s
                </a>',
				esc_url( $download_url ),
				esc_html( $ticket_label )
			);
		}

		echo '</div>';
	}

	/**
	 * Build a normalized per-ticket list for an order — event name/date, ticket
	 * type, holder (attendee) details and ticket id — shared by the order-page
	 * "Your Tickets" cards and the order email so both render identical tickets.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	public static function get_order_ticket_blocks( $order ) {
		$all_tickets = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! self::is_ticket_product( $product ) ) {
				continue;
			}

			$tickets = self::get_tickets_for_order_item( $order, $item_id );

			// Resolve the event once per item (every ticket on the item shares it).
			$event_id = (int) $product->get_meta( '_event_id' );
			if ( ! $event_id && $product->get_parent_id() ) {
				$parent = wc_get_product( $product->get_parent_id() );
				if ( $parent ) {
					$event_id = (int) $parent->get_meta( '_event_id' );
				}
			}
			$event      = ( $event_id && class_exists( 'Venuera_Event' ) ) ? Venuera_Event::get( $event_id ) : null;
			$event_name = $event ? $event->get_title() : '';
			$event_when = ( $event && $event->get_start_datetime() ) ? $event->get_start_datetime( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '';

			// Attendee details are stored per ticket index (1-based) on the item;
			// each ticket carries its own holder, so we surface them on the ticket.
			$attendee_data = $item->get_meta( '_venuera_attendee_data' );
			$attendee_data = is_array( $attendee_data ) ? $attendee_data : array();

			$idx = 0;
			foreach ( $tickets as $ticket ) {
				++$idx;
				$ticket['order_item_id'] = $item_id;
				$ticket['type_label']    = $item->get_name();
				$ticket['event_name']    = $event_name;
				$ticket['event_when']    = $event_when;
				$ticket['attendee']      = ( isset( $attendee_data[ $idx ] ) && is_array( $attendee_data[ $idx ] ) ) ? $attendee_data[ $idx ] : array();
				$all_tickets[]           = $ticket;
			}
		}

		return $all_tickets;
	}

	/**
	 * Display all tickets section on order details page.
	 *
	 * @param WC_Order $order Order object.
	 */
	public static function display_order_tickets_section( $order ) {
		// Check order status.
		$create_on        = get_option( 'venuera_reduce_stock_on', 'order_processing' );
		$allowed_statuses = ( 'order_completed' === $create_on ) ? array( 'completed' ) : array( 'processing', 'completed' );
		if ( ! in_array( $order->get_status(), $allowed_statuses, true ) ) {
			return;
		}

		$all_tickets = self::get_order_ticket_blocks( $order );

		if ( empty( $all_tickets ) ) {
			return;
		}

		// Delivery mode: 'download' (PDF button) or 'code' (inline QR image).
		$mode  = ( 'code' === get_option( 'venuera_order_ticket_delivery', 'download' ) ) ? 'code' : 'download';
		$total = count( $all_tickets );
		?>
		<section class="venuera-order-tickets">
			<h2><?php esc_html_e( 'Your Tickets', 'tickera-event-ticketing-system' ); ?></h2>
			<div class="venuera-tickets-grid">
				<?php
				$row = 0;
				foreach ( $all_tickets as $ticket ) :
					++$row;
					$attendee = ( ! empty( $ticket['attendee'] ) && is_array( $ticket['attendee'] ) ) ? $ticket['attendee'] : array();
					?>
					<div class="venuera-ticket-card">
						<div class="vt-card-head">
							<span class="vt-event"><?php echo esc_html( $ticket['event_name'] ? $ticket['event_name'] : $ticket['type_label'] ); ?></span>
							<span class="vt-meta">
								<?php
								$bits = array();
								if ( ! empty( $ticket['event_when'] ) ) {
									$bits[] = $ticket['event_when'];
								}
								if ( ! empty( $ticket['type_label'] ) ) {
									$bits[] = $ticket['type_label'];
								}
								echo esc_html( implode( ' · ', $bits ) );
								?>
							</span>
						</div>

						<div class="vt-card-body">
							<div class="vt-info">
								<?php if ( $total > 1 ) : ?>
									<div class="vt-tag">
										<?php
										echo esc_html(
											sprintf(
												/* translators: 1: ticket number, 2: total tickets. */
												__( 'Ticket %1$d of %2$d', 'tickera-event-ticketing-system' ),
												$row,
												$total
											)
										);
										?>
									</div>
								<?php endif; ?>
								<?php if ( ! empty( $attendee ) ) : ?>
									<ul class="vt-attendee">
										<?php
										foreach ( $attendee as $field ) :
											$value = isset( $field['value'] ) ? $field['value'] : '';
											$value = is_array( $value ) ? implode( ', ', $value ) : $value;
											if ( '' === (string) $value ) {
												continue;
											}
											?>
											<li>
												<span class="vt-label"><?php echo esc_html( $field['label'] ?? '' ); ?>:</span>
												<span class="vt-value"><?php echo esc_html( $value ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>

							<div class="vt-delivery">
								<?php
								$display_id = ! empty( $ticket['ticket_uid'] ) ? $ticket['ticket_uid'] : (string) $ticket['id'];
								if ( 'code' === $mode ) {
									$qr = class_exists( 'TC_Ticket_Designer_Element' ) ? TC_Ticket_Designer_Element::generate_qr_data_uri( $display_id ) : false;
									if ( $qr ) {
										printf(
											'<img src="%s" alt="%s" class="venuera-ticket-qr">',
											esc_attr( $qr ),
											esc_attr__( 'Ticket QR code', 'tickera-event-ticketing-system' )
										);
									}
								} else {
									$download_url = self::get_download_url( $ticket['id'], $order->get_id(), $ticket['order_item_id'] );
									printf(
										'<a href="%s" class="button vt-download">%s</a>',
										esc_url( $download_url ),
										esc_html__( 'Download PDF', 'tickera-event-ticketing-system' )
									);
								}
								?>
								<span class="vt-id"><?php echo esc_html( $display_id ); ?></span>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<style>
			.venuera-order-tickets { margin-top: 40px; }
			.venuera-tickets-grid { display: flex; flex-direction: column; gap: 16px; }
			.venuera-ticket-card { border: 1px solid #e3e3e8; border-radius: 10px; overflow: hidden; background: #fff; width: 100%; }
			.venuera-ticket-card .vt-card-head { padding: 14px 18px; background: #f7f7fa; border-bottom: 1px solid #ececf1; }
			.venuera-ticket-card .vt-event { display: block; font-weight: 700; font-size: 1.1em; line-height: 1.3; }
			.venuera-ticket-card .vt-meta { display: block; color: #6a6a75; font-size: .88em; margin-top: 3px; }
			.venuera-ticket-card .vt-card-body { display: flex; gap: 24px; padding: 18px; align-items: center; justify-content: space-between; }
			.venuera-ticket-card .vt-info { flex: 1 1 auto; min-width: 0; }
			.venuera-ticket-card .vt-tag { display: inline-block; font-size: .72em; letter-spacing: .04em; text-transform: uppercase; color: #6a6a75; background: #f0f0f4; border-radius: 999px; padding: 3px 9px; margin-bottom: 12px; }
			.venuera-ticket-card .vt-attendee { list-style: none; margin: 0; padding: 0; }
			.venuera-ticket-card .vt-attendee li { margin: 0 0 5px; font-size: .95em; }
			.venuera-ticket-card .vt-attendee .vt-label { color: #6a6a75; }
			.venuera-ticket-card .vt-delivery { flex: 0 0 auto; text-align: center; display: flex; flex-direction: column; align-items: center; }
			.venuera-ticket-card .venuera-ticket-qr { display: block; width: 128px; height: 128px; }
			.venuera-ticket-card .vt-id { display: block; margin-top: 8px; font-family: monospace; font-size: .78em; color: #6a6a75; word-break: break-all; max-width: 160px; }
			.venuera-ticket-card .vt-download { white-space: nowrap; }
			@media (max-width: 520px) { .venuera-ticket-card .vt-card-body { flex-direction: column; align-items: stretch; } .venuera-ticket-card .vt-delivery { align-items: flex-start; } }
		</style>
		<?php
	}

	/**
	 * Get tickets for an order item.
	 *
	 * @param WC_Order $order   Order object.
	 * @param int      $item_id Order item ID.
	 * @return array Array of ticket data.
	 */
	private static function get_tickets_for_order_item( $order, $item_id ) {
		$tickets = array();
		$item    = $order->get_item( $item_id );

		if ( ! $item ) {
			return $tickets;
		}

		// Get quantity - each quantity is a ticket.
		$quantity = $item->get_quantity();

		// Prefer the real ticket rows linked from `class-venuera-ticket.php`
		// (written as `_venuera_ticket_ids` when tickets are created from the order).
		$ticket_ids = $item->get_meta( '_venuera_ticket_ids' );

		if ( $ticket_ids && is_array( $ticket_ids ) && class_exists( 'Venuera_Ticket' ) ) {
			$index = 0;
			foreach ( $ticket_ids as $ticket_id ) {
				$ticket = Venuera_Ticket::get( (int) $ticket_id );
				if ( ! $ticket ) {
					continue;
				}
				$uid       = $ticket->get_ticket_uid();
				$tickets[] = array(
					'id'           => $uid ? $uid : (int) $ticket_id,
					'ticket_id'    => (int) $ticket_id,
					'ticket_uid'   => $uid,
					'qr_code_hash' => $ticket->get_qr_code_hash(),
					'label'        => $quantity > 1
						/* translators: placeholders are dynamic values. */
						? sprintf( __( 'Download Ticket %d', 'tickera-event-ticketing-system' ), $index + 1 )
						: __( 'Download Ticket', 'tickera-event-ticketing-system' ),
				);
				++$index;
			}
		}

		// Legacy fallback: orders created before real ticket linkage existed have no
		// `_venuera_ticket_ids` meta, so synthesize a stable id so downloads still work.
		if ( empty( $tickets ) ) {
			for ( $i = 0; $i < $quantity; $i++ ) {
				$ticket_id = sprintf( 'TKT-%06d-%04d-%d', $order->get_id(), $item_id, $i + 1 );
				$tickets[] = array(
					'id'    => $ticket_id,
					'label' => $quantity > 1
						/* translators: placeholders are dynamic values. */
						? sprintf( __( 'Download Ticket %d', 'tickera-event-ticketing-system' ), $i + 1 )
						: __( 'Download Ticket', 'tickera-event-ticketing-system' ),
				);
			}
		}

		return $tickets;
	}

	/**
	 * Admin order item header.
	 */
	public static function admin_order_item_header() {
		echo '<th class="venuera-ticket-download">' . esc_html__( 'Ticket', 'tickera-event-ticketing-system' ) . '</th>';
	}

	/**
	 * Admin order item value - download link.
	 *
	 * @param WC_Product|null $product Product object.
	 * @param WC_Order_Item   $item    Order item.
	 * @param int             $item_id Item ID.
	 */
	public static function admin_order_item_value( $product, $item, $item_id ) {
		echo '<td class="venuera-ticket-download">';

		if ( self::is_ticket_product( $product ) ) {
			$order   = $item->get_order();
			$tickets = self::get_tickets_for_order_item( $order, $item_id );

			foreach ( $tickets as $index => $ticket ) {
				$download_url = self::get_download_url( $ticket['id'], $order->get_id(), $item_id );
				echo wp_kses_post(
					sprintf(
						'<a href="%s" class="button button-small" target="_blank" style="margin: 2px;">PDF %d</a>',
						esc_url( $download_url ),
						$index + 1
					)
				);
			}
		} else {
			echo '—';
		}

		echo '</td>';
	}
}



