<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $tc_gateway_plugins, $tc;
$settings = get_option( 'tickera_settings' );

if ( isset( $_POST[ 'gateway_settings' ] ) ) {

    if ( check_admin_referer( 'save_payment_gateways' ) ) {
        if ( current_user_can( 'manage_options' ) || current_user_can( 'save_settings_cap' ) ) {

            if ( isset( $_POST[ 'tc' ] ) ) {

                $post_data = tickera_sanitize_array( $_POST[ 'tc' ], true, true );
                $post_data = $post_data ? $post_data : [];

                $filtered_settings = apply_filters( 'tc_gateway_settings_filter', $post_data );
                $settings = array_merge( $settings, $filtered_settings );

                update_option( 'tickera_settings', tickera_sanitize_array( $settings, true, true ) );
                do_action( 'tc_save_tc_gateway_settings' );
            }

            echo wp_kses_post( '<div class="updated fade"><p>' . esc_html__( 'Settings saved.', 'tickera-event-ticketing-system' ) . '</p></div>' );

        } else {
            echo wp_kses_post( '<div class="updated fade"><p>' . esc_html__( 'You do not have required permissions for this action.', 'tickera-event-ticketing-system' ) . '</p></div>' );
        }
    }
} ?>
<div class="wrap tc_wrap" id="tc_delete_info">
    <div id="poststuff" class="metabox-holder tc-settings">
        <?php
        $current_tab_url = add_query_arg( [
            'post_type' => 'tc_events',
            'page' => sanitize_key( $_GET[ 'page' ] ),
            'tab' => isset( $_GET[ 'tab' ] ) ? sanitize_key( $_GET[ 'tab' ] ) : '',
        ], admin_url( 'edit.php' ) );
        ?>
        <form id="tc-gateways-form" method="post" action="<?php echo esc_url( $current_tab_url ); ?>">
            <?php wp_nonce_field( 'save_payment_gateways' ); ?>
            <input type="hidden" name="gateway_settings" value="1"/>
            <div id="tc_gateways" class="postbox">
                <h3>
                    <span><?php esc_html_e( 'Enable Payment Gateway(s)', 'tickera-event-ticketing-system' ) ?></span>
                    <span class="description"><?php esc_html_e( 'Enable payment gateways you want to use by clicking the icon of the desired payment gateway below.', 'tickera-event-ticketing-system' ) ?></span>
                </h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <td>
                                <?php foreach ( (array) $tc_gateway_plugins as $code => $plugin ) {
                                    if ( $tc->gateway_is_network_allowed( $code ) ) {

                                        $checked = '';
                                        $input_class = '';
                                        $gateway = new $plugin[ 0 ];

                                        if ( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) {
                                            $checked = ' checked="checked" readonly';
                                            $input_class = ' auto';

                                        } elseif ( in_array( $code, $this->get_setting( 'gateways->active', [] ) ) ) {
                                            $checked = ' checked="checked"';
                                        } ?>
                                        <div class="image-check-wrap<?php echo esc_attr( $input_class ); ?>">
                                            <label>
                                                <input type="checkbox" class="tc_active_gateways" name="tc[gateways][active][]" value="<?php echo esc_attr( $code ); ?>"<?php echo esc_html( $checked ); ?> />
                                                <div class="check-image check-image-<?php echo esc_html( in_array( $code, $this->get_setting( 'gateways->active', array() ) ) ) ?>">
                                                    <img src="<?php echo esc_url( $gateway->admin_img_url ); ?>"/>
                                                </div>
                                            </label>
                                        </div><!-- image-check-wrap --><?php
                                    }
                                } ?>
                            </td>
                        </tr>
                    </table>
                    <?php if ( ! tickera_iw_is_pr() || Tickera\tets_fs()->is_free_plan() ) : ?>
                        <a class="tc_link" target="_blank" href="https://tickera.com/?utm_source=plugin&utm_medium=upsell&utm_campaign=gateways"><?php esc_html_e( 'Get premium support, more payment gateways and unlock additional features', 'tickera-event-ticketing-system' ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php foreach ( (array) $tc_gateway_plugins as $code => $plugin ) {
                if ( $tc->gateway_is_network_allowed( $code ) ) {
                    $gateway = new $plugin[ 0 ];
                    $active_gateways = isset( $settings[ 'gateways' ][ 'active' ] ) ? $settings[ 'gateways' ][ 'active' ] : [];

                    if ( $active_gateways ) {
                        if ( in_array( $code, $active_gateways ) || ( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) ) {
                            $visible = true;

                        } else {
                            $visible = false;
                        }

                    } elseif ( isset( $gateway->automatically_activated ) && $gateway->automatically_activated ) {
                        $visible = true;

                    } else {
                        $visible = false;
                    }

                    $gateway->gateway_admin_settings( $settings, $visible );
                }
            } ?>
            <p class="submit">
                <input class="button-primary" type="submit" name="submit_settings" value="<?php esc_html_e( 'Save Changes', 'tickera-event-ticketing-system' ) ?>"/>
            </p>
        </form>
    </div>
</div>
