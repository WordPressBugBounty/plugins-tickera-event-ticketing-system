<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Deprecated function "tc_installation_wizard".
 * @since 3.5.3.0
 */
add_action( 'current_screen', 'tickera_installation_wizard' );
if ( ! function_exists( 'tickera_installation_wizard' ) ) {

    function tickera_installation_wizard() {

        global $tc;

        if ( current_user_can( 'manage_options' ) ) {

            if ( empty( $_GET[ 'page' ] ) || 'tc-installation-wizard' !== $_GET[ 'page' ] ) {
                return;
            }

            ob_start();
            wp_enqueue_style( 'tc-open-sans-font', 'http://fonts.googleapis.com/css?family=Open+Sans:300,700', array(), $tc->version );
            wp_enqueue_style( 'tc-installation-wizard', $tc->plugin_url . 'css/installation-wizard.css', array(), $tc->version );
            wp_enqueue_style( 'tc-chosen-installation-wizard', $tc->plugin_url . 'css/chosen.min.css', array(), $tc->version );

            wp_enqueue_script( 'tc-installation-wizard-js', $tc->plugin_url . 'js/installation-wizard.js', [ 'jquery' ], $tc->version );
            wp_enqueue_script( 'tc-chosen-installation-wizard', $tc->plugin_url . 'js/chosen.jquery.min.js', [ 'jquery' ], false, false );
            wp_localize_script( 'tc-installation-wizard-js', 'tc_ajax', array(
                'ajaxUrl' => apply_filters( 'tc_ajaxurl', admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ) ),
                'ajaxNonce' => wp_create_nonce( 'tc_ajax_nonce' ),
            ) );

            tickera_setup_wizard_header();
            tickera_setup_wizard_content();
            tickera_setup_wizard_footer();
            exit;
        }
    }
}

