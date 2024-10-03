<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc;
?>
<div class="tc-wiz-wrapper">
    <div class="tc-wiz-screen-wrap <?php echo esc_attr( tickera_wizard_wrapper_class() ); ?>">
        <form name="tc-step-wrap-0" method="get" id="tc_wizard_start_form" action="<?php echo esc_url( admin_url( 'index.php' ) ); ?>">
            <h1><?php echo esc_html( $tc->title ); ?></h1>
            <input type="hidden" name="page" value="tc-installation-wizard"/>
            <input type="hidden" name="step" value="<?php echo esc_attr( tickera_wizard_get_start_screen_next_step() ); ?>"/>
            <div class="tc-wiz-screen">
                <div class="tc-wiz-screen-header">
                    <h2><?php esc_html_e( 'I will use...', 'tickera-event-ticketing-system' ); ?></h2>
                </div><!-- .tc-wiz-screen-header -->
                <div class="tc-wiz-screen-content">
                    <div class="tc-wiz-screen-half tc-standalone-tickera">
                        <?php $mode_checked = get_option( 'tickera_wizard_mode', 'sa' ); ?>
                        <input type="radio" id="tc-standalone" <?php checked( $mode_checked, 'sa', true ); ?>name="mode" class="tc_mode" value="sa"/>
                        <label for="tc-standalone"><span></span>
                            <h3><?php echo esc_html( sprintf( /* translators: %s: Tickera */ __( 'Standalone %s', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ); ?></h3>
                            <p>
                                <?php echo esc_html( sprintf( /* translators: %s: Tickera */ __( 'Great choice! %s is packed with a number of features (including payment gateways) which will help you out to sell tickets for your next event.', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ); ?>
                                <?php if ( ! tickera_iw_is_wl() ) { // Show only if the plugin isn't white-labeled at this point
                                    echo wp_kses_post( 'If that\'s not enough, make sure to check out our <a href="https://tickera.com/tickera-events-add-ons/">add-ons</a> section as well', 'tickera-event-ticketing-system' );
                                } ?>
                            </p>
                        </label>
                    </div><!-- tc-wiz-screen-half -->
                    <div class="tc-wiz-screen-half tc-standalone-tickera">
                        <input type="radio" id="tc-woocommerce" <?php checked( $mode_checked, 'wc', true ); ?> name="mode" class="tc_mode" value="wc"/>
                        <label for="tc-woocommerce"><span></span>
                            <h3><?php echo esc_html( sprintf( /* translators: %s: Tickera */ __( 'WooCommerce + %s', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ); ?></h3>
                            <p>
                                <?php if ( ! tickera_iw_is_wl() ) {
                                    echo wp_kses_post( __( 'With more than 100.000.000 downloads, <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> is certainly the most popular e-commerce system for the WordPress platform. <a href="https://tickera.com/addons/bridge-for-woocommerce/?utm_source=plugin&utm_medium=upsell&utm_campaign=wizard" target="_blank">Bridge for WooCommerce</a> add-on is required for this mode. You can install it later.', 'tickera-event-ticketing-system' ) );

                                } else {
                                    // If the plugin is white-labeled, don't show Bridge for WooCommerce link
                                    echo wp_kses_post( __( 'With more than 100.000.000 downloads, <a href="https://woocommerce.com/" target="_blank">WooCommerce</a> is certainly the most popular e-commerce system for the WordPress platform. <a href="https://tickera.com/addons/bridge-for-woocommerce/?utm_source=plugin&utm_medium=upsell&utm_campaign=wizard" target="_blank">Bridge for WooCommerce</a> add-on is required for this mode. You can install it later.', 'tickera-event-ticketing-system' ) );
                                } ?>
                            </p>
                        </label>
                    </div><!-- tc-wiz-screen-half -->
                    <?php tickera_wizard_navigation(); ?>
                    <div class="tc-clear"></div>
                </div><!-- .tc-wiz-screen-content -->
            </div><!-- tc-wiz-screen -->
        </form>
    </div><!-- .tc-wiz-screen-wrap -->
</div><!-- .tc-wiz-wrapper -->
