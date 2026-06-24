<?php
/**
 * Tickera Frontend 2026 — front-end theming module.
 *
 * Bundled internal module (loaded via TC::load_tc_addons). Provides selectable
 * style presets for Tickera's front-end output (single event first), mirroring
 * the Venuera front-end "style picker". Adds a body class + a scoped stylesheet
 * and an "Appearance" tab in Tickera Settings. Works for both standalone and
 * the WooCommerce Bridge (events are the same tc_events CPT in both cases).
 *
 * @package Tickera
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TC_Frontend_2026' ) ) {

	class TC_Frontend_2026 {

		/**
		 * Available style presets (besides 'none').
		 *
		 * @var string[]
		 */
		private static $presets = array( 'minimal', 'bold', 'dark' );

		/**
		 * Option name holding the chosen preset.
		 */
		const OPTION = 'tc_frontend_style';

		public static function init() {
			add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );

			if ( is_admin() ) {
				add_filter( 'tickera_settings_new_menus', array( __CLASS__, 'settings_menu' ) );
				add_action( 'tickera_settings_menu_appearance', array( __CLASS__, 'settings_page' ) );
			}
		}

		/**
		 * Current style preset: 'minimal' | 'bold' | 'dark' | 'none'.
		 *
		 * @return string
		 */
		public static function get_style() {
			$value = get_option( self::OPTION, 'minimal' );
			if ( 'none' === $value || in_array( $value, self::$presets, true ) ) {
				return $value;
			}
			return 'minimal';
		}

		/**
		 * Whether the built-in styles should apply (preset other than 'none').
		 *
		 * @return bool
		 */
		public static function styles_enabled() {
			return 'none' !== self::get_style();
		}

		/**
		 * Is the current request a Tickera front-end surface we theme?
		 * Single event + the events archive and event-category taxonomy archives
		 * (they reuse the same Tickera elements: date/location widgets, the
		 * tickets table and the add-to-cart button).
		 *
		 * @return bool
		 */
		public static function is_themed_screen() {
			if ( is_admin() ) {
				return false;
			}
			if ( is_singular( 'tc_events' )
				|| is_post_type_archive( 'tc_events' )
				|| is_tax( 'event_category' ) ) {
				return true;
			}
			// Standalone Tickera flow pages (cart, payment, order history, order
			// details/confirmation). Detect by the page IDs Tickera stores in its
			// settings (most robust), with a shortcode fallback.
			if ( is_page() ) {
				$page_id = (int) get_queried_object_id();
				if ( $page_id ) {
					$option_keys = array(
						'tickera_cart_page_id',
						'tickera_payment_page_id',
						'tickera_confirmation_page_id',
						'tickera_order_page_id',
						'tickera_process_payment_page_id',
					);
					foreach ( $option_keys as $opt ) {
						if ( (int) get_option( $opt ) === $page_id ) {
							return true;
						}
					}
				}
				$post = get_post();
				if ( $post instanceof WP_Post ) {
					$shortcodes = array( 'tc_cart', 'tc_order_history', 'tc_payment', 'tc_process_payment', 'tc_order_confirmation' );
					foreach ( $shortcodes as $sc ) {
						if ( has_shortcode( $post->post_content, $sc ) ) {
							return true;
						}
					}
				}
			}
			return false;
		}

		/**
		 * Add the scoping body classes on themed front-end screens.
		 *
		 * @param string[] $classes Body classes.
		 * @return string[]
		 */
		public static function body_class( $classes ) {
			if ( self::is_themed_screen() && self::styles_enabled() ) {
				$classes[] = 'tc-front-2026';
				$classes[] = 'tc-style-' . self::get_style();
			}
			return $classes;
		}

		/**
		 * Enqueue the front-end stylesheet on themed screens.
		 */
		public static function enqueue() {
			if ( ! self::is_themed_screen() || ! self::styles_enabled() ) {
				return;
			}
			$url  = plugin_dir_url( __FILE__ ) . 'assets/tickera-frontend-2026.css';
			$path = plugin_dir_path( __FILE__ ) . 'assets/tickera-frontend-2026.css';
			$ver  = file_exists( $path ) ? filemtime( $path ) : '1.0.0';
			wp_enqueue_style( 'tc-frontend-2026', $url, array(), $ver );
		}

		/**
		 * Register the "Appearance" settings tab.
		 *
		 * @param array $menus Existing settings menus.
		 * @return array
		 */
		public static function settings_menu( $menus ) {
			$menus['appearance'] = __( 'Appearance', 'tickera-event-ticketing-system' );
			return $menus;
		}

		/**
		 * Render (and save) the Appearance settings tab — the style picker.
		 */
		public static function settings_page() {

			// Save.
			if ( isset( $_POST['tc_frontend_2026_nonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tc_frontend_2026_nonce'] ) ), 'tc_frontend_2026_save' ) ) {
				$value = isset( $_POST[ self::OPTION ] ) ? sanitize_key( wp_unslash( $_POST[ self::OPTION ] ) ) : 'minimal';
				if ( 'none' !== $value && ! in_array( $value, self::$presets, true ) ) {
					$value = 'minimal';
				}
				update_option( self::OPTION, $value );
				echo '<div class="notice notice-success is-dismissible"><p>'
					. esc_html__( 'Appearance settings saved.', 'tickera-event-ticketing-system' )
					. '</p></div>';
			}

			$current = self::get_style();

			$presets = array(
				'minimal' => array(
					'label' => __( 'Minimal', 'tickera-event-ticketing-system' ),
					'desc'  => __( 'Clean and light, subtle borders, brand accents.', 'tickera-event-ticketing-system' ),
				),
				'bold'    => array(
					'label' => __( 'Bold', 'tickera-event-ticketing-system' ),
					'desc'  => __( 'Strong brand colours and gradient buttons.', 'tickera-event-ticketing-system' ),
				),
				'dark'    => array(
					'label' => __( 'Dark', 'tickera-event-ticketing-system' ),
					'desc'  => __( 'Dark cards with light text and brand accents.', 'tickera-event-ticketing-system' ),
				),
				'none'    => array(
					'label' => __( 'None (use my theme)', 'tickera-event-ticketing-system' ),
					'desc'  => __( 'Output plain markup and let your theme style everything.', 'tickera-event-ticketing-system' ),
				),
			);
			?>
			<div class="wrap tc_wrap">
				<div id="poststuff">
					<form action="" method="post">
						<?php wp_nonce_field( 'tc_frontend_2026_save', 'tc_frontend_2026_nonce' ); ?>
						<div class="postbox">
							<h3 class="hndle"><span><?php esc_html_e( 'Event Page Style', 'tickera-event-ticketing-system' ); ?></span></h3>
							<div class="inside">
								<p class="description" style="margin:2px 0 14px;">
									<?php esc_html_e( 'Choose a built-in design for your Tickera event pages (single event for now). Pick "None" to let your theme handle the styling.', 'tickera-event-ticketing-system' ); ?>
								</p>
								<div class="tc-style-presets">
									<?php foreach ( $presets as $key => $p ) : ?>
										<label class="tc-style-preset">
											<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current, $key ); ?>>
											<span class="tc-style-preset__thumb tc-style-thumb--<?php echo esc_attr( $key ); ?>"></span>
											<span class="tc-style-preset__label"><?php echo esc_html( $p['label'] ); ?></span>
											<span class="tc-style-preset__desc"><?php echo esc_html( $p['desc'] ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="submit" style="margin-top:16px;">
									<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'tickera-event-ticketing-system' ); ?></button>
								</p>
							</div>
						</div>
					</form>
				</div>
			</div>
			<style>
				.tc-style-presets{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;max-width:820px;}
				.tc-style-preset{display:block;border:1px solid #e5e7eb;border-radius:12px;padding:10px;cursor:pointer;background:#fff;transition:border-color .15s,box-shadow .15s;}
				.tc-style-preset:hover{border-color:#9D8FBE;}
				.tc-style-preset.is-active,
				.tc-style-preset:has(input:checked){border-color:#6b5f89;box-shadow:0 0 0 3px rgba(107,95,137,.18);}
				.tc-style-preset input{position:absolute;opacity:0;}
				.tc-style-preset__thumb{display:block;height:84px;border-radius:8px;margin-bottom:8px;background:#f3f4f6;}
				.tc-style-thumb--minimal{background:linear-gradient(180deg,#ffffff 60%,#f3f1f8 60%);border:1px solid #ece9f3;}
				.tc-style-thumb--bold{background:linear-gradient(135deg,#6b5f89,#9D8FBE);}
				.tc-style-thumb--dark{background:linear-gradient(135deg,#241d33,#4A4262);}
				.tc-style-thumb--none{background:repeating-linear-gradient(45deg,#f3f4f6,#f3f4f6 8px,#e9e9ee 8px,#e9e9ee 16px);}
				.tc-style-preset__label{display:block;font-weight:700;color:#1A1428;}
				.tc-style-preset__desc{display:block;font-size:12px;color:#6b7280;margin-top:2px;line-height:1.4;}
			</style>
			<?php
		}
	}

	TC_Frontend_2026::init();
}
