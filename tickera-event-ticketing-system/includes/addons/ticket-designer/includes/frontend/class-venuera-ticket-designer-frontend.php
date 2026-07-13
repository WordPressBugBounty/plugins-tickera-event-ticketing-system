<?php

namespace Tickera;

/**
 * Ticket Designer Frontend
 *
 * Handles rendering tickets on the frontend.
 *
 * @package Venuera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ticket Designer Frontend class.
 */
class TC_Ticket_Designer_Frontend {

	/**
	 * Initialize frontend.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_filter( 'venuera_ticket_html', array( __CLASS__, 'render_ticket_html' ), 10, 3 );
		add_action( 'venuera_render_ticket', array( __CLASS__, 'render_ticket' ), 10, 2 );
		add_shortcode( 'venuera_ticket', array( __CLASS__, 'ticket_shortcode' ) );
	}

	/**
	 * Enqueue frontend scripts.
	 */
	public static function enqueue_scripts() {
		// Only load on pages that display tickets.
		if ( ! is_account_page() && ! is_order_received_page() ) {
			return;
		}

		$addon_url  = tickera_ticket_designer()->get_url();
		$addon_path = tickera_ticket_designer()->get_path();

		// JsBarcode for barcode rendering — bundled locally under
		// assets/vendor/jsbarcode/ so on-screen ticket pages keep rendering
		// barcodes even when the customer is offline or behind a network
		// that blocks third-party CDNs.
		$jsbarcode_rel  = 'assets/vendor/jsbarcode/JsBarcode.all.min.js';
		$jsbarcode_file = $addon_path . $jsbarcode_rel;
		wp_enqueue_script(
			'jsbarcode',
			$addon_url . $jsbarcode_rel,
			array(),
			file_exists( $jsbarcode_file ) ? filemtime( $jsbarcode_file ) : '3.11.5',
			true
		);

		// Frontend ticket styles.
		wp_enqueue_style(
			'venuera-ticket-designer-frontend',
			$addon_url . 'assets/css/frontend.css',
			array(),
			TC_TICKET_DESIGNER_VERSION
		);

		// @font-face rules so the rendered HTML ticket uses the same bundled
		// fonts as the design/PDF.
		if ( class_exists( 'TC_Ticket_Designer_Fonts' ) ) {
			wp_add_inline_style( 'venuera-ticket-designer-frontend', TC_Ticket_Designer_Fonts::get_font_face_css() );
		}

		// Frontend ticket scripts.
		wp_enqueue_script(
			'venuera-ticket-designer-frontend',
			$addon_url . 'assets/js/frontend/ticket-render.js',
			array( 'jquery', 'jsbarcode' ),
			TC_TICKET_DESIGNER_VERSION,
			true
		);
	}

	/**
	 * Render ticket HTML.
	 *
	 * @param string $html        Default HTML.
	 * @param array  $ticket_data Ticket data.
	 * @param int    $order_id    Order ID.
	 * @return string
	 */
	public static function render_ticket_html( $html, $ticket_data, $order_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameter required by the venuera_ticket_html filter signature.
		$template = self::get_template_for_ticket( $ticket_data );

		if ( ! $template ) {
			return $html;
		}

		return self::render_template( $template, $ticket_data );
	}

