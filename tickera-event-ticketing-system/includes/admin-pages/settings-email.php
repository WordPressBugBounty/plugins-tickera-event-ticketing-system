<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $tc_email_settings, $wp_rewrite;

/**
 * Update Email Settings on clicked save button
 */
if ( isset( $_POST[ 'save_tc_settings' ] ) ) {

    if ( check_admin_referer( 'save_settings' ) ) {

        if ( current_user_can( 'manage_options' ) || current_user_can( 'save_settings_cap' ) ) {
            update_option( 'tickera_email_setting', tickera_sanitize_array( $_POST[ 'tickera_email_setting' ], true ) );
            $wp_rewrite->flush_rules();
            $message = __( 'Settings data has been successfully saved.', 'tickera-event-ticketing-system' );

        } else {
            $message = __( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' );
        }
    }
}

$tc_email_settings = get_option( 'tickera_email_setting', false );

/**
 * Update Email Settings on page load.
 * Force save values base on some conditions.
 */
if ( isset( $tc_email_settings[ 'attendee_send_message' ] ) && 'yes' == $tc_email_settings[ 'attendee_send_message' ] ) {

    $general_settings = get_option( 'tickera_general_setting' );
    $owner_fields = isset( $general_settings[ 'show_owner_fields' ] ) ? $general_settings[ 'show_owner_fields' ] : 'no';
    $owner_email = isset( $general_settings[ 'show_owner_email_field' ] ) ? $general_settings[ 'show_owner_email_field' ] : 'no';

    // Disable the Attendee Order Completed Email if owner fields or owner email fields are disabled.
    if ( 'no' == $owner_fields || 'no' == $owner_email ) {
        $tc_email_settings[ 'attendee_send_message' ] = 'no';
        update_option( 'tickera_email_setting', tickera_sanitize_array( $tc_email_settings, true ) );
        $tc_email_settings = get_option( 'tickera_email_setting', false );
    }
}
?>
<div class="wrap tc_wrap">
    <?php
    if ( isset( $message ) ) { ?>
        <div id="message" class="updated fade"><p><?php echo esc_html( $message ); ?></p></div>
    <?php } ?>
    <div id="poststuff" class="metabox-holder tc-settings">
        <?php
        $current_tab_url = add_query_arg( array(
            'post_type' => 'tc_events',
            'page' => sanitize_key( $_GET[ 'page' ] ),
            'tab' => isset( $_GET[ 'tab' ] ) ? sanitize_key( $_GET[ 'tab' ] ) : '',
        ), admin_url( 'edit.php' ) );
        ?>
        <form id="tc-email-settings" method="post" action="<?php echo esc_url( $current_tab_url ); ?>">
            <?php
            wp_nonce_field( 'save_settings' );
            $email_settings = new \Tickera\TC_Settings_Email();
            $sections = $email_settings->get_settings_email_sections();

            foreach ( $sections as $section ) { ?>
                <div id="<?php echo esc_attr( $section[ 'name' ] ); ?>" class="postbox">
                    <h3><span><?php echo esc_attr( $section[ 'title' ] ); ?></span></h3>
                    <div class="inside">
                        <?php echo wp_kses_post( ( isset( $section[ 'class' ] ) && $section[ 'class' ] ) ? '<div class="' . esc_attr( $section[ 'class' ] ) . '"></div>': '' ); /* Currently use as a base selector to style the succeeding elements */ ?>
                        <?php if ( isset( $section[ 'description' ] ) && $section[ 'description' ] ) : ?>
                            <span class="description"><?php echo esc_html( $section[ 'description' ] ); ?></span>
                        <?php endif; ?>
                        <?php if ( isset( $section[ 'note' ] ) && $section[ 'note' ] ) : ?>
                            <div class="tc-notice tc-notice-warning"><p><?php echo esc_html( $section[ 'note' ] ); ?></p></div>
                        <?php endif; ?>
                        <table class="form-table">
                            <?php
                            $fields = $email_settings->get_settings_email_fields();
                            foreach ( $fields as $field ) {
                                if ( isset( $field[ 'section' ] ) && $field[ 'section' ] == $section[ 'name' ] ) { ?>
                                    <tr valign="top" id="<?php echo esc_attr( $field[ 'field_name' ] . '_holder' ); ?>" <?php echo wp_kses_post( \Tickera\TC_Fields::conditionals( $field, false ) ); ?>>
                                        <th scope="row"><label for="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( ( isset( $field[ 'tooltip' ] ) && $field[ 'tooltip' ] ) ? wp_kses_post( tickera_tooltip( $field[ 'tooltip' ] ) ) : '' ); ?></label></th>
                                        <td>
                                            <?php
                                            do_action( 'tc_before_settings_general_field_type_check' );
                                            echo wp_kses( \Tickera\TC_Fields::render_field( $field, 'tickera_email_setting' ), wp_kses_allowed_html( 'tickera_setting' ) );
                                            do_action( 'tc_after_settings_general_field_type_check' );
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
