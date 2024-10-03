<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Fields' ) ) {

    class TC_Fields {

        /**
         * Render Fields
         *
         * @param $field
         * @param bool $key
         * @param null $current_value
         * @return false|string
         */
        public static function render_field( $field, $key = false, $current_value = null ) {

            ob_start();

            switch ( $field[ 'field_type' ] ) {

                case 'function':
                    \Tickera\TC_Fields::field_function( $field, $key );
                    break;

                case 'text':
                    \Tickera\TC_Fields::field_text( $field, $key );
                    break;

                case 'option'://depricated, same as text
                    \Tickera\TC_Fields::field_text( $field, $key );
                    break;

                case 'textarea':
                    \Tickera\TC_Fields::field_textarea( $field, $key );
                    break;

                case 'wp_editor':
                    \Tickera\TC_Fields::field_wp_editor( $field, $key );
                    break;

                case 'radio':
                    \Tickera\TC_Fields::field_radio( $field, $key );
                    break;

                case 'select':
                    \Tickera\TC_Fields::field_select( $field, $key );
                    break;

                case 'color_field':
                    \Tickera\TC_Fields::color_field( $field, $key );
                    break;

                case 'file':
                    \Tickera\TC_Fields::field_file( $field, $key );
                    break;

                case 'date':
                    \Tickera\TC_Fields::field_date( $field, $key );
                    break;

                case 'field_extended_text':
                    \Tickera\TC_Fields::field_extended_text( $field, $key, $current_value );
                    break;

                case 'field_extended_radio':
                    \Tickera\TC_Fields::field_extended_radio( $field, $key, $current_value );
                    break;

                case 'field_select_multiple':
                    \Tickera\TC_Fields::field_select_multiple( $field, $key, $current_value );
                    break;

                default:
                    \Tickera\TC_Fields::field_text( $field, $key );
            }

            return ob_get_clean();
        }

        /**
         * Render fields by type ( function, text, textarea, etc )
         *
         * @param bool $obj_class
         * @param $field
         * @param $post_id
         * @param bool $show_title
         * @return false|string
         */
        public static function render_post_type_field( $obj_class, $field, $post_id, $show_title = true ) {

            ob_start();

            if ( ! $obj_class ) {
                echo wp_kses_post( '<strong>Class cannot be empty - called from render_post_type_field method</strong>' );
                return ob_get_clean();
            }

            if ( ! class_exists( $obj_class ) ) {
                echo wp_kses_post( '<strong>Class ' . esc_html( $obj_class ) . ' doesn\'t exists called from render_post_type_field method</strong>' );
                return ob_get_clean();
            }

            $obj = new $obj_class( $post_id );

            if ( $show_title ) { ?>
                <label><?php echo esc_html( $field[ 'field_title' ] ); ?>
            <?php }

            if ( 'function' == $field[ 'field_type' ] ) {

                if ( isset( $post_id ) ) {
                    call_user_func( $field[ 'function' ], $field[ 'field_name' ], $post_id, $field );

                } else {
                    call_user_func( $field[ 'function' ], $field[ 'field_name' ], '', $field );
                }

                if ( isset( $field[ 'field_description' ] ) ) { ?>
                    <span class="description"><?php echo esc_html( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ); ?></span>
                <?php }

            } elseif ( 'text' == $field[ 'field_type' ] ) { ?>
                <input type="text" class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?>" value="<?php if ( isset( $obj ) ) { if ( $field[ 'post_field_type' ] == 'post_meta' ) { echo esc_attr( isset( $obj->details->{$field[ 'field_name' ]} ) ? sanitize_text_field( $obj->details->{$field[ 'field_name' ]} ) : '' ); } else { echo esc_attr( sanitize_text_field( $obj->details->{$field[ 'post_field_type' ]} ) ); } } ?>" id="<?php echo esc_attr( isset( $field[ 'field_name' ] ) ? sanitize_text_field( $field[ 'field_name' ] ) : '' ); ?>" name="<?php echo esc_attr( sanitize_text_field( $field[ 'field_name' ] ) . '_' . sanitize_text_field( $field[ 'post_field_type' ] ) ); ?>" placeholder="<?php echo esc_attr( isset( $field[ 'placeholder' ] ) ? sanitize_text_field( $field[ 'placeholder' ] ) : '' ); ?>"  <?php echo esc_attr( isset( $field[ 'required' ] ) ? 'required' : '' ); ?><?php echo esc_attr( isset( $field[ 'number' ] ) ? 'number="true"' : '' ); ?>>
                <?php if ( isset( $field[ 'field_description' ] ) ) { ?>
                    <span class="description"><?php echo esc_html( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ); ?></span>
                <?php }

            } elseif ( 'textarea' == $field[ 'field_type' ] ) { ?>
                <textarea class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"><?php
                    if ( isset( $obj ) ) {
                        echo esc_textarea(
                        ( 'post_meta' == $field[ 'post_field_type' ] )
                            ? isset( $obj->details->{$field[ 'field_name' ]} ) ? $obj->details->{$field[ 'field_name' ]} : ''
                            : $obj->details->{$field[ 'post_field_type' ]}
                        );
                    } ?>
                </textarea>
                <?php if ( isset( $field[ 'field_description' ] ) ) { ?>
                    <span class="description"><?php echo esc_html( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ); ?></span>
                <?php }

            } elseif ( 'textarea_editor' == $field[ 'field_type' ] ) { ?>
                <?php if ( isset( $obj ) ) {
                    $editor_content = ( 'post_meta' == $field[ 'post_field_type' ] )
                        ? ( isset( $obj->details->{$field[ 'field_name' ]} ) ? $obj->details->{$field[ 'field_name' ]} : '' )
                        : ( $obj->details->{$field[ 'post_field_type' ]} );

                } else {
                    $editor_content = '';
                }
                wp_editor( html_entity_decode( stripcslashes( esc_textarea( $editor_content ) ) ), esc_attr( sanitize_key( $field[ 'field_name' ] ) ), array( 'textarea_name' => esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ), 'textarea_rows' => 5 ) );
                if ( isset( $field[ 'field_description' ] ) ) { ?>
                    <span class="description"><?php echo esc_html( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ); ?></span>
                <?php }

            } elseif ( 'image' == $field[ 'field_type' ] ) { ?>
                <div class="file_url_holder">
                    <label>
                        <input class="file_url" type="text" size="36" name="<?php echo esc_attr( $field[ 'field_name' ] . '_file_url_' . $field[ 'post_field_type' ] ); ?>" value="<?php if ( isset( $obj ) ) { echo esc_attr( isset( $obj->details->{$field[ 'field_name' ] . '_file_url'} ) ? $obj->details->{$field[ 'field_name' ] . '_file_url'} : '' ); } ?>"/>
                        <input class="file_url_button button-secondary" type="button" value="<?php esc_html_e( 'Browse', 'tickera-event-ticketing-system' ); ?>"/>
                        <?php if ( isset( $field[ 'field_description' ] ) ) { ?>
                            <span class="description"><?php echo esc_html( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ); ?></span>
                        <?php } ?>
                    </label>
                </div><?php
            }

            if ( $show_title ) { ?>
                </label>
            <?php }

            return ob_get_clean();
        }

        /**
         * @param $field
         * @param bool $echo
         * @param string $additional_classes
         * @return string
         */
        public static function conditionals( $field, $echo = true, $additional_classes = '' ) {

            $conditional_atts = '';

            if ( isset( $field[ 'conditional' ] ) ) {
                $conditional_atts .= ' class="tc_conditional ' . esc_attr( $additional_classes ) . '" ';
                $conditional_atts .= ' data-condition-field_name="' . esc_attr( $field[ 'conditional' ][ 'field_name' ] ) . '" ';
                $conditional_atts .= ' data-condition-field_type="' . esc_attr( $field[ 'conditional' ][ 'field_type' ] ) . '" ';
                $conditional_atts .= ' data-condition-value="' . esc_attr( $field[ 'conditional' ][ 'value' ] ) . '" ';
                $conditional_atts .= ' data-condition-action="' . esc_attr( $field[ 'conditional' ][ 'action' ] ) . '" ';
            } else {
                $conditional_atts .= ' class="' . esc_attr( $additional_classes ) . '" ';
            }

            if ( $echo ) {
                echo wp_kses_post( $conditional_atts );

            } else {
                return $conditional_atts;
            }
        }

        /**
         * Render function fields
         *
         * @param $field
         * @param $key
         */
        public static function field_function( $field, $key ) {

            if ( isset( $field[ 'default_value' ] ) ) {
                call_user_func( $field[ 'function' ], $field[ 'field_name' ], $field[ 'default_value' ] );

            } else {
                call_user_func( $field[ 'function' ], $field[ 'field_name' ] );
            } ?>
            <span class="description"><?php echo esc_html( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ); ?></span>
        <?php }

        /**
         * Render input text fields
         *
         * @param $field
         * @param $key
         */

        public static function field_text( $field, $key ) {
            $tc_settings = get_option( sanitize_key( $key ), false ); ?>
            <input type="text" class="<?php echo esc_attr( sanitize_text_field( $field[ 'field_name' ] ) ); ?> <?php echo esc_attr( isset( $field[ 'field_class' ] ) ? sanitize_text_field( $field[ 'field_class' ] ) : '' ); ?>" id="<?php echo esc_attr( sanitize_text_field( $field[ 'field_name' ] ) ); ?>" name="<?php echo esc_attr( sanitize_text_field( $key ) ); ?>[<?php echo esc_attr( sanitize_text_field( $field[ 'field_name' ] ) ); ?>]" value="<?php echo esc_attr( isset( $tc_settings[ $field[ 'field_name' ] ] ) ? sanitize_text_field( stripslashes( $tc_settings[ $field[ 'field_name' ] ] ) ) : ( isset( $field[ 'default_value' ] ) ? sanitize_text_field( stripslashes( $field[ 'default_value' ] ) ) : '' ) ) ?>" <?php echo esc_attr( isset( $field[ 'required' ] ) ? 'required' : '' ); ?> <?php echo esc_attr( isset( $field[ 'number' ] ) ? 'number="true"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'minlength' ] ) ? 'minlength="' . (int) $field[ 'minlength' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'maxlength' ] ) ? 'maxlength="' . (int) $field[ 'maxlength' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'rangelength' ] ) ? 'rangelength="' . (int) $field[ 'rangelength' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'min' ] ) ? 'min="' . (int) $field[ 'min' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'max' ] ) ? 'max="' . (int) $field[ 'max' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'range' ] ) ? 'range="' . (int) $field[ 'range' ] . '"' : '' ); ?>>
            <span class="description"><?php echo esc_html( stripslashes( ( isset( $field[ 'field_description' ] ) ? sanitize_text_field( $field[ 'field_description' ] ) : '' ) ) ); ?></span>
        <?php }

        /**
         * Render input text fields
         *
         * @param $field
         * @param $key
         */
        public static function color_field( $field, $key ) {
            $tc_settings = get_option( sanitize_key( $key ), false ); ?>
            <input type="hidden" name="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" value="<?php echo esc_attr( $field[ 'default_value' ] ); ?>" class="tc-default-color"/>
            <input type="text" class="tc_color_field <?php echo esc_attr( sanitize_text_field( $field[ 'field_name' ] ) ); ?> <?php echo esc_attr( isset( $field[ 'field_class' ] ) ? sanitize_text_field( $field[ 'field_class' ] ) : '' ); ?>" id="<?php echo esc_attr( sanitize_text_field( $field[ 'field_name' ] ) ); ?>" name="<?php echo esc_attr( sanitize_text_field( $key ) ); ?>[<?php echo esc_attr( sanitize_text_field( $field[ 'field_name' ] ) ); ?>]" value="<?php echo esc_attr( isset( $tc_settings[ $field[ 'field_name' ] ] ) ? sanitize_text_field( stripslashes( $tc_settings[ $field[ 'field_name' ] ] ) ) : ( isset( $field[ 'default_value' ] ) ? sanitize_text_field( stripslashes( esc_attr( $field[ 'default_value' ] ) ) ) : '' ) ) ?>" <?php echo esc_attr( isset( $field[ 'required' ] ) ? 'required' : '' ); ?> <?php echo esc_attr( isset( $field[ 'number' ] ) ? 'number="true"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'minlength' ] ) ? 'minlength="' . (int) $field[ 'minlength' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'maxlength' ] ) ? 'maxlength="' . (int) $field[ 'maxlength' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'rangelength' ] ) ? 'rangelength="' . (int) $field[ 'rangelength' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'min' ] ) ? 'min="' . (int) $field[ 'min' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'max' ] ) ? 'max="' . (int) $field[ 'max' ] . '"' : '' ); ?> <?php echo esc_attr( isset( $field[ 'range' ] ) ? 'range="' . (int) $field[ 'range' ] . '"' : '' ); ?>>
            <span class="description"><?php echo esc_html( stripslashes( ( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ) ) ); ?></span>
        <?php }

        /**
         * Render file text fields
         *
         * @param $field
         * @param $key
         */
        function field_file( $field, $key ) {
            $tc_settings = get_option( sanitize_key( $key ), false ); ?>
            <input class="file_url <?php echo esc_attr( isset( $field[ 'field_class' ] ) ? $field[ 'field_class' ] : '' ); ?>" type="text" name="<?php echo esc_attr( $key ); ?>[<?php echo esc_attr( $field[ 'field_name' ] ); ?>]" value="<?php echo esc_attr( isset( $tc_settings[ $field[ 'field_name' ] ] ) ? stripslashes( $tc_settings[ $field[ 'field_name' ] ] ) : ( isset( $field[ 'default_value' ] ) ? stripslashes( $field[ 'default_value' ] ) : '' ) ); ?>"/>
            <input class="file_url_button button-secondary" type="button" value="<?php esc_html_e( 'Browse', 'tickera-event-ticketing-system' ); ?>"/>
            <span class="description"><?php echo esc_html( stripslashes( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ) ); ?></span>
        <?php }

        /**
         * Render date text fields
         *
         * @param $field
         * @param $key
         */
        function field_date( $field, $key ) {

            global $tc;
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style( 'jquery-style', $tc->plugin_url . 'css/jquery-ui-smoothness.css' );
            ?>
            <input type="text" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" value=""/>
            <input type="hidden" name="<?php echo esc_attr( $field[ 'field_name' ] ); ?>_raw" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>_raw" value=""/>
            <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
            <script>
                jQuery( document ).ready( function( $ ) {
                    jQuery( '#<?php echo esc_attr( $field[ 'field_name' ] ); ?>' ).datepicker( {
                        dateFormat: '<?php echo esc_attr( isset( $field[ 'date_format' ] ) ? $field[ 'date_format' ] : 'dd-mm-yy' ); ?>',
                        onSelect: function( dateText, inst ) {
                            jQuery( '#<?php echo esc_attr( $field[ 'field_name' ] ); ?>_raw' ).val( inst.selectedYear + '-' + inv_leading_zeros( inst.selectedMonth ) + '-' + inv_leading_zeros( inst.selectedDay ) );
                        }
                    } );

                    var current_value = jQuery( "#<?php echo esc_attr( $field[ 'field_name' ] ); ?>" ).val();

                    if ( !current_value ) {
                        jQuery( '#<?php echo esc_attr( $field[ 'field_name' ] ); ?>' ).datepicker( "setDate", 15 );
                    }

                } );
            </script>
            <?php
        }

        /**
         * Render extended radio button fields
         *
         * @param $field
         * @param $key
         * @param $value
         */
        public static function field_extended_radio( $field, $key, $value ) {

            $group_name = isset( $field[ 'group_name' ] ) ? '[' . $field[ 'group_name' ] . ']' : '';
            $default_value = isset ( $field[ 'default_value' ] ) ? $field[ 'default_value' ] : '';
            $value = $value ? $value : $default_value;

            $html = '';
            foreach ( $field[ 'values' ] as $index => $val ) {

                $checked = ( $val == $value ) ? 'checked="checked"' : '';

                $label = sprintf(
                    /* translators: %s: Label of a radio button. */
                    __( '%s', 'tickera-event-ticketing-system' ),
                    ucfirst( $val )
                );

                $html .= '<label>';
                $html .= '<input type="radio" class="' . esc_attr( $field[ 'field_name' ] ) . '" name="' . esc_attr( $key . $group_name ) . '[' . esc_attr( $field[ 'field_name' ] ) . ']" value = "' . esc_attr( $val ) . '" ' . $checked . '/>' . esc_html( $label ) . ' ';
                $html .= '</label>';
            }
            $html .= '<span class="description">' . esc_html( stripslashes( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ) ) . '</span>';

            echo wp_kses_post( $html );
        }

        /**
         * Render extended text fields
         *
         * @param $field
         * @param $key
         * @param $value
         */
        public static function field_extended_text( $field, $key, $value ) {

            $group_name = isset( $field[ 'group_name' ] ) ? '[' . $field[ 'group_name' ] . ']' : '';
            $placeholder = isset( $field[ 'placeholder' ] ) ? $field[ 'placeholder' ] : '';
            $default_value = isset ( $field[ 'default_value' ] ) ? $field[ 'default_value' ] : '';
            $value = $value ? $value : $default_value;
            $html = '';
            $html .= '<input type="text" class="' . esc_attr( $field[ 'field_name' ] ) . '" id="' . esc_attr( $field[ 'field_name' ] ) . '" name="' . esc_attr( $key . $group_name ) . '[' . esc_attr( $field[ 'field_name' ] ) . ']" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '"/>';
            $html .= '<span class="description">' . esc_html( stripslashes( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ) ) . '</span>';
            echo wp_kses_post( $html );
        }

        /**
         * Render select multiple fields
         *
         * @param $field
         * @param $key
         * @param $current_value
         */
        public static function field_select_multiple( $field, $key, $current_value ) {

            $group_name = isset( $field[ 'group_name' ] ) ? '[' . $field[ 'group_name' ] . ']' : '';
            $values = isset( $field[ 'values' ] ) ? $field[ 'values' ] : '';
            $currently_selected = ( $current_value ) ? array_filter( $current_value ) : '';
            ?>

            <select name="<?php echo esc_attr( $key . $group_name . '[' . $field[ 'field_name' ] . ']' ); ?>[]" multiple="true" id="tc_ticket_types">
                <option value="" <?php echo esc_attr( ( ( is_array( $currently_selected ) && count( $currently_selected ) == 1 && in_array( '', $currently_selected ) ) || ! is_array( $currently_selected ) ) ? 'selected' : '' ); ?>><?php esc_html_e( 'All', 'tickera-event-ticketing-system' ); ?></option>
                <?php foreach ( $values as $key => $value ) : ?>
                    <option value="<?php echo esc_attr( $value ) ?>" <?php echo esc_attr( is_array( $currently_selected ) && in_array( $value, $currently_selected ) ? 'selected' : '' ); ?>><?php echo esc_html( ucfirst( str_replace( '_', ' ', $value ) ) ); ?></option>
                <?php endforeach; ?>
            </select>
            <span class="description"> <?php echo esc_html( isset( $field[ 'field_description' ] ) ? stripslashes( $field[ 'field_description' ] ) : '' ); ?> </span>
            <?php // Open PHP for succeeding source codes
        }

        /**
         * Render textarea fields
         *
         * @param $field
         * @param $key
         */
        public static function field_textarea( $field, $key ) {
            $tc_settings = get_option( sanitize_key( $key ), false );
            ?>
            <textarea class="<?php echo esc_attr( $field[ 'field_name' ] ); ?> <?php echo esc_attr( isset( $field[ 'field_class' ] ) ? $field[ 'field_class' ] : '' ); ?>" name="<?php echo esc_attr( $key ); ?>[<?php echo esc_attr( $field[ 'field_name' ] ); ?>]"><?php echo esc_textarea( isset( $tc_settings[ $field[ 'field_name' ] ] ) ? stripslashes( $tc_settings[ $field[ 'field_name' ] ] ) : ( isset( $field[ 'default_value' ] ) ? stripslashes( $field[ 'default_value' ] ) : '' ) ) ?></textarea>
            <span class="description"><?php echo esc_html( stripslashes( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ) ); ?></span>
            <?php
        }

        /**
         * Render wp_editor fields
         *
         * @param $field
         * @param $key
         */
        public static function field_wp_editor( $field, $key ) {

            $tc_settings = get_option( sanitize_key( $key ), false );
            $saved_value = isset( $tc_settings[ $field[ 'field_name' ] ] ) ? $tc_settings[ $field[ 'field_name' ] ] : '';

            if ( $saved_value == '' && $field[ 'default_value' ] !== '' ) {
                $saved_value = $field[ 'default_value' ];
            }
            ?>
            <?php wp_editor(  html_entity_decode( stripcslashes( esc_textarea( $saved_value ) ) ), esc_attr( sanitize_key( 'inv_wp_editor_' . $field[ 'field_name' ] ) ), array( 'textarea_name' => esc_attr( $key . '[' . $field[ 'field_name' ] . ']' ), 'textarea_rows' => 2 ) ); ?>
            <br/><span class="description"><?php echo esc_html( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ); ?></span>
            <?php
        }

        /**
         * Render radio fields
         *
         * @param $field
         * @param $key
         */
        public static function field_radio( $field, $key ) {

            $tc_settings = get_option( sanitize_key( $key ), false );
            $saved_value = isset( $tc_settings[ $field[ 'field_name' ] ] ) ? $tc_settings[ $field[ 'field_name' ] ] : '';

            if ( $saved_value == '' && $field[ 'default_value' ] !== '' ) {
                $saved_value = $field[ 'default_value' ];
            }

            foreach ( $field[ 'values' ] as $key => $value ) { ?>
                <input type="radio" class="<?php echo esc_attr( $field[ 'field_name' ] ); ?> <?php echo esc_attr( isset( $field[ 'field_class' ] ) ? $field[ 'field_class' ] : '' ); ?>" name="<?php echo esc_attr( $key ); ?>[<?php echo esc_attr( $field[ 'field_name' ] ); ?>]" value="<?php echo esc_attr( stripslashes( $key ) ); ?>" <?php checked( $key, $saved_value, true ); ?> /> <?php echo esc_html( $value ); ?><?php
            } ?>
            <br/><span class="description"><?php echo esc_html( stripslashes( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ) ); ?></span>
            <?php
        }

        /**
         * Render checkbox fields
         *
         * @param $field
         * @param $key
         */
        public static function field_select( $field, $key ) {
            $tc_settings = get_option( esc_attr( $key ), false );
            $saved_value = isset( $tc_settings[ $field[ 'field_name' ] ] ) ? $tc_settings[ $field[ 'field_name' ] ] : '';

            if ( $saved_value == '' && $field[ 'default_value' ] !== '' ) {
                $saved_value = $field[ 'default_value' ];
            }
            ?>
            <select name="<?php echo esc_attr( $key ); ?>[<?php echo esc_attr( $field[ 'field_name' ] ); ?>]" class="<?php echo esc_attr( $field[ 'field_name' ] ); ?> <?php echo esc_attr( isset( $field[ 'field_class' ] ) ? $field[ 'field_class' ] : '' ); ?>">
                <?php foreach ( $field[ 'values' ] as $key => $value ) { ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $saved_value, true ); ?>><?php echo esc_html( $value ); ?></option>
                <?php } ?>
            </select>
            <br/><span class="description"><?php echo esc_html( stripslashes( isset( $field[ 'field_description' ] ) ? $field[ 'field_description' ] : '' ) ); ?></span>
            <?php
        }
    }
}
