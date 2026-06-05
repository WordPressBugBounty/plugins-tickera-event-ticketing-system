<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file is used only on Tickera-specific admin-side custom settings or sections.
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tickera_general_settings, $wp_rewrite;

if ( isset( $_POST[ 'save_tc_settings' ] ) ) {

    if ( check_admin_referer( 'save_settings' ) ) {

        if ( current_user_can( 'manage_options' ) || current_user_can( 'save_settings_cap' ) ) {

            if ( isset( $_POST[ 'tickera_general_setting' ] ) ) {

                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized within tickera_sanitize_array().
                $save_data = isset( $_POST[ 'tickera_general_setting' ] ) ? tickera_sanitize_array( wp_unslash( $_POST[ 'tickera_general_setting' ] ) ) : [];

                update_option( 'tickera_general_setting', $save_data );
            }
            tickera_do_action( 'tickera_save_tc_general_settings' );
            tickera_save_page_ids();

            $wp_rewrite->flush_rules();
            $message = __( 'Settings data has been successfully saved.', 'tickera-event-ticketing-system' );

        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

$tickera_general_settings = get_option( 'tickera_general_setting', false );
?>
<div class="wrap tc_wrap">
    <?php if ( isset( $message ) ) { ?>
        <div id="message" class="updated fade"><p><?php echo esc_html($message); ?></p></div>
    <?php } ?>
    <div id="poststuff" class="metabox-holder tc-settings">
        <?php
        $general_setting_url = add_query_arg( array(
            'post_type' => 'tc_events',
            'page' => isset( $_GET[ 'page' ] ) ? sanitize_key( wp_unslash( $_GET[ 'page' ] ) ) : '',
            'tab' => isset( $_GET[ 'tab' ] ) ? sanitize_key( wp_unslash( $_GET[ 'tab' ] ) ) : '',
        ), admin_url( 'edit.php' ) );
        ?>
        <form id="tc-general-settings" method="post" action="<?php echo esc_url( $general_setting_url ); ?>">
            <?php wp_nonce_field( 'save_settings' );
            $general_settings = new \Tickera\TC_Settings_General();
            $sections = $general_settings->get_settings_general_sections();

            foreach ( $sections as $section ) { ?>
                <div id="<?php echo esc_attr( $section[ 'name' ] ); ?>" class="postbox">
                    <h3><span><?php echo esc_attr( $section[ 'title' ] ); ?></span></h3>
                    <div class="inside">
                        <span class="description"><?php echo wp_kses_post( $section[ 'description' ] ); ?></span>
                        <table class="form-table">
                            <?php
                            $fields = $general_settings->get_settings_general_fields();
                            foreach ( $fields as $field ) {
                                if ( isset( $field[ 'section' ] ) && $field[ 'section' ] == $section[ 'name' ] ) { ?>
                                    <tr valign="top" id="<?php echo esc_attr( $field[ 'field_name' ] . '_holder' ); ?>" <?php echo wp_kses_post( \Tickera\TC_Fields::conditionals( $field, false ) ); ?>>
                                        <th scope="row"><label for="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( ( isset( $field[ 'tooltip' ] ) && $field[ 'tooltip' ] ) ? wp_kses_post( tickera_tooltip( $field[ 'tooltip' ] ) ) : '' ); ?></label></th>
                                        <td>
                                            <?php
                                            tickera_do_action( 'tickera_before_settings_general_field_type_check', $field );
                                            echo wp_kses( \Tickera\TC_Fields::render_field( $field, 'tickera_general_setting' ), wp_kses_allowed_html( 'tickera_setting' ) );
                                            tickera_do_action( 'tickera_after_settings_general_field_type_check', $field ); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } ?>
                        </table>
                    </div>
                </div>
            <?php }
            submit_button( __( 'Save Settings', 'tickera-event-ticketing-system' ), 'primary', 'save_tc_settings' ); ?>
        </form>
    </div>
</div>
