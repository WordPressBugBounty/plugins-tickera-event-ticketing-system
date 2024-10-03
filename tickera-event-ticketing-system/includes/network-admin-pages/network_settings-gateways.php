<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $tc_gateway_plugins, $tc;
$settings = get_site_option( 'tickera_network_settings', array() );

if ( ! is_array( $settings ) ) {
    $settings = array();
}

if ( isset( $_POST[ 'gateway_network_settings' ] ) ) {
    if ( current_user_can( 'manage_network_options' ) ) {
        if ( isset( $_POST[ 'tc' ] ) ) {
            $filtered_settings = apply_filters( 'tc_gateway_settings_filter', tickera_sanitize_array( $_POST[ 'tc' ], false, true ) );
            $settings = array_merge( $settings, $filtered_settings );
            update_site_option( 'tickera_network_settings', $settings );
        }
        echo wp_kses_post( '<div class="updated fade"><p>' . esc_html__( 'Settings saved.', 'tickera-event-ticketing-system' ) . '</p></div>' );

    } else {
        echo wp_kses_post( '<div class="updated fade"><p>' . esc_html__( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' ) . '</p></div>' );
    }
}
?>
<div id="poststuff" class="metabox-holder tc-settings">

    <form id="tc-gateways-form" method="post" action="admin.php?page=<?php echo esc_attr( $tc->name ); ?>_network_settings&tab=gateways">
        <input type="hidden" name="gateway_network_settings" value="1"/>
        <input type="hidden" name="tc[submit]" value=""/>
        <p class="description"><?php esc_html_e( 'Check payment gateways you want to allow on the subsites.', 'tickera-event-ticketing-system' ); ?></p>
        <div id="tc_gateways" class="postbox">
            <h3><span><?php esc_html_e( 'Select Payment Gateway(s)', 'tickera-event-ticketing-system' ) ?></span></h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <td>
                            <?php
                            foreach ( (array) $tc_gateway_plugins as $code => $plugin ) {
                                $gateway = new $plugin[ 0 ]; ?>
                                <div class="image-check-wrap">
                                    <label>
                                        <input type="checkbox" class="tc_active_gateways" name="tc[gateways][active][]" value="<?php echo esc_attr( $code ); ?>"<?php echo esc_attr( in_array( $code, $this->get_network_setting( 'gateways->active', array() ) ) ) ? ' checked="checked"' : ( ( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) ? ' checked="checked"' : '' ); ?> <?php echo esc_attr( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) ? 'disabled' : ''; ?> />
                                        <div class="check-image check-image-<?php echo esc_attr( in_array( $code, $this->get_network_setting( 'gateways->active', array() ) ) ) ?>">
                                            <img src="<?php echo esc_attr( $gateway->admin_img_url ); ?>"/>
                                        </div>

                                    </label>
                                </div><!-- image-check-wrap -->
                            <?php } ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php foreach ( (array) $tc_gateway_plugins as $code => $plugin ) {
            $gateway = new $plugin[ 0 ];
            if ( isset( $settings[ 'gateways' ][ 'active' ] ) ) {
                if ( in_array( $code, $settings[ 'gateways' ][ 'active' ] ) || ( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) ) {
                    $visible = true;
                } else {
                    $visible = false;
                }
            } else if ( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) {
                $visible = true;
            } else {
                $visible = false;
            }
            if ( method_exists( $gateway, 'gateway_network_admin_settings' ) ) {
                $gateway->gateway_network_admin_settings( $settings, $visible );
            }
        }
        ?>
        <p class="submit"><input class="button-primary" type="submit" name="submit_settings" value="<?php esc_html_e( 'Save Changes', 'tickera-event-ticketing-system' ) ?>"/></p>
    </form>
</div>
