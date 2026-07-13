<?php
/**
 * Ticket PDF Generator
 *
 * Generates PDF tickets from templates using TCPDF.
 * All coordinates and sizes are in POINTS (72 pts = 1 inch).
 *
 * @package Venuera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Venuera custom-table data access:
	// The single error_log() call is gated behind WP_DEBUG and only records image-render diagnostics during development.

	exit;
}

// Include TCPDF if available via Composer.
if ( file_exists( TC_TICKET_DESIGNER_PARENT_DIR . 'vendor/autoload.php' ) ) {
	require_once TC_TICKET_DESIGNER_PARENT_DIR . 'vendor/autoload.php';
}

/**
 * Ticket PDF Generator class.
 */
class TC_Ticket_Designer_PDF_Generator {

	/**
	 * Points per inch (PDF standard).
	 */
	const PTS_PER_INCH = 72;

	/**
	 * Points per mm.
	 */
	const PTS_PER_MM = 2.834645669;

	/**
	 * Screen DPI (standard).
	 */
	const SCREEN_DPI = 96;

	/**
	 * Pixel to point conversion factor.
	 * 1 point = 1/72 inch, 1 pixel = 1/96 inch (at 96 DPI)
	 * So 1 pixel = 72/96 = 0.75 points
	 */
	const PX_TO_PT = 0.75;

	/**
	 * Standard ticket sizes in points.
	 */
	const TICKET_SIZES = array(
		'standard'     => array(
			'width'  => 432,
			'height' => 180,
			'label'  => 'Standard (6" × 2.5")',
		),
		'concert'      => array(
			'width'  => 576,
			'height' => 216,
			'label'  => 'Concert (8" × 3")',
		),
		'compact'      => array(
			'width'  => 360,
			'height' => 144,
			'label'  => 'Compact (5" × 2")',
		),
		'large'        => array(
			'width'  => 612,
			'height' => 252,
			'label'  => 'Large (8.5" × 3.5")',
		),
		'pass'         => array(
			'width'  => 252,
			'height' => 360,
			'label'  => 'Pass (3.5" × 5")',
		),
		'badge'        => array(
			'width'  => 252,
			'height' => 324,
			'label'  => 'Badge (3.5" × 4.5")',
		),
		'a6_landscape' => array(
			'width'  => 420,
			'height' => 297,
			'label'  => 'A6 Landscape',
		),
		'a6_portrait'  => array(
			'width'  => 297,
			'height' => 420,
			'label'  => 'A6 Portrait',
		),
		'a5_landscape' => array(
			'width'  => 595,
			'height' => 420,
			'label'  => 'A5 Landscape',
		),
		'a5_portrait'  => array(
			'width'  => 420,
			'height' => 595,
			'label'  => 'A5 Portrait',
		),
		'a4_landscape' => array(
			'width'  => 842,
			'height' => 595,
			'label'  => 'A4 Landscape',
		),
		'a4_portrait'  => array(
			'width'  => 595,
			'height' => 842,
			'label'  => 'A4 Portrait',
		),
	);

	/**
	 * TCPDF instance.
	 *
	 * @var TCPDF
	 */
	private $pdf;

	/**
	 * Template width in points.
	 *
	 * @var float
	 */
	private $width;

	/**
	 * Template height in points.
	 *
	 * @var float
	 */
	private $height;

	/**
	 * Generate PDF ticket.
	 *
	 * @param TC_Ticket_Designer_Template $template    Template object.
	 * @param array                   $ticket_data Ticket data.
	 * @param string                  $output      Output mode: 'S' (string), 'F' (file), 'I' (inline), 'D' (download).
	 * @param string                  $filename    Filename for 'F' and 'D' modes.
	 * @return string|bool PDF content or success status.
	 */
	public static function generate( $template, $ticket_data, $output = 'S', $filename = '' ) {
		$generator = new self();
		return $generator->create_pdf( $template, $ticket_data, $output, $filename );
	}

	/**
	 * Generate ONE PDF containing several tickets — one page per ticket.
	 *
	 * @param array  $items    List of [ 'template' => TC_Ticket_Designer_Template, 'ticket_data' => array ].
	 * @param string $output   Output mode ('S' string, 'I' inline, etc.).
	 * @param string $filename Filename.
	 * @return string|bool
	 */
	public static function generate_multi( $items, $output = 'S', $filename = '' ) {
		$generator = new self();
		return $generator->create_multi_pdf( $items, $output, $filename );
	}

	/**
	 * Whether the "Powered by Tickera" attribution footer should be rendered.
	 *
	 * The attribution lives entirely inside is__premium_only() blocks, which
	 * Freemius auto-removes from the free WordPress.org build — so the free
	 * version ships no attribution code or strings at all (wp.org compliant). In
	 * the premium build it is shown ONLY to users who are NOT on an active paid
	 * plan or in trial (e.g. a lapsed / never-activated license); paying and
	 * trial customers get a clean, unbranded ticket.
	 *
	 * @return bool
	 */
	private function powered_by_enabled() {
		// This "if" block is auto-removed from the Free (wordpress.org) version.
		if ( \Tickera\tets_fs()->is__premium_only() ) {
			$fs = \Tickera\tets_fs();
			return ! ( $fs->is_paying() || $fs->is_trial() );
		}

		return false;
	}

	/**
	 * Height (in points) reserved below the ticket for the attribution footer —
	 * zero when the footer is disabled.
	 *
	 * @return float
	 */
	private function powered_by_height() {
		return $this->powered_by_enabled() ? 28.0 : 0.0;
	}

	/**
	 * Draw the "Powered by Tickera" line, centred 10pt below the bottom edge of
	 * the ticket, with "Tickera" linked to tickera.com. Uses a core font
	 * (helvetica) so it needs no embedding.
	 */
	private function draw_powered_by_footer() {
		// This "if" block is auto-removed from the Free (wordpress.org) version.
		if ( \Tickera\tets_fs()->is__premium_only() ) {
			$y = $this->height + 10.0; // 10pt gap below the ticket's bottom edge.
			$this->pdf->SetFont( 'helvetica', '', 8 );
			$this->pdf->SetTextColor( 136, 136, 136 );
			$html = '<div style="text-align:center; font-size:8pt; color:#888888;">'
				. esc_html__( 'Powered by', 'tickera-event-ticketing-system' )
				. ' <a href="https://tickera.com/" style="color:#6b5f89; text-decoration:none;">Tickera</a></div>';
			$this->pdf->writeHTMLCell( $this->width, 0, 0, $y, $html, 0, 1, false, true, 'C', true );
		}
	}

	/**
	 * Build a multi-page PDF (one ticket per page) reusing the single-ticket
	 * rendering pipeline.
	 *
	 * @param array  $items    List of [ 'template' => TC_Ticket_Designer_Template, 'ticket_data' => array ].
	 * @param string $output   Output mode ('S' string, 'I' inline, etc.).
	 * @param string $filename Filename.
	 * @return string|bool
	 */
	public function create_multi_pdf( $items, $output = 'S', $filename = '' ) {
		if ( function_exists( 'tickera_ticket_designer_ensure_tcpdf' ) ) {
			tickera_ticket_designer_ensure_tcpdf();
		}
		$items = array_values( array_filter( (array) $items ) );
		if ( ! class_exists( 'TCPDF' ) || empty( $items ) ) {
			// Fall back to the first ticket's single PDF if TCPDF is unavailable.
			if ( ! empty( $items[0] ) ) {
				return $this->create_pdf( $items[0]['template'], $items[0]['ticket_data'], $output, $filename );
			}
			return false;
		}

		$first = $items[0]['template']->get_template_array();
		$w     = floatval( $first['width'] ?? 432 );
		$h     = floatval( $first['height'] ?? 180 );

		$this->pdf = new TCPDF( ( $w > $h ? 'L' : 'P' ), 'pt', array( $w, $h ), true, 'UTF-8', false );
		// Embed the full embedded TTFs (no glyph subsetting). Tickera's bundled
		// TCPDF mis-subsets the cmap of some library fonts (e.g. Lora), producing
		// garbled glyphs; embedding unsubset keeps the PDF text matching the editor.
		$this->pdf->setFontSubsetting( false );
		$this->pdf->SetCreator( 'Venuera' );
		$this->pdf->SetAuthor( get_bloginfo( 'name' ) );
		$this->pdf->SetTitle( 'Event Tickets' );
		$this->pdf->setPrintHeader( false );
		$this->pdf->setPrintFooter( false );
		$this->pdf->SetMargins( 0, 0, 0 );
		$this->pdf->SetAutoPageBreak( false, 0 );
		$this->pdf->setCellPaddings( 0, 0, 0, 0 );
		$this->pdf->setCellMargins( 0, 0, 0, 0 );

		$footer_h = 0.0;
		// This "if" block is auto-removed from the Free (wordpress.org) version.
		if ( \Tickera\tets_fs()->is__premium_only() ) {
			$footer_h = $this->powered_by_height();
		}

		foreach ( $items as $it ) {
			$tarr         = $it['template']->get_template_array();
			$this->width  = floatval( $tarr['width'] ?? 432 );
			$this->height = floatval( $tarr['height'] ?? 180 );

			$page_h = $this->height + $footer_h;
			$this->pdf->AddPage( ( $this->width > $page_h ? 'L' : 'P' ), array( $this->width, $page_h ) );

			$bg = $tarr['background'] ?? '#ffffff';
			if ( $bg && 'transparent' !== $bg ) {
				$rgb = $this->hex_to_rgb( $bg );
				$this->pdf->SetFillColor( $rgb[0], $rgb[1], $rgb[2] );
				$this->pdf->Rect( 0, 0, $this->width, $this->height, 'F' );
			}
			foreach ( ( $tarr['elements'] ?? array() ) as $element ) {
				$this->render_element( $element, $it['ticket_data'] );
			}

			// This "if" block is auto-removed from the Free (wordpress.org) version.
			if ( \Tickera\tets_fs()->is__premium_only() ) {
				if ( $footer_h ) {
					$this->draw_powered_by_footer();
				}
			}
		}

		$result = $this->pdf->Output( $filename ? $filename : 'tickets.pdf', $output );
		$this->cleanup_temp_images();
		return $result;
	}

