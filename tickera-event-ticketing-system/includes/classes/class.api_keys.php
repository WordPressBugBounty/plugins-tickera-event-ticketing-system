<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_API_Keys' ) ) {

    class TC_API_Keys {

        var $form_title = '';
        var $valid_admin_fields_type = array( 'text', 'textarea', 'image', 'function' );

        function __construct() {
            $this->form_title = __( 'API Keys', 'tickera-event-ticketing-system' );
            $this->valid_admin_fields_type = apply_filters( 'tc_valid_admin_fields_type', $this->valid_admin_fields_type );
        }

        function TC_API_Keys() {
            $this->__construct();
        }

        function get_rand_api_key() {
            $data = '';
            $uid = uniqid( "", true );
            $data .= isset( $_SERVER[ 'REQUEST_TIME' ] ) ? (int) $_SERVER[ 'REQUEST_TIME' ] : '';
            $data .= isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? sanitize_text_field( $_SERVER[ 'HTTP_USER_AGENT' ] ) : '';
            $data .= isset( $_SERVER[ 'LOCAL_ADDR' ] ) ? sanitize_text_field( $_SERVER[ 'LOCAL_ADDR' ] ) : '';
            $data .= isset( $_SERVER[ 'LOCAL_PORT' ] ) ? sanitize_text_field( $_SERVER[ 'LOCAL_PORT' ] ) : '';
            $data .= isset( $_SERVER[ 'REMOTE_ADDR' ] ) ? sanitize_text_field( $_SERVER[ 'REMOTE_ADDR' ] ) : '';
            $data .= isset( $_SERVER[ 'REMOTE_PORT' ] ) ? (int) $_SERVER[ 'REMOTE_PORT' ] : '';
            return substr( strtoupper( hash( 'ripemd128', $uid . md5( $data ) ) ), 0, apply_filters( 'tc_rand_api_key_length', 8 ) );
        }

        function get_api_keys_fields() {
            global $tc;
            $default_fields = array(
                array(
                    'field_name' => 'event_name',
                    'field_title' => __( 'Events / event categories', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_api_keys_events',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'field_name' => 'api_key_name',
                    'field_title' => __( 'API key title', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => __( 'For example: iPhone 1, South Entrance, John Smith etc. This name will be linked with every check-in operation.', 'tickera-event-ticketing-system' ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                ),
                array(
                    'field_name' => 'api_key',
                    'field_title' => __( 'API key', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => sprintf(
                        /* translators: %s: Tickera */
                        __( 'This is the API key you will have to enter in your %s <a href="https://apps.apple.com/us/app/checkinera/id1487078834" target="_blank">iPhone</a>, <a href="https://play.google.com/store/apps/details?id=com.tickera.checkinera&hl=en" target="_blank">Android</a> or premium <a href="https://tickera.com/checkinera-web/" target="_blank">Web app</a> check-in application', 'tickera-event-ticketing-system' ),
                        esc_html( $tc->title )
                    ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'default_value' => $this->get_rand_api_key(),
                ),
                array(
                    'field_name' => 'api_username',
                    'field_title' => __( 'Username', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => __( 'This is the WordPress user who will have access to the API key within the WP Admin. It is useful if you want to create API key which will be available to a user with "Staff" role. If you leave it empty, API key will be available for administrators only.', 'tickera-event-ticketing-system' ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'default_value' => '',
                ),
                array(
                    'field_name' => 'api_url',
                    'field_title' => __( 'Your URL', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_api_get_site_url',
                    'field_description' => __( 'This is the exact URL you should use when logging in to the check-in app.', 'tickera-event-ticketing-system' ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'default_value' => '',
                ),
            );

            return apply_filters( 'tc_api_keys_fields', $default_fields );
        }

        function get_columns() {

            $columns = [];
            $fields = $this->get_api_keys_fields();
            $results = tickera_search_array( $fields, 'table_visibility', true );

            $columns[ 'ID' ] = __( 'ID', 'tickera-event-ticketing-system' );

            foreach ( $results as $result ) {
                $columns[ $result[ 'field_name' ] ] = $result[ 'field_title' ];
            }

            $columns[ 'edit' ] = __( 'Edit', 'tickera-event-ticketing-system' );
            $columns[ 'delete' ] = __( 'Delete', 'tickera-event-ticketing-system' );

            return $columns;
        }

        function check_field_property( $field_name, $property ) {
            $fields = $this->get_api_keys_fields();
            $result = tickera_search_array( $fields, 'field_name', $field_name );
            return isset( $result[ 0 ][ 'post_field_type' ] ) ? $result[ 0 ][ 'post_field_type' ] : '';
        }

        function is_valid_api_key_field_type( $field_type ) {

            if ( in_array( $field_type, $this->valid_admin_fields_type ) ) {
                return true;

            } else {
                return false;
            }
        }

        function get_api_keys() {}

        /**
         * Sanitize text or textarea fields
         *
         * @param $text
         * @return array|mixed|string|void
         */
        function maybe_sanitize_text_or_textarea_field( $text ) {

            if ( is_array( $text ) ) {

                $temp = [];
                foreach ( $text as $key => &$val ) {

                    if ( is_array( $val ) ) {

                        // Multidimensional Array
                        foreach ( $val as &$md_val ) {
                            $temp[ $key ][] = ( strstr( $md_val, "\n" ) ) ? sanitize_textarea_field( $md_val ) : sanitize_text_field( $md_val );
                        }

                    } else {

                        // Regular Array
                        $temp[] = ( strstr( $val, "\n" ) ) ? sanitize_textarea_field( $val ) : sanitize_text_field( $val );
                    }
                }
                return $temp;

            } else {
                return ( strstr( $text, "\n" ) ) ? sanitize_textarea_field( $text ) : sanitize_text_field( $text );
            }
        }

        function add_new_api_key() {

            global $user_id;

            if ( check_admin_referer( 'tickera_save_api_key' ) && isset( $_POST[ 'add_new_api_key' ] ) ) {

                $metas = [];

                $post_data = tickera_sanitize_array( $_POST, false, true );
                $post_data = $post_data ? $post_data : [];

                foreach ( $post_data as $field_name => $field_value ) {

                    if ( preg_match( '/_post_title/', $field_name ) ) {
                        $title = sanitize_text_field( $field_value );

                    } elseif ( preg_match( '/_post_excerpt/', $field_name ) ) {
                        $excerpt = sanitize_text_field( $field_value );

                    } elseif ( preg_match( '/_post_content/', $field_name ) ) {
                        $content = sanitize_text_field( $field_value );

                    } elseif ( preg_match( '/_post_meta/', $field_name ) ) {
                        $field_value = maybe_unserialize( $field_value );
                        $metas[ sanitize_key( str_replace( '_post_meta', '', $field_name ) ) ] = ( is_array( $field_value ) ? tickera_sanitize_array( $field_value, false, true ) : sanitize_text_field( $field_value ) );
                    }

                    do_action( 'tc_after_api_key_post_field_type_check', $post_data, $field_name, $field_value );
                }

                $metas = apply_filters( 'tc_api_keys_metas', $metas );

                $arg = array(
                    'post_author'   => (int) $user_id,
                    'post_excerpt'  => ( isset( $excerpt ) ? $excerpt : '' ),
                    'post_content'  => ( isset( $content ) ? $content : '' ),
                    'post_status'   => 'publish',
                    'post_title'    => ( isset( $title ) ? $title : '' ),
                    'post_type'     => 'tc_api_keys',
                );

                if ( isset( $_POST[ 'post_id' ] ) ) {
                    $arg[ 'ID' ] = (int) $_POST[ 'post_id' ]; // For edit
                }

                $post_id = @wp_insert_post( tickera_sanitize_array( $arg, true ), true );

                // Update post meta
                if ( $post_id !== 0 ) {

                    foreach ( $metas as $key => $value ) {
                        update_post_meta( (int) $post_id, $key, tickera_sanitize_array( $value, false, true ) );
                    }

                    // Flag as user edit.
                    wp_set_post_lock( $post_id );
                }

                return $post_id;
            }
        }
    }
}
