<?php
/**
 * Tickera Ticket Designer — Field provider & data adapter.
 *
 * Bridges the (free-form) ticket designer to Tickera's real data model.
 *
 *  - data_fields()         : the grouped field list the designer's picker shows.
 *  - sample_data()         : sample values used for the editor/PDF preview.
 *  - resolve_ticket_data() : real values for a given ticket instance id, used by
 *                            the render adapter at PDF time.
 *
 * Field sources (conditional on what's active):
 *  - Core     : event / ticket / attendee / buyer / order (always).
 *  - Seating  : seat_label / seat_id            (when Seating Charts is active).
 *  - Custom   : Custom Forms buyer + owner fields (when Custom Forms is active).
 *  - Woo      : billing / shipping / order info  (when Bridge for WooCommerce active).
 *
 * @package Tickera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field provider & data adapter.
 */
class TC_Ticket_Designer_Fields {

	/* --------------------------------------------------------------------- *
	 * Capability detection
	 * --------------------------------------------------------------------- */

	/**
	 * Is the Bridge for WooCommerce add-on active?
	 *
	 * @return bool
	 */
	public static function bridge_active() {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return (bool) apply_filters( 'tc_bridge_for_woocommerce_is_active', false ) && class_exists( 'WC_Order' );
	}

	/**
	 * Is the Seating Charts add-on active?
	 *
	 * @return bool
	 */
	public static function seating_active() {
		return class_exists( '\Tickera\TC_Seat_Chart' ) || class_exists( 'TC_Seat_Chart' );
	}

	/**
	 * Is the Custom Forms add-on active?
	 *
	 * @return bool
	 */
	public static function custom_forms_active() {
		return class_exists( '\Tickera\TC_Custom_Fields' ) || class_exists( 'TC_Custom_Fields' );
	}

	/* --------------------------------------------------------------------- *
	 * Field picker (grouped) — shown in the designer's "Field" dropdown.
	 * --------------------------------------------------------------------- */

	/**
	 * Grouped, labelled field list for the designer picker.
	 *
	 * @return array group_key => [ 'label' => string, 'fields' => [ key => label ] ]
	 */
	public static function data_fields() {

		$groups = array(
			'event'    => array(
				'label'  => __( 'Event', 'tickera-event-ticketing-system' ),
				'fields' => array(
					'event_name'        => __( 'Event Name', 'tickera-event-ticketing-system' ),
					'event_datetime'    => __( 'Event Date & Time', 'tickera-event-ticketing-system' ),
					'venue_name'        => __( 'Event Location', 'tickera-event-ticketing-system' ),
					'event_category'    => __( 'Event Category', 'tickera-event-ticketing-system' ),
					'event_terms'       => __( 'Event Terms & Conditions', 'tickera-event-ticketing-system' ),
				),
			),
			'ticket'   => array(
				'label'  => __( 'Ticket', 'tickera-event-ticketing-system' ),
				'fields' => array(
					'ticket_type'        => __( 'Ticket Type', 'tickera-event-ticketing-system' ),
					'ticket_description' => __( 'Ticket Description', 'tickera-event-ticketing-system' ),
					'ticket_code'        => __( 'Ticket Code', 'tickera-event-ticketing-system' ),
					'ticket_number'      => __( 'Ticket ID', 'tickera-event-ticketing-system' ),
				),
			),
			'attendee' => array(
				'label'  => __( 'Attendee', 'tickera-event-ticketing-system' ),
				'fields' => array(
					'attendee_name' => __( 'Ticket Owner Name', 'tickera-event-ticketing-system' ),
				),
			),
			'order'    => array(
				'label'  => __( 'Order / Buyer', 'tickera-event-ticketing-system' ),
				'fields' => array(
					'buyer_name' => __( 'Ticket Buyer Name', 'tickera-event-ticketing-system' ),
					'order_date' => __( 'Date of Purchase', 'tickera-event-ticketing-system' ),
				),
			),
		);

		// Anything registered through tickera_register_template_element() — Custom
		// Forms fields flagged "as ticket template", seating add-ons, and any
		// third-party element — appears here automatically. Same hook-based
		// extensibility as the classic ticket template editor.
		$addon = self::registered_element_fields();
		if ( ! empty( $addon ) ) {
			$groups['addons'] = array(
				'label'  => __( 'Add-on Fields', 'tickera-event-ticketing-system' ),
				'fields' => $addon,
			);
		}

		return apply_filters( 'tickera_ticket_designer_data_fields', $groups );
	}