	/**
	 * Create PDF document.
	 *
	 * @param TC_Ticket_Designer_Template $template    Template object.
	 * @param array                   $ticket_data Ticket data.
	 * @param string                  $output      Output mode.
	 * @param string                  $filename    Filename.
	 * @return string|bool
	 */
	public function create_pdf( $template, $ticket_data, $output = 'S', $filename = '' ) {
		if ( function_exists( 'tickera_ticket_designer_ensure_tcpdf' ) ) {
			tickera_ticket_designer_ensure_tcpdf();
		}
		if ( ! class_exists( 'TCPDF' ) ) {
			// Fallback to HTML if TCPDF not available.
			return $this->generate_html_fallback( $template, $ticket_data );
		}

		$template_array = $template->get_template_array();
		$settings       = $template->get_settings_array();

		// Get dimensions - canvas pixels = PDF points (1:1 mapping).
		$this->width  = floatval( $template_array['width'] ?? 432 );
		$this->height = floatval( $template_array['height'] ?? 180 );

		// Optional footer band height (premium only; always 0 in the free build).
		$footer_h = 0.0;
		// This "if" block is auto-removed from the Free (wordpress.org) version.
		if ( \Tickera\tets_fs()->is__premium_only() ) {
			$footer_h = $this->powered_by_height();
		}
		$page_h   = $this->height + $footer_h;

		// Determine orientation (based on the full page including the footer band).
		$orientation = $this->width > $page_h ? 'L' : 'P';

		// Create PDF using POINTS as the unit (important!)
		// This ensures 1:1 mapping with canvas coordinates.
		$this->pdf = new TCPDF( $orientation, 'pt', array( $this->width, $page_h ), true, 'UTF-8', false );
		$this->pdf->setFontSubsetting( false );

		// Set document information.
		$this->pdf->SetCreator( 'Venuera' );
		$this->pdf->SetAuthor( get_bloginfo( 'name' ) );
		$this->pdf->SetTitle( $ticket_data['event_name'] ?? 'Event Ticket' );
		$this->pdf->SetSubject( 'Event Ticket' );

		// Remove default header/footer.
		$this->pdf->setPrintHeader( false );
		$this->pdf->setPrintFooter( false );

		// Set margins to 0.
		$this->pdf->SetMargins( 0, 0, 0 );
		$this->pdf->SetAutoPageBreak( false, 0 );

		// Zero TCPDF's default CELL padding/margins. TCPDF seeds a default cell
		// padding (~2.835pt in 'pt' units) that MultiCell keeps on the left/right,
		// which would inset every text element ~2.8pt to the right of its x —
		// breaking the 1:1 match with the canvas/preview (which have no inset).
		$this->pdf->setCellPaddings( 0, 0, 0, 0 );
		$this->pdf->setCellMargins( 0, 0, 0, 0 );

		// Add page.
		$this->pdf->AddPage();

		// Draw background.
		$background = $template_array['background'] ?? '#ffffff';
		if ( $background && 'transparent' !== $background ) {
			$rgb = $this->hex_to_rgb( $background );
			$this->pdf->SetFillColor( $rgb[0], $rgb[1], $rgb[2] );
			$this->pdf->Rect( 0, 0, $this->width, $this->height, 'F' );
		}

		// Render elements (sorted by z-index if available).
		$elements = $template_array['elements'] ?? array();
		foreach ( $elements as $element ) {
			$this->render_element( $element, $ticket_data );
		}

		// This "if" block is auto-removed from the Free (wordpress.org) version.
		if ( \Tickera\tets_fs()->is__premium_only() ) {
			if ( $footer_h ) {
				$this->draw_powered_by_footer();
			}
		}

		// Output.
		if ( empty( $filename ) ) {
			$filename = 'ticket-' . ( $ticket_data['ticket_id'] ?? uniqid() ) . '.pdf';
		}

		$result = $this->pdf->Output( $filename, $output );

		// Clean up any temp images created while rendering (data-URI decodes,
		// remote downloads, webp→png conversions).
		$this->cleanup_temp_images();

		return $result;
	}

	/**
	 * Render single element.
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data.
	 */
	private function render_element( $element, $ticket_data ) {
		$type = $element['type'] ?? '';

		// Reset the line style to SOLID before each element. TCPDF's line style
		// (dash pattern, width, cap) is global state that persists across draw
		// calls. A dashed_line element sets a dash pattern that would otherwise
		// "leak" into every following stroke — e.g. a QR/barcode border or a
		// rectangle outline would render dashed even though the editor shows it
		// solid. Resetting here keeps each element independent and matches the
		// canvas, where every object carries its own style.
		$this->pdf->SetLineStyle(
			array(
				'dash' => 0,
				'cap'  => 'butt',
			)
		);

		// Get position - coordinates are directly in points (same as canvas).
		$x = floatval( $element['x'] ?? 0 );
		$y = floatval( $element['y'] ?? 0 );

		// Get rotation angle.
		$rotation = floatval( $element['rotation'] ?? 0 );

		// Get opacity (0-1). Apply with SetAlpha and reset after the element so it
		// does not leak into following elements.
		$opacity     = isset( $element['opacity'] ) ? floatval( $element['opacity'] ) : 1;
		$has_opacity = ( $opacity < 1 );
		if ( $has_opacity ) {
			$this->pdf->SetAlpha( max( 0, $opacity ) );
		}

		// Apply rotation if needed.
		//
		// Rotation pivot is the element's TOP-LEFT (its saved x/y) so PDF
		// matches both:
		// • the HTML preview, which uses `transform-origin: top left`
		// • fabric.js with default origins (originX='left', originY='top'),
		// where the angle is applied around the origin point — i.e. the
		// box's top-left after fabric's internal `centeredRotation`
		// bookkeeping has already adjusted left/top on the user's
		// rotation drag.
		//
		// TCPDF's Rotate is counter-clockwise; fabric's `angle` is CSS-style
		// clockwise — so negate.
		if ( 0.0 !== (float) $rotation ) {
			$this->pdf->StartTransform();
			$this->pdf->Rotate( -$rotation, $x, $y );
		}

		switch ( $type ) {
			// Rectangles and shapes.
			case 'rectangle':
			case 'header_bg':
			case 'footer_bg':
			case 'sidebar_panel':
			case 'rounded_box':
			case 'ticket_border':
				$this->render_rectangle( $element, $x, $y );
				break;

			// Lines.
			case 'horizontal_line':
			case 'vertical_line':
			case 'dashed_line':
				$this->render_line( $element, $x, $y );
				break;

			// QR Code.
			case 'qr_code':
				$this->render_qr_code( $element, $ticket_data, $x, $y );
				break;

			// Barcode.
			case 'barcode':
				$this->render_barcode( $element, $ticket_data, $x, $y );
				break;

			// Images.
			case 'custom_image':
			case 'logo':
			case 'event_image':
			case 'sponsor_logo':
				$this->render_image( $element, $ticket_data, $x, $y );
				break;

			// Named data ids, dynamic_text, static_text, custom fields and any
			// unrecognised type id resolve by their saved baseType (defaulting
			// to text) so every element the editor can produce renders.
			default:
				switch ( $element['baseType'] ?? '' ) {
					case 'rectangle':
					case 'rect':
						$this->render_rectangle( $element, $x, $y );
						break;
					case 'line':
						$this->render_line( $element, $x, $y );
						break;
					case 'qrcode':
					case 'qr':
						$this->render_qr_code( $element, $ticket_data, $x, $y );
						break;
					case 'barcode':
						$this->render_barcode( $element, $ticket_data, $x, $y );
						break;
					case 'image':
						$this->render_image( $element, $ticket_data, $x, $y );
						break;
					default:
						$this->render_text( $element, $ticket_data, $x, $y );
						break;
				}
				break;
		}

		// End rotation transform.
		if ( 0.0 !== (float) $rotation ) {
			$this->pdf->StopTransform();
		}

		// Reset opacity so it does not leak into following elements.
		if ( $has_opacity ) {
			$this->pdf->SetAlpha( 1 );
		}
	}

