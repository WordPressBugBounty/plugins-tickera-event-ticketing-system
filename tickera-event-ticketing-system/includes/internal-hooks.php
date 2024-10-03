<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Confirm to trash ticket type
 *
 * Deprecated function "trash_post_before".
 * @since 3.5.3.0
 */
add_action( 'wp_ajax_tickera_trash_post_before', 'tickera_trash_post_before' );
if ( ! function_exists( 'tickera_trash_post_before' ) ) {

    function tickera_trash_post_before() {

        if ( isset( $_POST[ 'nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {

            $btn_action = sanitize_text_field( $_POST[ 'btn_action' ] );

            if ( isset( $btn_action ) ) {

                if ( $btn_action == 'trash' ) {
                    $post_id = absint( (int) $_POST[ 'trash_id' ] );
                    $ticket_type = new \Tickera\TC_Ticket( $post_id );//$99 = id of the ticket type
                    $sold_tickets = tickera_get_tickets_count_sold( $ticket_type->id );
                    $resp = $sold_tickets;
                    wp_send_json( wp_json_encode( $resp ) );

                } elseif ( $btn_action == 'multi_trash' ) {
                    $ids = tickera_sanitize_array( $_POST[ 'multi_trash_id' ] );

                    foreach ( $ids as $id ) {
                        $ticket_type = new \Tickera\TC_Ticket( $id );//$99 = id of the ticket type
                        $sold_tickets = tickera_get_tickets_count_sold( $ticket_type->id );
                        $resp = $sold_tickets;
                        if ( $resp > 0 ) {
                            wp_send_json( wp_json_encode( $resp ) );
                        }
                    }
                }
            }
        }
    }
}

/**
 * Populate All Users table header
 *
 * Deprecated function "tc_modify_user_columns".
 * @since 3.5.3.0
 */
add_action( 'manage_users_columns', 'tickera_modify_user_columns' );
if ( ! function_exists( 'tickera_modify_user_columns' ) ) {

    function tickera_modify_user_columns( $column_headers ) {
        $column_headers[ 'tc_number_of_orders' ] = __( 'Ticket Orders', 'tickera-event-ticketing-system' );
        return $column_headers;
    }
}

/**
 *  Display the total number of users' committed orders
 *
 * Deprecated function "tc_add_number_of_orders_value".
 * @since 3.5.3.0
 */
add_action( 'manage_users_custom_column', 'tickera_add_number_of_orders_value', 10, 3 );
if ( ! function_exists( 'tickera_add_number_of_orders_value' ) ) {

    function tickera_add_number_of_orders_value( $value, $column_name, $user_id ) {

        if ( 'tc_number_of_orders' == $column_name ) {

            global $wpdb;

            if ( user_can( $user_id, 'manage_options' ) && $user_id != 0 ) {
                $value = '-';

            } else {
                $user_id = (int) $user_id;
                $query = $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id WHERE {$wpdb->postmeta}.meta_key = '_customer_user' AND {$wpdb->postmeta}.meta_value = %d AND {$wpdb->posts}.post_type = 'shop_order'", $user_id );
                $count = $wpdb->get_var( $query );
                $value = "<a href='" . esc_url( admin_url( 'edit.php?s&post_type=shop_order&_customer_user=' . $user_id ) ) . "'>" . (int) $count . "</a>";
            }
        }
        return $value;
    }
}

/**
 * Deprecated function "tc_show_extra_profile_fields_order_history".
 * @since 3.5.3.0
 */
add_action( 'edit_user_profile', 'tickera_show_extra_profile_fields_order_history' );
if ( ! function_exists( 'tickera_show_extra_profile_fields_order_history' ) ) {

    function tickera_show_extra_profile_fields_order_history( $user ) { ?>
        <h3><?php esc_html_e( 'Ticket Order History', 'tickera-event-ticketing-system' ); ?><a name="tc_order_history"></a></h3>
        <table class="form-table">
            <tr>
                <th></th>
                <td>
                    <?php
                    $user_orders = \Tickera\TC_Orders::get_user_orders( $user->ID );

                    if ( count( $user_orders ) == 0 ) {
                        esc_html_e( 'No Orders Found', 'tickera-event-ticketing-system' );

                    } else {
                        ?>
                        <table cellspacing="0" class="widefat shadow-table" cellpadding="10">
                            <tr>
                                <td><?php esc_html_e( 'Order ID', 'tickera-event-ticketing-system' ); ?></td>
                                <td><?php esc_html_e( 'Status', 'tickera-event-ticketing-system' ); ?></td>
                                <td><?php esc_html_e( 'Date', 'tickera-event-ticketing-system' ); ?></td>
                                <td><?php esc_html_e( 'Total', 'tickera-event-ticketing-system' ); ?></td>
                                <td><?php esc_html_e( 'Details', 'tickera-event-ticketing-system' ); ?></td>
                            </tr>
                            <?php
                            $style = '';
                            foreach ( $user_orders as $user_order ) {
                                $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                                $order = new \Tickera\TC_Order( $user_order->ID );
                                ?>
                                <tr <?php echo wp_kses_post($style ); ?>>
                                    <td>
                                        <?php echo esc_html( $order->details->post_title ); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $post_status = $order->details->post_status;
                                        $init_post_status = $post_status;

                                        $tc_order_status_color = array(
                                            'order_fraud' => 'tc_order_fraud',
                                            'order_received' => 'tc_order_received',
                                            'order_paid' => 'tc_order_paid',
                                            'order_cancelled' => 'tc_order_cancelled',
                                            'order_refunded' => 'tc_order_fraud'
                                        );

                                        $color = isset( $tc_order_status_color[ $post_status ] ) ? $tc_order_status_color[ $post_status ] : 'tc_order_received';

                                        if ( 'order_fraud' == $post_status ) {
                                            $post_status = __( 'Held for Review', 'tickera-event-ticketing-system' );
                                        }

                                        $post_status = ucwords( str_replace( '_', ' ', $post_status ) );
                                        echo wp_kses_post( sprintf(
                                            /* translators: 1: Order status color class identifier  2: Order status */
                                            __( '<span class="%1$s">%2$s</span>', 'tickera-event-ticketing-system' ),
                                            esc_attr( apply_filters( 'tc_order_history_color', $color, $init_post_status ) ),
                                            esc_html( $post_status )
                                        ) );
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html( tickera_format_date( $order->details->tc_order_date, true ) ); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) ); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $order_status_url = admin_url( 'post.php?post=' . (int) $order->details->ID . '&action=edit' );
                                        ?>
                                        <a href="<?php echo esc_url( $order_status_url ); ?>"><?php esc_html_e( 'Order Details', 'tickera-event-ticketing-system' ); ?></a>
                                    </td>
                                </tr>
                                <?php
                            } ?>
                        </table>
                        <?php
                    } ?>
                </td>
            </tr>
        </table>
        <?php
    }
}