	/**
	 * Enumerate Custom Forms fields as designer picker entries.
	 *
	 * Keys carry enough info to resolve later, without re-querying:
	 *  - owner field : "cf_owner_{field_name}"   (value lives in instance meta)
	 *  - buyer field : "cf_buyer_{field_post_id}" (value lives in order buyer_data)
	 *
	 * @return array key => label
	 */
	public static function custom_form_fields() {
		$out = array();

		$forms = get_posts(
			array(
				'post_type'      => 'tc_forms',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $forms ) ) {
			return $out;
		}

		foreach ( $forms as $form_id ) {
			$form_type = get_post_meta( $form_id, 'form_type', true );
			$form_type = ( 'buyer' === $form_type ) ? 'buyer' : 'owner';

			$fields = get_posts(
				array(
					'post_type'      => 'tc_form_fields',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post_parent'    => $form_id,
				)
			);

			if ( empty( $fields ) ) {
				continue;
			}

			foreach ( $fields as $field ) {
				$label = $field->post_title;
				if ( '' === trim( (string) $label ) ) {
					continue;
				}

				if ( 'buyer' === $form_type ) {
					$key = 'cf_buyer_' . (int) $field->ID;
				} else {
					$name = get_post_meta( $field->ID, 'name', true );
					if ( '' === trim( (string) $name ) ) {
						continue;
					}
					$key = 'cf_owner_' . $name;
				}

				$out[ $key ] = $label;
			}
		}

		return $out;
	}

	/**
	 * Built-in core element names already represented by the curated groups
	 * (and the native QR/barcode/image element types). Excluded from the
	 * auto-discovered "Add-on Fields" group to avoid duplicates.
	 *
	 * @return string[]
	 */
	public static function core_element_names() {
		return array(
			'tc_event_name_element', 'tc_event_date_time_element', 'tc_event_location_element',
			'tc_event_categories_element', 'tc_event_terms_element', 'tc_event_logo_element',
			'tc_sponsors_logos_element', 'tc_custom_image_element', 'tc_google_map_element',
			'tc_ticket_type_element', 'tc_ticket_description_element', 'tc_ticket_code_element',
			'tc_ticket_id_element', 'tc_ticket_qr_code_element', 'tc_ticket_barcode_element_core',
			'tc_ticket_buyer_name_element', 'tc_ticket_owner_name_element', 'tc_ticket_date_purchase_element',
		);
	}

