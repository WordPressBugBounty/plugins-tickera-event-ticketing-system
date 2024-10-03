<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="tickera_buyer_info info_section">
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
                    <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                </label>
                <input type="<?php echo esc_attr( $field[ 'field_type' ] ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" value="<?php echo esc_attr( isset( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ? sanitize_text_field( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) : $buyer_form->get_default_value( $field ) ); ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"<?php echo wp_kses_post( ( $min ? ' min="' . esc_attr( $min ) . '"' : '' ) . ( $max ? ' max="' . esc_attr( $max ) . '"' : '' ) . ( $step ? ' step="' . esc_attr( $step ) . '"' : '' ) ) ?>>
                <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                <?php if ( $field[ 'required' ] ) { ?>
                    <input type="hidden" name="tc_cart_required[]" value="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"/>
                <?php } ?>
            </div><?php

        } elseif ( 'textarea' == $field[ 'field_type' ] ) { ?>
        <div class="fields-wrap <?php if ( isset( $field[ 'field_class' ] ) ) echo esc_attr( $field[ 'field_class' ] ); $validation_class = ( isset( $field[ 'validation_type' ] ) ) ? 'tc_validate_field_type_' . $field[ 'validation_type' ] : ''; ?>">
            <label>
                <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
                <textarea class="buyer-field-<?php echo esc_attr( $field[ 'field_type' ] . ' ' . $validation_class ); ?> tickera-input-field" placeholder="<?php echo esc_attr( isset( $field[ 'field_placeholder' ] ) && $field[ 'field_placeholder' ] != '' ) ? $field[ 'field_placeholder' ] : ''; ?>" name="<?php echo esc_attr( 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"><?php echo esc_textarea( isset( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) ? sanitize_text_field( $_POST[ 'buyer_data_' . $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ] ) : $buyer_form->get_default_value( $field ) ); ?></textarea>
            </label>
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
                <span><?php echo esc_html( $field[ 'field_title' ] ); ?><?php echo wp_kses_post( $field[ 'required' ] ? '<abbr class="required" title="required">*</abbr>' : '' ); ?></span>
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
                <option value=""><?php echo esc_html( isset( $field[ 'field_placeholder' ] ) ? esc_attr( $field[ 'field_placeholder' ] ) : '' ); ?></option>
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