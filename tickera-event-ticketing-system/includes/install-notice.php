<?php
/**
 * Install Notice
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $tc; ?>
<div id="message" class="updated tickera-install-notice">
    <p><?php
        echo wp_kses_post( sprintf(
            /* translators: %s: Tickera */
            __( '<strong>Welcome to %s</strong> &#8211; Install pages required by the plugin automatically.', 'tickera-event-ticketing-system' ),
            esc_html( $tc->title )
        ) );
    ?></p>
    <p class="submit"><a href="<?php echo esc_url( add_query_arg( 'install_tickera_pages', 'true', admin_url( 'edit.php?post_type=tc_events&page=tc_settings' ) ) ); ?>" class="button-primary"><?php echo esc_html( sprintf( /* translators: %s: Tickera. */ __( 'Install %s Pages', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ); ?></a></p>
</div><?php