	/**
	 * Map of add-on (hook-registered) text fields → element class/title.
	 *
	 * Reads Tickera's global template-element registry so any field added via
	 * tickera_register_template_element() is available in the designer too.
	 * Visual elements (QR, barcode, image, logo, map) are skipped — those are
	 * placed via the designer's native element types.
	 *
	 * @return array key => [ 'class' => string, 'title' => string ]
	 */
	public static function registered_element_map() {
		$map = array();

		if ( empty( $GLOBALS['tickera_template_elements'] ) || ! is_array( $GLOBALS['tickera_template_elements'] ) ) {
			return $map;
		}

		$exclude = self::core_element_names();
		$visual  = array( 'qr', 'barcode', 'logo', 'image', 'map', 'google', 'sponsor' );

		foreach ( $GLOBALS['tickera_template_elements'] as $el ) {
			$class = is_array( $el ) ? ( isset( $el[0] ) ? $el[0] : '' ) : ( is_string( $el ) ? $el : '' );
			if ( ! $class || ! class_exists( $class ) ) {
				continue;
			}

			try {
				$inst = new $class();
			} catch ( \Throwable $e ) {
				continue;
			}

			$name = isset( $inst->element_name ) ? (string) $inst->element_name : '';
			if ( '' === $name || in_array( $name, $exclude, true ) ) {
				continue;
			}

			$lname = strtolower( $name );
			$skip  = false;
			foreach ( $visual as $v ) {
				if ( false !== strpos( $lname, $v ) ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}

			$title = ( isset( $inst->element_title ) && '' !== $inst->element_title ) ? (string) $inst->element_title : $name;

			$map[ 'el_' . $name ] = array(
				'class' => $class,
				'title' => $title,
			);
		}

		return $map;
	}

	/**
	 * Add-on fields as a flat key => label list for the picker.
	 *
	 * @return array
	 */
	public static function registered_element_fields() {
		$out = array();
		foreach ( self::registered_element_map() as $key => $info ) {
			$out[ $key ] = $info['title'];
		}
		return $out;
	}

	/**
	 * Convert rich text to plain text while preserving line breaks (so a ticket
	 * description / terms keeps its paragraph structure on the canvas/PDF).
	 *
	 * @param string $html
	 * @return string
	 */
	public static function to_plain_text( $html ) {
		$html = (string) $html;
		$html = preg_replace( '/<\s*br\s*\/?\s*>/i', "\n", $html );
		$html = preg_replace( '/<\/\s*(p|div|li|h[1-6])\s*>/i', "\n", $html );
		return trim( wp_strip_all_tags( $html ) );
	}

	/**
	 * Build a Google Static Maps image URL for an address (classic Google Map
	 * element). Returns '' when there's no address or no API key configured.
	 *
	 * @param string $address Event location.
	 * @param int    $width   Map width (px).
	 * @param int    $height  Map height (px).
	 * @param int    $zoom    Zoom level.
	 * @param string $type    Map type (roadmap, satellite, …).
	 * @return string
	 */
	public static function google_map_url( $address, $width = 600, $height = 300, $zoom = 13, $type = 'roadmap' ) {
		$address = trim( (string) $address );
		if ( '' === $address ) {
			return '';
		}

		$settings = get_option( 'tickera_general_setting', array() );
		$key      = ( is_array( $settings ) && ! empty( $settings['google_maps_api_key'] ) ) ? $settings['google_maps_api_key'] : '';
		if ( '' === $key ) {
			return '';
		}

		return 'https://maps.googleapis.com/maps/api/staticmap?center=' . rawurlencode( $address )
			. '&zoom=' . (int) $zoom
			. '&scale=2&size=' . (int) $width . 'x' . (int) $height
			. '&maptype=' . rawurlencode( $type )
			. '&markers=' . rawurlencode( 'color:red|' . $address )
			. '&key=' . rawurlencode( $key );
	}

	/* --------------------------------------------------------------------- *
	 * Sample data (editor + PDF preview)
	 * --------------------------------------------------------------------- */

	/**
	 * Sample values keyed by field id, for the design-time preview.
	 *
	 * @return array
	 */
	public static function sample_data() {
		$data = array(
			'event_name'          => __( 'Sample Event', 'tickera-event-ticketing-system' ),
			'event_date'          => date_i18n( get_option( 'date_format' ) ),
			'event_time'          => date_i18n( get_option( 'time_format' ) ),
			'event_datetime'      => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'venue_name'          => __( 'Main Hall, 123 Example St', 'tickera-event-ticketing-system' ),
			'event_category'      => __( 'Concert', 'tickera-event-ticketing-system' ),
			'event_terms'         => __( 'No refunds. This ticket admits one person.', 'tickera-event-ticketing-system' ),
			'ticket_type'         => __( 'General Admission', 'tickera-event-ticketing-system' ),
			'ticket_description'  => __( 'Admit one', 'tickera-event-ticketing-system' ),
			'ticket_code'         => 'ABC123-1',
			'ticket_id'           => 'ABC123-1',
			'ticket_number'       => '1024',
			'qr_code'             => 'ABC123-1',
			'barcode'             => 'ABC123-1',
			'event_image'         => '',
			'event_logo'          => '',
			'sponsors_logo'       => '',
			'google_map'          => '',
			'attendee_name'       => 'John Doe',
			'attendee_first_name' => 'John',
			'attendee_last_name'  => 'Doe',
			'attendee_email'      => 'john@example.com',
			'buyer_name'          => 'John Doe',
			'buyer_email'         => 'john@example.com',
			'order_id'            => '1024',
			'order_date'          => date_i18n( get_option( 'date_format' ) ),
			'seat_label'          => 'Row A, Seat 12',
			'seat_id'             => 'A-12',
		);

		// Preview text for add-on / hook-registered fields. There's no generic way
		// to know a third-party element's real output, so show the element's own
		// name (e.g. "WooCommerce Order Summary") — clearer than a bare "Sample".
		// Known elements (Bridge billing/shipping below) override this with a
		// realistic sample.
		foreach ( self::registered_element_fields() as $el_key => $el_label ) {
			$data[ $el_key ] = ( '' !== trim( (string) $el_label ) )
				? (string) $el_label
				: __( 'Sample', 'tickera-event-ticketing-system' );
		}
		if ( self::bridge_active() ) {
			$data['woo_billing_first_name'] = 'John';
			$data['woo_billing_last_name']  = 'Doe';
			$data['woo_billing_company']    = 'Example Ltd';
			$data['woo_billing_address']    = '123 Example St';
			$data['woo_billing_city']       = 'Amsterdam';
			$data['woo_billing_postcode']   = '1011 AB';
			$data['woo_billing_country']    = 'NL';
			$data['woo_billing_email']      = 'john@example.com';
			$data['woo_billing_phone']      = '+31 20 123 4567';
			$data['woo_shipping_first_name'] = 'John';
			$data['woo_shipping_last_name']  = 'Doe';
			$data['woo_shipping_company']    = 'Example Ltd';
			$data['woo_shipping_address']    = '123 Example St';
			$data['woo_shipping_city']       = 'Amsterdam';
			$data['woo_shipping_postcode']   = '1011 AB';
			$data['woo_shipping_country']    = 'NL';

			// Realistic multi-line preview for the Bridge Billing/Shipping Info
			// elements (placed via the picker as `el_<element_name>`), so the
			// canvas shows an address block instead of the generic "Sample".
			// Order/line layout matches the real element output (empty lines dropped).
			$data['el_tc_woo_billing_info_element']  = "John Doe\nExample Ltd\n123 Example St\nAmsterdam\n1011 AB\nNetherlands\njohn@example.com\n+31 20 123 4567";
			$data['el_tc_woo_shipping_info_element'] = "John Doe\nExample Ltd\n123 Example St\nAmsterdam\n1011 AB\nNetherlands";
		}

		return apply_filters( 'tickera_ticket_designer_sample_data', $data );
	}

	/* --------------------------------------------------------------------- *
	 * Real data resolution (render adapter)
	 * --------------------------------------------------------------------- */

	/**
	 * Build the flat ticket_data array for a real ticket instance.
	 *
	 * @param int $ticket_instance_id tc_tickets_instances post ID.
	 * @return array key => value
	 */
	public static function resolve_ticket_data( $ticket_instance_id ) {

		$ticket_instance_id = (int) $ticket_instance_id;
		$data               = array();

		if ( ! $ticket_instance_id || ! class_exists( '\Tickera\TC_Ticket_Instance' ) ) {
			return apply_filters( 'tickera_ticket_designer_ticket_data', $data, $ticket_instance_id );
		}

		$instance    = new \Tickera\TC_Ticket_Instance( $ticket_instance_id );
		$raw_type_id = isset( $instance->details->ticket_type_id ) ? (int) $instance->details->ticket_type_id : 0;
		$order_id    = isset( $instance->details->post_parent ) ? (int) $instance->details->post_parent : 0;

		// Bridge for WooCommerce stores a WooCommerce product id on the ticket
		// instance; the `tickera_ticket_type_id` filter maps it to the real
		// Tickera ticket-type id. The classic ticket template always resolves
		// through this filter, so without it the event/ticket-type lookups come
		// back empty under Bridge (Event Name, Date & Time, Location, Category,
		// Terms all blank). Resolve it the same way the classic elements do.
		$type_id = function_exists( 'tickera_apply_filters' )
			? (int) tickera_apply_filters( 'tickera_ticket_type_id', $raw_type_id )
			: $raw_type_id;

		// Event id via TC_Ticket::get_ticket_event() — mirrors the classic
		// elements and also honors the `tickera_event_name_field_name` filter.
		$event_id = 0;
		if ( $type_id && class_exists( '\Tickera\TC_Ticket' ) ) {
			$tc_ticket = new \Tickera\TC_Ticket();
			$event_id  = (int) $tc_ticket->get_ticket_event( $type_id );
		} elseif ( $type_id ) {
			$event_id = (int) get_post_meta( $type_id, 'event_name', true );
		}

		// --- Ticket / code (QR + barcode bind to the Tickera ticket_code) ---
		$ticket_code         = isset( $instance->details->ticket_code ) ? (string) $instance->details->ticket_code : '';
		$data['ticket_code']   = $ticket_code;
		$data['ticket_id']     = $ticket_code;
		$data['qr_code']       = $ticket_code;
		$data['barcode']       = $ticket_code;
		$data['ticket_number'] = (string) $ticket_instance_id;

		// --- Ticket type ---
		if ( $type_id ) {
			// Ticket type label: mirror the classic "Ticket Type" element — use the
			// RAW ticket type id (the variation for variable products) and run it
			// through the title filter so Bridge appends the variation attributes
			// (e.g. "Concert Pass (Day: Saturday)") instead of just the parent
			// product name. For simple products / standalone types the filter
			// returns the plain title unchanged.
			$type_title = get_the_title( $raw_type_id );
			if ( function_exists( 'tickera_apply_filters' ) ) {
				$type_title = (string) tickera_apply_filters( 'tickera_checkout_owner_info_ticket_title', $type_title, $raw_type_id, array(), $ticket_instance_id );
			}
			$data['ticket_type']        = trim( wp_strip_all_tags( (string) $type_title ) );
			$data['ticket_description'] = self::to_plain_text( (string) get_post_field( 'post_content', $type_id ) );
		}

		// --- Event ---
		if ( $event_id ) {
			$data['event_name']     = get_the_title( $event_id );
			$start                  = get_post_meta( $event_id, 'event_date_time', true );
			$end                    = get_post_meta( $event_id, 'event_end_date_time', true );
			$data['event_date']     = $start ? date_i18n( get_option( 'date_format' ), strtotime( $start ) ) : '';
			$data['event_time']     = $start ? date_i18n( get_option( 'time_format' ), strtotime( $start ) ) : '';
			$data['event_datetime'] = $start ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $start ) ) : '';
			// Only show a "start - end" range when the end actually differs from the
			// start; otherwise the range is redundant (e.g. "Sep 9, 2026 7:00 PM to
			// Sep 9, 2026 7:00 PM") and the date/time should be printed once.
			if ( $end && strtotime( $end ) !== strtotime( $start ) ) {
				$data['event_datetime'] .= ' - ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $end ) );
			}
			$data['venue_name']    = (string) get_post_meta( $event_id, 'event_location', true );
			$data['event_logo']    = (string) get_post_meta( $event_id, 'event_logo_file_url', true );
			$data['sponsors_logo'] = (string) get_post_meta( $event_id, 'sponsors_logo_file_url', true );
			$data['event_terms']   = self::to_plain_text( (string) get_post_meta( $event_id, 'event_terms', true ) );

			// 'event_image' = the event's featured image, falling back to the logo.
			$thumb               = get_the_post_thumbnail_url( $event_id, 'full' );
			$data['event_image'] = $thumb ? $thumb : $data['event_logo'];

			// 'google_map' = Google Static Maps image of the event location
			// (classic Google Map element). Empty unless an API key is set.
			$data['google_map'] = self::google_map_url( isset( $data['venue_name'] ) ? $data['venue_name'] : '' );

			$terms = get_the_terms( $event_id, 'event_category' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$data['event_category'] = implode( ', ', array_map( 'ucfirst', wp_list_pluck( $terms, 'name' ) ) );
			}
		}

