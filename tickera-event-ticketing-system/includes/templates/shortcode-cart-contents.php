<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
global $tc, $tickera_cart_errors, $tickera_discount;
$tickera_html = '';

$tickera_session_cart_errors = $tc->session->get( 'tc_cart_errors' );
if ( !is_null( $tickera_session_cart_errors ) && $tickera_session_cart_errors ) {
    // Retrieve error from session
    $tickera_html .= "<ul><li>" . ( $tickera_session_cart_errors ? wp_kses_post( $tickera_session_cart_errors ) : '' ) . "</li></ul>";
    $tc->session->drop( 'tc_cart_errors' );

} elseif ( '' != tickera_apply_filters( 'tickera_cart_errors', '' ) ) {
    $tickera_html .= '<ul>' . tickera_apply_filters( 'tickera_cart_errors', '' ) . '</ul>';

} else {
    // Retrieve error messages from global variable
    $tickera_html .= $tickera_cart_errors ? wp_kses_post( $tickera_cart_errors ) : '';
}

$tickera_session_cart_ticket_error_ids = $tc->session->get( 'tc_cart_ticket_error_ids' );
if ( !is_null( $tickera_session_cart_ticket_error_ids ) ) {

    $tickera_ticket_names = '';
    $tickera_ticket_count = count( $tickera_session_cart_ticket_error_ids );
    $tickera_ticket_foreach = 1;
    $tickera_ticket_ids = $tickera_session_cart_ticket_error_ids;

    $tickera_html .= '<ul>';
    foreach ( $tickera_ticket_ids as $tickera_ticket_id ) {
        $tickera_ticket_name = get_the_title( $tickera_ticket_id );
        $tickera_html .= '<li>' . sprintf(
                /* translators: %s: The ticket type name. */
                __( '%s has been sold out.', 'tickera-event-ticketing-system' ),
                esc_html( $tickera_ticket_name )
            ) . '</li>';
    }
    $tickera_html .= '</ul>';
    $tc->session->drop( 'tc_cart_ticket_error_ids' );
} ?>
<div class="tc_cart_errors"><?php echo wp_kses_post( $tickera_html ); ?></div>
<?php
$tickera_discount = new \Tickera\TC_Discounts();
$tickera_cart_contents = $tc->get_cart_cookie();

$tickera_settings = get_option( 'tickera_general_setting', false );
$tickera_frontend_tooltip = isset( $tickera_settings[ 'frontend_tooltip' ] ) ? ( 'yes' == $tickera_settings[ 'frontend_tooltip' ] ? true : false ) : false; // Default true
$tickera_frontend_tooltip_quantity_selector = isset( $tickera_settings[ 'frontend_tooltip_quantity_selector' ] ) ? $tickera_settings[ 'frontend_tooltip_quantity_selector' ] : __( 'Select the quantity of the ticket type.', 'tickera-event-ticketing-system' );

$tickera_show_owner_fields = isset( $tickera_settings[ 'show_owner_fields' ] ) ? $tickera_settings[ 'show_owner_fields' ] : 'yes';
$tickera_show_owner_fields = ( 'yes' == $tickera_show_owner_fields ) ? true : false;

$tickera_session_cart_subtotal = $tc->session->get( 'tc_cart_subtotal' );
$tickera_session_discount_code = $tc->session->get( 'tc_discount_code' );
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Cart coupon code is sanitized before discount validation.
$tickera_coupon_code = isset( $_POST[ 'coupon_code' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'coupon_code' ] ) ) : '';

if ( $tickera_coupon_code ) {
    $tickera_discount->unset_discount();

} elseif ( !is_null( $tickera_session_cart_subtotal ) && !is_null( $tickera_session_discount_code ) ) {
    $tickera_discount->discounted_cart_total( (float) $tickera_session_cart_subtotal, sanitize_text_field( $tickera_session_discount_code ) );

} elseif ( !is_null( $tickera_session_discount_code ) && $tickera_session_discount_code ) {
    $tickera_discount->discounted_cart_total( false, sanitize_text_field( $tickera_session_discount_code ) );
}

$tickera_session_remove_from_cart = $tc->session->get( 'tc_remove_from_cart' );
if ( !is_null( $tickera_session_remove_from_cart ) ) {
    foreach ( $tickera_session_remove_from_cart as $tickera_remove_id ) {
        $tc->session->drop( (int) $tickera_remove_id );
        $tc->session->drop( 'tc_remove_from_cart' );
    }
}

