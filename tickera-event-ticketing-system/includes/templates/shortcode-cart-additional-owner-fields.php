<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$tickera_settings = get_option( 'tickera_general_setting', false );
$tickera_cart_contents = tickera_apply_filters( 'tickera_cart_contents', [] );

$tickera_show_owner_fields = isset( $tickera_settings[ 'show_owner_fields' ] ) ? $tickera_settings[ 'show_owner_fields' ] : 'yes';
$tickera_show_owner_fields = ( 'yes' == $tickera_show_owner_fields ) ? true : false;
?>
<div class="tickera_owner_info info_section">
    <?php if ( $tickera_show_owner_fields ) {

        $tickera_ticket_type_order = 1;

        foreach ( $tickera_cart_contents as $tickera_ticket_type => $tickera_ordered_count ) {

            $tickera_get_post_type = get_post_type( $tickera_ticket_type );

            if ( 'product_variation' == $tickera_get_post_type ) {
                $tickera_get_variation_parent = wp_get_post_parent_id( $tickera_ticket_type );
                $tickera_get_custom_form = get_post_meta( $tickera_get_variation_parent, '_owner_form_template', true );

            } else {
                $tickera_get_custom_form = get_post_meta( $tickera_ticket_type, '_owner_form_template', true );
            }

            $tickera_owner_form = new \Tickera\TC_Cart_Form( tickera_apply_filters( 'tickera_ticket_type_id', $tickera_ticket_type ) );
            $tickera_owner_form_fields = $tickera_owner_form->get_owner_info_fields( tickera_apply_filters( 'tickera_ticket_type_id', $tickera_ticket_type ) );

            $tickera_form_visibilities = array_column( $tickera_owner_form_fields, 'form_visibility' );
            $tickera_show_field = ( ! in_array( true, $tickera_form_visibilities ) ) ? 'tc-hidden' : '';

            $tickera_ticket = new \Tickera\TC_Ticket( $tickera_ticket_type );
            ?>
            <div class="tc-form-ticket-fields-wrap <?php echo esc_attr( $tickera_show_field ); ?>">
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

                                $tickera_min = isset( $tickera_field[ 'field_min' ] ) ? $tickera_field[ 'field_min' ] : '';
                                $tickera_max = isset( $tickera_field[ 'field_max' ] ) ? $tickera_field[ 'field_max' ] : '';
                                $tickera_step = isset( $tickera_field[ 'field_step' ] ) ? $tickera_field[ 'field_step' ] : '';

                                if ( 'owner_email' == $tickera_field[ 'field_name' ] ) {
                                    $tickera_input_value = tickera_apply_filters( 'tickera_input_email_field', '', wp_get_current_user() );

                                } elseif ( 'first_name' == $tickera_field[ 'field_name' ] ) {
                                    $tickera_input_value = tickera_apply_filters( 'tickera_input_first_name_field', '', wp_get_current_user() );

                                } elseif ( 'last_name' == $tickera_field[ 'field_name' ] ) {
                                    $tickera_input_value = tickera_apply_filters( 'tickera_input_last_name_field', '', wp_get_current_user() );

                                } else {
                                    $tickera_input_value = '';
                                }

                                if ( ( isset( $tickera_settings[ 'show_owner_email_field' ] ) && 'yes' == $tickera_settings[ 'show_owner_email_field' ] && 'owner_email' == $tickera_field[ 'field_name' ] ) || $tickera_field[ 'field_name' ] !== 'owner_email' ) { ?>
                                    <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                        <label>
                                            <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                        </label>
                                        <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_email' == $tickera_field[ 'field_name' ] ) { ?>tc_owner_email<?php } ?>" value="<?php echo esc_attr( $tickera_input_value ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]"<?php echo wp_kses_post( ( $tickera_min ? ' min="' . esc_attr( $tickera_min ) . '"' : '' ) . ( $tickera_max ? ' max="' . esc_attr( $tickera_max ) . '"' : '' ) . ( $tickera_step ? ' step="' . esc_attr( $tickera_step ) . '"' : '' ) ) ?>>
                                        <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                        <?php if ( $tickera_field[ 'required' ] ) { ?>
                                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                        <?php } ?>
                                    </div>
                                <?php }

                            } elseif ( 'email' == $tickera_field[ 'field_type' ] ) { ?>
                                <?php if ( ( isset( $tickera_settings[ 'email_verification_buyer_owner' ] ) && 'yes' == $tickera_settings[ 'email_verification_buyer_owner' ] && ( 'owner_confirm_email' == $tickera_field[ 'field_name' ] ) || $tickera_field[ 'field_name' ] !== 'owner_confirm_email' ) && isset( $tickera_settings[ 'show_owner_email_field' ] ) && 'yes' == $tickera_settings[ 'show_owner_email_field' ] ) { ?>
                                    <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                        <?php
                                        $tickera_posted_name = 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ];
                                        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout owner email field is sanitized before repopulating the cart form.
                                        if ( isset( $_POST[ $tickera_posted_name ] ) ) {
                                            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout owner email field is sanitized before repopulating the cart form.
                                            $tickera_posted_value = isset( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ? sanitize_text_field( wp_unslash( $_POST[ $tickera_posted_name ][ $tickera_ticket_type ][ $tickera_owner_index ] ) ) : '';

                                        } else {
                                            $tickera_posted_value = '';
                                        }
                                        ?>
                                        <label>
                                            <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                        </label>
                                        <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : '' ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( $tickera_field[ 'field_name' ] == 'owner_confirm_email' ) { ?>tc_owner_confirm_email<?php } ?>" value="<?php echo esc_attr( stripslashes( $tickera_posted_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]">
                                        <span class="description"><?php echo esc_html($tickera_field[ 'field_description' ]); ?></span>
                                        <?php if ( $tickera_field[ 'required' ] ) { ?>
                                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                        <?php } ?>
                                    </div>
                                <?php }

                            } elseif ( 'date' == $tickera_field[ 'field_type' ] ) { ?>
                            <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                <label>
                                    <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                </label>
                                <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : '' ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( 'owner_email' == $tickera_field[ 'field_name' ] ) { ?>tc_owner_email<?php } ?>" value="<?php echo esc_attr( $tickera_input_value ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]">
                                <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                <?php if ( $tickera_field[ 'required' ] ) { ?>
                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                <?php } ?>
                                </div><?php

                            } elseif ( 'textarea' == $tickera_field[ 'field_type' ] ) { ?>
                            <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                <label>
                                    <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                </label>
                                <textarea class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]"></textarea>
                                <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                <?php if ( $tickera_field[ 'required' ] ) { ?>
                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                <?php } ?>
                                </div><?php

                            } elseif ( 'radio' == $tickera_field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                    </label>
                                    <?php if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                        $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                        foreach ( $tickera_field_values as $tickera_field_value ) { ?>
                                            <label>
                                                <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]" <?php if ( isset( $tickera_field[ 'field_default_value' ] ) && $tickera_field[ 'field_default_value' ] == trim( $tickera_field_value ) || ( empty( $tickera_field[ 'field_default_value' ] ) && isset( $tickera_field_values[ 0 ] ) && $tickera_field_values[ 0 ] == trim( $tickera_field_value ) ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?>
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
                            <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                <label>
                                    <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                </label>
                                <?php if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                    $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                    foreach ( $tickera_field_values as $tickera_field_value ) { ?>
                                        <label>
                                            <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( isset( $tickera_field[ 'field_default_value' ] ) && $tickera_field[ 'field_default_value' ] == trim( $tickera_field_value ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?>
                                        </label>
                                    <?php } ?>
                                    <input type="text" class="checkbox_values tickera-input-field tc-hidden-important" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]" value=""/>
                                <?php } ?>
                                <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                <?php if ( $tickera_field[ 'required' ] ) { ?>
                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                <?php } ?>
                                </div><?php

                            } elseif ( 'select' == $tickera_field[ 'field_type' ] ) { ?>
                                <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                    <label>
                                        <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                        <select class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]">
                                            <?php if ( ! $tickera_field[ 'required' ] ) : ?>
                                                <option value=""><?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : '' ); ?></option><?php
                                            endif;
                                            if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                                $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                                foreach ( $tickera_field_values as $tickera_field_value ) : ?>
                                                    <option value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( isset( $tickera_field[ 'field_default_value' ] ) && $tickera_field[ 'field_default_value' ] == trim( $tickera_field_value ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?></option>
                                                <?php endforeach;
                                            } ?>
                                        </select>
                                    </label>
                                    <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                    <?php if ( $tickera_field[ 'required' ] ) { ?>
                                        <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                    <?php } ?>
                                </div>
                            <?php }
                        } ?>
                        <div class="tc-clearfix"></div>
                    </div>
                <?php }
                $tickera_i++; ?>
            </div>
        <?php }

    } else {

        /**
         * If Show attendee's fields is disabled. Configured from the Tickera > Settings > General
         */
        foreach ( $tickera_cart_contents as $tickera_ticket_type => $tickera_ordered_count ) {

            $tickera_get_post_type = get_post_type( $tickera_ticket_type );

            if ( 'product_variation' == $tickera_get_post_type ) {
                $tickera_get_variation_parent = wp_get_post_parent_id( $tickera_ticket_type );
                $tickera_get_custom_form = get_post_meta( $tickera_get_variation_parent, '_owner_form_template', true );

            } else {
                $tickera_get_custom_form = get_post_meta( $tickera_ticket_type, '_owner_form_template', true );
            }

            $tickera_owner_form = new \Tickera\TC_Cart_Form( tickera_apply_filters( 'tickera_ticket_type_id', $tickera_ticket_type ) );
            $tickera_owner_form_fields = $tickera_owner_form->get_owner_info_fields( tickera_apply_filters( 'tickera_ticket_type_id', $tickera_ticket_type ) );
            $tickera_ticket = new \Tickera\TC_Ticket( $tickera_ticket_type );
            ?>
            <div class="tc-form-ticket-fields-wrap">
                <?php for ( $tickera_i = 1; $tickera_i <= $tickera_ordered_count; $tickera_i++ ) {
                    $tickera_owner_index = $tickera_i - 1; ?>
                    <div class="owner-info-wrap">
                        <?php
                        tickera_do_action( 'tickera_cart_before_attendee_info_wrap', $tickera_ticket, $tickera_owner_index );
                        foreach ( $tickera_owner_form_fields as $tickera_field ) {

                            if (
                                ( ! isset( $tickera_field[ 'form_visibility' ] ) && 'ticket_type_id' == $tickera_field[ 'field_name' ] ) ||
                                ( isset( $tickera_field[ 'form_visibility' ] ) && ! $tickera_field[ 'form_visibility' ] )
                            ) {
                                $tickera_array_of_arguments = array();
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
    tickera_do_action( 'tickera_before_cart_submit' );
    ?>
</div>