		// --- Attendee (ticket owner) ---
		$first                       = isset( $instance->details->first_name ) ? (string) $instance->details->first_name : '';
		$last                        = isset( $instance->details->last_name ) ? (string) $instance->details->last_name : '';
		$data['attendee_first_name'] = $first;
		$data['attendee_last_name']  = $last;
		$data['attendee_name']       = trim( $first . ' ' . $last );
		$data['attendee_email']      = isset( $instance->details->owner_email ) ? (string) $instance->details->owner_email : '';

		// --- Order / buyer ---
		$order = null;
		if ( $order_id && class_exists( '\Tickera\TC_Order' ) ) {
			$order               = new \Tickera\TC_Order( $order_id );
			$data['order_id']    = get_the_title( $order_id );
			$order_post_date     = get_post_field( 'post_date', $order_id );
			$data['order_date']  = $order_post_date ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order_post_date ) ) : '';
			$cart_info           = isset( $order->details->tc_cart_info ) && is_array( $order->details->tc_cart_info ) ? $order->details->tc_cart_info : array();
			$buyer               = isset( $cart_info['buyer_data'] ) && is_array( $cart_info['buyer_data'] ) ? $cart_info['buyer_data'] : array();
			$bfn                 = isset( $buyer['first_name_post_meta'] ) ? $buyer['first_name_post_meta'] : '';
			$bln                 = isset( $buyer['last_name_post_meta'] ) ? $buyer['last_name_post_meta'] : '';
			$buyer_name          = trim( $bfn . ' ' . $bln );
			// For WooCommerce-created orders (Bridge) the native cart_info
			// buyer_data is empty; Bridge fills the buyer name from the WC
			// billing fields via this filter, exactly like the classic
			// "Ticket Buyer Name" element.
			$data['buyer_name']  = function_exists( 'tickera_apply_filters' )
				? (string) wp_strip_all_tags( (string) tickera_apply_filters( 'tickera_ticket_buyer_name_element', $buyer_name, $order_id ) )
				: $buyer_name;
			$data['buyer_email'] = isset( $buyer['email_post_meta'] ) ? $buyer['email_post_meta'] : '';
		}

		// --- Seating ---
		if ( self::seating_active() ) {
			$data['seat_label'] = (string) get_post_meta( $ticket_instance_id, 'seat_label', true );
			$data['seat_id']    = (string) get_post_meta( $ticket_instance_id, 'seat_id', true );
		}

		// --- Add-on / hook-registered template elements ---
		// Resolve via each element's own ticket_content() so any field added
		// through tickera_register_template_element() (Custom Forms fields,
		// seating add-ons, third-party elements) prints automatically.
		foreach ( self::registered_element_map() as $key => $info ) {
			try {
				$el = new $info['class']();
				if ( method_exists( $el, 'ticket_content' ) ) {
					$val   = $el->ticket_content( $ticket_instance_id, $type_id );
					// Use to_plain_text (not wp_strip_all_tags) so multi-line element
					// output keeps its line breaks — e.g. the Bridge WooCommerce
					// Billing/Shipping Info elements emit "Name<br/>Address<br/>City…",
					// which must render on separate lines like the classic template
					// instead of collapsing into one run-on string.
					$plain = self::to_plain_text( (string) $val );
					// Those elements append a <br/> after EVERY field, including the
					// empty ones (address line 2, company, state…), which would leave
					// blank gap-lines. Keep one line per non-empty value only.
					$lines        = array_filter(
						array_map( 'trim', explode( "\n", $plain ) ),
						static function ( $line ) {
							return '' !== $line;
						}
					);
					$data[ $key ] = implode( "\n", $lines );
				}
			} catch ( \Throwable $e ) {
				$data[ $key ] = '';
			}
		}

		// --- WooCommerce (Bridge) ---
		if ( self::bridge_active() && $order_id && class_exists( 'WC_Order' ) ) {
			try {
				$wc = new \WC_Order( $order_id );
				if ( $wc instanceof \WC_Order ) {
					$data['woo_billing_first_name'] = $wc->get_billing_first_name();
					$data['woo_billing_last_name']  = $wc->get_billing_last_name();
					$data['woo_billing_company']    = $wc->get_billing_company();
					$data['woo_billing_address']    = trim( $wc->get_billing_address_1() . ' ' . $wc->get_billing_address_2() );
					$data['woo_billing_city']       = $wc->get_billing_city();
					$data['woo_billing_postcode']   = $wc->get_billing_postcode();
					$data['woo_billing_country']    = $wc->get_billing_country();
					$data['woo_billing_email']      = $wc->get_billing_email();
					$data['woo_billing_phone']      = $wc->get_billing_phone();
					$data['woo_shipping_first_name'] = $wc->get_shipping_first_name();
					$data['woo_shipping_last_name']  = $wc->get_shipping_last_name();
					$data['woo_shipping_company']    = $wc->get_shipping_company();
					$data['woo_shipping_address']    = trim( $wc->get_shipping_address_1() . ' ' . $wc->get_shipping_address_2() );
					$data['woo_shipping_city']       = $wc->get_shipping_city();
					$data['woo_shipping_postcode']   = $wc->get_shipping_postcode();
					$data['woo_shipping_country']    = $wc->get_shipping_country();
				}
			} catch ( \Exception $e ) {
				// Order isn't a WooCommerce order (standalone Tickera sale) — skip.
			}
		}

		return apply_filters( 'tickera_ticket_designer_ticket_data', $data, $ticket_instance_id );
	}
}
