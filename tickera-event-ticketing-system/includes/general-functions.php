<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Check which radio value should be checked in the cart form
 *
 * @param type $field
 * @param type $field_value
 * @param type $field_values
 * @param $field_name
 * @param bool $ticket_type
 * @param bool $owner_index
 * @return boolean
 *
 * Deprecated function "tc_cart_field_get_radio_value_checked".
 * New function "tickera_cart_field_get_radio_value_checked"
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cart_field_get_radio_value_checked' ) ) {

    function tickera_cart_field_get_radio_value_checked( $field, $field_value, $field_values, $field_name, $ticket_type = false, $owner_index = false ) {

        if ( ! $_POST ) {
            return false;
        }

        $result = false;

        if ( isset( $_POST[ $field_name ] ) ) {

            if ( is_array( $_POST[ $field_name ] ) ) {
                $posted_value = tickera_sanitize_array( $_POST[ $field_name ], false, true );
                $posted_value = isset( $posted_value[ $ticket_type ][ $owner_index ] ) ? sanitize_text_field( $posted_value[ $ticket_type ][ $owner_index ] ) : ( isset( $field[ 'field_default_value' ] ) ? sanitize_text_field( $field[ 'field_default_value' ] ) : '' );

            } else {
                $posted_value = sanitize_text_field( $_POST[ $field_name ] );
            }
        }

        if ( isset( $posted_value ) ) {

            if ( trim( $posted_value ) == trim( $field_value ) ) {
                $result = true;
            }

        } else {

            if ( ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) ) ) {
                $result = true;
            }
        }

        return $result;
    }
}

/**
 * Deprecated function "tc_check_ajax".
 * New function "tickera_check_ajax"
 *
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_check_ajax' ) ) {

    function tickera_check_ajax(){
        $no_ajax = '';
        if( ! empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) == 'xmlhttprequest' ) {
            $no_ajax = true;
        }
        return $no_ajax;
    }
}

/**
 * Deprecated function "tc_final_cart_check".
 * New function "tickera_final_cart_check"
 *
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_final_cart_check' ) ) {

    function tickera_final_cart_check( $cart ) {

        global $tc;
        $tickets_soldout = [];
        $error_numbers = 0;

        foreach ( $cart as $ticket_type_id => $tc_quantity ) {
            $ticket = new \Tickera\TC_Ticket( $ticket_type_id );

            if ( ! \Tickera\TC_Ticket::is_sales_available( $ticket_type_id )
                || $ticket->is_sold_ticket_exceeded_limit_level() ) {
                $tickets_soldout[] = $ticket->id;
                $error_numbers++;
            }
        }

        do_action( 'tc_add_more_final_checks', $cart );

        if ( $error_numbers > 0 ) {
            $tc->session->set( 'tc_cart_ticket_error_ids', $tickets_soldout );
            $tc->session->set( 'tc_remove_from_cart', $tickets_soldout );
            tickera_redirect( $tc->get_cart_slug( true ), true );
        }
    }
}

/**
 * Check which select option value should be selected in the cart form
 *
 * @param type $field
 * @param type $field_value
 * @param type $field_name
 * @param bool $ticket_type
 * @param bool $owner_index
 * @return boolean
 *
 * Deprecated function "tc_cart_field_get_option_value_selected".
 * New function "tickera_cart_field_get_option_value_selected"
 *
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cart_field_get_option_value_selected' ) ) {

    function tickera_cart_field_get_option_value_selected( $field, $field_value, $field_name, $ticket_type = false, $owner_index = false ) {

        $result = false;

        if ( isset( $_POST[ $field_name ] ) ) {

            if ( is_array( $_POST[ $field_name ] ) ) {
                $posted_value = tickera_sanitize_array( $_POST[ $field_name ], false, true );
                $posted_value = isset( $posted_value[ $ticket_type ][ $owner_index ] ) ? sanitize_text_field( $posted_value[ $ticket_type ][ $owner_index ] ) : '';

            } else {
                $posted_value = sanitize_text_field( $_POST[ $field_name ] );
            }
        }

        if ( isset( $posted_value ) ) {

            if ( trim( $posted_value ) == trim( $field_value ) ) {
                $result = true;
            }

        } else {

            if ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) ) {
                $result = true;
            }
        }

        return $result;
    }
}

/**
 * Get checkbox or radio button posted values
 *
 * @param type $field_name
 * @param bool $ticket_type
 * @param bool $owner_index
 * @return type
 *
 * Deprecated function "tc_cart_field_posted_values".
 * New function "tickera_cart_field_posted_values"
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cart_field_posted_values' ) ) {

    function tickera_cart_field_posted_values( $field_name, $ticket_type = false, $owner_index = false ) {

        if ( isset( $_POST[ $field_name ] ) ) {

            if ( is_array( $_POST[ $field_name ] ) ) {
                $posted_value = tickera_sanitize_array( $_POST[ $field_name ], false, true );
                $posted_value = isset( $posted_value[ $ticket_type ][ $owner_index ] ) ? sanitize_text_field( $posted_value[ $ticket_type ][ $owner_index ] ) : '';

            } else {
                $posted_value = sanitize_text_field( $_POST[ $field_name ] );
            }
        }

        if ( isset( $posted_value ) ) {
            return $posted_value;
        }
    }
}

/**
 * Check which checboxes should be checked on the cart form
 *
 * @param type $field
 * @param type $field_value
 * @param type $field_values
 * @param type $field_name
 * @param bool $ticket_type
 * @param bool $owner_index
 * @return boolean
 *
 * Deprecated function "tc_cart_field_get_checkbox_value_checked".
 * New function "titckera_cart_field_get_checkbox_value_checked"
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cart_field_get_checkbox_value_checked' ) ) {

    function tickera_cart_field_get_checkbox_value_checked( $field, $field_value, $field_values, $field_name, $ticket_type = false, $owner_index = false ) {
        $result = false;

        if ( isset( $_POST[ $field_name ] ) ) {

            if ( is_array( $_POST[ $field_name ] ) ) {
                $posted_value = tickera_sanitize_array( $_POST[ $field_name ], false, true );
                $posted_value = isset( $posted_value[ $ticket_type ][ $owner_index ] ) ? sanitize_text_field( $posted_value[ $ticket_type ][ $owner_index ] ) : '';

            } else {
                $posted_value = sanitize_text_field( $_POST[ $field_name ] );
            }

            $posted_value = explode( ', ', $posted_value );
        }

        if ( isset( $posted_value ) ) {

            if ( in_array( trim( $field_value ), $posted_value ) ) {
                $result = true;
            }

        } else {

            if ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) ) {
                $result = true;
            }
        }

        return $result;
    }
}

/**
 * Start Session
 * Suppressed error to avoid process interruption.
 * Remove @ to display error.
 * Keeping the function for other add-on to use.
 *
 * Deprecated function "tc_session_start".
 * New function "tickera_session_start"
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_session_start' ) ) {

    function tickera_session_start() {
        if ( ! session_id() || ! isset( $_SESSION ) || session_status() != PHP_SESSION_ACTIVE ) {
            @session_start();
        }
    }
}

/**
 * Disable session write right after a data is stored.
 * Suppressed error to avoid process interruption.
 * Keeping the function for other add-on to use.
 * @since 3.5.1.5
 */
if ( ! function_exists( 'tickera_session_write_close' ) ) {

    function tickera_session_write_close() {
        if ( session_id() || isset( $_SESSION ) || session_status() == PHP_SESSION_ACTIVE ) {
            @session_write_close();
        }
    }
}

if ( ! function_exists( 'tickera_sanitize_array' ) ) {

    /**
     * Tickera specific sanitization.
     *
     * @param $value
     * @param bool $allow_html      'Allow html elements within an array'
     * @param bool $deep            'Recursively sanitize multidimensional array'
     * @param bool $strict          'Strictly sanitize array based on Tickera specific filters.'
     *
     * @return array|bool|float|int|mixed|string|void
     * @throws Exception
     */
    function tickera_sanitize_array( $value, $allow_html = false, $deep = false, $strict = false ) {

        if ( ! $value ) {
            return ! is_array( $value ) ? $value : [];
        }

        switch( gettype( $value ) ) {

            case 'integer':
                return (int) $value;
                break;

            case 'double':
                return (float) $value;
                break;

            case 'boolean':
                return (boolean) $value;
                break;

            case 'string':
            case 'object':
            case 'array':

                if ( ! is_array( $value ) ) {
                    $value = maybe_unserialize( $value );
                    $value = json_encode( $value );
                    $value = json_decode( $value, true );
                }

                if ( is_array( $value ) ) {

                    if ( $deep ) {

                        if ( $allow_html ) {
                            return ( $strict ) ? tickera_map_deep( $value, [ new \Tickera\TC_Kses( false ), 'callback' ] ) : tickera_map_deep( $value, 'wp_kses_post' );

                        } else {
                            return tickera_map_deep( $value, 'sanitize_text_field' );
                        }

                    } else {

                        if ( $allow_html ) {
                            return ( $strict ) ? array_map( [ new \Tickera\TC_Kses( false ), 'callback' ], $value ) : array_map( 'wp_kses_post', $value );

                        } else {
                            return array_map( 'sanitize_text_field', $value );
                        }
                    }

                } else {
                    
                    if ( $allow_html ) {
                        return ( $strict ) ? wp_kses( $value, wp_kses_allowed_html( 'tickera' ) ) : wp_kses_post( $value );

                    } else {
                        return sanitize_text_field( $value );
                    }
                }
                break;

            default:
                throw new \Exception( __( 'Invalid data type passed on tickera_sanitize_array function.', 'tickera-event-ticketing-system' ) );
                exit;
        }
    }
}

if ( ! function_exists( 'tickera_map_deep' ) ) {

    /**
     * Maps a function to all non-iterable elements of an array or an object.
     * In reference to `map_deep` but filters out __PHP_Incomplete_Class_Name.
     *
     * This is similar to `array_walk_recursive()` but acts upon objects too.
     *
     * @param mixed    $value    The array, object, or scalar.
     * @param callable $callback The function to map onto $value.
     * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
     */
    function tickera_map_deep( $value, $callback ) {

        if ( is_array( $value ) ) {

            foreach ( $value as $index => $item ) {
                $value[ $index ] = tickera_map_deep( $item, $callback );
            }

        } elseif ( is_object( $value )  ) {

            if ( $value instanceof __PHP_Incomplete_Class ) {
                return;
            }

            $object_vars = get_object_vars( $value );
            foreach ( $object_vars as $property_name => $property_value ) {
                $value->$property_name = tickera_map_deep( $property_value, $callback );
            }

        } else {
            $value = call_user_func( $callback, $value );
        }

        return $value;
    }
}

/**
 * Additional allowed style properties in wp_kses
 * As default, Wordpress check style attributes against a list of safe/allowed properties.
 */
add_filter( 'safe_style_css', 'tickera_modify_safe_css_styles' );
if ( ! function_exists( 'tickera_modify_safe_css_styles' ) ) {

    function tickera_modify_safe_css_styles( $styles ) {
        $styles[] = 'display';
        return $styles;
    }
}

/**
 * Deprecated function "tc_discount_codes_admin".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_discount_codes_admin' ) ) {

    function tickera_discount_codes_admin() {
        global $tc;
        require_once( $tc->plugin_dir . "includes/admin-pages/discount_codes.php" );
    }
}

/**
 * Deprecated function "tc_orders_admin".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_orders_admin' ) ) {

    function tickera_orders_admin() {
        global $tc;
        require_once( $tc->plugin_dir . "includes/admin-pages/orders.php" );
    }
}

/**
 * Deprecated function "tc_attendees_admin".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_attendees_admin' ) ) {

    function tickera_attendees_admin() {
        global $tc;
        require_once( $tc->plugin_dir . "includes/admin-pages/attendees.php" );
    }
}

/**
 * Deprecated function "tc_ticket_templates_admin".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_ticket_templates_admin' ) ) {

    function tickera_ticket_templates_admin() {
        global $tc;
        if ( defined( 'TC_DEV' ) ) {
            require_once( $tc->plugin_dir . "includes/admin-pages/ticket_templates_new.php" );

        } else {
            require_once( $tc->plugin_dir . "includes/admin-pages/ticket_templates.php" );
        }
    }
}

/**
 * Deprecated function "tc_settings_admin".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_settings_admin' ) ) {

    function tickera_settings_admin() {
        global $tc;
        require_once( $tc->plugin_dir . "includes/admin-pages/settings.php" );
    }
}

/**
 * Deprecated function "tc_addons_admin".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_addons_admin' ) ) {

    function tickera_addons_admin() {
        global $tc;
        require_once( $tc->plugin_dir . "includes/admin-pages/addons.php" );
    }
}

/**
 * Deprecated function "tc_network_settings_admin".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_network_settings_admin' ) ) {

    function tickera_network_settings_admin() {
        global $tc;
        require_once( $tc->plugin_dir . "includes/network-admin-pages/network_settings.php" );
    }
}

/**
 * Internal cache functions
 * @param $key
 * @param $value
 * @param int $ttl
 *
 * Deprecated function "tc_cache_set".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cache_set' ) ) {

    function tickera_cache_set( $key, $value, $ttl = 3600 ) {
        set_transient( $key, $value, $ttl );
    }
}

/**
 * Deprecated function "tc_cache_get".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cache_get' ) ) {

    function tickera_cache_get( $key ) {
        return get_transient( $key );
    }
}

/**
 * Deprecated function "tc_cache_delete".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cache_delete' ) ) {

    function tickera_cache_delete( $key ) {
        delete_transient( $key );
    }
}

/**
 * Deprecated function "tc_the_content".
 * @since 3.5.3.0
 */
add_filter( 'tc_the_content', 'tickera_the_content' );
if ( ! function_exists( 'tickera_the_content' ) ) {

    function tickera_the_content( $content ) {
        return wpautop( $content );
    }
}

/**
 * Deprecated function "tc_tooltip".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_tooltip' ) ) {

    function tickera_tooltip( $content ) {

        if ( ! empty( $content ) ) {
            return '<a title="' . esc_attr( htmlentities( $content ) ) . '" class="tc_tooltip"><span class="dashicons dashicons-editor-help"></span></a>';
        }
    }
}

/**
 * Handles server side redirection
 * @param $url
 * @param bool $force client side redirection as a fallback function
 * @param bool $exit
 * @return void
 * @since 3.5.1.6
 *
 * Deprecated function "tc_redirect".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_redirect' ) ) {

    function tickera_redirect( $url, $force = false, $exit = true ) {

        /**
         * Don't execute redirection if request is coming from REST API.
         * @since 3.5.1.7
         */
        if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        if ( ! $url ) {
            return;
        }

        if ( apply_filters( 'tc_bypass_redirection', false ) ) {
            return;
        }

        ob_start();
        @wp_redirect( $url );

        if ( $force ) {
            tickera_js_redirect( $url, false );
        }

        if ( $exit ) {
            exit;
        }
    }
}

/**
 * Handles client side redirection
 * @param $url
 * @param bool $buffer
 * @return bool
 *
 * Deprecated function "tc_js_redirect".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_js_redirect' ) ) {

    function tickera_js_redirect( $url, $buffer = true ) {

        if ( $buffer ) {
            ob_start();
        }

        if ( apply_filters( 'tc_bypass_redirection', false ) ) {
            return false;
        }
        ?>
        <script type="text/javascript">
            window.location = "<?php echo esc_url( $url ); ?>";
        </script>
        <?php
    }
}

/**
 * Deprecated function "tc_get_ticket_price".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_price' ) ) {

    function tickera_get_ticket_price( $id ) {
        $price_per_ticket = get_post_meta( $id, 'price_per_ticket', true );
        return (float) apply_filters( 'tc_price_per_ticket', $price_per_ticket, $id );
    }
}

/**
 * Deprecated function "tc_unistr_to_ords".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_unistr_to_ords' ) ) {

    function tickera_unistr_to_ords( $str, $encoding = 'UTF-8' ) {

        /*
        * Turns a string of unicode characters into an array of ordinal values,
        * Even if some of those characters are multibyte.
        */
        $str = mb_convert_encoding( $str, "UCS-4BE", $encoding );
        $ords = [];

        /*
        * Visit each unicode character
        * Result: Now we have 4 bytes. Find their total
        * Type: numeric value.
        */
        for ( $i = 0; $i < mb_strlen( $str, "UCS-4BE" ); $i++ ) {
            $s2 = mb_substr( $str, $i, 1, "UCS-4BE" );
            $val = unpack( "N", $s2 );
            $ords[] = $val[ 1 ];
        }

        return ( $ords );
    }
}

/**
 * Format date.
 * Ex. February 18, 2020 -1:00 pm
 *
 * Deprecated function "tc_format_date".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_format_date' ) ) {

    function tickera_format_date( $timestamp, $date_only = false ) {

        $format = get_option( 'date_format' );
        if ( ! $date_only ) {
            $format .= ' - ' . get_option( 'time_format' );
        }

        $date = get_date_from_gmt( date( 'Y-m-d H:i:s', (int) $timestamp ) );
        return date_i18n( $format, strtotime( $date ) );
    }
}

/**
 * Render Ticket Type Quantity Selector
 * @param $ticket_id
 * @param $value
 * @param bool $return
 * @return false|string
 *
 * Deprecated function "tc_quantity_selector".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_quantity_selector' ) ) {

    function tickera_quantity_selector( $ticket_id, $return = false, $value = false ) {
        $quantity = (int) apply_filters( 'tc_quantity_selector_quantity', 25 );
        $ticket = new \Tickera\TC_Ticket( $ticket_id );
        $quantity_left = $ticket->get_tickets_quantity_left();
        $max_quantity = get_post_meta( $ticket_id, 'max_tickets_per_order', true );

        $quantity = ( isset( $max_quantity ) && is_numeric( $max_quantity ) ) ? $max_quantity : $quantity;
        $quantity = ( $quantity_left <= $quantity ) ? $quantity_left : $quantity;

        $min_quantity = get_post_meta( $ticket_id, 'min_tickets_per_order', true );
        $i_val = ( isset( $min_quantity ) && is_numeric( $min_quantity ) && $min_quantity <= $quantity ) ? $min_quantity : 1;

        if ( $quantity_left > 0 ) {
            if ( $return ) ob_start(); ?>
            <select class="tc_quantity_selector">
            <?php for ( $i = $i_val; $i <= $quantity; $i++ ) { ?>
                <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $value, (int) $i, true ); ?>><?php echo esc_html( $i ); ?></option>
            <?php } ?>
            <?php if ( $value > $quantity ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php echo esc_html( 'selected' ) ?>><?php echo esc_html( $value ); ?></option>
            <?php endif; ?>
            </select><?php

            if ( $return ) { return ob_get_clean(); }
        }
    }
}

/**
 * Deprecated function "tc_is_tax_inclusive".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_is_tax_inclusive' ) ) {

    function tickera_is_tax_inclusive() {
        $tc_general_settings = get_option( 'tickera_general_setting', false );
        return ( isset( $tc_general_settings[ 'tax_inclusive' ] ) && 'yes' == $tc_general_settings[ 'tax_inclusive' ] ) ? true : false;;
    }
}

/**
 * Deprecated function "tc_get_tickets_user_purchased_count".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_tickets_user_purchased_count' ) ) {

    function tickera_get_tickets_user_purchased_count( $user_id, $ticket_type_id = null ) {

        global $wpdb;

        if ( ! $user_id )
            return false;

        $order_statuses = [ 'order_paid', 'order_received', 'order_fraud' ];
        $prepare_placeholder = implode( ",", array_fill( 0, count( $order_statuses ), '%s' ) );
        $prepare_arguments = array_merge( [ (int) $user_id ], $order_statuses );

        $query = $wpdb->prepare( "SELECT `post_content` FROM {$wpdb->posts} WHERE `post_type` = 'tc_orders' AND `post_author`=%d AND `post_status` IN ($prepare_placeholder) AND `post_content` <> ''", $prepare_arguments );
        $user_purchase = $wpdb->get_results( $query );

        $sold_count = 0;

        foreach ( $user_purchase as $key => $val ) {
            $post_content = maybe_unserialize( $val->post_content );
            $quanity = ( $ticket_type_id ) ? ( isset( $post_content[ $ticket_type_id ] ) ) ? $post_content[ $ticket_type_id ] : 0 : array_sum( $post_content );
            $sold_count = $sold_count + $quanity;
        }

        return $sold_count;
    }
}

/**
 * Calculate the total number of quantity left of a ticket.
 * @param $ticket_id
 * @return float|int|string
 *
 * Deprecated function "tc_get_tickets_count_left".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_tickets_count_left' ) ) {

    function tickera_get_tickets_count_left( $ticket_id ) {

        $global_quantity_available = 0;
        $unlimited = false;

        $quantity_available = get_post_meta( $ticket_id, 'quantity_available', true );

        if ( is_numeric( $quantity_available ) ) {
            $global_quantity_available = $global_quantity_available + $quantity_available;

        } else {
            $unlimited = true;
        }

        if ( $unlimited ) {
            return '∞';

        } else {
            $quantity_sold = tickera_get_tickets_count_sold( $ticket_id );
            return ( $global_quantity_available > $quantity_sold ) ? abs( $global_quantity_available - $quantity_sold ) : 0;
        }
    }
}

/**
 * Get the total number of ticket purchased
 * @param $ticket_ids
 * @return int
 *
 * Deprecated function "tc_get_tickets_count_sold".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_tickets_count_sold' ) ) {

    function tickera_get_tickets_count_sold( $ticket_ids ) {

        global $wpdb;

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $removed_cancelled_orders_from_stock = isset( $tc_general_settings[ 'removed_cancelled_orders_from_stock' ] ) ? $tc_general_settings[ 'removed_cancelled_orders_from_stock' ] : 'yes';

        // $return_cancelled_orders_in_stock
        $skip_statuses = ( 'yes' == $removed_cancelled_orders_from_stock ) ? [ 'trash', 'draft', 'order_cancelled', 'order_refunded', 'order_fraud' ] : [ 'trash', 'draft' ];

        if ( is_array( $ticket_ids ) ) {
            $prepare_placeholder = implode( ',', array_fill( 0, count( $ticket_ids ), '%d' ) );
            $query = $wpdb->prepare( "SELECT COUNT(*) as cnt, p.post_parent FROM {$wpdb->posts} p, {$wpdb->postmeta} pm WHERE p.ID = pm.post_id AND p.post_type = 'tc_tickets_instances' AND p.post_status = 'publish' AND pm.meta_key = 'ticket_type_id' AND pm.meta_value IN ($prepare_placeholder) GROUP BY p.post_parent", $ticket_ids );

        } else {
            $query = $wpdb->prepare( "SELECT COUNT(*) as cnt, p.post_parent FROM {$wpdb->posts} p, {$wpdb->postmeta} pm WHERE p.ID = pm.post_id AND p.post_type = 'tc_tickets_instances' AND p.post_status = 'publish' AND pm.meta_key = 'ticket_type_id' AND pm.meta_value = %1s GROUP BY p.post_parent", (int) $ticket_ids );
        }

        $sold_records = $wpdb->get_results( $query );

        $sold_count = 0;
        foreach ( $sold_records as $sold_record ) {
            $order_status = get_post_status( $sold_record->post_parent );

            if ( ! in_array( $order_status, $skip_statuses ) ) {
                $sold_count += $sold_record->cnt;
            }
        }

        return $sold_count;
    }
}

/**
 * Calculate the total number of quantity left of an event.
 * @param $event_id
 * @return float|int|string
 *
 * Deprecated function "tc_get_event_tickets_count_left".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_event_tickets_count_left' ) ) {

    function tickera_get_event_tickets_count_left( $event_id ) {

        $event_obj = get_post_meta( $event_id );
        $limit_level = isset( $event_obj[ 'limit_level' ] ) ? $event_obj[ 'limit_level' ][ 0 ] : 0;

        if ( $limit_level ) {

            $max_limit_value = ''; // Unlimited as default
            if ( isset( $event_obj[ 'limit_level_value' ] ) && '' != $event_obj[ 'limit_level_value' ][0] ) {
                $max_limit_value = (int) $event_obj[ 'limit_level_value' ][0];
            }

            $event_ticket_sold_count = (int) tickera_get_event_tickets_count_sold( $event_id );

            if ( '' !== $max_limit_value ) {
                return ( $max_limit_value > $event_ticket_sold_count ) ? abs( $max_limit_value - $event_ticket_sold_count ) : 0;

            } else {
                // Unlimited
                return '∞';
            }

        } else {

            $event = new \Tickera\TC_Event( $event_id );
            $ticket_types = $event->get_event_ticket_types();

            $global_quantity_available = 0;
            $unlimited = false;

            foreach ( $ticket_types as $ticket_type_id ) {

                $ticket_count_left = tickera_get_tickets_count_left( $ticket_type_id );

                if ( is_numeric( $ticket_count_left ) ) {
                    $global_quantity_available = $global_quantity_available + $ticket_count_left;

                } else {
                    $unlimited = true;
                    break;
                }
            }

            return ( ! $unlimited ) ? $global_quantity_available : '∞';
        }
    }
}

/**
 * Count the number of ticket purchases based on event id
 * @param $event_id
 * @return int
 *
 * Deprecated function "tc_get_event_tickets_count_sold".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_event_tickets_count_sold' ) ) {

    function tickera_get_event_tickets_count_sold( $event_id ) {
        $event = new \Tickera\TC_Event( $event_id );
        $ticket_types = $event->get_event_ticket_types();
        return ( $ticket_types ) ? tickera_get_tickets_count_sold( $ticket_types ) : 0;
    }
}

/**
 * Deprecated function "tc_get_payment_page_slug".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_payment_page_slug' ) ) {

    function tickera_get_payment_page_slug() {
        $page_id = get_option( 'tickera_payment_page_id', false );
        $page = get_post( $page_id, OBJECT );
        return isset( $page->post_name ) ? isset( $page->post_name ) : null;
    }
}

/**
 * Deprecated function "tc_create_page".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_create_page' ) ) {

    function tickera_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {

        global $wpdb;
        $option_value = get_option( sanitize_key( $option ) );

        if ( $option_value > 0 && get_post( $option_value ) ) {
            return -1;
        }

        $page_found = null;

        $page_found = ( strlen( $page_content ) > 0 )
            ? $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_type='page' AND post_content LIKE '%%%s%%' LIMIT 1;", $page_content ) ) // Search for an existing page with the specified page content ( typically a shortcode )
            : $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_type='page' AND post_name = %s LIMIT 1;", $slug ) ); // Search for an existing page with the specified page slug

        $page_found = (int) apply_filters( 'tc_create_page_id', $page_found, $slug, $page_content );

        if ( $page_found ) {

            if ( ! $option_value ) {
                update_option( sanitize_key( $option ), (int) $page_found );
            }

            return $page_found;
        }

        $args = [
            'post_author'       => get_current_user_id(),
            'post_status'       => 'publish',
            'post_type'         => 'page',
            'post_author'       => 1,
            'post_name'         => $slug,
            'post_title'        => $page_title,
            'post_content'      => $page_content,
            'post_parent'       => (int) $post_parent,
            'comment_status'    => 'closed'
        ];

        $page_id = wp_insert_post( tickera_sanitize_array( $args, true ) );

        if ( $option ) {
            update_option( sanitize_key( $option ), (int) $page_id );
        }

        return $page_id;
    }
}

/**
 * Deprecated function "tc_get_events_and_tickets_shortcode_select_box".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_events_and_tickets_shortcode_select_box' ) ) {

    function tickera_get_events_and_tickets_shortcode_select_box() { ?>
        <select name="tc_events_tickets_shortcode_select" class="tc_events_tickets_shortcode_select">
            <?php
            $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 );
            foreach ( $wp_events_search->get_results() as $event ) {
                $event_obj = new \Tickera\TC_Event( $event->ID );
                $ticket_types = $event_obj->get_event_ticket_types(); ?>
                <option class="option_event" value="<?php echo esc_attr( (int) $event_obj->details->ID ); ?>"><?php echo esc_html( $event_obj->details->post_title ); ?></option>
                <?php
                foreach ( $ticket_types as $ticket_type ) {
                    $ticket_type_obj = new \Tickera\TC_Ticket( $ticket_type );
                    ?>
                    <option class="option_ticket" value="<?php echo esc_attr( (int) $ticket_type_obj->details->ID ); ?>"><?php echo esc_html( $ticket_type_obj->details->post_title ); ?></option>
                    <?php
                }
            } ?>
        </select>
        <?php
    }
}

add_action( 'tc_order_created', 'tickera_order_created_email', 10, 5 );

/**
 * Bridge for Woocommerce Hook
 * @since 1.4.4
 *
 * Replace "tc_wb_allowed_tickets_access" with "tc_wb_maybe_send_attendee_order_completed_email".
 * @since 3.5.1.7
 *
 * Deprecated function "tc_maybe_send_order_paid_attendee_email".
 * @since 3.5.3.0
 */
