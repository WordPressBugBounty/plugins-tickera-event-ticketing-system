<?php
/**
 * Ticket Designer Font Library
 *
 * Single source of truth for the bundled font families. The SAME TTF files are
 * used by the editor (via @font-face) and by the PDF generator (via TCPDF
 * addTTFfont), so the generated PDF matches the on-screen design 1:1.
 *
 * Font files live in assets/fonts/<key>-<variant>.ttf where variant is one of
 * regular | bold | italic | bolditalic.
 *
 * @package Venuera
 * @subpackage Addons/TicketDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ticket Designer fonts registry.
 */
class TC_Ticket_Designer_Fonts {

	/**
	 * Core PDF fonts (no embedding needed). Map of label => TCPDF core font.
	 * Kept for backward compatibility with templates saved before the font
	 * library existed, and as lightweight always-available choices.
	 *
	 * @var array
	 */
	private static $core = array(
		'Arial'           => 'helvetica',
		'Helvetica'       => 'helvetica',
		'Verdana'         => 'helvetica',
		'Times New Roman' => 'times',
		'Georgia'         => 'times',
		'Courier New'     => 'courier',
	);

	/**
	 * Cached registry.
	 *
	 * @var array|null
	 */
	private static $fonts = null;

	/**
	 * Get the bundled font registry.
	 *
	 * Each entry: key => [ label, category, cyrillic, variants[] ].
	 *
	 * @return array
	 */
	public static function get_fonts() {
		if ( null !== self::$fonts ) {
			return self::$fonts;
		}

		// category: sans | serif | display | handwriting
		// cyrillic: whether the family includes Cyrillic glyphs (affects PDF
		// fallback for non-Latin text).
		$f = array(
			// --- Sans-serif ---
			'roboto'           => array(
				'label'    => 'Roboto',
				'category' => 'sans',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			'open-sans'        => array(
				'label'    => 'Open Sans',
				'category' => 'sans',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			'lato'             => array(
				'label'    => 'Lato',
				'category' => 'sans',
				'cyrillic' => false,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			'montserrat'       => array(
				'label'    => 'Montserrat',
				'category' => 'sans',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			'poppins'          => array(
				'label'    => 'Poppins',
				'category' => 'sans',
				'cyrillic' => false,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			'oswald'           => array(
				'label'    => 'Oswald',
				'category' => 'sans',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold' ),
			),
			'raleway'          => array(
				'label'    => 'Raleway',
				'category' => 'sans',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			'nunito'           => array(
				'label'    => 'Nunito',
				'category' => 'sans',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			'work-sans'        => array(
				'label'    => 'Work Sans',
				'category' => 'sans',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold' ),
			),
			'ubuntu'           => array(
				'label'    => 'Ubuntu',
				'category' => 'sans',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			// --- Serif ---
			'merriweather'     => array(
				'label'    => 'Merriweather',
				'category' => 'serif',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic' ),
			),
			'playfair-display' => array(
				'label'    => 'Playfair Display',
				'category' => 'serif',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			'lora'             => array(
				'label'    => 'Lora',
				'category' => 'serif',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic' ),
			),
			'pt-serif'         => array(
				'label'    => 'PT Serif',
				'category' => 'serif',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold', 'italic', 'bolditalic' ),
			),
			// --- Display ---
			'bebas-neue'       => array(
				'label'    => 'Bebas Neue',
				'category' => 'display',
				'cyrillic' => false,
				'variants' => array( 'regular' ),
			),
			'anton'            => array(
				'label'    => 'Anton',
				'category' => 'display',
				'cyrillic' => false,
				'variants' => array( 'regular' ),
			),
			// --- Handwriting / decorative ---
			'caveat'           => array(
				'label'    => 'Caveat',
				'category' => 'handwriting',
				'cyrillic' => true,
				'variants' => array( 'regular', 'bold' ),
			),
			'dancing-script'   => array(
				'label'    => 'Dancing Script',
				'category' => 'handwriting',
				'cyrillic' => false,
				'variants' => array( 'regular', 'bold' ),
			),
			'lobster'          => array(
				'label'    => 'Lobster',
				'category' => 'handwriting',
				'cyrillic' => true,
				'variants' => array( 'regular' ),
			),
			'pacifico'         => array(
				'label'    => 'Pacifico',
				'category' => 'handwriting',
				'cyrillic' => false,
				'variants' => array( 'regular' ),
			),
			'great-vibes'      => array(
				'label'    => 'Great Vibes',
				'category' => 'handwriting',
				'cyrillic' => false,
				'variants' => array( 'regular' ),
			),
		);

		// Only keep families whose files are actually present on disk.
		$dir = self::get_fonts_dir();
		foreach ( $f as $key => $data ) {
			$present = array();
			foreach ( $data['variants'] as $v ) {
				if ( file_exists( $dir . $key . '-' . $v . '.ttf' ) ) {
					$present[] = $v;
				}
			}
			if ( empty( $present ) ) {
				unset( $f[ $key ] );
				continue;
			}
			$f[ $key ]['variants'] = $present;
		}

		self::$fonts = $f;
		return self::$fonts;
	}

	/**
	 * Filesystem path to the bundled fonts directory (trailing slash).
	 *
	 * @return string
	 */
	public static function get_fonts_dir() {
		return trailingslashit( tickera_ticket_designer()->get_path( 'assets/fonts' ) );
	}

	/**
	 * URL of the bundled fonts directory (trailing slash).
	 *
	 * @return string
	 */
	public static function get_fonts_url() {
		return trailingslashit( tickera_ticket_designer()->get_url( 'assets/fonts' ) );
	}

	/**
	 * Path to a specific font variant TTF, with graceful fallback to the
	 * family's regular weight when the requested variant is not bundled.
	 *
	 * @param string $key     Font key.
	 * @param string $variant regular|bold|italic|bolditalic.
	 * @return string|false Path or false.
	 */
	public static function get_variant_path( $key, $variant ) {
		$fonts = self::get_fonts();
		if ( ! isset( $fonts[ $key ] ) ) {
			return false;
		}
		$available = $fonts[ $key ]['variants'];
		// Best-available fallback chain.
		$chain = array( $variant );
		if ( 'bolditalic' === $variant ) {
			$chain = array( 'bolditalic', 'bold', 'italic', 'regular' );
		} elseif ( 'bold' === $variant ) {
			$chain = array( 'bold', 'regular' );
		} elseif ( 'italic' === $variant ) {
			$chain = array( 'italic', 'regular' );
		} else {
			$chain = array( 'regular' );
		}
		foreach ( $chain as $v ) {
			if ( in_array( $v, $available, true ) ) {
				$path = self::get_fonts_dir() . $key . '-' . $v . '.ttf';
				if ( file_exists( $path ) ) {
					return $path;
				}
			}
		}
		return false;
	}

	/**
	 * Resolve a font-family label (as stored on an element) to a registry key.
	 *
	 * @param string $label Family label, e.g. "Roboto".
	 * @return string|null Key, or null if it is not a bundled family (e.g. a core font).
	 */
	public static function resolve_key( $label ) {
		$label = trim( (string) $label );
		foreach ( self::get_fonts() as $key => $data ) {
			if ( strcasecmp( $data['label'], $label ) === 0 ) {
				return $key;
			}
		}
		return null;
	}

	/**
	 * Whether a label is a core (non-embedded) PDF font.
	 *
	 * @param string $label Family label.
	 * @return string|null Core TCPDF font name, or null.
	 */
	public static function core_font( $label ) {
		return isset( self::$core[ $label ] ) ? self::$core[ $label ] : null;
	}

	/**
	 * Build the editor/frontend font dropdown choices: core fonts first, then
	 * the bundled families grouped by category.
	 *
	 * @return array Map of family label => display label.
	 */
	public static function get_choices() {
		$choices = array(
			'Arial'           => 'Arial',
			'Helvetica'       => 'Helvetica',
			'Times New Roman' => 'Times New Roman',
			'Georgia'         => 'Georgia',
			'Courier New'     => 'Courier New',
		);
		foreach ( self::get_fonts() as $data ) {
			$choices[ $data['label'] ] = $data['label'];
		}
		return $choices;
	}

	/**
	 * Generate the @font-face CSS for every bundled variant. Family name is the
	 * label so editor/canvas/frontend all reference fonts by their display name.
	 *
	 * @return string CSS.
	 */
	public static function get_font_face_css() {
		$url     = self::get_fonts_url();
		$css     = '';
		$weights = array(
			'regular'    => array(
				'weight' => 400,
				'style'  => 'normal',
			),
			'bold'       => array(
				'weight' => 700,
				'style'  => 'normal',
			),
			'italic'     => array(
				'weight' => 400,
				'style'  => 'italic',
			),
			'bolditalic' => array(
				'weight' => 700,
				'style'  => 'italic',
			),
		);
		foreach ( self::get_fonts() as $key => $data ) {
			foreach ( $data['variants'] as $v ) {
				$w    = $weights[ $v ];
				$css .= sprintf(
					"@font-face{font-family:'%s';src:url('%s%s-%s.ttf') format('truetype');font-weight:%d;font-style:%s;font-display:swap;}\n",
					$data['label'],
					$url,
					$key,
					$v,
					$w['weight'],
					$w['style']
				);
			}
		}
		return $css;
	}

	/**
	 * Font family names that need to be web-loaded in the editor (for fabric to
	 * measure/render correctly).
	 *
	 * @return array
	 */
	public static function get_family_labels() {
		$labels = array();
		foreach ( self::get_fonts() as $data ) {
			$labels[] = $data['label'];
		}
		return $labels;
	}
}
