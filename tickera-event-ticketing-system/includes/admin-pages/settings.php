<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $action, $page, $tc;
$tc->session->start();

wp_reset_vars(array('action', 'page'));
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin settings page parameter is sanitized before rendering tabs.
$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin settings tab parameter is sanitized before rendering tabs.
$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
?>
<div class="wrap tc_outside_wrap nosubsub">
    <div class="icon32 icon32-posts-page" id="icon-options-general"><br></div>
    <h2>
        <?php esc_html_e('Settings', 'tickera-event-ticketing-system'); ?>
        <?php if($tab == 'general'){ ?>
        <div class="tc_options_search">
            <input type="text" id="tc_options_search_val" placeholder="Search for options" />
        </div>
        <?php } ?>
    </h2>
    <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin settings submit flag only controls the saved settings notice. ?>
    <?php if (isset($_POST['submit'])) { ?>
        <div id="message" class="updated fade"><p><?php esc_html_e('Settings saved successfully.', 'tickera-event-ticketing-system'); ?></p></div>
    <?php }
    if (version_compare(phpversion(), '5.3', '<')) { ?>
        <div id="tc_php_53_version_error" class="error" style=""><p><?php
            echo wp_kses_post( sprintf(
                /* translators: %s: Currently running PHP Version */
                __('Your current version of PHP is %s and recommended version is at least 5.3. You should contact your hosting company and <a href="https://wordpress.org/about/requirements/">ask for upgrade</a>.', 'tickera-event-ticketing-system'),
                phpversion()
            ) )
            ?></p>
        </div>
    <?php }
    $tickera_setting_menus = [];
    $tickera_setting_menus['general'] = __('General', 'tickera-event-ticketing-system');
    $tickera_setting_menus['gateways'] = __('Payment Gateways', 'tickera-event-ticketing-system');
    $tickera_setting_menus['email'] = __('E-mail', 'tickera-event-ticketing-system');
    $tickera_setting_menus['api'] = __('API Access', 'tickera-event-ticketing-system');
    $tickera_setting_menus = tickera_apply_filters( 'tickera_settings_new_menus', $tickera_setting_menus);
    ?>
    <div class="nav-tab-wrapper">
        <ul>
            <?php 
                $tab_index = 0;
                foreach ($tickera_setting_menus as $tickera_setting_key => $tickera_menu) {
                    $tickera_setting_tab_url = add_query_arg(array(
                            'post_type' => 'tc_events',
                            'page' => $page,
                            'tab' => $tickera_setting_key,
                        ), admin_url('edit.php'));
                    if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_' . sanitize_text_field( $tickera_setting_key ) . '_settings_cap' ) ) { ?>
                        <li>
                            <a class="nav-tab<?php echo wp_kses_post( ( ( $tab == $tickera_setting_key || ( ! $tab && ! $tab_index ) ) ? ' nav-tab-active' : '' ) ); ?>" href="<?php echo esc_url( sanitize_text_field( $tickera_setting_tab_url ) ); ?>"><?php echo esc_html( sanitize_text_field( $tickera_menu ) ); ?></a>
                        </li><?php 
                        $tab = ( ! $tab && ! $tab_index ) ? $tickera_setting_key : $tab;
                        $tab_index++;
                    }
                } 
            ?>
        </ul>
    </div>
    <?php 
    switch ($tab) {

        case 'general':
            $tc->show_page_tab('general');
            break;

        case 'gateways':
            $tc->show_page_tab('gateways');
            break;

        case 'email':
            $tc->show_page_tab('email');
            break;

        case 'api':
            $tc->show_page_tab('api');
            break;

        case 'permissions':
            $tc->show_page_tab('permissions');
            break;

        case 'social':
            $tc->show_page_tab('social');
            break;

        default: tickera_do_action( 'tickera_settings_menu_' . $tab);
            break;
    } ?>
</div><?php
$tc->session->close();
