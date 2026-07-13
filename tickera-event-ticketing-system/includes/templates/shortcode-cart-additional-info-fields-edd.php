<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<fieldset id="edd_checkout_user_info">
    <?php
    $tickera_general_settings = get_option( 'tickera_general_setting', false );
    $tickera_cart_contents = tickera_apply_filters( 'tickera_cart_contents', array() );
    $tickera_buyer_form = new \Tickera\TC_Cart_Form();
    $tickera_buyer_form_fields = $tickera_buyer_form->get_buyer_info_fields();
    $tickera_buyer_fields_count = count( $tickera_buyer_form_fields ); ?>
    <div class="tickera_additional_info">
        <div class="tickera_buyer_info<?php echo esc_attr( (int)$tickera_buyer_fields_count == 0 ? '_edd' : '' ); ?> info_section">
            <?php foreach ( $tickera_buyer_form_fields as $tickera_field ) {

                if ( 'function' == $tickera_field[ 'field_type' ] ) {
                    call_user_func( $tickera_field[ 'function' ], $tickera_field );

                } elseif ( 'label' == $tickera_field[ 'field_type' ] ) { ?>
                    <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] );?>"><?php echo wp_kses_post( '<' . $tickera_field[ 'field_tag' ] . '>' . $tickera_field[ 'field_title' ] . '</' . $tickera_field[ 'field_tag' ] . '>' ); ?></div><?php

                } elseif ( in_array( $tickera_field[ 'field_type' ], [ 'text', 'date', 'number' ] ) ) {
                    $tickera_min = isset( $tickera_field[ 'field_min' ] ) ? $tickera_field[ 'field_min' ] : '';
                    $tickera_max = isset( $tickera_field[ 'field_max' ] ) ? $tickera_field[ 'field_max' ] : '';
                    $tickera_step = isset( $tickera_field[ 'field_step' ] ) ? $tickera_field[ 'field_step' ] : ''; ?>
                    <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = isset( $tickera_field[ 'validation_type' ] ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                        <label>
                            <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout EDD buyer field value is sanitized before repopulating the cart form. ?>
                        <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ) : $tickera_buyer_form->get_default_value( $tickera_field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"<?php echo wp_kses_post( ( $tickera_min ? ' min="' . esc_attr( $tickera_min ) . '"' : '' ) . ( $tickera_max ? ' max="' . esc_attr( $tickera_max ) . '"' : '' ) . ( $tickera_step ? ' step="' . esc_attr( $tickera_step ) . '"' : '' ) ) ?>>
                        <span class="description"><?php echo esc_html($tickera_field[ 'field_description' ]); ?></span>
                        <?php if ( $tickera_field[ 'required' ] ) { ?>
                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                        <?php } ?>
                    </div><?php

                } elseif ( 'textarea' == $tickera_field[ 'field_type' ] ) { ?>
                    <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                        <label>
                            <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout EDD buyer textarea value is sanitized before repopulating the cart form. ?>
                        <textarea class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"><?php echo esc_textarea( isset( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ) : $tickera_buyer_form->get_default_value( $tickera_field ) ); ?></textarea>
                        <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                        <?php if ( $tickera_field[ 'required' ] ) { ?>
                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
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
                                    <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>" <?php if ( isset( $tickera_field[ 'field_default_value' ] ) && $tickera_field[ 'field_default_value' ] == trim( $tickera_field_value ) || ( empty( $tickera_field[ 'field_default_value' ] ) && isset( $tickera_field_values[ 0 ] ) && $tickera_field_values[ 0 ] == trim( $tickera_field_value ) ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?>
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
                            <span><?php echo esc_html($tickera_field[ 'field_title' ]); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <?php if ( isset( $tickera_field[ 'field_values' ] ) ) {
                            $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                            foreach ( $tickera_field_values as $tickera_field_value ) { ?>
                                <label>
                                    <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( isset( $tickera_field[ 'field_default_value' ] ) && $tickera_field[ 'field_default_value' ] == trim( $tickera_field_value ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?>
                                </label>
                            <?php } ?>
                            <input type="text" class="checkbox_values tickera-input-field tc-hidden-important" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>" value=""/>
                        <?php } ?>
                        <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                        <?php if ( $tickera_field[ 'required' ] ) { ?>
                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                        <?php } ?>
                    </div><?php

                } elseif ( 'select' == $tickera_field[ 'field_type' ] ) { ?>
                    <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                        <label>
                            <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <select class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>">
                            <?php if ( ! $tickera_field[ 'required'] ) : ?>
                                <option value="" selected><?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : '' ); ?></option><?php
                            endif;
                            if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                foreach ( $tickera_field_values as $tickera_field_value ) : ?>
                                    <option value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( isset( $tickera_field[ 'field_default_value' ] ) && $tickera_field[ 'field_default_value' ] == trim( $tickera_field_value ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?></option>
                                <?php endforeach;
                            } ?>
                        </select>
                        <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                        <?php if ( $tickera_field[ 'required' ] ) { ?>
                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                        <?php } ?>
                    </div><?php
                }
            } ?>
        </div>
        <?php $tickera_show_owner_fields = ( ! isset( $tickera_general_settings[ 'show_owner_fields' ] ) || ( isset( $tickera_general_settings[ 'show_owner_fields' ] ) && $tickera_general_settings[ 'show_owner_fields' ] == 'yes' ) ) ? true : false; ?>
        <div class="tickera_owner_info info_section">
            <?php if ( $tickera_show_owner_fields ) {
                $tickera_ticket_type_order = 1;
                foreach ( $tickera_cart_contents as $tickera_ticket_type => $tickera_ordered_count ) {
                    $tickera_owner_form = new \Tickera\TC_Cart_Form( tickera_apply_filters( 'tickera_ticket_type_id', $tickera_ticket_type ) );
                    $tickera_owner_form_fields = $tickera_owner_form->get_owner_info_fields( tickera_apply_filters( 'tickera_ticket_type_id', $tickera_ticket_type ) );

                    $tickera_form_visibilities = array_column( $tickera_owner_form_fields, 'form_visibility' );
                    $tickera_show_field = ( ! in_array( true, $tickera_form_visibilities ) ) ? 'tc-hidden' : '';
                    $tickera_ticket = new \Tickera\TC_Ticket( $tickera_ticket_type );
                    ?>
                    <div class="tc-form-ticket-fields-wrap <?php echo esc_html( sanitize_text_field( $tickera_show_field ) ); ?>">
                        <legend><?php echo esc_html( tickera_apply_filters( 'tickera_checkout_owner_info_ticket_title', $tickera_ticket->details->post_title, $tickera_ticket_type, $tickera_cart_contents, false ) ); ?></legend>
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
                                <?php foreach ( $tickera_owner_form_fields as $tickera_field ) {

                                    if ( 'function' == $tickera_field[ 'field_type' ] ) {
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
                                        $tickera_step = isset( $tickera_field[ 'field_step' ] ) ? $tickera_field[ 'field_step' ] : ''; ?>
                                        <?php if ( ( isset( $tickera_general_settings[ 'show_owner_email_field' ] ) && $tickera_general_settings[ 'show_owner_email_field' ] == 'yes' && $tickera_field[ 'field_name' ] == 'owner_email' ) || $tickera_field[ 'field_name' ] !== 'owner_email' ) { ?>
                                            <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                                <label>
                                                    <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo esc_attr( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                                </label>
                                                <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( $tickera_field[ 'field_name' ] == 'owner_email' ) { ?>tc_owner_email<?php } ?>" value="" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]"<?php echo wp_kses_post( ( $tickera_min ? ' min="' . esc_attr( $tickera_min ) . '"' : '' ) . ( $tickera_max ? ' max="' . esc_attr( $tickera_max ) . '"' : '' ) . ( $tickera_step ? ' step="' . esc_attr( $tickera_step ) . '"' : '' ) ) ?>>
                                                <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                                <?php if ( $tickera_field[ 'required' ] ) : ?>
                                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                                <?php endif; ?>
                                            </div>
                                        <?php }

                                    } elseif ( 'date' == $tickera_field[ 'field_type' ] ) { ?>
                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                            <label>
                                                <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                            </label>
                                            <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field tc-owner-field <?php if ( $tickera_field[ 'field_name' ] == 'owner_email' ) { ?>tc_owner_email<?php } ?>" value="" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]">
                                            <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                            <?php if ( $tickera_field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
                                        </div><?php

                                    } elseif ( 'textarea' == $tickera_field[ 'field_type' ] ) { ?>
                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                            <label>
                                                <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                            </label>
                                            <textarea class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]"></textarea>
                                            <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                            <?php if ( $tickera_field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
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
                                            <?php if ( $tickera_field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
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
                                            <?php if ( $tickera_field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
                                        </div><?php

                                    } elseif ( 'select' == $tickera_field[ 'field_type' ] ) { ?>
                                        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                                            <label>
                                                <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                            </label>
                                            <select class="owner-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $tickera_ticket_type ); ?>][<?php echo esc_attr( (int) $tickera_owner_index ); ?>]">
                                                <?php if ( ! $tickera_field[ 'required' ] ) : ?>
                                                    <option value="" selected><?php echo wp_kses_post( isset( $tickera_field[ 'field_placeholder' ] ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : '' ); ?></option><?php
                                                endif;
                                                if ( isset( $tickera_field[ 'field_values' ] ) ) {
                                                    $tickera_field_values = explode( ',', $tickera_field[ 'field_values' ] );
                                                    foreach ( $tickera_field_values as $tickera_field_value ) : ?>
                                                        <option value="<?php echo esc_attr( trim( $tickera_field_value ) ); ?>" <?php if ( isset( $tickera_field[ 'field_default_value' ] ) && $tickera_field[ 'field_default_value' ] == trim( $tickera_field_value ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $tickera_field_value ) ); ?></option>
                                                    <?php endforeach;
                                                } ?>
                                            </select>
                                            <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                                            <?php if ( $tickera_field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
                                        </div><?php
                                    }
                                } ?>
                                <div class="tc-clearfix"></div>
                            </div>
                        <?php } ?>
                    </div><?php
                    $tickera_i++;
                }
            } else {

                /**
                 * If Show attendee's fields is disabled. Configured from the Tickera > Settings > General
                 */
                $tickera_ticket_type_order = 1;
                foreach ( $tickera_cart_contents as $tickera_ticket_type => $tickera_ordered_count ) {
                    $tickera_owner_form = new \Tickera\TC_Cart_Form( tickera_apply_filters( 'tickera_ticket_type_id', $tickera_ticket_type ) );
                    $tickera_owner_form_fields = $tickera_owner_form->get_owner_info_fields( tickera_apply_filters( 'tickera_ticket_type_id', $tickera_ticket_type ) );
                    $tickera_ticket = new \Tickera\TC_Ticket( $tickera_ticket_type );

                    for ( $tickera_i = 1; $tickera_i <= $tickera_ordered_count; $tickera_i++ ) {
                        $tickera_owner_index = $tickera_i - 1; ?>
                        <div class="owner-info-wrap">
                            <?php foreach ( $tickera_owner_form_fields as $tickera_field ) {

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
                        </div><?php
                    }
                }
            } ?>
        </div>
        <?php
        tickera_do_action( 'tickera_before_cart_submit' );
        ?>
    </div>
</fieldset>
