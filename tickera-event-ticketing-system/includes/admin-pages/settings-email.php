<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tickera_email_settings, $wp_rewrite;

/**
 * Update Email Settings on clicked save button
 */
if ( isset( $_POST[ 'save_tc_settings' ] ) ) {

    if ( check_admin_referer( 'save_settings' ) ) {

        if ( current_user_can( 'manage_options' ) || current_user_can( 'save_settings_cap' ) ) {

            $tickera_email_settings = isset( $_POST[ 'tickera_email_setting' ] )
                    ? tickera_sanitize_array( wp_unslash( $_POST[ 'tickera_email_setting' ] ), true ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_POST variable is being sanitized by tickera_sanitize_array.
                    : [];

            update_option( 'tickera_email_setting', $tickera_email_settings );
            $wp_rewrite->flush_rules();
            $tickera_action_message = __( 'Settings data has been successfully saved.', 'tickera-event-ticketing-system' );

        } else {
            $tickera_action_message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

$tickera_email_settings = get_option( 'tickera_email_setting', false );

/**
 * Update Email Settings on page load.
 * Force save values base on some conditions.
 */
if ( isset( $tickera_email_settings[ 'attendee_send_message' ] ) && 'yes' == $tickera_email_settings[ 'attendee_send_message' ] ) {

    $tickera_general_settings = get_option( 'tickera_general_setting' );
    $tickera_owner_fields = isset( $tickera_general_settings[ 'show_owner_fields' ] ) ? $tickera_general_settings[ 'show_owner_fields' ] : 'no';
    $tickera_owner_email = isset( $tickera_general_settings[ 'show_owner_email_field' ] ) ? $tickera_general_settings[ 'show_owner_email_field' ] : 'no';

    // Disable the Attendee Order Completed Email if owner fields or owner email fields are disabled.
    if ( 'no' == $tickera_owner_fields || 'no' == $tickera_owner_email ) {
        $tickera_email_settings[ 'attendee_send_message' ] = 'no';
        update_option( 'tickera_email_setting', tickera_sanitize_array( $tickera_email_settings, true ) );
        $tickera_email_settings = get_option( 'tickera_email_setting', false );
    }
}
?>
<div class="wrap tc_wrap">
    <?php
    if ( isset( $tickera_action_message ) ) { ?>
        <div id="message" class="updated fade"><p><?php echo esc_html( $tickera_action_message ); ?></p></div>
    <?php } ?>
    <div id="poststuff" class="metabox-holder tc-settings">
        <?php
        $tickera_setting_current_tab_url = add_query_arg( array(
            'post_type' => 'tc_events',
            'page' => isset( $_GET[ 'page' ] ) ? sanitize_key( wp_unslash( $_GET[ 'page' ] ) ) : 1,
            'tab' => isset( $_GET[ 'tab' ] ) ? sanitize_key( wp_unslash( $_GET[ 'tab' ] ) ) : '',
        ), admin_url( 'edit.php' ) );
        ?>
        <form id="tc-email-settings" method="post" action="<?php echo esc_url( $tickera_setting_current_tab_url ); ?>">
            <?php
            wp_nonce_field( 'save_settings' );
            $tickera_email_settings_fields = new \Tickera\TC_Settings_Email();
            $tickera_email_sections = $tickera_email_settings_fields->get_settings_email_sections();

            foreach ( $tickera_email_sections as $tickera_email_section ) { ?>
                <div id="<?php echo esc_attr( $tickera_email_section[ 'name' ] ); ?>" class="postbox">
                    <h3><span><?php echo esc_attr( $tickera_email_section[ 'title' ] ); ?></span></h3>
                    <div class="inside">
                        <?php echo wp_kses_post( ( isset( $tickera_email_section[ 'class' ] ) && $tickera_email_section[ 'class' ] ) ? '<div class="' . esc_attr( $tickera_email_section[ 'class' ] ) . '"></div>': '' ); /* Currently use as a base selector to style the succeeding elements */ ?>
                        <?php if ( isset( $tickera_email_section[ 'description' ] ) && $tickera_email_section[ 'description' ] ) : ?>
                            <span class="description"><?php echo esc_html( $tickera_email_section[ 'description' ] ); ?></span>
                        <?php endif; ?>
                        <?php if ( isset( $tickera_email_section[ 'note' ] ) && $tickera_email_section[ 'note' ] ) : ?>
                            <div class="tc-notice tc-notice-warning"><p><?php echo esc_html( $tickera_email_section[ 'note' ] ); ?></p></div>
                        <?php endif; ?>
                        <table class="form-table">
                            <?php
                            $tickera_email_fields = $tickera_email_settings_fields->get_settings_email_fields();
                            foreach ( $tickera_email_fields as $tickera_email_field ) {
                                if ( isset( $tickera_email_field[ 'section' ] ) && $tickera_email_field[ 'section' ] == $tickera_email_section[ 'name' ] ) { ?>
                                    <tr valign="top" id="<?php echo esc_attr( $tickera_email_field[ 'field_name' ] . '_holder' ); ?>" <?php echo wp_kses_post( \Tickera\TC_Fields::conditionals( $tickera_email_field, false ) ); ?>>
                                        <th scope="row"><label for="<?php echo esc_attr( $tickera_email_field[ 'field_name' ] ); ?>"><?php echo esc_html( $tickera_email_field[ 'field_title' ] ); ?><?php echo wp_kses_post( ( isset( $tickera_email_field[ 'tooltip' ] ) && $tickera_email_field[ 'tooltip' ] ) ? wp_kses_post( tickera_tooltip( $tickera_email_field[ 'tooltip' ] ) ) : '' ); ?></label></th>
                                        <td>
                                            <?php
                                            tickera_do_action( 'tickera_before_settings_general_field_type_check' );
                                            echo wp_kses( \Tickera\TC_Fields::render_field( $tickera_email_field, 'tickera_email_setting' ), wp_kses_allowed_html( 'tickera_setting' ) );
                                            tickera_do_action( 'tickera_after_settings_general_field_type_check' );
                                            ?>
                                        </td>
                                    </tr><?php
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