add_action( 'tc_wb_maybe_send_attendee_order_completed_email', 'tickera_maybe_send_order_paid_attendee_email' );
if ( ! function_exists( 'tickera_maybe_send_order_paid_attendee_email' ) ) {

    function tickera_maybe_send_order_paid_attendee_email( $wc_order ) {
        $order_id = $wc_order->get_id();
        tickera_order_paid_attendee_email( $order_id );
    }
}

/**
 * Deprecated function "client_email_from_name".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_client_email_from_name' ) ) {

    function tickera_client_email_from_name() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'client_order_from_name' ] ) && $tc_email_settings[ 'client_order_from_name' ] ) ? $tc_email_settings[ 'client_order_from_name' ] : get_option( 'blogname' );
    }
}

/**
 * Deprecated function "client_email_from_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_client_email_from_email' ) ) {

    function tickera_client_email_from_email() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'client_order_from_email' ] ) && $tc_email_settings[ 'client_order_from_email' ] ) ? $tc_email_settings[ 'client_order_from_email' ] : get_option( 'admin_email' );
    }
}

/**
 * Deprecated function "attendee_email_from_name".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_attendee_email_from_name' ) ) {

    function tickera_attendee_email_from_name() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'attendee_order_from_name' ] ) && $tc_email_settings[ 'attendee_order_from_name' ] ) ? $tc_email_settings[ 'attendee_order_from_name' ] : get_option( 'blogname' );
    }
}

/**
 * Deprecated function "attendee_email_from_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_attendee_email_from_email' ) ) {

    function tickera_attendee_email_from_email() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'attendee_order_from_email' ] ) && $tc_email_settings[ 'attendee_order_from_email' ] ) ? $tc_email_settings[ 'attendee_order_from_email' ] : get_option( 'admin_email' );
    }
}

/**
 * Deprecated function "client_email_from_placed_name".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_client_email_from_placed_name' ) ) {

    function tickera_client_email_from_placed_name() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'client_order_from_placed_name' ] ) && $tc_email_settings[ 'client_order_from_placed_name' ] ) ? $tc_email_settings[ 'client_order_from_placed_name' ] : get_option( 'blogname' );
    }
}

/**
 * Deprecated function "client_email_from_placed_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_client_email_from_placed_email' ) ) {

    function tickera_client_email_from_placed_email() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'client_order_from_placed_email' ] ) && $tc_email_settings[ 'client_order_from_placed_email' ] ) ? $tc_email_settings[ 'client_order_from_placed_email' ] : get_option( 'admin_email' );
    }
}

/**
 * Deprecated function "admin_email_from_name".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_admin_email_from_name' ) ) {

    function tickera_admin_email_from_name() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'admin_order_from_name' ] ) && $tc_email_settings[ 'admin_order_from_name' ] ) ? $tc_email_settings[ 'admin_order_from_name' ] : get_option( 'blogname' );
    }
}

/**
 * Deprecated function "admin_email_from_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_admin_email_from_email' ) ) {

    function tickera_admin_email_from_email() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'admin_order_from_email' ] ) && $tc_email_settings[ 'admin_order_from_email' ] ) ? $tc_email_settings[ 'admin_order_from_email' ] : get_option( 'admin_email' );
    }
}

/**
 * Deprecated function "admin_email_from_placed_name".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_admin_email_from_placed_name' ) ) {

    function tickera_admin_email_from_placed_name() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'admin_order_placed_from_name' ] ) && $tc_email_settings[ 'admin_order_placed_from_name' ] ) ? $tc_email_settings[ 'admin_order_placed_from_name' ] : get_option( 'blogname' );
    }
}

/**
 * Deprecated function "admin_email_from_placed_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_admin_email_from_placed_email' ) ) {

    function tickera_admin_email_from_placed_email() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'admin_order_placed_from_email' ] ) && $tc_email_settings[ 'admin_order_placed_from_email' ] ) ? $tc_email_settings[ 'admin_order_placed_from_email' ] : get_option( 'admin_email' );
    }
}

/**
 * Deprecated function "admin_email_from_refunded_name".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_admin_email_from_refunded_name' ) ) {

    function tickera_admin_email_from_refunded_name() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'admin_order_refunded_from_name' ] ) && $tc_email_settings[ 'admin_order_refunded_from_name' ] ) ? $tc_email_settings[ 'admin_order_refunded_from_name' ] : get_option( 'blogname' );
    }
}

/**
 * Deprecated function "admin_email_from_refunded_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_admin_email_from_refunded_email' ) ) {

    function tickera_admin_email_from_refunded_email() {
        $tc_email_settings = get_option( 'tickera_email_setting', false );
        return ( isset( $tc_email_settings[ 'admin_order_refunded_from_email' ] ) && $tc_email_settings[ 'admin_order_refunded_from_email' ] ) ? $tc_email_settings[ 'admin_order_refunded_from_email' ] : get_option( 'admin_email' );
    }
}

/**
 * Deprecated function "insert_string_attachment".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_email_insert_string_attachment' ) ) {

    function tickera_email_insert_string_attachment( $phpmailer ) {

        // The default attachment will fail since it's not an actual file.
        if ( '' !== $phpmailer->ErrorInfo && isset( $_POST[ 'ticket_instance_id' ] ) ) {

            $error_info = $phpmailer->ErrorInfo;
            $translations = $phpmailer->getTranslations();

            // See if there was an error while processing an email attachment
            if ( false !== stripos( $error_info, $translations[ 'file_access' ] ) ) {

                // Remove default error messages
                $attachment_string = str_replace( $translations[ 'file_access' ], '', $error_info );

                // The result will be the json encoded string that was attached as default
                $attachment = ( is_object( $attachment_string ) ) ? json_decode( $attachment_string ) : $attachment_string;

                if ( isset( $attachment ) && $attachment ) {

                    // Retrieve Ticket Instance
                    $ticket_instance_id = (int) $_POST[ 'ticket_instance_id' ];
                    $ticket_instance = new \Tickera\TC_Ticket_Instance( $ticket_instance_id );
                    $ticket_code = apply_filters( 'tc_pdf_ticket_name', $ticket_instance->details->ticket_code, $ticket_instance );

                    // Retrieve filename via post
                    $file_name = $ticket_code ? $ticket_code . '.pdf' : 'ticket.pdf';
                    unset( $_POST[ 'ticket_instance_id' ] );

                    try {
                        // Insert String as Attachment
                        $phpmailer->AddStringAttachment( $attachment_string, $file_name, 'base64', 'application/pdf' );
                    } catch ( phpmailerException $e ) {}
                }
            }
        }
    }
}

/**
 * Send email on paid orders
 * @param $order_id
 *
 * Deprecated function "tc_order_paid_attendee_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_order_paid_attendee_email' ) ) {

    function tickera_order_paid_attendee_email( $order_id ) {
        global $tc;

        $tc_email_settings = get_option( 'tickera_email_setting', false );
        $email_send_type = isset( $tc_email_settings[ 'email_send_type' ] ) ? $tc_email_settings[ 'email_send_type' ] : 'wp_mail';

        if ( isset( $tc_email_settings[ 'attendee_send_message' ] ) && 'yes' == $tc_email_settings[ 'attendee_send_message' ] ) {

            add_filter( 'wp_mail_content_type', function( $content_type ) {
                return 'text/html';
            } );

            add_filter( 'wp_mail_from', 'tickera_attendee_email_from_email', 999 );
            add_filter( 'wp_mail_from_name', 'tickera_attendee_email_from_name', 999 );

            $subject = isset( $tc_email_settings[ 'attendee_order_subject' ] ) ? $tc_email_settings[ 'attendee_order_subject' ] : __( 'Your Ticket is here!', 'tickera-event-ticketing-system' );
            $default_message = __( 'Hello, <br /><br />You can download ticket for EVENT_NAME here DOWNLOAD_URL', 'tickera-event-ticketing-system' );
            $order = new \Tickera\TC_Order( $order_id );

            $tc_attendee_order_message = isset( $tc_email_settings[ 'attendee_order_message' ] ) ? $tc_email_settings[ 'attendee_order_message' ] : '';
            $tc_attendee_order_message = apply_filters( 'tc_attendee_order_message', $tc_attendee_order_message, $order );

            $attendee_headers = '';
            $order_attendees = \Tickera\TC_Orders::get_tickets_ids( $order->details->ID );

            foreach ( $order_attendees as $order_attendee_id ) {

                $subject = apply_filters( 'tc_attendee_order_completed_email_subject', $subject, $order_attendee_id );
                $ticket_meta = get_post_meta( $order_attendee_id );
                $ticket_type_id = isset( $ticket_meta[ 'ticket_type_id' ] ) ? reset( $ticket_meta[ 'ticket_type_id' ] ) : '';
                $ticket_type_name = get_the_title( $ticket_type_id );

                $ticket_code = isset( $ticket_meta[ 'ticket_code' ] ) ? reset( $ticket_meta[ 'ticket_code' ] ) : '';
                $ticket_code = strtoupper( $ticket_code );

                $event_id = isset( $ticket_meta[ 'event_id' ] ) ? reset( $ticket_meta[ 'event_id' ] ) : '';
                $event_id = (int) $event_id;

                $first_name = isset( $ticket_meta[ 'first_name' ] ) ? reset( $ticket_meta[ 'first_name' ] ) : '';
                $last_name = isset( $ticket_meta[ 'last_name' ] ) ? reset( $ticket_meta[ 'last_name' ] ) : '';
                $owner_email = isset( $ticket_meta[ 'owner_email' ] ) ? reset( $ticket_meta[ 'owner_email' ] ) : '';

                $event = new \Tickera\TC_Event( $event_id );
                $event_location = get_post_meta( $event_id, 'event_location', true );

                $message = isset( $tc_attendee_order_message ) ? $tc_attendee_order_message : $default_message;
                $placeholders = array( 'EVENT_NAME', 'DOWNLOAD_LINK', 'DOWNLOAD_URL', 'TICKET_TYPE', 'TICKET_CODE','FIRST_NAME', 'LAST_NAME', 'EVENT_LOCATION' );
                $placeholder_values = array( $event->details->post_title, tickera_get_ticket_download_link( '', '', $order_attendee_id, true ), tickera_get_raw_ticket_download_link( '', '', $order_attendee_id, true ), $ticket_type_name, $ticket_code,$first_name, $last_name, $event_location );

                if ( ! empty( $owner_email ) ) {

                    // Generate pdf file
                    $templates = new \Tickera\TC_Ticket_Templates();
                    $enabled_attachment = ( isset( $tc_email_settings[ 'attendee_attach_ticket' ] ) && 'yes' == $tc_email_settings[ 'attendee_attach_ticket' ] ) ? true : false;
                    $content = ( $enabled_attachment ) ? $templates->generate_preview( $order_attendee_id, false, false, false, $enabled_attachment ) : '';

                    $placeholders = apply_filters( 'tc_order_completed_attendee_email_placeholders', $placeholders );
                    $placeholder_values = apply_filters( 'tc_order_completed_attendee_email_placeholder_values', $placeholder_values, $order_attendee_id, $order_id );
                    $message = str_replace( $placeholders, $placeholder_values, $message );

                    if ( $email_send_type == 'wp_mail' ) {

                        $attachment = array( $content );
                        $_POST[ 'ticket_instance_id' ] = $order_attendee_id;

                        // Override PHPMailer addAttachment method if attachment is not a physical file
                        add_action( 'phpmailer_init', 'tickera_email_insert_string_attachment' );

                        $message = apply_filters( 'tc_order_completed_attendee_email_message', wpautop( $message ) );
                        $attendee_headers = apply_filters( 'tc_order_completed_attendee_email_headers', $attendee_headers );

                        @wp_mail( sanitize_email( $owner_email ), sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( stripcslashes( wpautop ( $message ) ) ), $attendee_headers, $attachment );

                    } else {

                        // Boundary
                        $semi_rand = md5( time() );
                        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

                        // Header for sender info
                        $headers = "From: " . tickera_attendee_email_from_email() . " <" . tickera_attendee_email_from_email() . ">\n" .
                            'Reply-To: ' . tickera_attendee_email_from_email() . "\n" .
                            'X-Mailer: PHP/' . phpversion();

                        // Headers for attachment
                        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;" . " boundary=\"{$mime_boundary}\"";

                        // Multipart boundary
                        $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
                            "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";

                        // Attachment Content
                        $message .= "--{$mime_boundary}\n";
                        $message .= "Content-Type: application/octet-stream; name=\"" . $ticket_code . '.pdf' . "\"\n" .
                            "Content-Description: " . $ticket_code . '.pdf' . "\n" .
                            "Content-Disposition: attachment;" . " filename=\"" . $ticket_code . '.pdf' . "\";\n" .
                            "Content-Transfer-Encoding: base64;\n\n" . base64_encode( $content ) . "\n\n";
                        $message .= "--{$mime_boundary}--";

                        @mail( sanitize_email( $owner_email ), sanitize_text_field( stripslashes( $subject ) ), stripcslashes( wpautop( $message ) ), $headers );
                    }
                }
            }
        }
    }
}

/**
 * Send Email on Success Checkout
 *
 * @param $order_id
 * @param $status
 * @param bool $cart_contents
 * @param bool $cart_info
 * @param bool $payment_info
 * @param bool $send_email_to_admin
 *
 * Deprecated function "tc_order_created_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_order_created_email' ) ) {

    function tickera_order_created_email( $order_id, $status, $cart_contents = false, $cart_info = false, $payment_info = false, $send_email_to_admin = true ) {

        global $tc;

        $tc_email_settings = get_option( 'tickera_email_setting', false );
        $email_send_type = isset( $tc_email_settings[ 'email_send_type' ] ) ? $tc_email_settings[ 'email_send_type' ] : 'wp_mail';
        $order_id = strtoupper( $order_id );
        $order = tickera_get_order_id_by_name( $order_id );

        if ( $cart_contents === false ) {
            $cart_contents = get_post_meta( $order->ID, 'tc_cart_contents', true );
        }

        if ( $cart_info === false ) {
            $cart_info = get_post_meta( $order->ID, 'tc_cart_info', true );
        }

        $buyer_data = $cart_info[ 'buyer_data' ];
        $buyer_name = $buyer_data[ 'first_name_post_meta' ] . ' ' . $buyer_data[ 'last_name_post_meta' ];

        if ( $payment_info === false ) {
            $payment_info = get_post_meta( $order->ID, 'tc_payment_info', true );
        }

        add_filter( 'wp_mail_content_type', function( $content_type ) {
            return 'text/html';
        } );

        do_action( 'tc_before_order_created_email', $order_id, $status, $cart_contents, $cart_info, $payment_info, $send_email_to_admin );

        if ( 'order_paid' == $status ) {

            /**
             * Send e-mail to the client
             */
            if ( ! isset( $tc_email_settings[ 'client_send_message' ] ) || ( isset( $tc_email_settings[ 'client_send_message' ] ) && 'yes' == $tc_email_settings[ 'client_send_message' ] ) ) {

                add_filter( 'wp_mail_from', 'tickera_client_email_from_email', 999 );
                add_filter( 'wp_mail_from_name', 'tickera_client_email_from_name', 999 );

                $subject = isset( $tc_email_settings[ 'client_order_subject' ] ) ? $tc_email_settings[ 'client_order_subject' ] : __( 'Order Completed', 'tickera-event-ticketing-system' );
                $default_message = __( 'Hello, <br /><br />Your order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> is completed. <br /><br />You can download your tickets here: DOWNLOAD_URL', 'tickera-event-ticketing-system' );
                $order = new \Tickera\TC_Order( $order->ID );
                $order_status_url = $tc->tc_order_status_url( $order, $order->details->tc_order_date, '', false );
                $tc_client_order_message = isset( $tc_email_settings[ 'client_order_message' ] ) ? $tc_email_settings[ 'client_order_message' ] : $default_message;
                $tc_client_order_message = apply_filters( 'tc_client_order_message', $tc_client_order_message, $order );

                // Temporary storage for event details
                $event_locations = [];
                $event_titles = [];

                $order_clients = \Tickera\TC_Orders::get_tickets_ids( $order->details->ID );
                foreach ( $order_clients as $order_client_id ) {

                    $event_id = get_post_meta( $order_client_id, 'event_id', true );
                    $event_title = get_the_title( $event_id );
                    $event_location = get_post_meta( $event_id, 'event_location', true );

                    // Store event title in temporary storage for later use
                    if ( ! in_array( $event_title, $event_titles ) )
                        $event_titles[] = $event_title;

                    // Store event location in temporary storage for later use
                    if ( ! in_array( $event_location, $event_locations ) )
                        $event_locations[] = $event_location;

                }

                $placeholders = array( 'ORDER_ID', 'ORDER_TOTAL', 'DOWNLOAD_URL', 'BUYER_NAME', 'ORDER_DETAILS', 'EVENT_NAME', 'EVENT_LOCATION' );
                $placeholder_values = array( $order_id, esc_html( apply_filters( 'tc_cart_currency_and_format', $payment_info[ 'total' ] ) ), $order_status_url, $buyer_name, tickera_get_order_details_email( $order->details->ID, $order->details->tc_order_date, true, $status ), implode( ' | ', $event_titles ), implode( ' | ', $event_locations ) );

                $to = $buyer_data[ 'email_post_meta' ];
                $message = str_replace( apply_filters( 'tc_order_completed_client_email_placeholders', $placeholders ), apply_filters( 'tc_order_completed_client_email_placeholder_values', $placeholder_values ), $tc_client_order_message );

                if ( 'wp_mail' == $email_send_type ) {
                    @wp_mail( sanitize_email( $to ), sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( apply_filters( 'tc_order_completed_admin_email_message', stripcslashes( wpautop( $message ) ) ) ), apply_filters( 'tc_order_completed_client_email_headers', '' ) );

                } else {
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= 'From: ' . tickera_client_email_from_email() . "\r\n";
                    $headers .= 'Reply-To: ' . tickera_client_email_from_email() . "\r\n";
                    $headers .= 'X-Mailer: PHP/' . phpversion();

                    @mail( sanitize_email( $to ), sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( stripcslashes( wpautop( $message ) ) ), apply_filters( 'tc_order_completed_client_email_headers', $headers ) );
                }
            }

            /**
             * Send e-mail to the attendees
             */
            tickera_order_paid_attendee_email( isset( $order->ID ) ? $order->ID : $order->details->ID );

            /**
             * Send e-mail to the admin
             */
            if ( ( ! isset( $tc_email_settings[ 'admin_send_message' ] ) || ( isset( $tc_email_settings[ 'admin_send_message' ] ) && 'yes' == $tc_email_settings[ 'admin_send_message' ] ) ) && $send_email_to_admin ) {

                add_filter( 'wp_mail_from', 'tickera_admin_email_from_email', 999 );
                add_filter( 'wp_mail_from_name', 'tickera_admin_email_from_name', 999 );

                $subject = isset( $tc_email_settings[ 'admin_order_subject' ] ) ? $tc_email_settings[ 'admin_order_subject' ] : __( 'New Order Completed', 'tickera-event-ticketing-system' );
                $default_message = __( 'Hello, <br /><br />a new order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> has been placed. <br /><br />You can check the order details here: ORDER_ADMIN_URL', 'tickera-event-ticketing-system' );
                $message = isset( $tc_email_settings[ 'admin_order_message' ] ) ? $tc_email_settings[ 'admin_order_message' ] : $default_message;

                $order = tickera_get_order_id_by_name( $order_id );
                $order = new \Tickera\TC_Order( $order->ID );

                $order_admin_url = admin_url( 'post.php?post=' . $order->details->ID . '&action=edit' );

                $placeholders = array( 'ORDER_ID', 'ORDER_TOTAL', 'ORDER_ADMIN_URL', 'BUYER_NAME', 'ORDER_DETAILS' );
                $placeholder_values = array( $order_id, esc_html( apply_filters( 'tc_cart_currency_and_format', $payment_info[ 'total' ] ) ), $order_admin_url, $buyer_name, tickera_get_order_details_email( $order->details->ID, $order->details->tc_order_date, true, $status ) );

                if ( isset( $tc_email_settings[ 'admin_order_to_email' ] )
                    && $tc_email_settings[ 'admin_order_to_email' ] ) {

                    /**
                     * @since 3.5.2.3
                     */
                    $to = $tc_email_settings[ 'admin_order_to_email' ];

                } elseif ( ! isset( $tc_email_settings[ 'admin_order_to_email' ] )
                    && isset( $tc_email_settings[ 'admin_order_from_email' ] )
                    && $tc_email_settings[ 'admin_order_from_email' ] ) {

                    /**
                     * Fallback: Value from version 3.5.2.2
                     */
                    $to = $tc_email_settings[ 'admin_order_from_email' ];

                } else {
                    $to = get_option('admin_email');
                }

                $message = str_replace( apply_filters( 'tc_order_completed_admin_email_placeholders', $placeholders ), apply_filters( 'tc_order_completed_admin_email_placeholder_values', $placeholder_values ), $message );

                // Preparing Cc:
                $ccs = explode( ',', $to );
                $temp_ccs = [];
                foreach ( $ccs as $cc ) {
                    $temp_ccs[] = sanitize_email( $cc );
                }
                $ccs = array_filter( $temp_ccs );
                $to = sanitize_email( reset( $ccs ) );
                array_shift( $ccs );

                if ( 'wp_mail' == $email_send_type ) {

                    $admin_headers = '';

                    if ( $ccs ) {
                        $admin_headers = 'Cc: ' . implode(', Cc: ', $ccs );
                        $admin_headers = explode( ', ', $admin_headers );
                    }

                    @wp_mail( $to, sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( apply_filters( 'tc_order_completed_admin_email_message', stripcslashes( wpautop( $message ) ) ) ), apply_filters( 'tc_order_completed_admin_email_headers', $admin_headers ) );

                } else {

                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= 'From: ' . tickera_admin_email_from_email() . "\r\n";
                    $headers .= 'Reply-To: ' . tickera_admin_email_from_email() . "\r\n";
                    $headers .= ( $ccs ) ? 'Cc: ' . implode( ', ', $ccs ) . "\r\n" : '';
                    $headers .= 'X-Mailer: PHP/' . phpversion();

                    @mail( $to, sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( stripcslashes( wpautop( $message ) ) ), apply_filters( 'tc_order_completed_admin_email_headers', $headers ) );
                }
            }
        }

        if ( 'order_received' == $status ) {

            /**
             * Send e-mail to the client when order is placed / pending
             */
            if ( ( isset( $tc_email_settings[ 'client_send_placed_message' ] ) && 'yes' == $tc_email_settings[ 'client_send_placed_message' ] ) ) {

                add_filter( 'wp_mail_from', 'tickera_client_email_from_placed_email', 999 );
                add_filter( 'wp_mail_from_name', 'tickera_client_email_from_placed_name', 999 );

                $subject = isset( $tc_email_settings[ 'client_order_placed_subject' ] ) ? $tc_email_settings[ 'client_order_placed_subject' ] : __( 'Order Placed', 'tickera-event-ticketing-system' );
                $default_message = __( 'Hello, <br /><br />Your order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> is placed. <br /><br />You can track your order status here: DOWNLOAD_URL', 'tickera-event-ticketing-system' );
                $message = isset( $tc_email_settings[ 'client_order_placed_message' ] ) ? $tc_email_settings[ 'client_order_placed_message' ] : $default_message;

                $order = new \Tickera\TC_Order( $order->ID );
                $order_status_url = $tc->tc_order_status_url( $order, $order->details->tc_order_date, '', false );
                $placeholders = array( 'ORDER_ID', 'ORDER_TOTAL', 'DOWNLOAD_URL', 'BUYER_NAME' );
                $placeholder_values = array( $order_id, esc_html( apply_filters( 'tc_cart_currency_and_format', $payment_info[ 'total' ] ) ), $order_status_url, $buyer_name );

                $to = $buyer_data[ 'email_post_meta' ];
                $message = str_replace( apply_filters( 'tc_order_placed_client_email_placeholders', $placeholders ), apply_filters( 'tc_order_placed_client_email_placeholder_values', $placeholder_values ), $message );

                if ( 'wp_mail' == $email_send_type ) {
                    @wp_mail( sanitize_email( $to ), sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( apply_filters( 'tc_order_placed_admin_email_message', stripcslashes( wpautop( $message ) ) ) ), apply_filters( 'tc_order_placed_client_email_headers', '' ) );

                } else {
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= 'From: ' . tickera_client_email_from_placed_email() . "\r\n";
                    $headers .= 'Reply-To: ' . tickera_client_email_from_placed_email() . "\r\n";
                    $headers .= 'X-Mailer: PHP/' . phpversion();

                    @mail( sanitize_email( $to ), sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( stripcslashes( wpautop( $message ) ) ), apply_filters( 'tc_order_placed_client_email_headers', $headers ) );
                }
            }

            /**
             * Send e-mail to the admin when order is placed / pending
             */
            if ( ( isset( $tc_email_settings[ 'admin_send_placed_message' ] ) && 'yes' == $tc_email_settings[ 'admin_send_placed_message' ] ) ) {

                add_filter( 'wp_mail_from', 'tickera_admin_email_from_placed_email', 999 );
                add_filter( 'wp_mail_from_name', 'tickera_admin_email_from_placed_name', 999 );

                $subject = isset( $tc_email_settings[ 'admin_order_placed_subject' ] ) ? $tc_email_settings[ 'admin_order_placed_subject' ] : __( 'New Order Placed', 'tickera-event-ticketing-system' );
                $default_message = __( 'Hello, <br /><br />a new order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> has been placed. <br /><br />You can check the order details here: ORDER_ADMIN_URL', 'tickera-event-ticketing-system' );
                $message = isset( $tc_email_settings[ 'admin_order_placed_message' ] ) ? $tc_email_settings[ 'admin_order_placed_message' ] : $default_message;
                $order = tickera_get_order_id_by_name( $order_id );
                $order = new \Tickera\TC_Order( $order->ID );
                $order_admin_url = admin_url( 'post.php?post=' . $order->details->ID . '&action=edit' );

                $placeholders = array( 'ORDER_ID', 'ORDER_TOTAL', 'ORDER_ADMIN_URL', 'BUYER_NAME' );
                $placeholder_values = array( $order_id, esc_html( apply_filters( 'tc_cart_currency_and_format', $payment_info[ 'total' ] ) ), $order_admin_url, $buyer_name );

                if ( isset( $tc_email_settings[ 'admin_order_placed_to_email' ] )
                    && $tc_email_settings[ 'admin_order_placed_to_email' ] ) {

                    /**
                     * @since 3.5.2.3
                     */
                    $to = $tc_email_settings[ 'admin_order_placed_to_email' ];

                } elseif ( ! isset( $tc_email_settings[ 'admin_order_placed_to_email' ] )
                    && isset( $tc_email_settings[ 'admin_order_placed_from_email' ] )
                    && $tc_email_settings[ 'admin_order_placed_from_email' ] ) {

                    /**
                     * Fallback: Value from version 3.5.2.2
                     */
                    $to = $tc_email_settings[ 'admin_order_placed_from_email' ];

                } else {
                    $to = get_option('admin_email');
                }

                $message = str_replace( apply_filters( 'tc_order_placed_admin_email_placeholders', $placeholders ), apply_filters( 'tc_order_placed_admin_email_placeholder_values', $placeholder_values ), $message );

                // Preparing Cc:
                $ccs = explode( ',', $to );
                $temp_ccs = [];
                foreach ( $ccs as $cc ) {
                    $temp_ccs[] = sanitize_email( $cc );
                }
                $ccs = array_filter( $temp_ccs );
                $to = sanitize_email( reset( $ccs ) );
                array_shift( $ccs );

                if ( 'wp_mail' == $email_send_type ) {

                    $admin_headers = '';

                    if ( $ccs ) {
                        $admin_headers = 'Cc: ' . implode(', Cc: ', $ccs );
                        $admin_headers = explode( ', ', $admin_headers );
                    }

                    @wp_mail( $to, sanitize_text_field( stripslashes( $subject ) ), apply_filters( 'tc_order_completed_admin_email_message', wp_kses_post( stripcslashes( wpautop( $message ) ) ) ), apply_filters( 'tc_order_completed_admin_email_headers', $admin_headers ) );

                } else {

                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= 'From: ' . tickera_admin_email_from_placed_email() . "\r\n";
                    $headers .= 'Reply-To: ' . tickera_admin_email_from_placed_email() . "\r\n";
                    $headers .= ( $ccs ) ? 'Cc: ' . implode( ', ', $ccs ) . "\r\n" : '';
                    $headers .= 'X-Mailer: PHP/' . phpversion();

                    @mail( $to, sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( stripcslashes( wpautop( $message ) ) ), apply_filters( 'tc_order_completed_admin_email_headers', $headers ) );
                }
            }
        }

        if ( 'order_refunded' == $status ) {

            if ( ( isset( $tc_email_settings[ 'admin_send_refunded_message' ] ) && 'yes' == $tc_email_settings[ 'admin_send_refunded_message' ] ) ) {

                add_filter( 'wp_mail_from', 'tickera_admin_email_from_refunded_email', 999 );
                add_filter( 'wp_mail_from_name', 'tickera_admin_email_from_refunded_name', 999 );

                $subject = isset( $tc_email_settings[ 'admin_order_refunded_subject' ] ) ? $tc_email_settings[ 'admin_order_refunded_subject' ] : __( 'Order Refunded', 'tickera-event-ticketing-system' );
                $default_message = __( 'Hello, <br /><br />Your order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> was refunded. <br /><br />You can track your order status here: DOWNLOAD_URL', 'tickera-event-ticketing-system' );
                $message = isset( $tc_email_settings[ 'admin_order_refunded_message' ] ) ? $tc_email_settings[ 'admin_order_refunded_message' ] : $default_message;

                $order = tickera_get_order_id_by_name( $order_id );
                $order = new \Tickera\TC_Order( $order->ID );
                $order_admin_url = admin_url( 'post.php?post=' . $order->details->ID . '&action=edit' );

                $placeholders = array( 'ORDER_ID', 'ORDER_TOTAL', 'ORDER_ADMIN_URL', 'BUYER_NAME', 'ORDER_DETAILS' );
                $placeholder_values = array( $order_id, esc_html( apply_filters( 'tc_cart_currency_and_format', $payment_info[ 'total' ] ) ), $order_admin_url, $buyer_name, tickera_get_order_details_email( $order->details->ID, $order->details->tc_order_date, true, $status ) );

                $to = ( isset( $tc_email_settings[ 'admin_order_refunded_to_email' ] ) && $tc_email_settings[ 'admin_order_refunded_to_email' ] ) ? $tc_email_settings[ 'admin_order_refunded_to_email' ] : get_option('admin_email');
                $message = str_replace( apply_filters( 'tc_order_refunded_admin_email_placeholders', $placeholders ), apply_filters( 'tc_order_refunded_admin_email_placeholder_values', $placeholder_values ), $message );

                // Preparing Cc:
                $ccs = explode( ',', $to );
                $temp_ccs = [];
                foreach ( $ccs as $cc ) {
                    $temp_ccs[] = sanitize_email( $cc );
                }
                $ccs = array_filter( $temp_ccs );
                $to = sanitize_email( reset( $ccs ) );
                array_shift( $ccs );

                if ( 'wp_mail' == $email_send_type ) {

                    $admin_headers = '';

                    if ( $ccs ) {
                        $admin_headers = 'Cc: ' . implode(', Cc: ', $ccs );
                        $admin_headers = explode( ', ', $admin_headers );
                    }

                    @wp_mail( $to, sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( apply_filters( 'tc_order_refunded_admin_email_message', stripcslashes( wpautop( $message ) ) ) ), apply_filters( 'tc_order_refunded_admin_email_headers', $admin_headers ) );

                } else {
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    $headers .= 'From: ' . tickera_admin_email_from_refunded_email() . "\r\n";
                    $headers .= 'Reply-To: ' . tickera_admin_email_from_refunded_email() . "\r\n";
                    $headers .= ( $ccs ) ? 'Cc: ' . implode( ', ', $ccs ) . "\r\n" : '';
                    $headers .= 'X-Mailer: PHP/' . phpversion();

                    @mail( $to, sanitize_text_field( stripslashes( $subject  ) ), wp_kses_post( stripcslashes( wpautop( $message ) ) ), apply_filters( 'tc_order_refunded_admin_email_headers', $headers ) );
                }
            }
        }

        do_action( 'tc_after_order_created_email', $order_id, $status, $cart_contents, $cart_info, $payment_info, $send_email_to_admin );
    }
}