if ( isset( $tickera_settings[ 'force_login' ] ) && 'yes' == $tickera_settings[ 'force_login' ] && ! is_user_logged_in() ) : ?>
    <div class="force_login_message"><?php
        echo wp_kses_post( sprintf(
            /* translators: %s: Admin login url */
            __( 'Please <a href="%s">Log In</a> to see this page', 'tickera-event-ticketing-system' ),
            esc_url( tickera_apply_filters( 'tickera_force_login_url', wp_login_url( $tc->get_cart_slug( true ) ), $tc->get_cart_slug( true ) ) )
        ) );
    ?></div>
<?php else :
    if ( ! empty( $tickera_cart_contents ) ) :

        /**
         * Initialize global variables for cart totals.
         */
        global $tickera_total_fees, $tickera_tax_value, $tickera_subtotal_value;
        $tickera_total_fees = 0;
        $tickera_tax_value = 0;
        $tickera_subtotal_value = 0;

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
                                <?php tickera_do_action( 'tickera_cart_col_title_before_ticket_type' ); ?>
                                <th><?php esc_html_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?></th>
                                <?php tickera_do_action( 'tickera_cart_col_title_before_ticket_price' ); ?>
                                <th class="ticket-price-header"><?php esc_html_e( 'Ticket Price', 'tickera-event-ticketing-system' ); ?></th>
                                <?php tickera_do_action( 'tickera_cart_col_title_before_quantity' ); ?>
                                <th><?php esc_html_e( 'Quantity', 'tickera-event-ticketing-system' ); ?></th>
                                <?php tickera_do_action( 'tickera_cart_col_title_before_total_price' ); ?>
                                <th><?php esc_html_e( 'Subtotal', 'tickera-event-ticketing-system' ); ?></th>
                                <?php tickera_do_action( 'tickera_cart_col_title_after_total_price' ); ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php

                            $tickera_cart_subtotal = 0;

                            foreach ( $tickera_cart_contents as $tickera_ticket_type => $tickera_ordered_count ) {

                                $tickera_ticket = new \Tickera\TC_Ticket( $tickera_ticket_type );

                                if ( ! empty( $tickera_ticket->details->post_title ) && ( 'tc_tickets' == get_post_type( $tickera_ticket_type ) || 'product' == get_post_type( $tickera_ticket_type ) ) ) {

                                    // Sum of cart's tickets subtotal
                                    $tickera_cart_subtotal = $tickera_cart_subtotal + ( tickera_get_ticket_price( $tickera_ticket->details->ID ) * $tickera_ordered_count );

                                    // Used to calculate discount and individual ticket's total values
                                    $tc->session->set( 'cart_subtotal_pre', $tickera_cart_subtotal );

                                    // Allow developer to disable quantity selector
                                    $tickera_editable_qty = (bool) tickera_apply_filters( 'tickera_editable_quantity', true, $tickera_ticket_type, $tickera_ordered_count );

                                    // Used to calculate fee and tax. Preserve the value even when tc_cart shortcode is being rendered multiple times. Currently used in internal-hooks.php
                                    $tickera_subtotal_value = $tickera_cart_subtotal;

                                    $tickera_quantity_left = (int) $tickera_ticket->get_tickets_quantity_left();
                                    $tickera_min_quantity = (int) $tickera_ticket->details->min_tickets_per_order;
                                    $tickera_max_quantity = (int) $tickera_ticket->details->max_tickets_per_order;
                                    $tickera_max_quantity = ( $tickera_max_quantity && $tickera_quantity_left > $tickera_max_quantity ) ? $tickera_max_quantity : $tickera_quantity_left;
                                    ?>
                                    <tr>
                                        <?php tickera_do_action( 'tickera_cart_col_value_before_ticket_type', $tickera_ticket_type, $tickera_ordered_count, tickera_get_ticket_price( $tickera_ticket->details->ID ) ); ?>
                                        <td class="ticket-type"><?php echo esc_html( tickera_apply_filters( 'tickera_cart_col_before_ticket_name', $tickera_ticket->details->post_title, $tickera_ticket->details->ID ) ); ?> <?php tickera_do_action( 'tickera_cart_col_after_ticket_type', $tickera_ticket, false ); ?>
                                            <input type="hidden" name="ticket_cart_id[]" value="<?php echo esc_attr( (int) $tickera_ticket_type ); ?>">
                                        </td>
                                        <?php tickera_do_action( 'tickera_cart_col_value_before_ticket_price', $tickera_ticket_type, $tickera_ordered_count, tickera_get_ticket_price( $tickera_ticket->details->ID ) ); ?>
                                        <td class="ticket-price">
                                            <span class="ticket_price"><?php echo esc_html( apply_filters( 'tickera_cart_currency_and_format', tickera_apply_filters( 'tickera_cart_price_per_ticket', tickera_get_ticket_price( $tickera_ticket->details->ID ), $tickera_ticket_type ) ) ); ?></span>
                                        </td>
                                        <?php tickera_do_action( 'tickera_cart_col_value_before_quantity', $tickera_ticket_type, $tickera_ordered_count, tickera_get_ticket_price( $tickera_ticket->details->ID ) ); ?>
                                        <td class="ticket-quantity ticket_quantity"><?php echo esc_html( $tickera_editable_qty ? '' : $tickera_ordered_count ); ?>
                                            <div class="inner-wrap">
                                                <?php if ( $tickera_editable_qty ) { /* Hidden - Remove false to show */ ?>
                                                    <input class="tickera_button minus" type="button" value="-" data-action="minus"/>
                                                <?php } ?>
                                                <input type="<?php echo esc_attr( $tickera_editable_qty ? 'text' : 'hidden' ); ?>" inputmode="numeric" pattern="[0-9]*" name="ticket_quantity[]" min="<?php echo esc_attr( $tickera_min_quantity ); ?>" max="<?php echo esc_attr( $tickera_max_quantity ); ?>" value="<?php echo esc_attr( (int) $tickera_ordered_count ); ?>" class="quantity tc_quantity_selector<?php echo esc_attr( $tickera_frontend_tooltip ? ' tc-tooltip' : '' ); ?>" data-tooltip="<?php echo esc_attr( $tickera_frontend_tooltip ? $tickera_frontend_tooltip_quantity_selector : '' ); ?>" autocomplete="off">
                                                <?php if ( ! $tickera_editable_qty ) : ?>
                                                    <span><?php esc_html( $tickera_ordered_count ); ?></span>
                                                <?php endif; ?>
                                                <?php if ( $tickera_editable_qty ) { /* Hidden - Remove false to show */ ?>
                                                    <input class="tickera_button plus" type="button" value="+" data-action="plus"/>
                                                <?php } ?></td>
                                            </div>
                                        <?php tickera_do_action( 'tickera_cart_col_value_before_total_price', $tickera_ticket_type, $tickera_ordered_count, tickera_get_ticket_price( $tickera_ticket->details->ID ) ); ?>
                                        <td class="ticket-total"><span class="ticket_total"><?php echo esc_html( apply_filters( 'tickera_cart_currency_and_format', tickera_apply_filters( 'tickera_cart_price_per_ticket_and_quantity', ( tickera_get_ticket_price( $tickera_ticket->details->ID ) * $tickera_ordered_count ), $tickera_ticket_type, $tickera_ordered_count ) ) ); ?></span>
                                        </td>
                                        <?php tickera_do_action( 'tickera_cart_col_value_after_total_price', $tickera_ticket_type, $tickera_ordered_count, tickera_get_ticket_price( $tickera_ticket->details->ID ) ); ?>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                            <tr class="last-table-row">
                                <td class="ticket-total-all" colspan="<?php echo esc_attr( tickera_apply_filters( 'tickera_cart_table_colspan', '5' ) ); ?>">
                                    <?php tickera_do_action( 'tickera_cart_col_value_before_total_price_subtotal', tickera_apply_filters( 'tickera_cart_subtotal', $tickera_cart_subtotal ) ); ?>
                                    <div>
                                        <span class="total_item_title"><?php esc_html_e( 'SUBTOTAL: ', 'tickera-event-ticketing-system' ); ?></span>
                                        <span class="total_item_amount"><?php echo esc_html( apply_filters( 'tickera_cart_currency_and_format', tickera_apply_filters( 'tickera_cart_subtotal', $tickera_cart_subtotal ) ) ); ?></span>
                                    </div>
                                    <?php tickera_do_action( 'tickera_cart_col_value_before_total_price_discount', tickera_apply_filters( 'tickera_cart_discount', 0 ) ); ?>
                                    <?php if ( ! isset( $tickera_settings[ 'show_discount_field' ] ) || ( isset( $tickera_settings[ 'show_discount_field' ] ) && 'yes' == $tickera_settings[ 'show_discount_field' ] ) ) : ?>
                                        <div>
                                            <span class="total_item_title"><?php esc_html_e( 'DISCOUNT: ', 'tickera-event-ticketing-system' ); ?></span>
                                            <span class="total_item_amount"><?php echo esc_html( apply_filters( 'tickera_cart_currency_and_format', tickera_apply_filters( 'tickera_cart_discount', 0 ) ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php tickera_do_action( 'tickera_cart_col_value_before_total_price_total', tickera_apply_filters( 'tickera_cart_total', $tickera_cart_subtotal ) ); ?>
                                    <div>
                                        <span class="total_item_title cart_total_price_title"><?php esc_html_e( 'TOTAL: ', 'tickera-event-ticketing-system' ); ?></span>
                                        <span class="total_item_amount cart_total_price"><?php echo esc_html( apply_filters( 'tickera_cart_currency_and_format', tickera_apply_filters( 'tickera_cart_total', $tickera_cart_subtotal ) ) ); ?></span>
                                    </div>
                                    <?php tickera_do_action( 'tickera_cart_col_value_after_total_price_total' ); ?>
                                </td>
                                <?php tickera_do_action( 'tickera_cart_col_value_after_total_price_total' ); ?>
                            </tr>
                            <tr>
                                <td class="actions" colspan="<?php echo esc_attr( tickera_apply_filters( 'tickera_cart_table_colspan', '5' ) ); ?>">
                                    <?php tickera_do_action( 'tickera_cart_before_discount_field' ); ?>
                                    <div class="action-wrap">
                                        <?php if ( ! isset( $tickera_settings[ 'show_discount_field' ] ) || ( isset( $tickera_settings[ 'show_discount_field' ] ) && 'yes' == $tickera_settings[ 'show_discount_field' ] ) ) : ?>
                                            <div class="discount-wrap">
                                                <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Cart coupon code is sanitized before repopulating the discount field. ?>
                                                <input type="text" name="coupon_code" id="coupon_code" placeholder="<?php esc_html_e( "Discount Code", "tickera-event-ticketing-system" ); ?>" class="coupon_code tickera-input-field coupon-code" value="<?php echo esc_attr( ( isset( $_POST[ 'coupon_code' ] ) && '' !== sanitize_text_field( wp_unslash( $_POST[ 'coupon_code' ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ 'coupon_code' ] ) ) : ( !is_null( $tickera_session_discount_code ) ? sanitize_text_field( $tickera_session_discount_code ) : '' ) ) ); ?>" autocomplete="off"/>
                                                <?php if ( $tickera_discount->discount_message ) : ?>
                                                    <span class="message"><?php echo esc_html( $tickera_discount->discount_message ); ?></span>
                                                <?php endif; ?>
                                                <input type="submit" id="apply_coupon" value="<?php esc_html_e( "Apply", "tickera-event-ticketing-system" ); ?>" class="apply_coupon tickera-button <?php echo esc_attr( ( $tickera_discount->discount_message ? 'tc-hidden' : '' ) ) ?>" formnovalidate/>
                                            </div>
                                            <?php tickera_do_action( 'tickera_cart_after_discount_field' ); ?>
                                        <?php endif; ?>
                                        <div class="update-wrap">
                                            <input type="submit" id="empty_cart" value="<?php esc_html_e( "Empty Cart", "tickera-event-ticketing-system" ); ?>" class="tickera_empty tickera-button" formnovalidate/>
                                            <input type="submit" id="update_cart" value="<?php esc_html_e( "Update Cart", "tickera-event-ticketing-system" ); ?>" class="tickera_update tickera-button" formnovalidate/>
                                        </div></div><?php tickera_do_action( 'tickera_cart_after_update_cart' ); ?>
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
                        $tickera_buyer_form = new \Tickera\TC_Cart_Form();
                        $tickera_buyer_form_fields = $tickera_buyer_form->get_buyer_info_fields();

                        foreach ( $tickera_buyer_form_fields as $tickera_field ) {

                            if ( 'function' == $tickera_field[ 'field_type' ] ) {
                                call_user_func( $tickera_field[ 'function' ], $tickera_field );

                            } elseif ( 'label' == $tickera_field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ) ?>"><?php echo wp_kses_post( '<' . $tickera_field[ 'field_tag' ] . '>' . $tickera_field[ 'field_title' ] . '</' . $tickera_field[ 'field_tag' ] . '>' ); ?></div><?php

                            } elseif ( in_array( $tickera_field[ 'field_type' ], [ 'text', 'date', 'number' ] ) ) {
                                $tickera_min = ( isset( $tickera_field[ 'field_min' ] ) && $tickera_field[ 'field_min' ] ) ? $tickera_field[ 'field_min' ] : 0;
                                $tickera_max = ( isset( $tickera_field[ 'field_max' ] ) && $tickera_field[ 'field_max' ] ) ? $tickera_field[ 'field_max' ] : 0;
                                $tickera_step = ( isset( $tickera_field[ 'field_step' ] ) && $tickera_field[ 'field_step' ] ) ? $tickera_field[ 'field_step' ] : 1; ?>
                                <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                        <?php if ( 'number' == $tickera_field[ 'field_type' ] ) : ?>
                                            <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout buyer field value is sanitized before repopulating the cart form. ?>
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ) : $tickera_buyer_form->get_default_value( $tickera_field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"<?php echo wp_kses_post( ( $tickera_min ? ' min="' . esc_attr( $tickera_min ) . '"' : '' ) . ( $tickera_max ? ' max="' . esc_attr( $tickera_max ) . '"' : '' ) . ( $tickera_step ? ' step="' . esc_attr( $tickera_step ) . '"' : '' ) ) ?> autocomplete="off">
                                        <?php else : ?>
                                            <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout buyer field value is sanitized before repopulating the cart form. ?>
                                            <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ) : $tickera_buyer_form->get_default_value( $tickera_field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>">
                                        <?php endif; ?>
                                    </label>
                                    <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                    <?php if ( $tickera_field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div><?php

                            } elseif ( 'email' == $tickera_field[ 'field_type' ] ) {
                                if ( ( isset( $tickera_settings[ 'email_verification_buyer_owner' ] ) && 'yes' == $tickera_settings[ 'email_verification_buyer_owner' ] && 'confirm_email' == $tickera_field[ 'field_name' ] ) || $tickera_field[ 'field_name' ] !== 'confirm_email' ) { ?>
                                    <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_confirm_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                        <label>
                                            <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                            <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout buyer email field is sanitized before repopulating the cart form. ?>
                                            <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ) : $tickera_buyer_form->get_default_value( $tickera_field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>">
                                        </label>
                                        <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                        <?php if ( $tickera_field[ 'required' ] ) { ?>
                                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                        <?php } ?>
                                    </div>
                                <?php }

                            } elseif ( 'textarea' == $tickera_field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                    </label>
                                    <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout buyer textarea value is sanitized before repopulating the cart form. ?>
                                    <textarea class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"><?php echo esc_textarea( isset( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ) : $tickera_buyer_form->get_default_value( $tickera_field ) ); ?></textarea>
                                    <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                    <?php if ( $tickera_field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div><?php

                            } elseif ( 'radio' == $tickera_field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                    <label><span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span></label>
                                    <?php if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                        $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                        foreach ( $tickera_field_values as $tickera_field_value ) { ?>
                                            <label>
                                                <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>" <?php if ( tickera_cart_field_get_radio_value_checked( $tickera_field, $tickera_field_value, $tickera_field_values, esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ) ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?>
                                            </label>
                                        <?php } ?>
                                    <input type="text" class="validation tickera-input-field tc-hidden-important" value=""/>
                                    <?php } ?>
                                    <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                    <?php if ( $tickera_field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div><?php

                            } elseif ( 'checkbox' == $tickera_field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                    </label>
                                    <?php if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                        $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                        foreach ( $tickera_field_values as $tickera_field_value ) { ?>
                                            <label>
                                                <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( tickera_cart_field_get_checkbox_value_checked( $tickera_field, $tickera_field_value, $tickera_field_values, esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ) ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?>
                                            </label>
                                        <?php } ?>
                                        <input type="text" class="checkbox_values tickera-input-field tc-hidden-important" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>" value="<?php echo esc_attr( tickera_cart_field_posted_values( esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ) ) ); ?>"/>
                                    <?php } ?>
                                    <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                    <?php if ( $tickera_field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div><?php

                            } elseif ( 'select' == $tickera_field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                        <select class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>">
                                            <?php if ( ! $tickera_field[ 'required' ] ) : ?>
                                                <option value=""><?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : '' ); ?></option><?php
                                            endif;
                                            if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                                $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                                foreach ( $tickera_field_values as $tickera_field_value ) : ?>
                                                    <option value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( tickera_cart_field_get_option_value_selected( $tickera_field, $tickera_field_value, esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ) ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?></option>
                                                <?php endforeach;
                                            } ?>
                                        </select>
                                    </label>
                                    <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                    <?php if ( $tickera_field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <div class="tickera_owner_info info_section">
                        <?php
                        if ( $tickera_show_owner_fields ) {
                            $tickera_ticket_type_order = 1;

                            foreach ( $tickera_cart_contents as $tickera_ticket_type => $tickera_ordered_count ) {
                                $tickera_ticket = new \Tickera\TC_Ticket( $tickera_ticket_type );

                                if ( ! empty( $tickera_ticket->details->post_title ) && in_array( get_post_type( $tickera_ticket_type ), [ 'tc_tickets', 'product' ] ) ) {
                                    $tickera_owner_form = new \Tickera\TC_Cart_Form( $tickera_ticket_type );
                                    $tickera_owner_form_fields = $tickera_owner_form->get_owner_info_fields( $tickera_ticket_type );
                                    $tickera_form_visibilities = array_column( $tickera_owner_form_fields, 'form_visibility' );

                                    $tickera_show_field = ( ! in_array( true, $tickera_form_visibilities ) ) ? 'tc-hidden' : '';
                                    ?>
                                    <div class="tc-form-ticket-fields-wrap <?php echo esc_html( $tickera_show_field ); ?>">
                                        <h2>
                                            <?php
                                            tickera_do_action( 'tickera_before_checkout_owner_info_ticket_title', $tickera_ticket_type, $tickera_cart_contents );
                                            echo esc_html( tickera_apply_filters( 'tickera_checkout_owner_info_ticket_title', $tickera_ticket->details->post_title, $tickera_ticket_type, $tickera_cart_contents, false ) );
                                            tickera_do_action( 'tickera_after_checkout_owner_info_ticket_title', $tickera_ticket_type, $tickera_cart_contents );
                                            ?>
                                        </h2>
                                        <?php for ( $tickera_i = 1; $tickera_i <= $tickera_ordered_count; $tickera_i++ ) {
                                            $tickera_owner_index = $tickera_i - 1; ?>
                                            <div class="owner-info-wrap">
                                                <h5>
                                                <?php
                                                    echo wp_kses_post( tickera_apply_filters( 'tickera_cart_attendee_info_caption', sprintf(
                                                        /* translators: %s: The prefix sequence of attendee info header in the checkout page. */
                                                        __( '%s. Attendee Info', 'tickera-event-ticketing-system' ),
                                                        $tickera_i
                                                    ), $tickera_ticket, $tickera_owner_index ) );
                                                ?>
                                                </h5>
                                                <?php
                                                tickera_do_action( 'tickera_cart_before_attendee_info_wrap', $tickera_ticket, $tickera_owner_index );
                                                foreach ( $tickera_owner_form_fields as $tickera_field ) { ?>

                                                    <?php if ( 'function' == $tickera_field[ 'field_type' ] ) {
                                                        $tickera_array_of_arguments = [];
                                                        $tickera_array_of_arguments[] = isset( $tickera_field[ 'field_name' ] ) ? $tickera_field[ 'field_name' ] : '';
                                                        $tickera_array_of_arguments[] = isset( $tickera_field[ 'post_field_type' ] ) ? $tickera_field[ 'post_field_type' ] : '';
                                                        $tickera_array_of_arguments[] = $tickera_ticket_type;
                                                        $tickera_array_of_arguments[] = $tickera_ordered_count;
                                                        $tickera_array_of_arguments[] = $tickera_owner_index;
                                                        $tickera_array_of_arguments[] = $tickera_field;
                                                        call_user_func_array( $tickera_field[ 'function' ], $tickera_array_of_arguments );

                                                    } elseif ( 'label' == $tickera_field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] );?>"><?php echo wp_kses_post( '<' . $tickera_field[ 'field_tag' ] . '>' . $tickera_field[ 'field_title' ] . '</' . $tickera_field[ 'field_tag' ] . '>' ); ?></div><?php

                                                    } elseif ( in_array( $tickera_field[ 'field_type' ], [ 'text', 'number' ] ) ) {

                                                        $tickera_posted_name = 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ];
                                                        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout owner field value is sanitized before repopulating the cart form.
                                                        $tickera_posted_value = ( isset( $_POST[ $tickera_posted_name ] ) ) ? ( isset( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ? sanitize_text_field( wp_unslash( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ) : '' ) : '';
                                                        $tickera_min = ( isset( $tickera_field[ 'field_min' ] ) && $tickera_field[ 'field_min' ] ) ? $tickera_field[ 'field_min' ] : 0;
                                                        $tickera_max = ( isset( $tickera_field[ 'field_max' ] ) && $tickera_field[ 'field_max' ] ) ? $tickera_field[ 'field_max' ] : 0;
                                                        $tickera_step = ( isset( $tickera_field[ 'field_step' ] ) && $tickera_field[ 'field_step' ] ) ? $tickera_field[ 'field_step' ] : 1;

                                                        if ( ( isset( $tickera_settings[ 'show_owner_email_field' ] ) && 'yes' == $tickera_settings[ 'show_owner_email_field' ] && 'owner_email' == $tickera_field[ 'field_name' ] ) || $tickera_field[ 'field_name' ] !== 'owner_email' ) { ?>
                                                            <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                                                <label>
                                                                    <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                                                </label>
                                                                <?php if ( 'number' == $tickera_field[ 'field_type' ] ) : ?>
                                                                    <input type="text" inputmode="numeric" pattern="[0-9]*" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_email' == $tickera_field[ 'field_name' ] ) { ?>tc_owner_email<?php } ?>" value="<?php echo esc_attr( stripslashes( $tickera_posted_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int)$tickera_ticket_type ); ?>][<?php echo esc_attr( (int)$tickera_owner_index ); ?>]"<?php echo wp_kses_post( ( $tickera_min ? ' min="' . esc_attr( $tickera_min ) . '"' : '' ) . ( $tickera_max ? ' max="' . esc_attr( $tickera_max ) . '"' : '' ) . ( $tickera_step ? ' step="' . esc_attr( $tickera_step ) . '"' : '' ) ) ?> autocomplete="off">
                                                                <?php else : ?>
                                                                    <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_email' == $tickera_field[ 'field_name' ] ) { ?>tc_owner_email<?php } ?>" value="<?php echo esc_attr( stripslashes( $tickera_posted_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int)$tickera_ticket_type ); ?>][<?php echo esc_attr( (int)$tickera_owner_index ); ?>]">
                                                                <?php endif; ?>
                                                                <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                                                <?php if ( $tickera_field[ 'required' ] ) { ?>
                                                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                                                <?php } ?>
                                                            </div>
                                                        <?php }

                                                    } elseif ( 'email' == $tickera_field[ 'field_type' ] ) { ?>
                                                        <?php if ( ( isset( $tickera_settings[ 'email_verification_buyer_owner' ] ) && ( isset( $tickera_settings[ 'show_owner_email_field' ] ) ) && 'yes' == $tickera_settings[ 'email_verification_buyer_owner' ] && 'yes' == $tickera_settings[ 'show_owner_email_field' ] && ( 'owner_confirm_email' == $tickera_field[ 'field_name' ] ) || $tickera_field[ 'field_name' ] !== 'owner_confirm_email' ) ) { ?>
                                                            <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                                                <?php
                                                                    $tickera_posted_name = 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ];
                                                                    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout owner email field is sanitized before repopulating the cart form.
                                                                    $tickera_posted_value = ( isset( $_POST[ $tickera_posted_name ] ) ) ? ( isset( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ? sanitize_text_field( wp_unslash( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ): '' ) : '';
                                                                ?>
                                                                <label>
                                                                    <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                                                </label>
                                                                <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_confirm_email' == $tickera_field[ 'field_name' ] ) { ?>tc_owner_confirm_email<?php } ?>" value="<?php echo esc_attr( stripslashes( $tickera_posted_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]">
                                                                <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                                                <?php if ( $tickera_field[ 'required' ] ) { ?>
                                                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                                                <?php } ?>
                                                            </div>
                                                        <?php }

                                                    } elseif ( 'date' == $tickera_field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                                            <?php
                                                                $tickera_posted_name = 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ];
                                                                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout owner date field is sanitized before repopulating the cart form.
                                                                $tickera_posted_value = ( isset( $_POST[ $tickera_posted_name ] ) ) ? ( isset( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ? sanitize_text_field( wp_unslash( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ) : '' ) : '';
                                                            ?>
                                                            <label>
                                                                <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : '' ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_email' == $tickera_field[ 'field_name' ] ) { ?>tc_owner_email<?php } ?>" value="<?php echo esc_attr( stripslashes ( $tickera_posted_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]">
                                                            <span class="description"><?php echo esc_html($tickera_field[ 'field_description' ]); ?></span>
                                                            <?php if ( $tickera_field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php

                                                    } elseif ( 'textarea' == $tickera_field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                                            <label>
                                                                <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <?php
                                                                $tickera_posted_name = esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] );
                                                                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout owner textarea value is sanitized before repopulating the cart form.
                                                                $tickera_posted_value = ( isset( $_POST[ $tickera_posted_name ] ) ) ? ( isset( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ? sanitize_text_field( wp_unslash( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ) : '' ) : '';
                                                            ?>
                                                            <textarea class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]"><?php echo esc_textarea( stripslashes( $tickera_posted_value ) ); ?></textarea>
                                                            <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                                            <?php if ( $tickera_field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php

                                                    } elseif ( 'radio' == $tickera_field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                                            <label>
                                                                <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <?php if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                                                $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                                                foreach ( $tickera_field_values as $tickera_field_value ) { ?>
                                                                    <label>
                                                                        <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]" <?php if ( tickera_cart_field_get_radio_value_checked( $tickera_field, $tickera_field_value, $tickera_field_values, ( esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ) ), $tickera_ticket_type, $tickera_owner_index ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?>
                                                                    </label>
                                                                <?php } ?>
                                                            <input type="text" class="validation tickera-input-field tc-hidden-important" value=""/>
                                                            <?php } ?>
                                                            <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                                            <?php if ( $tickera_field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php

                                                    } elseif ( 'checkbox' == $tickera_field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_html( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                                            <label>
                                                                <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <?php if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                                                $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                                                foreach ( $tickera_field_values as $tickera_field_value ) { ?>
                                                                    <label>
                                                                        <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( tickera_cart_field_get_checkbox_value_checked( $tickera_field, $tickera_field_value, $tickera_field_values, esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ), $tickera_ticket_type, $tickera_owner_index ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?>
                                                                    </label>
                                                                <?php } ?>
                                                                <input type="text" class="checkbox_values tickera-input-field tc-hidden-important" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]" value="<?php echo esc_attr( tickera_cart_field_posted_values( esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ), $tickera_ticket_type, $tickera_owner_index ) ); ?>"/>
                                                            <?php } ?>
                                                            <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                                            <?php if ( $tickera_field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                                            <?php } ?>
                                                        </div><?php

                                                    } elseif ( 'select' == $tickera_field[ 'field_type' ] ) { ?>
                                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                                            <label>
                                                                <span><?php echo esc_html( $tickera_field[ 'required' ] ? '*' : '' ); ?><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?></span>
                                                            </label>
                                                            <select class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]">
                                                                <?php if ( ! $tickera_field[ 'required' ] ) : ?>
                                                                    <option value=""><?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : '' ); ?></option><?php
                                                                endif;
                                                                if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                                                    $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                                                    foreach ( $tickera_field_values as $tickera_field_value ) : ?>
                                                                        <option value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( tickera_cart_field_get_option_value_selected( $tickera_field, $tickera_field_value, esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ), $tickera_ticket_type, $tickera_owner_index ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?></option>
                                                                    <?php endforeach;
                                                                } ?>
                                                            </select>
                                                            <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                                            <?php if ( $tickera_field[ 'required' ] ) { ?>
                                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
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
                            foreach ( $tickera_cart_contents as $tickera_ticket_type => $tickera_ordered_count ) {
                                $tickera_ticket = new \Tickera\TC_Ticket( $tickera_ticket_type );

                                if ( ! empty( $tickera_ticket->details->post_title ) && in_array( get_post_type( $tickera_ticket_type ), [ 'tc_tickets', 'product' ] ) ) {
                                    $tickera_owner_form = new \Tickera\TC_Cart_Form( $tickera_ticket_type );
                                    $tickera_owner_form_fields = $tickera_owner_form->get_owner_info_fields( $tickera_ticket_type );
                                    ?>
                                    <div class="tc-form-ticket-fields-wrap">
                                        <?php for ( $tickera_i = 1; $tickera_i <= $tickera_ordered_count; $tickera_i++ ) {
                                            $tickera_owner_index = $tickera_i - 1;
                                            ?>
                                            <div class="owner-info-wrap">
                                                <?php
                                                    tickera_do_action( 'tickera_cart_before_attendee_info_wrap', $tickera_ticket, $tickera_owner_index );
                                                    foreach ( $tickera_owner_form_fields as $tickera_field ) {

                                                        if (
                                                            ( ! isset( $tickera_field[ 'form_visibility' ] ) && 'ticket_type_id' == $tickera_field[ 'field_name' ] ) ||
                                                            ( isset( $tickera_field[ 'form_visibility' ] ) && ! $tickera_field[ 'form_visibility' ] )
                                                        ) {
                                                            $tickera_array_of_arguments = [];
                                                            $tickera_array_of_arguments[] = isset( $tickera_field[ 'field_name' ] ) ? $tickera_field[ 'field_name' ] : '';
                                                            $tickera_array_of_arguments[] = isset( $tickera_field[ 'post_field_type' ] ) ? $tickera_field[ 'post_field_type' ] : '';
                                                            $tickera_array_of_arguments[] = $tickera_ticket_type;
                                                            $tickera_array_of_arguments[] = $tickera_ordered_count;
                                                            $tickera_array_of_arguments[] = $tickera_owner_index;
                                                            $tickera_array_of_arguments[] = $tickera_field;
                                                            call_user_func_array( $tickera_field[ 'function' ], $tickera_array_of_arguments );
                                                        }
                                                } ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php }
                            }
                        } ?>
                    </div><?php
                    tickera_do_action( 'tickera_before_cart_submit' );
                    tickera_do_action( 'tickera_only_before_cart_submit' ); ?>
                    <div class="proceed-to-checkout-container">
                        <input type="submit" id="proceed_to_checkout" name="proceed_to_checkout" value="<?php esc_html_e( "Proceed to Checkout", "tickera-event-ticketing-system" ); ?>" class="tickera_checkout tickera-button"/>
                    </div>
                </div>
            </div>
            <div><?php wp_nonce_field( 'page_cart' ); ?></div>
        </form>
    <?php else : ?>
        <?php tickera_do_action( 'tickera_empty_cart' ); ?>
        <div class="cart_empty_message"><?php esc_html_e( "The cart is empty.", "tickera-event-ticketing-system" ); ?></div>
    <?php endif; ?>
<?php endif;