add_action( 'tc_cart_col_title_before_total_price', 'tickera_cart_col_title_before_total_price' );

/**
 * Deprecated function "tc_is_disabled_fee_column".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_is_disabled_fee_column' ) ) {

    function tickera_is_disabled_fee_column() {

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $use_global_fees = isset( $tc_general_settings[ 'use_global_fees' ] ) ? $tc_general_settings[ 'use_global_fees' ] : 'no';
        $show_fees = isset( $tc_general_settings[ 'show_fees' ] ) ? $tc_general_settings[ 'show_fees' ] : 'yes';

        $disabled = true;

        if ( $use_global_fees == 'yes' ) {
            $global_fee_type = $tc_general_settings[ 'global_fee_type' ];
            $global_fee_scope = $tc_general_settings[ 'global_fee_scope' ];

            if ( empty( $global_fee_scope ) || $global_fee_scope == '' || $global_fee_scope == 'ticket' ) {
                $global_fee_scope = 'ticket';
                $disabled = false;
            }

            if ( $global_fee_scope == 'order' && $global_fee_type == 'fixed' ) {
                $disabled = true;
            }
        } else {
            if ( $show_fees == 'no' ) {
                $disabled = true;
            } else {
                $disabled = false;
            }
        }

        if ( $show_fees == 'no' ) {
            $disabled = true;
        }

        if ( $disabled ) {
            add_filter( 'tc_cart_table_colspan', 'tickera_cart_table_colspan_modify', 10, 1 );
        }

        return $disabled;
    }
}

/**
 * Deprecated function "tc_cart_table_colspan_modify".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cart_table_colspan_modify' ) ) {

    function tickera_cart_table_colspan_modify( $colspan ) {
        return $colspan - 1;
    }
}

/**
 * Deprecated function "tc_cart_col_title_before_total_price".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_cart_col_title_before_total_price' ) ) {

    function tickera_cart_col_title_before_total_price() {

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $fees_label = isset( $tc_general_settings[ 'fees_label' ] ) ? $tc_general_settings[ 'fees_label' ] : 'FEES';
        $use_global_fees = isset( $tc_general_settings[ 'use_global_fees' ] ) ? $tc_general_settings[ 'use_global_fees' ] : 'no';
        $disabled = tickera_is_disabled_fee_column();

        if ( ! isset( $tc_general_settings[ 'show_fees' ] ) || ( isset( $tc_general_settings[ 'show_fees' ] ) && $tc_general_settings[ 'show_fees' ] == 'yes' ) ) {
            if ( ! $disabled ) {
                ?>
                <th><?php echo esc_html( $fees_label ); ?></th>
                <?php
            }
        }
    }
}

/**
 * Deprecated function "tc_cart_col_value_before_total_price".
 * @since 3.5.3.0
 */