/**
 * Deprecated function "tc_minimum_total".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_minimum_total' ) ) {

    function tickera_minimum_total( $total ) {
        return ( $total < 0 ) ? 0 : $total;
    }
}


/**
 * Deprecated function "tc_get_delete_pending_orders_intervals".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_delete_pending_orders_intervals' ) ) {

    function tickera_get_delete_pending_orders_intervals( $field_name, $default_value = '' ) {

        $tc_general_setting = get_option( 'tickera_general_setting', false );

        if ( isset( $tc_general_setting[ $field_name ] ) ) {
            $checked = $tc_general_setting[ $field_name ];

        } else {
            $checked = ( $default_value !== '' ) ? $default_value : '24';
        }
        ?>
        <select name="tickera_general_setting[<?php echo esc_html( $field_name ); ?>]">
            <?php
            $option_title = '';

            for ( $i = 1; $i <= 72; $i++ ) {

                $option_title = sprintf(
                /* translators: %s: A number of hours starting from 1 hour */
                    __( 'After %s hours', 'tickera-event-ticketing-system' ),
                    $i
                );

                if ( $i == 1 ) {
                    $option_title = sprintf(
                    /* translators: %s: A number of hours starting from 1 hour */
                        __( 'After %s hour', 'tickera-event-ticketing-system' ),
                        $i
                    );
                }
                ?>
                <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $checked, $i, true ); ?>><?php echo esc_html( $option_title ); ?></option>
                <?php
            }
            ?>
            <?php do_action( 'tc_get_delete_pending_orders_intervals_after' ); ?>
        </select>
        <p class="description"><?php echo wp_kses_post( __( '</br><strong>Important:</strong> Some payment gateways have long intervals of clearing payments (i.e. PayPal eCheck, Mollie) which may cause an order to be cancelled prior the payment is cleared. </br>For example, PayPal eCheck takes <strong>several working days</strong> to clear. In such cases, it is the best practice to leave this option disabled in order to avoid cancelation of the orders that were later fully paid.', 'tickera-event-ticketing-system' ) ); ?></p><?php
    }
}

/**
 * General purpose which retrieves yes/no values
 *
 * Deprecated function "tc_yes_no_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_yes_no_email' ) ) {

    function tickera_yes_no_email( $field_name, $default_value = '' ) {
        $tc_email_settings = get_option( 'tickera_email_setting', false );

        if ( isset( $tc_email_settings[ $field_name ] ) ) {
            $checked = $tc_email_settings[ $field_name ];

        } else {
            $checked = ( $default_value !== '' ) ? $default_value : 'no';
        }
        ?>
        <label>
            <input type="radio" class="<?php echo esc_attr( $field_name ); ?>" name="tickera_email_setting[<?php echo esc_attr( $field_name ); ?>]" value="yes" <?php checked( $checked, 'yes', true ); ?> /><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?>
        </label>
        <label>
            <input type="radio" class="<?php echo esc_attr( $field_name ); ?>" name="tickera_email_setting[<?php echo esc_attr( $field_name ); ?>]" value="no" <?php checked( $checked, 'no', true ); ?> /><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?>
        </label>
        <?php
    }
}

/**
 * Deprecated function "extended_radio_button".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_extended_radio_button' ) ) {

    function tickera_extended_radio_button( $field_name, $values, $value = '' ) {

        if ( ! is_array( $values ) ) {
            $values = explode( ',', $values );
        }

        $html = '';

        foreach ( $values as $key => $val ) {

            $label = spritnf(
            /* translators: %s: Label of a radio button. */
                __( '%s', 'tickera-event-ticketing-system' ),
                ucfirst( $val )
            );
            $checked = ( $val == $value ) ? 'checked="checked"' : '';
            $html .= '<label>';
            $html .= '<input type="radio" class="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value = "' . esc_attr( $val ) . '" ' . $checked . '/>' . $label;
            $html .= '</label>';
        }

        echo wp_kses_post( $html );
    }
}

/**
 * Deprecated function "tc_yes_no".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_yes_no' ) ) {

    function tickera_yes_no( $field_name, $default_value = '' ) {

        global $tc_general_settings;
        if ( isset( $tc_general_settings[ $field_name ] ) ) {
            $checked = $tc_general_settings[ $field_name ];

        } else {
            $checked = ( $default_value !== '' ) ? $default_value : 'no';
        }
        ?>
        <label>
            <input type="radio" class="<?php echo esc_attr( $field_name ); ?>" name="tickera_general_setting[<?php echo esc_attr( $field_name ); ?>]" value="yes" <?php checked( $checked, 'yes', true ); ?> /><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?>
        </label>
        <label>
            <input type="radio" class="<?php echo esc_attr( $field_name ); ?>" name="tickera_general_setting[<?php echo esc_attr( $field_name ); ?>]" value="no" <?php checked( $checked, 'no', true ); ?> /><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?>
        </label>
        <?php
    }
}

/**
 * Deprecated function "tc_get_client_order_message".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_client_order_message' ) ) {

    function tickera_get_client_order_message( $field_name, $default_value = '' ) {

        global $tc_email_settings;

        if ( isset( $tc_email_settings[ $field_name ] ) ) {
            $value = $tc_email_settings[ $field_name ];

        } else {
            $value = ( $default_value !== '' ) ? $default_value : '';
        }

        wp_editor( html_entity_decode( stripcslashes( esc_textarea( $value ) ) ), esc_attr( sanitize_key( $field_name ) ), array( 'textarea_name' => esc_attr( 'tickera_email_setting[' . $field_name . ']' ), 'textarea_rows' => 2 ) );
    }
}

/**
 * Deprecated function "tc_get_attendee_order_message".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_attendee_order_message' ) ) {

    function tickera_get_attendee_order_message( $field_name, $default_value = '' ) {

        global $tc_email_settings;

        if ( isset( $tc_email_settings[ $field_name ] ) ) {
            $value = $tc_email_settings[ $field_name ];

        } else {
            $value = ( $default_value !== '' ) ? $default_value : '';
        }

        wp_editor( html_entity_decode( stripcslashes( esc_textarea( $value ) ) ), esc_attr( sanitize_key( $field_name ) ), array( 'textarea_name' => esc_attr( 'tickera_email_setting[' . $field_name . ']' ), 'textarea_rows' => 2 ) );
    }
}

/**
 * Deprecated function "tc_get_admin_order_message".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_admin_order_message' ) ) {

    function tickera_get_admin_order_message( $field_name, $default_value = '' ) {

        global $tc_email_settings;

        if ( isset( $tc_email_settings[ $field_name ] ) ) {
            $value = $tc_email_settings[ $field_name ];

        } else {
            $value = ( $default_value !== '' ) ? $default_value : '';
        }

        wp_editor( html_entity_decode( stripcslashes( esc_textarea( $value ) ) ), esc_attr( sanitize_key( $field_name ) ), array( 'textarea_name' => esc_attr( 'tickera_email_setting[' . $field_name . ']' ), 'textarea_rows' => 2 ) );
    }
}

/**
 * Deprecated function "tc_email_send_type".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_email_send_type' ) ) {

    function tickera_email_send_type( $field_name, $default_value = '' ) {

        global $tc_email_settings;

        if ( isset( $tc_email_settings[ $field_name ] ) ) {
            $checked = $tc_email_settings[ $field_name ];

        } else {
            $checked = ( $default_value !== '' ) ? $default_value : 'wp_mail';
        }
        ?>
        <label>
            <input type="radio" name="tickera_email_setting[<?php echo esc_attr( $field_name ); ?>]" value="wp_mail" <?php checked( $checked, 'wp_mail', true ); ?> /><?php esc_html_e( 'WP Mail (default)', 'tickera-event-ticketing-system' ); ?>
        </label>
        <label>
            <input type="radio" name="tickera_email_setting[<?php echo esc_attr( $field_name ); ?>]" value="mail" <?php checked( $checked, 'mail', true ); ?> /><?php esc_html_e( 'PHP Mail', 'tickera-event-ticketing-system' ); ?>
        </label>
        <?php
    }
}

/**
 * Deprecated function "tc_global_fee_type".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_global_fee_type' ) ) {

    function tickera_global_fee_type( $field_name, $default_value = '' ) {

        global $tc_general_settings;
        $checked = ( isset( $tc_general_settings[ $field_name ] ) )
            ? $tc_general_settings[ $field_name ]
            : $default_value;
        ?>
        <label>
            <input type="radio" name="tickera_general_setting[<?php echo esc_attr( $field_name ); ?>]" value="percentage" <?php checked( $checked, 'percentage', true ); ?> /><?php esc_html_e( 'Percentage', 'tickera-event-ticketing-system' ); ?>
        </label>
        <label>
            <input type="radio" name="tickera_general_setting[<?php echo esc_attr( $field_name ); ?>]" value="fixed" <?php checked( $checked, 'fixed', true ); ?> /><?php esc_html_e( 'Fixed', 'tickera-event-ticketing-system' ); ?>
        </label>
        <?php
    }
}

/**
 * Deprecated function "tc_global_fee_scope".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_global_fee_scope' ) ) {

    function tickera_global_fee_scope( $field_name, $default_value = '' ) {

        global $tc_general_settings;

        $checked = ( isset( $tc_general_settings[ $field_name ] ) )
            ? $tc_general_settings[ $field_name ]
            : $default_value;
        ?>
        <label>
            <input type="radio" name="tickera_general_setting[<?php echo esc_attr( $field_name ); ?>]" value="ticket" <?php checked( $checked, 'ticket', true ); ?> /><?php esc_html_e( 'Ticket', 'tickera-event-ticketing-system' ); ?>
        </label>
        <label>
            <input type="radio" name="tickera_general_setting[<?php echo esc_attr( $field_name ); ?>]" value="order" <?php checked( $checked, 'order', true ); ?> /><?php esc_html_e( 'Order', 'tickera-event-ticketing-system' ); ?>
        </label>
        <?php
    }
}

/**
 * Deprecated function "tc_get_price_formats".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_price_formats' ) ) {

    function tickera_get_price_formats( $field_name, $default_value = '' ) {

        global $tc_general_settings;

        if ( isset( $tc_general_settings[ $field_name ] ) ) {
            $checked = $tc_general_settings[ $field_name ];

        } else {
            $checked = ( $default_value !== '' ) ? $default_value : 'us';
        }
        ?>
        <select name="tickera_general_setting[<?php echo esc_html( $field_name ); ?>]">
            <option value="us" <?php selected( $checked, 'us', true ); ?>><?php esc_html_e( '1,234.56', 'tickera-event-ticketing-system' ); ?></option>
            <option value="eu" <?php selected( $checked, 'eu', true ); ?>><?php esc_html_e( '1.234,56', 'tickera-event-ticketing-system' ); ?></option>
            <option value="french_comma" <?php selected( $checked, 'french_comma', true ); ?>><?php esc_html_e( '1 234,56', 'tickera-event-ticketing-system' ); ?></option>
            <option value="french_dot" <?php selected( $checked, 'french_dot', true ); ?>><?php esc_html_e( '1 234.56', 'tickera-event-ticketing-system' ); ?></option>
            <?php do_action( 'tc_price_formats' ); ?>
        </select>
        <?php
    }
}

/**
 * Deprecated function "tc_get_currency_positions".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_currency_positions' ) ) {

    function tickera_get_currency_positions( $field_name, $default_value = '' ) {
        global $tc_general_settings;
        if ( isset( $tc_general_settings[ $field_name ] ) ) {
            $checked = $tc_general_settings[ $field_name ];
        } else {
            if ( $default_value !== '' ) {
                $checked = $default_value;
            } else {
                $checked = 'pre_nospace';
            }
        }

        $symbol = ( isset( $tc_general_settings[ 'currency_symbol' ] ) && $tc_general_settings[ 'currency_symbol' ] != '' ? $tc_general_settings[ 'currency_symbol' ] : ( isset( $tc_general_settings[ 'currencies' ] ) ? $tc_general_settings[ 'currencies' ] : '$' ) );
        ?>
        <select name="tickera_general_setting[<?php echo esc_html( $field_name ); ?>]">
            <option value="pre_space" <?php selected( $checked, 'pre_space', true ); ?>><?php echo esc_html( $symbol . ' 10' ); ?></option>
            <option value="pre_nospace" <?php selected( $checked, 'pre_nospace', true ); ?>><?php echo esc_html( $symbol . '10' ); ?></option>
            <option value="post_nospace" <?php selected( $checked, 'post_nospace', true ); ?>><?php echo esc_html( '10' . $symbol ); ?></option>
            <option value="post_space" <?php selected( $checked, 'post_space', true ); ?>><?php echo esc_html( '10 ' . $symbol ); ?></option>
            <?php do_action( 'tc_currencies_position' ); ?>
        </select>
        <?php
    }
}

/**
 * Deprecated function "tc_get_global_currencies".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_global_currencies' ) ) {

    function tickera_get_global_currencies( $field_name, $default_value = '' ) {
        global $tc_general_settings;
        $settings = get_option( 'tickera_settings' );
        $currencies = $settings[ 'gateways' ][ 'currencies' ];

        ksort( $currencies );

        if ( isset( $tc_general_settings[ $field_name ] ) ) {
            $checked = $tc_general_settings[ $field_name ];
        } else {
            if ( $default_value !== '' ) {
                $checked = $default_value;
            } else {
                $checked = 'USD';
            }
        }
        ?>
        <select name="tickera_general_setting[<?php echo esc_html( $field_name ); ?>]">
            <?php foreach ( $currencies as $symbol => $title ) : ?>
                <option value="<?php echo esc_attr( $symbol ); ?>" <?php selected( $checked, $symbol, true ); ?>><?php echo esc_html( $title ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}

/**
 * Deprecated function "tc_global_admin_per_page".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_global_admin_per_page' ) ) {

    function tickera_global_admin_per_page( $value ) {
        global $tc_general_settings;
        $settings = get_option( 'tickera_settings' );
        return isset( $tc_general_settings[ 'global_admin_per_page' ] ) ? $tc_general_settings[ 'global_admin_per_page' ] : $value;
    }
}

/**
 * Deprecated function "tc_get_global_admin_per_page".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_global_admin_per_page' ) ) {

    function tickera_get_global_admin_per_page( $field_name, $default_value = '' ) {
        global $tc_general_settings;

        $settings = get_option( 'tickera_settings' );
        $rows = array( 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100 );

        if ( isset( $tc_general_settings[ $field_name ] ) ) {
            $checked = $tc_general_settings[ $field_name ];
        } else {
            if ( $default_value !== '' ) {
                $checked = $default_value;
            } else {
                $checked = '10';
            }
        }
        ?>
        <select name="tickera_general_setting[<?php echo esc_attr( $field_name ); ?>]">
            <?php foreach ( $rows as $row ) : ?>
                <option value="<?php echo esc_attr( $row ); ?>" <?php selected( $checked, $row, true ); ?>><?php echo esc_html( $row ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}

/**
 * Deprecated function "tc_save_page_ids".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_save_page_ids' ) ) {

    function tickera_save_page_ids() {

        if ( isset( $_POST[ 'tc_cart_page_id' ] ) ) {
            update_option( 'tickera_cart_page_id', (int) $_POST[ 'tc_cart_page_id' ] );
        }

        if ( isset( $_POST[ 'tc_payment_page_id' ] ) ) {
            update_option( 'tickera_payment_page_id', (int) $_POST[ 'tc_payment_page_id' ] );
        }

        if ( isset( $_POST[ 'tc_confirmation_page_id' ] ) ) {
            update_option( 'tickera_confirmation_page_id', (int) $_POST[ 'tc_confirmation_page_id' ] );
        }

        if ( isset( $_POST[ 'tc_order_page_id' ] ) ) {
            update_option( 'tickera_order_page_id', (int) $_POST[ 'tc_order_page_id' ] );
        }

        if ( isset( $_POST[ 'tc_process_payment_page_id' ] ) ) {
            update_option( 'tickera_process_payment_page_id', (int) $_POST[ 'tc_process_payment_page_id' ] );
        }

        if ( isset( $_POST[ 'tc_process_payment_use_virtual' ] ) ) {
            update_option( 'tickera_process_payment_use_virtual', (int) $_POST[ 'tc_process_payment_use_virtual' ] );
        }

        if ( isset( $_POST[ 'tc_ipn_page_id' ] ) ) {
            update_option( 'tickera_ipn_page_id', (int) $_POST[ 'tc_ipn_page_id' ] );
        }

        if ( isset( $_POST[ 'tc_ipn_use_virtual' ] ) ) {
            update_option( 'tickera_ipn_use_virtual', (int) $_POST[ 'tc_ipn_use_virtual' ] );
        }
    }
}

/**
 * Deprecated function "tc_get_cart_page_settings".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_cart_page_settings' ) ) {

    function tickera_get_cart_page_settings( $field_name, $default_value = '' ) {

        wp_dropdown_pages( [
            'selected' => get_option( 'tickera_cart_page_id', -1 ),
            'echo' => 1,
            'name' => 'tc_cart_page_id',
        ]);
    }
}

/**
 * Deprecated function "tc_get_payment_page_settings".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_payment_page_settings' ) ) {

    function tickera_get_payment_page_settings( $field_name, $default_value = '' ) {

        wp_dropdown_pages( [
            'selected' => get_option( 'tickera_payment_page_id', -1 ),
            'echo' => 1,
            'name' => 'tc_payment_page_id',
        ]);
    }
}

/**
 * Deprecated function "tc_get_confirmation_page_settings".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_confirmation_page_settings' ) ) {

    function tickera_get_confirmation_page_settings( $field_name, $default_value = '' ) {

        wp_dropdown_pages( [
            'selected' => get_option( 'tickera_confirmation_page_id', -1 ),
            'echo' => 1,
            'name' => 'tc_confirmation_page_id',
        ]);
    }
}

/**
 * Deprecated function "tc_get_process_payment_page_settings".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_process_payment_page_settings' ) ) {

    function tickera_get_process_payment_page_settings( $field_name, $default_value = '' ) {

        wp_dropdown_pages( [
            'selected' => get_option( 'tickera_process_payment_page_id', -1 ),
            'echo' => 1,
            'name' => 'tc_process_payment_page_id',
        ]);
    }
}

/**
 * Deprecated function "tc_get_ipn_page_settings".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ipn_page_settings' ) ) {

    function tickera_get_ipn_page_settings( $field_name, $default_value = '' ) {

        wp_dropdown_pages( [
            'selected' => get_option( 'tickera_ipn_page_id', -1 ),
            'echo' => 1,
            'name' => 'tc_ipn_page_id',
        ]);
    }
}

/**
 * Deprecated function "tc_get_order_page_settings".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_page_settings' ) ) {

    function tickera_get_order_page_settings( $field_name, $default_value = '' ) {

        wp_dropdown_pages( [
            'selected' => get_option( 'tickera_order_page_id', -1 ),
            'echo' => 1,
            'name' => 'tc_order_page_id',
        ]);
    }
}

/**
 * Deprecated function "tc_get_pages_settings".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_pages_settings' ) ) {

    function tickera_get_pages_settings( $field_name, $default_value = '' ) {
        global $tc;
        if ( get_option( 'tickera_needs_pages', 1 ) == 1 ) {
            $install_caption = __( 'Install', 'tickera-event-ticketing-system' );
            $install_desciption = '';
        } else {
            $install_caption = __( 'Re-install', 'tickera-event-ticketing-system' );
            $install_desciption = __( 'If you want to reinstall the pages, make sure to delete old ones first (even from the trash).', 'tickera-event-ticketing-system' );
        }
        ?>
        <p class="submit"><a href="<?php echo esc_url( add_query_arg( 'install_tickera_pages', 'true', admin_url( 'edit.php?post_type=tc_events&page=tc_settings' ) ) ); ?>" class="button-secondary"><?php echo esc_html( sprintf( /* translators: 1: Caption (Install or Re-install) 2: Tickera */ __( '%1$s %2$s Pages', 'tickera-event-ticketing-system' ), esc_html( $install_caption ), esc_html( $tc->title ) ) ); ?></a>
        </p>
        <p class="description"><?php echo esc_html( $install_desciption ); ?></p>
        <?php
    }
}