/**
 * Deprecated function "tc_setup_wizard_header".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_setup_wizard_header' ) ) {

    function tickera_setup_wizard_header() { ?>
        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title><?php esc_html_e( 'Installation Wizard', 'tickera-event-ticketing-system' ); ?></title>
            <?php do_action( 'admin_print_styles' ); ?>
            <?php do_action( 'admin_print_scripts' ); ?>
            <?php do_action( 'admin_head' ); ?>
        </head>
        <body class="tc-installation-wizard">
    <?php }
}


/**
 * Deprecated function "tc_setup_wizard_content".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_setup_wizard_content' ) ) {

    function tickera_setup_wizard_content() {
        global $tc;
        $steps = tickera_get_wizard_steps();
        $step = isset( $_GET[ 'step' ] ) ? sanitize_key( $_GET[ 'step' ] ) : 'start';

        if ( ! in_array( $step, $steps ) ) {
            $mode_checked = get_option( 'tickera_wizard_mode', 'sa' );
            $last_step = add_query_arg( array(
                'page' => 'tc-installation-wizard',
                'step' => tickera_wizard_get_start_screen_next_step(),
                'mode' => $mode_checked
            ), admin_url( 'index.php' ) );
            tickera_redirect( $last_step );
        }
        require_once( $tc->plugin_dir . 'includes/admin-pages/installation-wizard/' . $step . '.php' );
    }
}

/**
 * Deprecated function "tc_setup_wizard_footer".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_setup_wizard_footer' ) ) {

    function tickera_setup_wizard_footer() {
        $current_step = isset( $_GET[ 'step' ] ) ? sanitize_key( $_GET[ 'step' ] ) : 'start'; ?>
        <input type="hidden" name="tc_step" class="tc_step" value="<?php echo esc_attr( $current_step ); ?>">
        </body>
        </html>
    <?php }
}

/**
 * Deprecated function "tc_wizard_progress".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_wizard_progress' ) ) {

    function tickera_wizard_progress() {

        $steps = tickera_get_wizard_steps( true );
        $steps_count = count( $steps );
        $current_step = isset( $_GET[ 'step' ] ) ? sanitize_key( $_GET[ 'step' ] ) : 'start';

        $key = array_search( $current_step, $steps );
        $key = ( $key + 1 ); // Lift the index by 1 so it can match with an i variable
        ?>
        <div class="tc-steps-countdown <?php echo esc_attr( $current_step ); ?>">
            <div class="tc-progress-bar">
                <div class="tc-progress-bar-inside"></div>
            </div>
            <?php for ( $i = 1; $i <= $steps_count; $i++ ) : ?>
                <div class="tc-step-no tc-step-<?php echo esc_attr( (int) $i ); ?> <?php echo wp_kses_post( (int) $key >= $i ? 'tc-active-step' : '' ); ?>"><?php echo esc_html( $i ); ?></div>
            <?php endfor; ?>
        </div><!-- .tc-steps-countdown -->
        <?php
    }
}

/**
 * Deprecated function "tc_wizard_navigation".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_wizard_navigation' ) ) {

    function tickera_wizard_navigation() {

        $current_step = isset( $_GET[ 'step' ] ) ? sanitize_key( $_GET[ 'step' ] ) : 'start';
        $steps = tickera_get_wizard_steps( false );

        switch ( $current_step ) {

            case 'start':
                $skip_title = __( 'Skip Wizard', 'tickera-event-ticketing-system' );
                $skip_url = admin_url( 'index.php' );
                $continue_url = add_query_arg( array(
                    'page' => 'tc-installation-wizard',
                    'step' => $steps[ 0 ],
                ), admin_url( 'index.php' ) );
                break;

            default:
                $skip_title = __( 'Skip Step', 'tickera-event-ticketing-system' );
                $key = array_search( $current_step, $steps );
                $skip_url = add_query_arg( array(
                    'page' => 'tc-installation-wizard',
                    'step' => isset( $steps[ $key + 1 ] ) ? $steps[ $key + 1 ] : $steps[ 0 ],
                    'mode' => isset( $_GET[ 'mode' ] ) ? sanitize_key( $_GET[ 'mode' ] ) : 'sa'
                ), admin_url( 'index.php' ) );
                $continue_url = add_query_arg( array(
                    'page' => 'tc-installation-wizard',
                    'step' => isset( $steps[ $key + 1 ] ) ? $steps[ $key + 1 ] : $steps[ 0 ],
                    'mode' => isset( $_GET[ 'mode' ] ) ? sanitize_key( $_GET[ 'mode' ] ) : 'sa'
                ), admin_url( 'index.php' ) );
        }
        ?>
        <div class="tc-wiz-screen-footer">
            <?php if ( 'checkin-apps' !== $current_step ) : ?>
                <button class="tc-skip-button tc-button" data-href="<?php echo esc_url( $skip_url ); ?>"><?php echo esc_html( $skip_title ); ?></button>
            <?php endif; ?>
            <?php if ( 'start' == $current_step ) : ?>
                <input type="submit" class="tc-continue-button tc-button" value="<?php esc_html_e( 'Continue', 'tickera-event-ticketing-system' ); ?>"/>
            <?php else : ?>
                <button class="tc-continue-button tc-button" data-href="<?php echo esc_url( $continue_url ); ?>" onclick="window.location.href = '<?php echo esc_url( $continue_url ); ?>'"><?php esc_html_e( 'Continue', 'tickera-event-ticketing-system' ) ?></button>
            <?php endif; ?>
        </div><!-- tc-wiz-screen-footer -->
        <?php
    }
}

/**
 * Deprecated function "tc_wizard_mode".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_wizard_mode' ) ) {

    function tickera_wizard_mode() {

        if ( isset( $_GET[ 'mode' ] ) && isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'tc-installation-wizard' ) {

            if ( $_GET[ 'mode' ] == 'wc' || $_GET[ 'mode' ] == 'sa' ) {
                return sanitize_key( $_GET[ 'mode' ] );

            } else {
                return 'sa'; // standalone
            }
        }
    }
}

/**
 * Deprecated function "tc_get_wizard_steps".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_wizard_steps' ) ) {

    function tickera_get_wizard_steps( $include_start_step = true ) {

        $steps = [ 'start', 'license-key', 'settings', 'pages-setup', 'checkin-apps', 'final', 'finish' ];

        unset( $steps[ 1 ] );
        if ( ! $include_start_step ) {
            unset( $steps[ 0 ] ); // Start
        }

        if ( tickera_iw_is_wl() ) {
            unset( $steps[ 4 ] ); // Plugin is white-labeled, don't show the check-in apps screen
        }

        /* if ( tickera_iw_is_wl() || ( defined( 'TC_LCK' ) && TC_LCK !== '' ) ) {//plugin is white-labeled
            $key = array_search( 'license-key', $steps );
            unset( $steps[ 1 ] ); //'license-key'
        }*/

        if ( 1 == get_option( 'tickera_needs_pages', 1 ) ) {
            // Do nothing
        } else {
            unset( $steps[ 3 ] ); // pages-setup
        }

        /* if ( ! tickera_iw_is_pr() ) {
            unset( $steps[ 1 ] ); // 'license-key' // not pr version
        }*/

        if ( tickera_wizard_mode() == 'wc' ) {
            unset( $steps[ 2 ] ); // 'settings'
            unset( $steps[ 3 ] ); // 'pages-setup'
        }

        $steps = apply_filters( 'tc_wizard_steps', $steps, tickera_wizard_mode() );
        return array_merge( $steps ); // array_merge to rebase indexes after unsetting elements
    }
}

