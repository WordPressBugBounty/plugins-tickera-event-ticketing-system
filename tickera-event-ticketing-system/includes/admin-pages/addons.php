<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $tc;
$tickera_addons = get_transient( 'tc_addons_data' . $tc->version );

if ( false === $tickera_addons ) {

    $tickera_addons_json = wp_remote_get( 'https://tickera.com/addons.json?ts=' . time(), array( 'user-agent' => 'Tickera Addons Page', 'sslverify' => false ) );
    $tickera_addons = json_decode( wp_remote_retrieve_body( $tickera_addons_json ), true );
    $tickera_addons = tickera_sanitize_array( $tickera_addons, true, true );

    if ( ! is_wp_error( $tickera_addons_json ) ) {

        $tickera_addons = json_decode( wp_remote_retrieve_body( $tickera_addons_json ), true );
        $tickera_addons = tickera_sanitize_array( $tickera_addons, true, true );

        if ( $tickera_addons ) {
            set_transient( 'tc_addons_data' . $tc->version, $tickera_addons, HOUR_IN_SECONDS );
        }
    }
} ?>
<div class="wrap tc_wrap">
    <h2><?php esc_html_e( 'Add-ons', 'tickera-event-ticketing-system' ); ?></h2>
    <div class="updated">
        <p><?php echo wp_kses_post( __( 'NOTE: All add-ons are included for FREE with the <a href="https://tickera.com/pricing/?utm_source=plugin&utm_medium=upsell&utm_campaign=addons" target="_blank">Bundle Package</a>', 'tickera-event-ticketing-system' ) ); ?></p>
    </div>
    <div class="tc_addons_wrap">
        <?php
        if ( count( $tickera_addons ) > 0 ) {
            foreach ( $tickera_addons as $tickera_addon ) {
                echo wp_kses_post( '<div class="tc_addon"><a target="_blank" href="' . esc_url( $tickera_addon->link ) . '">' );
                if ( ! empty( $tickera_addon->image ) ) {
                    echo wp_kses_post( '<div class="tc-addons-image"><img src="' . esc_url( $tickera_addon->image ) . '"/></div>' );
                } else {
                    echo wp_kses_post( '<h3>' . esc_html( $tickera_addon->title ) . '</h3>' );
                }
                echo wp_kses_post( '<div class="tc-addon-content"><p>' . esc_html( $tickera_addon->excerpt ) . '</p>' );
                echo wp_kses_post( '</div></a></div>' );
            }
        } else {
            echo wp_kses_post( __( 'Something went wrong and we can\'t get a list of add-ons :( The good news is that you can check them online <a href="https://tickera.com/tickera-events-add-ons/">here</a>', 'tickera-event-ticketing-system' ) );
        }
        ?>
    </div>
</div>