/**
 * Print years
 *
 * @param string $sel
 * @param bool $pfp
 * @return string
 *
 * Deprecated function "tc_years_dropdown".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_years_dropdown' ) ) {

    function tickera_years_dropdown( $sel = '', $pfp = false ) {

        $localDate = getdate();
        $minYear = $localDate[ "year" ];
        $maxYear = $minYear + 15;

        $output = "<option value=''>--</option>";
        for ( $i = $minYear; $i < $maxYear; $i++ ) {
            if ( $pfp ) {
                $output .= "<option value='" . esc_attr( substr( $i, 0, 4 ) ) . "'" . ( $sel == ( substr( $i, 0, 4 ) ) ? ' selected' : '' ) . ">" . esc_html( $i ) . "</option>";
            } else {
                $output .= "<option value='" . esc_attr( substr( $i, 2, 2 ) ) . "'" . ( $sel == ( substr( $i, 2, 2 ) ) ? ' selected' : '' ) . ">" . esc_html( $i ) . "</option>";
            }
        }
        return ( $output );
    }
}

/**
 * Deprecated function "tc_get_client_ip".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_client_ip' ) ) {

    function tickera_get_client_ip() {

        if ( isset( $_SERVER[ 'X-Real-IP' ] ) ) {
            return sanitize_text_field( $_SERVER[ 'X-Real-IP' ] );

        } elseif ( isset( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {

            /*
            * Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
            * Make sure we always only send through the first IP in the list which should always be the client IP.
            */
            return trim( current( explode( ',', sanitize_text_field( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) ) );

        } elseif ( isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
            return sanitize_text_field( $_SERVER[ 'REMOTE_ADDR' ] );
        }

        return false;
    }
}

/**
 * Deprecated function "tc_get_client_info".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_client_info' ) ) {

    function tickera_get_client_info() {

        $ip_address = tickera_get_client_ip();
        $client_data = wp_remote_get( 'http://freegeoip.net/json/' . $ip_address, array( 'user-agent' => 'Tickera', 'sslverify' => false ) );

        if ( ! is_wp_error( $client_data ) ) {
            $client_data = json_decode( wp_remote_retrieve_body( $client_data ) );
        }

        return $client_data;
    }
}

/**
 * Deprecated function "tc_get_client_city".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_client_city' ) ) {

    function tickera_get_client_city() {
        $client_data = tickera_get_client_info();

        if ( isset( $client_data->city ) && ! empty( $client_data->city ) ) {
            return $client_data->city;
        } else {
            return '';
        }
    }
}

/**
 * Deprecated function "tc_get_client_zip".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_client_zip' ) ) {

    function tickera_get_client_zip() {
        $client_data = tickera_get_client_info();

        if ( isset( $client_data->zip_code ) && ! empty( $client_data->zip_code ) ) {
            return $client_data->zip_code;
        } else {
            return '';
        }
    }
}

/**
 * Deprecated function "tc_countries".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_countries' ) ) {

    function tickera_countries( $class = '', $name = '' ) {
        ob_start();

        $selected = 'US';

        $client_data = tickera_get_client_info();

        if ( isset( $client_data->country_code ) && ! empty( $client_data->country_code ) ) {
            $selected = $client_data->country_code;
        }

        $selected = apply_filters( 'tc_default_selected_country', $selected );
        ?>
        <select class="<?php echo esc_attr( $class ); ?>" name="<?php echo esc_attr( $name ); ?>">
            <option value="AF" <?php selected( $selected, 'AF', true ); ?>><?php esc_html_e( 'Afghanistan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AX" <?php selected( $selected, 'AX', true ); ?>><?php esc_html_e( 'Åland Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AL" <?php selected( $selected, 'AL', true ); ?>><?php esc_html_e( 'Albania', 'tickera-event-ticketing-system' ); ?></option>
            <option value="DZ" <?php selected( $selected, 'DZ', true ); ?>><?php esc_html_e( 'Algeria', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AS" <?php selected( $selected, 'AS', true ); ?>><?php esc_html_e( 'American Samoa', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AD" <?php selected( $selected, 'AD', true ); ?>><?php esc_html_e( 'Andorra', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AO" <?php selected( $selected, 'AO', true ); ?>><?php esc_html_e( 'Angola', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AI" <?php selected( $selected, 'AI', true ); ?>><?php esc_html_e( 'Anguilla', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AQ" <?php selected( $selected, 'AQ', true ); ?>><?php esc_html_e( 'Antarctica', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AG" <?php selected( $selected, 'AG', true ); ?>><?php esc_html_e( 'Antigua and Barbuda', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AR" <?php selected( $selected, 'AR', true ); ?>><?php esc_html_e( 'Argentina', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AM" <?php selected( $selected, 'AM', true ); ?>><?php esc_html_e( 'Armenia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AW" <?php selected( $selected, 'AW', true ); ?>><?php esc_html_e( 'Aruba', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AU" <?php selected( $selected, 'AU', true ); ?>><?php esc_html_e( 'Australia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AT" <?php selected( $selected, 'AT', true ); ?>><?php esc_html_e( 'Austria', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AZ" <?php selected( $selected, 'AZ', true ); ?>><?php esc_html_e( 'Azerbaijan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BS" <?php selected( $selected, 'BS', true ); ?>><?php esc_html_e( 'Bahamas', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BH" <?php selected( $selected, 'BH', true ); ?>><?php esc_html_e( 'Bahrain', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BD" <?php selected( $selected, 'BD', true ); ?>><?php esc_html_e( 'Bangladesh', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BB" <?php selected( $selected, 'BB', true ); ?>><?php esc_html_e( 'Barbados', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BY" <?php selected( $selected, 'BY', true ); ?>><?php esc_html_e( 'Belarus', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BE" <?php selected( $selected, 'BE', true ); ?>><?php esc_html_e( 'Belgium', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BZ" <?php selected( $selected, 'BZ', true ); ?>><?php esc_html_e( 'Belize', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BJ" <?php selected( $selected, 'BJ', true ); ?>><?php esc_html_e( 'Benin', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BM" <?php selected( $selected, 'BM', true ); ?>><?php esc_html_e( 'Bermuda', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BT" <?php selected( $selected, 'BT', true ); ?>><?php esc_html_e( 'Bhutan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BO" <?php selected( $selected, 'BO', true ); ?>><?php esc_html_e( 'Bolivia, Plurinational State of', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BQ" <?php selected( $selected, 'BQ', true ); ?>><?php esc_html_e( 'Bonaire, Sint Eustatius and Saba', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BA" <?php selected( $selected, 'BA', true ); ?>><?php esc_html_e( 'Bosnia and Herzegovina', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BW" <?php selected( $selected, 'BW', true ); ?>><?php esc_html_e( 'Botswana', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BV" <?php selected( $selected, 'BV', true ); ?>><?php esc_html_e( 'Bouvet Island', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BR" <?php selected( $selected, 'BR', true ); ?>><?php esc_html_e( 'Brazil', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IO" <?php selected( $selected, 'IO', true ); ?>><?php esc_html_e( 'British Indian Ocean Territory', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BN" <?php selected( $selected, 'BN', true ); ?>><?php esc_html_e( 'Brunei Darussalam', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BG" <?php selected( $selected, 'BG', true ); ?>><?php esc_html_e( 'Bulgaria', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BF" <?php selected( $selected, 'BF', true ); ?>><?php esc_html_e( 'Burkina Faso', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BI" <?php selected( $selected, 'BI', true ); ?>><?php esc_html_e( 'Burundi', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KH" <?php selected( $selected, 'KH', true ); ?>><?php esc_html_e( 'Cambodia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CM" <?php selected( $selected, 'CM', true ); ?>><?php esc_html_e( 'Cameroon', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CA" <?php selected( $selected, 'CA', true ); ?>><?php esc_html_e( 'Canada', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CV" <?php selected( $selected, 'CV', true ); ?>><?php esc_html_e( 'Cape Verde', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KY" <?php selected( $selected, 'KY', true ); ?>><?php esc_html_e( 'Cayman Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CF" <?php selected( $selected, 'CF', true ); ?>><?php esc_html_e( 'Central African Republic', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TD" <?php selected( $selected, 'TD', true ); ?>><?php esc_html_e( 'Chad', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CL" <?php selected( $selected, 'CL', true ); ?>><?php esc_html_e( 'Chile', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CN" <?php selected( $selected, 'CN', true ); ?>><?php esc_html_e( 'China', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CX" <?php selected( $selected, 'CX', true ); ?>><?php esc_html_e( 'Christmas Island', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CC" <?php selected( $selected, 'CC', true ); ?>><?php esc_html_e( 'Cocos (Keeling) Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CO" <?php selected( $selected, 'CO', true ); ?>><?php esc_html_e( 'Colombia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KM" <?php selected( $selected, 'KM', true ); ?>><?php esc_html_e( 'Comoros', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CG" <?php selected( $selected, 'CG', true ); ?>><?php esc_html_e( 'Congo', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CD" <?php selected( $selected, 'CD', true ); ?>><?php esc_html_e( 'Congo, the Democratic Republic of the', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CK" <?php selected( $selected, 'CK', true ); ?>><?php esc_html_e( 'Cook Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CR" <?php selected( $selected, 'CR', true ); ?>><?php esc_html_e( 'Costa Rica', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CI" <?php selected( $selected, 'CI', true ); ?>><?php esc_html_e( "Côte d'Ivoire", 'tickera-event-ticketing-system' ); ?></option>
            <option value="HR" <?php selected( $selected, 'HR', true ); ?>><?php esc_html_e( 'Croatia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CU" <?php selected( $selected, 'CU', true ); ?>><?php esc_html_e( 'Cuba', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CW" <?php selected( $selected, 'CW', true ); ?>><?php esc_html_e( 'Curaçao', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CY" <?php selected( $selected, 'CY', true ); ?>><?php esc_html_e( 'Cyprus', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CZ" <?php selected( $selected, 'CZ', true ); ?>><?php esc_html_e( 'Czech Republic', 'tickera-event-ticketing-system' ); ?></option>
            <option value="DK" <?php selected( $selected, 'DK', true ); ?>><?php esc_html_e( 'Denmark', 'tickera-event-ticketing-system' ); ?></option>
            <option value="DJ" <?php selected( $selected, 'DJ', true ); ?>><?php esc_html_e( 'Djibouti', 'tickera-event-ticketing-system' ); ?></option>
            <option value="DM" <?php selected( $selected, 'DM', true ); ?>><?php esc_html_e( 'Dominica', 'tickera-event-ticketing-system' ); ?></option>
            <option value="DO" <?php selected( $selected, 'DO', true ); ?>><?php esc_html_e( 'Dominican Republic', 'tickera-event-ticketing-system' ); ?></option>
            <option value="EC" <?php selected( $selected, 'EC', true ); ?>><?php esc_html_e( 'Ecuador', 'tickera-event-ticketing-system' ); ?></option>
            <option value="EG" <?php selected( $selected, 'EG', true ); ?>><?php esc_html_e( 'Egypt', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SV" <?php selected( $selected, 'SV', true ); ?>><?php esc_html_e( 'El Salvador', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GQ" <?php selected( $selected, 'GQ', true ); ?>><?php esc_html_e( 'Equatorial Guinea', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ER" <?php selected( $selected, 'ER', true ); ?>><?php esc_html_e( 'Eritrea', 'tickera-event-ticketing-system' ); ?></option>
            <option value="EE" <?php selected( $selected, 'EE', true ); ?>><?php esc_html_e( 'Estonia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ET" <?php selected( $selected, 'ET', true ); ?>><?php esc_html_e( 'Ethiopia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="FK" <?php selected( $selected, 'FK', true ); ?>><?php esc_html_e( 'Falkland Islands (Malvinas)', 'tickera-event-ticketing-system' ); ?></option>
            <option value="FO" <?php selected( $selected, 'FO', true ); ?>><?php esc_html_e( 'Faroe Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="FJ" <?php selected( $selected, 'FJ', true ); ?>><?php esc_html_e( 'Fiji', 'tickera-event-ticketing-system' ); ?></option>
            <option value="FI" <?php selected( $selected, 'FI', true ); ?>><?php esc_html_e( 'Finland', 'tickera-event-ticketing-system' ); ?></option>
            <option value="FR" <?php selected( $selected, 'FR', true ); ?>><?php esc_html_e( 'France', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GF" <?php selected( $selected, 'GF', true ); ?>><?php esc_html_e( 'French Guiana', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PF" <?php selected( $selected, 'PF', true ); ?>><?php esc_html_e( 'French Polynesia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TF" <?php selected( $selected, 'TF', true ); ?>><?php esc_html_e( 'French Southern Territories', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GA" <?php selected( $selected, 'GA', true ); ?>><?php esc_html_e( 'Gabon', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GM" <?php selected( $selected, 'GM', true ); ?>><?php esc_html_e( 'Gambia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GE" <?php selected( $selected, 'GE', true ); ?>><?php esc_html_e( 'Georgia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="DE" <?php selected( $selected, 'DE', true ); ?>><?php esc_html_e( 'Germany', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GH" <?php selected( $selected, 'GH', true ); ?>><?php esc_html_e( 'Ghana', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GI" <?php selected( $selected, 'GI', true ); ?>><?php esc_html_e( 'Gibraltar', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GR" <?php selected( $selected, 'GR', true ); ?>><?php esc_html_e( 'Greece', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GL" <?php selected( $selected, 'GL', true ); ?>><?php esc_html_e( 'Greenland', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GD" <?php selected( $selected, 'GD', true ); ?>><?php esc_html_e( 'Grenada', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GP" <?php selected( $selected, 'GP', true ); ?>><?php esc_html_e( 'Guadeloupe', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GU" <?php selected( $selected, 'GU', true ); ?>><?php esc_html_e( 'Guam', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GT" <?php selected( $selected, 'GT', true ); ?>><?php esc_html_e( 'Guatemala', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GG" <?php selected( $selected, 'GG', true ); ?>><?php esc_html_e( 'Guernsey', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GN" <?php selected( $selected, 'GN', true ); ?>><?php esc_html_e( 'Guinea', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GW" <?php selected( $selected, 'GW', true ); ?>><?php esc_html_e( 'Guinea-Bissau', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GY" <?php selected( $selected, 'GY', true ); ?>><?php esc_html_e( 'Guyana', 'tickera-event-ticketing-system' ); ?></option>
            <option value="HT" <?php selected( $selected, 'HT', true ); ?>><?php esc_html_e( 'Haiti', 'tickera-event-ticketing-system' ); ?></option>
            <option value="HM" <?php selected( $selected, 'HM', true ); ?>><?php esc_html_e( 'Heard Island and McDonald Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="VA" <?php selected( $selected, 'VA', true ); ?>><?php esc_html_e( 'Holy See (Vatican City State)', 'tickera-event-ticketing-system' ); ?></option>
            <option value="HN" <?php selected( $selected, 'HN', true ); ?>><?php esc_html_e( 'Honduras', 'tickera-event-ticketing-system' ); ?></option>
            <option value="HK" <?php selected( $selected, 'HK', true ); ?>><?php esc_html_e( 'Hong Kong', 'tickera-event-ticketing-system' ); ?></option>
            <option value="HU" <?php selected( $selected, 'HU', true ); ?>><?php esc_html_e( 'Hungary', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IS" <?php selected( $selected, 'IS', true ); ?>><?php esc_html_e( 'Iceland', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IN" <?php selected( $selected, 'IN', true ); ?>><?php esc_html_e( 'India', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ID" <?php selected( $selected, 'ID', true ); ?>><?php esc_html_e( 'Indonesia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IR" <?php selected( $selected, 'IR', true ); ?>><?php esc_html_e( 'Iran, Islamic Republic of', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IQ" <?php selected( $selected, 'IQ', true ); ?>><?php esc_html_e( 'Iraq', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IE" <?php selected( $selected, 'IE', true ); ?>><?php esc_html_e( 'Ireland', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IM" <?php selected( $selected, 'IM', true ); ?>><?php esc_html_e( 'Isle of Man', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IL" <?php selected( $selected, 'IL', true ); ?>><?php esc_html_e( 'Israel', 'tickera-event-ticketing-system' ); ?></option>
            <option value="IT" <?php selected( $selected, 'IT', true ); ?>><?php esc_html_e( 'Italy', 'tickera-event-ticketing-system' ); ?></option>
            <option value="JM" <?php selected( $selected, 'JM', true ); ?>><?php esc_html_e( 'Jamaica', 'tickera-event-ticketing-system' ); ?></option>
            <option value="JP" <?php selected( $selected, 'JP', true ); ?>><?php esc_html_e( 'Japan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="JE" <?php selected( $selected, 'JE', true ); ?>><?php esc_html_e( 'Jersey', 'tickera-event-ticketing-system' ); ?></option>
            <option value="JO" <?php selected( $selected, 'JO', true ); ?>><?php esc_html_e( 'Jordan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KZ" <?php selected( $selected, 'KZ', true ); ?>><?php esc_html_e( 'Kazakhstan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KE" <?php selected( $selected, 'KE', true ); ?>><?php esc_html_e( 'Kenya', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KI" <?php selected( $selected, 'KI', true ); ?>><?php esc_html_e( 'Kiribati', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KP" <?php selected( $selected, 'KP', true ); ?>><?php esc_html_e( "Korea, Democratic People's Republic of", 'tickera-event-ticketing-system' ); ?></option>
            <option value="KR" <?php selected( $selected, 'KR', true ); ?>><?php esc_html_e( 'Korea, Republic of', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KW" <?php selected( $selected, 'KW', true ); ?>><?php esc_html_e( 'Kuwait', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KG" <?php selected( $selected, 'KG', true ); ?>><?php esc_html_e( 'Kyrgyzstan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LA" <?php selected( $selected, 'LA', true ); ?>><?php esc_html_e( "Lao People's Democratic Republic", 'tickera-event-ticketing-system' ); ?></option>
            <option value="LV" <?php selected( $selected, 'LV', true ); ?>><?php esc_html_e( 'Latvia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LB" <?php selected( $selected, 'LB', true ); ?>><?php esc_html_e( 'Lebanon', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LS" <?php selected( $selected, 'LS', true ); ?>><?php esc_html_e( 'Lesotho', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LR" <?php selected( $selected, 'LR', true ); ?>><?php esc_html_e( 'Liberia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LY" <?php selected( $selected, 'LY', true ); ?>><?php esc_html_e( 'Libya', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LI" <?php selected( $selected, 'LI', true ); ?>><?php esc_html_e( 'Liechtenstein', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LT" <?php selected( $selected, 'LT', true ); ?>><?php esc_html_e( 'Lithuania', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LU" <?php selected( $selected, 'LU', true ); ?>><?php esc_html_e( 'Luxembourg', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MO" <?php selected( $selected, 'MO', true ); ?>><?php esc_html_e( 'Macao', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MK" <?php selected( $selected, 'MK', true ); ?>><?php esc_html_e( 'Macedonia, the former Yugoslav Republic of', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MG" <?php selected( $selected, 'MG', true ); ?>><?php esc_html_e( 'Madagascar', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MW" <?php selected( $selected, 'MW', true ); ?>><?php esc_html_e( 'Malawi', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MY" <?php selected( $selected, 'MY', true ); ?>><?php esc_html_e( 'Malaysia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MV" <?php selected( $selected, 'MV', true ); ?>><?php esc_html_e( 'Maldives', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ML" <?php selected( $selected, 'ML', true ); ?>><?php esc_html_e( 'Mali', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MT" <?php selected( $selected, 'MT', true ); ?>><?php esc_html_e( 'Malta', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MH" <?php selected( $selected, 'MH', true ); ?>><?php esc_html_e( 'Marshall Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MQ" <?php selected( $selected, 'MQ', true ); ?>><?php esc_html_e( 'Martinique', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MR" <?php selected( $selected, 'MR', true ); ?>><?php esc_html_e( 'Mauritania', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MU" <?php selected( $selected, 'MU', true ); ?>><?php esc_html_e( 'Mauritius', 'tickera-event-ticketing-system' ); ?></option>
            <option value="YT" <?php selected( $selected, 'YT', true ); ?>><?php esc_html_e( 'Mayotte', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MX" <?php selected( $selected, 'MX', true ); ?>><?php esc_html_e( 'Mexico', 'tickera-event-ticketing-system' ); ?></option>
            <option value="FM" <?php selected( $selected, 'FM', true ); ?>><?php esc_html_e( 'Micronesia, Federated States of', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MD" <?php selected( $selected, 'MD', true ); ?>><?php esc_html_e( 'Moldova, Republic of', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MC" <?php selected( $selected, 'MC', true ); ?>><?php esc_html_e( 'Monaco', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MN" <?php selected( $selected, 'MN', true ); ?>><?php esc_html_e( 'Mongolia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ME" <?php selected( $selected, 'ME', true ); ?>><?php esc_html_e( 'Montenegro', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MS" <?php selected( $selected, 'MS', true ); ?>><?php esc_html_e( 'Montserrat', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MA" <?php selected( $selected, 'MA', true ); ?>><?php esc_html_e( 'Morocco', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MZ" <?php selected( $selected, 'MZ', true ); ?>><?php esc_html_e( 'Mozambique', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MM" <?php selected( $selected, 'MM', true ); ?>><?php esc_html_e( 'Myanmar', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NA" <?php selected( $selected, 'NA', true ); ?>><?php esc_html_e( 'Namibia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NR" <?php selected( $selected, 'NR', true ); ?>><?php esc_html_e( 'Nauru', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NP" <?php selected( $selected, 'NP', true ); ?>><?php esc_html_e( 'Nepal', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NL" <?php selected( $selected, 'NL', true ); ?>><?php esc_html_e( 'Netherlands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NC" <?php selected( $selected, 'NC', true ); ?>><?php esc_html_e( 'New Caledonia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NZ" <?php selected( $selected, 'NZ', true ); ?>><?php esc_html_e( 'New Zealand', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NI" <?php selected( $selected, 'NI', true ); ?>><?php esc_html_e( 'Nicaragua', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NE" <?php selected( $selected, 'NE', true ); ?>><?php esc_html_e( 'Niger', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NG" <?php selected( $selected, 'NG', true ); ?>><?php esc_html_e( 'Nigeria', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NU" <?php selected( $selected, 'NU', true ); ?>><?php esc_html_e( 'Niue', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NF" <?php selected( $selected, 'NF', true ); ?>><?php esc_html_e( 'Norfolk Island', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MP" <?php selected( $selected, 'MP', true ); ?>><?php esc_html_e( 'Northern Mariana Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="NO" <?php selected( $selected, 'NO', true ); ?>><?php esc_html_e( 'Norway', 'tickera-event-ticketing-system' ); ?></option>
            <option value="OM" <?php selected( $selected, 'OM', true ); ?>><?php esc_html_e( 'Oman', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PK" <?php selected( $selected, 'PK', true ); ?>><?php esc_html_e( 'Pakistan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PW" <?php selected( $selected, 'PW', true ); ?>><?php esc_html_e( 'Palau', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PS" <?php selected( $selected, 'PS', true ); ?>><?php esc_html_e( 'Palestinian Territory, Occupied', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PA" <?php selected( $selected, 'PA', true ); ?>><?php esc_html_e( 'Panama', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PG" <?php selected( $selected, 'PG', true ); ?>><?php esc_html_e( 'Papua New Guinea', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PY" <?php selected( $selected, 'PY', true ); ?>><?php esc_html_e( 'Paraguay', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PE" <?php selected( $selected, 'PE', true ); ?>><?php esc_html_e( 'Peru', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PH" <?php selected( $selected, 'PH', true ); ?>><?php esc_html_e( 'Philippines', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PN" <?php selected( $selected, 'PN', true ); ?>><?php esc_html_e( 'Pitcairn', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PL" <?php selected( $selected, 'PL', true ); ?>><?php esc_html_e( 'Poland', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PT" <?php selected( $selected, 'PT', true ); ?>><?php esc_html_e( 'Portugal', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PR" <?php selected( $selected, 'PR', true ); ?>><?php esc_html_e( 'Puerto Rico', 'tickera-event-ticketing-system' ); ?></option>
            <option value="QA" <?php selected( $selected, 'QA', true ); ?>><?php esc_html_e( 'Qatar', 'tickera-event-ticketing-system' ); ?></option>
            <option value="RE" <?php selected( $selected, 'RE', true ); ?>><?php esc_html_e( 'Réunion', 'tickera-event-ticketing-system' ); ?></option>
            <option value="RO" <?php selected( $selected, 'RO', true ); ?>><?php esc_html_e( 'Romania', 'tickera-event-ticketing-system' ); ?></option>
            <option value="RU" <?php selected( $selected, 'RU', true ); ?>><?php esc_html_e( 'Russian Federation', 'tickera-event-ticketing-system' ); ?></option>
            <option value="RW" <?php selected( $selected, 'RW', true ); ?>><?php esc_html_e( 'Rwanda', 'tickera-event-ticketing-system' ); ?></option>
            <option value="BL" <?php selected( $selected, 'BL', true ); ?>><?php esc_html_e( 'Saint Barthélemy', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SH" <?php selected( $selected, 'SH', true ); ?>><?php esc_html_e( 'Saint Helena, Ascension and Tristan da Cunha', 'tickera-event-ticketing-system' ); ?></option>
            <option value="KN" <?php selected( $selected, 'KN', true ); ?>><?php esc_html_e( 'Saint Kitts and Nevis', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LC" <?php selected( $selected, 'LC', true ); ?>><?php esc_html_e( 'Saint Lucia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="MF" <?php selected( $selected, 'MF', true ); ?>><?php esc_html_e( 'Saint Martin (French part)', 'tickera-event-ticketing-system' ); ?></option>
            <option value="PM" <?php selected( $selected, 'PM', true ); ?>><?php esc_html_e( 'Saint Pierre and Miquelon', 'tickera-event-ticketing-system' ); ?></option>
            <option value="VC" <?php selected( $selected, 'VC', true ); ?>><?php esc_html_e( 'Saint Vincent and the Grenadines', 'tickera-event-ticketing-system' ); ?></option>
            <option value="WS" <?php selected( $selected, 'WS', true ); ?>><?php esc_html_e( 'Samoa', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SM" <?php selected( $selected, 'SM', true ); ?>><?php esc_html_e( 'San Marino', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ST" <?php selected( $selected, 'ST', true ); ?>><?php esc_html_e( 'Sao Tome and Principe', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SA" <?php selected( $selected, 'SA', true ); ?>><?php esc_html_e( 'Saudi Arabia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SN" <?php selected( $selected, 'SN', true ); ?>><?php esc_html_e( 'Senegal', 'tickera-event-ticketing-system' ); ?></option>
            <option value="RS" <?php selected( $selected, 'RS', true ); ?>><?php esc_html_e( 'Serbia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SC" <?php selected( $selected, 'SC', true ); ?>><?php esc_html_e( 'Seychelles', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SL" <?php selected( $selected, 'SL', true ); ?>><?php esc_html_e( 'Sierra Leone', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SG" <?php selected( $selected, 'SG', true ); ?>><?php esc_html_e( 'Singapore', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SX" <?php selected( $selected, 'SX', true ); ?>><?php esc_html_e( 'Sint Maarten (Dutch part)', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SK" <?php selected( $selected, 'SK', true ); ?>><?php esc_html_e( 'Slovakia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SI" <?php selected( $selected, 'SI', true ); ?>><?php esc_html_e( 'Slovenia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SB" <?php selected( $selected, 'SB', true ); ?>><?php esc_html_e( 'Solomon Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SO" <?php selected( $selected, 'SO', true ); ?>><?php esc_html_e( 'Somalia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ZA" <?php selected( $selected, 'ZA', true ); ?>><?php esc_html_e( 'South Africa', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GS" <?php selected( $selected, 'GS', true ); ?>><?php esc_html_e( 'South Georgia and the South Sandwich Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SS" <?php selected( $selected, 'SS', true ); ?>><?php esc_html_e( 'South Sudan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ES" <?php selected( $selected, 'ES', true ); ?>><?php esc_html_e( 'Spain', 'tickera-event-ticketing-system' ); ?></option>
            <option value="LK" <?php selected( $selected, 'LK', true ); ?>><?php esc_html_e( 'Sri Lanka', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SD" <?php selected( $selected, 'SD', true ); ?>><?php esc_html_e( 'Sudan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SR" <?php selected( $selected, 'SR', true ); ?>><?php esc_html_e( 'Suriname', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SJ" <?php selected( $selected, 'SJ', true ); ?>><?php esc_html_e( 'Svalbard and Jan Mayen', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SZ" <?php selected( $selected, 'SZ', true ); ?>><?php esc_html_e( 'Swaziland', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SE" <?php selected( $selected, 'SE', true ); ?>><?php esc_html_e( 'Sweden', 'tickera-event-ticketing-system' ); ?></option>
            <option value="CH" <?php selected( $selected, 'CH', true ); ?>><?php esc_html_e( 'Switzerland', 'tickera-event-ticketing-system' ); ?></option>
            <option value="SY" <?php selected( $selected, 'SY', true ); ?>><?php esc_html_e( 'Syrian Arab Republic', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TW" <?php selected( $selected, 'TW', true ); ?>><?php esc_html_e( 'Taiwan, Province of China', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TJ" <?php selected( $selected, 'TJ', true ); ?>><?php esc_html_e( 'Tajikistan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TZ" <?php selected( $selected, 'TZ', true ); ?>><?php esc_html_e( 'Tanzania, United Republic of', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TH" <?php selected( $selected, 'TH', true ); ?>><?php esc_html_e( 'Thailand', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TL" <?php selected( $selected, 'TL', true ); ?>><?php esc_html_e( 'Timor-Leste', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TG" <?php selected( $selected, 'TG', true ); ?>><?php esc_html_e( 'Togo', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TK" <?php selected( $selected, 'TK', true ); ?>><?php esc_html_e( 'Tokelau', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TO" <?php selected( $selected, 'TO', true ); ?>><?php esc_html_e( 'Tonga', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TT" <?php selected( $selected, 'TT', true ); ?>><?php esc_html_e( 'Trinidad and Tobago', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TN" <?php selected( $selected, 'TN', true ); ?>><?php esc_html_e( 'Tunisia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TR" <?php selected( $selected, 'TR', true ); ?>><?php esc_html_e( 'Turkey', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TM" <?php selected( $selected, 'TM', true ); ?>><?php esc_html_e( 'Turkmenistan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TC" <?php selected( $selected, 'TC', true ); ?>><?php esc_html_e( 'Turks and Caicos Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="TV" <?php selected( $selected, 'TV', true ); ?>><?php esc_html_e( 'Tuvalu', 'tickera-event-ticketing-system' ); ?></option>
            <option value="UG" <?php selected( $selected, 'UG', true ); ?>><?php esc_html_e( 'Uganda', 'tickera-event-ticketing-system' ); ?></option>
            <option value="UA" <?php selected( $selected, 'UA', true ); ?>><?php esc_html_e( 'Ukraine', 'tickera-event-ticketing-system' ); ?></option>
            <option value="AE" <?php selected( $selected, 'AE', true ); ?>><?php esc_html_e( 'United Arab Emirates', 'tickera-event-ticketing-system' ); ?></option>
            <option value="GB" <?php selected( $selected, 'GB', true ); ?>><?php esc_html_e( 'United Kingdom', 'tickera-event-ticketing-system' ); ?></option>
            <option value="US" <?php selected( $selected, 'US', true ); ?>><?php esc_html_e( 'United States', 'tickera-event-ticketing-system' ); ?></option>
            <option value="UM" <?php selected( $selected, 'UM', true ); ?>><?php esc_html_e( 'United States Minor Outlying Islands', 'tickera-event-ticketing-system' ); ?></option>
            <option value="UY" <?php selected( $selected, 'UY', true ); ?>><?php esc_html_e( 'Uruguay', 'tickera-event-ticketing-system' ); ?></option>
            <option value="UZ" <?php selected( $selected, 'UZ', true ); ?>><?php esc_html_e( 'Uzbekistan', 'tickera-event-ticketing-system' ); ?></option>
            <option value="VU" <?php selected( $selected, 'VU', true ); ?>><?php esc_html_e( 'Vanuatu', 'tickera-event-ticketing-system' ); ?></option>
            <option value="VE" <?php selected( $selected, 'VE', true ); ?>><?php esc_html_e( 'Venezuela, Bolivarian Republic of', 'tickera-event-ticketing-system' ); ?></option>
            <option value="VN" <?php selected( $selected, 'VN', true ); ?>><?php esc_html_e( 'Viet Nam', 'tickera-event-ticketing-system' ); ?></option>
            <option value="VG" <?php selected( $selected, 'VG', true ); ?>><?php esc_html_e( 'Virgin Islands, British', 'tickera-event-ticketing-system' ); ?></option>
            <option value="VI" <?php selected( $selected, 'VI', true ); ?>><?php esc_html_e( 'Virgin Islands, U.S.', 'tickera-event-ticketing-system' ); ?></option>
            <option value="WF" <?php selected( $selected, 'WF', true ); ?>><?php esc_html_e( 'Wallis and Futuna', 'tickera-event-ticketing-system' ); ?></option>
            <option value="EH" <?php selected( $selected, 'EH', true ); ?>><?php esc_html_e( 'Western Sahara', 'tickera-event-ticketing-system' ); ?></option>
            <option value="YE" <?php selected( $selected, 'YE', true ); ?>><?php esc_html_e( 'Yemen', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ZM" <?php selected( $selected, 'ZM', true ); ?>><?php esc_html_e( 'Zambia', 'tickera-event-ticketing-system' ); ?></option>
            <option value="ZW" <?php selected( $selected, 'ZW', true ); ?>><?php esc_html_e( 'Zimbabwe', 'tickera-event-ticketing-system' ); ?></option>
        </select>
        <?php
        return ob_get_clean();
    }
}


/**
 * Print months
 *
 * Deprecated function "tc_months_dropdown".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_months_dropdown' ) ) {

    function tickera_months_dropdown( $sel = '' ) {
        $output = "<option value=''>--</option>";
        $output .= "<option " . ( $sel == 1 ? ' selected' : '' ) . " value='01'>01 - " . esc_html__( 'Jan', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 2 ? ' selected' : '' ) . "  value='02'>02 - " . esc_html__( 'Feb', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 3 ? ' selected' : '' ) . "  value='03'>03 - " . esc_html__( 'Mar', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 4 ? ' selected' : '' ) . "  value='04'>04 - " . esc_html__( 'Apr', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 5 ? ' selected' : '' ) . "  value='05'>05 - " . esc_html__( 'May', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 6 ? ' selected' : '' ) . "  value='06'>06 - " . esc_html__( 'Jun', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 7 ? ' selected' : '' ) . "  value='07'>07 - " . esc_html__( 'Jul', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 8 ? ' selected' : '' ) . "  value='08'>08 - " . esc_html__( 'Aug', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 9 ? ' selected' : '' ) . "  value='09'>09 - " . esc_html__( 'Sep', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 10 ? ' selected' : '' ) . "  value='10'>10 - " . esc_html__( 'Oct', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 11 ? ' selected' : '' ) . "  value='11'>11 - " . esc_html__( 'Nov', 'tickera-event-ticketing-system' ) . "</option>";
        $output .= "<option " . ( $sel == 12 ? ' selected' : '' ) . "  value='12'>12 - " . esc_html__( 'Dec', 'tickera-event-ticketing-system' ) . "</option>";
        return ( $output );
    }
}

/**
 * Prevent search engines to index a page
 *
 * Deprecated function "tc_no_index_no_follow".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_no_index_no_follow' ) ) {

    function tickera_no_index_no_follow() { ?>
        <meta name='robots' content='noindex,nofollow'/>
    <?php }
}

/**
 * Deprecated function "tc_get_order_id_by_name".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_id_by_name' ) ) {

    function tickera_get_order_id_by_name( $slug ) {

        global $wpdb;

        $order_post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '%s'", strtolower( $slug ) ) );
        $post = get_post( $order_post_id );

        if ( isset( $post ) && ! empty( $post ) ) {
            if ( $post->post_name == strtolower( $slug ) ) {
                return $post;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

/**
 * Retrieve the status of an order
 *
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_order_status".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_status' ) ) {

    function tickera_get_order_status( $field_name = '', $post_id = '' ) {

        $value = get_post_status( $post_id );
        $new_value = str_replace( '_', ' ', $value );

        switch ( $value ) {

            case 'order_fraud':
            case 'order_refunded':
                $color = "tc_order_fraud";
                break;

            case 'order_received':
                $color = "tc_order_received";
                break;

            case 'order_paid':
                $color = "tc_order_paid";
                break;

            case 'order_cancelled':
                $color = "tc_order_cancelled";
                break;

            default:
                $color = 'tc_order_received';
        }

        echo esc_html( sprintf(
        /* translators: 1: Order status color class identifier 2: Order status */
            __( '<span class="%1$s">%2$s</span>', 'tickera-event-ticketing-system' ),
            esc_attr( $color ),
            esc_html( $new_value )
        ) );
    }
}

