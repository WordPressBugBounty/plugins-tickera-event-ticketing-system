<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc;
?>
<div class="tc-wiz-wrapper">
    <div class="tc-wiz-screen-wrap tc-installation-page-setup <?php echo esc_attr( tickera_wizard_wrapper_class() ); ?>">
        <h1><?php echo esc_html( $tc->title ); ?></h1>
        <?php tickera_wizard_progress(); ?>
        <div class="tc-clear"></div>
        <div class="tc-wiz-screen">
            <div class="tc-wiz-screen-header">
                <h2><?php esc_html_e( 'Pages Setup', 'tickera-event-ticketing-system' ); ?></h2>
            </div><!-- .tc-wiz-screen-header -->
            <div class="tc-wiz-screen-content">
                <p><?php esc_html_e( 'Your event ticketing store needs some important pages. If you click "Continue", the following pages will be created automatically:', 'tickera-event-ticketing-system' ); ?></p>
                <div class="tc-pages-wrap">
                    <div class="tc-page-title">
                        <span><?php esc_html_e( 'Cart Page', 'tickera-event-ticketing-system' ); ?></span>
                    </div><!-- .tc-page-title -->
                    <div class="tc-page-description">
                        <p><?php
                            echo wp_kses_post( sprintf(
                                /* translators: %s: Admin nav menu url */
                                __( 'Your clients will be able to see their cart contents on this page, insert buyer and attendees\' info. You can add this page to the <a href="%s" target="_blank">site menu</a> later for easy accessibility.', 'tickera-event-ticketing-system' ),
                                esc_url( admin_url( 'nav-menus.php' ) )
                            ) );
                        ?></p>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->
                <div class="tc-pages-wrap">
                    <div class="tc-page-title">
                        <span><?php esc_html_e( 'Payment Page', 'tickera-event-ticketing-system' ); ?></span>
                    </div><!-- .tc-page-title -->
                    <div class="tc-page-description">
                        <p><?php esc_html_e( 'Your clients will choose payment method on this page. Do NOT add this page directly to the site menu.', 'tickera-event-ticketing-system' ); ?></p>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->
                <div class="tc-pages-wrap">
                    <div class="tc-page-title">
                        <span><?php esc_html_e( 'Payment Confirmation Page', 'tickera-event-ticketing-system' ); ?></span>
                    </div><!-- .tc-page-title -->
                    <div class="tc-page-description">
                        <p><?php esc_html_e( 'This page will be shown after completed payment. Information about payment status and link to order page will be visible on this page. Do NOT add this page directly to the site menu.', 'tickera-event-ticketing-system' ); ?></p>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->
                <div class="tc-pages-wrap">
                    <div class="tc-page-title">
                        <span><?php esc_html_e( 'Order Details Page', 'tickera-event-ticketing-system' ); ?></span>
                    </div><!-- .tc-page-title -->
                    <div class="tc-page-description">
                        <p><?php esc_html_e( 'The page where buyers will be able to check order status and / or download their ticket(s). Do NOT add this page directly to the site menu.', 'tickera-event-ticketing-system' ); ?></p>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->
                <div class="tc-pages-wrap">
                    <div class="tc-page-title">
                        <span><?php esc_html_e( 'Process Payment Page', 'tickera-event-ticketing-system' ); ?></span>
                    </div><!-- .tc-page-title -->
                    <div class="tc-page-description">
                        <p><?php esc_html_e( 'This page is used by the plugin internally to process payments. Do NOT add this page directly to the site menu.', 'tickera-event-ticketing-system' ); ?></p>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->

                <div class="tc-pages-wrap">
                    <div class="tc-page-title">
                        <span><?php esc_html_e( 'IPN Page (Instant Payment Notification)', 'tickera-event-ticketing-system' ); ?></span>
                    </div><!-- .tc-page-title -->
                    <div class="tc-page-description">
                        <p><?php esc_html_e( 'This page is used by the plugin internally to receive payment statuses from various payment gateways like PayPal, 2Checkout and similar. Do NOT add this page directly to the site menu.', 'tickera-event-ticketing-system' ); ?></p>
                    </div><!-- .tc-setting-field -->
                </div><!-- .tc-setting-wrap -->
                <?php tickera_wizard_navigation(); ?>
                <div class="tc-clear"></div>
            </div><!-- .tc-wiz-screen-content -->
        </div><!-- tc-wiz-screen -->
    </div><!-- .tc-wiz-screen-wrap -->
</div><!-- .tc-wiz-wrapper -->