	/**
	 * Render rectangle element.
	 *
	 * @param array $element Element data.
	 * @param float $x       X position in points.
	 * @param float $y       Y position in points.
	 */
	private function render_rectangle( $element, $x, $y ) {
		$width        = floatval( $element['width'] ?? 100 );
		$height       = floatval( $element['height'] ?? 50 );
		$fill         = $element['fill'] ?? 'transparent';
		$stroke       = $element['stroke'] ?? 'transparent';
		$stroke_width = floatval( $element['strokeWidth'] ?? 0 );
		$rx           = floatval( $element['rx'] ?? 0 );

		$style = '';

		// Set fill color.
		if ( $fill && 'transparent' !== $fill ) {
			$rgb = $this->hex_to_rgb( $fill );
			$this->pdf->SetFillColor( $rgb[0], $rgb[1], $rgb[2] );
			$style .= 'F';
		}

		// Set stroke.
		if ( $stroke && 'transparent' !== $stroke && $stroke_width > 0 ) {
			$rgb = $this->hex_to_rgb( $stroke );
			$this->pdf->SetDrawColor( $rgb[0], $rgb[1], $rgb[2] );
			$this->pdf->SetLineWidth( $stroke_width );
			$style .= 'D';
		}

		if ( empty( $style ) ) {
			return;
		}

		// Draw rounded or regular rectangle.
		if ( $rx > 0 ) {
			$this->pdf->RoundedRect( $x, $y, $width, $height, $rx, '1111', $style );
		} else {
			$this->pdf->Rect( $x, $y, $width, $height, $style );
		}
	}

	/**
	 * Render line element.
	 *
	 * @param array $element Element data.
	 * @param float $x       X position in points.
	 * @param float $y       Y position in points.
	 */
	private function render_line( $element, $x, $y ) {
		$length       = floatval( $element['width'] ?? $element['length'] ?? 200 );
		$stroke       = $element['stroke'] ?? '#cccccc';
		$stroke_width = floatval( $element['strokeWidth'] ?? 1 );
		$orientation  = $element['orientation'] ?? 'horizontal';
		$is_dashed    = ( ( $element['type'] ?? '' ) === 'dashed_line' )
			|| ! empty( $element['strokeDashArray'] )
			|| ! empty( $element['dashed'] );

		$rgb = $this->hex_to_rgb( $stroke );
		$this->pdf->SetDrawColor( $rgb[0], $rgb[1], $rgb[2] );
		$this->pdf->SetLineWidth( $stroke_width );

		if ( $is_dashed ) {
			// Match the canvas/preview pattern. fabric uses
			// strokeDashArray = [5, 5] for dashed_line; if the element saved
			// a custom array, respect it. Otherwise default to "5,5" so all
			// three contexts dash at the same cadence.
			$dash_pattern = '5,5';
			if ( isset( $element['strokeDashArray'] ) && is_array( $element['strokeDashArray'] ) ) {
				$arr = array_filter( array_map( 'floatval', $element['strokeDashArray'] ) );
				if ( ! empty( $arr ) ) {
					$dash_pattern = implode( ',', $arr );
				}
			}
			$this->pdf->SetLineStyle(
				array(
					'dash' => $dash_pattern,
					'cap'  => 'butt',
				)
			);
		} else {
			$this->pdf->SetLineStyle(
				array(
					'dash' => 0,
					'cap'  => 'butt',
				)
			);
		}

		if ( 'vertical' === $orientation || 'vertical_line' === ( $element['type'] ?? '' ) ) {
			// Use the same length source as the preview (width/length on the
			// line's axis), not a separately-rounded `height`, so vertical-line
			// length matches across canvas/preview/PDF.
			$v_length = floatval( $element['width'] ?? $element['length'] ?? $element['height'] ?? $length );
			$this->pdf->Line( $x, $y, $x, $y + $v_length );
		} else {
			$this->pdf->Line( $x, $y, $x + $length, $y );
		}
	}

	/**
	 * Render QR code element.
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data.
	 * @param float $x           X position in points.
	 * @param float $y           Y position in points.
	 */
	private function render_qr_code( $element, $ticket_data, $x, $y ) {
		// Prefer the live width/height saved by the canvas (which mirror any
		// resize the user did via the fabric handles) over the original
		// `size` baked into the element on creation. Old templates saved
		// before onObjectModified started syncing the width/height back into
		// elementData still have a stale `size`, so we read width FIRST.
		$size       = floatval(
			$element['width']
			?? $element['height']
			?? $element['size']
			?? 100
		);
		$data_field = $element['dataField'] ?? 'ticket_id';
		$data       = $ticket_data[ $data_field ] ?? $ticket_data['ticket_id'] ?? '';
		$ecl        = $element['errorCorrectionLevel'] ?? 'M';
		$foreground = $element['foreground'] ?? '#000000';
		$background = $element['background'] ?? '#ffffff';

		// Quiet-zone padding (default 5 — matches the canvas/preview
		// defaults), capped so the inner QR area stays positive.
		$padding = isset( $element['padding'] ) ? floatval( $element['padding'] ) : 5.0;
		if ( $padding < 0 ) {
			$padding = 0;
		}
		$max_padding = max( 0, ( $size / 2 ) - 5 );
		if ( $padding > $max_padding ) {
			$padding = $max_padding;
		}
		$inner_size = max( 10, $size - ( $padding * 2 ) );

		// Optional rounded border around the QR card.
		$border_width  = isset( $element['borderWidth'] ) ? floatval( $element['borderWidth'] ) : 0.0;
		$border_color  = $element['borderColor'] ?? '#000000';
		$border_radius = isset( $element['borderRadius'] ) ? floatval( $element['borderRadius'] ) : 0.0;
		if ( $border_width < 0 ) {
			$border_width = 0; }
		if ( $border_radius < 0 ) {
			$border_radius = 0; }

		if ( empty( $data ) ) {
			// Deterministic fallback so the QR pattern is stable across
			// renders and matches the preview's 'SAMPLE-QR-123' placeholder.
			$data = 'SAMPLE-QR-123';
		}

		// Map error correction level.
		$ecl_map  = array(
			'L' => 'L',
			'M' => 'M',
			'Q' => 'Q',
			'H' => 'H',
		);
		$ecl_code = $ecl_map[ $ecl ] ?? 'M';

		// Set colors.
		$fg_rgb = $this->hex_to_rgb( $foreground );
		$bg_rgb = $this->hex_to_rgb( $background );

		// Draw background card. With a border requested, use RoundedRect
		// and stroke; otherwise just a filled rectangle. Stroke is drawn
		// on the edge so the border bleeds 50% inside / 50% outside — we
		// keep that consistent with the canvas where the fabric.Rect
		// stroke also straddles the edge.
		if ( $border_width > 0 ) {
			$bw_rgb = $this->hex_to_rgb( $border_color );
			$this->pdf->SetFillColor( $bg_rgb[0], $bg_rgb[1], $bg_rgb[2] );
			$this->pdf->SetDrawColor( $bw_rgb[0], $bw_rgb[1], $bw_rgb[2] );
			$this->pdf->SetLineWidth( $border_width );
			if ( $border_radius > 0 ) {
				$this->pdf->RoundedRect( $x, $y, $size, $size, $border_radius, '1111', 'DF' );
			} else {
				$this->pdf->Rect( $x, $y, $size, $size, 'DF' );
			}
		} else {
			$this->pdf->SetFillColor( $bg_rgb[0], $bg_rgb[1], $bg_rgb[2] );
			if ( $border_radius > 0 ) {
				$this->pdf->RoundedRect( $x, $y, $size, $size, $border_radius, '1111', 'F' );
			} else {
				$this->pdf->Rect( $x, $y, $size, $size, 'F' );
			}
		}

		// Generate the QR at INNER size, offset by padding. Disable
		// TCPDF's own auto-padding (vpadding/hpadding = 0) — we already
		// reserved the quiet zone via $padding, doubling it would shrink
		// the modules further.
		$style = array(
			'border'        => false,
			'vpadding'      => 0,
			'hpadding'      => 0,
			'fgcolor'       => $fg_rgb,
			'bgcolor'       => $bg_rgb,
			'module_width'  => 1,
			'module_height' => 1,
		);

		$this->pdf->write2DBarcode(
			$data,
			'QRCODE,' . $ecl_code,
			$x + $padding,
			$y + $padding,
			$inner_size,
			$inner_size,
			$style,
			'N'
		);
	}