/**
 * Deprecated function "tc_wizard_wrapper_class".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_wizard_wrapper_class' ) ) {

    function tickera_wizard_wrapper_class() {
        $steps = tickera_get_wizard_steps( true );
        $steps_count = count( $steps );
        echo esc_attr( 'tc-wizard-steps-count-' . $steps_count );
    }
}

/**
 * Deprecated function "tc_wizard_get_start_screen_next_step".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_wizard_get_start_screen_next_step' ) ) {

    function tickera_wizard_get_start_screen_next_step() {
        $steps = tickera_get_wizard_steps( false );
        return $steps[ 0 ];
    }
}

/**
 * Deprecated function "tc_ajax_installation_wizard_save_step_data".
 * @since 3.5.3.0
 */
add_action( 'wp_ajax_tc_installation_wizard_save_step_data', 'tickera_ajax_installation_wizard_save_step_data' );
if ( ! function_exists( 'tickera_ajax_installation_wizard_save_step_data' ) ) {

    function tickera_ajax_installation_wizard_save_step_data() {

        if ( isset( $_POST[ 'nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {

            global $tc;
            $step = isset( $_POST[ 'data' ][ 'step' ] ) ? sanitize_key( $_POST[ 'data' ][ 'step' ] ) : 'start';

            switch ( $step ) {

                case 'start':
                    update_option( 'tickera_wizard_mode', isset( $_POST[ 'data' ][ 'mode' ] ) ? sanitize_text_field( $_POST[ 'data' ][ 'mode' ] ) : 'sa' );
                    break;

                case 'license-key':
                    $tc_general_settings = get_option( 'tickera_general_setting', false );
                    $tc_general_settings[ 'license_key' ] = sanitize_text_field( $_POST[ 'data' ][ 'license_key' ] );
                    update_option( 'tickera_general_setting', array_map( 'sanitize_text_field', $tc_general_settings ) );
                    // tc_fr_opt_in(sanitize_text_field($_POST['data']['license_key']));
                    break;

                case 'settings':
                    $tc_general_settings = get_option( 'tickera_general_setting', [] );
                    $tc_general_settings[ 'currencies' ] = sanitize_text_field( $_POST[ 'data' ][ 'currencies' ] );
                    $tc_general_settings[ 'currency_symbol' ] = sanitize_text_field( $_POST[ 'data' ][ 'currency_symbol' ] );
                    $tc_general_settings[ 'currency_position' ] = sanitize_text_field( $_POST[ 'data' ][ 'currency_position' ] );
                    $tc_general_settings[ 'price_format' ] = sanitize_text_field( $_POST[ 'data' ][ 'price_format' ] );
                    $tc_general_settings[ 'show_tax_rate' ] = sanitize_text_field( $_POST[ 'data' ][ 'show_tax_rate' ] );
                    $tc_general_settings[ 'tax_rate' ] = sanitize_text_field( $_POST[ 'data' ][ 'tax_rate' ] );
                    $tc_general_settings[ 'tax_inclusive' ] = sanitize_text_field( $_POST[ 'data' ][ 'tax_inclusive' ] );
                    $tc_general_settings[ 'tax_label' ] = sanitize_text_field( $_POST[ 'data' ][ 'tax_label' ] );
                    update_option( 'tickera_general_setting', array_map( 'sanitize_text_field', $tc_general_settings ) );
                    break;

                case 'pages-setup':
                    $tc_general_settings = get_option( 'tickera_general_setting', false );
                    $tc->create_pages();
                    $tc_general_settings[ 'tc_process_payment_use_virtual' ] = 'no';
                    $tc_general_settings[ 'tc_ipn_page_use_virtual' ] = 'no';
                    update_option( 'tickera_general_setting', array_map( 'sanitize_text_field', $tc_general_settings ) );
                    break;
            }

            update_option( 'tickera_wizard_step', sanitize_key( $step ) );
            exit;
        }
    }
}