add_action( 'tc_cart_col_value_before_total_price', 'tickera_cart_col_value_before_total_price', 10, 3 );
if ( ! function_exists( 'tickera_cart_col_value_before_total_price' ) ) {

    function tickera_cart_col_value_before_total_price( $ticket_type, $ordered_count, $ticket_price ) {

        global $tc, $total_fees;
        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $use_global_fees = isset( $tc_general_settings[ 'use_global_fees' ] ) ? $tc_general_settings[ 'use_global_fees' ] : 'no';

        if ( ! isset( $total_fees ) || ! is_numeric( $total_fees ) ) {
            $total_fees = 0;
        }

        $ticket = new \Tickera\TC_Ticket( $ticket_type );
        $fee_type = $ticket->details->ticket_fee_type;
        $fee = $ticket->details->ticket_fee;

        if ( ! isset( $fee ) || '' == $fee ) {
            $fee = 0;

        } else {

            $fee = ( 'fixed' == $fee_type )
                ? round( ( $ordered_count * $fee ), 2 )
                : round( ( ( $ticket_price * $ordered_count ) / 100 ) * $fee, 2 );
        }

        if ( 'yes' == $use_global_fees ) {
            $global_fee_type = $tc_general_settings[ 'global_fee_type' ];
            $global_fee_value = $tc_general_settings[ 'global_fee_value' ];
            $global_fee_scope = $tc_general_settings[ 'global_fee_scope' ];

            if ( empty( $global_fee_scope ) || '' == $global_fee_scope ) {
                $global_fee_scope = 'ticket';
            }

            if ( ! isset( $global_fee_value ) || '' == $global_fee_value ) {
                $fee = 0;

            } else {

                $fee = ( 'fixed' == $global_fee_type )
                    ? apply_filters( 'tc_global_fixed_fee_value', round( ( $ordered_count * $global_fee_value ), 2 ), $ordered_count, $global_fee_value )
                    : apply_filters( 'tc_global_percentage_fee_value', round( ( ( $ticket_price * $ordered_count ) / 100 ) * $global_fee_value, 2 ), $ticket_price, $ordered_count, $global_fee_value );
            }
        }

        $total_fees = apply_filters( 'tc_total_fees_value', $total_fees + $fee, $use_global_fees, isset( $global_fee_scope ) ? $global_fee_scope : 'ticket', $ordered_count, isset( $global_fee_value ) ? $global_fee_value : 0, $ticket_price, isset( $global_fee_type ) ? $global_fee_type : 'percentage' );
        $disabled = tickera_is_disabled_fee_column();
        $tc->session->set( 'tc_total_fees', $total_fees );

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        if ( ! isset( $tc_general_settings[ 'show_fees' ] ) || ( isset( $tc_general_settings[ 'show_fees' ] ) && $tc_general_settings[ 'show_fees' ] == 'yes' ) ) {
            if ( ! $disabled ) { ?>
                <td class="ticket-fee" class="ticket_fee"><?php echo wp_kses_post( apply_filters( 'tc_cart_currency_and_format', $fee ) ); ?></td>
            <?php }
        }
    }
}

/**
 * Deprecated function "tc_total_fees_value_modify".
 * @since 3.5.3.0
 */
add_filter( 'tc_total_fees_value', 'tickera_total_fees_value_modify', 10, 7 );
if ( ! function_exists( 'tickera_total_fees_value_modify' ) ) {

    function tickera_total_fees_value_modify( $total_fees, $use_global_fees, $global_fee_scope, $ordered_count, $global_fee_value, $ticket_price, $global_fee_type ) {
        if ( $use_global_fees == 'yes' && $global_fee_scope == 'order' && $global_fee_type == 'fixed' ) {
            $total_fees = $global_fee_value;
        }
        return $total_fees;
    }
}

/**
 * Deprecated function "tc_modify_global_fixed_fee_value".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_modify_global_fixed_fee_value' ) ) {

    function tickera_modify_global_fixed_fee_value( $value, $ordered_count, $global_fee_value ) {
        return $global_fee_value;
    }
}

/**
 * Deprecated function "tc_global_percentage_fee_value".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_global_percentage_fee_value' ) ) {

    function tickera_global_percentage_fee_value( $value, $ticket_price, $ordered_count, $global_fee_value ) {
        return $value;
    }
}

/**
 * Render total price in cart page.
 *
 * Deprecated function "tc_cart_col_value_before_total_price_total".
 * @since 3.5.3.0
 */
add_action( 'tc_cart_col_value_before_total_price_total', 'tickera_cart_col_value_before_total_price_total', 11, 1 );
if ( ! function_exists( 'tickera_cart_col_value_before_total_price_total' ) ) {

    function tickera_cart_col_value_before_total_price_total( $total ) {

        global $total_fees;

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $fees_label = isset( $tc_general_settings[ 'fees_label' ] ) ? $tc_general_settings[ 'fees_label' ] : 'FEES';

        do_action( 'tc_cart_col_value_before_total_price_fees' );
        add_filter( 'tc_cart_total', function( $total_price ) {
            global $total_fees, $subtotal_value;
            return $subtotal_value + apply_filters( 'tc_discounted_fees_total', $total_fees );
        }, 10, 1 );

        if ( ! isset( $tc_general_settings[ 'show_fees' ] ) || ( isset( $tc_general_settings[ 'show_fees' ] ) && 'yes' == $tc_general_settings[ 'show_fees' ] ) ) : ?>
            <div>
                <span class="total_item_title"><?php echo esc_html( $fees_label ); ?>:</span><span class="total_item_amount"><?php echo wp_kses_post( apply_filters( 'tc_cart_currency_and_format', $total_fees ) ); ?></span>
            </div>
        <?php endif;
    }
}

/**
 * Render the total price with tax in cart page.
 *
 * Deprecated function "tc_cart_tax".
 * @since 3.5.3.0
 */
