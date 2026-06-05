<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="tickera_buyer_info info_section">
    <?php
    $tickera_buyer_form = new \Tickera\TC_Cart_Form();
    $tickera_buyer_form_fields = $tickera_buyer_form->get_buyer_info_fields();

    foreach ( $tickera_buyer_form_fields as $tickera_field ) {

        if ( 'function' == $tickera_field[ 'field_type' ] ) {
            call_user_func( $tickera_field[ 'function' ], $tickera_field );

        } elseif ( 'label' == $tickera_field[ 'field_type' ] ) { ?>
            <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ) ?>"><?php echo wp_kses_post( '<' . $tickera_field[ 'field_tag' ] . '>' . $tickera_field[ 'field_title' ] . '</' . $tickera_field[ 'field_tag' ] . '>' ); ?></div><?php

        } elseif ( in_array( $tickera_field[ 'field_type' ], [ 'text', 'date', 'number' ] ) ) {
            $tickera_min = isset( $tickera_field[ 'field_min' ] ) ? $tickera_field[ 'field_min' ] : '';
            $tickera_max = isset( $tickera_field[ 'field_max' ] ) ? $tickera_field[ 'field_max' ] : '';
            $tickera_step = isset( $tickera_field[ 'field_step' ] ) ? $tickera_field[ 'field_step' ] : ''; ?>
            <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
                <label>
                    <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                </label>
                <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout buyer field value is sanitized before repopulating the cart form. ?>
                <input type="<?php echo esc_attr( $tickera_field[ 'field_type' ] ); ?>" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ) : $tickera_buyer_form->get_default_value( $tickera_field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"<?php echo wp_kses_post( ( $tickera_min ? ' min="' . esc_attr( $tickera_min ) . '"' : '' ) . ( $tickera_max ? ' max="' . esc_attr( $tickera_max ) . '"' : '' ) . ( $tickera_step ? ' step="' . esc_attr( $tickera_step ) . '"' : '' ) ) ?>>
                <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
                <?php if ( $tickera_field[ 'required' ] ) { ?>
                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
                <?php } ?>
            </div><?php

        } elseif ( 'textarea' == $tickera_field[ 'field_type' ] ) { ?>
        <div class="fields-wrap <?php if ( isset( $tickera_field[ 'field_class' ] ) ) echo esc_attr( $tickera_field[ 'field_class' ] ); $tickera_validation_class = ( isset( $tickera_field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $tickera_field[ 'validation_type' ] : ''; ?>">
            <label>
                <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                <?php // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checkout buyer textarea value is sanitized before repopulating the cart form. ?>
                <textarea class="buyer-field-<?php echo esc_attr( $tickera_field[ 'field_type' ] . ' ' . $tickera_validation_class ); ?> tickera-input-field" placeholder="<?php echo ( isset( $tickera_field[ 'field_placeholder' ] ) && $tickera_field[ 'field_placeholder' ] != '' ) ? esc_attr( $tickera_field[ 'field_placeholder' ] ) : ''; ?>" name="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"><?php echo esc_textarea( isset( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ] ) ) : $tickera_buyer_form->get_default_value( $tickera_field ) ); ?></textarea>
            </label>
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
                <span><?php echo esc_html( $tickera_field[ 'field_title' ] ); ?><?php echo wp_kses_post( $tickera_field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
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
            <span class="description"><?php echo esc_html( $tickera_field[ 'field_description' ] ); ?></span>
            <?php if ( $tickera_field[ 'required' ] ) { ?>
                <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $tickera_field[ 'field_name' ] . '_' . $tickera_field[ 'post_field_type' ] ); ?>"/>
            <?php } ?>
            </div><?php
        }
    } ?>
</div>
