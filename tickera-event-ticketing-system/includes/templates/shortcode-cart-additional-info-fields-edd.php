<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<fieldset id="edd_checkout_user_info">
    <?php
    $tc_general_settings = get_option( 'tickera_general_setting', false );
    $cart_contents = apply_filters( 'tc_cart_contents', array() );
    $buyer_form = new \Tickera\TC_Cart_Form();
    $buyer_form_fields = $buyer_form->get_buyer_info_fields();
    $buyer_fields_count = count( $buyer_form_fields ); ?>
    <div class="tickera_additional_info">
        <div class="tickera_buyer_info<?php echo esc_attr( (int)$buyer_fields_count == 0 ? '_edd' : '' ); ?> info_section">
            <?php foreach ( $buyer_form_fields as $field ) {

                if ( 'function' == $field[ 'field_type' ] ) {
                    call_user_func( $field[ 'function' ], $field );

                } elseif ( 'label' == $field[ 'field_type' ] ) { ?>
                    <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] );?>"><?php echo wp_kses_post( '<' . $field[ 'field_tag' ] . '>' . $field[ 'field_title' ] . '</' . $field[ 'field_tag' ] . '>' ); ?></div><?php

                } elseif ( in_array( $field[ 'field_type' ], [ 'text', 'date', 'number' ] ) ) {
                    $min = isset( $field[ 'field_min' ] ) ? $field[ 'field_min' ] : '';
                    $max = isset( $field[ 'field_max' ] ) ? $field[ 'field_max' ] : '';
                    $step = isset( $field[ 'field_step' ] ) ? $field[ 'field_step' ] : ''; ?>
                    <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = isset( $field[ 'validation_type' ] ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                        <label>
                            <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ? sanitize_text_field( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) : $buyer_form->get_default_value( $field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"<?php echo wp_kses_post( ( $min ? ' min="' . esc_attr( $min ) . '"' : '' ) . ( $max ? ' max="' . esc_attr( $max ) . '"' : '' ) . ( $step ? ' step="' . esc_attr( $step ) . '"' : '' ) ) ?>>
                        <span class="description"><?php echo esc_html($field[ 'field_description' ]); ?></span>
                        <?php if ( $field[ 'required' ] ) { ?>
                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                        <?php } ?>
                    </div><?php

                } elseif ( 'textarea' == $field[ 'field_type' ] ) { ?>
                    <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                        <label>
                            <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <textarea class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"><?php echo esc_textarea( isset( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ? sanitize_text_field( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) : $buyer_form->get_default_value( $field ) ); ?></textarea>
                        <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                        <?php if ( $field[ 'required' ] ) { ?>
                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                        <?php } ?>
                    </div><?php

                } elseif ( 'radio' == $field[ 'field_type' ] ) { ?>
                    <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                        <label>
                            <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <?php if ( isset( $field[ 'field_values' ] ) ) {
                            $field_values = explode( ',', $field[ 'field_values' ] );
                            foreach ( $field_values as $field_value ) { ?>
                                <label>
                                    <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $field_value ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>" <?php if ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) || ( empty( $field[ 'field_default_value' ] ) && isset( $field_values[ 0 ] ) && $field_values[ 0 ] == trim( $field_value ) ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $field_value ) ); ?>
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
                            <span><?php echo esc_html($field[ 'field_title' ]); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <?php if ( isset( $field[ 'field_values' ] ) ) {
                            $field_values = explode( ',', $field[ 'field_values' ] );
                            foreach ( $field_values as $field_value ) { ?>
                                <label>
                                    <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $field_value ) ); ?>" <?php if ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $field_value ) ); ?>
                                </label>
                            <?php } ?>
                            <input type="text" class="checkbox_values tickera-input-field tc-hidden-important" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>" value=""/>
                        <?php } ?>
                        <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                        <?php if ( $field[ 'required' ] ) { ?>
                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                        <?php } ?>
                    </div><?php

                } elseif ( 'select' == $field[ 'field_type' ] ) { ?>
                    <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                        <label>
                            <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                        </label>
                        <select class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>">
                            <option value="" selected><?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) ? esc_attr( $field[ 'field_placeholder' ] ) : '' ); ?></option>
                            <?php if ( isset( $field[ 'field_values' ] ) ) {
                                $field_values = explode( ',', $field[ 'field_values' ] );
                                foreach ( $field_values as $field_value ) { ?>
                                    <option value="<?php echo esc_attr( trim( $field_value ) ); ?>" <?php if ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $field_value ) ); ?></option>
                                <?php }
                            } ?>
                        </select>
                        <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                        <?php if ( $field[ 'required' ] ) { ?>
                            <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                        <?php } ?>
                    </div><?php
                }
            } ?>
        </div>
        <?php $show_owner_fields = ( ! isset( $tc_general_settings[ 'show_owner_fields' ] ) || ( isset( $tc_general_settings[ 'show_owner_fields' ] ) && $tc_general_settings[ 'show_owner_fields' ] == 'yes' ) ) ? true : false; ?>
        <div class="tickera_owner_info info_section">
            <?php if ( $show_owner_fields ) {
                $ticket_type_order = 1;
                foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                    $owner_form = new \Tickera\TC_Cart_Form( apply_filters( 'tc_ticket_type_id', $ticket_type ) );
                    $owner_form_fields = $owner_form->get_owner_info_fields( apply_filters( 'tc_ticket_type_id', $ticket_type ) );

                    $form_visibilities = array_column( $owner_form_fields, 'form_visibility' );
                    $show_field = ( ! in_array( true, $form_visibilities ) ) ? 'tc-hidden' : '';
                    $ticket = new \Tickera\TC_Ticket( $ticket_type );
                    ?>
                    <div class="tc-form-ticket-fields-wrap <?php echo esc_html( sanitize_text_field( $show_field ) ); ?>">
                        <legend><?php echo esc_html( apply_filters( 'tc_checkout_owner_info_ticket_title', $ticket->details->post_title, $ticket_type, $cart_contents, false ) ); ?></legend>
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
                                <?php foreach ( $owner_form_fields as $field ) {

                                    if ( 'function' == $field[ 'field_type' ] ) {
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
                                        $min = isset( $field[ 'field_min' ] ) ? $field[ 'field_min' ] : '';
                                        $max = isset( $field[ 'field_max' ] ) ? $field[ 'field_max' ] : '';
                                        $step = isset( $field[ 'field_step' ] ) ? $field[ 'field_step' ] : ''; ?>
                                        <?php if ( ( isset( $tc_general_settings[ 'show_owner_email_field' ] ) && $tc_general_settings[ 'show_owner_email_field' ] == 'yes' && $field[ 'field_name' ] == 'owner_email' ) || $field[ 'field_name' ] !== 'owner_email' ) { ?>
                                            <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                                <label>
                                                    <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo esc_attr( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                                </label>
                                                <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field tc-owner-field <?php if ( $field[ 'field_name' ] == 'owner_email' ) { ?>tc_owner_email<?php } ?>" value="" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]"<?php echo wp_kses_post( ( $min ? ' min="' . esc_attr( $min ) . '"' : '' ) . ( $max ? ' max="' . esc_attr( $max ) . '"' : '' ) . ( $step ? ' step="' . esc_attr( $step ) . '"' : '' ) ) ?>>
                                                <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                                <?php if ( $field[ 'required' ] ) : ?>
                                                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                                <?php endif; ?>
                                            </div>
                                        <?php }

                                    } elseif ( 'date' == $field[ 'field_type' ] ) { ?>
                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                            <label>
                                                <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                            </label>
                                            <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field tc-owner-field <?php if ( $field[ 'field_name' ] == 'owner_email' ) { ?>tc_owner_email<?php } ?>" value="" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]">
                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                            <?php if ( $field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
                                        </div><?php

                                    } elseif ( 'textarea' == $field[ 'field_type' ] ) { ?>
                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                            <label>
                                                <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                            </label>
                                            <textarea class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]"></textarea>
                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                            <?php if ( $field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
                                        </div><?php

                                    } elseif ( 'radio' == $field[ 'field_type' ] ) { ?>
                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                            <label>
                                                <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                            </label>
                                            <?php if ( isset( $field[ 'field_values' ] ) ) {
                                                $field_values = explode( ',', $field[ 'field_values' ] );
                                                foreach ( $field_values as $field_value ) { ?>
                                                    <label>
                                                        <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $field_value ) ); ?>" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]" <?php if ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) || ( empty( $field[ 'field_default_value' ] ) && isset( $field_values[ 0 ] ) && $field_values[ 0 ] == trim( $field_value ) ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $field_value ) ); ?>
                                                    </label>
                                                <?php } ?>
                                            <input type="text" class="validation tickera-input-field tc-hidden-important" value=""/>
                                            <?php } ?>
                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                            <?php if ( $field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
                                        </div><?php

                                    } elseif ( 'checkbox' == $field[ 'field_type' ] ) { ?>
                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                            <label>
                                                <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                            </label>
                                            <?php if ( isset( $field[ 'field_values' ] ) ) {
                                                $field_values = explode( ',', $field[ 'field_values' ] );
                                                foreach ( $field_values as $field_value ) { ?>
                                                    <label>
                                                        <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( trim( $field_value ) ); ?>" <?php if ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) ) echo esc_attr( 'checked' ); ?>><?php echo esc_html( trim( $field_value ) ); ?>
                                                    </label>
                                                <?php } ?>
                                                <input type="text" class="checkbox_values tickera-input-field tc-hidden-important" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]" value=""/>
                                            <?php } ?>
                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                            <?php if ( $field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
                                        </div><?php

                                    } elseif ( 'select' == $field[ 'field_type' ] ) { ?>
                                        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
                                            <label>
                                                <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                                            </label>
                                            <select class="owner-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" name="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>[<?php echo esc_attr( (int) $ticket_type ); ?>][<?php echo esc_attr( (int) $owner_index ); ?>]">
                                                <option value="" selected><?php echo wp_kses_post( isset( $field[ 'field_placeholder' ] ) ? esc_attr( $field[ 'field_placeholder' ] ) : '' ); ?></option>
                                                <?php if ( isset( $field[ 'field_values' ] ) ) {
                                                    $field_values = explode( ',', $field[ 'field_values' ] );
                                                    foreach ( $field_values as $field_value ) { ?>
                                                        <option value="<?php echo esc_attr( trim( $field_value ) ); ?>" <?php if ( isset( $field[ 'field_default_value' ] ) && $field[ 'field_default_value' ] == trim( $field_value ) ) echo esc_attr( 'selected' ); ?>><?php echo esc_html( trim( $field_value ) ); ?></option>
                                                    <?php }
                                                } ?>
                                            </select>
                                            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                                            <?php if ( $field[ 'required' ] ) : ?>
                                                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'owner_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                                            <?php endif; ?>
                                        </div><?php
                                    }
                                } ?>
                                <div class="tc-clearfix"></div>
                            </div>
                        <?php } ?>
                    </div><?php
                    $i++;
                }
            } else {

                /**
                 * If Show attendee's fields is disabled. Configured from the Tickera > Settings > General
                 */
                $ticket_type_order = 1;
                foreach ( $cart_contents as $ticket_type => $ordered_count ) {
                    $owner_form = new \Tickera\TC_Cart_Form( apply_filters( 'tc_ticket_type_id', $ticket_type ) );
                    $owner_form_fields = $owner_form->get_owner_info_fields( apply_filters( 'tc_ticket_type_id', $ticket_type ) );
                    $ticket = new \Tickera\TC_Ticket( $ticket_type );

                    for ( $i = 1; $i <= $ordered_count; $i++ ) {
                        $owner_index = $i - 1; ?>
                        <div class="owner-info-wrap">
                            <?php foreach ( $owner_form_fields as $field ) {

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
                        </div><?php
                    }
                }
            } ?>
        </div>
        <?php
        do_action( 'before_cart_submit' );
        do_action( 'tc_before_cart_submit' );
        ?>
    </div>
</fieldset>