/**
 * Deprecated function "tc_get_order_front_link".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_front_link' ) ) {

    function tickera_get_order_front_link( $field_name = '', $post_id = '' ) {
        global $tc, $wp;
        $order = new \Tickera\TC_Order( $post_id );
        echo wp_kses_post( $tc->tc_order_status_url( $order, $order->details->tc_order_date, __( 'Order details page', 'tickera-event-ticketing-system' ) ) );
    }
}

/**
 * Generate a selection field for Order Status
 *
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_order_status_select".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_status_select' ) ) {

    function tickera_get_order_status_select( $field_name = '', $post_id = '' ) {
        $value = get_post_status( $post_id ); ?>
        <select class="order_status_change" name="order_status_change">
            <?php foreach ( tickera_get_order_statuses() as $order_status => $order_status_label ) : ?>
                <option value="<?php echo esc_attr( $order_status ); ?>" <?php selected( $value, $order_status, true ); ?>><?php echo esc_html( $order_status_label ); ?></option>
            <?php endforeach; ?>
            <?php if ( $value == 'trash' ) { ?>
                <option value='trash' <?php selected( $value, 'trash', true ); ?>><?php esc_html_e( 'Trash', 'tickera-event-ticketing-system' ); ?></option>
            <?php } ?>
        </select>
        <?php
    }
}

/**
 * Deprecated function "tc_get_order_customer".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_customer' ) ) {

    function tickera_get_order_customer( $field_name = '', $post_id = '' ) {
        $value = get_post_meta( $post_id, $field_name, true );
        $order = new \Tickera\TC_Order( $post_id );
        $author_id = $order->details->post_author;

        $first_name = ( isset( $value[ 'buyer_data' ] ) && isset( $value[ 'buyer_data' ][ 'first_name_post_meta' ] ) ) ? $value[ 'buyer_data' ][ 'first_name_post_meta' ] : '';
        $last_name = ( isset( $value[ 'buyer_data' ] ) && isset( $value[ 'buyer_data' ][ 'last_name_post_meta' ] ) ) ? $value[ 'buyer_data' ][ 'last_name_post_meta' ] : '';
        ?>
        <input type="text" name="customer_first_name" id="tc_order_details_customer_first_name" placeholder="<?php esc_attr_e( 'First Name', 'tickera-event-ticketing-system' ); ?>" value="<?php echo esc_attr( $first_name ); ?>"/>
        <input type="text" name="customer_last_name" id="tc_order_details_customer_last_name" placeholder="<?php esc_attr_e( 'Last Name', 'tickera-event-ticketing-system' ); ?>" value="<?php echo esc_attr( $last_name ); ?>"/>
        <?php
    }
}

/**
 * Deprecated function "tc_get_order_customer_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_customer_email' ) ) {

    function tickera_get_order_customer_email( $field_name = '', $post_id = '' ) {
        $value = get_post_meta( $post_id, $field_name, true );
        $customer_email = isset( $value[ 'buyer_data' ][ 'email_post_meta' ] ) ? $value[ 'buyer_data' ][ 'email_post_meta' ] : '';
        ?>
        <input type="text" name="customer_email" id="tc_order_details_customer_email" value="<?php echo esc_attr( $customer_email ); ?>"/>
        <?php
    }
}

/**
 * Deprecated function "tc_get_ticket_instance_event".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_instance_event' ) ) {

    function tickera_get_ticket_instance_event( $field_name, $field_id, $ticket_instance_id, $echo = true ) {

        $ticket_instance_event_id = get_post_meta( $ticket_instance_id, 'event_id', true );
        if ( ! empty( $ticket_instance_event_id ) ) {
            $event_id = $ticket_instance_event_id;
        } else {
            $ticket_type_id = get_post_meta( $ticket_instance_id, 'ticket_type_id', true );
            $ticket_type = new \Tickera\TC_Ticket( $ticket_type_id );
            $event_id = $ticket_type->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_type_id ) );
        }
        if ( ! empty( $event_id ) ) {
            $event = new \Tickera\TC_Event( $event_id );
            $value = $event->details->post_title;

        } else {
            $value = __( 'N/A', 'tickera-event-ticketing-system' );
        }

        if ( ! $echo ) {
            return $value;

        } else {
            echo esc_html( $value );
        }
    }
}

/**
 * Render event name with link.
 *
 * @param $field_name
 * @param $field_id
 * @param $ticket_instance_id
 *
 * Instead of rendering the ticket type's event, render registered event from the tickets instances.
 * @since 3.5.1.8
 *
 * Deprecated function "tc_get_ticket_instance_event_front".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_instance_event_front' ) ) {

    function tickera_get_ticket_instance_event_front( $field_name, $field_id, $ticket_instance_id ) {
        $event_id = get_post_meta( $ticket_instance_id, 'event_id', true );
        if ( ! empty( $event_id ) && get_post( $event_id ) ) {
            $event = new \Tickera\TC_Event( $event_id );
            echo wp_kses_post( '<a href="' . esc_url( apply_filters( 'tc_email_event_permalink', get_the_permalink( $event->details->ID ), $event_id, $ticket_instance_id ) ) . '">' . esc_html( $event->details->post_title ) . '</a>' );
            do_action( 'tc_after_event_title_table_front_event_permalink', $event_id );
        } else {
            esc_html_e( 'N/A', 'tickera-event-ticketing-system' );
        }
    }
}

/**
 * Deprecated function "tc_get_ticket_instance_type".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_instance_type' ) ) {

    function tickera_get_ticket_instance_type( $field_name, $field_id, $ticket_instance_id ) {
        $ticket_type_id = get_post_meta( $ticket_instance_id, 'ticket_type_id', true );
        $ticket_type = new \Tickera\TC_Ticket( $ticket_type_id );

        if ( isset( $ticket_type->details->post_title ) ) {
            $ticket_type_title = $ticket_type->details->post_title;

        } else {
            $ticket_type_title = sprintf( /* translators: %d: Ticket type Post ID. */ __( 'Missing Ticket Type ID %d', 'tickera-event-ticketing-system' ), (int) $ticket_type_id );
        }

        $ticket_type_title = apply_filters( 'tc_checkout_owner_info_ticket_title', $ticket_type_title, $ticket_type_id, array(), $ticket_instance_id );
        echo wp_kses_post( $ticket_type_title );
    }
}