add_action( 'tc_cart_col_value_before_total_price_total', 'tickera_cart_tax', 12, 1 );
if ( ! function_exists( 'tickera_cart_tax' ) ) {

    function tickera_cart_tax( $total ) {

        global $tc, $total_fees, $tax_value, $subtotal_value;

        $tc_general_settings = get_option( 'tickera_general_setting', false );
        $tax_before_fees = ( isset( $tc_general_settings[ 'tax_before_fees' ] ) && $tc_general_settings[ 'tax_before_fees' ] ) ? $tc_general_settings[ 'tax_before_fees' ] : 'no';
        $tax_inclusive = tickera_is_tax_inclusive();

        $total_cart = ( 'no' == $tax_before_fees )
            ? round( $subtotal_value + $total_fees, 2 )
            : round( $subtotal_value, 2 );

        $tax_value = ( $tax_inclusive )
            ? round( $total_cart - ( $total_cart / ( ( $tc->get_tax_value() / 100 ) + 1 ) ), 2 )
            : round( $total_cart * ( $tc->get_tax_value() / 100 ), 2 );

        $tax_label = isset( $tc_general_settings[ 'tax_label' ] ) ? $tc_general_settings[ 'tax_label' ] : 'TAX';
        $tc->session->set( 'tc_tax_value', $tax_value );

        $session = $tc->session->get();

        $session[ 'cart_info' ][ 'total' ] = ( 'no' == $tax_before_fees )
            ? ( $tax_inclusive ? $total_cart : ( $total_cart + $tax_value ) )
            : ( $tax_inclusive ? $total_cart : ( $total_cart + $total_fees + $tax_value ) );

        $tc->session->set( 'cart_info', $session[ 'cart_info' ] );

        add_filter( 'tc_cart_total', function( $total_price ) {
            global $tc, $tax_value;
            $tax_inclusive = tickera_is_tax_inclusive();
            $cart_total = $tax_inclusive ? $total_price : ( $total_price + $tax_value );
            $tc->session->set( 'tc_cart_total', $cart_total );
            return $cart_total;
        }, 10, 1 );

        do_action( 'tc_cart_col_value_before_total_price_tax' );

        if ( ! isset( $tc_general_settings[ 'show_tax_rate' ] ) || ( isset( $tc_general_settings[ 'show_tax_rate' ] ) && 'yes' == $tc_general_settings[ 'show_tax_rate' ] ) ) : ?>
            <div>
                <span class="total_item_title"><?php echo esc_html( $tax_label ); ?>:</span><span class="total_item_amount"><?php echo wp_kses_post( apply_filters( 'tc_cart_currency_and_format', $tax_value ) ); ?></span>
            </div>
        <?php endif;
    }
}

/**
 * Deprecated function "tc_discounted_total".
 * @since 3.5.3.0
 */
add_filter( 'tc_discounted_total', 'tickera_discounted_total', 10, 1 );
if ( ! function_exists( 'tickera_discounted_total' ) ) {

    function tickera_discounted_total( $total ) {

        global $tc;
        $session = $tc->session->get();
        $tax_value = isset( $session[ 'tc_tax_value' ] ) ? (float) $session[ 'tc_tax_value' ] : 0;
        $total_fees = isset( $session[ 'tc_total_fees' ] ) ? (float) $session[ 'tc_total_fees' ] : 0;

        return ( tickera_is_tax_inclusive() )
            ? round( $total + $total_fees, 2 )
            : round( $total + $total_fees + $tax_value, 2 );
    }
}

/**
 * Deprecated function "tc_event_date_time_element".
 * @since 3.5.3.0
 */
add_filter( 'tc_event_date_time_element', 'tickera_event_date_time_element', 10, 1 );
if ( ! function_exists( 'tickera_event_date_time_element' ) ) {

    function tickera_event_date_time_element( $date ) {
        return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ), false );
    }
}

/**
 * Deprecated function "tc_checkins_date_checked".
 * @since 3.5.3.0
 */
add_filter( 'tc_checkins_date_checked', 'tickera_checkins_date_checked', 10, 1 );
if ( ! function_exists( 'tickera_checkins_date_checked' ) ) {

    function tickera_checkins_date_checked( $date ) {
        return tickera_format_date( $date );
    }
}

/**
 * Deprecated function "tc_checkins_status".
 * @since 3.5.3.0
 */
add_filter( 'tc_checkins_status', 'tickera_checkins_status', 10, 1 );
if ( ! function_exists( 'tickera_checkins_status' ) ) {

    function tickera_checkins_status( $status ) {

        if ( 'Pass' == $status ) {
            $status = '<span class="status_green">' . esc_html( $status ) . '</span>';

        } elseif ( 'Fail' == $status ) {
            $status = '<span class="status_red">' . esc_html( $status ) . '</span>';
        }

        return $status;
    }
}

/**
 * Deprecated function "tc_checkins_api_key_id".
 * @since 3.5.3.0
 */
add_filter( 'tc_checkins_api_key_id', 'tickera_checkins_api_key_id', 10, 1 );
if ( ! function_exists( 'tickera_checkins_api_key_id' ) ) {

    function tickera_checkins_api_key_id( $api_key_id ) {
        $api_key = new \Tickera\TC_API_Key( $api_key_id );
        return '<a href="' . esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_settings&tab=api&action=edit&ID=' . (int) $api_key_id ) ) . '">' . esc_html( $api_key->details->api_key_name ) . '</a>';;
    }
}

/**
 * Assign Order Status Colors
 *
 * Deprecated function "tc_order_field_value".
 * @since 3.5.3.0
 */
