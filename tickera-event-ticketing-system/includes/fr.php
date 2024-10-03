<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
Tickera\tets_fs()->add_filter( 'show_deactivation_feedback_form', '__return_false' );
/**
 * Deprecated function "tc_get_license_key".
 * @since 3.5.3.0
 */
if ( !function_exists( 'tickera_get_license_key' ) ) {
    function tickera_get_license_key() {
        @($fr_license_key = \Tickera\tets_fs()->_get_license()->secret_key);
        if ( !empty( $fr_license_key ) ) {
            return $fr_license_key;
        } else {
            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $license_key = ( defined( 'TC_LCK' ) && TC_LCK !== '' ? TC_LCK : (( isset( $tc_general_settings['license_key'] ) && $tc_general_settings['license_key'] !== '' ? $tc_general_settings['license_key'] : '' )) );
            return $license_key;
        }
    }

}
/**
 * Deprecated function "tc_get_license_email".
 * @since 3.5.3.0
 */
if ( !function_exists( 'tickera_get_license_email' ) ) {
    function tickera_get_license_email() {
        @($fr_user = \Tickera\tets_fs()->get_user()->email);
        if ( !empty( $fr_user ) ) {
            return $fr_user;
        } else {
            return get_option( 'admin_email' );
        }
    }

}
Tickera\tets_fs()->add_action( 'addons/after_title', 'tickera_add_fs_templates_addons_poststuff_before_bundle_message_and_link' );
/**
 * Deprecated function "tc_add_fs_templates_addons_poststuff_before_bundle_message_and_link".
 * @since 3.5.3.0
 */
if ( !function_exists( 'tickera_add_fs_templates_addons_poststuff_before_bundle_message_and_link' ) ) {
    function tickera_add_fs_templates_addons_poststuff_before_bundle_message_and_link() {
        if ( tickera_iw_is_wl() == false ) {
            ?>
            <div class="updated"><p><?php 
            echo wp_kses_post( __( 'NOTE: All add-ons are included for FREE with the <a href="https://tickera.com/pricing/?utm_source=plugin&utm_medium=upsell&utm_campaign=addons" target="_blank">Bundle Package</a>', 'tickera-event-ticketing-system' ) );
            ?></p></div>
        <?php 
        }
    }

}
/**
 * Deprecated function "tc_members_account_url".
 * @since 3.5.3.0
 */
if ( !function_exists( 'tickera_members_account_url' ) ) {
    function tickera_members_account_url() {
        return 'https://tickera.com/members';
    }

}
Tickera\tets_fs()->add_filter( 'pricing_url', 'tickera_members_account_url' );
/**
 * Deprecated function "tc_is_pr_only".
 * @since 3.5.3.0
 */
if ( !function_exists( 'tickera_is_pr_only' ) ) {
    function tickera_is_pr_only() {
        return false;
    }

}