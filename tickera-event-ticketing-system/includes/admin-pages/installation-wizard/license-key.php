<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc;
?>
<div class="tc-wiz-wrapper">
    <div class="tc-wiz-screen-wrap tc-license-key <?php echo esc_attr( tickera_wizard_wrapper_class() ); ?>">
        <h1><?php echo esc_html( $tc->title ); ?></h1>
        <?php tickera_wizard_progress(); ?>
        <div class="tc-clear"></div>
        <div class="tc-wiz-screen">
            <div class="tc-wiz-screen-header">
                <h2><?php esc_html_e( 'License Key', 'tickera-event-ticketing-system' ); ?></h2>
            </div><!-- .tc-wiz-screen-header -->
            <div class="tc-wiz-screen-content">
                <p><?php esc_html_e( 'You can obtain your license key <a href="https://tickera.com/downloads/" target="_blank">here</a>. You\'ll need it in order to receive automatic updates for the plugin and add-ons and/or if you want to use check-in applications.', 'tickera-event-ticketing-system' ); ?></p>
                <?php $tc_general_settings = get_option( 'tickera_general_setting', false ); ?>
                <input type="text" placeholder="<?php esc_attr_e( 'License Key', 'tickera-event-ticketing-system' ); ?>" name="tc-license-key" id="tc-license-key" value="<?php echo esc_attr( isset( $tc_general_settings[ 'license_key' ] ) ? $tc_general_settings[ 'license_key' ] : '' ); ?>"/>
                <?php tickera_wizard_navigation(); ?>
                <div class="tc-clear"></div>
            </div><!-- .tc-wiz-screen-content -->
        </div><!-- tc-wiz-screen -->
    </div><!-- .tc-wiz-screen-wrap -->
</div><!-- .tc-wiz-wrapper -->