add_filter( 'tc_order_field_value', 'tickera_order_field_value', 10, 5 );
if ( ! function_exists( 'tickera_order_field_value' ) ) {

    function tickera_order_field_value( $order_id, $value, $meta_key, $field_type, $field_id = false ) {

        global $tc;

        if ( 'order_status' == $field_type ) {

            $new_value = ucwords( str_replace( '_', ' ', $value ) );

            switch ( $value ) {

                case 'order_refunded':
                    $color = 'tc_order_refunded';
                    break;

                case 'order_fraud':
                    $color = 'tc_order_fraud';
                    break;

                case 'order_received':
                    $color = 'tc_order_received';
                    break;

                case 'order_paid':
                    $color = 'tc_order_paid';
                    break;

                case 'order_cancelled':
                    $color = 'tc_order_cancelled';
                    break;

                default:
                    $color = 'black';
            }

            $note = '';
            $notes = \Tickera\TC_Order::get_order_notes( $order_id );

            if ( isset( $notes ) && isset( $notes[ 'tc_order_notes' ] ) && count( $notes[ 'tc_order_notes' ] ) > 0 ) {
                $note_text = isset( $notes[ 'tc_order_notes' ][ 0 ] ) ? wp_kses_post( $notes[ 'tc_order_notes' ][ 0 ][ 'created_at' ] . '' . $notes[ 'tc_order_notes' ][ 0 ][ 'note' ] ) : '';
                $note = '<i class="fa fa-flag" title="' . esc_attr( $note_text ) . '" alt="' . esc_attr( $note_text ) . '" aria-hidden="true"></i>';
            }

            return sprintf(
            /* translators: 1: Order status color class identifier 2: Order status 3: flag icon */
                __( '<span class="%1$s">%2$s %3$s</span>', 'tickera-event-ticketing-system' ),
                esc_attr( $color ),
                esc_html( $new_value ),
                $note
            );

        } elseif ( 'order_date' == $field_id ) {
            return tickera_format_date( $value );

        } elseif ( 'customer' == $field_id ) {

            $order = new \Tickera\TC_Order( $order_id );
            $author_id = $order->details->post_author;

            return ( ! user_can( $author_id, 'manage_options' ) && $author_id != 0 )
                ? '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . (int) $author_id ) ) . '">' . sanitize_text_field( $value[ 'buyer_data' ][ 'first_name_post_meta' ] . ' ' . $value[ 'buyer_data' ][ 'last_name_post_meta' ] ) . '</a>'
                : ( isset( $value[ 'buyer_data' ] ) ? sanitize_text_field( $value[ 'buyer_data' ][ 'first_name_post_meta' ] . ' ' . $value[ 'buyer_data' ][ 'last_name_post_meta' ] ) : __( 'N/A', 'tickera-event-ticketing-system' ) );

        } elseif ( 'parent_event' == $field_id ) {

            $events = [];
            $ticket_instances = \Tickera\TC_Orders::get_tickets_ids( $order_id );

            // Collection of Events and Quantity
            foreach ( $ticket_instances as $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket_Instance( $ticket_instance_id );

                if ( isset( $ticket_instance->details->event_ID ) ) {

                    if ( isset( $events[ $ticket_instance->details->event_ID ] ) ) {
                        $events[ $ticket_instance->details->event_ID ]++;

                    } else {
                        $events[ $ticket_instance->details->event_ID ] = 1;
                    }
                }
            }

            // Render Events x Quantity
            if ( $events ) {

                foreach ( $events as $event_id => $qty ) {
                    return wp_kses_post( '<a href="post.php?post=' . esc_attr( (int) $event_id ) . '&action=edit">' . esc_html( get_the_title( $event_id ) ) . '</a> x ' . (int) $qty . '<br />' );
                }

            } else {
                return esc_html__( 'N/A', 'tickera-event-ticketing-system' );
            }


        } elseif ( 'gateway' == $field_id && isset( $value[ 'gateway' ] ) ) {
            return $value[ 'gateway' ];

        } elseif ( 'gateway_admin_name' == $field_id && isset( $value[ 'gateway_admin_name' ] ) ) {
            return $value[ 'gateway_admin_name' ];

        } elseif ( 'discount' == $field_id ) {

            $discounts = new \Tickera\TC_Discounts();
            $discount_total = $discounts->get_discount_total_by_order( $order_id );

            $discount_code = ( isset( $value[ 'coupon_code' ] ) && $value[ 'coupon_code' ] ) ? $value[ 'coupon_code' ] : get_post_meta( $order_id, 'tc_discount_code', true );

            $discount = new \Tickera\TC_Discount();
            $discount = $discount->get_discount_by_code( $discount_code );

            if ( $discount ) {

                return sprintf(
                    /* translators: 1: Discount amount 2: Discount ID 3: Discount Code */
                    __( '%1$s<br/>Code: <a href="edit.php?post_type=tc_events&page=tc_discount_codes&action=edit&ID=%2$s">%3$s</a>', 'tickera-event-ticketing-system' ),
                    esc_html( apply_filters( 'tc_cart_currency_and_format', $discount_total ) ),
                    (int) $discount->ID,
                    esc_html( $discount_code ) );

            } else {
                return '-';
            }

        } elseif ( 'total' == $field_id && isset( $value[ 'total' ] ) ) {
            return esc_html( apply_filters( 'tc_cart_currency_and_format', $value[ 'total' ] ) );

        } elseif ( 'subtotal' == $field_id && isset( $value[ 'subtotal' ] ) ) {
            return esc_html( apply_filters( 'tc_cart_currency_and_format', $value[ 'subtotal' ] ) );

        } elseif ( 'fees_total' == $field_id && isset( $value[ 'fees_total' ] ) ) {
            return esc_html( apply_filters( 'tc_cart_currency_and_format', $value[ 'fees_total' ] ) );

        } elseif ( 'tax_total' == $field_id && isset( $value[ 'tax_total' ] ) ) {
            return esc_html( apply_filters( 'tc_cart_currency_and_format', $value[ 'tax_total' ] ) );

        } else {
            return $value;
        }
    }
}

