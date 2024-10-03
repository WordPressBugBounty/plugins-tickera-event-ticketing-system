<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $action, $page, $tc;
$tc->session->start();

wp_reset_vars(array('action', 'page'));
$page = sanitize_key( $_GET['page'] );
$tab = (isset($_GET['tab'])) ? sanitize_key( $_GET['tab'] ) : '';
if (empty($tab)) {
    $tab = 'general';
}
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
    $menus = array();
    $menus['general'] = __('General', 'tickera-event-ticketing-system');
    $menus['gateways'] = __('Payment Gateways', 'tickera-event-ticketing-system');
    $menus['email'] = __('E-mail', 'tickera-event-ticketing-system');
    $menus['api'] = __('API Access', 'tickera-event-ticketing-system');
    $menus = apply_filters('tc_settings_new_menus', $menus);
    ?>
    <div class="nav-tab-wrapper">
        <ul>
            <?php foreach ($menus as $key => $menu) {
                $tab_url = add_query_arg(array(
                        'post_type' => 'tc_events',
                        'page' => $page,
                        'tab' => $key,
                    ), admin_url('edit.php'));
                ?>
                <li>
                    <a class="nav-tab<?php echo wp_kses_post( ( ( $tab == $key ) ? ' nav-tab-active' : '' ) ); ?>" href="<?php echo esc_url( sanitize_text_field( $tab_url ) ); ?>"><?php echo esc_html( sanitize_text_field( $menu ) ); ?></a>
                </li>
            <?php } ?>
        </ul>
    </div>
    <?php switch ($tab) {

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

        default: do_action('tc_settings_menu_' . $tab);
            break;
    } ?>
</div><?php
$tc->session->close();
