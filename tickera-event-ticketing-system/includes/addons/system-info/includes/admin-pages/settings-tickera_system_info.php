<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $tc;

/**
 * Deprecated function "tc_let_to_num".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_let_to_num' ) ) {

    function tickera_let_to_num( $size ) {
        $l = substr( $size, -1 );
        $ret = substr( $size, 0, -1 );
        switch ( strtoupper( $l ) ) {
            case 'P':
                $ret *= 1024;
            case 'T':
                $ret *= 1024;
            case 'G':
                $ret *= 1024;
            case 'M':
                $ret *= 1024;
            case 'K':
                $ret *= 1024;
        }
        return $ret;
    }
}

$tc_general_settings = get_option( 'tickera_general_setting', false ); ?>
<div class="wrap tc_wrap" id="tc_system_info">
    <div id="poststuff" class="metabox-holder tc-settings">
        <form id="tc-system-info">
            <div class="postbox">
                <h3><span><?php esc_html_e( 'WordPress Environment', 'tickera-event-ticketing-system' ); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>
                    <table class="form-table" cellspacing="0" id="status">
                        <tbody>
                        <tr>
                            <td><?php esc_html_e( 'Home URL', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The URL of your site\'s homepage.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php form_option( 'home' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Site URL', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The root URL of your site.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php form_option( 'siteurl' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Permalink structure', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Website permalink structure', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( form_option( 'permalink_structure' ) ); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html( sprintf( /* translators: %s: Tickera */ __( '%s Version', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ); ?>:</td>
                            <td class="help"><?php
                                echo wp_kses_post( tickera_tooltip( sprintf(
                                    /* translators: %s: Tickera. */
                                    __( 'The version of %s installed on your site.', 'tickera-event-ticketing-system' ),
                                    esc_html( $tc->title )
                                ) ) );
                            ?></td>
                            <td><?php echo esc_html( $tc->version ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'WordPress version', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The version of WordPress installed on your site.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( bloginfo( 'version' ) ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'WP Multisite', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Whether or not you have WordPress Multisite.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php
                                if ( is_multisite() )
                                    echo wp_kses_post( '<span class="dashicons dashicons-yes"></span>' );
                                else
                                    echo wp_kses_post( '&ndash;' );
                                ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'WP memory limit', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The maximum amount of memory (RAM) that your site can use at one time.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php
                                $memory = tickera_let_to_num( WP_MEMORY_LIMIT );

                                if ( function_exists( 'memory_get_usage' ) ) {
                                    $system_memory = tickera_let_to_num( @ini_get( 'memory_limit' ) );
                                    $memory = max( $memory, $system_memory );
                                }

                                if ( $memory < 134217728 ) {
                                    echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-info"></span> ' . wp_kses_post( sprintf( /* translators: 1: Memory (Integer) 2: Link to Wordpress memory configuration document. */ __( '%1$s - We recommend setting memory limit to at least 128MB. See: <a href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">%2$s</a>', 'tickera-event-ticketing-system' ), esc_html( size_format( $memory ) ), esc_html( 'Increasing memory allocated to PHP', 'tickera-event-ticketing-system' ) ) ) . '</mark>' );

                                } else {
                                    echo wp_kses_post( '<mark class="yes">' . esc_html( size_format( $memory ) ) . '</mark>' );
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'WP Debug Mode', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Displays whether or not WordPress is in Debug Mode.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td>
                                <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                                    <?php echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'It is recommended to turn off WP_DEBUG (set it to false in the wp-config.php file) on live/production site.', 'tickera-event-ticketing-system' ) . '</mark>' ); ?>
                                <?php else : ?>
                                    <mark class="no">&ndash;</mark>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'TC Debug Mode', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( sprintf( /* translators: %s: Tickera */ esc_html__( 'Displays whether or not %s is in special-case debug mode.', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ) ); ?></td>
                            <td>
                                <?php if ( defined( 'TC_DEBUG' ) && TC_DEBUG ) : ?>
                                    <?php echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'It is recommended to turn off TC_DEBUG on production site (delete TC_DEBUG value in wp-config.php file)', 'tickera-event-ticketing-system' ) . '</mark>' );  ?>
                                <?php else : ?>
                                    <mark class="no">&ndash;</mark>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Caching Plugin', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Whether or not you have a caching plugin installed.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td>
                                <?php if ( defined( 'WP_CACHE' ) && WP_CACHE ) : ?>
                                    <?php echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . wp_kses_post( sprintf(  /* translators: %s: Tickera */ __( 'It seems that you have a caching plugin installed. In order to avoid potential issues, you should exclude all the pages which contain one of %s shortcodes from caching as well as all the cookies. Read more <a href="https://tickera.com/tickera-documentation/configuring-caching-plugins/">here</a>', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ) . '</mark>' ); ?>
                                <?php else : ?>
                                    <mark class="no">&ndash;</mark>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Cookie Hash', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'COOKIEHASH constant used for naming cookies.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( COOKIEHASH ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Cookie Path', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Path where cookies are accessible. If your cart is empty after adding a ticket to it, you may try changing a cookie path in wp-config.php by adding this line of code: define( "COOKIEPATH", "/" );', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( COOKIEPATH ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Language', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Current language used by WordPress.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( get_locale() ); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="postbox">
                <h3><span><?php esc_html_e( 'Server Environment', 'tickera-event-ticketing-system' ); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>
                    <table class="form-table" cellspacing="0">
                        <tbody>
                        <tr>
                            <td><?php esc_html_e( 'Server info', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Info about the server where your website is hosted.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php
                                echo esc_html( sanitize_text_field( $_SERVER[ 'SERVER_SOFTWARE' ] ) ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'PHP Version', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Version of PHP installed on your server.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php
                                // Check if phpversion function exists.
                                if ( function_exists( 'phpversion' ) ) {
                                    $php_version = phpversion();

                                    if ( version_compare( $php_version, '5.6', '<' ) ) {
                                        echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-info"></span> ' . esc_html( sprintf( /* translators: %s: Currently running PHP version. */ __( '%s - We recommend a minimum PHP version of 5.6', 'tickera-event-ticketing-system' ), esc_html( $php_version ) ) ) . '</mark>' );

                                    } else {
                                        echo wp_kses_post( '<mark class="yes">' . esc_html( $php_version ) . '</mark>' );
                                    }

                                } else {
                                    esc_html_e( "Couldn't determine PHP version because phpversion() doesn't exist.", 'tickera-event-ticketing-system' );
                                }
                                ?></td>
                        </tr>
                        <?php if ( function_exists( 'ini_get' ) ) : ?>
                            <tr>
                                <td><?php esc_html_e( 'PHP post max size', 'tickera-event-ticketing-system' ); ?>:</td>
                                <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The largest filesize that can be contained in one post.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                                <td><?php echo esc_html( size_format( tickera_let_to_num( ini_get( 'post_max_size' ) ) ) ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'PHP time limit', 'tickera-event-ticketing-system' ); ?>:</td>
                                <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Maximum execution time of a single operation before timing out', 'tickera-event-ticketing-system' ) ) ); ?></td>
                                <td><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'PHP max input vars', 'tickera-event-ticketing-system' ); ?>:</td>
                                <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The maximum number of input variables your server can use for a single function to avoid overloads.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                                <td><?php echo wp_kses_post( ( ini_get( 'max_input_vars' ) >= 1000 ) ? esc_html( ini_get( 'max_input_vars' ) ) : '<mark class="error"><span class="dashicons dashicons-info"></span> ' . esc_html( ini_get( 'max_input_vars' ) ) . ' - ' . esc_html__( 'If you expect to sell many tickets at once (per order) and use custom forms, you should increase this value to 2000 or more.', 'tickera-event-ticketing-system' ) . '</mark>' ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'cURL version', 'tickera-event-ticketing-system' ); ?>:</td>
                                <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The version of cURL installed on your server.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                                <td><?php
                                    if ( function_exists( 'curl_version' ) ) {
                                        $curl_version = curl_version();
                                        echo esc_html( $curl_version[ 'version' ] . ', ' . $curl_version[ 'ssl_version' ] );
                                    } else {
                                        esc_html_e( 'N/A', 'tickera-event-ticketing-system' );
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td><?php esc_html_e( 'Max upload size', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The largest filesize that can be uploaded to your website', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></td>
                        </tr>
                        <tr>
                            <?php
                            // allow_url_include
                            $mark = @ini_get( 'allow_url_fopen' ) ? 'yes' : 'error';
                            ?>
                            <td><?php esc_html_e( 'allow_url_fopen', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Ticket templates might need allow_url_fopen to be enabled on your server in order to retrieve images.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td>
                                <mark class="<?php echo esc_attr( $mark ); ?>">
                                    <?php echo wp_kses_post( ini_get( 'allow_url_fopen' ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-info"></span> ' . esc_html__( 'Ask your hosting provider to enable allow_url_fopen option if you\'re experiencing issues with opening / downloading PDF tickets.', 'tickera-event-ticketing-system' ) ); ?>
                                </mark>
                            </td>
                        </tr>
                        <?php
                        $mark = ( ! extension_loaded( 'imagick' ) && ! extension_loaded( 'gd' ) ) ? 'error' : 'yes';
                        ?>
                        <tr>
                            <td><?php esc_html_e( 'GD or Imagick extension', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'GD or Imagick PHP extension is required for the images on ticket templates', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td>
                                <mark class="<?php echo esc_attr( $mark ); ?>">
                                    <?php echo wp_kses_post( ( $mark == 'yes' ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>' ); ?><?php echo esc_html( ( $mark == 'error' ) ? esc_html__( 'GD or Imagick PHP extension is required for images to get retrieved in the ticket templates. We suggest you contact your hosting provider and ask them to enable one of these extensions on the server.', 'tickera-event-ticketing-system' ) : '' ); ?>
                                </mark>
                            </td>
                        </tr>
                        <?php do_action( 'tc_system_info_server_environment_options' ); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="postbox">
                <h3><span><?php esc_html_e( 'Active Plugins', 'tickera-event-ticketing-system' ); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>
                    <table class="form-table" cellspacing="0">
                        <tbody>
                        <?php
                        $active_plugins = (array) get_option( 'active_plugins', array() );

                        if ( is_multisite() ) {
                            $network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
                            $active_plugins = array_merge( $active_plugins, $network_activated_plugins );
                        }

                        foreach ( $active_plugins as $plugin ) {

                            $plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
                            $plugin_data = tickera_sanitize_array( $plugin_data, true );

                            $dirname = dirname( $plugin );
                            $version_string = '';
                            $network_string = '';

                            if ( ! empty( $plugin_data[ 'Name' ] ) ) {

                                // Link the plugin name to the plugin url if available.
                                $plugin_name = $plugin_data[ 'Name' ];
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $plugin_name ); ?></td>
                                    <td class="help">&nbsp</td>
                                    <td><?php echo esc_html( $plugin_data[ 'Version' ] . $version_string . $network_string ); ?></td>
                                </tr><?php
                            }
                        } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if ( ! is_plugin_active( 'bridge-for-woocommerce/bridge-for-woocommerce.php' ) ) { ?>
                <div class="postbox">
                    <h3><span><?php echo esc_html( sprintf( /* translators: %s: Tickera */ __( '%s Pages', 'tickera-event-ticketing-system' ), esc_html( $tc->title ) ) ); ?></span></h3>
                    <div class="inside">
                        <span class="description"></span>
                        <table class="form-table" cellspacing="0">
                            <tbody>
                            <?php

                            // If bridge is not activated
                            $check_pages = array(
                                _x( 'Cart page', 'Page setting', 'tickera-event-ticketing-system' ) => array(
                                    'option' => 'tickera_cart_page_id',
                                    'shortcode' => '[tc_cart]',
                                    'help' => __( 'The URL of your ticketing cart page', 'tickera-event-ticketing-system' ),
                                ),
                                _x( 'Payment page', 'Page setting', 'tickera-event-ticketing-system' ) => array(
                                    'option' => 'tickera_payment_page_id',
                                    'shortcode' => '[tc_payment]',
                                    'help' => __( 'The URL of your payment page', 'tickera-event-ticketing-system' ),
                                ),
                                _x( 'Payment confirmation page', 'Page setting', 'tickera-event-ticketing-system' ) => array(
                                    'option' => 'tickera_confirmation_page_id',
                                    'shortcode' => '[tc_order_confirmation]',
                                    'help' => __( 'The URL of your payment / order confirmation page', 'tickera-event-ticketing-system' ),
                                ),
                                _x( 'Order details page', 'Page setting', 'tickera-event-ticketing-system' ) => array(
                                    'option' => 'tickera_order_page_id',
                                    'shortcode' => '[tc_order_details]',
                                    'help' => __( 'The URL of your order details page', 'tickera-event-ticketing-system' ),
                                ),
                                _x( 'Process payment page', 'Page setting', 'tickera-event-ticketing-system' ) => array(
                                    'option' => 'tickera_process_payment_page_id',
                                    'shortcode' => '[tc_process_payment]',
                                    'help' => __( 'The URL of process payment page', 'tickera-event-ticketing-system' ),
                                ),
                                _x( 'IPN page', 'Page setting', 'tickera-event-ticketing-system' ) => array(
                                    'option' => 'tickera_ipn_page_id',
                                    'shortcode' => '[tc_ipn]',
                                    'help' => __( 'The URL of IPN (instant payment notification) page used by some payment gateways', 'tickera-event-ticketing-system' ),
                                ),
                            );

                            $alt = 1;

                            foreach ( $check_pages as $page_name => $values ) {
                                $error = false;
                                $page_id = get_option( sanitize_key( $values[ 'option' ] ) );
                                $page_name = $page_name;

                                echo wp_kses_post( '<tr><td>' . esc_html( $page_name ) . ':</td>' );
                                echo wp_kses_post( '<td class="help">' . wp_kses_post( tickera_tooltip( $values[ 'help' ], false ) ) . '</td><td>' );

                                // Page ID check.
                                if ( ! $page_id ) {
                                    echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . esc_html__( 'Page not set', 'tickera-event-ticketing-system' ) . '</mark>' );
                                    $error = true;
                                } else if ( ! get_post( $page_id ) ) {
                                    echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . esc_html__( 'Page ID is saved, but the page does not exist', 'tickera-event-ticketing-system' ) . '</mark>' );
                                    $error = true;
                                } else if ( get_post_status( $page_id ) !== 'publish' ) {
                                    echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . esc_html__( 'Page should have Publish status', 'tickera-event-ticketing-system' ) . '</mark>' );
                                    $error = true;
                                } else {

                                    // Shortcode check
                                    if ( $values[ 'shortcode' ] ) {
                                        $page = get_post( $page_id );

                                        if ( empty( $page ) ) {
                                            echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . esc_html__( 'Page does not exist', 'tickera-event-ticketing-system' ) . '</mark>' );
                                            $error = true;

                                        } else if ( ! strstr( $page->post_content, $values[ 'shortcode' ] ) ) {
                                            echo wp_kses_post( '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . esc_html( sprintf( /* translators: %s: Tickera Shortcode. */ __( 'Page does not contain required shortcode: %s', 'tickera-event-ticketing-system' ), esc_html( $values[ 'shortcode' ] ) ) ) . '</mark>' );
                                            $error = true;
                                        }
                                    }
                                }

                                if ( ! $error )
                                    echo wp_kses_post( '<mark class="yes">' . esc_html( str_replace( home_url(), '', get_permalink( $page_id ) ) ) . '</mark>' );
                                echo wp_kses_post( '</td></tr>' );
                            } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>
            <div class="postbox">
                <h3><span><?php esc_html_e( 'Theme', 'tickera-event-ticketing-system' ); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>
                    <table class="form-table" cellspacing="0">
                        <?php
                        include_once( ABSPATH . 'wp-admin/includes/theme-install.php' );
                        $active_theme = wp_get_theme();
                        $theme_version = $active_theme->Version;
                        ?>
                        <tbody>
                        <tr>
                            <td><?php esc_html_e( 'Title', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Currently active theme.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( $active_theme->Name ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Version', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The installed version of the current active theme.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( $theme_version ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Author URL', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The theme developers URL.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo esc_html( $active_theme->{'Author URI'} ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Child theme', 'tickera-event-ticketing-system' ); ?>:</td>
                            <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Displays whether or not the current theme is a child theme.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                            <td><?php echo wp_kses_post( is_child_theme() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<span class="dashicons dashicons-no-alt"></span>' ); ?></td>
                        </tr>
                        <?php
                        if ( is_child_theme() ) :
                            $parent_theme = wp_get_theme( $active_theme->Template );
                            ?>
                            <tr>
                                <td><?php esc_html_e( 'Parent theme title', 'tickera-event-ticketing-system' ); ?>:</td>
                                <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'The title of the parent theme.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                                <td><?php echo esc_html( $parent_theme->Name ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Parent theme version', 'tickera-event-ticketing-system' ); ?>:</td>
                                <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Version of the parent theme.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                                <td><?php echo esc_html( $parent_theme->Version ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Parent theme author URL', 'tickera-event-ticketing-system' ); ?>:</td>
                                <td class="help"><?php echo wp_kses_post( tickera_tooltip( __( 'Parent theme developers URL.', 'tickera-event-ticketing-system' ) ) ); ?></td>
                                <td><?php echo esc_html( $parent_theme->{'Author URI'} ); ?></td>
                            </tr>
                        <?php endif ?>
                        </tbody>
                    </table>
        </form>
    </div>
</div>

<div class="postbox">
    <h3><span><?php esc_html_e( 'Full Report', 'tickera-event-ticketing-system' ); ?></span></h3>
    <div class="inside">
        <span class="description"><?php esc_html_e( 'You can copy and paste this report when contacting support.', 'tickera-event-ticketing-system' ); ?></span>
        <textarea id="tc_system_info_text" style="width: 100%; height: 200px;"></textarea>
        <input type="submit" name="tc_system_info_button" id="tc_system_info_button" class="button button-primary" style="display: none;" value="Show Report">
    </div>
</div>
<?php do_action( 'tc_after_system' ); ?>
</div>
</div>
<script type="text/javascript">
    jQuery( document ).ready( function( $ ) {
        jQuery( '#tc_system_info_button' ).click( function() {

            var report = '',
                section_title = '',
                value_title = '',
                value = '';

            jQuery( '#tc-system-info .postbox' ).each( function() {
                section_title = jQuery( this ).find( 'h3.hndle span' ).html();
                report = report + '\n### ' + section_title + ' ###\n\n';

                jQuery( this ).find( '.form-table tr' ).each( function() {
                    value_title = jQuery( this ).find( 'td:eq(0)' ).html();
                    value_title = value_title.replace( ":", "" );

                    var $value_html = jQuery( this ).find( 'td:eq(2)' ).clone();
                    $value_html.find( '.dashicons-yes' ).replaceWith( '&#10004;' );
                    $value_html.find( '.dashicons-no-alt' ).replaceWith( '&#10060;' );//.dashicons-warning

                    var value = jQuery.trim( $value_html.text() );
                    report = report + '' + value_title + ': ' + value + '\n';
                } );
            } );

            jQuery( '#tc_system_info_text' ).val( report );
        } );
        jQuery( '#tc_system_info_button' ).click();
    } );
</script><?php