/**
 * Add additional fields to events admin
 *
 * Deprecated function "my_custom_events_admin_fields".
 * @since 3.5.3.0
 */
add_filter( 'tc_event_fields', 'tickera_events_admin_fields' );
if ( ! function_exists( 'tickera_events_admin_fields' ) ) {

    function tickera_events_admin_fields( $event_fields ) {

        $event_fields[] = array(
            'field_name' => 'event_shortcode',
            'field_title' => __( 'Shortcode', 'tickera-event-ticketing-system' ),
            'field_type' => 'read-only',
            'table_visibility' => true,
            'show_in_post_type' => false
        );

        if ( current_user_can( apply_filters( 'tc_event_activation_capability', 'edit_others_tc_events' ) ) || current_user_can( 'manage_options' ) ) {
            $event_fields[] = array(
                'field_name' => 'event_active',
                'field_title' => __( 'Active', 'tickera-event-ticketing-system' ),
                'field_type' => 'read-only',
                'table_visibility' => true,
                'table_edit_invisible' => true,
                'show_in_post_type' => false
            );
        }

        return $event_fields;
    }
}

/**
 * Deprecated function "my_custom_tc_event_object_details".
 * @since 3.5.3.0
 */
add_filter( 'tc_event_object_details', 'tickera_event_object_details' );
if ( ! function_exists( 'tickera_event_object_details' ) ) {

    function tickera_event_object_details( $object_details ) {
        $object_details->event_shortcode = '[event id="' . (int) $object_details->ID . '"]';
        $event_status = get_post_status( $object_details->ID );
        $on = $event_status == 'publish' ? 'tc-on' : '';
        $object_details->event_active = '<div class="tc-control ' . $on . '" event_id="' . esc_attr( $object_details->ID ) . '"><div class="tc-toggle"></div></div>';
        return $object_details;
    }
}

/**
 * Add custom fields to tickets admin
 *
 * Deprecated function "my_custom_tickets_admin_fields".
 * @since 3.5.3.0
 */
add_filter( 'tc_ticket_fields', 'tickera_tickets_admin_fields' );
if ( ! function_exists( 'tickera_tickets_admin_fields' ) ) {

    function tickera_tickets_admin_fields( $ticket_fields ) {

        $ticket_fields[] = array(
            'field_name' => 'ticket_shortcode',
            'field_title' => __( 'Shortcode', 'tickera-event-ticketing-system' ),
            'field_type' => 'read-only',
            'table_visibility' => true,
            'post_field_type' => 'read-only'
        );

        return $ticket_fields;
    }
}

/**
 * Deprecated function "my_custom_tc_ticket_object_details".
 * @since 3.5.3.0
 */
add_filter( 'tc_ticket_object_details', 'tickera_ticket_object_details' );
if ( ! function_exists( 'tickera_ticket_object_details' ) ) {

    function tickera_ticket_object_details( $object_details ) {
        $object_details->ticket_shortcode = '[ticket id="' . (int) $object_details->ID . '"]';

        global $wpdb;
        $sold_records = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(*) as cnt, p.post_parent FROM {$wpdb->posts} p, {$wpdb->postmeta} pm WHERE p.ID = pm.post_id AND pm.meta_key = 'ticket_type_id' AND pm.meta_value = %d GROUP BY p.post_parent", $object_details->ID ) );
        $sold_count = 0;
        foreach ( $sold_records as $sold_record ) {
            if ( get_post_status( $sold_record->post_parent ) == 'order_paid' ) {
                $sold_count = $sold_count + $sold_record->cnt;
            }
        }

        $ticket_status = get_post_status( $object_details->ID );
        $on = $ticket_status == 'publish' ? 'tc-on' : '';
        $object_details->ticket_active = '<div class="tc-control ' . esc_attr( $on ) . '" ticket_id="' . esc_attr( $object_details->ID ) . '"><div class="tc-toggle"></div></div>';

        $object_details->quantity_sold = $sold_count;
        return $object_details;
    }
}

/**
 * Deprecated function "tc_ticket_instance_field_value".
 * @since 3.5.3.0
 */
