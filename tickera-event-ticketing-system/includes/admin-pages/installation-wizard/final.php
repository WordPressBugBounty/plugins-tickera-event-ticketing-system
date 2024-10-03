<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $tc;
update_option( 'tickera_wizard_step', 'final' );
?>
<div class="tc-wiz-wrapper">
    <div class="tc-wiz-screen-wrap tc-finish-setup <?php echo esc_attr( tickera_wizard_wrapper_class() ); ?>">
        <h1><?php echo esc_html( $tc->title ); ?></h1>
        <?php tickera_wizard_progress(); ?>
        <div class="tc-clear"></div>
        <div class="tc-wiz-screen">
            <div class="tc-wiz-screen-header">
                <h2><?php esc_html_e( 'ALMOST READY!', 'tickera-event-ticketing-system' ); ?></h2>
            </div><!-- .tc-wiz-screen-header -->
            <div class="tc-wiz-screen-content">
                <p>
                    <?php
                        echo wp_kses_post( sprintf(
                            /* translators: %s: A link to Tickera > Settings */
                            __( 'The initial setup steps have been completed successfully. If you want, you can tweak the other settings <a href="%s" target="_blank">here</a> later.', 'tickera-event-ticketing-system' ),
                            esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_settings' ) )
                        ) );

                        if ( ! tickera_iw_is_wl() ) {
                            echo wp_kses_post( __( 'If you\'re stuck with anything at some point, don\'t hesitate to <a href="https://tickera.com/contact/" target="_blank">contact us</a>.', 'tickera-event-ticketing-system' ) );
                        }
                    ?>
                </p>
                <p><?php esc_html_e( 'Happy Ticketing!', 'tickera-event-ticketing-system' ); ?></p>
                <div class="tc-extra-steps">
                    <h3><?php esc_html_e( 'What to Do next?', 'tickera-event-ticketing-system' ); ?></h3>
                    <a href="<?php echo esc_attr( admin_url( 'edit.php?post_type=tc_events' ) ); ?>" target="_blank" class="tc-extra-button tc-button"><?php esc_html_e( 'CREATE YOUR EVENT', 'tickera-event-ticketing-system' ); ?></a>
                    <?php if ( 'sa' == tickera_wizard_mode() ) : ?>
                        <span class="tc-and-between">&</span>
                        <a href="<?php echo esc_attr( admin_url( 'edit.php?post_type=tc_events&page=tc_settings&tab=gateways' ) ); ?>" target="_blank" class="tc-extra-button tc-button"><?php esc_html_e( 'PAYMENT GATEWAY SETUP', 'tickera-event-ticketing-system' ); ?></a>
                    <?php endif; ?>
                </div><!-- .tc-extra-steps -->
                <div class="tc-wiz-screen-footer">
                    <button class="tc-finish-button tc-button" data-href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Finish', 'tickera-event-ticketing-system' ); ?></button>
                </div><!-- tc-wiz-screen-footer -->
                <div class="tc-clear"></div>
            </div><!-- .tc-wiz-screen-content -->
        </div><!-- tc-wiz-screen -->
    </div><!-- .tc-wiz-screen-wrap -->
</div><!-- .tc-wiz-wrapper -->
