<?php
namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\TC_Events' ) ) {

    class TC_Events {

        var $form_title = '';
        var $valid_admin_fields_type = array( 'text', 'textarea', 'textarea_editor', 'image', 'function' );

        function __construct() {
            $this->form_title = __( 'Events', 'tickera-event-ticketing-system' );
            $this->valid_admin_fields_type = tickera_apply_filters( 'tickera_valid_admin_fields_type', $this->valid_admin_fields_type );
        }

        function TC_Events() {
            $this->__construct();
        }

        public static function get_event_fields() {

            $default_fields = array(
                array(
                    'field_name' => 'post_title',
                    'field_title' => __( 'Event title', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_title',
                    'show_in_post_type' => false
                ),
                array(
                    'field_name' => 'event_dates',
                    'field_title' => __( 'Dates', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_event_dates_fields',
                    'tooltip' => __( 'Start and end date/time of your event', 'tickera-event-ticketing-system' ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => true,
                ),
                array(
                    'field_name' => 'event_misc',
                    'field_title' => __( 'Misc', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_event_misc_fields',
                    'tooltip' => '',
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => true,
                ),
                array(
                    'field_name' => 'event_date_time',
                    'field_title' => __( 'Start date & time', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'tooltip' => __( 'Start date & time of your event', 'tickera-event-ticketing-system' ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => false,
                    // 'post_type_position' => 'publish_box'
                ),
                array(
                    'field_name' => 'event_end_date_time',
                    'field_title' => __( 'End date & time', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'tooltip' => __( 'End date & time of your event.', 'tickera-event-ticketing-system' ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => false,
                    // 'post_type_position' => 'publish_box'
                ),
                array(
                    'field_name' => 'event_location',
                    'field_title' => __( 'Event location', 'tickera-event-ticketing-system' ),
                    'field_type' => 'text',
                    'tooltip' => sprintf(
                        /* translators: %s: A link to Tickera > Ticket Templates */
                        __( 'Location of your event. This field could be shown on a <a href="%s" target="_blank">ticket template</a> and/or on the event\'s page via shortcode. Example: Grosvenor Square, Mayfair, London', 'tickera-event-ticketing-system' ),
                        esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_ticket_templates' ) )
                    ),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => true
                ),
                array(
                    'field_name' => 'event_terms',
                    'field_title' => __( 'Event terms and conditions', 'tickera-event-ticketing-system' ),
                    'field_type' => 'textarea_editor',
                    'tooltip' => sprintf(
                        /* translators: %s: A link to Tickera > Ticket Templates */
                        __( 'Terms and Conditions for your event.<br/>The content of this field can be displayed on a <a href="%s" target="_blank">ticket template</a> by placing <i>Terms & Conditions</i> element to the ticket template and/or on the event\'s page via shortcode.<br/>Optional field.', 'tickera-event-ticketing-system' ),
                        esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_ticket_templates' ) )
                    ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => true
                ),
                array(
                    'field_name' => 'event_logo',
                    'field_title' => __( 'Event logo', 'tickera-event-ticketing-system' ),
                    'field_type' => 'image',
                    'tooltip' => sprintf(
                        /* translators: %s: A link to Tickera > Ticket Templates */
                        __( 'Logo of your event. 300 DPI is recommended.<br/>The image selected here can be displayed on a <a href="%s" target="_blank">ticket template</a> by placing <i>Event Logo</i> element to the ticket template and/or on the event\'s page via shortcode.<br/>Optional field.', 'tickera-event-ticketing-system' ),
                        esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_ticket_templates' ) )
                    ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => true
                ),
                array(
                    'field_name' => 'sponsors_logo',
                    'field_title' => __( 'Sponsor logo', 'tickera-event-ticketing-system' ),
                    'field_type' => 'image',
                    'tooltip' => sprintf(
                        /* translators: %s: A link to Tickera > Ticket Templates */
                        __( 'Logo of the sponsor of your event. 300 DPI is recommended.<br/>The image selected here can be displayed on a <a href="%s" target="_blank">ticket template</a> by placing <i>Sponsor Logo</i> element to the ticket template and/or on the event\'s page via shortcode.<br/>Optional field.', 'tickera-event-ticketing-system' ),
                        esc_url( admin_url( 'edit.php?post_type=tc_events&page=tc_ticket_templates' ) )
                    ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => true
                ),
                array(
                    'field_name' => 'limit_level',
                    'field_title' => __( 'Ticket quantity limitation', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_event_limit_level_option',
                    'tooltip' => __( 'Select the option "Per Event" and then enter a number if you want to limit the number of available tickets on per-event basis.  If "Per ticket type" is selected, the available quantity will be dictated by the quantity set for each ticket type.<br/>Note: If you are using Bridge For Woocommerce, you will need to disable "Manage Stock" option for each product declared as ticket and assigned to this event', 'tickera-event-ticketing-system' ),
                    'table_visibility' => false,
                    'post_field_type' => 'post_meta',
                    'show_in_post_type' => true
                ),
            );

            return tickera_apply_filters( 'tickera_event_fields', $default_fields );
        }

        function get_columns() {
            $fields = $this->get_event_fields();
            $results = tickera_search_array( $fields, 'table_visibility', true );

            $columns = array();

            $columns[ 'ID' ] = __( 'ID', 'tickera-event-ticketing-system' );

            foreach ( $results as $result ) {
                $columns[ $result[ 'field_name' ] ] = $result[ 'field_title' ];
            }

            $columns[ 'edit' ] = __( 'Edit', 'tickera-event-ticketing-system' );
            $columns[ 'delete' ] = __( 'Delete', 'tickera-event-ticketing-system' );

            return $columns;
        }

        function check_field_property( $field_name, $property ) {
            $fields = $this->get_event_fields();
            $result = tickera_search_array( $fields, 'field_name', $field_name );
            return $result[ 0 ][ 'post_field_type' ];
        }

        function is_valid_event_field_type( $field_type ) {
            if ( in_array( $field_type, $this->valid_admin_fields_type ) ) {
                return true;
            } else {
                return false;
            }
        }

        function add_new_event() {

            global $user_id;

            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin event save request is handled by the Tickera event editor workflow.
            if ( isset( $_POST[ 'add_new_event' ] ) ) {

                $metas = [];

                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin event payload is unslashed and sanitized within tickera_sanitize_array().
                $post_data = tickera_sanitize_array( wp_unslash( $_POST ), true, true );
                $post_data = $post_data ? $post_data : [];

                foreach ( $post_data as $field_name => $field_value ) {

                    if ( preg_match( '/_post_title/', $field_name ) ) {
                        $title = sanitize_text_field( $field_value );

                    } elseif ( preg_match( '/_post_excerpt/', $field_name ) ) {
                        $excerpt = wp_filter_post_kses( $field_value );

                    } elseif ( preg_match( '/_post_content/', $field_name ) ) {
                        $content = wp_filter_post_kses( $field_value );

                    } elseif ( preg_match( '/_post_meta/', $field_name ) ) {
                        $metas[ sanitize_key( str_replace( '_post_meta', '', $field_name ) ) ] = sanitize_text_field( $field_value );
                    }

                    tickera_do_action( 'tickera_after_event_post_field_type_check' );
                }

                $metas = tickera_apply_filters( 'tickera_events_metas', $metas );

                $arg = array(
                    'post_author'   => (int) $user_id,
                    'post_excerpt'  => ( isset( $excerpt ) ? $excerpt : '' ),
                    'post_content'  => ( isset( $content ) ? $content : '' ),
                    'post_status'   => 'publish',
                    'post_title'    => ( isset( $title ) ? $title : '' ),
                    'post_type'     => 'tc_events',
                );

                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin event post ID is used only to update the submitted event.
                if ( isset( $_POST[ 'post_id' ] ) ) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin event post ID is cast before being passed to wp_insert_post().
                    $arg[ 'ID' ] = (int) $_POST[ 'post_id' ];
                }

                $post_id = @wp_insert_post( tickera_sanitize_array( $arg, true ), true );

                // Update post meta
                if ( $post_id !== 0 ) {
                    foreach ( $metas as $key => $value ) {
                        update_post_meta( (int) $post_id, $key, tickera_sanitize_array( $value, true, true ) );
                    }
                }

                return $post_id;
            }
        }

        /**
         * Collection of Event Ids that will be automatically hidden when the event expires
         *
         * @return array
         */
        public static function get_hidden_events_ids( $event_id = false ) {

            $hidden_events_ids = [];
            $current_timestamp = date_i18n( 'U', current_time( 'timestamp' ) );

            if ( $event_id ) {
                $event_id = (int) $event_id;

                if (
                    'tc_events' === get_post_type( $event_id )
                    && 1 === (int) get_post_meta( $event_id, 'hide_event_after_expiration', true )
                    && $current_timestamp > date_i18n( 'U', strtotime( get_post_meta( $event_id, 'event_end_date_time', true ) ) )
                    && ! tickera_apply_filters( 'tickera_bypass_hide_event_after_expiration', false, $event_id )
                ) {
                    $hidden_events_ids[] = $event_id;
                }

                return $hidden_events_ids;
            }

            $results = get_posts(
                array(
                    'post_type'              => 'tc_events',
                    'post_status'            => 'any',
                    'posts_per_page'         => -1,
                    'fields'                 => 'ids',
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required to filter events configured to hide after expiration.
                    'meta_key'               => 'hide_event_after_expiration',
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Required to filter events configured to hide after expiration.
                    'meta_value'             => 1,
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                )
            );

            foreach ( $results as $maybe_hidden_event_id ) {
                $maybe_hidden_event_id = (int) $maybe_hidden_event_id;

                if (
                    $current_timestamp > date_i18n( 'U', strtotime( get_post_meta( $maybe_hidden_event_id, 'event_end_date_time', true ) ) )
                    && ! tickera_apply_filters( 'tickera_bypass_hide_event_after_expiration', false, $maybe_hidden_event_id )
                ) {
                    $hidden_events_ids[] = $maybe_hidden_event_id;
                }
            }

            return $hidden_events_ids;
        }
    }
}