/**
 * Deprecated function "tc_get_ticket_download_link".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_download_link' ) ) {

    function tickera_get_ticket_download_link( $field_name, $field_id, $ticket_id, $return = false ) {
        global $tc, $wp;

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $use_order_details_pretty_links = isset( $tc_general_settings[ 'use_order_details_pretty_links' ] ) ? $tc_general_settings[ 'use_order_details_pretty_links' ] : 'yes';

        $ticket = new \Tickera\TC_Ticket( $ticket_id );
        $order = new \Tickera\TC_Order( $ticket->details->post_parent );

        if ( $use_order_details_pretty_links == 'yes' ) {
            $order_key = isset( $wp->query_vars[ 'tc_order_key' ] ) ? sanitize_text_field( $wp->query_vars[ 'tc_order_key' ] ) : strtotime( $order->details->post_date );
            $download_url = apply_filters( 'tc_download_ticket_url_front', trailingslashit( $tc->get_order_slug( true ) ) . $order->details->post_title . '/' . $order_key . '/?download_ticket=' . $ticket_id . '&order_key=' . $order_key . '&nonce=' . wp_hash( $ticket_id . $order_key ), $order_key, $ticket_id );

            if ( $return ) {
                return apply_filters( 'tc_download_ticket_url_front_link', '<a href="' . esc_url( $download_url ) . '">' . esc_html__( 'Download', 'tickera-event-ticketing-system' ) . '</a>', $ticket_id, $ticket->details->post_parent, $download_url );
            } else {
                echo wp_kses_post( apply_filters( 'tc_download_ticket_url_front_link', '<a href="' . esc_url( $download_url ) . '">' . esc_html__( 'Download', 'tickera-event-ticketing-system' ) . '</a>', $ticket_id, $ticket->details->post_parent, $download_url ) );
            }

        } else {

            $order_key = isset( $_GET[ 'tc_order_key' ] ) ? sanitize_key( $_GET[ 'tc_order_key' ] ) : strtotime( $order->details->post_date );
            $download_url = str_replace( ' ', '', apply_filters( 'tc_download_ticket_url_front', trailingslashit( $tc->get_order_slug( true ) ) . '?tc_order=' . $order->details->post_title . '&tc_order_key=' . $order_key . '&download_ticket=' . $ticket_id . '&order_key=' . $order_key . '&nonce=' . wp_hash( $ticket_id . $order_key ), $order_key, $ticket_id ) );

            if ( $return ) {
                return apply_filters( 'tc_download_ticket_url_front_link', '<a href="' . esc_url( $download_url ) . '">' . esc_html__( 'Download', 'tickera-event-ticketing-system' ) . '</a>', $ticket_id, $ticket->details->post_parent, $download_url );
            } else {
                echo wp_kses_post( apply_filters( 'tc_download_ticket_url_front_link', '<a href="' . esc_url( $download_url ) . '">' . esc_html__( 'Download', 'tickera-event-ticketing-system' ) . '</a>', $ticket_id, $ticket->details->post_parent, $download_url ) );
            }
        }
    }
}

/**
 * Deprecated function "tc_get_raw_ticket_download_link".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_raw_ticket_download_link' ) ) {

    function tickera_get_raw_ticket_download_link( $field_name, $field_id, $ticket_id, $return = false ) {

        global $tc, $wp;

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $use_order_details_pretty_links = isset( $tc_general_settings[ 'use_order_details_pretty_links' ] ) ? $tc_general_settings[ 'use_order_details_pretty_links' ] : 'yes';

        $ticket = new \Tickera\TC_Ticket( $ticket_id );
        $order = new \Tickera\TC_Order( $ticket->details->post_parent );

        if ( $use_order_details_pretty_links == 'yes' ) {

            $order_key = isset( $wp->query_vars[ 'tc_order_key' ] ) ? sanitize_text_field( $wp->query_vars[ 'tc_order_key' ] ) : strtotime( $order->details->post_date );
            $download_url = apply_filters( 'tc_download_ticket_url_front', trailingslashit( $tc->get_order_slug( true ) ) . $order->details->post_title . '/' . $order_key . '/?download_ticket=' . $ticket_id . '&order_key=' . $order_key . '&nonce=' . wp_hash( $ticket_id . $order_key ), $order_key, $ticket_id );

            if ( $return ) {
                return apply_filters( 'tc_download_ticket_url_front_link', $download_url, $ticket_id, $ticket->details->post_parent );

            } else {
                echo esc_html( apply_filters( 'tc_download_ticket_url_front_link', $download_url, $ticket_id, $ticket->details->post_parent ) );
            }

        } else {

            $order_key = isset( $_GET[ 'tc_order_key' ] ) ? sanitize_key( $_GET[ 'tc_order_key' ] ) : strtotime( $order->details->post_date );
            $download_url = str_replace( ' ', '', apply_filters( 'tc_download_ticket_url_front', trailingslashit( $tc->get_order_slug( true ) ) . '?tc_order=' . $order->details->post_title . '&tc_order_key=' . $order_key . '&download_ticket=' . $ticket_id . '&order_key=' . $order_key . '&nonce=' . wp_hash( $ticket_id . $order_key ), $order_key, $ticket_id ) );

            if ( $return ) {
                return apply_filters( 'tc_download_ticket_url_front_link', $download_url, $ticket_id, $ticket->details->post_parent );

            } else {
                echo esc_html( apply_filters( 'tc_download_ticket_url_front_link', $download_url, $ticket_id, $ticket->details->post_parent ) );
            }
        }
    }
}

/**
 * Render the placeholder TICKET_TABLES
 *
 * @param string $order_id
 * @param string $order_key
 * @return string
 *
 * Deprecated function "tc_get_tickets_table_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_tickets_table_email' ) ) {

    function tickera_get_tickets_table_email( $order_id = '', $order_key = '' ) {

        ob_start();

        $order = new \Tickera\TC_Order( $order_id );
        $order_is_paid = ( 'order_paid' == $order->details->post_status ) ? true : false;
        $order_is_paid = apply_filters( 'tc_order_is_paid', $order_is_paid, $order_id );

        if ( $order_is_paid ) {

            $orders = new \Tickera\TC_Orders();

            // Collect all published tickets
            $tickets = get_posts( [
                'posts_per_page' => -1,
                'orderby' => 'post_date',
                'order' => 'ASC',
                'post_type' => 'tc_tickets_instances',
                'post_parent' => $order->details->ID,
            ] );

            // Collect and merge trashed cancelled tickets onto the previous collection.
            $tickets = array_merge( $tickets, get_posts( [
                'posts_per_page' => -1,
                'orderby' => 'post_date',
                'order' => 'ASC',
                'post_type' => 'tc_tickets_instances',
                'post_status' => [ 'trash' ],
                'post_parent' => $order->details->ID,
                'meta_query' => [
                    [
                        'key' => '_cancelled_order',
                        'compare' => 'EXISTS'
                    ]
                ]
            ] ) );

            $columns = apply_filters( 'tc_ticket_table_email_columns', $orders->get_owner_info_fields_front() );

            $style = '';
            $style_css_table = 'cellspacing="0" cellpadding="6" style="width: 100%; font-family: Helvetica, Roboto, Arial, sans-serif;" border="1"';
            $style_css_tr = '';
            $style_css_td = '';

            if ( $tickets ) :
                do_action( 'tc_tickets_table_email_before_table', $order_id, $tickets, $columns );
                ?>
            <table class="td" <?php echo wp_kses_post( apply_filters( 'tc_style_css_table', $style_css_table ) ); ?>>
                <tr <?php echo wp_kses_post( apply_filters( 'tc_style_css_tr', $style_css_tr ) ); ?>>
                    <?php foreach ( $columns as $column ) : ?>
                        <?php do_action( 'tc_tickets_table_email_column_title_before_' . $column[ 'id' ] ); ?>
                        <th class="td" <?php echo wp_kses_post( apply_filters( 'tc_style_css_th', $style_css_tr ) ); ?>><?php echo esc_html( $column[ 'field_title' ] ); ?></th>
                        <?php do_action( 'tc_tickets_table_email_column_title_after_' . $column[ 'id' ] ); ?>
                    <?php endforeach; ?>
                </tr>
                <?php foreach ( $tickets as $ticket ) :
                $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"'; ?>
                <tr <?php echo wp_kses( $style, [ 'class' => [] ] ); ?> <?php echo wp_kses_post( apply_filters( 'tc_style_css_tr', $style_css_tr ) ); ?>>
                    <?php foreach ( $columns as $column ) : ?>
                        <?php do_action( 'tc_tickets_table_email_column_value_before_' . $column[ 'id' ], $ticket ); ?>
                        <td class="td" <?php echo wp_kses_post( apply_filters( 'tc_style_css_td', $style_css_td ) ); ?>>
                            <?php if ( 'function' == $column[ 'field_type' ] ) {
                                call_user_func( $column[ 'function' ], $column[ 'field_name' ], ( isset( $column[ 'field_id' ] ) ? $column[ 'field_id' ] : '' ), $ticket->ID );
                            } else {
                                if ( 'post_meta' == $column[ 'post_field_type' ] ) {
                                    echo esc_html( get_post_meta( $ticket->ID, $column[ 'field_name' ], true ) );

                                } elseif ( 'ID' == $column[ 'post_field_type' ] ) {
                                    echo esc_html( $ticket->ID );
                                }
                            }
                            ?>
                        </td>
                        <?php do_action( 'tc_tickets_table_email_column_value_after_' . $column[ 'id' ], $ticket ); ?>
                    <?php endforeach; ?>
                </tr>
                <?php do_action( 'tc_tickets_table_email_additional_row', $ticket ); ?>
            <?php endforeach; ?>
                </table><?php
                do_action( 'tc_tickets_table_email_after_table', $order_id, $tickets, $columns );
            endif;
        }

        return wpautop( ob_get_clean(), true );
    }
}

/**
 * Generate order details template for mailing
 *
 * @param string $order_id
 * @param string $order_key
 * @param bool $return
 * @param $status
 * @return string
 *
 * Deprecated function "tc_get_order_details_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_details_email' ) ) {

    function tickera_get_order_details_email( $order_id, $order_key, $return, $status ) {
        global $tc;

        if ( $return ) {
            ob_start();
        }

        $tc_general_settings = get_option( 'tickera_general_setting', false );

        $order = new \Tickera\TC_Order( $order_id );

        if ( empty( $order_key ) ) {
            $order_key = strtotime( $order->details->post_date );
        }

        if ( isset( $status ) ) {
            if ( $status == 'order_paid' ) {
                $order->details->post_status = $status;
            }
        }

        // Key must match order creation date for security reasons
        if ( $order->details->tc_order_date == $order_key || strtotime( $order->details->post_date ) == $order_key ) {

            switch ( $order->details->post_status ) {

                case 'order_received':
                    $order_status = __( 'Pending Payment', 'tickera-event-ticketing-system' );
                    break;

                case 'order_fraud':
                    $order_status = __( 'Under Review', 'tickera-event-ticketing-system' );
                    break;

                case 'order_paid':
                    $order_status = __( 'Payment Completed', 'tickera-event-ticketing-system' );
                    break;

                case 'trash':
                    $order_status = __( 'Order Deleted', 'tickera-event-ticketing-system' );
                    break;

                case 'order_cancelled':
                    $order_status = __( 'Order Cancelled', 'tickera-event-ticketing-system' );
                    break;

                case 'order_refunded':
                    $order_status = __( 'Order Refunded', 'tickera-event-ticketing-system' );
                    break;

                default:
                    $order_status = $order->details->post_status;
            }

            $fees_total = sanitize_text_field( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'fees_total' ] ) );
            $tax_total = sanitize_text_field( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'tax_total' ] ) );
            $subtotal = sanitize_text_field( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'subtotal' ] ) );
            $total = sanitize_text_field( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) );

            $transaction_id = isset( $order->details->tc_payment_info[ 'transaction_id' ] ) ? sanitize_text_field( $order->details->tc_payment_info[ 'transaction_id' ] ) : '';
            $order_id = strtoupper( $order->details->post_name );
            $order_date = $payment_date = apply_filters( 'tc_order_date', tickera_format_date( $order->details->tc_order_date, true ) );

            $tc_style_email_label = '';
            $tc_style_email_label_span = '';

            do_action( 'tc_get_order_details_email_labels_before', $order_id );

            if ( apply_filters( 'tc_get_order_details_email_show_order', true, $order_id ) == true ) { ?>
                <label <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label ) ); ?>>
                    <span <?php echo wp_kses_post( apply_filters( 'tc_style_email_label_span', $tc_style_email_label_span ) ); ?> class="order_details_title"><?php esc_html_e( 'Order: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $order_id ); ?>
                </label>
            <?php } ?>
            <?php if ( apply_filters( 'tc_get_order_details_email_show_order_date', true, $order_id ) == true ) { ?>
                <label <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label ) ); ?>>
                    <span <?php echo wp_kses_post( apply_filters( 'tc_style_email_label_span', $tc_style_email_label_span ) ); ?> class="order_details_title"><?php esc_html_e( 'Order date: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $order_date ); ?>
                </label>
            <?php } ?>
            <?php if ( apply_filters( 'tc_get_order_details_email_show_order_status', true, $order_id ) == true ) { ?>
                <label <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label ) ); ?>>
                    <span <?php echo wp_kses_post( apply_filters( 'tc_style_email_label_span', $tc_style_email_label_span ) ); ?> class="order_details_title"><?php esc_html_e( 'Order status: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $order_status ); ?>
                </label>
            <?php } ?>
            <?php if ( apply_filters( 'tc_get_order_details_email_show_transaction_id', true, $order_id ) == true ) { ?>
                <?php if ( isset( $transaction_id ) && $transaction_id !== '' ) { ?>
                    <label <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label ) ); ?>>
                        <span <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label_span ) ); ?> class="order_details_title"><?php esc_html_e( 'Transaction ID: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $transaction_id ); ?>
                    </label>
                <?php } ?>
            <?php } ?>
            <?php if ( apply_filters( 'tc_get_order_details_email_show_subtitle', true, $order_id ) == true ) { ?>
                <label <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label ) ); ?>>
                    <span <?php echo wp_kses_post( apply_filters( 'tc_style_email_label_span', $tc_style_email_label_span ) ); ?> class="order_details_title"><?php esc_html_e( 'Subtotal: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $subtotal ); ?>
                </label>
            <?php } ?>
            <?php if ( apply_filters( 'tc_get_order_details_email_show_fees', true, $order_id ) == true ) { ?>
                <?php if ( ! isset( $tc_general_settings[ 'show_fees' ] ) || isset( $tc_general_settings[ 'show_fees' ] ) && $tc_general_settings[ 'show_fees' ] == 'yes' ) { ?>
                    <label <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label ) ); ?>>
                        <span <?php echo wp_kses_post( apply_filters( 'tc_style_email_label_span', $tc_style_email_label_span ) ); ?> class="order_details_title"><?php echo esc_html( @$tc_general_settings[ 'fees_label' ] ); ?></span> <?php echo esc_html( $fees_total ); ?>
                    </label>
                <?php } ?>
            <?php } ?>
            <?php if ( apply_filters( 'tc_get_order_details_email_show_tax', true, $order_id ) == true ) { ?>
                <?php if ( ! isset( $tc_general_settings[ 'show_tax_rate' ] ) || isset( $tc_general_settings[ 'show_tax_rate' ] ) && $tc_general_settings[ 'show_tax_rate' ] == 'yes' ) { ?>
                    <label <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label ) ); ?>>
                        <span <?php echo wp_kses_post( apply_filters( 'tc_style_email_label_span', $tc_style_email_label_span ) ); ?> class="order_details_title"><?php echo esc_html( @$tc_general_settings[ 'tax_label' ] ); ?></span> <?php echo esc_html( $tax_total ); ?>
                    </label>
                <?php } ?>
            <?php } ?>
            <?php if ( apply_filters( 'tc_get_order_details_email_show_total', true, $order_id ) == true ) { ?>
                <label <?php echo wp_kses_post( apply_filters( 'tc_style_email_label', $tc_style_email_label ) ); ?>>
                    <span <?php echo wp_kses_post( apply_filters( 'tc_style_email_label_span', $tc_style_email_label_span ) ); ?> class="order_details_title"><?php esc_html_e( 'Total: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $total ); ?>
                </label>
                <?php
            }
            do_action( 'tc_get_order_details_email_tickets_table_before', $order_id );

            if ( apply_filters( 'tc_get_order_details_email_show_tickets_table', true, $order_id ) == true ) { ?>
                <?php
                if ( 'order_paid' == $order->details->post_status ) {

                    $orders = new \Tickera\TC_Orders();

                    $tickets = get_posts( [
                        'posts_per_page' => -1,
                        'orderby' => 'post_date',
                        'order' => 'ASC',
                        'post_type' => 'tc_tickets_instances',
                        'post_parent' => (int) $order->details->ID
                    ]);

                    $columns = apply_filters( 'tc_ticket_table_email_columns', $orders->get_owner_info_fields_front() );
                    $style = '';

                    $style_css_table = 'cellspacing="0" cellpadding="6" style="width: 100%; font-family: Helvetica, Roboto, Arial, sans-serif;" border="1"';
                    $style_css_tr = '';
                    $style_css_td = '';
                    ?>
                    <table class="order-details widefat shadow-table" <?php echo wp_kses_post( apply_filters( 'tc_style_css_table', $style_css_table ) ); ?>>
                        <tr <?php echo wp_kses_post( apply_filters( 'tc_style_css_tr', $style_css_tr ) ); ?>>
                            <?php foreach ( $columns as $column ) { ?>
                                <?php do_action( 'tc_order_details_email_column_title_before_' . $column[ 'id' ] ); ?>
                                <th <?php echo wp_kses_post( apply_filters( 'tc_style_css_th', $style_css_td ) ); ?>><?php echo esc_html( $column[ 'field_title' ] ); ?></th>
                                <?php do_action( 'tc_order_details_email_column_title_after_' . $column[ 'id' ] ); ?>
                            <?php } ?>
                        </tr>
                        <?php
                        foreach ( $tickets as $ticket ) {
                            $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"'; ?>
                            <tr <?php echo wp_kses_post( $style ); ?> <?php echo wp_kses_post( apply_filters( 'tc_style_css_tr', $style_css_tr ) ); ?>>
                                <?php foreach ( $columns as $column ) { ?>
                                    <?php do_action( 'tc_order_details_email_column_value_before_' . $column[ 'id' ], $ticket ); ?>
                                    <td <?php echo wp_kses_post( apply_filters( 'tc_style_css_td', $style_css_td ) ); ?>>
                                        <?php
                                        if ( $column[ 'field_type' ] == 'function' ) {
                                            call_user_func( $column[ 'function' ], $column[ 'field_name' ], ( isset( $column[ 'field_id' ] ) ? $column[ 'field_id' ] : '' ), $ticket->ID );
                                        } else {
                                            if ( $column[ 'post_field_type' ] == 'post_meta' ) {
                                                echo esc_html( get_post_meta( $ticket->ID, $column[ 'field_name' ], true ) );
                                            }
                                            if ( $column[ 'post_field_type' ] == 'ID' ) {
                                                echo esc_html( $ticket->ID );
                                            }
                                        }
                                        ?>
                                    </td>
                                    <?php do_action( 'tc_order_details_email_column_value_after_' . $column[ 'id' ], $ticket ); ?>
                                <?php }
                                ?>
                            </tr>
                            <?php do_action( 'tc_order_details_email_additional_row', $ticket ); ?>
                            <?php
                        } ?>
                    </table>
                    <?php
                }
            }
            do_action( 'tc_get_order_details_email_tickets_table_after', $order_id );
        }

        if ( $return ) {
            return wpautop( ob_get_clean(), true );
        }
    }
}

/**
 * Render Order Detail Table in Frontend
 *
 * @param $order_id
 * @param bool $return
 * @return string
 *
 * Deprecated function "tc_order_details_table_front".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_order_details_table_front' ) ) {

    function tickera_order_details_table_front( $order_id, $return = false ) {

        if ( $return ) {
            ob_start();
        }

        $order = new \Tickera\TC_Order( $order_id );
        $order_is_paid = ( 'order_paid' == $order->details->post_status ) ? true : false;
        $order_is_paid = apply_filters( 'tc_order_is_paid', $order_is_paid, $order_id );

        if ( true == $order_is_paid ) {

            $tickets = get_posts( [
                    'posts_per_page' => -1,
                    'orderby' => 'post_date',
                    'order' => 'ASC',
                    'post_type' => 'tc_tickets_instances',
                    'post_parent' => $order->details->ID
                ]
            );

            $style = '';
            $orders = new \Tickera\TC_Orders();
            $columns = apply_filters( 'tc_front_ticket_table_columns', $orders->get_owner_info_fields_front() );
            $classes = apply_filters( 'tc_order_details_table_front_classes', 'order-details widefat shadow-table' );

            if ( apply_filters( 'tc_order_details_table_front_show_tickets_header', true ) == true ) {
                echo wp_kses_post( '<h2>' . __( 'Tickets', 'tickera-event-ticketing-system' ) . '</h2>' );
            }

            do_action( 'tc_order_details_table_front_before_table', $order_id, $tickets, $columns, $classes );
            ?>
        <table class="<?php echo esc_attr( $classes ); ?>">
            <tr>
                <?php foreach ( $columns as $column ) : ?>
                    <?php do_action( 'tc_order_details_table_front_column_title_before_' . $column[ 'id' ] ); ?>
                    <th><?php echo esc_html( $column[ 'field_title' ] ); ?></th>
                    <?php do_action( 'tc_order_details_table_front_column_title_after_' . $column[ 'id' ] ); ?>
                <?php endforeach; ?>
            </tr>
            <?php foreach ( $tickets as $ticket ) : ?>
                <?php $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"'; ?>
                <tr <?php echo wp_kses_post( sanitize_text_field( $style ) ); ?>>
                    <?php foreach ( $columns as $column ) : ?>
                        <?php do_action( 'tc_order_details_table_front_column_value_before_' . $column[ 'id' ], $ticket ); ?>
                        <td data-column="<?php echo esc_attr( $column[ 'field_title' ] ); ?>"><?php
                            if ( 'function' == $column[ 'field_type' ] ) {
                                $array_of_arguments = array();
                                $array_of_arguments[] = $column[ 'field_name' ];
                                $array_of_arguments[] = ( isset( $column[ 'field_id' ] ) ? $column[ 'field_id' ] : '' );
                                $array_of_arguments[] = $ticket->ID;
                                call_user_func_array( $column[ 'function' ], $array_of_arguments );

                            } else {

                                if ( 'post_meta' == $column[ 'post_field_type' ] ) {
                                    echo esc_html( get_post_meta( $ticket->ID, $column[ 'field_name' ], true ) );

                                } elseif ( 'ID' == $column[ 'post_field_type' ] ) {
                                    echo esc_html( $ticket->ID );
                                }
                            }
                            ?></td>
                        <?php do_action( 'tc_order_details_table_front_column_value_after_' . $column[ 'id' ], $ticket ); ?>
                    <?php endforeach; ?>
                </tr>
                <?php do_action( 'tc_order_details_table_front_additional_row', $ticket ); ?>
            <?php endforeach; ?>
            </table><?php
            do_action( 'tc_order_details_table_front_after_table', $order_id, $tickets, $columns, $classes );
        }

        if ( $return ) {
            return wpautop( ob_get_clean(), true );
        }
    }
}

/**
 * Render order details in Frontend
 *
 * @param string $order_id
 * @param string $order_key
 * @param bool $return
 * @return string
 *
 * Deprecated function "tc_get_order_details_front".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_details_front' ) ) {

    function tickera_get_order_details_front( $order_id = '', $order_key = '', $return = false ) {

        if ( $return ) {
            ob_start();
        }

        $tc_general_settings = get_option( 'tickera_general_setting', false );

        $order = new \Tickera\TC_Order( $order_id );
        $init_order_id = $order_id;

        // Key must match order creation date for security reasons
        if ( $order->details->tc_order_date == $order_key ) {

            switch ( $order->details->post_status ) {

                case 'order_received':
                    $order_status = __( 'Pending Payment', 'tickera-event-ticketing-system' );
                    break;

                case 'order_fraud':
                    $order_status = __( 'Under Review', 'tickera-event-ticketing-system' );
                    break;

                case 'order_paid':
                    $order_status = __( 'Payment Completed', 'tickera-event-ticketing-system' );
                    break;

                case 'trash':
                    $order_status = __( 'Order Deleted', 'tickera-event-ticketing-system' );
                    break;

                case 'order_cancelled':
                    $order_status = __( 'Order Cancelled', 'tickera-event-ticketing-system' );
                    break;

                case 'order_refunded':
                    $order_status = __( 'Order Refunded', 'tickera-event-ticketing-system' );
                    break;

                default:
                    $order_status = $order->details->post_status;
            }

            $fees_total = sanitize_text_field( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'fees_total' ] ) );
            $tax_total = sanitize_text_field( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'tax_total' ] ) );
            $subtotal = sanitize_text_field( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'subtotal' ] ) );
            $total = sanitize_text_field( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) );

            $transaction_id = isset( $order->details->tc_payment_info[ 'transaction_id' ] ) ? sanitize_text_field( $order->details->tc_payment_info[ 'transaction_id' ] ) : '';
            $order_id = strtoupper( $order->details->post_name );
            $order_date = $payment_date = apply_filters( 'tc_order_date', tickera_format_date( $order->details->tc_order_date, true ) );

            $discounts = new \Tickera\TC_Discounts();
            $discount_total = $discounts->get_discount_total_by_order( $order->details->ID );
            ?>
            <label id="order_title"><span class="order_details_title"><?php esc_html_e( 'Order: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $order_id ); ?></label>
            <label id="order_date"><span class="order_details_title"><?php esc_html_e( 'Order date: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $order_date ); ?></label>
            <label id="order_status"><span class="order_details_title"><?php esc_html_e( 'Order status: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $order_status ); ?></label>
            <?php if ( isset( $transaction_id ) && $transaction_id !== '' ) : ?>
                <label id="order_transaction_id"><span class="order_details_title"><?php esc_html_e( 'Transaction ID: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $transaction_id ); ?></label>
            <?php endif; ?>
            <label id="order_subtotal"><span class="order_details_title"><?php esc_html_e( 'Subtotal: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $subtotal ); ?></label>
            <?php if ( $discount_total !== 0 ) : ?>
                <?php $order_discount_code = get_post_meta( $order->details->ID, 'tc_discount_code', true ); ?>
                <label id="order_discount" class="tc_order_details_discount_value"><span class="order_details_title"><?php esc_html_e( 'Discount: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( tickera_get_order_discount_info( '', $order->details->ID ) ); ?></label>
                <label id="order_discount_code" class="tc_order_details_discount_code"><span class="order_details_title"><?php esc_html_e( 'Discount code: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $order_discount_code ); ?></label>
            <?php endif; ?>
            <?php if ( ! isset( $tc_general_settings[ 'show_fees' ] ) || isset( $tc_general_settings[ 'show_fees' ] ) && 'yes' == $tc_general_settings[ 'show_fees' ] ) : ?>
                <label id="order_fees"><span class="order_details_title"><?php echo esc_html( isset( $tc_general_settings[ 'fees_label' ] ) ? $tc_general_settings[ 'fees_label' ] : __( 'Fees', 'tickera-event-ticketing-system' ) ); ?></span> <?php echo esc_html( $fees_total ); ?></label>
            <?php endif; ?>
            <?php if ( ! isset( $tc_general_settings[ 'show_tax_rate' ] ) || isset( $tc_general_settings[ 'show_tax_rate' ] ) && 'yes' == $tc_general_settings[ 'show_tax_rate' ] ) : ?>
                <label id="order_tax_rate"><span class="order_details_title"><?php echo esc_html( isset( $tc_general_settings[ 'tax_label' ] ) ? $tc_general_settings[ 'tax_label' ] : __( 'Tax', 'tickera-event-ticketing-system' ) ); ?></span> <?php echo esc_html( $tax_total ); ?></label>
            <?php endif; ?>
            <hr/>
            <label id="order_total"><span class="order_details_title"><?php esc_html_e( 'Total: ', 'tickera-event-ticketing-system' ); ?></span> <?php echo esc_html( $total ); ?></label>
            <?php

            // Print Tickets Table if the return value is true.
            tickera_order_details_table_front( $init_order_id, ! $return );

        } else {
            esc_html_e( "You don't have required permissions to access this page.", 'tickera-event-ticketing-system' );
        }

        do_action( 'tc_after_order_details', $order_id );

        if ( $return ) {
            return wpautop( ob_get_clean(), true );
        }
    }
}

/**
 * Generate Tables for Order details
 * @param $order_id
 *
 * Deprecated function "tc_get_order_details_buyer_custom_fields".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_details_buyer_custom_fields' ) ) {

    function tickera_get_order_details_buyer_custom_fields( $order_id ) {

        $orders = new \Tickera\TC_Orders();
        $fields = \Tickera\TC_Orders::get_order_fields();
        $order = new \Tickera\TC_Order( (int) $order_id );
        $post_id = (int) $order_id;
        ?>

        <p class="form-field form-field-wide">
        <h4><?php esc_html_e( 'Buyer Extras', 'tickera-event-ticketing-system' ); ?></h4>

        <table class="order-table">
            <tbody>
            <?php foreach ( $fields as $field ) { ?>
                <?php if ( $orders->is_valid_order_field_type( $field[ 'field_type' ] ) ) { ?>

                    <tr valign="top">
                        <?php if ( $field[ 'field_type' ] !== 'separator' ) { ?>
                            <th scope="row"><label for="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"><?php echo esc_html( $field[ 'field_title' ] ); ?></label>
                            </th>
                        <?php } ?>
                        <td <?php echo esc_attr( $field[ 'field_type' ] == 'separator' ? 'colspan="2"' : '' ); ?>>
                            <?php
                            do_action( 'tc_before_orders_field_type_check' );
                            if ( $field[ 'field_type' ] == 'ID' ) {
                                echo esc_html( $order->details->{$field[ 'post_field_type' ]} );
                            }

                            if ( $field[ 'field_type' ] == 'function' ) {
                                $array_of_arguments = array();
                                $array_of_arguments[] = $field[ 'field_name' ];
                                if ( isset( $post_id ) ) {
                                    $array_of_arguments[] = $post_id;
                                }
                                if ( isset( $field[ 'id' ] ) ) {
                                    $array_of_arguments[] = $field[ 'id' ];
                                }

                                call_user_func_array( $field[ 'function' ], $array_of_arguments );
                            }
                            ?>
                            <?php if ( $field[ 'field_type' ] == 'text' ) { ?>
                                <input type="text" class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?>"
                                       value="<?php
                                       if ( isset( $order ) ) {
                                       if ( $field[ 'post_field_type' ] == 'post_meta' ) {
                                           echo esc_attr( isset( $order->details->{$field[ 'field_name' ]} ) ? $order->details->{$field[ 'field_name' ]} : '' );
                                       } else {
                                           echo esc_attr( $order->details->{$field[ 'post_field_type' ]} );
                                       } ?>" id="<?php echo esc_attr( $field[ 'field_name' ] );
                                } ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>">
                            <?php } ?>
                            <?php if ( $field[ 'field_type' ] == 'separator' ) { ?>
                                <hr/>
                            <?php } ?>
                            <?php do_action( 'tc_after_orders_field_type_check' ); ?>
                        </td>
                    </tr>
                    <?php
                }
            }
            do_action( 'tc_after_order_details_fields' );
            ?>
            </tbody>
        </table>
        </p>
        <?php
    }
}

/**
 * Generates Table for Ticket Instance(s) and contents
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_order_event".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_event' ) ) {

    function tickera_get_order_event( $field_name = '', $post_id = '' ) {

        $order_status = get_post_status( $post_id );

        $order_status = $order_status == 'trash' ? 'trash' : 'publish';

        $orders = new \Tickera\TC_Orders();

        $user_id = get_current_user_id();
        $order_id = get_the_title( $post_id );
        $cart_contents = get_post_meta( $post_id, 'tc_cart_contents', true );
        $cart_info = get_post_meta( $post_id, 'tc_cart_info', true );
        $owner_data = isset( $cart_info[ 'owner_data' ] ) ? $cart_info[ 'owner_data' ] : array();
        $tickets = count( $cart_contents );

        $args = array(
            'posts_per_page' => -1,
            'orderby' => 'post_date',
            'order' => 'ASC',
            'post_type' => 'tc_tickets_instances',
            'post_status' => $order_status, //array( 'trash', 'publish' ),
            'post_parent' => $post_id
        );

        $tickets = get_posts( $args );
        $columns = $orders->get_owner_info_fields();
        $columns = apply_filters( 'tc_order_details_owner_columns', $columns );
        $style = '';
        ?>
        <table class="order-details widefat shadow-table">
            <tr>
                <?php
                foreach ( $columns as $column ) { ?>
                    <th><?php echo esc_html( $column[ 'field_title' ] ); ?></th>
                <?php } ?>
            </tr>
            <?php
            $ticket_summary_fields = [ 'ticket_subtotal', 'ticket_discount', 'ticket_fee', 'ticket_tax', 'ticket_total' ];
            foreach ( $tickets as $ticket ) {
                $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';  ?>
                <tr <?php echo wp_kses( $style, [ 'class' => [] ] ); ?>>
                    <?php foreach ( $columns as $column ) { ?>
                        <td class="<?php echo esc_attr( $column[ 'field_name' ] ); ?>"
                            data-id="<?php echo esc_attr( $column[ 'field_name' ] == 'ID' ? (int) $ticket->ID : '' ); ?>">
                            <?php
                            if ( $column[ 'field_type' ] == 'function' ) {
                                call_user_func( $column[ 'function' ], $column[ 'field_name' ], ( isset( $column[ 'field_id' ] ) ? $column[ 'field_id' ] : '' ), $ticket->ID );

                            } else {
                                if ( $column[ 'post_field_type' ] == 'post_meta' ) {
                                    $value = get_post_meta( $ticket->ID, $column[ 'field_name' ], true );

                                    if ( in_array( $column[ 'field_name' ], $ticket_summary_fields ) ) {
                                        $value = esc_html( apply_filters( 'tc_cart_currency_and_format', $value ) );
                                    }

                                    if ( empty( $value ) ) {
                                        echo esc_html( '-' );
                                    } else {
                                        echo esc_html( $value );
                                    }
                                }
                                if ( $column[ 'post_field_type' ] == 'ID' ) {
                                    echo esc_html( $ticket->ID );
                                }
                            }
                            ?>
                        </td>
                    <?php }
                    ?>
                </tr>
                <?php
            }
            ?>
        </table>
        <?php if ( count( $tickets ) == 0 && count( $cart_contents ) > 0 ) { ?>
            <div class="tc_order_tickets_warning">
            <?php esc_html_e( 'We can\'t find any ticket associated with this order. It seems that attendee info / ticket is deleted.', 'tickera-event-ticketing-system' ); ?>
            </div><?php
        }
    }
}

/**
 * Deprecated function "tc_get_order_date".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_date' ) ) {

    function tickera_get_order_date( $field_name = '', $post_id = '' ) {
        $value = get_post_meta( $post_id, $field_name, true );
        echo esc_html( tickera_format_date( $value ) );
    }
}

/**
 * Deprecated function "tc_get_order_tickets_info".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_tickets_info' ) ) {
    function tickera_get_order_tickets_info( $field_name = '', $post_id = '' ) {}
}

/**
 * Deprecated function "tc_get_order_gateway".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_gateway' ) ) {

    function tickera_get_order_gateway( $field_name = '', $post_id = '' ) {
        $order = new \Tickera\TC_Order( $post_id );
        echo esc_html( $order->details->tc_cart_info[ 'gateway_admin_name' ] );
    }
}

/**
 * Deprecated function "tc_get_order_transaction_id".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_transaction_id' ) ) {

    function tickera_get_order_transaction_id( $field_name = '', $post_id = '' ) {
        $order = new \Tickera\TC_Order( $post_id );
        echo esc_html( $order->details->tc_payment_info[ 'transaction_id' ] );
    }
}

/**
 * Deprecated function "tc_get_order_discount_info".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_discount_info' ) ) {

    function tickera_get_order_discount_info( $field_name = '', $post_id = '' ) {
        $discounts = new \Tickera\TC_Discounts();
        $discount_total = $discounts->get_discount_total_by_order( $post_id );
        echo esc_html( ( $discount_total > 0 ) ? esc_html( apply_filters( 'tc_cart_currency_and_format', $discount_total ) ) : '-' );
    }
}

/**
 * Deprecated function "tc_get_order_total".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_total' ) ) {

    function tickera_get_order_total( $field_name = '', $post_id = '' ) {
        global $tc;
        $order = new \Tickera\TC_Order( $post_id );
        echo esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) );
    }
}

/**
 * Deprecated function "tc_get_order_subtotal".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_subtotal' ) ) {

    function tickera_get_order_subtotal( $field_name = '', $post_id = '' ) {
        global $tc;
        $order = new \Tickera\TC_Order( $post_id );
        echo esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'subtotal' ] ) );
    }
}

/**
 * Deprecated function "tc_get_order_fees_total".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_fees_total' ) ) {

    function tickera_get_order_fees_total( $field_name = '', $post_id = '' ) {
        global $tc;
        $order = new \Tickera\TC_Order( $post_id );
        echo esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'fees_total' ] ) );
    }
}

/**
 * Deprecated function "tc_get_order_tax_total".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_tax_total' ) ) {

    function tickera_get_order_tax_total( $field_name = '', $post_id = '' ) {
        global $tc;
        $order = new \Tickera\TC_Order( $post_id );
        echo esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'tax_total' ] ) );
    }
}

/**
 * Deprecated function "tc_resend_order_confirmation_email".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_resend_order_confirmation_email' ) ) {

    function tickera_resend_order_confirmation_email( $field_name = '', $post_id = '' ) {
        if ( get_post_status( $post_id ) == 'order_paid' ) {
            global $tc;
            echo wp_kses_post( '<a href="#" id="tc_order_resend_confirmation_email">' . esc_html__( 'Resend order confirmation e-mail', 'tickera-event-ticketing-system' ) . '</a>' );
        }
    }
}

/**
 * Deprecated function "tc_order_ipn_messages".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_order_ipn_messages' ) ) {

    function tickera_order_ipn_messages( $field_name = '', $post_id = '' ) {

        $notes = \Tickera\TC_Order::get_order_notes( $post_id );
        if ( isset( $notes ) && isset( $notes[ 'tc_order_notes' ] ) && count( $notes[ 'tc_order_notes' ] ) > 0 ) {
            ?>
            <div class="tc_order_notes_title"><?php esc_html_e( 'Order Notes:', 'tickera-event-ticketing-system' ); ?></div>
            <ul class="tc_order_notes">
                <?php
                foreach ( $notes[ 'tc_order_notes' ] as $note ) {
                    ?>
                    <li rel="<?php echo esc_attr( absint( $note[ 'id' ] ) ); ?>" class="note">
                        <div class="note_content">
                            <?php echo wp_kses_post( wpautop( wptexturize( $note[ 'note' ] ) ) ); ?>
                        </div>
                        <p class="meta">
                            <abbr class="exact-date" title="<?php echo esc_attr( $note[ 'created_at' ] ); ?>"><?php echo esc_html( $note[ 'note_author' ] ); ?>, <?php echo esc_html( $note[ 'created_at' ] ); ?></abbr>
                        </p>
                    </li>
                    <?php
                }
                ?>
            </ul><!--tc_order_notes-->
            <?php
        }
    }
}

/**
 * Deprecated function "tc_get_order_download_tickets_link".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_order_download_tickets_link' ) ) {
    function tickera_get_order_download_tickets_link( $field_name = '', $post_id = '' ) {}
}

/**
 * Deprecated function "tc_get_ticket_type_form_field".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_type_form_field' ) ) {

    function tickera_get_ticket_type_form_field( $field_name, $field_type, $ticket_type_id, $ticket_type_count ) { ?>
        <div class="tc-hidden"><input type="hidden" name="owner_data_<?php echo esc_attr( $field_name . '_' . $field_type ); ?>[<?php echo esc_attr( (int) $ticket_type_id ); ?>][]" value="<?php echo esc_attr( (int) $ticket_type_id ); ?>"/></div>
    <?php }
}

/**
 * Render ticket fees drop down field in better ticket types
 * Admin Dashboard: Tickera Ticket Types
 *
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_ticket_fee_type".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_fee_type' ) ) {

    function tickera_get_ticket_fee_type( $field_name = '', $post_id = '' ) {
        $currently_selected = ( $post_id !== '' ) ? get_post_meta( $post_id, $field_name, true ) : ''; ?>
    <select name="<?php echo esc_attr( $field_name ); ?>_post_meta">
        <option value="fixed" <?php selected( $currently_selected, 'fixed', true ); ?>><?php esc_html_e( 'Fixed', 'tickera-event-ticketing-system' ); ?></option>
        <option value="percentage" <?php selected( $currently_selected, 'percentage', true ); ?>><?php esc_html_e( 'Percentage', 'tickera-event-ticketing-system' ); ?></option>
        </select><?php
    }
}

/**
 * Event dates in Post metabox.
 * Previously in publish section.
 *
 * @param string $field_name
 * @param string $post_id
 * @param string $field
 * @since 3.5.1.2
 *
 * Deprecated function "tc_get_event_dates_fields".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_event_dates_fields' ) ) {

    function tickera_get_event_dates_fields( $field_name = '', $post_id = '', $field = '' ) {
        $event = new \Tickera\TC_Event( $post_id );
        $start = isset( $event->details ) ? $event->details->event_date_time : '';
        $end = isset( $event->details ) ? $event->details->event_end_date_time : '';
        ?>
        <label class="tc-metabox-field event_date_time">
            <div>
                <span><?php esc_html_e( 'Start date & time', 'tickera-event-ticketing-system' ); ?></span>
                <input type="text" class="regular-text" value="<?php echo esc_attr( $start ); ?>" id="event_date_time" name="event_date_time_post_meta" placeholder="">
            </div>
        </label>
        <br/>
        <label class="tc-metabox-field event_end_date_time">
            <div>
                <span><?php esc_html_e( 'End date & time', 'tickera-event-ticketing-system' ); ?></span>
                <input type="text" class="regular-text" value="<?php echo esc_attr( $end ); ?>" id="event_end_date_time" name="event_end_date_time_post_meta" placeholder="">
            </div>
        </label>
    <?php }
}

/**
 * Event Misc in Post metabox.
 * Previously in publish section.
 *
 * Fields:
 * Show tickets automatically
 * Hide event after expiration
 *
 * @param string $field_name
 * @param string $post_id
 * @param string $field
 * @since 3.5.1.2
 *
 * Deprecated function "tc_get_event_misc_fields".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_event_misc_fields' ) ) {

    function tickera_get_event_misc_fields( $field_name = '', $post_id = '', $field = '' ) {
        $show_tickets_automatically = (int) get_post_meta( $post_id, 'show_tickets_automatically', true );
        $hide_event_after_expiration = (int) get_post_meta( $post_id, 'hide_event_after_expiration', true );
        ?>
        <label class="tc-metabox-field" for="show_tickets_automatically">
            <input type="checkbox" id="show_tickets_automatically" name="show_tickets_automatically_post_meta" value="1" <?php checked( $show_tickets_automatically, true, true ); ?> />
            <span><?php esc_html_e( 'Show tickets automatically', 'tickera-event-ticketing-system' ); ?></span>
        </label>
        <br/>
        <label class="tc-metabox-field" for="hide_event_after_expiration">
            <input type="checkbox" id="hide_event_after_expiration" name="hide_event_after_expiration_post_meta" value="1" <?php checked( $hide_event_after_expiration, true, true ); ?> />
            <?php esc_html_e( 'Hide event after expiration', 'tickera-event-ticketing-system' ); ?>
        </label>
    <?php }
}

/**
 * Deprecated function "get_limit_checkins_fields".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_limit_checkins_fields' ) ) {

    function tickera_get_limit_checkins_fields( $field_name = '', $post_id = '' ) {

        $enable = get_post_meta( $post_id, apply_filters( 'tc_checkins_time_basis_field_name', $field_name, $post_id ), true );
        $enable = ( $enable ) ? sanitize_key( $enable ) : 'no';

        $time_basis = get_post_meta( $post_id, apply_filters( 'tc_checkins_time_basis_type_field_name', 'checkins_time_basis_type', $post_id ), true );
        $time_basis = ( $time_basis ) ? $time_basis : 'hour';

        $calendar_basis = get_post_meta( $post_id, apply_filters( 'tc_checkins_time_calendar_basis_field_name', 'checkins_time_calendar_basis', $post_id ), true );
        $calendar_basis = $calendar_basis ? $calendar_basis : 'no';

        $allowed_checkin = get_post_meta( $post_id, apply_filters( 'tc_allowed_checkins_per_time_basis_field_name', 'allowed_checkins_per_time_basis', $post_id ), true );
        ?>
        <label><input type="radio" name="<?php echo esc_attr( $field_name ) . '_post_meta'; ?>" class="<?php echo esc_attr( $field_name ) . '_post_meta'; ?> has_conditional" value="yes" <?php checked( $enable, 'yes', true ); ?>/><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?></label>
        <label><input type="radio" name="<?php echo esc_attr( $field_name ) . '_post_meta'; ?>" class="<?php echo esc_attr( $field_name ) . '_post_meta'; ?> has_conditional" value="no" <?php checked( $enable, 'no', true ); ?>/><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?></label><br/>
        <label class="tc_conditional" data-condition-field_name="<?php echo esc_attr( $field_name ) . '_post_meta'; ?>" data-condition-field_type="radio" data-condition-value="no" data-condition-action="hide">
            <br/>
            <input type="number" id="allowed_checkins_per_time_basis_field" name="allowed_checkins_per_time_basis_post_meta" value="<?php echo esc_attr( $allowed_checkin ); ?>" number="true" placeholder="<?php esc_html_e( 'Unlimited', 'tickera-event-ticketing-system' ); ?>"/>
            <span><?php esc_html_e( 'Check-ins per: ', 'tickera-event-ticketing-system' ); ?></span>
            <select name="checkins_time_basis_type_post_meta" class="checkins_time_basis_type_post_meta has_conditional">
                <option value="hour" <?php selected( $time_basis, 'hour', true ); ?>><?php esc_html_e( 'Hour', 'tickera-event-ticketing-system' ); ?></option>
                <option value="day" <?php selected( $time_basis, 'day', true ); ?>><?php esc_html_e( 'Day', 'tickera-event-ticketing-system' ); ?></option>
                <option value="week" <?php selected( $time_basis, 'week', true ); ?>><?php esc_html_e( 'Week', 'tickera-event-ticketing-system' ); ?></option>
                <option value="month" <?php selected( $time_basis, 'month', true ); ?>><?php esc_html_e( 'Month', 'tickera-event-ticketing-system' ); ?></option>
            </select>
            <select name="checkins_time_calendar_basis_post_meta" class="tc_conditional" data-condition-field_name="checkins_time_basis_type_post_meta" data-condition-field_type="select" data-condition-value="hour" data-condition-action="hide">
                <option value="no" <?php selected( $calendar_basis, 'no', true ); ?>><?php esc_html_e( '24 hours basis', 'tickera-event-ticketing-system' ); ?></option>
                <option value="yes" <?php selected( $calendar_basis, 'yes', true ); ?>><?php esc_html_e( 'Calendar day basis (resets at midnight)', 'tickera-event-ticketing-system' ); ?></option>
            </select>
        </label>
    <?php }
}

/**
 * Render fields for ticket selling dates.
 * Admin Dashboard > Tickera > Ticket Types
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_ticket_availability_dates".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_availability_dates' ) ) {

    function tickera_get_ticket_availability_dates( $field_name = '', $post_id = '' ) {

        if ( $post_id !== '' ) {

            // ticket_availability ( Could be open_ended, range )
            $currently_selected = get_post_meta( $post_id, $field_name, true );
            if ( empty( $currently_selected ) )
                $currently_selected = 'open_ended';

            $from_date = get_post_meta( $post_id, '_ticket_availability_from_date', true );
            $to_date = get_post_meta( $post_id, '_ticket_availability_to_date', true );

        } else {

            $currently_selected = 'open_ended';
            $from_date = '';
            $to_date = '';
        } ?>
        <label><input type="radio" name="_ticket_availability_post_meta" value="open_ended" <?php checked( $currently_selected, 'open_ended', true ); ?> /><?php esc_html_e( 'Open-ended', 'tickera-event-ticketing-system' ); ?></label><br/><br/>
        <label><input type="radio" name="_ticket_availability_post_meta" value="range" <?php checked( $currently_selected, 'range', true ); ?> /><?php esc_html_e( 'During selected date range', 'tickera-event-ticketing-system' ); ?></label><br/><br/>
        <label><?php esc_html_e( 'From', 'tickera-event-ticketing-system' ); ?> <input type="text" value="<?php echo esc_attr( $from_date ); ?>" name="_ticket_availability_from_date_post_meta" class="tc_date_field"/></label>
        <label><?php esc_html_e( 'To', 'tickera-event-ticketing-system' ); ?> <input type="text" value="<?php echo esc_attr( $to_date ); ?>" name="_ticket_availability_to_date_post_meta" class="tc_date_field"/></label><?php
    }
}

/**
 * Render fields for ticket's available checkin dates.
 * Admin Dashboard: Tickera Ticket Types
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_ticket_checkin_availability_dates".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_checkin_availability_dates' ) ) {

    function tickera_get_ticket_checkin_availability_dates( $field_name = '', $post_id = '' ) {

        if ( $post_id !== '' ) {

            // ticket_checkin_availability ( Could be open_ended, range )
            $currently_selected = get_post_meta( $post_id, $field_name, true );
            if ( empty( $currently_selected ) ) {
                $currently_selected = 'open_ended';
            }

            $from_date = get_post_meta( $post_id, '_ticket_checkin_availability_from_date', true );
            $to_date = get_post_meta( $post_id, '_ticket_checkin_availability_to_date', true );

        } else {
            $currently_selected = 'open_ended';
            $from_date = '';
            $to_date = '';
        }

        $days_selected = get_post_meta( $post_id, '_time_after_order_days', true );
        $hours_selected = get_post_meta( $post_id, '_time_after_order_hours', true );
        $minutes_selected = get_post_meta( $post_id, '_time_after_order_minutes', true );
        $days_selected_after_checkin = get_post_meta( $post_id, '_time_after_first_checkin_days', true );
        $hours_selected_after_checkin = get_post_meta( $post_id, '_time_after_first_checkin_hours', true );
        $minutes_selected_after_checkin = get_post_meta( $post_id, '_time_after_first_checkin_minutes', true ); ?>

        <label><input type="radio" name="_ticket_checkin_availability_post_meta" value="open_ended" <?php checked( $currently_selected, 'open_ended', true ); ?> /><?php esc_html_e( 'Open-ended', 'tickera-event-ticketing-system' ); ?></label><br/><br/>
        <label><input type="radio" name="_ticket_checkin_availability_post_meta" value="range" <?php checked( $currently_selected, 'range', true ); ?> /><?php esc_html_e( 'During selected date range', 'tickera-event-ticketing-system' ); ?></label><br/><br/>
        <label><?php esc_html_e( 'From', 'tickera-event-ticketing-system' ); ?> <input type="text" value="<?php echo esc_attr( $from_date ); ?>" name="_ticket_checkin_availability_from_date_post_meta" class="tc_date_field"/></label>
        <label><?php esc_html_e( 'To', 'tickera-event-ticketing-system' ); ?> <input type="text" value="<?php echo esc_attr( $to_date ); ?>" name="_ticket_checkin_availability_to_date_post_meta" class="tc_date_field"/></label><br/><br/>
        <label><input type="radio" name="_ticket_checkin_availability_post_meta" value="time_after_order" <?php checked( $currently_selected, 'time_after_order', true ); ?> /><?php esc_html_e( 'Within the following time after order is placed', 'tickera-event-ticketing-system' ); ?></label><br/><br/>
        <label><?php esc_html_e( 'Days', 'tickera-event-ticketing-system' ); ?>
            <select name="_time_after_order_days_post_meta" id="time_after_order_days">
                <?php for ( $day = apply_filters( 'tc_ticket_checkin_availability_time_after_order_day_min', 0 ); $day <= apply_filters( 'tc_ticket_checkin_availability_time_after_order_day_max', 365 ); $day++ ) { ?>
                    <option value="<?php echo esc_attr( $day ); ?>" <?php selected( $day, $days_selected, true ); ?>><?php echo esc_html( $day ); ?></option>
                <?php } ?>
            </select>
        </label>
        <label>
            <?php esc_html_e( 'Hours', 'tickera-event-ticketing-system' ); ?>
            <select name="_time_after_order_hours_post_meta" id="time_after_order_hours">
                <?php for ( $hour = apply_filters( 'tc_ticket_checkin_availability_time_after_order_hour_min', 0 ); $hour <= apply_filters( 'tc_ticket_checkin_availability_time_after_order_hour_max', 24 ); $hour++ ) { ?>
                    <option value="<?php echo esc_attr( $hour ); ?>" <?php selected( $hour, $hours_selected, true ); ?>><?php echo esc_html( $hour ); ?></option>
                <?php } ?>
            </select>
        </label>
        <label>
            <?php esc_html_e( 'Minutes', 'tickera-event-ticketing-system' ); ?>
            <select name="_time_after_order_minutes_post_meta" id="time_after_order_minutes">
                <?php for ( $minute = apply_filters( 'tc_ticket_checkin_availability_time_after_order_minute_min', 0 ); $minute <= apply_filters( 'tc_ticket_checkin_availability_time_after_order_minute_max', 60 ); $minute++ ) { ?>
                    <option value="<?php echo esc_attr( $minute ); ?>" <?php selected( $minute, $minutes_selected, true ); ?>><?php echo esc_html( $minute ); ?></option>
                <?php } ?>
            </select>
        </label><br/><br/>
        <label><input type="radio" name="_ticket_checkin_availability_post_meta" value="time_after_first_checkin" <?php checked( $currently_selected, 'time_after_first_checkin', true ); ?> /><?php esc_html_e( 'Within the following time after first check-in', 'tickera-event-ticketing-system' ); ?></label><br/><br/>
        <label>
            <?php esc_html_e( 'Days', 'tickera-event-ticketing-system' ); ?>
            <select name="_time_after_first_checkin_days_post_meta" id="time_after_first_checkin_days">
                <?php for ( $day = apply_filters( 'tc_ticket_checkin_availability_time_after_first_checkin_day_min', 0 ); $day <= apply_filters( 'tc_ticket_checkin_availability_time_after_first_checkin_day_max', 365 ); $day++ ) { ?>
                    <option value="<?php echo esc_attr( $day ); ?>" <?php selected( $day, $days_selected_after_checkin, true ); ?>><?php echo esc_html( $day ); ?></option>
                <?php } ?>
            </select>
        </label>
        <label>
            <?php esc_html_e( 'Hours', 'tickera-event-ticketing-system' ); ?>
            <select name="_time_after_first_checkin_hours_post_meta" id="time_after_first_checkin_hours">G
                <?php for ( $hour = apply_filters( 'tc_ticket_checkin_availability_time_after_first_checkin_hour_min', 0 ); $hour <= apply_filters( 'tc_ticket_checkin_availability_time_after_first_checkin_hour_max', 24 ); $hour++ ) { ?>
                    <option value="<?php echo esc_attr( $hour ); ?>" <?php selected( $hour, $hours_selected_after_checkin, true ); ?>><?php echo esc_html( $hour ); ?></option>
                <?php } ?>
            </select>
        </label>
        <label>
            <?php esc_html_e( 'Minutes', 'tickera-event-ticketing-system' ); ?>
            <select name="_time_after_first_checkin_minutes_post_meta" id="time_after_first_checkin_minutes">
                <?php for ( $minute = apply_filters( 'tc_ticket_checkin_availability_time_after_first_checkin_minute_min', 0 ); $minute <= apply_filters( 'tc_ticket_checkin_availability_time_after_first_checkin_minute_max', 60 ); $minute++ ) { ?>
                    <option value="<?php echo esc_attr( $minute ); ?>" <?php selected( $minute, $minutes_selected_after_checkin, true ); ?>><?php echo esc_html( $minute ); ?></option>
                <?php } ?>
            </select>
        </label><br/><br/>
        <label><input type="radio" name="_ticket_checkin_availability_post_meta" value="upon_event_starts" <?php checked( $currently_selected, 'upon_event_starts', true ); ?> /><?php esc_html_e( 'When the event starts', 'tickera-event-ticketing-system' ); ?></label><br/><br/><?php
    }
}

/**
 * Render field for Ticket's check-out availability.
 * Admin Dashboard: Tickera Ticket Types
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_ticket_allow_checkouts".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_allow_checkouts' ) ) {

    function tickera_get_ticket_allow_checkouts( $field_name = '', $post_id = '' ) {
        $currently_selected = ( $post_id !== '' ) ? get_post_meta( $post_id, $field_name, true ) : 'no';
        $currently_selected = ( $currently_selected ) ? $currently_selected : 'no'; ?>
        <label for="<?php echo esc_attr( $field_name ); ?>"><input type="radio" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>_post_meta" value="yes" <?php checked( $currently_selected, 'yes', true ); ?>/><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ) ?></label>
    <label for="<?php echo esc_attr( $field_name ); ?>"><input type="radio" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>_post_meta" value="no" <?php checked( $currently_selected, 'no', true ); ?>/><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ) ?></label><?php
    }
}

/**
 * Deprecated function "tc_get_ticket_templates_array".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_templates_array' ) ) {

    function tickera_get_ticket_templates_array() {

        $ticket_templates = array();
        $wp_templates_search = new \Tickera\TC_Templates_Search( '', '', -1 );

        foreach ( $wp_templates_search->get_results() as $template ) {
            $template_obj = new \Tickera\TC_Event( $template->ID );
            $template_object = $template_obj->details;
            $ticket_templates[ $template_object->ID ] = $template_object->post_title;
        }

        return $ticket_templates;
    }
}

/**
 * Render ticket's template dropdown field in better ticket types.
 * Admin Dashboard: Tickera Ticket Types
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_ticket_templates".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_templates' ) ) {

    function tickera_get_ticket_templates( $field_name = '', $post_id = '' ) {

        $wp_templates_search = new \Tickera\TC_Templates_Search( '', '', -1 );
        $currently_selected = ( $post_id !== '' ) ? get_post_meta( $post_id, $field_name, true ) : ''; ?>
        <select name="<?php echo esc_attr( $field_name ); ?>_post_meta">
            <?php foreach ( $wp_templates_search->get_results() as $template ) {
                $template_obj = new \Tickera\TC_Event( $template->ID );
                $template_object = $template_obj->details; ?>
                <option value="<?php echo esc_attr( (int) $template_object->ID ); ?>" <?php selected( $currently_selected, $template_object->ID, true ); ?>><?php echo esc_html( $template_object->post_title ); ?></option>
            <?php } ?>
        </select>
        <?php if ( isset( $_GET[ 'ID' ] ) ) {
            $ticket = new \Tickera\TC_Ticket( (int) $_GET[ 'ID' ] );
            $template_id = $ticket->details->ticket_template; ?>
            <a class="ticket_preview_link" target="_blank"
               href="<?php echo esc_url( apply_filters( 'tc_ticket_preview_link', admin_url( 'edit.php?post_type=tc_events&page=tc_ticket_templates&action=preview&ticket_type_id=' . (int) $_GET[ 'ID' ] ) . '&template_id=' . $template_id ) ); ?>"><?php esc_html_e( 'Preview', 'tickera-event-ticketing-system' ); ?></a>
        <?php }
    }
}

/**
 * Render Events Drop Down
 * @param string $field_name
 * @param string $post_id
 * @param bool $multi_select
 *
 * Deprecated function "tc_get_api_keys_events".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_api_keys_events' ) ) {

    function tickera_get_api_keys_events( $field_name = '', $post_id = '', $multi_select = false ) {

        $multi_select = ( $multi_select ) ? 'multiple' : '';
        $placeholder = ( $multi_select ) ? __( 'Choose any events', 'tickera-event-ticketing-system' ) : '';

        $wp_events_search =  new \Tickera\TC_Events_Search( '', '', -1 );
        $terms = get_terms( [ 'taxonomy' => 'event_category', 'hide_empty' => false ] );

        $selected = ( $post_id !== '' ) ? (array) get_post_meta( $post_id, $field_name, true ) : [];

        /*
         * Identify if dynamic field
         * Convert selected values with the latest format
         */
        $currently_selected = ( ! is_array( reset( $selected ) ) ) ? [ 'all' => $selected ] : $selected;
        $current_event_ids = reset( $currently_selected );
        $current_term_id = array_keys( $currently_selected )[ 0 ];
        ?>
        <!-- Event Category Field -->
        <select id="tc-event-category-field" class="regular-text dynamic-field-parent">
            <option value="all" <?php selected( $current_term_id, 'all', true ); ?>><?php esc_html_e( 'All Event Categories', 'tickera-event-ticketing-system' ); ?></option>
            <?php foreach ( $terms as $term ) : ?>
                <option value="<?php echo esc_attr( (int) $term->term_id ); ?>" <?php selected( $current_term_id, (int) $term->term_id, true ); ?>><?php echo esc_html( $term->name ); ?></option>
            <?php endforeach; ?>
        </select>
        <br/>
        <!-- Event Name Field -->
        <select id="tc-event-name-field" class="regular-text dynamic-field-child" name="<?php echo esc_attr( $field_name ); ?>_post_meta[<?php echo esc_attr( $current_term_id ); ?>][]" data-placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php echo esc_attr( $multi_select ); ?>>
            <option value="all" <?php selected( true, ( ! $current_event_ids || in_array( 'all', $current_event_ids ) ), true ); ?>><?php esc_html_e( 'All Events', 'tickera-event-ticketing-system' ); ?></option>
            <?php foreach ( $wp_events_search->get_results() as $event ) : ?>
                <?php
                $event_terms = get_the_terms( $event->ID, 'event_category' );
                $event_title = get_the_title( $event->ID );

                if ( 'all' == $current_term_id ) : ?>
                    <option value="<?php echo esc_attr( (int) $event->ID ); ?>" <?php selected( true, ( in_array( $event->ID, $current_event_ids ) && ! in_array( 'all', $current_event_ids ) ), true ); ?>><?php echo esc_html( $event_title ); ?></option>

                <?php else :
                    foreach ( (array) $event_terms as &$term ) {
                        if ( isset( $term->term_id ) && $current_term_id == $term->term_id ) : ?>
                            <option value="<?php echo esc_attr( (int) $event->ID ); ?>" <?php selected( true, ( in_array( $event->ID, $current_event_ids ) && ! in_array( 'all', $current_event_ids ) ), true ); ?>><?php echo esc_html( $event->post_title ); ?></option><?php
                            break;
                        endif;
                    }
                endif; ?>
            <?php endforeach; ?>
        </select>
        <?php
    }
}