	/**
	 * Render ticket action.
	 *
	 * @param array $ticket_data Ticket data.
	 * @param int   $order_id    Order ID.
	 */
	public static function render_ticket( $ticket_data, $order_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameter required by the venuera_render_ticket action signature.
		$template = self::get_template_for_ticket( $ticket_data );

		if ( $template ) {
			echo self::render_template( $template, $ticket_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returns pre-escaped ticket markup built by the renderer.
		}
	}

	/**
	 * Ticket shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function ticket_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'order_id'    => 0,
				'item_id'     => 0,
				'template_id' => 0,
			),
			$atts
		);

		$order_id = absint( $atts['order_id'] );
		$item_id  = absint( $atts['item_id'] );

		if ( ! $order_id || ! $item_id ) {
			return '';
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return '';
		}

		// Check permission.
		if ( ! current_user_can( 'manage_options' ) && $order->get_customer_id() !== get_current_user_id() ) {
			return '';
		}

		$ticket_data = self::get_ticket_data_from_order( $order, $item_id );

		if ( empty( $ticket_data ) ) {
			return '';
		}

		$template = null;
		if ( $atts['template_id'] ) {
			$template = new TC_Ticket_Designer_Template( absint( $atts['template_id'] ) );
		} else {
			$template = self::get_template_for_ticket( $ticket_data );
		}

		if ( ! $template ) {
			return '';
		}

		return self::render_template( $template, $ticket_data );
	}

	/**
	 * Get template for ticket.
	 *
	 * @param array $ticket_data Ticket data.
	 * @return TC_Ticket_Designer_Template|null
	 */
	private static function get_template_for_ticket( $ticket_data ) {
		$event_id       = $ticket_data['event_id'] ?? 0;
		$ticket_type_id = $ticket_data['product_id'] ?? 0;

		return TC_Ticket_Designer_Template::get_for_ticket( $event_id, $ticket_type_id );
	}

	/**
	 * Render template with data.
	 *
	 * @param TC_Ticket_Designer_Template $template    Template object.
	 * @param array                   $ticket_data Ticket data.
	 * @return string HTML output.
	 */
	public static function render_template( $template, $ticket_data ) {
		$template_array = $template->get_template_array();
		$settings       = $template->get_settings_array();

		if ( empty( $template_array ) ) {
			return '';
		}

		$width      = $template_array['width'] ?? 600;
		$height     = $template_array['height'] ?? 250;
		$background = $template_array['background'] ?? '#ffffff';
		$elements   = $template_array['elements'] ?? array();

		ob_start();
		?>
		<div class="venuera-ticket" style="width: <?php echo esc_attr( $width ); ?>px; height: <?php echo esc_attr( $height ); ?>px; background: <?php echo esc_attr( $background ); ?>; position: relative; overflow: hidden;">
			<?php foreach ( $elements as $element ) : ?>
				<?php echo TC_Ticket_Designer_Element::render( $element, $ticket_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Element renderer returns pre-escaped markup. ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get ticket data from order item.
	 *
	 * Each order item may represent several real tickets (quantity > 1 and/or
	 * multiple attendees/seats). Passing a 1-based $ticket_index builds the
	 * dataset for that specific ticket: its own attendee fields, its real
	 * ticket UID and QR source, and its occurrence when the ticket carries one.
	 *
	 * @param WC_Order $order        Order object.
	 * @param int      $item_id      Order item ID.
	 * @param int      $ticket_index Optional 1-based ticket index within the item.
	 *                               0 (default) keeps the legacy whole-item behavior
	 *                               (first attendee, synthetic item-level ticket id).
	 * @return array
	 */
	public static function get_ticket_data_from_order( $order, $item_id, $ticket_index = 0 ) {
		$item = $order->get_item( $item_id );

		if ( ! $item ) {
			return array();
		}

		$ticket_index = absint( $ticket_index );

		$product = $item->get_product();
		// Order-item meta is written with the `_venuera_` prefix by the WC integration;
		// fall back to the product-level `_event_id`. For a variable event ticket the
		// event id lives on the PARENT product, so check there too (the variation
		// itself has no `_event_id`).
		$event_id = $item->get_meta( '_venuera_event_id' );
		if ( ! $event_id && $product ) {
			$event_id = $product->get_meta( '_event_id' );
			if ( ! $event_id && $product->get_parent_id() ) {
				$event_id = get_post_meta( $product->get_parent_id(), '_event_id', true );
			}
		}

		if ( ! $event_id ) {
			return array();
		}

		$event = Venuera_Event::get( $event_id );

		// Resolve the real ticket row for this index (1-based) where available.
		// The order item stores the created ticket-row ids in `_venuera_ticket_ids`.
		$real_ticket = null;
		if ( $ticket_index >= 1 && class_exists( 'Venuera_Ticket' ) ) {
			$ticket_ids = $item->get_meta( '_venuera_ticket_ids' );
			if ( is_array( $ticket_ids ) && isset( $ticket_ids[ $ticket_index - 1 ] ) ) {
				$real_ticket = Venuera_Ticket::get( (int) $ticket_ids[ $ticket_index - 1 ] );
			}
		}

		$occurrence_id   = $item->get_meta( '_venuera_occurrence_id' );
		$occurrence_data = null;

		// Prefer the per-ticket occurrence when the real ticket carries one.
		if ( $real_ticket ) {
			$ticket_occurrence_id = $real_ticket->get_event_occurrence_id();
			if ( $ticket_occurrence_id ) {
				$occurrence_id = $ticket_occurrence_id;
			}
		}

		if ( $occurrence_id ) {
			// Occurrence id may be a numeric DB id or a "virtual:Y-m-d H:i:s" string.
			// Resolve via the Recurring Events add-on's filter API (no direct
			// add-on class reference in core). With the add-on inactive these
			// return null and the ticket simply renders without occurrence data.
			if ( is_string( $occurrence_id ) && 0 === strpos( $occurrence_id, 'virtual:' ) ) {
				$occurrence_datetime = urldecode( substr( $occurrence_id, 8 ) );
				$occurrence_data     = apply_filters( 'tickera_find_occurrence_by_datetime', null, $event_id, $occurrence_datetime );
			} else {
				$occurrence_data = apply_filters( 'tickera_get_occurrence', null, $occurrence_id );
			}
		}

		// Build ticket data.
		$data = array(
			'event_id'          => $event_id,
			'product_id'        => $product ? $product->get_id() : 0,
			'order_id'          => $order->get_id(),
			'order_item_id'     => $item_id,
			'event_name'        => $event ? $event->get_title() : '',
			'event_date'        => '',
			'event_time'        => '',
			'event_datetime'    => '',
			'event_description' => $event ? wp_strip_all_tags( $event->get_description() ) : '',
			'event_image'       => $event ? $event->get_image_url( 'large' ) : '',
			'venue_name'        => $event ? $event->get_venue_name() : '',
			'venue_address'     => $event ? $event->get_venue_address() : '',
			'ticket_type'       => $product ? $product->get_name() : '',
			'ticket_price'      => $item->get_total() ? wc_price( $item->get_total() ) : '',
			'ticket_id'         => self::generate_ticket_id( $order->get_id(), $item_id ),
			'order_date'        => $order->get_date_created() ? wp_date( get_option( 'date_format' ), $order->get_date_created()->getTimestamp() ) : '',
			'qr_code'           => '',
			'seat_info'         => '',
			'seat_row'          => '',
			'seat_number'       => '',
			'seat_zone'         => '',
			'table_info'        => '',
			'table_name'        => '',
			'attendee_name'     => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'attendee_email'    => $order->get_billing_email(),
			'attendee_phone'    => $order->get_billing_phone(),
		);

		// When a real ticket row is resolved for this index, use its own UID as the
		// ticket id and its QR hash (falling back to the UID) as the QR source so each
		// generated PDF carries that specific ticket's identifier.
		if ( $real_ticket ) {
			$real_uid = $real_ticket->get_ticket_uid();
			if ( $real_uid ) {
				$data['ticket_id'] = $real_uid;
			}
			$qr_hash         = $real_ticket->get_qr_code_hash();
			$data['qr_code'] = $qr_hash ? $qr_hash : ( $real_uid ? $real_uid : $data['ticket_id'] );

			$ticket_type_label = $real_ticket->get_ticket_type_label();
			if ( $ticket_type_label ) {
				$data['ticket_type'] = $ticket_type_label;
			}
		}

		// Default QR source to the ticket id when no dedicated QR value was set.
		if ( empty( $data['qr_code'] ) ) {
			$data['qr_code'] = $data['ticket_id'];
		}

		// Set date/time from occurrence or event.
		if ( $occurrence_data ) {
			$datetime               = strtotime( $occurrence_data->start_datetime );
			$data['event_date']     = wp_date( get_option( 'date_format' ), $datetime );
			$data['event_time']     = wp_date( get_option( 'time_format' ), $datetime );
			$data['event_datetime'] = $data['event_date'] . ' ' . $data['event_time'];
		} elseif ( $event ) {
			$start_datetime = $event->get_start_datetime();
			if ( $start_datetime ) {
				$datetime               = strtotime( $start_datetime );
				$data['event_date']     = wp_date( get_option( 'date_format' ), $datetime );
				$data['event_time']     = wp_date( get_option( 'time_format' ), $datetime );
				$data['event_datetime'] = $data['event_date'] . ' ' . $data['event_time'];
			}
		}

		// Seat info. Venue addon writes `_venuera_seat_label`; keep the legacy
		// unprefixed `venuera_seat_label` as a fallback for older orders.
		$seat_label = $item->get_meta( '_venuera_seat_label' ) ? $item->get_meta( '_venuera_seat_label' ) : $item->get_meta( 'venuera_seat_label' );
		if ( $seat_label ) {
			$data['seat_info'] = $seat_label;

			// Try to parse row and seat.
			if ( preg_match( '/^([A-Z]+)(\d+)$/i', $seat_label, $matches ) ) {
				$data['seat_row']    = $matches[1];
				$data['seat_number'] = $matches[2];
			}
		}

		$zone_id = $item->get_meta( '_venuera_zone_id' ) ? $item->get_meta( '_venuera_zone_id' ) : $item->get_meta( 'venuera_zone_id' );
		if ( $zone_id ) {
			// Resolve the stored zone id to its human-readable zone name from the
			// event's assigned venue design when the venue addon is available;
			// fall back to the raw id so the field is never blank.
			$data['seat_zone'] = self::resolve_zone_name( $event_id, $zone_id );
		}

		// Table info. No table-booking order-item meta is written by the plugin today,
		// but read the prefixed key for forward compatibility.
		$table_label = $item->get_meta( '_venuera_table_label' );
		if ( $table_label ) {
			$data['table_info'] = $table_label;
			$data['table_name'] = $table_label;
		}

		// Attendee fields. Attendee Fields addon writes `_venuera_attendee_data`
		// keyed by ticket index (1..N), each entry being field_id => [label,value,type].
		// For a specific ticket index, use that attendee's fields; otherwise (legacy
		// whole-item behavior) flatten the first attendee's fields.
		$attendee_data = $item->get_meta( '_venuera_attendee_data' );
		if ( is_array( $attendee_data ) && ! empty( $attendee_data ) ) {
			if ( $ticket_index >= 1 && isset( $attendee_data[ $ticket_index ] ) ) {
				$attendee_entry = $attendee_data[ $ticket_index ];
			} else {
				$attendee_entry = reset( $attendee_data );
			}
			if ( is_array( $attendee_entry ) ) {
				// Canonical IDs for the standard attendee fields. When present
				// on a ticket, they OVERRIDE the order-billing fallback set
				// earlier in this method, so the Attendee Name/Email/Phone
				// ticket-designer elements display the per-attendee value
				// (not the buyer's billing info) — which matches the labels
				// users see in the editor.
				$standard_ids = array( 'attendee_name', 'attendee_email', 'attendee_phone' );

				foreach ( $attendee_entry as $field_id => $field ) {
					$value = is_array( $field ) && isset( $field['value'] ) ? $field['value'] : $field;
					if ( is_array( $value ) ) {
						$value = implode( ', ', $value );
					}

					if ( in_array( $field_id, $standard_ids, true ) ) {
						$value_str = trim( (string) $value );
						if ( '' !== $value_str ) {
							$data[ $field_id ] = $value_str;
						}
					} else {
						$data[ 'attendee_field_' . $field_id ] = $value;
					}
				}
			}
		}

		return apply_filters( 'tickera_ticket_data', $data, $order, $item );
	}

	/**
	 * Generate unique ticket ID.
	 *
	 * @param int $order_id Order ID.
	 * @param int $item_id  Order item ID.
	 * @return string
	 */
	public static function generate_ticket_id( $order_id, $item_id ) {
		$prefix = apply_filters( 'tickera_ticket_id_prefix', 'TKT' );
		return sprintf( '%s-%06d-%04d', $prefix, $order_id, $item_id );
	}

	/**
	 * Resolve a stored venue zone id to its human-readable zone name.
	 *
	 * Zones live in the venue design's JSON blob as `zones => [ { id, name, … } ]`.
	 * The chart assigned to the event is loaded via the venue addon; the zone
	 * whose id matches is returned by name. Falls back to the raw zone id when the
	 * venue addon is unavailable or the zone cannot be resolved.
	 *
	 * @param int    $event_id Event ID.
	 * @param string $zone_id  Stored zone id.
	 * @return string Zone name, or the raw zone id as a fallback.
	 */
	private static function resolve_zone_name( $event_id, $zone_id ) {
		if ( ! $event_id || ! class_exists( 'Venuera_Venue_Chart' ) ) {
			return (string) $zone_id;
		}

		$chart = Venuera_Venue_Chart::get_for_event( $event_id );
		if ( ! $chart || ! method_exists( $chart, 'get_chart_data_array' ) ) {
			return (string) $zone_id;
		}

		$chart_data = $chart->get_chart_data_array();
		if ( empty( $chart_data['zones'] ) || ! is_array( $chart_data['zones'] ) ) {
			return (string) $zone_id;
		}

		foreach ( $chart_data['zones'] as $zone ) {
			if ( is_array( $zone ) && isset( $zone['id'] ) && (string) $zone['id'] === (string) $zone_id ) {
				if ( ! empty( $zone['name'] ) ) {
					return (string) $zone['name'];
				}
				break;
			}
		}

		return (string) $zone_id;
	}
}