	/**
	 * Render barcode element.
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data.
	 * @param float $x           X position in points.
	 * @param float $y           Y position in points.
	 */
	private function render_barcode( $element, $ticket_data, $x, $y ) {
		$width      = floatval( $element['width'] ?? 150 );
		$height     = floatval( $element['height'] ?? 50 );
		$data_field = $element['dataField'] ?? 'ticket_id';
		$data       = $ticket_data[ $data_field ] ?? $ticket_data['barcode'] ?? '';
		$format     = $element['format'] ?? 'C128';
		$show_text  = $element['showText'] ?? true;
		$foreground = $element['foreground'] ?? '#000000';
		$background = $element['background'] ?? '#ffffff';

		if ( empty( $data ) ) {
			// Match the canvas/preview barcode placeholder so bars + label agree.
			$data = 'TKT123456';
		}

		// Map format names to TCPDF format codes.
		$format_map   = array(
			'CODE128'    => 'C128',
			'CODE39'     => 'C39',
			'EAN13'      => 'EAN13',
			'EAN8'       => 'EAN8',
			'UPC'        => 'UPCA',
			'UPCA'       => 'UPCA',
			'UPCE'       => 'UPCE',
			'ITF14'      => 'I25',
			'ITF'        => 'I25',
			'MSI'        => 'MSI',
			'pharmacode' => 'PHARMA',
			'codabar'    => 'CODABAR',
			'C128'       => 'C128',
		);
		$tcpdf_format = $format_map[ $format ] ?? 'C128';

		$fg_rgb = $this->hex_to_rgb( $foreground );
		$bg_rgb = $this->hex_to_rgb( $background );

		// Quiet-zone padding (matches the canvas/preview defaults) and
		// optional rounded border, mirroring the QR element. Cap padding
		// so the inner bars area stays positive on both axes.
		$padding       = isset( $element['padding'] ) ? floatval( $element['padding'] ) : 3.0;
		$border_width  = isset( $element['borderWidth'] ) ? floatval( $element['borderWidth'] ) : 0.0;
		$border_color  = $element['borderColor'] ?? '#000000';
		$border_radius = isset( $element['borderRadius'] ) ? floatval( $element['borderRadius'] ) : 0.0;
		if ( $padding < 0 ) {
			$padding = 0; }
		if ( $border_width < 0 ) {
			$border_width = 0; }
		if ( $border_radius < 0 ) {
			$border_radius = 0; }
		$max_pad = max( 0, min( ( $width / 2 ) - 5, ( $height / 2 ) - 5 ) );
		if ( $padding > $max_pad ) {
			$padding = $max_pad;
		}
		$inner_x      = $x + $padding;
		$inner_y      = $y + $padding;
		$inner_width  = max( 10, $width - ( $padding * 2 ) );
		$inner_height = max( 10, $height - ( $padding * 2 ) );

		// Draw the background card (with optional rounded border) at the
		// full element box; the bars go inside the quiet zone below.
		if ( $border_width > 0 ) {
			$bw_rgb = $this->hex_to_rgb( $border_color );
			$this->pdf->SetFillColor( $bg_rgb[0], $bg_rgb[1], $bg_rgb[2] );
			$this->pdf->SetDrawColor( $bw_rgb[0], $bw_rgb[1], $bw_rgb[2] );
			$this->pdf->SetLineWidth( $border_width );
			if ( $border_radius > 0 ) {
				$this->pdf->RoundedRect( $x, $y, $width, $height, $border_radius, '1111', 'DF' );
			} else {
				$this->pdf->Rect( $x, $y, $width, $height, 'DF' );
			}
		} else {
			$this->pdf->SetFillColor( $bg_rgb[0], $bg_rgb[1], $bg_rgb[2] );
			if ( $border_radius > 0 ) {
				$this->pdf->RoundedRect( $x, $y, $width, $height, $border_radius, '1111', 'F' );
			} else {
				$this->pdf->Rect( $x, $y, $width, $height, 'F' );
			}
		}

		// Match the JsBarcode bitmap layout used by canvas + preview EXACTLY:
		// bars     80 px
		// gap       2 px (textMargin)
		// text     20 px (fontSize)
		// total   102 px
		// → bars  = 80/102 ≈ 0.7843 of inner_height
		// → gap   =  2/102 ≈ 0.0196 of inner_height
		// → text  = 20/102 ≈ 0.1961 of inner_height
		// Without these exact proportions the label would sit slightly
		// higher and smaller than canvas/preview, which the user spotted.
		$bars_height  = $show_text ? $inner_height * ( 80 / 102 ) : $inner_height;
		$label_gap    = $show_text ? $inner_height * ( 2 / 102 ) : 0;
		$label_height = $show_text ? $inner_height * ( 20 / 102 ) : 0;

		// CRITICAL — render the bars + label as a SINGLE rasterized bitmap,
		// NOT as separate TCPDF write1DBarcode() bars + Cell() text. Two
		// independent reasons:
		//
		// 1. write1DBarcode draws every bar as a thin vector rectangle.
		// For a tightly-sized barcode (e.g. 60–80 pt wide) each bar
		// ends up sub-pixel thin and effectively disappears — you get
		// an empty white card. JsBarcode on canvas/preview produces a
		// bitmap that stays visible under any downscale because of
		// anti-aliasing.
		// 2. A separate Cell() call for the human-readable label, while
		// inside a Rotate() transform around the element's top-left,
		// ends up positioned at a different visual location than
		// JsBarcode's in-bitmap text. Pixel analysis of a rotated
		// barcode showed the Cell text being painted OVER the bars
		// instead of in the strip next to them. Drawing them as ONE
		// bitmap means the rotation moves bars + text together, with
		// no inter-call coordinate drift.
		//
		// We build a 492 × 102-equivalent layout in GD — the same 80 + 2 +
		// 20 px split JsBarcode produces — so the PDF visual matches the
		// canvas pixel-for-pixel.
		$rendered_via_bitmap = false;
		if ( $show_text && class_exists( 'TCPDFBarcode' ) && function_exists( 'imagecreate' ) ) {
			$combined_png = $this->build_barcode_bitmap_with_label( $data, $tcpdf_format, $fg_rgb, $bg_rgb );
			if ( $combined_png ) {
				$this->pdf->Image(
					'@' . $combined_png,
					$inner_x,
					$inner_y,
					$inner_width,
					$inner_height, // full inner area — bitmap contains bars + text.
					'PNG',
					'',  // link.
					'',  // align.
					true, // resize.
					300,  // dpi.
					'',   // palign.
					false,
					false,
					0,
					false,
					false,
					false
				);
				$rendered_via_bitmap = true;
			}
		}

		// Bars-only bitmap path (when show_text is off — just embed bars).
		if ( ! $rendered_via_bitmap && ! $show_text && class_exists( 'TCPDFBarcode' ) && function_exists( 'imagecreate' ) ) {
			try {
				$bc       = new TCPDFBarcode( $data, $tcpdf_format );
				$png_data = $bc->getBarcodePngData( 4, 80, $fg_rgb );
				if ( is_string( $png_data ) && '' !== $png_data ) {
					$this->pdf->Image(
						'@' . $png_data,
						$inner_x,
						$inner_y,
						$inner_width,
						$bars_height,
						'PNG',
						'',
						'',
						true,
						300,
						'',
						false,
						false,
						0,
						false,
						false,
						false
					);
					$rendered_via_bitmap = true;
				}
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Bitmap barcode rendering failed; the vector fallback below takes over.
			}
		}

		// Vector fallback — only fires if TCPDFBarcode or GD is unavailable.
		if ( ! $rendered_via_bitmap ) {
			$style = array(
				'position'     => '',
				'align'        => 'C',
				'stretch'      => false,
				'fitwidth'     => true,
				'cellfitalign' => '',
				'border'       => false,
				'padding'      => 0,
				'hpadding'     => 0,
				'vpadding'     => 0,
				'fgcolor'      => $fg_rgb,
				'bgcolor'      => $bg_rgb,
				'text'         => $show_text,
				'font'         => 'helvetica',
				'fontsize'     => $show_text ? max( 6, $inner_height * ( 20 / 102 ) ) : 0,
				'stretchtext'  => 0,
			);
			$this->pdf->write1DBarcode(
				$data,
				$tcpdf_format,
				$inner_x,
				$inner_y,
				$inner_width,
				$show_text ? $bars_height : $inner_height,
				0.4,
				$style,
				'N'
			);
		}
	}

