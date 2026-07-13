<?php
/**
 * Ticket Element Types
 *
 * @package Venuera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ticket Element class.
 */
class TC_Ticket_Designer_Element {

	/**
	 * Get available element types.
	 *
	 * @return array
	 */
	public static function get_types() {
		return array(
			'dynamic_text' => array(
				'label'       => __( 'Dynamic Text', 'tickera-event-ticketing-system' ),
				'description' => __( 'Text that displays ticket data', 'tickera-event-ticketing-system' ),
				'icon'        => 'format-text',
				'category'    => 'data',
			),
			'static_text'  => array(
				'label'       => __( 'Static Text', 'tickera-event-ticketing-system' ),
				'description' => __( 'Custom text label', 'tickera-event-ticketing-system' ),
				'icon'        => 'editor-textcolor',
				'category'    => 'basic',
			),
			'qr_code'      => array(
				'label'       => __( 'QR Code', 'tickera-event-ticketing-system' ),
				'description' => __( 'Scannable QR code', 'tickera-event-ticketing-system' ),
				'icon'        => 'grid-view',
				'category'    => 'codes',
			),
			'barcode'      => array(
				'label'       => __( 'Barcode', 'tickera-event-ticketing-system' ),
				'description' => __( 'Scannable barcode', 'tickera-event-ticketing-system' ),
				'icon'        => 'barcode',
				'category'    => 'codes',
			),
			'image'        => array(
				'label'       => __( 'Image', 'tickera-event-ticketing-system' ),
				'description' => __( 'Custom image or logo', 'tickera-event-ticketing-system' ),
				'icon'        => 'format-image',
				'category'    => 'media',
			),
			'event_image'  => array(
				'label'       => __( 'Event Image', 'tickera-event-ticketing-system' ),
				'description' => __( 'Featured image of the event', 'tickera-event-ticketing-system' ),
				'icon'        => 'calendar',
				'category'    => 'data',
			),
			'rectangle'    => array(
				'label'       => __( 'Rectangle', 'tickera-event-ticketing-system' ),
				'description' => __( 'Rectangular shape', 'tickera-event-ticketing-system' ),
				// `marker` is a map-pin in Dashicons (looks like a circle);
				// `image-crop` is a clean rectangle outline, which actually
				// reads as a rectangle in the toolbar.
				'icon'        => 'image-crop',
				'category'    => 'shapes',
			),
			'line'         => array(
				'label'       => __( 'Line', 'tickera-event-ticketing-system' ),
				'description' => __( 'Divider line', 'tickera-event-ticketing-system' ),
				'icon'        => 'minus',
				'category'    => 'shapes',
			),
			'custom_field' => array(
				'label'       => __( 'Custom Field', 'tickera-event-ticketing-system' ),
				'description' => __( 'Attendee or event custom field', 'tickera-event-ticketing-system' ),
				'icon'        => 'admin-generic',
				'category'    => 'data',
			),
		);
	}

