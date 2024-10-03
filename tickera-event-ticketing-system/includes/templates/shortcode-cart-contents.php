<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $tc, $tc_cart_errors, $discount;
$html = '';

$session_cart_errors = $tc->session->get( 'tc_cart_errors' );
if ( !is_null( $session_cart_errors ) && $session_cart_errors ) {
    // Retrieve error from session
    $html .= "<ul><li>" . ( $session_cart_errors ? wp_kses_post( $session_cart_errors ) : '' ) . "</li></ul>";
    $tc->session->drop( 'tc_cart_errors' );

} elseif ( '' != apply_filters( 'tc_cart_errors', '' ) ) {
    $html .= '<ul>' . apply_filters( 'tc_cart_errors', '' ) . '</ul>';

} else {
    // Retrieve error messages from global variable
    $html .= $tc_cart_errors ? wp_kses_post( $tc_cart_errors ) : '';
}

$session_cart_ticket_error_ids = $tc->session->get( 'tc_cart_ticket_error_ids' );
if ( !is_null( $session_cart_ticket_error_ids ) ) {

    $tc_ticket_names = '';
    $tc_ticket_count = count( $session_cart_ticket_error_ids );
    $tc_ticket_foreach = 1;
    $tc_ticket_ids = $session_cart_ticket_error_ids;

    $html .= '<ul>';
    foreach ( $tc_ticket_ids as $tc_ticket_id ) {
        $tc_ticket_name = get_the_title( $tc_ticket_id );
        $html .= '<li>' . sprintf(
                /* translators: %s: The ticket type name. */
                __( '%s has been sold out.', 'tickera-event-ticketing-system' ),
                esc_html( $tc_ticket_name )
            ) . '</li>';
    }
    $html .= '</ul>';
    $tc->session->drop( 'tc_cart_ticket_error_ids' );
} ?>
<div class="tc_cart_errors"><?php echo wp_kses_post( $html ); ?></div>
<?php
$discount = new \Tickera\TC_Discounts();
$cart_contents = $tc->get_cart_cookie();
$tc_general_settings = get_option( 'tickera_general_setting', false );

$session_cart_subtotal = $tc->session->get( 'tc_cart_subtotal' );
$session_discount_code = $tc->session->get( 'tc_discount_code' );

if ( isset( $_POST[ 'coupon_code' ] ) && ! $_POST[ 'coupon_code' ] ) {
    $discount->unset_discount();

} elseif ( !is_null( $session_cart_subtotal ) && !is_null( $session_discount_code ) ) {
    $discount->discounted_cart_total( (float) $session_cart_subtotal, sanitize_text_field( $session_discount_code ) );

} elseif ( !is_null( $session_discount_code ) && $session_discount_code ) {
    $discount->discounted_cart_total( false, sanitize_text_field( $session_discount_code ) );
}

$session_remove_from_cart = $tc->session->get( 'tc_remove_from_cart' );
if ( !is_null( $session_remove_from_cart ) ) {
    foreach ( $session_remove_from_cart as $tc_remove_id ) {
        $tc->session->drop( (int) $tc_remove_id );
        $tc->session->drop( 'tc_remove_from_cart' );
    }
}

if ( isset( $tc_general_settings[ 'force_login' ] ) && 'yes' == $tc_general_settings[ 'force_login' ] && ! is_user_logged_in() ) : ?>
    <div class="force_login_message"><?php
        echo wp_kses_post( sprintf(
            /* translators: %s: Admin login url */
            __( 'Please <a href="%s">Log In</a> to see this page', 'tickera-event-ticketing-system' ),
            esc_url( apply_filters( 'tc_force_login_url', wp_login_url( $tc->get_cart_slug( true ) ), $tc->get_cart_slug( true ) ) )
        ) );
    ?></div>