	/**
	 * Build a PNG bitmap of the barcode bars + human-readable label as a
	 * SINGLE image, mirroring JsBarcode's bitmap layout (bars 80 px,
	 * margin 2 px, label 20 px = 102 px tall total).
	 *
	 * Returns the raw PNG data (suitable for TCPDF::Image '@' input), or
	 * false if GD is unavailable.
	 *
	 * @param string $data     Encoded value (e.g. "TKT-001234").
	 * @param string $type     TCPDF barcode type code (C128, EAN13, …).
	 * @param array  $fg_rgb   Foreground colour [r, g, b].
	 * @param array  $bg_rgb   Background colour [r, g, b].
	 * @return string|false PNG data or false.
	 */
	private function build_barcode_bitmap_with_label( $data, $type, $fg_rgb, $bg_rgb ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return false;
		}
		try {
			$bc     = new TCPDFBarcode( $data, $type );
			$bc_arr = $bc->getBarcodeArray();
			if ( ! is_array( $bc_arr ) || empty( $bc_arr['maxw'] ) ) {
				return false;
			}
			$module_w = 4;                                  // matches JsBarcode width:4.
			$bars_h   = 80;                                 // matches JsBarcode height:80.
			$gap      = 2;                                  // matches JsBarcode textMargin:2.
			$text_h   = 20;                                 // matches JsBarcode fontSize:20.
			$img_w    = $bc_arr['maxw'] * $module_w;
			$img_h    = $bars_h + $gap + $text_h;

			// Build the bars PNG via TCPDFBarcode and load it into a GD
			// resource so we can composite the label below it.
			$bars_png = $bc->getBarcodePngData( $module_w, $bars_h, $fg_rgb );
			$bars_img = @imagecreatefromstring( $bars_png ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- GD emits warnings on malformed image data; failure is handled below.
			if ( ! $bars_img ) {
				return false;
			}

			$canvas   = imagecreatetruecolor( $img_w, $img_h );
			$bg_color = imagecolorallocate( $canvas, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2] );
			imagefilledrectangle( $canvas, 0, 0, $img_w, $img_h, $bg_color );

			// Paste bars at the top of the canvas.
			imagecopy( $canvas, $bars_img, 0, 0, 0, 0, $img_w, $bars_h );
			imagedestroy( $bars_img );

			// Draw the label centred horizontally in the bottom 20 px
			// strip. GD's built-in font #5 is ~9 × 15 px — small but
			// legible and language-agnostic for the typical ticket-id
			// characters (digits, dashes, letters). Picking font #5 keeps
			// the label visually proportionate to the 80 px bars without
			// requiring a bundled TTF.
			$fg_color  = imagecolorallocate( $canvas, $fg_rgb[0], $fg_rgb[1], $fg_rgb[2] );
			$font_idx  = 5;
			$glyph_w   = imagefontwidth( $font_idx );
			$glyph_h   = imagefontheight( $font_idx );
			$label_str = (string) $data;
			$label_w   = $glyph_w * strlen( $label_str );
			$label_x   = max( 0, intval( ( $img_w - $label_w ) / 2 ) );
			$label_y   = $bars_h + $gap + intval( ( $text_h - $glyph_h ) / 2 );
			imagestring( $canvas, $font_idx, $label_x, $label_y, $label_str, $fg_color );

			ob_start();
			imagepng( $canvas );
			$png_data = ob_get_clean();
			imagedestroy( $canvas );
			return $png_data;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Render image element.
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data.
	 * @param float $x           X position in points.
	 * @param float $y           Y position in points.
	 */
	private function render_image( $element, $ticket_data, $x, $y ) {
		$width  = floatval( $element['width'] ?? 100 );
		$height = floatval( $element['height'] ?? 100 );
		$src    = $element['src'] ?? '';

		if ( ( $element['type'] ?? '' ) === 'google_map' && class_exists( 'TC_Ticket_Designer_Fields' ) ) {
			// Google Map: build a Static Maps URL from the element's own settings
			// (address/zoom/type), defaulting to the event location. Matches the
			// classic Google Map element.
			$addr = isset( $element['map_address'] ) ? trim( (string) $element['map_address'] ) : '';
			if ( '' === $addr ) {
				$addr = isset( $ticket_data['venue_name'] ) ? (string) $ticket_data['venue_name'] : '';
			}
			$zoom    = isset( $element['map_zoom'] ) ? (int) $element['map_zoom'] : 14;
			$maptype = isset( $element['map_maptype'] ) ? (string) $element['map_maptype'] : 'roadmap';
			$src     = \TC_Ticket_Designer_Fields::google_map_url( $addr, (int) round( $width ), (int) round( $height ), $zoom, $maptype );
		} else {
			// Data-bound images: pull the src from ticket_data using the element's
			// dataField (event_image, event_logo, sponsors_logo, …), falling back
			// to the element type for the legacy 'event_image' binding.
			$img_field = '';
			if ( ! empty( $element['dataField'] ) ) {
				$img_field = $element['dataField'];
			} elseif ( ( $element['type'] ?? '' ) === 'event_image' ) {
				$img_field = 'event_image';
			}
			if ( $img_field && isset( $ticket_data[ $img_field ] ) && '' !== $ticket_data[ $img_field ] ) {
				$src = $ticket_data[ $img_field ];
			}
		}

		if ( empty( $src ) ) {
			// Draw placeholder.
			$this->pdf->SetFillColor( 240, 240, 240 );
			$this->pdf->Rect( $x, $y, $width, $height, 'F' );
			return;
		}

		// Resolve the src to a LOCAL FILE that TCPDF can read. TCPDF must never
		// be handed a URL (it would fetch over HTTP(S) and fail on local
		// self-signed `.test` domains), nor a format it can't decode (webp/avif).
		// prepare_image_file() returns a path on disk (possibly a temp file it
		// created) or false when the image truly can't be obtained.
		$file = $this->prepare_image_file( $src, $element );

		if ( ! $file ) {
			// Couldn't resolve/convert the image — draw a visible placeholder
			// and record why, so a failing image is diagnosable from the log
			// instead of silently vanishing.
			$this->log_image_failure( $src, 'could not resolve to a readable local file' );
			$this->pdf->SetFillColor( 240, 240, 240 );
			$this->pdf->Rect( $x, $y, $width, $height, 'F' );
			return;
		}

		// Determine the explicit image type from the prepared file's extension
		// so TCPDF doesn't have to guess (and so a `.tmp` temp file still
		// decodes correctly).
		$type = strtoupper( pathinfo( $file, PATHINFO_EXTENSION ) );
		if ( 'JPG' === $type ) {
			$type = 'JPEG';
		}
		if ( ! in_array( $type, array( 'PNG', 'JPEG', 'GIF' ), true ) ) {
			$type = ''; // let TCPDF detect.
		}

		// Draw image. Pass the local file + explicit type; resize=false keeps
		// the exact box (so canvas px == PDF pt, 1:1). The image is stretched
		// to fill the element box, matching the canvas (fabric scales the image
		// to width×height) and the preview.
		try {
			$this->pdf->Image( $file, $x, $y, $width, $height, $type, '', '', false, 300, '', false, false, 0 );
		} catch ( Exception $e ) {
			$this->log_image_failure( $src, 'TCPDF::Image threw: ' . $e->getMessage() );
			$this->pdf->SetFillColor( 240, 240, 240 );
			$this->pdf->Rect( $x, $y, $width, $height, 'F' );
		} catch ( Error $e ) {
			$this->log_image_failure( $src, 'TCPDF::Image error: ' . $e->getMessage() );
			$this->pdf->SetFillColor( 240, 240, 240 );
			$this->pdf->Rect( $x, $y, $width, $height, 'F' );
		}
	}

	/**
	 * Temp files created during this request, removed after Output.
	 *
	 * @var array
	 */
	private $temp_image_files = array();

	/**
	 * Resolve an image element's `src` to a local file path TCPDF can decode.
	 *
	 * Handles, in order:
	 *   • data: URIs           → decode base64 to a temp file
	 *   • local URLs           → url_to_path() (uploads / plugins / themes / root)
	 *   • remote/unresolved    → wp_remote_get() (sslverify off for local hosts)
	 *   • webp / avif / others → convert to PNG via GD or Imagick
	 *
	 * @param string $src     Image source (URL or data URI).
	 * @param array  $element Element data (unused today, kept for future fit/bg).
	 * @return string|false Local file path (possibly temp), or false.
	 */
	private function prepare_image_file( $src, $element = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Kept for future fit/background handling; see docblock.
		$path = false;

		// 1) data: URI — decode straight to a temp file.
		if ( 0 === strpos( $src, 'data:' ) ) {
			$path = $this->data_uri_to_temp( $src );
			if ( ! $path ) {
				return false;
			}
		}

		// 2) Local URL → on-disk path.
		if ( ! $path ) {
			$local = $this->url_to_path( $src );
			if ( $local && file_exists( $local ) ) {
				$path = $local;
			}
		}

		// 3) Genuinely remote (or a local URL we couldn't map) → download.
		// sslverify is disabled because local dev hosts (.test/.local) use
		// self-signed certificates that would otherwise abort the fetch —
		// the very failure that made images disappear from the PDF.
		if ( ! $path && preg_match( '#^https?://#i', $src ) ) {
			$path = $this->download_to_temp( $src );
		}

		if ( ! $path || ! file_exists( $path ) ) {
			return false;
		}

		// 4) Normalise formats TCPDF can't decode (webp/avif) to PNG.
		$path = $this->ensure_tcpdf_readable( $path );

		return $path;
	}

	/**
	 * Decode a data: URI to a temp file. Returns the path or false.
	 *
	 * @param string $uri data: URI.
	 * @return string|false
	 */
	private function data_uri_to_temp( $uri ) {
		if ( ! preg_match( '#^data:image/([a-z0-9.+-]+);base64,(.+)$#is', $uri, $m ) ) {
			return false;
		}
		$ext  = strtolower( $m[1] );
		$ext  = ( 'jpeg' === $ext ) ? 'jpg' : preg_replace( '/[^a-z0-9]/', '', $ext );
		$data = base64_decode( $m[2], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding a data: URI image payload, not obfuscated code.
		if ( false === $data ) {
			return false;
		}
		$base = wp_tempnam( 'venuera-img-' );
		$tmp  = $base . '.' . ( $ext ? $ext : 'png' );
		wp_delete_file( $base ); // wp_tempnam() pre-creates an empty file at the base path; remove it so it doesn't leak.
		if ( false === file_put_contents( $tmp, $data ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a plugin-generated file to a temporary path for PDF embedding.
			return false;
		}
		$this->temp_image_files[] = $tmp;
		return $tmp;
	}

	/**
	 * Download a remote image to a temp file (SSL verification off so local
	 * self-signed dev hosts work). Returns the path or false.
	 *
	 * @param string $url Remote URL.
	 * @return string|false
	 */
	private function download_to_temp( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'sslverify'   => false,
				'redirection' => 3,
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			$this->log_image_failure( $url, 'remote fetch failed: ' . ( is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response ) ) );
			return false;
		}
		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			return false;
		}
		// Guess extension from content-type, default png.
		$ctype = wp_remote_retrieve_header( $response, 'content-type' );
		$ext   = 'png';
		if ( false !== strpos( $ctype, 'jpeg' ) || false !== strpos( $ctype, 'jpg' ) ) {
			$ext = 'jpg';
		} elseif ( false !== strpos( $ctype, 'gif' ) ) {
			$ext = 'gif';
		} elseif ( false !== strpos( $ctype, 'webp' ) ) {
			$ext = 'webp';
		}
		$base = wp_tempnam( 'venuera-img-' );
		$tmp  = $base . '.' . $ext;
		wp_delete_file( $base ); // wp_tempnam() pre-creates an empty file at the base path; remove it so it doesn't leak.
		if ( false === file_put_contents( $tmp, $body ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a plugin-generated file to a temporary path for PDF embedding.
			return false;
		}
		$this->temp_image_files[] = $tmp;
		return $tmp;
	}

	/**
	 * Ensure the file is in a format TCPDF can decode. webp/avif (and anything
	 * GD/Imagick can open but TCPDF can't) are re-encoded to PNG, preserving
	 * transparency. Returns a path (the original, or a new temp PNG).
	 *
	 * @param string $path Local file path.
	 * @return string Path TCPDF can read.
	 */
	private function ensure_tcpdf_readable( $path ) {
		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'webp', 'avif' ), true ) ) {
			return $path; // png/jpg/gif handled natively by TCPDF.
		}

		$base = wp_tempnam( 'venuera-img-' );
		$png  = $base . '.png';
		wp_delete_file( $base ); // wp_tempnam() pre-creates an empty file at the base path; remove it so it doesn't leak.

		// Prefer Imagick (better colour/alpha fidelity), fall back to GD.
		if ( class_exists( 'Imagick' ) ) {
			try {
				$im = new Imagick( $path );
				$im->setImageFormat( 'png' );
				$im->writeImage( $png );
				$im->clear();
				$this->temp_image_files[] = $png;
				return $png;
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Imagick conversion failed; intentionally fall through to the GD path below.
				// fall through to GD.
			}
		}

		if ( 'webp' === $ext && function_exists( 'imagecreatefromwebp' ) ) {
			$img = @imagecreatefromwebp( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- GD emits warnings on malformed image data; failure is handled below.
			if ( $img ) {
				imagepalettetotruecolor( $img );
				imagealphablending( $img, false );
				imagesavealpha( $img, true );
				if ( imagepng( $img, $png ) ) {
					imagedestroy( $img );
					$this->temp_image_files[] = $png;
					return $png;
				}
				imagedestroy( $img );
			}
		}

		// Couldn't convert — return the original and let TCPDF try (it will
		// fail gracefully into the placeholder, with a logged reason).
		$this->log_image_failure( $path, 'unsupported format (' . $ext . ') and no Imagick/GD webp support to convert' );
		return $path;
	}

	/**
	 * Log an image rendering failure (only when WP_DEBUG is on) so missing
	 * images are diagnosable instead of silently absent.
	 *
	 * @param string $src    The offending source.
	 * @param string $reason Human-readable reason.
	 */
	private function log_image_failure( $src, $reason ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[Venuera Ticket PDF] image not rendered (%s): %s', $reason, $src ) );
		}
	}

	/**
	 * Remove temp image files created during this request.
	 */
	private function cleanup_temp_images() {
		foreach ( $this->temp_image_files as $f ) {
			if ( $f && file_exists( $f ) ) {
				@unlink( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged -- Removing a temporary local file created by this plugin; WP_Filesystem adds no benefit for transient cleanup.
			}
		}
		$this->temp_image_files = array();
	}


	/**
	 * Render text element.
	 *
	 * @param array $element     Element data.
	 * @param array $ticket_data Ticket data.
	 * @param float $x           X position in points.
	 * @param float $y           Y position in points.
	 */
	private function render_text( $element, $ticket_data, $x, $y ) {
		$data_field = isset( $element['dataField'] ) ? (string) $element['dataField'] : '';
		$text       = $element['text'] ?? $element['staticText'] ?? '';

		if ( '' !== $data_field ) {
			// Explicit data field (named data ids carrying a dataField, the
			// generic dynamic_text element, and custom fields whose dataField is
			// `attendee_field_<id>` — already present in $ticket_data).
			$text = $ticket_data[ $data_field ] ?? '';
		} else {
			// Backward compatibility: named data id with no dataField. Map the
			// element type id to its matching ticket_data key.
			$named = self::get_named_field_key( $element['type'] ?? '' );
			if ( null !== $named ) {
				$text = $ticket_data[ $named ] ?? '';
			}
		}

		// Handle conditional display.
		if ( ! empty( $element['conditional'] ) && empty( $text ) ) {
			return;
		}

		// Add label.
		$label = $element['label'] ?? '';
		if ( $label ) {
			$position = $element['labelPosition'] ?? 'before';
			if ( 'before' === $position ) {
				$text = $label . ' ' . $text;
			} else {
				$text = $text . ' ' . $label;
			}
		}

		// Strip HTML tags AND decode entities so formatted values render as real
		// characters. wc_price() returns markup with entities (e.g. "100&nbsp;&#1088;&#1089;&#1076;"
		// for "100 рсд"); stripping tags alone would print the raw entities.
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
		// Normalise the non-breaking space (U+00A0) to a regular space for layout.
		$text = str_replace( "\xc2\xa0", ' ', $text );

		if ( '' === $text ) {
			return;
		}

		// Font settings. Using font size directly - canvas units = PDF points (1:1).
		$is_bold   = ( ( $element['fontWeight'] ?? 'normal' ) === 'bold' );
		$is_italic = ( ( $element['fontStyle'] ?? 'normal' ) === 'italic' );

		// Resolve the family to a real PDF font: a bundled embedded TTF for the
		// font library (so the PDF matches the editor 1:1), a core font for the
		// built-in families, or a DejaVu Unicode fallback when the chosen font
		// can't render the text's script.
		list( $font_family, $font_styles ) = $this->resolve_pdf_font(
			$element['fontFamily'] ?? 'Arial',
			$is_bold,
			$is_italic,
			$text
		);

		$font_size  = floatval( $element['fontSize'] ?? 14 );
		$fill       = $element['fill'] ?? '#333333';
		$text_align = $element['textAlign'] ?? 'left';
		$width      = floatval( $element['width'] ?? 0 );

		// Set font.
		$this->pdf->SetFont( $font_family, $font_styles, $font_size );

		// Set color.
		$rgb = $this->hex_to_rgb( $fill );
		$this->pdf->SetTextColor( $rgb[0], $rgb[1], $rgb[2] );

		// Alignment mapping.
		$align_map = array(
			'left'   => 'L',
			'center' => 'C',
			'right'  => 'R',
		);
		$align     = $align_map[ $text_align ] ?? 'L';

		// Calculate line height. Match fabric.Textbox's default lineHeight of
		// 1.16 (and the preview, set to the same) so multi-line spacing and the
		// first-line baseline line up across canvas/preview/PDF.
		$line_height = $font_size * 1.16;

		// Draw text into a box of the element's width with the matching alignment
		// so wrapping + alignment match the canvas. fabric.Textbox always has an
		// explicit width that drives both wrapping AND text-align; the saved
		// contract mirrors that. When width is missing (legacy templates from
		// before the explicit-width save), fall back to the remaining ticket
		// width so center / right alignment doesn't suddenly snap the text to
		// the ticket's right edge (TCPDF's `Cell(0, …)` consumes ALL remaining
		// width to the right margin, which sent right-aligned text to the
		// edge in PDFs that looked correct on canvas).
		if ( $width <= 0 ) {
			$width = max( 1, $this->width - $x );
		}

		$this->pdf->SetXY( $x, $y );

		// MultiCell wraps within the explicit box width and applies alignment,
		// mirroring the on-canvas Textbox.
		$this->pdf->MultiCell( $width, $line_height, $text, 0, $align, false, 1, $x, $y );
	}

	/**
	 * Map a named data element type id to its ticket_data key.
	 *
	 * Backward compatibility for templates saved before text/data elements
	 * carried an explicit `dataField`. The type id IS the ticket_data key for
	 * these named data elements.
	 *
	 * @param string $type Element type id.
	 * @return string|null Matching ticket_data key, or null when not a named data element.
	 */
	private static function get_named_field_key( $type ) {
		$map = array(
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

		return isset( $map[ $type ] ) ? $map[ $type ] : null;
	}

	/**
	 * Cache of registered TTF fonts: source path => TCPDF font name.
	 *
	 * @var array
	 */
	private static $ttf_cache = array();

	/**
	 * Resolve an element's font family/weight/style to a usable PDF font.
	 *
	 * Order of preference:
	 *  1. Bundled font-library family → embed the exact TTF variant (1:1 with the
	 *     editor). If the text needs a script the family lacks (e.g. Cyrillic in a
	 *     Latin-only display font), fall back to a DejaVu Unicode font instead.
	 *  2. Core family (Arial/Times/Courier…) → core PDF font, switched to DejaVu
	 *     when the text needs Unicode.
	 *
	 * @param string $family    Family label.
	 * @param bool   $is_bold   Bold requested.
	 * @param bool   $is_italic Italic requested.
	 * @param string $text      The text to render (for script detection).
	 * @return array{0:string,1:string} [ font name, style flags ].
	 */
	private function resolve_pdf_font( $family, $is_bold, $is_italic, $text ) {
		$needs_unicode = $this->needs_unicode_font( $text );
		$style_flags   = ( $is_bold ? 'B' : '' ) . ( $is_italic ? 'I' : '' );

		$key = class_exists( 'TC_Ticket_Designer_Fonts' ) ? TC_Ticket_Designer_Fonts::resolve_key( $family ) : null;

		// Not a bundled family → core PDF font (with DejaVu fallback for Unicode).
		if ( null === $key ) {
			$core = $this->map_font_family( $family );
			if ( $needs_unicode ) {
				$core = $this->unicode_font_for( $core );
			}
			return array( $core, $style_flags );
		}

		$fonts = TC_Ticket_Designer_Fonts::get_fonts();
		$font  = isset( $fonts[ $key ] ) ? $fonts[ $key ] : array(
			'category' => 'sans',
			'cyrillic' => false,
		);

		// Embed the bundled TTF for the requested variant (no style flag — the
		// TTF itself carries the weight/style).
		$variant = $is_bold && $is_italic ? 'bolditalic' : ( $is_bold ? 'bold' : ( $is_italic ? 'italic' : 'regular' ) );
		$name    = $this->register_ttf( $key, $variant );

		if ( $name ) {
			// Use the embedded TTF as long as it actually contains glyphs for
			// every character in the text. We inspect the font's real glyph
			// coverage (TCPDF's generated $cw metrics) rather than a crude
			// "outside Latin-1 → fall back" heuristic. That heuristic wrongly
			// demoted display fonts to DejaVu for ordinary typographic
			// punctuation — an em dash (—), curly quotes (' ' " "), ellipsis (…)
			// or bullet (•) all live above U+00FF but ARE present in these
			// Google fonts, so the editor's font must be kept. We only fall
			// back when a glyph is genuinely missing (e.g. Cyrillic in a
			// Latin-only display face), where the embedded font would otherwise
			// print blanks/tofu.
			$covers = $this->font_covers_text( $name, $text );
			if ( false === $covers ) {
				$dejavu = ( 'serif' === $font['category'] ) ? 'dejavuserif' : 'dejavusans';
				return array( $dejavu, $style_flags );
			}
			// Covered, or coverage unknown (metrics unreadable) → trust the TTF.
			return array( $name, '' );
		}

		// Embedding failed (e.g. fonts dir not writable) → graceful fallback.
		$core = ( 'serif' === $font['category'] ) ? 'times' : 'helvetica';
		if ( $needs_unicode ) {
			$core = $this->unicode_font_for( $core );
		}
		return array( $core, $style_flags );
	}

	/**
	 * Per-request cache of font glyph-width maps (codepoint => width), keyed by
	 * TCPDF font name. `false` means the metrics file couldn't be read.
	 *
	 * @var array
	 */
	private static $font_cw_cache = array();

	/**
	 * Whether the embedded TTF named $name has a glyph for every (non-space)
	 * character in $text.
	 *
	 * @param string $name TCPDF font name (from addTTFfont).
	 * @param string $text Text to render.
	 * @return bool|null true = fully covered, false = a glyph is missing,
	 *                    null = coverage unknown (metrics unreadable).
	 */
	private function font_covers_text( $name, $text ) {
		$cw = $this->get_font_widths( $name );
		if ( null === $cw ) {
			return null; // unknown — caller trusts the TTF.
		}
		if ( ! preg_match_all( '/./us', (string) $text, $m ) ) {
			return true;
		}
		foreach ( $m[0] as $ch ) {
			$cp = $this->utf8_codepoint( $ch );
			// Ignore whitespace and control chars — fonts often omit them and
			// they don't render a visible glyph anyway.
			if ( $cp <= 32 ) {
				continue;
			}
			if ( ! isset( $cw[ $cp ] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Load (and cache) a TCPDF font's $cw glyph-width map by including its
	 * generated metrics file. Returns the map, or null if unavailable.
	 *
	 * @param string $name TCPDF font name.
	 * @return array|null
	 */
	private function get_font_widths( $name ) {
		if ( array_key_exists( $name, self::$font_cw_cache ) ) {
			return self::$font_cw_cache[ $name ];
		}
		$dir  = defined( 'K_PATH_FONTS' ) ? K_PATH_FONTS : ( defined( 'TC_TICKET_DESIGNER_PARENT_DIR' ) ? TC_TICKET_DESIGNER_PARENT_DIR . 'vendor/tecnickcom/tcpdf/fonts/' : '' );
		$file = $dir ? rtrim( $dir, '/\\' ) . '/' . $name . '.php' : '';
		if ( ! $file || ! file_exists( $file ) ) {
			self::$font_cw_cache[ $name ] = null;
			return null;
		}
		// The metrics file defines $cw (and other vars) in the local scope.
		$cw = null;
		include $file;
		self::$font_cw_cache[ $name ] = is_array( $cw ) ? $cw : null;
		return self::$font_cw_cache[ $name ];
	}

	/**
	 * Decode the first character of a UTF-8 string to its Unicode codepoint.
	 *
	 * @param string $ch Single UTF-8 character.
	 * @return int Codepoint (0 on failure).
	 */
	private function utf8_codepoint( $ch ) {
		$b = unpack( 'C*', (string) $ch );
		if ( ! $b ) {
			return 0;
		}
		$n = count( $b );
		if ( 1 === $n ) {
			return $b[1];
		}
		if ( 2 === $n ) {
			return ( ( $b[1] & 0x1F ) << 6 ) | ( $b[2] & 0x3F );
		}
		if ( 3 === $n ) {
			return ( ( $b[1] & 0x0F ) << 12 ) | ( ( $b[2] & 0x3F ) << 6 ) | ( $b[3] & 0x3F );
		}
		if ( 4 === $n ) {
			return ( ( $b[1] & 0x07 ) << 18 ) | ( ( $b[2] & 0x3F ) << 12 ) | ( ( $b[3] & 0x3F ) << 6 ) | ( $b[4] & 0x3F );
		}
		return 0;
	}

	/**
	 * Register a bundled TTF variant with TCPDF and return its font name.
	 * Results are cached per source path for the request.
	 *
	 * @param string $key     Font key.
	 * @param string $variant regular|bold|italic|bolditalic.
	 * @return string|false TCPDF font name, or false on failure.
	 */
	private function register_ttf( $key, $variant ) {
		$path = TC_Ticket_Designer_Fonts::get_variant_path( $key, $variant );
		if ( ! $path ) {
			return false;
		}
		if ( isset( self::$ttf_cache[ $path ] ) ) {
			return self::$ttf_cache[ $path ];
		}
		if ( ! class_exists( 'TCPDF_FONTS' ) ) {
			return false;
		}
		try {
			// Converts + caches the font in K_PATH_FONTS on first use; idempotent.
			$name = TCPDF_FONTS::addTTFfont( $path, 'TrueTypeUnicode', '', 32 );
		} catch ( Exception $e ) {
			return false;
		} catch ( Error $e ) {
			return false;
		}
		if ( ! $name ) {
			return false;
		}
		self::$ttf_cache[ $path ] = $name;
		return $name;
	}

	/**
	 * Map font family to a bundled TCPDF font.
	 *
	 * The DejaVu family is bundled (sans/serif/mono, with bold/italic variants)
	 * because it is a full Unicode TrueType font: it renders accented Latin,
	 * Cyrillic and most scripts correctly, unlike the core PDF fonts (helvetica/
	 * times/courier) which are limited to Latin-1 and would drop characters such
	 * as the Serbian č/ć/đ/š/ž or any Cyrillic name. TCPDF subsets the embedded
	 * font, so only the glyphs actually used are included in the PDF.
	 *
	 * Bold/italic are applied via the style flag (B/I/BI) by render_text(), which
	 * resolves to dejavusansb / dejavuserifi / … (all bundled).
	 *
	 * @param string $font Font family name.
	 * @return string TCPDF font name.
	 */
	private function map_font_family( $font ) {
		// Latin text uses the core PDF fonts, which visually match the on-canvas
		// Arial/Times/Courier closely (so the PDF looks like the editor). Text
		// containing characters the core fonts can't encode (Cyrillic, Serbian
		// č/ć/đ/š/ž, etc.) is switched to the bundled Unicode DejaVu font by
		// unicode_font_for() at render time.
		$font_map = array(
			'Arial'           => 'helvetica',
			'Helvetica'       => 'helvetica',
			'Verdana'         => 'helvetica',
			'Times New Roman' => 'times',
			'Times'           => 'times',
			'Georgia'         => 'times',
			'Courier New'     => 'courier',
			'Courier'         => 'courier',
		);

		return $font_map[ $font ] ?? 'helvetica';
	}

	/**
	 * Whether a string contains characters the core PDF fonts cannot render
	 * (anything outside Latin-1 / ISO-8859-1, e.g. Cyrillic or Serbian Latin
	 * diacritics).
	 *
	 * @param string $text Text to test.
	 * @return bool
	 */
	private function needs_unicode_font( $text ) {
		return (bool) preg_match( '/[^\x{0000}-\x{00FF}]/u', (string) $text );
	}

	/**
	 * Map a core PDF font to its bundled DejaVu Unicode equivalent (same family
	 * class: sans/serif/mono), used when the text needs Unicode glyphs.
	 *
	 * @param string $core_font Core font name (helvetica/times/courier).
	 * @return string DejaVu font name.
	 */
	private function unicode_font_for( $core_font ) {
		switch ( $core_font ) {
			case 'times':
				return 'dejavuserif';
			case 'courier':
				return 'dejavusansmono';
			default:
				return 'dejavusans';
		}
	}

	/**
	 * Convert an image URL to a local filesystem path.
	 *
	 * TCPDF must be fed a LOCAL FILE — never a URL. If it receives a URL it
	 * tries to fetch it over HTTP(S), which on a local dev domain (e.g. a
	 * self-signed `.test` host) fails SSL verification and the image silently
	 * disappears from the PDF. So we resolve the URL to a file on disk through
	 * several strategies, scheme-agnostically (http/https/protocol-relative).
	 *
	 * Strategies, in order:
	 *   1. Match against the uploads / content / plugins / site base URLs and
	 *      swap the base URL for its base directory.
	 *   2. Fall back to the path after `/wp-content/` mapped onto WP_CONTENT_DIR.
	 *   3. Remap a stale ticket-designer template asset whose URL still points
	 *      at a previous plugin folder name (e.g. `plugins/ticketswp/…`) onto the
	 *      current plugin directory.
	 *
	 * @param string $url URL to convert.
	 * @return string|false Local path or false when it can't be resolved.
	 */
	private function url_to_path( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}
		// Data URIs are handled directly by the caller, not as file paths.
		if ( 0 === strpos( $url, 'data:' ) ) {
			return false;
		}

		// Strip any query string / fragment ("?ver=1.2", "#frag").
		$clean = preg_replace( '/[?#].*$/', '', $url );

		// An already-local absolute path (rare, but possible for bundled assets).
		if ( ( '/' === $clean[0] || preg_match( '/^[A-Za-z]:[\\\\\/]/', $clean ) ) && file_exists( $clean ) ) {
			return $clean;
		}

		// 1) Known WordPress base URL → base directory pairs.
		$upload_dir = wp_upload_dir();
		$pairs      = array(
			array( $upload_dir['baseurl'] ?? '', $upload_dir['basedir'] ?? '' ),
			array( defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL : content_url(), defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '' ),
			array( plugins_url(), defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : '' ),
			array( site_url(), untrailingslashit( ABSPATH ) ),
			array( home_url(), untrailingslashit( ABSPATH ) ),
		);
		foreach ( $pairs as $pair ) {
			$candidate = $this->map_url_base( $clean, $pair[0], $pair[1] );
			if ( $candidate && file_exists( $candidate ) ) {
				return $candidate;
			}
		}

		// 2) Scheme/host-agnostic: everything after `/wp-content/`.
		if ( preg_match( '#/wp-content/(.+)$#', $clean, $m ) ) {
			$rel       = $m[1];
			$candidate = untrailingslashit( WP_CONTENT_DIR ) . '/' . $rel;
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}

			// 3) Stale plugin-folder name: a ticket-designer template asset
			// saved under an older folder (e.g. `plugins/ticketswp/…`).
			// Re-anchor it on the CURRENT plugin directory.
			if ( preg_match( '#/includes/addons/ticket-designer/(.+)$#', $rel, $mm ) && defined( 'TC_TICKET_DESIGNER_PARENT_DIR' ) ) {
				$candidate = untrailingslashit( TC_TICKET_DESIGNER_PARENT_DIR ) . '/includes/addons/ticket-designer/' . $mm[1];
				if ( file_exists( $candidate ) ) {
					return $candidate;
				}
			}
		}

		return false;
	}

	/**
	 * Map a URL onto a local directory by stripping a known base URL prefix,
	 * ignoring the scheme (http vs https) and protocol-relative form so a URL
	 * saved as https still resolves on an http site and vice-versa.
	 *
	 * @param string $url      Cleaned URL (no query/fragment).
	 * @param string $base_url Base URL (e.g. uploads baseurl).
	 * @param string $base_dir Matching base directory.
	 * @return string|false Local path or false when the URL isn't under the base.
	 */
	private function map_url_base( $url, $base_url, $base_dir ) {
		if ( empty( $base_url ) || empty( $base_dir ) ) {
			return false;
		}
		// Reduce both sides to a scheme-less, leading-slash-less comparable form.
		$strip = function ( $s ) {
			$s = preg_replace( '#^https?:#i', '', (string) $s ); // drop scheme, keep //host/...
			$s = ltrim( $s, '/' );                                 // drop // (now host/...).
			return $s;
		};
		$u     = $strip( $url );
		$b     = $strip( $base_url );
		if ( '' !== $b && 0 === strpos( $u, $b ) ) {
			return untrailingslashit( $base_dir ) . substr( $u, strlen( $b ) );
		}
		return false;
	}

	/**
	 * Hex color to RGB array.
	 *
	 * @param string $hex Hex color.
	 * @return array RGB array.
	 */
	private function hex_to_rgb( $hex ) {
		if ( empty( $hex ) ) {
			return array( 0, 0, 0 );
		}

		$hex = ltrim( $hex, '#' );

		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Generate HTML fallback when TCPDF is not available.
	 *
	 * @param TC_Ticket_Designer_Template $template    Template.
	 * @param array                   $ticket_data Ticket data.
	 * @return string HTML.
	 */
	private function generate_html_fallback( $template, $ticket_data ) {
		return \Tickera\TC_Ticket_Designer_Frontend::render_template( $template, $ticket_data );
	}

	/**
	 * Generate PDF and save to file.
	 *
	 * @param TC_Ticket_Designer_Template $template    Template.
	 * @param array                   $ticket_data Ticket data.
	 * @return string|false File path or false on failure.
	 */
	public static function generate_and_save( $template, $ticket_data ) {
		$upload_dir  = wp_upload_dir();
		$tickets_dir = $upload_dir['basedir'] . '/venuera-tickets/' . gmdate( 'Y/m' );

		if ( ! file_exists( $tickets_dir ) ) {
			wp_mkdir_p( $tickets_dir );

			// Add security files.
			$base_dir = $upload_dir['basedir'] . '/venuera-tickets';
			if ( ! file_exists( $base_dir . '/index.php' ) ) {
				file_put_contents( $base_dir . '/index.php', '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a plugin-generated file into the uploads directory.
			}
			if ( ! file_exists( $base_dir . '/.htaccess' ) ) {
				file_put_contents( $base_dir . '/.htaccess', "Order deny,allow\nDeny from all\nAllow from 127.0.0.1" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing a plugin-generated file into the uploads directory.
			}
		}

		$filename = sprintf(
			'ticket-%s-%s.pdf',
			sanitize_file_name( $ticket_data['ticket_id'] ?? uniqid() ),
			wp_generate_password( 8, false )
		);
		$filepath = $tickets_dir . '/' . $filename;

		$result = self::generate( $template, $ticket_data, 'F', $filepath );

		if ( $result && file_exists( $filepath ) ) {
			return $filepath;
		}

		return false;
	}

	/**
	 * Get ticket sizes.
	 *
	 * @return array
	 */
	public static function get_ticket_sizes() {
		return self::TICKET_SIZES;
	}
}