/**
 * Deprecated function "tc_api_get_site_url".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_api_get_site_url' ) ) {

    function tickera_api_get_site_url( $field_name = '', $post_id = '' ) {
        echo wp_kses_post( '<a href="' . esc_url( trailingslashit( site_url() ) ) . '">' . trailingslashit( site_url() ) . '</a>' );
    }
}

/**
 * Deprecated function "tc_ticket_limit_types".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_ticket_limit_types' ) ) {

    function tickera_ticket_limit_types( $field_name = '', $post_id = '' ) {
        if ( $post_id !== '' ) {
            $currently_selected = get_post_meta( $post_id, $field_name, true );
        } else {
            $currently_selected = '';
        }
        ?>
        <select name="<?php echo esc_attr( $field_name ); ?>_post_meta" id="tickets_limit_type">
            <option value="ticket_level" <?php selected( $currently_selected, 'ticket_level', true ); ?>><?php echo esc_html__( 'Ticket Type', 'tickera-event-ticketing-system' ); ?></option>
            <option value="event_level" <?php selected( $currently_selected, 'event_level', true ); ?>><?php echo esc_html__( 'Event', 'tickera-event-ticketing-system' ); ?></option>
        </select>
        <?php
    }
}

/**
 * Deprecated function "tc_get_quantity_sold".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_quantity_sold' ) ) {

    function tickera_get_quantity_sold( $field_name = '', $post_id = '' ) {
        return $post_id;
    }
}

/**
 * Collect a list of events in an array
 * @param string $field_name
 * @param string $post_id
 * @return array
 *
 * Deprecated function "tc_get_events_array".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_events_array' ) ) {

    function tickera_get_events_array( $field_name = '', $post_id = '' ) {

        $events = array();
        $wp_events_search = new \Tickera\TC_Events_Search( '', '', '-1' );

        foreach ( $wp_events_search->get_results() as $event ) {
            $event_obj = new \Tickera\TC_Event( $event->ID );
            $event_object = $event_obj->details;
            $events[ $event_object->ID ] = apply_filters( 'tc_event_select_name', $event_object->post_title, $event_object->ID );
        }

        return $events;
    }
}

/**
 * Generate a dropdown menu with a list of events
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_events".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_events' ) ) {

    function tickera_get_events( $field_name = '', $post_id = '' ) {
        $wp_events_search =  new \Tickera\TC_Events_Search( '', '', '-1' );
        if ( $post_id !== '' ) {
            $currently_selected = get_post_meta( $post_id, $field_name, true );
        } else {
            $currently_selected = '';
        }

        $disable_if_selected = apply_filters( 'tc_disable_event_selection_for_ticket_types_if_selected_already', false, $post_id, $currently_selected );
        ?>
        <select name="<?php echo esc_attr( $field_name ); ?>_post_meta" <?php echo esc_attr( $disable_if_selected ? 'disabled="disabled"' : '' ); ?>>
            <?php foreach ( $wp_events_search->get_results() as $event ) {
                $event_obj = new \Tickera\TC_Event( $event->ID );
                $event_object = $event_obj->details; ?>
                <option value="<?php echo esc_attr( (int) $event_object->ID ); ?>" <?php selected( $currently_selected, $event_object->ID, true ); ?>><?php echo esc_html( apply_filters( 'tc_event_select_name', $event_object->post_title, $event_object->ID ) ); ?></option>
            <?php } ?>
        </select>
        <?php
    }
}

/**
 * Get Event Level Dropdown Option (Used in Tickera Admin Event Page)
 * @param string $field_name
 * @param $post_id
 *
 * Deprecated function "tc_get_event_limit_level_option".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_event_limit_level_option' ) ) {

    function tickera_get_event_limit_level_option( $field_name, $post_id ) {

        $field_name = esc_attr( $field_name );
        $post_meta = get_post_meta( $post_id );
        $limit_level_type = isset( $post_meta[ 'limit_level' ] ) ? (int) $post_meta[ 'limit_level' ][0] : 0;

        $limit_level_value = ''; // Unlimited as default
        if ( isset( $post_meta[ 'limit_level_value' ] ) && '' != $post_meta[ 'limit_level_value' ][0] ) {
            $limit_level_value = (int) $post_meta[ 'limit_level_value' ][0];
        } ?>
        <label for="<?php echo esc_attr( $field_name ); ?>_0">
            <input type="radio" id="<?php echo esc_attr( $field_name ); ?>_0" class="<?php echo esc_attr( $field_name ); ?>_post_meta has_conditional" name="<?php echo esc_attr( $field_name ); ?>_post_meta" value = "0" <?php checked( $limit_level_type, 0, true ); ?>/><?php esc_html_e( 'Per ticket type (Default)', 'tickera-event-ticketing-system' ); ?>
        </label><br><br>
        <label for="<?php echo esc_attr( $field_name ); ?>_1">
            <input type="radio" id="<?php echo esc_attr( $field_name ); ?>_1" class="<?php echo esc_attr( $field_name ); ?>_post_meta has_conditional" name="<?php echo esc_attr( $field_name ); ?>_post_meta" value = "1" <?php checked( $limit_level_type, 1, true ); ?>/><?php esc_html_e( 'Per event', 'tickera-event-ticketing-system' ); ?>
        </label><br><br>
        <input type="text" name="<?php echo esc_attr( $field_name ); ?>_value_post_meta" id="<?php echo esc_attr( $field_name ); ?>" class="regular-text tc_conditional" value="<?php echo esc_attr( $limit_level_value ); ?>" placeholder="<?php echo esc_attr( ! $limit_level_value ) ? __( 'Unlimited', 'tickera-event-ticketing-system' ) : ''; ?>" data-condition-field_name="<?php echo esc_attr( $field_name ); ?>_post_meta" data-condition-field_type="radio" data-condition-value="0" data-condition-action="hide" number="true" />
    <?php }
}

/**
 * Get tickets drop down (used in the discount codes admin page)
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_ticket_types".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_ticket_types' ) ) {

    function tickera_get_ticket_types( $field_name = '', $post_id = '' ) {
        $wp_tickets_search = new \Tickera\TC_Tickets_Search( '', '', -1 );
        if ( $post_id !== '' ) {
            $currently_selected = get_post_meta( $post_id, $field_name, true );
            $currently_selected = explode( ',', $currently_selected );
        } else {
            $currently_selected = '';
        }
        ?>
        <select name="<?php echo esc_attr( $field_name ); ?>_post_meta[]" multiple="true" id="tc_ticket_types">
            <option value="" <?php echo esc_attr( is_array( $currently_selected ) && count( $currently_selected ) == 1 && in_array( '', $currently_selected ) ) || ! is_array( $currently_selected ) ? 'selected' : ''; ?>><?php esc_html_e( 'All', 'tickera-event-ticketing-system' ); ?></option>
            <?php foreach ( $wp_tickets_search->get_results() as $ticket ) {
                $ticket_obj = new \Tickera\TC_Ticket( $ticket->ID );
                $ticket_object = $ticket_obj->details;
                $event_id = $ticket_object->event_name;
                $event_obj = new \Tickera\TC_Event( $event_id );
                ?>
                <option value="<?php echo esc_attr( (int) $ticket_object->ID ); ?>" <?php echo esc_attr( is_array( $currently_selected ) && in_array( $ticket_object->ID, $currently_selected ) ? 'selected' : '' ); ?>><?php echo esc_html( $ticket_object->post_title . ' (' . $event_obj->details->post_title . ')' ); ?></option>
            <?php } ?>
        </select>
        <?php
    }
}

/**
 * Generate a dropdown menu with a list of users' roles.
 * @param string $field_name
 * @param string $post_id
 *
 * Deprecated function "tc_get_user_roles".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_user_roles' ) ) {

    function tickera_get_user_roles( $field_name = '', $post_id = '' ) {

        $user_roles_object = new WP_Roles();

        if ( $post_id !== '' ) {
            $currently_selected = get_post_meta( $post_id, $field_name, true );
            $currently_selected = explode( ',', $currently_selected );
        } else {
            $currently_selected = '';
        }
        ?>

        <select name="<?php echo esc_attr( $field_name ); ?>_post_meta[]" multiple="true" id="tc_ticket_types">
            <option value="" <?php echo esc_attr( is_array( $currently_selected ) && count( $currently_selected ) == 1 && in_array( '', $currently_selected ) ) || ! is_array( $currently_selected ) ? 'selected' : ''; ?>><?php esc_html_e( 'All', 'tickera-event-ticketing-system' ); ?></option>
            <?php foreach ( $user_roles_object->roles as $key => $value ) : ?>
                <option value="<?php echo esc_attr( $key ) ?>" <?php echo esc_attr( is_array( $currently_selected ) && in_array( $key, $currently_selected ) ? 'selected' : '' ); ?>><?php echo esc_html( $value[ 'name' ] ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php // Open PHP for succeeding source codes
    }
}

/**
 * Deprecated function "tc_get_discount_types".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_discount_types' ) ) {

    function tickera_get_discount_types( $field_name = '', $post_id = '' ) {
        if ( $post_id !== '' ) {
            $currently_selected = get_post_meta( $post_id, $field_name, true );
        } else {
            $currently_selected = '';
        }
        ?>
        <select name="<?php echo esc_attr( $field_name ); ?>_post_meta" class="postform" id="<?php echo esc_attr( $field_name ); ?>">
            <option value="1" <?php selected( $currently_selected, '1', true ); ?>><?php esc_html_e( 'Fixed Amount (per item)', 'tickera-event-ticketing-system' ); ?></option>
            <option value="3" <?php selected( $currently_selected, '3', true ); ?>><?php esc_html_e( 'Fixed Amount (per order)', 'tickera-event-ticketing-system' ); ?></option>
            <option value="2" <?php selected( $currently_selected, '2', true ); ?>><?php esc_html_e( 'Percentage (%)', 'tickera-event-ticketing-system' ); ?></option>
        </select>
        <?php
    }
}

/**
 * Deprecated function "search_array".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_search_array' ) ) {

    function tickera_search_array( $array, $key, $value ) {
        $results = array();

        if ( is_array( $array ) ) {
            if ( isset( $array[ $key ] ) && $array[ $key ] == $value )
                $results[] = $array;

            foreach ( $array as $subarray )
                $results = array_merge( $results, tickera_search_array( $subarray, $key, $value ) );
        }

        return $results;
    }
}

/**
 * Update Cart Widget Contents
 *
 * Deprecated function "tc_update_widget_cart".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_update_widget_cart' ) ) {

    function tickera_update_widget_cart() {

        global $tc;

        if ( isset( $_POST[ 'nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {

            $cart_contents = $tc->get_cart_cookie();

            if ( ! empty( $cart_contents ) ) {

                $tc_cart_list = '';

                foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                    $ticket = new \Tickera\TC_Ticket( $ticket_type );
                    $tc_cart_list .= "<li id='tc_ticket_type_'" . esc_attr( $ticket_type ) . ">" . wp_kses_post( apply_filters( 'tc_cart_widget_item', ( $ordered_count . ' x ' . $ticket->details->post_title . ' (' . apply_filters( 'tc_cart_currency_and_format', tickera_get_ticket_price( $ticket->details->ID ) * $ordered_count ) . ')' ), $ordered_count, $ticket->details->post_title, tickera_get_ticket_price( $ticket->details->ID ) ) ) . "</li>";
                }

                echo wp_kses_post( $tc_cart_list );

            } else {
                do_action( 'tc_cart_before_empty' ); ?>
                <span class='tc_empty_cart'><?php esc_html_e( 'The cart is empty', 'tickera-event-ticketing-system' ); ?></span><?php
                do_action( 'tc_cart_after_empty' );
            }
        }
        ?>
        <div class='tc-clearfix'></div>
        <?php
        exit;
    }
}

/**
 * Deprecated function "tc_post_fields".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_post_fields' ) ) {

    function tickera_post_fields() {
        return array(
            'ID',
            'post_author',
            'post_date',
            'post_date_gmt',
            'post_content',
            'post_title',
            'post_excerpt',
            'post_status',
            'comment_status',
            'ping_status',
            'post_password',
            'post_name',
            'to_ping',
            'pinged',
            'post_modified',
            'post_modified_gmt',
            'post_content_filtered',
            'post_parent',
            'guid',
            'menu_order',
            'post_type',
            'post_mime_type',
            'comment_count'
        );
    }
}

/**
 * Deprecated function "tc_get_post_meta_all".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_post_meta_all' ) ) {

    function tickera_get_post_meta_all( $post_id ) {
        $data = [];
        $metas = get_post_meta( $post_id );
        foreach ( $metas as $key => $value ) {
            $data[ $key ] = is_array( $value ) ? $value[ 0 ] : $value;
        };
        return $data;
    }
}

/**
 * Deprecated function "tc_get_post_meta_all_old".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_get_post_meta_all_old' ) ) {

    function tickera_get_post_meta_all_old( $post_id ) {
        global $wpdb;
        $data = [];
        $wpdb->query( $wpdb->prepare( "SELECT `meta_key`, `meta_value` FROM " . $wpdb->postmeta . " WHERE `post_id` = %d", $post_id ) );

        foreach ( $wpdb->last_result as $k => $v ) {
            $data[ $v->meta_key ] = $v->meta_value;
        }
        return $data;
    }
}

/**
 * Deprecated function "tc_hex2rgb".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_hex2rgb' ) ) {

    function tickera_hex2rgb( $hex ) {
        $hex = str_replace( "#", "", $hex );

        if ( strlen( $hex ) == 3 ) {
            $r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
            $g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
            $b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
        } else {
            $r = hexdec( substr( $hex, 0, 2 ) );
            $g = hexdec( substr( $hex, 2, 2 ) );
            $b = hexdec( substr( $hex, 4, 2 ) );
        }
        $rgb = array( $r, $g, $b );
        return $rgb; // returns an array with the rgb values
    }
}

/**
 * Deprecated function "json_encode".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_json_encode' ) ) {

    function tickera_json_encode( $a = false ) {
        if ( is_null( $a ) )
            return 'null';
        if ( $a === false )
            return 'false';
        if ( $a === true )
            return 'true';
        if ( is_scalar( $a ) ) {
            if ( is_float( $a ) ) {
                return floatval( str_replace( ",", ".", strval( $a ) ) );
            }

            if ( is_string( $a ) ) {
                static $jsonReplaces = array( array( "\\", "/", "\n", "\t", "\r", "\b", "\f", '"' ), array( '\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"' ) );
                return '"' . str_replace( $jsonReplaces[ 0 ], $jsonReplaces[ 1 ], $a ) . '"';
            } else
                return $a;
        }
        $isList = true;
        for ( $i = 0, reset( $a ); $i < count( $a ); $i++, next( $a ) ) {
            if ( key( $a ) !== $i ) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ( $isList ) {
            foreach ( $a as $v )
                $result[] = json_encode( $v );
            return '[' . join( ',', $result ) . ']';
        } else {
            foreach ( $a as $k => $v )
                $result[] = json_encode( $k ) . ':' . json_encode( $v );
            return '{' . join( ',', $result ) . '}';
        }
    }
}

/**
 * Deprecated function "ticket_code_to_id".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_ticket_code_to_id' ) ) {

    function tickera_ticket_code_to_id( $ticket_code ) {
        $result = get_posts( array(
            'posts_per_page' => 1,
            'meta_key' => 'ticket_code',
            'meta_value' => $ticket_code,
            'post_type' => 'tc_tickets_instances'
        ) );

        if ( isset( $result[ 0 ] ) ) {
            return $result[ 0 ]->ID;
        } else {
            return false;
        }
    }
}

/**
 * Deprecated function "tc_checkout_step_url".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_checkout_step_url' ) ) {

    function tickera_checkout_step_url( $checkout_step ) {
        return apply_filters( 'tc_checkout_step_url', trailingslashit( home_url() ) . trailingslashit( $checkout_step ) );
    }
}

/**
 * Check if the tcpdf throws image error and if it does change the url
 * @param $image_url
 * @return false|string
 *
 * Deprecated function "tc_ticket_template_image_url".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_ticket_template_image_url' ) ) {

    function tickera_ticket_template_image_url( $image_url ) {

        if ( ! $image_url ) {
            return false;
        }

        $imsize = @getimagesize( $image_url );

        if ( $imsize === FALSE || defined( 'TC_FULLSIZE_PATH' ) ) {
            $img_id = attachment_url_to_postid( $image_url );
            return get_attached_file( $img_id );

        } else {
            return $image_url;
        }
    }
}

/**
 * Deprecated function "tc_current_url".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_current_url' ) ) {

    function tickera_current_url() {
        $pageURL = 'http';
        if ( isset( $_SERVER[ "HTTPS" ] ) && $_SERVER[ "HTTPS" ] == "on" ) {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ( isset( $_SERVER[ "SERVER_PORT" ] ) && $_SERVER[ "SERVER_PORT" ] != "80" ) {
            $pageURL .= sanitize_text_field( $_SERVER[ "SERVER_NAME" ] ) . ":" . sanitize_text_field( $_SERVER[ "SERVER_PORT" ] ) . sanitize_text_field( $_SERVER[ "REQUEST_URI" ] );
        } else {
            $pageURL .= sanitize_text_field( $_SERVER[ "SERVER_NAME" ] ) . sanitize_text_field( $_SERVER[ "REQUEST_URI" ] );
        }
        return $pageURL;
    }
}

/**
 * Deprecated function "tc_write_log".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_write_log' ) ) {

    function tickera_write_log( $log ) {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}

/**
 * Deprecated function "tc_iw_is_pr".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_iw_is_pr' ) ) {

    function tickera_iw_is_pr() {
        global $tc_gateway_plugins;
        if ( tickera_is_pr_only() ) {
            return true;
        }
        if ( count( $tc_gateway_plugins ) < 10 ) {
            return false;
        } else {
            return true;
        }
    }
}

/**
 * Check if Tickera is white-labeled
 *
 * Deprecated function "tc_iw_is_wl".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_iw_is_wl' ) ) {

    function tickera_iw_is_wl() {
        global $tc;
        if ( $tc->title == 'Tickera' ) {
            return false;
        } else {
            return true;
        }
    }
}

/**
 * Collection of Order Statuses
 * @since 3.5.4.5
 */
if ( ! function_exists( 'tickera_get_order_statuses' ) ) {

    function tickera_get_order_statuses() {

        $order_statuses = [
            'order_received'    => _x( 'Order Received', 'Order status', 'tickera-event-ticketing-system' ),
            'order_paid' => _x( 'Order Paid', 'Order status', 'tickera-event-ticketing-system' ),
            'order_cancelled'    => _x( 'Order Cancelled', 'Order status', 'tickera-event-ticketing-system' ),
            'order_fraud'  => _x( 'Order Fraud', 'Order status', 'tickera-event-ticketing-system' ),
            'order_refunded'  => _x( 'Order Refunded', 'Order status', 'tickera-event-ticketing-system' ),
        ];

        return apply_filters( 'tickera_order_statuses', $order_statuses );
    }
}

require_once( "wizard-functions.php" );
require_once( "internal-hooks.php" );