add_filter( 'tc_ticket_instance_field_value', 'tickera_ticket_instance_field_value', 10, 5 );
if ( ! function_exists( 'tickera_ticket_instance_field_value' ) ) {

    function tickera_ticket_instance_field_value( $value = false, $field_value = false, $post_field_type = false, $col_field_id = false, $field_id = false ) {//$value, $post_field_type, $var_name
        if ( $field_id == 'order' ) {

            $parent_post = get_post_ancestors( $value );
            $parent_post = isset( $parent_post[ 0 ] ) ? $parent_post[ 0 ] : 0;

            $order = new \Tickera\TC_Order( $parent_post );

            $order_found = false;

            if ( get_post_type( $order->details->ID ) == 'tc_orders' ) {
                $order_found = true;
            }

            $order_found = apply_filters( 'tc_order_found', $order_found, $order->details->ID );

            if ( $order_found ) {

                if ( current_user_can( 'manage_orders_cap' ) ) {
                    $value = wp_kses_post( apply_filters( 'tc_ticket_instance_order_admin_url', '<a target="_blank" href="' . esc_url( admin_url( 'post.php?post=' . $order->details->ID . '&action=edit' ) ) . '">' . esc_html( $order->details->post_title ) . '</a>', $parent_post, $order->details->post_title ) );
                } else {
                    $value = $order->details->post_title;
                }
            } else {
                $value = __( 'N/A', 'tickera-event-ticketing-system' );
            }
        }

        if ( $field_id == 'event' ) {
            $value = tickera_get_ticket_instance_event( false, false, $value, false );
        }

        if ( $field_id == 'ticket_code' ) {
            $value = $field_value;
        }

        if ( $field_id == 'ticket_type_id' ) {
            $ticket_type = new \Tickera\TC_Ticket( $field_value );
            $value = apply_filters( 'tc_checkout_owner_info_ticket_title', isset( $ticket_type->details->post_title ) ? $ticket_type->details->post_title : __( 'N/A', 'tickera-event-ticketing-system' ), $field_value, array(), $value );
        }

        if ( $field_id == 'ticket' ) {
            $value = '<a target="_blank" href="' . esc_url( admin_url( 'edit.php?post_type=tc_tickets_instances&tc_preview&ticket_instance_id=' . $field_value ) ) . '">' . esc_html__( 'View', 'tickera-event-ticketing-system' ) . '</a> | <a target="_top" href="' . esc_url( admin_url( 'edit.php?post_type=tc_tickets_instances&tc_download&ticket_instance_id=' . $field_value ) ) . '">' . esc_html__( 'Download', 'tickera-event-ticketing-system' ) . '</a>';
        }

        if ( $field_id == 'checkins' ) {
            $ticket_instance = new \Tickera\TC_Ticket_Instance( $field_value );
            $checkins_pass = $ticket_instance->get_number_of_checkins( 'pass' );
            $checkins_fail = $ticket_instance->get_number_of_checkins( 'fail' );
            $value = '<a href="' . admin_url( 'post.php?post=' . $field_value . '&action=edit' ) . '">';
            $value .= '<span class="' .  ( $checkins_pass > 0 ? 'status_green' : '' ) . '">' . (int) $checkins_pass . '</span>';

            if ( $checkins_fail > 0 ) {
                $value .= ' | <span class="status_red">' . (int) $checkins_fail . '</span>';
            }

            $value .= esc_html__( ' Details', 'tickera-event-ticketing-system' );
            $value .= '</a>';
        }

        if ( $field_id == 'owner_name' ) {
            $owner_name = get_post_meta( $value, 'first_name', true ) . ' ' . get_post_meta( $value, 'last_name', true );
            if ( trim( $owner_name ) == '' ) {
                $parent_post = get_post_ancestors( $value );
                $parent_post = isset( $parent_post[ 0 ] ) ? $parent_post[ 0 ] : 0;
                $order = new \Tickera\TC_Order( $parent_post );
                $buyer_full_name = isset( $order->details->tc_cart_info[ 'buyer_data' ] ) ? ( isset( $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] ) ? $order->details->tc_cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] : '' ) . ' ' . ( isset( $order->details->tc_cart_info[ 'buyer_data' ][ 'last_name_post_meta' ] ) ? $order->details->tc_cart_info[ 'buyer_data' ][ 'last_name_post_meta' ] : '' ) : __( 'N/A', 'tickera-event-ticketing-system' );

                if ( trim( $buyer_full_name ) !== '' ) {
                    $value = apply_filters( 'tc_ticket_buyer_name_element', $buyer_full_name, $parent_post );
                    $value = trim( $value );
                    if ( empty( $value ) ) {
                        $value = '-';
                    }

                } else {
                    $value = apply_filters( 'tc_ticket_buyer_name_element', '-', $parent_post );
                    $value = trim( $value );
                    if ( empty( $value ) ) {
                        $value = '-';
                    }
                }

            } else {
                $value = $owner_name;
            }
        }

        return apply_filters( 'tc_column_value', $value, $field_id );
    }
}

/**
 * Render API Key Table Values
 *
 * Deprecated function "tc_api_key_field_value".
 * @since 3.5.3.0
 */
