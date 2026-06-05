<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This file is used only on Tickera-specific admin-side custom settings or sections.
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $action, $page, $tc;
wp_reset_vars( array( 'action', 'page' ) );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Network settings page parameter is sanitized before rendering tabs.
$page = isset( $_GET[ 'page' ] ) ? sanitize_key( wp_unslash( $_GET[ 'page' ] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Network settings tab parameter is sanitized before rendering tabs.
$tab = isset( $_GET[ 'tab' ] ) ? sanitize_key( wp_unslash( $_GET[ 'tab' ] ) ) : '';

if ( empty( $tab ) ) {
    $tab = 'gateways';
}
?>
<div class="wrap tc_wrap nosubsub">
    <div class="icon32 icon32-posts-page" id="icon-options-general"><br></div>
    <h2><?php esc_html_e( 'Network Settings', 'tickera-event-ticketing-system' ); ?></h2>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Network settings submit flag only controls the saved settings notice.
    if ( isset( $_POST[ 'submit' ] ) ) { ?>
        <div id="message" class="updated fade"><p><?php esc_html_e( 'Settings saved successfully.', 'tickera-event-ticketing-system' ); ?></p></div><?php
    }
    $menus = [];
    $menus[ 'gateways' ] = __( 'Payment Gateways', 'tickera-event-ticketing-system' );
    $menus = tickera_apply_filters( 'tickera_network_settings_new_menus', $menus );
    ?>
    <h3 class="nav-tab-wrapper">
        <?php foreach ( $menus as $key => $menu ) { ?>
            <a class="nav-tab<?php echo wp_kses_post( ( $tab == $key ) ? ' nav-tab-active' : '' );?>" href="admin.php?page=<?php echo esc_attr( $page ); ?>&amp;tab=<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $menu ); ?></a>
        <?php } ?>
    </h3>
    <?php switch ( $tab ) {
        case 'gateways':
            $tc->show_network_page_tab( 'gateways' );
            break;
            // do_action( 'tickera_tab_case_' . $tab );

        default:
            tickera_do_action( 'tickera_network_settings_menu_' . $tab );
            break;
    } ?>
</div>