	/**
	 * Get available data fields for dynamic text.
	 *
	 * @return array
	 */
	public static function get_data_fields() {
		// Tickera field provider (core + Seating + Custom Forms + Woo/Bridge).
		if ( class_exists( 'TC_Ticket_Designer_Fields' ) ) {
			return TC_Ticket_Designer_Fields::data_fields();
		}
		return array(
			'event'    => array(
				'label'  => __( 'Event Data', 'tickera-event-ticketing-system' ),
				'fields' => array(
					'event_name'        => __( 'Event Name', 'tickera-event-ticketing-system' ),
					'event_date'        => __( 'Event Date', 'tickera-event-ticketing-system' ),
					'event_time'        => __( 'Event Time', 'tickera-event-ticketing-system' ),
					'event_datetime'    => __( 'Date & Time', 'tickera-event-ticketing-system' ),
					'venue_name'        => __( 'Venue Name', 'tickera-event-ticketing-system' ),
					'venue_address'     => __( 'Venue Address', 'tickera-event-ticketing-system' ),
					'event_description' => __( 'Event Description', 'tickera-event-ticketing-system' ),
				),
			),
			'ticket'   => array(
				'label'  => __( 'Ticket Data', 'tickera-event-ticketing-system' ),
				'fields' => array(
					'ticket_type'  => __( 'Ticket Type', 'tickera-event-ticketing-system' ),
					'ticket_price' => __( 'Ticket Price', 'tickera-event-ticketing-system' ),
					'ticket_id'    => __( 'Ticket ID', 'tickera-event-ticketing-system' ),
					'order_id'     => __( 'Order ID', 'tickera-event-ticketing-system' ),
					'order_date'   => __( 'Order Date', 'tickera-event-ticketing-system' ),
				),
			),
			'venue'    => array(
				'label'  => __( 'Venue Data', 'tickera-event-ticketing-system' ),
				'fields' => array(
					'seat_info'   => __( 'Seat Info', 'tickera-event-ticketing-system' ),
					'seat_row'    => __( 'Seat Row', 'tickera-event-ticketing-system' ),
					'seat_number' => __( 'Seat Number', 'tickera-event-ticketing-system' ),
					'seat_zone'   => __( 'Seat Zone', 'tickera-event-ticketing-system' ),
					'table_info'  => __( 'Table Info', 'tickera-event-ticketing-system' ),
					'table_name'  => __( 'Table Name', 'tickera-event-ticketing-system' ),
				),
			),
			// Per-ticket attendee fields with stable canonical IDs. Resolved
			// at checkout via the standard attendee fields system (Settings
			// → Options / per-product overrides). Falls back to the order
			// buyer's billing info when the standard field isn't enabled
			// for the product. Dynamic Custom Fields (Iskustvo etc.) appear
			// via the separate `custom_attendee_field` element.
			'attendee' => array(
				'label'  => __( 'Attendee Data', 'tickera-event-ticketing-system' ),
				'fields' => array(
					'attendee_name'  => __( 'Attendee Name', 'tickera-event-ticketing-system' ),
					'attendee_email' => __( 'Attendee Email', 'tickera-event-ticketing-system' ),
					'attendee_phone' => __( 'Attendee Phone', 'tickera-event-ticketing-system' ),
				),
			),
		);
	}