<?php else :
    if ( ! empty( $cart_contents ) ) :

        /**
         * Initialize global variables for cart totals.
         */
        global $total_fees, $tax_value, $subtotal_value;
        $total_fees = 0;
        $tax_value = 0;
        $subtotal_value = 0;

        ?>
        <form id="tickera_cart" method="post" class="tickera" name="tickera_cart" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <div class="tc-cart-form-inner">
                <input type="hidden" name="action" value="tickera_cart">
                <input type="hidden" name="cart_action" id="cart_action" value="update_cart"/>
                <div class="tc-cart-form-widget">
                    <div class="tickera-checkout">
                        <table cellspacing="0" class="tickera_table" cellpadding="10">
                            <thead>
                            <tr>
                                <?php do_action( 'tc_cart_col_title_before_ticket_type' ); ?>
                                <th><?php esc_html_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?></th>
                                <?php do_action( 'tc_cart_col_title_before_ticket_price' ); ?>
                                <th class="ticket-price-header"><?php esc_html_e( 'Ticket Price', 'tickera-event-ticketing-system' ); ?></th>
                                <?php do_action( 'tc_cart_col_title_before_quantity' ); ?>
                                <th><?php esc_html_e( 'Quantity', 'tickera-event-ticketing-system' ); ?></th>
                                <?php do_action( 'tc_cart_col_title_before_total_price' ); ?>
                                <th><?php esc_html_e( 'Subtotal', 'tickera-event-ticketing-system' ); ?></th>
                                <?php do_action( 'tc_cart_col_title_after_total_price' ); ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php

                            $cart_subtotal = 0;

                            foreach ( $cart_contents as $ticket_type => $ordered_count ) {

                                $ticket = new \Tickera\TC_Ticket( $ticket_type );

                                if ( ! empty( $ticket->details->post_title ) && ( 'tc_tickets' == get_post_type( $ticket_type ) || 'product' == get_post_type( $ticket_type ) ) ) {

                                    // Sum of cart's tickets subtotal
                                    $cart_subtotal = $cart_subtotal + ( tickera_get_ticket_price( $ticket->details->ID ) * $ordered_count );

                                    // Used to calculate discount and individual ticket's total values
                                    $tc->session->set( 'cart_subtotal_pre', $cart_subtotal );

                                    // Allow developer to disable quantity selector
                                    $editable_qty = (bool) apply_filters( 'tc_editable_quantity', true, $ticket_type, $ordered_count );

                                    // Used to calculate fee and tax. Preserve the value even when tc_cart shortcode is being rendered multiple times. Currently used in internal-hooks.php
                                    $subtotal_value = $cart_subtotal;
                                    ?>
                                    <tr>
                                        <?php do_action( 'tc_cart_col_value_before_ticket_type', $ticket_type, $ordered_count, tickera_get_ticket_price( $ticket->details->ID ) ); ?>
                                        <td class="ticket-type"><?php echo esc_html( apply_filters( 'tc_cart_col_before_ticket_name', $ticket->details->post_title, $ticket->details->ID ) ); ?> <?php do_action( 'tc_cart_col_after_ticket_type', $ticket, false ); ?>
                                            <input type="hidden" name="ticket_cart_id[]" value="<?php echo esc_attr( (int) $ticket_type ); ?>">
                                        </td>
                                        <?php do_action( 'tc_cart_col_value_before_ticket_price', $ticket_type, $ordered_count, tickera_get_ticket_price( $ticket->details->ID ) ); ?>
                                        <td class="ticket-price">
                                            <span class="ticket_price"><?php echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_price_per_ticket', tickera_get_ticket_price( $ticket->details->ID ), $ticket_type ) ) ); ?></span>
                                        </td>
                                        <?php do_action( 'tc_cart_col_value_before_quantity', $ticket_type, $ordered_count, tickera_get_ticket_price( $ticket->details->ID ) ); ?>
                                        <td class="ticket-quantity ticket_quantity"><?php echo esc_html( $editable_qty ? '' : $ordered_count ); ?>
                                            <?php if ( $editable_qty ) { ?>
                                                <input class="tickera_button minus" type="button" value="-">
                                            <?php } ?>
                                            <input type="<?php echo esc_attr( $editable_qty ? 'text' : 'hidden' ); ?>" name="ticket_quantity[]" value="<?php echo esc_attr( (int) $ordered_count ); ?>" class="quantity" autocomplete="off">
                                            <?php if ( $editable_qty ) { ?>
                                                <input class="tickera_button plus" type="button" value="+"/>
                                            <?php } ?></td>
                                        <?php do_action( 'tc_cart_col_value_before_total_price', $ticket_type, $ordered_count, tickera_get_ticket_price( $ticket->details->ID ) ); ?>
                                        <td class="ticket-total"><span class="ticket_total"><?php echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_price_per_ticket_and_quantity', ( tickera_get_ticket_price( $ticket->details->ID ) * $ordered_count ), $ticket_type, $ordered_count ) ) ); ?></span>
                                        </td>
                                        <?php do_action( 'tc_cart_col_value_after_total_price', $ticket_type, $ordered_count, tickera_get_ticket_price( $ticket->details->ID ) ); ?>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                            <tr class="last-table-row">
                                <td class="ticket-total-all" colspan="<?php echo esc_attr( apply_filters( 'tc_cart_table_colspan', '5' ) ); ?>">
                                    <?php do_action( 'tc_cart_col_value_before_total_price_subtotal', apply_filters( 'tc_cart_subtotal', $cart_subtotal ) ); ?>
                                    <div>
                                        <span class="total_item_title"><?php esc_html_e( 'SUBTOTAL: ', 'tickera-event-ticketing-system' ); ?></span>
                                        <span class="total_item_amount"><?php echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_subtotal', $cart_subtotal ) ) ); ?></span>
                                    </div>
                                    <?php do_action( 'tc_cart_col_value_before_total_price_discount', apply_filters( 'tc_cart_discount', 0 ) ); ?>
                                    <?php if ( ! isset( $tc_general_settings[ 'show_discount_field' ] ) || ( isset( $tc_general_settings[ 'show_discount_field' ] ) && 'yes' == $tc_general_settings[ 'show_discount_field' ] ) ) : ?>
                                        <div>
                                            <span class="total_item_title"><?php esc_html_e( 'DISCOUNT: ', 'tickera-event-ticketing-system' ); ?></span>
                                            <span class="total_item_amount"><?php echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_discount', 0 ) ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php do_action( 'tc_cart_col_value_before_total_price_total', apply_filters( 'tc_cart_total', $cart_subtotal ) ); ?>
                                    <div>
                                        <span class="total_item_title cart_total_price_title"><?php esc_html_e( 'TOTAL: ', 'tickera-event-ticketing-system' ); ?></span>
                                        <span class="total_item_amount cart_total_price"><?php echo esc_html( apply_filters( 'tc_cart_currency_and_format', apply_filters( 'tc_cart_total', $cart_subtotal ) ) ); ?></span>
                                    </div>
                                    <?php do_action( 'tc_cart_col_value_after_total_price_total' ); ?>
                                </td>
                                <?php do_action( 'tc_cart_col_value_after_total_price_total' ); ?>
                            </tr>
                            <tr>
                                <td class="actions" colspan="<?php echo esc_attr( apply_filters( 'tc_cart_table_colspan', '5' ) ); ?>">
                                    <?php do_action( 'tc_cart_before_discount_field' ); ?>
                                    <?php if ( ! isset( $tc_general_settings[ 'show_discount_field' ] ) || ( isset( $tc_general_settings[ 'show_discount_field' ] ) && 'yes' == $tc_general_settings[ 'show_discount_field' ] ) ) : ?>
                                        <input type="text" name="coupon_code" id="coupon_code" placeholder="<?php esc_html_e( "Discount Code", "tickera-event-ticketing-system" ); ?>" class="coupon_code tickera-input-field coupon-code" value="<?php echo esc_attr( ( isset( $_POST[ 'coupon_code' ] ) && ! empty( $_POST[ 'coupon_code' ] ) ? sanitize_text_field( $_POST[ 'coupon_code' ] ) : ( !is_null( $session_discount_code ) ? sanitize_text_field( $session_discount_code ) : '' ) ) ); ?>"/>
                                        <input type="submit" id="apply_coupon" value="<?php esc_html_e( "Apply", "tickera-event-ticketing-system" ); ?>" class="apply_coupon tickera-button" formnovalidate>
                                        <span class="coupon-code-message"><?php echo esc_html( $discount->discount_message ); ?></span>
                                        <?php do_action( 'tc_cart_after_discount_field' ); ?>
                                    <?php endif; ?>
                                    <input type="submit" id="update_cart" value="<?php esc_html_e( "Update Cart", "tickera-event-ticketing-system" ); ?>" class="tickera_update tickera-button" formnovalidate>
                                    <input type="submit" id="empty_cart" value="<?php esc_html_e( "Empty Cart", "tickera-event-ticketing-system" ); ?>" class="tickera_update tickera-button" formnovalidate>
                                    <?php do_action( 'tc_cart_after_update_cart' ); ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tickera_additional_info">
                    <div class="tickera_buyer_info info_section">
                        <h3><?php esc_html_e( 'Buyer Info', 'tickera-event-ticketing-system' ); ?></h3>
                        <?php
                        $buyer_form = new \Tickera\TC_Cart_Form();
                        $buyer_form_fields = $buyer_form->get_buyer_info_fields();

                        foreach ( $buyer_form_fields as $field ) {

                            if ( 'function' == $field[ 'field_type' ] ) {
                                call_user_func( $field[ 'function' ], $field );

                            } elseif ( 'label' == $field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ) ?>"><?php echo wp_kses_post( '<' . $field[ 'field_tag' ] . '>' . $field[ 'field_title' ] . '</' . $field[ 'field_tag' ] . '>' ); ?></div><?php

                            } elseif ( in_array( $field[ 'field_type' ], [ 'text', 'date', 'number' ] ) ) {
                                $min = isset( $field[ 'field_min' ] ) ? $field[ 'field_min' ] : '';
                                $max = isset( $field[ 'field_max' ] ) ? $field[ 'field_max' ] : '';
                                $step = isset( $field[ 'field_step' ] ) ? $field[ 'field_step' ] : ''; ?>
                                <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                        <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ? stripslashes( sanitize_text_field( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ) : $buyer_form->get_default_value( $field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"<?php echo wp_kses_post( ( $min ? ' min="' . esc_attr( $min ) . '"' : '' ) . ( $max ? ' max="' . esc_attr( $max ) . '"' : '' ) . ( $step ? ' step="' . esc_attr( $step ) . '"' : '' ) ) ?>>
                                    </label>
                                    <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                    <?php if ( $field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div><?php

                            } elseif ( 'email' == $field[ 'field_type' ] ) {
                                if ( ( isset( $tc_general_settings[ 'email_verification_buyer_owner' ] ) && 'yes' == $tc_general_settings[ 'email_verification_buyer_owner' ] && 'confirm_email' == $field[ 'field_name' ] ) || $field[ 'field_name' ] !== 'confirm_email' ) { ?>
                                    <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_confirm_' . $field[ 'validation_type' ] : ''; ?>">
                                        <label>
                                            <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                            <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ? stripslashes( sanitize_text_field( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ) : $buyer_form->get_default_value( $field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>">
                                        </label>
                                        <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                        <?php if ( $field[ 'required' ] ) { ?>
                                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                        <?php } ?>
                                    </div>
                                <?php }

                            } elseif ( 'textarea' == $field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                    </label>
                                    <textarea class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" placeholder="<?php echo esc_html( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"><?php echo esc_textarea( isset( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ? stripslashes( sanitize_text_field( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ) : $buyer_form->get_default_value( $field ) ); ?></textarea>
                                    <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                    <?php if ( $field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div><?php

                            } elseif ( 'radio' == $field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                    <label><span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span></label>
                                    <?php if ( isset( $field[ 'field_values' ] ) ) {
                                        $field_values = explode( ',', $field[ 'field_values' ] );
                                        foreach ( $field_values as $field_value ) { ?>
                                            <label>
                                                <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $field_value ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>" <?php if ( tickera_cart_field_get_radio_value_checked( $field, $field_value, $field_values, esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ) ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $field_value ) ); ?>
                                            </label>
                                        <?php } ?>
                                    <input type="text" class="validation tickera-input-field tc-hidden-important" value=""/>
                                    <?php } ?>
                                    <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                    <?php if ( $field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div><?php

                            } elseif ( 'checkbox' == $field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                    </label>
                                    <?php if ( isset( $field[ 'field_values' ] ) ) {
                                        $field_values = explode( ',', $field[ 'field_values' ] );
                                        foreach ( $field_values as $field_value ) { ?>
                                            <label>
                                                <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $field_value ) ); ?>" <?php if ( tickera_cart_field_get_checkbox_value_checked( $field, $field_value, $field_values, esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ) ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $field_value ) ); ?>
                                            </label>
                                        <?php } ?>
                                        <input type="text" class="checkbox_values tickera-input-field tc-hidden-important" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>" value="<?php echo esc_attr( tickera_cart_field_posted_values( esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ) ) ); ?>"/>
                                    <?php } ?>
                                    <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                    <?php if ( $field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div><?php

                            } elseif ( 'select' == $field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                        <select class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>">
                                            <option value=""><?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) ? $field[ 'field_placeholder' ] : '' ); ?></option>
                                            <?php if ( isset( $field[ 'field_values' ] ) ) {
                                                $field_values = explode( ',', $field[ 'field_values' ] );
                                                foreach ( $field_values as $field_value ) { ?>
                                                    <option value="<?php echo esc_attr( trim( $field_value ) ); ?>" <?php if ( tickera_cart_field_get_option_value_selected( $field, $field_value, esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ) ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $field_value ) ); ?></option>
                                                <?php } ?>
                                            <?php } ?>
                                        </select>
                                    </label>
                                    <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                    <?php if ( $field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <?php $show_owner_fields = ( ! isset( $tc_general_settings[ 'show_owner_fields' ] ) || ( isset( $tc_general_settings[ 'show_owner_fields' ] ) && 'yes' == $tc_general_settings[ 'show_owner_fields' ] ) ) ? true : false; ?>
                    <div class="tickera_owner_info info_section">
                        <?php
                        if ( $show_owner_fields ) {
                            $ticket_type_order = 1;

                            foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                                $ticket = new \Tickera\TC_Ticket( $ticket_type );

                                if ( ! empty( $ticket->details->post_title ) && in_array( get_post_type( $ticket_type ), [ 'tc_tickets', 'product' ] ) ) {
                                    $owner_form = new \Tickera\TC_Cart_Form( $ticket_type );
                                    $owner_form_fields = $owner_form->get_owner_info_fields( $ticket_type );
                                    $form_visibilities = array_column( $owner_form_fields, 'form_visibility' );
                                    $show_field = ( ! in_array( true, $form_visibilities ) ) ? 'tc-hidden' : '';
                                    ?>
                                    <div class="tc-form-ticket-fields-wrap <?php echo esc_html( $show_field ); ?>">
                                        <h2>
                                            <?php
                                            do_action( 'tc_before_checkout_owner_info_ticket_title', $ticket_type, $cart_contents );
                                            echo esc_html( apply_filters( 'tc_checkout_owner_info_ticket_title', $ticket->details->post_title, $ticket_type, $cart_contents, false ) );
                                            do_action( 'tc_after_checkout_owner_info_ticket_title', $ticket_type, $cart_contents );
                                            ?>
                                        </h2>
                                        <?php for ( $i = 1; $i <= $ordered_count; $i++ ) {
                                            $owner_index = $i - 1; ?>
                                            <div class="owner-info-wrap">
                                                <h5>
                                                <?php
                                                    echo wp_kses_post( apply_filters( 'tc_cart_attendee_info_caption', sprintf(
                                                        /* translators: %s: The prefix sequence of attendee info header in the checkout page. */
                                                        __( '%s. Attendee Info', 'tickera-event-ticketing-system' ),
                                                        $i
                                                    ), $ticket, $owner_index ) );
                                                ?>
                                                </h5>
                                                <?php
                                                do_action( 'tc_cart_before_attendee_info_wrap', $ticket, $owner_index );
                                                foreach ( $owner_form_fields as $field ) { ?>

                                                    <?php if ( 'function' == $field[ 'field_type' ] ) {
                                                        $array_of_arguments = [];
                                                        $array_of_arguments[] = isset( $field[ 'field_name' ] ) ? $field[ 'field_name' ] : '';
                                                        $array_of_arguments[] = isset( $field[ 'post_field_type' ] ) ? $field[ 'post_field_type' ] : '';
                                                        $array_of_arguments[] = $ticket_type;
                                                        $array_of_arguments[] = $ordered_count;
                                                        $array_of_arguments[] = $owner_index;
                                                        $array_of_arguments[] = $field;
                                                        call_user_func_array( $field[ 'function' ], $array_of_arguments );

                                                    } elseif ( 'label' == $field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] );?>"><?php echo wp_kses_post( '<' . $field[ 'field_tag' ] . '>' . $field[ 'field_title' ] . '</' . $field[ 'field_tag' ] . '>' ); ?></div><?php

                                                    } elseif ( in_array( $field[ 'field_type' ], [ 'text', 'number' ] ) ) {

                                                        $posted_name = 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ];
                                                        $posted_value = ( isset( $_POST[ $posted_name ] ) ) ? ( isset( $_POST[ $posted_name ][ $ticket_type ][ $owner_index ] ) ? sanitize_text_field( $_POST[ $posted_name ][ $ticket_type ][ $owner_index ] ) : '' ) : '';
                                                        $min = isset( $field[ 'field_min' ] ) ? $field[ 'field_min' ] : '';
                                                        $max = isset( $field[ 'field_max' ] ) ? $field[ 'field_max' ] : '';
                                                        $step = isset( $field[ 'field_step' ] ) ? $field[ 'field_step' ] : '';

                                                        if ( ( isset( $tc_general_settings[ 'show_owner_email_field' ] ) && 'yes' == $tc_general_settings[ 'show_owner_email_field' ] && 'owner_email' == $field[ 'field_name' ] ) || $field[ 'field_name' ] !== 'owner_email' ) { ?>
                                                            <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                                                <label>
                                                                    <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                                                </label>
                                                                <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_email' == $field[ 'field_name' ] ) { ?>tc_owner_email<?php } ?>" value="<?php echo esc_attr( stripslashes( $posted_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int)$ticket_type ); ?>][<?php echo esc_attr( (int)$owner_index ); ?>]"<?php echo wp_kses_post( ( $min ? ' min="' . esc_attr( $min ) . '"' : '' ) . ( $max ? ' max="' . esc_attr( $max ) . '"' : '' ) . ( $step ? ' step="' . esc_attr( $step ) . '"' : '' ) ) ?>>
                                                                <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                                                <?php if ( $field[ 'required' ] ) { ?>
                                                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                                                <?php } ?>
                                                            </div>
                                                        <?php }

                                                    } elseif ( 'email' == $field[ 'field_type' ] ) { ?>
                                                        <?php if ( ( isset( $tc_general_settings[ 'email_verification_buyer_owner' ] ) && ( isset( $tc_general_settings[ 'show_owner_email_field' ] ) ) && 'yes' == $tc_general_settings[ 'email_verification_buyer_owner' ] && 'yes' == $tc_general_settings[ 'show_owner_email_field' ] && ( 'owner_confirm_email' == $field[ 'field_name' ] ) || $field[ 'field_name' ] !== 'owner_confirm_email' ) ) { ?>
                                                            <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                                                <?php
                                                                    $posted_name = 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ];
                                                                    $posted_value = ( isset( $_POST[ $posted_name ] ) ) ? ( isset( $_POST[ $posted_name ][ $ticket_type ][ $owner_index ] ) ? sanitize_text_field( $_POST[ $posted_name ][ $ticket_type ][ $owner_index ] ): '' ) : '';
                                                                ?>
                                                                <label>
                                                                    <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                                                </label>
                                                                <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_confirm_email' == $field[ 'field_name' ] ) { ?>tc_owner_confirm_email<?php } ?>" value="<?php echo esc_attr( stripslashes( $posted_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]">
                                                                <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                                                <?php if ( $field[ 'required' ] ) { ?>
                                                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                                                <?php } ?>
                                                            </div>
                                                        <?php }

                                                    } elseif ( 'date' == $field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                                            <?php
                                                                $posted_name = 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ];
                                                                $posted_value = ( isset( $_POST[ $posted_name ] ) ) ? ( isset( $_POST[ $posted_name ][ $ticket_type ][ $owner_index ] ) ? sanitize_text_field( $_POST[ $posted_name ][ $ticket_type ][ $owner_index ] ) : '' ) : '';
                                                            ?>
                                                            <label>
                                                                <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : '' ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_email' == $field[ 'field_name' ] ) { ?>tc_owner_email<?php } ?>" value="<?php echo esc_attr( stripslashes ( $posted_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]">
                                                            <span class="description"><?php echo esc_html($field[ 'field_description' ]); ?></span>
                                                            <?php if ( $field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php

                                                    } elseif ( 'textarea' == $field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                                            <label>
                                                                <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <?php
                                                                $posted_name = esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] );
                                                                $posted_value = ( isset( $_POST[ $posted_name ] ) ) ? ( isset( $_POST[ $posted_name ][ $ticket_type ][ $owner_index ] ) ? sanitize_text_field( $_POST[ $posted_name ][ $ticket_type ][ $owner_index ] ) : '' ) : '';
                                                            ?>
                                                            <textarea class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]"><?php echo esc_textarea( stripslashes( $posted_value ) ); ?></textarea>
                                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                                            <?php if ( $field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php

                                                    } elseif ( 'radio' == $field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                                            <label>
                                                                <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <?php if ( isset( $field[ 'field_values' ] ) ) {
                                                                $field_values = explode( ',', $field[ 'field_values' ] );
                                                                foreach ( $field_values as $field_value ) { ?>
                                                                    <label>
                                                                        <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $field_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]" <?php if ( tickera_cart_field_get_radio_value_checked( $field, $field_value, $field_values, ( esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ) ), $ticket_type, $owner_index ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $field_value ) ); ?>
                                                                    </label>
                                                                <?php } ?>
                                                            <input type="text" class="validation tickera-input-field tc-hidden-important" value=""/>
                                                            <?php } ?>
                                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                                            <?php if ( $field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php

                                                    } elseif ( 'checkbox' == $field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_html( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                                            <label>
                                                                <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <?php if ( isset( $field[ 'field_values' ] ) ) {
                                                                $field_values = explode( ',', $field[ 'field_values' ] );
                                                                foreach ( $field_values as $field_value ) { ?>
                                                                    <label>
                                                                        <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $field_value ) ); ?>" <?php if ( tickera_cart_field_get_checkbox_value_checked( $field, $field_value, $field_values, esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ), $ticket_type, $owner_index ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $field_value ) ); ?>
                                                                    </label>
                                                                <?php } ?>
                                                                <input type="text" class="checkbox_values tickera-input-field tc-hidden-important" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]" value="<?php echo esc_attr( tickera_cart_field_posted_values( esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ), $ticket_type, $owner_index ) ); ?>"/>
                                                            <?php } ?>
                                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                                            <?php if ( $field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php

                                                    } elseif ( 'select' == $field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                                            <label>
                                                                <span><?php echo esc_html( $field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <select class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]">
                                                                <option value=""><?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) ? esc_attr( $field[ 'field_placeholder' ] ) : '' ); ?></option>
                                                                <?php if ( isset( $field[ 'field_values' ] ) ) {
                                                                    $field_values = explode( ',', $field[ 'field_values' ] );
                                                                    foreach ( $field_values as $field_value ) { ?>
                                                                        <option value="<?php echo esc_attr( trim( $field_value ) ); ?>" <?php if ( tickera_cart_field_get_option_value_selected( $field, $field_value, esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ), $ticket_type, $owner_index ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $field_value ) ); ?></option>
                                                                    <?php } ?>
                                                                <?php } ?>
                                                            </select>
                                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                                            <?php if ( $field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php
                                                    }
                                                } ?>
                                                <div class="tc-clearfix"></div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php }
                            }

                        } else {

                            /**
                             * If Show attendee's fields is disabled. Configured from the Tickera > Settings > General
                             */
                            foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                                $ticket = new \Tickera\TC_Ticket( $ticket_type );

                                if ( ! empty( $ticket->details->post_title ) && in_array( get_post_type( $ticket_type ), [ 'tc_tickets', 'product' ] ) ) {
                                    $owner_form = new \Tickera\TC_Cart_Form( $ticket_type );
                                    $owner_form_fields = $owner_form->get_owner_info_fields( $ticket_type );
                                    ?>
                                    <div class="tc-form-ticket-fields-wrap">
                                        <?php for ( $i = 1; $i <= $ordered_count; $i++ ) {
                                            $owner_index = $i - 1;
                                            ?>
                                            <div class="owner-info-wrap">
                                                <?php
                                                    do_action( 'tc_cart_before_attendee_info_wrap', $ticket, $owner_index );
                                                    foreach ( $owner_form_fields as $field ) {

                                                        if (
                                                            ( ! isset( $field[ 'form_visibility' ] ) && 'ticket_type_id' == $field[ 'field_name' ] ) ||
                                                            ( isset( $field[ 'form_visibility' ] ) && ! $field[ 'form_visibility' ] )
                                                        ) {
                                                            $array_of_arguments = [];
                                                            $array_of_arguments[] = isset( $field[ 'field_name' ] ) ? $field[ 'field_name' ] : '';
                                                            $array_of_arguments[] = isset( $field[ 'post_field_type' ] ) ? $field[ 'post_field_type' ] : '';
                                                            $array_of_arguments[] = $ticket_type;
                                                            $array_of_arguments[] = $ordered_count;
                                                            $array_of_arguments[] = $owner_index;
                                                            $array_of_arguments[] = $field;
                                                            call_user_func_array( $field[ 'function' ], $array_of_arguments );
                                                        }
                                                } ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php }
                            }
                        } ?>
                    </div><?php
                    do_action( 'before_cart_submit' );
                    do_action( 'tc_before_cart_submit' );
                    do_action( 'tc_only_before_cart_submit' ); ?>
                    <div class="proceed-to-checkout-container">
                        <input type="submit" id="proceed_to_checkout" name="proceed_to_checkout" value="<?php esc_html_e( "Proceed to Checkout", "tickera-event-ticketing-system" ); ?>" class="tickera_checkout tickera-button"/>
                    </div>
                </div>
            </div>
            <div><?php wp_nonce_field( 'page_cart' ); ?></div>
        </form>
    <?php else : ?>
        <?php do_action( 'tc_empty_cart' ); ?>
        <div class="cart_empty_message"><?php esc_html_e( "The cart is empty.", "tickera-event-ticketing-system" ); ?></div>
    <?php endif; ?>
<?php endif;