add_filter( 'tc_api_key_field_value', 'tickera_api_key_field_value', 10, 3 );
if ( ! function_exists( 'tickera_api_key_field_value' ) ) {

    function tickera_api_key_field_value( $value, $post_field_type, $var_name ) {

        if ( 'event_name' == $var_name ) {

            $values = (array) $value;

            // Searching through Multidimensional Array
            if ( is_array( $is_array = reset( $values ) ) && in_array( 'all', $is_array ) ) {
                $value = __( 'All Events', 'tickera-event-ticketing-system' );

            } elseif ( ! is_array( reset( $values ) ) && in_array( 'all', $values ) ) {
                $value = __( 'All Events', 'tickera-event-ticketing-system' );

            } else {

                $values = is_array( $is_array = reset( $values ) ) ? $is_array : $values;

                $temp = '';
                foreach ( $values as &$value ) {

                    $event_obj = new \Tickera\TC_Event( $value );
                    $event_object = $event_obj->details;
                    $temp .= $event_object->post_title . '<br>';
                }
                $value = $temp;
            }

        } elseif ( 'api_username' == $var_name ) {

            if ( '' == trim( $value ) ) {
                $value = __( 'Administrator', 'tickera-event-ticketing-system' );

            } else {

                $users = get_users( [
                    'blog_id' => (int) $GLOBALS[ 'blog_id' ],
                    'search' => $value
                ] );

                $user = ( isset( $users[ 0 ] ) ) ? $users[ 0 ] : $users;
                $value = isset( $user )
                    ? '<a target="_blank" href="' . esc_url( admin_url( 'user-edit.php?user_id=' . @$user->ID ) ) . '">' . esc_html( @$user->user_login . ' ' . ( isset( $user->display_name ) && '' != $user->display_name ? '(' . $user->display_name . ')' : '' ) ) . '</a>'
                    : __( 'Wrong user. API will be available to the administrators only.', 'tickera-event-ticketing-system' );
            }
        }

        return $value;
    }
}

/**
 * Deprecated function "tc_ticket_field_value".
 * @since 3.5.3.0
 */
add_filter( 'tc_ticket_field_value', 'tickera_ticket_field_value', 10, 3 );
if ( ! function_exists( 'tickera_ticket_field_value' ) ) {

    function tickera_ticket_field_value( $value, $post_field_type, $var_name ) {

        $quantity_available = 0;

        if ( $var_name == 'event_name' ) {
            $event_obj = new \Tickera\TC_Event( $value );
            $event_object = $event_obj->details;
            $value = $event_object->post_title;
        }

        if ( $var_name == 'quantity_available' ) {
            $quantity_available = $value;
            if ( $value == 0 || $value == '' ) {
                $value = __( 'Unlimited', 'tickera-event-ticketing-system' );
            }
        }


        if ( $var_name == 'min_tickets_per_order' ) {
            if ( $value == 0 || $value == '' ) {
                $value = __( 'No minimum', 'tickera-event-ticketing-system' );
            }
        }

        if ( $var_name == 'max_tickets_per_order' ) {
            if ( $value == 0 || $value == '' ) {
                $value = __( 'No maximum', 'tickera-event-ticketing-system' );
            }
        }

        if ( $var_name == 'ticket_fee' ) {
            if ( $value == 0 || $value == '' ) {
                $value = __( '-', 'tickera-event-ticketing-system' );
            } else {
                $value = $value;
            }
        }

        if ( $var_name == 'ticket_fee_type' ) {
            if ( $value == 'fixed' ) {
                $value = 'Fixed';
            } else {
                $value = 'Percentage';
            }
        }

        return $value;
    }
}

/**
 * Deprecated function "tc_discount_values".
 * @since 3.5.3.0
 */
add_filter( 'tc_discount_field_value', 'tickera_discount_values', 10, 3 );
if ( ! function_exists( 'tickera_discount_values' ) ) {

    function tickera_discount_values( $value, $post_field_type, $var_name ) {
        global $tc_last_discount_code;

        switch ( $var_name ) {

            case 'post_title':
                $tc_last_discount_code = $value;
                break;

            case 'usage_limit':
            case 'discount_per_user':
                $value = ( ! $value ) ? __( 'Unlimited', 'tickera-event-ticketing-system' ) : $value;
                break;

            case 'discount_availability':
            case 'discount_on_user_roles':

                if ( $value ) {
                    $values = ! is_array( $value ) ? explode( ',', $value ) : $value;

                    $value_combined = '';
                    foreach ( array_filter( $values ) as $val ) {

                        if ( 'discount_availability' == $var_name ) {

                            $ticket_obj = new \Tickera\TC_Ticket( $val );

                            if ( ! is_null( $ticket_obj->details ) ) {
                                $event_obj = new \Tickera\TC_Event( $ticket_obj->details->event_name );
                                $event_details = ( $event_obj->id ) ? ' (' . $event_obj->details->post_title . ')' : '';
                                $value_combined .= $ticket_obj->details->post_title . $event_details . '<br />';

                            } else {
                                $value_combined = __( 'Assigned ticket type(s) no longer exists.', 'tickera-event-ticketing-system' );
                            }

                        } else {
                            $value_combined .= ucfirst( $val ) . '<br />';
                        }
                    }

                    $value = $value_combined;

                } else {
                    $value = __( 'All', 'tickera-event-ticketing-system' );
                }
                break;

            case 'discount_type':

                if ( 1 == $value ) {
                    $value = __( 'Fixed Amount (per item)', 'tickera-event-ticketing-system' );

                } elseif ( 2 == $value ) {
                    $value = __( 'Percentage (%)', 'tickera-event-ticketing-system' );

                } else {
                    $value = __( 'Fixed Amount (per order)', 'tickera-event-ticketing-system' );
                }
                break;

            case 'discount_on_returning_customer':
                $value = ( 'yes' == $value ) ? __( 'Yes', 'tickera-event-ticketing-system' ) : __( 'No', 'tickera-event-ticketing-system' );
                break;
        }
        return $value;
    }
}