	/**
	 * Canonical sample ticket data used for design-time previews.
	 *
	 * Single source of truth so the editor canvas, the HTML preview and the PDF
	 * preview all render the SAME values (otherwise the PDF "looks different"
	 * from what was designed). Values are fully decoded (no HTML entities).
	 *
	 * @return array
	 */
	public static function get_sample_data() {
		// Tickera sample values (matches the Tickera field provider keys).
		if ( class_exists( 'TC_Ticket_Designer_Fields' ) ) {
			return TC_Ticket_Designer_Fields::sample_data();
		}
		$price = function_exists( 'wc_price' )
			? html_entity_decode( wp_strip_all_tags( wc_price( 99.99 ) ), ENT_QUOTES, 'UTF-8' )
			: '99.99';
		$price = str_replace( "\xc2\xa0", ' ', $price );

		return array(
			'event_name'              => __( 'Sample Event Name', 'tickera-event-ticketing-system' ),
			'event_date'              => wp_date( get_option( 'date_format' ), strtotime( '+7 days' ) ),
			'event_time'              => wp_date( get_option( 'time_format' ), strtotime( '19:30' ) ),
			'event_datetime'          => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( '+7 days 19:30' ) ),
			'event_description'       => __( 'An unforgettable evening of live music.', 'tickera-event-ticketing-system' ),
			'venue_name'              => __( 'Grand Concert Hall', 'tickera-event-ticketing-system' ),
			'venue_address'           => __( '123 Main Street, City', 'tickera-event-ticketing-system' ),
			'ticket_type'             => __( 'VIP Pass', 'tickera-event-ticketing-system' ),
			'ticket_price'            => $price,
			'ticket_id'               => 'TKT-001234',
			'order_id'                => '12345',
			'order_date'              => wp_date( get_option( 'date_format' ) ),
			'seat_info'               => __( 'Section A, Row 5, Seat 12', 'tickera-event-ticketing-system' ),
			'seat_row'                => '5',
			'seat_number'             => '12',
			'seat_zone'               => __( 'Section A', 'tickera-event-ticketing-system' ),
			'table_info'              => __( 'Table 7', 'tickera-event-ticketing-system' ),
			'table_name'              => __( 'Table 7', 'tickera-event-ticketing-system' ),
			'attendee_name'           => 'John Doe',
			'attendee_email'          => 'john@example.com',
			'attendee_phone'          => '+1 555 0123',
			'ticket_number_formatted' => 'TKT-001234',
			'qr_code'                 => 'TWP-XYZ123ABC',
			'barcode'                 => 'TWP-XYZ123ABC',
		);
	}

	/**
	 * Get default properties for an element type.
	 *
	 * @param string $type Element type.
	 * @return array
	 */
	public static function get_defaults( $type ) {
		$defaults = array(
			'dynamic_text' => array(
				'x'             => 50,
				'y'             => 50,
				'width'         => 200,
				'rotation'      => 0,
				'dataField'     => 'event_name',
				'fontSize'      => 14,
				'fontFamily'    => 'Arial',
				'fontWeight'    => 'normal',
				'fontStyle'     => 'normal',
				'fill'          => '#333333',
				'textAlign'     => 'left',
				'label'         => '',
				'labelPosition' => 'before',
				'conditional'   => false,
			),
			'static_text'  => array(
				'x'          => 50,
				'y'          => 50,
				'width'      => 200,
				'text'       => __( 'Enter text here', 'tickera-event-ticketing-system' ),
				'fontSize'   => 14,
				'fontFamily' => 'Arial',
				'fontWeight' => 'normal',
				'fontStyle'  => 'normal',
				'fill'       => '#333333',
				'textAlign'  => 'left',
			),
			'qr_code'      => array(
				'x'                    => 50,
				'y'                    => 50,
				'size'                 => 100,
				'rotation'             => 0,
				'dataField'            => 'ticket_id',
				'errorCorrectionLevel' => 'M',
				'foreground'           => '#000000',
				'background'           => '#ffffff',
			),
			'barcode'      => array(
				'x'          => 50,
				'y'          => 50,
				'width'      => 150,
				'height'     => 50,
				'dataField'  => 'ticket_id',
				'format'     => 'CODE128',
				'showText'   => true,
				'foreground' => '#000000',
				'background' => '#ffffff',
			),
			'image'        => array(
				'x'      => 50,
				'y'      => 50,
				'width'  => 100,
				'height' => 100,
				'src'    => '',
				'fit'    => 'contain',
			),
			'event_image'  => array(
				'x'      => 50,
				'y'      => 50,
				'width'  => 150,
				'height' => 100,
				'fit'    => 'cover',
			),
			'rectangle'    => array(
				'x'           => 50,
				'y'           => 50,
				'width'       => 200,
				'height'      => 100,
				'rotation'    => 0,
				'fill'        => '#f0f0f0',
				'stroke'      => '#cccccc',
				'strokeWidth' => 1,
				'rx'          => 0,
				'ry'          => 0,
			),
			'line'         => array(
				'x'           => 50,
				'y'           => 50,
				'width'       => 200,
				'stroke'      => '#cccccc',
				'strokeWidth' => 1,
				'orientation' => 'horizontal',
			),
			'custom_field' => array(
				'x'             => 50,
				'y'             => 50,
				'width'         => 200,
				'fieldId'       => '',
				'fieldSource'   => 'attendee',
				'fontSize'      => 14,
				'fontFamily'    => 'Arial',
				'fontWeight'    => 'normal',
				'fill'          => '#333333',
				'label'         => '',
				'labelPosition' => 'before',
			),
		);

		return isset( $defaults[ $type ] ) ? $defaults[ $type ] : array();
	}

	/**
	 * Get barcode formats.
	 *
	 * @return array
	 */
	public static function get_barcode_formats() {
		return array(
			'CODE128'    => 'Code 128',
			'CODE39'     => 'Code 39',
			'EAN13'      => 'EAN-13',
			'EAN8'       => 'EAN-8',
			'UPC'        => 'UPC',
			'ITF14'      => 'ITF-14',
			'ITF'        => 'Interleaved 2 of 5',
			'MSI'        => 'MSI',
			'pharmacode' => 'Pharmacode',
			'codabar'    => 'Codabar',
		);
	}

	/**
	 * Get QR code error correction levels.
	 *
	 * @return array
	 */
	public static function get_qr_error_levels() {
		return array(
			'L' => __( 'Low (7%)', 'tickera-event-ticketing-system' ),
			'M' => __( 'Medium (15%)', 'tickera-event-ticketing-system' ),
			'Q' => __( 'Quartile (25%)', 'tickera-event-ticketing-system' ),
			'H' => __( 'High (30%)', 'tickera-event-ticketing-system' ),
		);
	}

	/**
	 * Get element categories.
	 *
	 * @return array
	 */
	public static function get_categories() {
		return array(
			'data'   => __( 'Data Elements', 'tickera-event-ticketing-system' ),
			'codes'  => __( 'Codes', 'tickera-event-ticketing-system' ),
			'basic'  => __( 'Basic Elements', 'tickera-event-ticketing-system' ),
			'shapes' => __( 'Shapes', 'tickera-event-ticketing-system' ),
			'media'  => __( 'Media', 'tickera-event-ticketing-system' ),
		);
	}

	/**
	 * Named data type ids that map directly to a ticket_data key.
	 *
	 * Used for backward compatibility with templates saved before elements
	 * carried an explicit `dataField`. The type id IS the ticket_data key.
	 *
	 * @return array<string,string> Map of element type id => ticket_data key.
	 */
	private static function get_named_field_map() {
		return array(
			'event_name'        => 'event_name',
			'event_date'        => 'event_date',
			'event_time'        => 'event_time',
			'event_datetime'    => 'event_datetime',
			'venue_name'        => 'venue_name',
			'venue_address'     => 'venue_address',
			'event_description' => 'event_description',
			'ticket_type'       => 'ticket_type',
			'ticket_price'      => 'ticket_price',
			'ticket_id'         => 'ticket_id',
			'order_id'          => 'order_id',
			'order_date'        => 'order_date',
			'seat_info'         => 'seat_info',
			'seat_row'          => 'seat_row',
			'seat_number'       => 'seat_number',
			'seat_zone'         => 'seat_zone',
			'table_info'        => 'table_info',
			'table_name'        => 'table_name',
			'attendee_name'     => 'attendee_name',
			'attendee_email'    => 'attendee_email',
			'attendee_phone'    => 'attendee_phone',
		);
	}

	/**
	 * Resolve the base type of an element.
	 *
	 * Prefers the editor-saved `baseType`. Falls back to inferring it from the
	 * element type id so older templates (and any type id added by the editor)
	 * still render. Anything unrecognised is treated as text.
	 *
	 * @param array $element Element data.
	 * @return string One of: text, image, qr, barcode, rectangle, line.
	 */
	private static function get_base_type( $element ) {
		$base = isset( $element['baseType'] ) ? (string) $element['baseType'] : '';

		// Normalise the editor's base type names to our internal names.
		switch ( $base ) {
			case 'text':
				return 'text';
			case 'image':
				return 'image';
			case 'qrcode':
			case 'qr':
			case 'qr_code':
				return 'qr';
			case 'barcode':
				return 'barcode';
			case 'rectangle':
			case 'rect':
				return 'rectangle';
			case 'line':
				return 'line';
		}

		// No (recognised) baseType saved: infer from the type id.
		$type = isset( $element['type'] ) ? (string) $element['type'] : '';

		$image_types = array( 'image', 'custom_image', 'logo', 'event_image', 'sponsor_logo' );
		$rect_types  = array( 'rectangle', 'header_bg', 'footer_bg', 'sidebar_panel', 'rounded_box', 'ticket_border' );
		$line_types  = array( 'line', 'horizontal_line', 'vertical_line', 'dashed_line' );

		if ( 'qr_code' === $type || 'qrcode' === $type ) {
			return 'qr';
		}
		if ( 'barcode' === $type ) {
			return 'barcode';
		}
		if ( in_array( $type, $image_types, true ) ) {
			return 'image';
		}
		if ( in_array( $type, $rect_types, true ) ) {
			return 'rectangle';
		}
		if ( in_array( $type, $line_types, true ) ) {
			return 'line';
		}

		// Everything else (named data ids, dynamic_text, static_text, the text
		// variants and custom fields) is text.
		return 'text';
	}

	/**
	 * Render element to HTML for preview/PDF.
	 *
	 * Dispatches by BASE TYPE (text / image / qr / barcode / rectangle / line)
	 * derived from the element so every element type the editor can produce
	 * renders, including named data ids, text/shape/line/image variants and
	 * dynamic/custom-field text.
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data for dynamic fields.
	 * @return string HTML output.
	 */
	public static function render( $element, $ticket_data = array() ) {
		if ( ! is_array( $element ) ) {
			return '';
		}

		switch ( self::get_base_type( $element ) ) {
			case 'image':
				return self::render_image( $element, $ticket_data );
			case 'qr':
				return self::render_qr_code( $element, $ticket_data );
			case 'barcode':
				return self::render_barcode( $element, $ticket_data );
			case 'rectangle':
				return self::render_rectangle( $element, $ticket_data );
			case 'line':
				return self::render_line( $element, $ticket_data );
			case 'text':
			default:
				return self::render_text( $element, $ticket_data );
		}
	}

	/**
	 * Resolve the displayed value of a text/data element.
	 *
	 * Resolution order:
	 * 1. Explicit `dataField` against $ticket_data (covers named data ids that
	 *    carry a dataField, the generic dynamic_text element and custom fields
	 *    whose dataField is `attendee_field_<id>`).
	 * 2. For backward compatibility, when no dataField is present, map the
	 *    element type id to its ticket_data key.
	 * 3. Otherwise fall back to the element's static text (`text`/`staticText`).
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data.
	 * @return array{0:string,1:bool} The resolved value and whether it came from a data field.
	 */
	private static function resolve_text_value( $element, $ticket_data ) {
		$field = isset( $element['dataField'] ) ? (string) $element['dataField'] : '';

		if ( '' !== $field ) {
			$value = isset( $ticket_data[ $field ] ) ? $ticket_data[ $field ] : '';
			return array( (string) $value, true );
		}

		// Backward compat: named data id without a dataField.
		$type = isset( $element['type'] ) ? (string) $element['type'] : '';
		$map  = self::get_named_field_map();
		if ( isset( $map[ $type ] ) ) {
			$key   = $map[ $type ];
			$value = isset( $ticket_data[ $key ] ) ? $ticket_data[ $key ] : '';
			return array( (string) $value, true );
		}

		// Static text element.
		$static = $element['text'] ?? $element['staticText'] ?? '';
		return array( (string) $static, false );
	}

	/**
	 * Build the rotation + opacity CSS for an element.
	 *
	 * Rotation is degrees applied around the top-left origin (matching the
	 * canvas/preview/PDF contract). Opacity is a 0-1 float. Returns an empty
	 * string when neither is set so default elements are untouched.
	 *
	 * @param array $element Element data.
	 * @return string CSS snippet.
	 */
	private static function get_transform_styles( $element ) {
		$css = '';

		$rotation = isset( $element['rotation'] ) ? floatval( $element['rotation'] ) : 0;
		if ( 0.0 !== $rotation ) {
			$css .= sprintf( ' transform: rotate(%sdeg); transform-origin: top left;', $rotation );
		}

		if ( isset( $element['opacity'] ) ) {
			$opacity = floatval( $element['opacity'] );
			if ( $opacity < 1 ) {
				$opacity = max( 0, $opacity );
				$css    .= sprintf( ' opacity: %s;', $opacity );
			}
		}

		return $css;
	}

	/**
	 * Render a text / data element.
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data.
	 * @return string
	 */
	private static function render_text( $element, $ticket_data ) {
		list( $value, $is_dynamic ) = self::resolve_text_value( $element, $ticket_data );

		// Conditional elements with no resolved value render nothing.
		if ( '' === $value && ! empty( $element['conditional'] ) ) {
			return '';
		}

		// Strip any markup and decode entities (e.g. wc_price() output for
		// ticket_price returns "100&nbsp;&#1088;&#1089;&#1076;" for "100 рсд").
		// esc_html() below re-escapes safely for HTML output.
		if ( $is_dynamic ) {
			$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES, 'UTF-8' );
			$value = str_replace( "\xc2\xa0", ' ', $value );
		}

		$label    = isset( $element['label'] ) ? (string) $element['label'] : '';
		$position = $element['labelPosition'] ?? 'before';

		$before = '';
		$after  = '';
		if ( '' !== $label ) {
			if ( 'after' === $position ) {
				$after = ' ' . esc_html( $label );
			} else {
				$before = esc_html( $label ) . ' ';
			}
		}

		$style = self::get_text_styles( $element );

		return sprintf(
			'<div class="ticket-element ticket-text" style="%s">%s%s%s</div>',
			esc_attr( $style ),
			$before,
			esc_html( $value ),
			$after
		);
	}

	/**
	 * Render an image / media element.
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data.
	 * @return string
	 */
	private static function render_image( $element, $ticket_data ) {
		$type   = isset( $element['type'] ) ? (string) $element['type'] : '';
		$width  = absint( $element['width'] ?? 100 );
		$height = absint( $element['height'] ?? 100 );
		$src    = isset( $element['src'] ) ? (string) $element['src'] : '';

		// Event/featured image pulls its source from the ticket data when present.
		if ( ( 'event_image' === $type ) && ! empty( $ticket_data['event_image'] ) ) {
			$src = (string) $ticket_data['event_image'];
		}

		$fit        = $element['fit'] ?? 'contain';
		$object_fit = in_array( $fit, array( 'cover', 'contain', 'fill', 'none', 'scale-down' ), true ) ? $fit : 'contain';

		$transform = self::get_transform_styles( $element );

		$style = sprintf(
			'position: absolute; left: %dpx; top: %dpx; width: %dpx; height: %dpx; overflow: hidden;%s',
			(int) ( $element['x'] ?? 0 ),
			(int) ( $element['y'] ?? 0 ),
			$width,
			$height,
			$transform
		);

		// No image source: render a neutral placeholder so the layout is visible.
		if ( '' === $src ) {
			return sprintf(
				'<div class="ticket-element ticket-image ticket-image-placeholder" style="%s background-color: #f0f0f0; border: 1px solid #cccccc;"></div>',
				esc_attr( $style )
			);
		}

		return sprintf(
			'<div class="ticket-element ticket-image" style="%s"><img src="%s" alt="" style="width: 100%%; height: 100%%; object-fit: %s;"></div>',
			esc_attr( $style ),
			esc_url( $src ),
			esc_attr( $object_fit )
		);
	}

	/**
	 * Render QR code element.
	 *
	 * @param array $element Element data.
	 * @param array $ticket_data Ticket data.
	 * @return string
	 */
	private static function render_qr_code( $element, $ticket_data ) {
		// dataField is the single source of truth; fall back to ticket_id.
		$field = $element['dataField'] ?? 'ticket_id';
		$data  = $ticket_data[ $field ] ?? ( $ticket_data['ticket_id'] ?? '' );
		$size  = absint( $element['size'] ?? $element['width'] ?? 100 );

		if ( '' === (string) $data ) {
			return '';
		}

		$foreground = isset( $element['foreground'] ) && '' !== $element['foreground'] ? (string) $element['foreground'] : '#000000';
		$background = isset( $element['background'] ) && '' !== $element['background'] ? (string) $element['background'] : '#ffffff';
		$ecl        = isset( $element['errorCorrectionLevel'] ) ? (string) $element['errorCorrectionLevel'] : 'M';

		$style = sprintf(
			'position: absolute; left: %dpx; top: %dpx; width: %dpx; height: %dpx; background-color: %s;%s',
			(int) ( $element['x'] ?? 0 ),
			(int) ( $element['y'] ?? 0 ),
			$size,
			$size,
			$background,
			self::get_transform_styles( $element )
		);

		// Locally-generated QR only — no external request, works offline and under
		// a strict CSP, and never leaks the ticket id to a third-party API.
		$qr_src = self::generate_qr_data_uri( (string) $data, $foreground, $ecl );

		if ( false === $qr_src ) {
			// Bundled generator unavailable: render an empty placeholder rather than
			// calling an external service.
			return sprintf(
				'<div class="ticket-element ticket-qr-code" style="%s"></div>',
				esc_attr( $style )
			);
		}

		return sprintf(
			'<div class="ticket-element ticket-qr-code" style="%s"><img src="%s" alt="QR Code" style="width: 100%%; height: 100%%; display: block;"></div>',
			esc_attr( $style ),
			esc_attr( $qr_src )
		);
	}

	/**
	 * Generate a QR code as a self-contained SVG data URI using the bundled TCPDF
	 * 2D barcode engine. Returns false when TCPDF is unavailable so the caller can
	 * fall back to an external service.
	 *
	 * The SVG is vector, so it scales losslessly to the element box. The light
	 * background is supplied by the container element, so the QR itself only draws
	 * the dark modules (transparent background) which keeps it scannable.
	 *
	 * Public so other parts of the plugin (e.g. the WooCommerce email/order ticket
	 * display) can render the same self-hosted QR instead of calling an external
	 * service.
	 *
	 * @param string $data       Data to encode.
	 * @param string $foreground Dark module color (hex).
	 * @param string $ecl        Error correction level: L | M | Q | H.
	 * @return string|false Data URI, or false when local generation is unavailable.
	 */
	public static function generate_qr_data_uri( $data, $foreground = '#000000', $ecl = 'M' ) {
		if ( ! class_exists( 'TCPDF2DBarcode' ) ) {
			if ( defined( 'TC_TICKET_DESIGNER_PARENT_DIR' ) && file_exists( TC_TICKET_DESIGNER_PARENT_DIR . 'vendor/autoload.php' ) ) {
				require_once TC_TICKET_DESIGNER_PARENT_DIR . 'vendor/autoload.php';
			}
		}

		if ( ! class_exists( 'TCPDF2DBarcode' ) ) {
			return false;
		}

		$ecl   = in_array( $ecl, array( 'L', 'M', 'Q', 'H' ), true ) ? $ecl : 'M';
		$color = self::sanitize_hex_color_value( $foreground );

		try {
			$barcode = new TCPDF2DBarcode( $data, 'QRCODE,' . $ecl );
			// Module size in px; the SVG scales losslessly to the element box.
			$svg = $barcode->getBarcodeSVGcode( 4, 4, $color );
		} catch ( Exception $e ) {
			return false;
		} catch ( Error $e ) {
			return false;
		}

		if ( empty( $svg ) ) {
			return false;
		}

		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Building a data: URI from generated SVG markup.
	}

	/**
	 * Generate a QR code as raw PNG bytes.
	 *
	 * Used for email delivery, where SVG/data-URI images are not rendered by mail
	 * clients and a raster image embedded as an inline (CID) attachment is required.
	 *
	 * @param string $data   Value to encode (the ticket UID).
	 * @param int    $module Module (pixel) size.
	 * @param string $ecl    Error-correction level (L|M|Q|H).
	 * @return string|false  Raw PNG bytes, or false if unavailable.
	 */
	public static function generate_qr_png( $data, $module = 6, $ecl = 'M' ) {
		if ( ! class_exists( 'TCPDF2DBarcode' ) ) {
			if ( defined( 'TC_TICKET_DESIGNER_PARENT_DIR' ) && file_exists( TC_TICKET_DESIGNER_PARENT_DIR . 'vendor/autoload.php' ) ) {
				require_once TC_TICKET_DESIGNER_PARENT_DIR . 'vendor/autoload.php';
			}
		}

		if ( ! class_exists( 'TCPDF2DBarcode' ) || ! function_exists( 'imagepng' ) ) {
			return false;
		}

		$ecl    = in_array( $ecl, array( 'L', 'M', 'Q', 'H' ), true ) ? $ecl : 'M';
		$module = max( 1, (int) $module );

		try {
			$barcode = new TCPDF2DBarcode( (string) $data, 'QRCODE,' . $ecl );
			$png     = $barcode->getBarcodePngData( $module, $module, array( 0, 0, 0 ) );
		} catch ( Exception $e ) {
			return false;
		} catch ( Error $e ) {
			return false;
		}

		return ! empty( $png ) ? $png : false;
	}

	/**
	 * Sanitize a hex color value for safe inclusion in an SVG fill attribute.
	 *
	 * @param string $color Color value.
	 * @return string A valid #rgb / #rrggbb hex color, defaulting to #000000.
	 */
	private static function sanitize_hex_color_value( $color ) {
		$color = (string) $color;
		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) {
			return $color;
		}
		return '#000000';
	}

	/**
	 * Render barcode element.
	 *
	 * @param array $element Element data.
	 * @param array $ticket_data Ticket data.
	 * @return string
	 */
	private static function render_barcode( $element, $ticket_data ) {
		// dataField is the single source of truth; fall back to ticket_id.
		$field = $element['dataField'] ?? 'ticket_id';
		$data  = $ticket_data[ $field ] ?? ( $ticket_data['ticket_id'] ?? '' );

		if ( empty( $data ) ) {
			return '';
		}

		$width  = absint( $element['width'] ?? 150 );
		$height = absint( $element['height'] ?? 50 );

		$style = sprintf(
			'position: absolute; left: %dpx; top: %dpx; width: %dpx; height: %dpx;%s',
			(int) ( $element['x'] ?? 0 ),
			(int) ( $element['y'] ?? 0 ),
			$width,
			$height,
			self::get_transform_styles( $element )
		);

		// The frontend script (JsBarcode) renders the visual barcode into this
		// container via the data-* attributes. As a fallback for contexts where
		// that script does not run (e.g. the plain HTML/PDF fallback), show the
		// encoded value so the element is never blank.
		$show_text = ! isset( $element['showText'] ) || ! empty( $element['showText'] );

		return sprintf(
			'<div class="ticket-element ticket-barcode" style="%s text-align: center; font-family: monospace; overflow: hidden;" data-value="%s" data-format="%s">%s</div>',
			esc_attr( $style ),
			esc_attr( $data ),
			esc_attr( $element['format'] ?? 'CODE128' ),
			$show_text ? '<span class="ticket-barcode-fallback" style="font-size: 10px; line-height: ' . $height . 'px;">' . esc_html( $data ) . '</span>' : ''
		);
	}

	/**
	 * Render rectangle element.
	 *
	 * @param array $element Element data.
	 * @param array $ticket_data Ticket data.
	 * @return string
	 */
	private static function render_rectangle( $element, $ticket_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Uniform render-callback signature shared by all element renderers.
		$fill         = isset( $element['fill'] ) && '' !== $element['fill'] ? (string) $element['fill'] : 'transparent';
		$stroke       = isset( $element['stroke'] ) && '' !== $element['stroke'] ? (string) $element['stroke'] : 'transparent';
		$stroke_width = (int) ( $element['strokeWidth'] ?? 0 );
		$radius       = (int) ( $element['rx'] ?? 0 );

		$border = '';
		if ( 'transparent' !== $stroke && $stroke_width > 0 ) {
			$border = sprintf( ' border: %dpx solid %s;', $stroke_width, $stroke );
		}

		$style = sprintf(
			'position: absolute; left: %dpx; top: %dpx; width: %dpx; height: %dpx; background-color: %s; border-radius: %dpx;%s%s',
			(int) ( $element['x'] ?? 0 ),
			(int) ( $element['y'] ?? 0 ),
			absint( $element['width'] ?? 100 ),
			absint( $element['height'] ?? 50 ),
			$fill,
			$radius,
			$border,
			self::get_transform_styles( $element )
		);

		return sprintf(
			'<div class="ticket-element ticket-rectangle" style="%s"></div>',
			esc_attr( $style )
		);
	}

	/**
	 * Render line element.
	 *
	 * Lines store their run length in `length` (preferred) or `width`. A
	 * horizontal line uses that length as its width and the stroke width as its
	 * height; a vertical line swaps those. The `dashed_line` variant is drawn
	 * with a dashed border.
	 *
	 * @param array $element Element data.
	 * @param array $ticket_data Ticket data.
	 * @return string
	 */
	private static function render_line( $element, $ticket_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Uniform render-callback signature shared by all element renderers.
		$type        = isset( $element['type'] ) ? (string) $element['type'] : '';
		$orientation = $element['orientation'] ?? 'horizontal';
		if ( 'vertical_line' === $type ) {
			$orientation = 'vertical';
		}
		$length       = absint( $element['length'] ?? $element['width'] ?? 200 );
		$stroke_width = max( 1, (int) ( $element['strokeWidth'] ?? 1 ) );
		$stroke       = isset( $element['stroke'] ) && '' !== $element['stroke'] ? (string) $element['stroke'] : '#cccccc';
		$is_dashed    = ( 'dashed_line' === $type ) || ! empty( $element['strokeDashArray'] ) || ! empty( $element['dashed'] );
		$transform    = self::get_transform_styles( $element );

		if ( 'vertical' === $orientation ) {
			// For a vertical line a saved height may override the run length.
			if ( isset( $element['height'] ) ) {
				$length = absint( $element['height'] );
			}
			$width  = $stroke_width;
			$height = $length;
		} else {
			$width  = $length;
			$height = $stroke_width;
		}

		if ( $is_dashed ) {
			// Border-based dashes so the line is visible without background fill.
			$side  = 'vertical' === $orientation ? 'border-left' : 'border-top';
			$style = sprintf(
				'position: absolute; left: %dpx; top: %dpx; width: %dpx; height: %dpx; %s: %dpx dashed %s;%s',
				(int) ( $element['x'] ?? 0 ),
				(int) ( $element['y'] ?? 0 ),
				$width,
				$height,
				$side,
				$stroke_width,
				$stroke,
				$transform
			);
		} else {
			$style = sprintf(
				'position: absolute; left: %dpx; top: %dpx; width: %dpx; height: %dpx; background-color: %s;%s',
				(int) ( $element['x'] ?? 0 ),
				(int) ( $element['y'] ?? 0 ),
				$width,
				$height,
				$stroke,
				$transform
			);
		}

		return sprintf(
			'<div class="ticket-element ticket-line" style="%s"></div>',
			esc_attr( $style )
		);
	}

	/**
	 * Get text styles for an element.
	 *
	 * @param array $element Element data.
	 * @return string CSS styles.
	 */
	private static function get_text_styles( $element ) {
		return sprintf(
			'position: absolute; left: %dpx; top: %dpx; width: %dpx; font-size: %dpx; font-family: %s; font-weight: %s; font-style: %s; color: %s; text-align: %s; white-space: normal; overflow-wrap: break-word; word-wrap: break-word;%s',
			(int) ( $element['x'] ?? 0 ),
			(int) ( $element['y'] ?? 0 ),
			absint( $element['width'] ?? 200 ),
			absint( $element['fontSize'] ?? 14 ),
			$element['fontFamily'] ?? 'Arial',
			$element['fontWeight'] ?? 'normal',
			$element['fontStyle'] ?? 'normal',
			$element['fill'] ?? '#333333',
			$element['textAlign'] ?? 'left',
			self::get_transform_styles( $element )
		);
	}
}
