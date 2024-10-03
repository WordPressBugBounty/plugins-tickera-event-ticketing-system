<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $tc;
update_option( 'tickera_wizard_step', 'checkin-apps' );
?>
<div class="tc-wiz-wrapper">
    <div class="tc-wiz-screen-wrap tc-finish-setup <?php echo esc_attr( tickera_wizard_wrapper_class() ); ?>">
        <h1><?php echo esc_html( $tc->title ); ?></h1>
        <?php tickera_wizard_progress(); ?>
        <div class="tc-clear"></div>
        <div class="tc-wiz-screen">
            <div class="tc-wiz-screen-header">
                <h2><?php esc_html_e( 'Check-in Applications', 'tickera-event-ticketing-system' ); ?></h2>
            </div><!-- .tc-wiz-screen-header -->
            <div class="tc-wiz-screen-content">
                <div class="tc-aplications-half tc-wiz-left-wrap">
                    <div class="tc-feature-lock-wrap">
                        <div class="tc-lock-icon"></div>
                        <span><?php esc_html_e( 'Free', 'tickera-event-ticketing-system' ); ?></span>
                    </div><!-- .tc-feature-lock-wrap -->
                    <div class="tc-wiz-image-wrap">
                        <img src="<?php echo esc_url( $tc->plugin_url ); ?>images/barcode-scanner.png"/>
                    </div><!-- .tc-wiz-image-wrap -->
                    <h2>Check-in attendees with built-in barcode reader</h2>
                    <p>Connect any barcode scanner to your computer and check the tickets in from the back end of your website.</p>
                </div><!-- .tc-aplications-half -->
                <div class="tc-aplications-or">
                    <div class="tc-app-or">
                        <?php esc_html_e( 'OR', 'tickera-event-ticketing-system' ); ?>
                    </div>
                </div><!-- .tc-aplications-or -->
                <div class="tc-aplications-half tc-wiz-right-wrap">
                    <div class="tc-feature-lock-wrap premium">
                        <div class="tc-lock-icon"></div>
                        <span><?php esc_html_e( 'Premium', 'tickera-event-ticketing-system' ); ?></span>
                    </div><!-- .tc-feature-lock-wrap -->
                    <div class="tc-wiz-image-wrap">
                        <img src="<?php echo esc_url( $tc->plugin_url ); ?>images/scan-apps.png"/>
                    </div><!-- .tc-wiz-image-wrap -->
                    <h2>Check-in attendees using the app</h2>
                    <p>Use a camera of your iOS or Android based device or check the tickets in on any desktop computer using Checkinera - our premium solution for checking the tickets in.<br><a href="https://tickera.com/checkinera-app/" target="_blank">More info...</a></p>
                </div><!-- .tc-aplications-half -->
                <?php tickera_wizard_navigation(); ?>
                <div class="tc-clear"></div>
            </div><!-- .tc-wiz-screen-content -->
        </div><!-- tc-wiz-screen -->
    </div><!-- .tc-wiz-screen-wrap -->
</div><!-- .tc-wiz-wrapper -->
