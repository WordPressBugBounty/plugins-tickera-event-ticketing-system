<?php
/**
 * Better Events
 * Better events presentation for Tickera
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Addons\TC_Better_Events' ) ) {

    class TC_Better_Events {

        var $version = '1.0';
        var $title = 'Better Events';
        var $name = 'better-events';

        function __construct() {

            global $post;

            if ( ! isset( $post ) ) {
                $post_id = isset( $_GET[ 'post' ] ) ? (int) $_GET[ 'post' ] : '';
                $post_type = get_post_type( $post_id );

            } else {
                $post_type = get_post_type( $post );
            }

            if ( empty( $post_type ) ) {
                $post_type = isset( $_GET[ 'post_type' ] ) ? sanitize_text_field( $_GET[ 'post_type' ] ) : '';
            }

            add_action( 'init', array( $this, 'register_event_category' ), 1 );
            add_filter( 'tc_settings_general_sections', array( $this, 'tc_settings_general_sections' ) );
            add_filter( 'tc_general_settings_page_fields', array( $this, 'tc_general_settings_page_fields' ) );
            add_filter( 'tc_events_post_type_args', array( $this, 'tc_events_post_type_args' ) );
            add_filter( 'manage_tc_events_posts_columns', array( $this, 'manage_tc_events_columns' ) );
            add_action( 'manage_tc_events_posts_custom_column', array( $this, 'manage_tc_events_posts_custom_column' ) );
            add_filter( 'manage_edit-tc_events_sortable_columns', array( $this, 'manage_edit_tc_events_sortable_columns' ) );
            add_action( 'post_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_and_styles' ) );
            add_filter( 'tc_add_admin_menu_page', array( $this, 'tc_add_admin_menu_page' ) );
            add_filter( 'first_tc_menu_handler', array( $this, 'first_tc_menu_handler' ) );
            add_action( 'admin_menu', array( $this, 'rename_events_menu_item' ) );
            add_action( 'add_meta_boxes', array( $this, 'add_events_metaboxes' ) );
            add_action( 'save_post', array( $this, 'save_metabox_values' ) );
            add_action( 'delete_post', array( $this, 'delete_event_api_keys' ) );
            add_filter( 'the_content', array( $this, 'modify_the_content' ) );

            if ( 'tc_events' == $post_type ) {
                add_action( 'edit_form_after_editor', array( $this, 'edit_form_after_editor' ), 10, 1 );
                add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 10, 2 );
            }

            add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
            add_filter( 'post_row_actions', array( $this, 'duplicate_event_action' ), 10, 2 );
            add_action( 'admin_action_tc_duplicate_event_as_draft', 'Tickera\Addons\TC_Better_Events::tc_duplicate_event_as_draft' );
            add_action( 'pre_get_posts', 'Tickera\Addons\TC_Better_Events::tc_maybe_hide_events' );
            add_action( 'pre_get_posts', array( $this, 'tc_sort_end_start_date_columns' ) );
        }

        function tc_sort_end_start_date_columns( $query ) {

            global $post_type;

            if ( 'tc_events' == $post_type && is_admin() && $query->is_main_query() && isset( $_GET[ 'orderby' ] ) ) {

                if ( 'event_date_time' == $_GET[ 'orderby' ] ) {
                    $query->set( 'meta_key', 'event_date_time' );
                    $query->set( 'meta_type', 'DATE' );
                    $query->set( 'orderby', 'meta_value' );

                } elseif ( 'event_end_date_time' == $_GET[ 'orderby' ] ) {
                    $query->set( 'meta_key', 'event_end_date_time' );
                    $query->set( 'meta_type', 'DATE' );
                    $query->set( 'orderby', 'meta_value' );
                }
            }

            return $query;
        }

        public static function tc_maybe_hide_events( $query ) {

            $tc_check_taxonomy = ( isset( $query->tax_query->queries[ 0 ][ 'taxonomy' ] ) )
                ? $query->tax_query->queries[ 0 ][ 'taxonomy' ]
                : '';

            $is_event_category = ( isset( $query->queried_object->taxonomy ) && 'event_category' == $query->queried_object->taxonomy ) ? true : false;

            if ( is_array( $query->get( 'post_type' ) ) ) {
                $is_event_post_type = ( in_array( 'tc_events', $query->get( 'post_type' ) ) ) ? true : false;

            } elseif ( is_string( $query->get( 'post_type' ) ) ) {
                $is_event_post_type = ( 'tc_events' == $query->get( 'post_type' ) ) ? true : false;

            } else {
                $is_event_post_type = false;
            }

            // Removed from top and improved performance
            if ( ! is_admin() && $query->is_main_query() && true == $query->is_archive && ( $is_event_post_type || $is_event_category || 'event_category' == $tc_check_taxonomy ) ) {

                $hidden_events = \Tickera\TC_Events::get_hidden_events_ids();
                if ( count( $hidden_events ) > 0 ) {
                    $query->set( 'post__not_in', $hidden_events );
                }
            }

            return $query;
        }

        public static function tc_duplicate_event_as_draft( $post_id = false, $duplicate_title_extension = ' [duplicate]', $caller = 'standard', $caller_id = false, $old_caller_id = false, $redirect = true ) {

            global $wpdb;

            if ( $post_id !== false ) {
                if ( ! ( isset( $_GET[ 'post' ] ) || isset( $_POST[ 'post' ] ) || ( isset( $_REQUEST[ 'action' ] ) && 'tc_duplicate_event_as_draft' == $_REQUEST[ 'action' ] ) ) ) {
                    wp_die( 'No event to duplicate has been supplied!' );
                }
            }

            // Get the original post id
            $post_id = $post_id ? $post_id : ( isset( $_GET[ 'post' ] ) ? absint( (int) $_GET[ 'post' ] ) : absint( (int) $_POST[ 'post' ] ) );
            $show_tickets_automatically_old = get_post_meta( $post_id, 'show_tickets_automatically', true );

            // And all the original post data then
            $post = get_post( $post_id );

            /*
             * If you don't want current user to be the new post author,
             * then change next couple of lines to this: $new_post_author = $post->post_author;
             */
            $current_user = wp_get_current_user();
            $new_post_author = $current_user->ID;

            // If post data exists, create the post duplicate
            if ( isset( $post ) && $post != null ) {

                // New post data array
                $new_post_author = wp_get_current_user();
                $new_post_date = current_time( 'mysql' );
                $new_post_date_gmt = get_gmt_from_date( $new_post_date );

                $args = apply_filters( 'tc_duplicate_event_args', [
                    'post_author'               => (int) $new_post_author->ID,
                    'post_date'                 => $new_post_date,
                    'post_date_gmt'             => $new_post_date_gmt,
                    'post_content'              => $post->post_content,
                    'post_content_filtered'     => $post->post_content_filtered,
                    'post_title'                => $post->post_title . $duplicate_title_extension,
                    'post_excerpt'              => $post->post_excerpt,
                    'post_status'               => 'draft',
                    'post_type'                 => $post->post_type,
                    'comment_status'            => $post->comment_status,
                    'ping_status'               => $post->ping_status,
                    'post_password'             => $post->post_password,
                    'to_ping'                   => $post->to_ping,
                    'pinged'                    => $post->pinged,
                    'post_modified'             => $new_post_date,
                    'post_modified_gmt'         => $new_post_date_gmt,
                    'menu_order'                => (int) $post->menu_order,
                    'post_mime_type'            => $post->post_mime_type,
                ], (int) $post_id );

                // Insert the post by wp_insert_post() function
                $new_post_id = wp_insert_post( tickera_sanitize_array( $args, true ) );

                /*
                 * Get all current post terms ad set them to the new post draft
                 * Returns array of taxonomy names for post type, ex array("category", "post_tag");
                 */
                $taxonomies = get_object_taxonomies( $post->post_type );

                foreach ( $taxonomies as $taxonomy ) {
                    $post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
                    wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
                }

                // Duplicate all post meta just in two SQL queries
                $post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d", (int) $post_id ) );

                if ( count( $post_meta_infos ) != 0 ) {

                    $sql_query_sel = [];
                    $table_columns = [ 'post_id', 'meta_key', 'meta_value' ];
                    $prepare_table_columns_placeholder = implode( ',', array_fill( 0, count( $table_columns ), '%1s' ) );
                    $sql_query = $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} ($prepare_table_columns_placeholder) ", $table_columns );

                    foreach ( $post_meta_infos as $meta_info ) {
                        $meta_key = $meta_info->meta_key;
                        $meta_value = addslashes( $meta_info->meta_value );
                        $sql_query_sel[] = $wpdb->prepare( "SELECT %d, %s, %s", $new_post_id, $meta_key, $meta_value );
                    }
                    $sql_query .= implode( " UNION ALL ", $sql_query_sel );
                    $wpdb->query( $sql_query );
                }

                delete_post_meta( $new_post_id, 'show_tickets_automatically' );
                update_post_meta( $new_post_id, 'show_tickets_automatically', sanitize_text_field( $show_tickets_automatically_old ) );

                // Create new api access
                TC_Better_Events::create_event_api_key( $new_post_id );

                do_action( 'tc_after_event_duplication', $new_post_id, $post_id, $caller, $caller_id, $old_caller_id );

                // Finally, redirect to the edit post screen for the new draft
                $new_post_url = add_query_arg( [
                    'post'      => $new_post_id,
                    'action'    => 'edit',
                    'post'      => $new_post_id
                ], admin_url( 'post.php' ) );

                if ( $redirect ) {
                    tickera_redirect( $new_post_url );
                }

            } else {
                wp_die( 'Post creation failed, could not find original post: ' . $post_id );
            }
        }

        function duplicate_event_action( $actions, $post ) {

            if ( current_user_can( 'edit_posts' ) && 'tc_events' == $post->post_type ) {

                unset( $actions[ 'inline hide-if-no-js' ] );

                $duplicate_url = add_query_arg( [
                    'post_type' => 'tc_events',
                    'action'    => 'tc_duplicate_event_as_draft',
                    'post'      => (int) $post->ID
                ], admin_url( 'edit.php' ) );

                $actions[ 'duplicate' ] = '<a href="' . esc_url( $duplicate_url ) . '" title="' . esc_attr( __( 'Duplicate this Event', 'tickera-event-ticketing-system' ) ) . '" rel="permalink">' . esc_html__( 'Duplicate', 'tickera-event-ticketing-system' ) . '</a>';
            }

            return $actions;
        }

        public static function get_creation_messages() {

            $ticket_type_admin_url = apply_filters( 'tc_ticket_type_admin_url', admin_url( 'edit.php?post_type=tc_tickets' ) );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Good work! Now go and create some <a href="%s">ticket types</a> for your event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Great! Create some <a href="%s">ticket types</a> for this event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Good start! Now go <a href="%s">here</a> and create ticket types for your event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'You are almost there. Go <a href="%s">here</a> and create ticket types for your event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Awesome! Go <a href="%s">here</a> and create ticket types for your event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Lovely! You just need to <a href="%s">create ticket types</a> now.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Amazing! Now <a href="%s">create ticket types</a> for your event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Cool! But one thing is missing. Go <a href="%s">here</a> and create ticket types for your event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Saved. Now <a href="%s">create ticket types</a> for your event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Done. Now <a href="%s">create ticket types</a> for your event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Changes are saved. Consider adding <a href="%s">ticket types</a> for your event now.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            $creation_messages[] = sprintf(
                /* translators: %s: Admin url of Tickera > Ticket Type. */
                __( 'Good! It\'s time to add some <a href="%s">ticket types</a> for your next event.', 'tickera-event-ticketing-system' ),
                esc_url( $ticket_type_admin_url )
            );

            return apply_filters( 'tc_event_no_ticket_types_creation_messages', $creation_messages );
        }

        function post_updated_messages( $messages ) {

            $post = get_post();
            $event = new \Tickera\TC_Event( (int) $post->ID );
            $event_ticket_types = $event->get_event_ticket_types();
            $no_ticket_types = ( count( $event_ticket_types ) == 0 ) ? true : false;

            $creation_messages = TC_Better_Events::get_creation_messages();
            $random_creation_message = $creation_messages[ rand( 0, count( $creation_messages ) - 1 ) ];

            $messages[ 'tc_events' ] = array(
                0 => '', // Unused. Messages start at index 1.
                1 => $no_ticket_types
                    ? $random_creation_message
                    : sprintf(
                        /* translators: %s: Admin url of Tickera > Events. */
                        __( 'Event post updated. <a href="%s">View post</a>', 'tickera-event-ticketing-system' ),
                        ( isset( $permalink ) ? esc_url( $permalink ) : '' )
                    ),
                2 => __( 'Custom field updated.', 'tickera-event-ticketing-system' ),
                3 => __( 'Custom field deleted.', 'tickera-event-ticketing-system' ),
                4 => $no_ticket_types ? $random_creation_message : __( 'Event post updated.', 'tickera-event-ticketing-system' ),
                5 => isset( $_GET[ 'revision' ] )
                    ? sprintf(
                        /* translators: %s: Formatted datetime timestamp of a revision. */
                        __( 'Event post restored to revision from %s', 'tickera-event-ticketing-system' ),
                        wp_post_revision_title( (int) $_GET[ 'revision' ], false )
                    )
                    : false,
                6 => $no_ticket_types ? $random_creation_message : __( 'Event post published.', 'tickera-event-ticketing-system' ),
                7 => $no_ticket_types ? $random_creation_message : __( 'Event post saved.', 'tickera-event-ticketing-system' ),
                8 => $no_ticket_types
                    ? $random_creation_message
                    : sprintf(
                        /* translators: %s: Admin url of Tickera > Events. */
                        __( 'Event post submitted. <a target="_blank" href="%s">Preview post</a>', 'tickera-event-ticketing-system' ),
                        esc_url( add_query_arg( 'preview', 'true', ( isset( $permalink ) ? $permalink : '' ) ) )
                    ),
                9 => sprintf(
                    /* translators: 1: Formatted datetime timestamp of a scheduled event. */
                    __( 'Event post scheduled for: <strong>%1$s</strong>.', 'tickera-event-ticketing-system' ),
                    date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) )
                ),
                10 => __( 'Event draft updated.', 'tickera-event-ticketing-system' )
            );

            return $messages;
        }

        function edit_form_after_editor( $post ) {
            echo wp_kses_post( '<span class="description">' . esc_html__( 'You can add various shortcodes via shortcode builder located above the content editor. Make sure that you select "Show tickets automatically" option in the Publish box if you want to show all the available tickets for this event on the event\'s page automatically.', 'tickera-event-ticketing-system' ) . '</span>' );
        }

        function enter_title_here( $enter_title_here, $post ) {

            if ( 'tc_events' == get_post_type( $post ) ) {
                $enter_title_here = __( 'Enter event title here', 'tickera-event-ticketing-system' );
            }

            return $enter_title_here;
        }

        /**
         * Add Events Categories
         */
        public function register_event_category() {

            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $event_category_slug = isset( $tc_general_settings[ 'tc_event_category_slug' ] ) && ! empty( $tc_general_settings[ 'tc_event_category_slug' ] ) ? $tc_general_settings[ 'tc_event_category_slug' ] : 'tc-event-category';
            register_taxonomy( 'event_category', apply_filters( 'tc_events_category_availability', 'tc_events' ), apply_filters( 'tc_register_event_category', [
                'hierarchical' => true,
                'labels' => [
                    'name' => _x( 'Event Categories', 'event_category', 'tickera-event-ticketing-system' ),
                    'singular_name' => _x( 'Event Category', 'event_category', 'tickera-event-ticketing-system' ),
                    'all_items' => __( 'All Event Categories', 'tickera-event-ticketing-system' ),
                    'edit_item' => __( 'Edit Event Category', 'tickera-event-ticketing-system' ),
                    'view_item' => __( 'View Event Category', 'tickera-event-ticketing-system' ),
                    'update_item' => __( 'Update Event Category', 'tickera-event-ticketing-system' ),
                    'add_new_item' => __( 'Add New Event Category', 'tickera-event-ticketing-system' ),
                    'new_item_name' => __( 'New Event Category Name', 'tickera-event-ticketing-system' ),
                    'parent_item' => __( 'Parent Event Category', 'tickera-event-ticketing-system' ),
                    'parent_item_colon' => __( 'Parent Event Category:', 'tickera-event-ticketing-system' ),
                    'search_items' => __( 'Search Event Categories', 'tickera-event-ticketing-system' ),
                    'separate_items_with_commas' => __( 'Separate event categories with commas', 'tickera-event-ticketing-system' ),
                    'add_or_remove_items' => __( 'Add or remove event categories', 'tickera-event-ticketing-system' ),
                    'choose_from_most_used' => __( 'Choose from the most used event categories', 'tickera-event-ticketing-system' ),
                    'not_found' => __( 'No event categories found', 'tickera-event-ticketing-system' ),
                ],
                'capabilities' => [
                    'manage_categories' => 'manage_options',
                    'edit_categories' => 'manage_options',
                    'delete_categories' => 'manage_options',
                    'assign_categories' => 'manage_options'
                ],
                'show_ui' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => [
                    'with_front' => false,
                    'slug' => $event_category_slug,
                ],
            ] ) );
        }

        /**
         * Modify event post title
         *
         * Additional tc_lock_event_single_content to disable specific event information (e.g event date and location) from rendering.
         * Currently used for block type themes.
         * @since 3.5.1.8
         *
         * @param $content
         * @return mixed|string|void
         */
        function modify_the_content( $content ) {

            global $post, $post_type;

            if ( ! is_admin() && ( is_object( $post ) && 'tc_events' == $post->post_type ) && ( ( $post_type && 'tc_events' == $post_type ) || is_tax( 'event_category' ) ) ) {

                $new_content = '';
                $has_active_blocks = apply_filters( 'tc_lock_event_single_content', false );

                // Add date and location to the top of the content if needed
                $tc_general_settings = get_option( 'tickera_general_setting', false );
                $tc_attach_event_date_to_title = isset( $tc_general_settings[ 'tc_attach_event_date_to_title' ] ) && ! empty( $tc_general_settings[ 'tc_attach_event_date_to_title' ] ) ? $tc_general_settings[ 'tc_attach_event_date_to_title' ] : 'yes';
                $tc_attach_event_location_to_title = isset( $tc_general_settings[ 'tc_attach_event_location_to_title' ] ) && ! empty( $tc_general_settings[ 'tc_attach_event_location_to_title' ] ) ? $tc_general_settings[ 'tc_attach_event_location_to_title' ] : 'yes';

                if ( ! $has_active_blocks && 'yes' == $tc_attach_event_date_to_title ) {
                    $new_content .= '<span class="tc_event_date_title_front"><i class="fa fa-clock-o"></i>' . esc_html( do_shortcode( '[tc_event_date]' ) ) . '</span>';
                }

                $event_location = wp_kses_post( do_shortcode( '[tc_event_location]' ) );

                if ( ! $has_active_blocks && 'yes' == $tc_attach_event_location_to_title && ! empty( $event_location ) ) {
                    $new_content .= '<span class="tc_event_location_title_front"><i class="fa fa-map-marker"></i>' . '&nbsp;' . wp_kses_post( $event_location ) . '</span>';
                }

                $content = '<div class="tc_the_content_pre">' . apply_filters( 'tc_the_content_pre', $new_content ) . '</div>' . $content;

                // Add events shortcode to the end of the content if selected
                $show_tickets_automatically = get_post_meta( $post->ID, 'show_tickets_automatically', true );

                if ( is_single() && ( current_user_can( 'manage_options' ) ) ) {

                    $event = new \Tickera\TC_Event( $post->ID );
                    $ticket_types = $event->get_event_ticket_types();
                    $tc_post_type = get_post_type();

                    if ( count( $ticket_types ) == 0 && 'tc_events' == $tc_post_type ) {

                        /*
                         * Bridge for Woocommerce
                         * Notice to add ticket types/product.
                         */
                        $ticket_types_admin_url = ( apply_filters( 'tc_is_woo', false ) == true )
                            ? admin_url( 'post-new.php?post_type=product' )
                            : admin_url( 'post-new.php?post_type=tc_tickets' );

                        $content .= '<div class="tc_warning_ticket_types_needed">';

                        $content .= sprintf(
                                /* translators: %s: Tickera > Ticket types url */
                                __( '<strong>ADMIN NOTICE</strong>: Please <a href="%s">create ticket types</a> for this event.', 'tickera-event-ticketing-system' ),
                                esc_url( $ticket_types_admin_url )
                            );

                        $content .= '</div>';

                    } elseif ( ! $show_tickets_automatically ) {

                        $shortcodes = [
                            'tc_ticket' => __( 'Ticket / Add to cart button', 'tickera-event-ticketing-system' ),
                            'tc_ticket_group' => __( 'Ticket / Add to cart button', 'tickera-event-ticketing-system' ),
                            'tc_event' => __( 'Event Tickets', 'tickera-event-ticketing-system' ),
                            'tc_event_group' => __( 'Event - Add to Cart', 'tickera-event-ticketing-system' ),
                            'tc_event_date' => __( 'Event Date & Time', 'tickera-event-ticketing-system' ),
                            'tc_event_location' => __( 'Event Location', 'tickera-event-ticketing-system' ),
                            'tc_event_terms' => __( 'Event Terms & Conditions', 'tickera-event-ticketing-system' ),
                            'tc_event_logo' => __( 'Event Logo', 'tickera-event-ticketing-system' ),
                            'tc_event_sponsors_logo' => __( 'Event Sponsors Logo', 'tickera-event-ticketing-system' ),
                            'event_tickets_sold' => __( 'Number of tickets sold for an event', 'tickera-event-ticketing-system' ),
                            'event_tickets_left' => __( 'Number of tickets left for an event', 'tickera-event-ticketing-system' ),
                            'tickets_sold' => __( 'Number of sold tickets', 'tickera-event-ticketing-system' ),
                            'tickets_left' => __( 'Number of available tickets', 'tickera-event-ticketing-system' ),
                            'tc_order_history' => __( 'Display order history for a user', 'tickera-event-ticketing-system' ),
                        ];

                        $blocks = [
                            'tc_ticket' => 'tickera/add-to-cart',
                            'tc_ticket_group' => 'tickera/add-to-cart-group',
                            'tc_event' => 'tickera/event-add-to-cart',
                            'tc_event_group' => 'tickera/event-add-to-cart-group',
                            'tc_event_date' => 'tickera/event-date',
                            'tc_event_location' => 'tickera/event-location',
                            'tc_event_terms' => 'tickera/event-terms',
                            'tc_event_logo' => 'tickera/event-logo',
                            'tc_event_sponsors_logo' => 'tickera/event-sponsors-logo',
                            'event_tickets_sold' => 'tickera/event-tickets-sold',
                            'event_tickets_left' => 'tickera/event-tickets-left',
                            'tickets_sold' => 'tickera/tickets-sold',
                            'tickets_left' => 'tickera/tickets-left',
                            'tc_order_history' => 'tickera/order-history'
                        ];

                        $shortcodes = apply_filters( 'tc_shortcodes', $shortcodes );
                        $blocks = apply_filters( 'tc_gutenberg_blocks', $blocks );

                        $has_required_shortcodes = false;

                        foreach ( $shortcodes as $shortcode => $shortcode_title ) {
                            if ( has_shortcode( $content, $shortcode ) || ( isset( $blocks[ $shortcode ] ) && has_block( $blocks[ $shortcode ] ) ) ) {
                                $has_required_shortcodes = true;
                                break;
                            }
                        }

                        if ( ! $has_required_shortcodes && 'tc_events' == $tc_post_type ) {

                            $content .= '<div class="tc_warning_ticket_types_needed">';

                            $content .= sprintf(
                                /* translators: %s: Tickera > Events admin url */
                                __( '<strong>ADMIN NOTICE</strong>: it seems that you have associated ticket types with this event but you don\'t show them. You can show ticket types by checking the box "Show Tickets Automatically" above the update button <a href="%s">here</a>. Alternatively, you can add various shortcodes via shortcode builder located above the content editor.', 'tickera-event-ticketing-system' ),
                                esc_url( admin_url( 'post.php?post=' . (int) $post->ID . '&action=edit' ) )
                            );

                            $content .= '</div>';
                        }
                    }
                }

                if ( $show_tickets_automatically ) {
                    $content .= do_shortcode( apply_filters( 'tc_event_shortcode', '[tc_event]', (int) $post->ID ) );
                }

                return apply_filters( 'tc_the_content', $content );
            }

            return $content;
        }

        function delete_event_api_keys( $post_id ) {

            $api_key_post = array(
                'posts_per_page' => -1,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'tc_api_keys',
                'meta_key' => 'event_name',
                'meta_value' => $post_id,
            );

            $posts = get_posts( $api_key_post );

            foreach ( $posts as $post ) {
                wp_delete_post( $post->ID, true );
            }
        }

        /**
         * Save event post meta values
         *
         * @param $post_id
         * @throws \Exception
         */
        function save_metabox_values( $post_id ) {

            if ( 'tc_events' == get_post_type( $post_id ) ) {

                $metas = [];
                $metas[ 'event_presentation_page' ] = $post_id; // Event calendar support URL for better events interface

                $post_data = tickera_sanitize_array( $_POST, true, true );
                $post_data = $post_data ? $post_data : [];

                foreach ( $post_data as $field_name => $field_value ) {

                    if ( preg_match( '/_post_meta/', $field_name ) ) {

                        $field_name = str_replace( '_post_meta', '', $field_name );

                        switch ( $field_name ) {

                            case 'event_log_file_url':
                            case 'sponsors_logo_file_url':
                                $value = sanitize_text_field( $field_value );
                                break;

                            case 'event_terms':
                            case 'event_location':
                                $value = wp_filter_post_kses( $field_value );
                                break;

                            default:
                                $value = is_array( $field_value ) ? tickera_sanitize_array( $field_value, true, true ) : sanitize_text_field( $field_value );
                        }

                        $metas[ sanitize_key( $field_name ) ] = $value;
                    }
                }

                $metas = apply_filters( 'events_metas', $metas );

                /*
                 * Manually update Show Tickets and Hide Event fields.
                 * Please don't remove the following lines. Otherwise, recreate the process in the post_submitbox_misc_actions method.
                 */
                if ( $_POST && ! isset( $metas[ 'show_tickets_automatically' ] ) ) $metas[ 'show_tickets_automatically' ] = 0;
                if ( $_POST && ! isset( $metas[ 'hide_event_after_expiration' ] ) ) $metas[ 'hide_event_after_expiration' ] = 0;

                foreach ( $metas as $key => $value ) {
                    update_post_meta( (int) $post_id, $key, tickera_sanitize_array( $value, true, true ) );
                }

                TC_Better_Events::create_event_api_key( $post_id );
            }
        }

        /**
         * Create Event API Access
         *
         * @param $event_id
         * @throws \Exception
         * @since 3.5.1.5
         *
         * Serialize event_id as per the latest format
         * @since 3.5.1.6
         */
        public static function create_event_api_key( $event_id ) {

            if ( apply_filters( 'tc_create_event_api_key_automatically', true ) == true ) {

                $event_id = (int) $event_id;
                $status = get_post_status( $event_id );

                // Don't create api on auto draft
                if ( in_array( $status, [ 'auto-draft' ] ) ) {
                    return;
                }

                $formatted_event_id = [ 'all' => [ json_encode( $event_id ) ] ];
                $wp_api_keys_search = new \Tickera\TC_API_Keys_Search( '', '', maybe_serialize( $formatted_event_id ) );

                if ( count( $wp_api_keys_search->get_results() ) == 0 ) {

                    $api_key_post = array(
                        'post_content' => '',
                        'post_status' => 'publish',
                        'post_title' => '',
                        'post_type' => 'tc_api_keys',
                    );

                    $api_key_post = apply_filters( 'tc_event_api_key_post', $api_key_post );
                    $api_key_post_id = wp_insert_post( tickera_sanitize_array( $api_key_post, true ) );

                    // Add post metas for the API Key
                    $api_keys = new \Tickera\TC_API_Keys();

                    if ( $api_key_post_id != 0 ) {
                        update_post_meta( (int) $api_key_post_id, 'event_name', tickera_sanitize_array( $formatted_event_id, false, true ) );
                        update_post_meta( (int) $api_key_post_id, 'api_key_name', get_the_title( $event_id ) );
                        update_post_meta( (int) $api_key_post_id, 'api_key', sanitize_text_field( $api_keys->get_rand_api_key() ) );
                        update_post_meta( (int) $api_key_post_id, 'api_username', '' );
                    }

                } else {

                    /*
                     * Update API Key Title base on Event Name.
                     * Triggers on creating/updating event
                     */
                    foreach ( $wp_api_keys_search->get_results() as $api ) {

                        $api_id = (int) $api->ID;
                        $is_locked = get_post_meta( $api_id, '_edit_lock', true );

                        if ( ! $is_locked ) {
                            update_post_meta( $api_id, 'event_status', get_post_status( $event_id ) );
                            update_post_meta( $api_id, 'api_key_name', get_the_title( $event_id ) );
                        }
                    }
                }
            }
        }

        /**
         * Rename "Events" to the plugin title ("Tickera" by default)
         */
        function rename_events_menu_item() {

            global $menu, $tc;

            $menu_position = $tc->admin_menu_position;

            if ( $menu[ $menu_position ][ 2 ] = 'edit.php?post_type=tc_events' ) {
                $menu[ $menu_position ][ 0 ] = $tc->title;
            }
        }

        /**
         * Disable Tickera legacy menu
         *
         * @return bool
         */
        function tc_add_admin_menu_page() {
            return false;
        }

        /**
         * Change menu item handler to regular post type's
         *
         * @param $handler
         * @return string
         */
        function first_tc_menu_handler( $handler ) {
            $handler = 'edit.php?post_type=tc_events';
            return $handler;
        }

        /**
         * Enqueue scripts and styles
         */
        function admin_enqueue_scripts_and_styles() {

            global $post, $post_type;

            if ( 'tc_events' == $post_type ) {
                wp_enqueue_style( 'tc-better-events', plugins_url( 'css/admin.css', __FILE__ ) );
            }
        }

        function tc_settings_general_sections( $sections ) {

            $sections[] = [
                'name' => 'events_settings',
                'title' => __( 'Events Settings', 'tickera-event-ticketing-system' ),
                'description' => '',
            ];

            return $sections;
        }

        function tc_settings_gdpr_sections( $sections ) {

            $sections[] = [
                'name' => 'gdpr_settings',
                'title' => __( 'GDPR Settings', 'tickera-event-ticketing-system' ),
                'description' => '',
            ];

            return apply_filters( 'tc_settings_gdpr_sections', $sections );
        }

        /**
         * Adds additional field for Events slug under general settings > pages
         *
         * @param $pages_settings_default_fields
         * @return array
         */
        function tc_general_settings_page_fields( $pages_settings_default_fields ) {

            $pages_settings_default_fields[] = [
                'field_name' => 'tc_event_slug',
                'field_title' => __( 'Event slug', 'tickera-event-ticketing-system' ),
                'field_type' => 'texts',
                'default_value' => 'tc-events',
                'tooltip' => __( 'Enter the slug you want to use for your events. Please flush permalinks after changing this value.', 'tickera-event-ticketing-system' ),
                'section' => 'events_settings'
            ];

            $pages_settings_default_fields[] = [
                'field_name' => 'tc_event_category_slug',
                'field_title' => __( 'Event category slug', 'tickera-event-ticketing-system' ),
                'field_type' => 'texts',
                'default_value' => 'tc-event-category',
                'tooltip' => __( 'Enter the slug for the event categories. Please flush permalinks after changing this value.', 'tickera-event-ticketing-system' ),
                'section' => 'events_settings'
            ];

            $pages_settings_default_fields[] = [
                'field_name' => 'tc_attach_event_date_to_title',
                'field_title' => __( 'Display event date & time', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_yes_no',
                'default_value' => 'yes',
                'tooltip' => __( 'Include event date and time on the event page, below the event title', 'tickera-event-ticketing-system' ),
                'section' => 'events_settings'
            ];

            $pages_settings_default_fields[] = [
                'field_name' => 'tc_attach_event_location_to_title',
                'field_title' => __( 'Display event location', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_yes_no',
                'default_value' => 'yes',
                'tooltip' => __( 'Include the event location on the event page, below the event title', 'tickera-event-ticketing-system' ),
                'section' => 'events_settings'
            ];

            return $pages_settings_default_fields;
        }

        function tc_gdpr_settings_page_fields( $pages_settings_default_fields ) {

            $pages_settings_default_fields[] = [
                'field_name' => 'tc_gateway_collection_data',
                'field_title' => __( 'Add checkbox for agreement on payment gateway data collection', 'tickera-event-ticketing-system' ),
                'field_type' => 'function',
                'function' => 'tickera_yes_no',
                'default_value' => 'no',
                'section' => 'gdpr_settings'
            ];

            $pages_settings_default_fields[] = [
                'field_name' => 'tc_collection_data_text',
                'field_title' => __( 'Data collection text', 'tickera-event-ticketing-system' ),
                'field_type' => 'texts',
                'default_value' => 'In order to continue you need to agree to provide your details.',
                'section' => 'gdpr_settings'
            ];

            return $pages_settings_default_fields;
        }

        /**
         * Change Events post type arguments
         *
         * @param $args
         * @return mixed
         */
        function tc_events_post_type_args( $args ) {

            global $tc;

            $tc_general_settings = get_option( 'tickera_general_setting', false );
            $event_slug = isset( $tc_general_settings[ 'tc_event_slug' ] ) && ! empty( $tc_general_settings[ 'tc_event_slug' ] ) ? $tc_general_settings[ 'tc_event_slug' ] : 'tc-events';

            $args[ 'menu_position' ] = $tc->admin_menu_position;
            $args[ 'show_ui' ] = true;
            $args[ 'has_archive' ] = true;
            $args[ 'rewrite' ] = [
                'slug' => $event_slug,
                'with_front' => false
            ];

            $args[ 'supports' ] = [
                'title',
                'editor',
                'thumbnail',
            ];

            return $args;
        }

        /**
         * Add table column titles
         *
         * @param $columns
         * @return mixed
         */
        function manage_tc_events_columns( $columns ) {

            $events_columns = \Tickera\TC_Events::get_event_fields();

            foreach ( $events_columns as $events_column ) {

                if ( isset( $events_column[ 'table_visibility' ] ) && true == $events_column[ 'table_visibility' ] && $events_column[ 'field_name' ] !== 'post_title' ) {
                    $columns[ $events_column[ 'field_name' ] ] = $events_column[ 'field_title' ];
                }
            }

            unset( $columns[ 'date' ] );
            return $columns;
        }

        /**
         * Add table column values
         *
         * @param $name
         */
        function manage_tc_events_posts_custom_column( $name ) {

            global $post;

            $events_columns = \Tickera\TC_Events::get_event_fields();

            foreach ( $events_columns as $events_column ) {

                if ( isset( $events_column[ 'table_visibility' ] ) && true == $events_column[ 'table_visibility' ] && $events_column[ 'field_name' ] !== 'post_title' ) {

                    if ( $name == $events_column[ 'field_name' ] ) {

                        if ( isset( $events_column[ 'post_field_type' ] ) && 'post_meta' == $events_column[ 'post_field_type' ] ) {

                            if ( 'event_date_time' == $events_column[ 'field_name' ] || 'event_end_date_time' == $events_column[ 'field_name' ] ) {

                                $value = get_post_meta( $post->ID, $events_column[ 'field_name' ], true );
                                $start_date = date_i18n( get_option( 'date_format' ), strtotime( $value ) );
                                $start_time = date_i18n( get_option( 'time_format' ), strtotime( $value ) );
                                echo esc_html($start_date . ' ' . $start_time);

                            } else {
                                $value = get_post_meta( $post->ID, $events_column[ 'field_name' ], true );
                                $value = ! empty( $value ) ? $value : '-';
                                echo esc_html($value);
                            }

                        } elseif ( 'event_active' == $events_column[ 'field_name' ] ) {
                            $event_status = get_post_status( $post->ID );
                            $on = $event_status == 'publish' ? 'tc-on' : '';
                            echo wp_kses( '<div class="tc-control ' . esc_attr( $on ) . '" event_id="' . esc_attr( $post->ID ) . '"><div class="tc-toggle"></div></div>', wp_kses_allowed_html( 'tickera_toggle' ) );

                        } elseif ( 'event_shortcode' == $events_column[ 'field_name' ] ) {
                            echo esc_html( apply_filters( 'tc_event_shortcode_column', '[tc_event id="' . esc_attr( $post->ID ) . '"]', $post->ID ) );
                        }
                    }
                }
            }
        }

        function manage_edit_tc_events_sortable_columns( $columns ) {

            $custom = array(
                'event_location' => 'event_location',
                'event_date_time' => 'event_date_time',
                'event_end_date_time' => 'event_end_date_time',
            );

            return wp_parse_args( $custom, $columns );
        }

        /**
         * Add control for setting an event as active or inactive
         */
        function post_submitbox_misc_actions() {

            global $post, $post_type;

            if ( 'tc_events' == $post_type ) {

                $events_columns = \Tickera\TC_Events::get_event_fields();

                foreach ( $events_columns as $events_column ) {

                    if ( isset( $events_column[ 'show_in_post_type' ] ) && true == $events_column[ 'show_in_post_type' ] && isset( $events_column[ 'post_type_position' ] ) && 'publish_box' == $events_column[ 'post_type_position' ] ) { ?>
                        <div class="misc-pub-section <?php echo esc_attr( $events_column[ 'field_name' ] ); ?>">
                            <?php echo wp_kses( \Tickera\TC_Fields::render_post_type_field( '\Tickera\TC_Event', $events_column, $post->ID, true ), wp_kses_allowed_html( 'tickera' ) ); ?>
                        </div>
                    <?php }
                }

                $event_status = get_post_status( $post->ID );
                $on = ( $event_status == 'publish' ) ? 'tc-on' : '';

                //$show_tickets_automatically = (int) get_post_meta( $post->ID, 'show_tickets_automatically', true );
                //$hide_event_after_expiration = (int) get_post_meta( $post->ID, 'hide_event_after_expiration', true );
                ?>
                <!--<div class="misc-pub-section event_append_tickets" id="append_tickets">
                    <span id="post_event_append_tickets">
                        <input type="checkbox" id="show_tickets_automatically" name="show_tickets_automatically_post_meta" value="1" <?php //checked( $show_tickets_automatically, true, true ); ?> />
                        <label for="show_tickets_automatically">
                            <span></span>
                            <?php //_e( 'Show tickets automatically', 'tickera-event-ticketing-system' ); ?>
                        </label>
                    </span>
                </div>
                <div class="misc-pub-section event_append_tickets" id="append_tickets">
                    <span id="post_event_append_tickets">
                        <input type="checkbox" id="hide_event_after_expiration" name="hide_event_after_expiration_post_meta" value="1" <?php //checked( $hide_event_after_expiration, true, true ); ?> />
                        <label for="hide_event_after_expiration">
                            <span></span>
                            <?php //_e( 'Hide event after expiration', 'tickera-event-ticketing-system' ); ?>
                        </label>
                    </span>
                </div>-->
            <?php }
        }

        function non_visible_fields() {

            return [
                'event_shortcode',
                'event_date_time',
                'event_end_date_time',
                'post_title',
                'event_active',
                'event_presentation_page'
            ];
        }

        function add_events_metaboxes() {

            global $pagenow, $typenow, $post;

            if ( ( 'edit.php' == $pagenow ) || ( $post->post_type !== 'tc_events' ) ) {
                return;
            }

            $post_id = isset( $_GET[ 'post' ] ) ? (int) $_GET[ 'post' ] : 0;
            $events_columns = \Tickera\TC_Events::get_event_fields();

            foreach ( $events_columns as $events_column ) {

                if ( ! in_array( $events_column[ 'field_name' ], $this->non_visible_fields() ) ) {
                    $args = [
                        'post_id' => $post_id,
                        'field_name' => $events_column[ 'field_name' ]
                    ];

                    add_meta_box( $events_column[ 'field_name' ] . '-tc-metabox-wrapper', $events_column[ 'field_title' ] . ( ( isset( $events_column[ 'tooltip' ] ) && $events_column[ 'tooltip' ] ) ? wp_kses_post( tickera_tooltip( $events_column[ 'tooltip' ] ) ) : '' ), function () use ( $args ) {
                        tickera_render_metabox( $args[ 'post_id' ], $args[ 'field_name' ] );
                    }, 'tc_events' );
                }
            }
        }

        /**
         * Render fields by type (function, text, textarea, etc)
         *
         * @param $field
         * @param bool $show_title
         */
        public static function render_field( $field, $show_title = true ) {

            global $post;

            $event = new \Tickera\TC_Event( $post->ID );

            if ( $show_title ) { ?>
                <label><?php echo esc_html( $field[ 'field_title' ] ); ?>
            <?php }

            // Text
            if ( 'text' == $field[ 'field_type' ] ) { ?>
                <input type="text" class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?>" value="<?php
                if ( isset( $event ) ) {

                    if ( 'post_meta' == $field[ 'post_field_type' ] ) {
                        echo esc_attr( isset( $event->details->{$field[ 'field_name' ]} ) ? $event->details->{$field[ 'field_name' ]} : '' );

                    } else {
                        echo esc_attr( $event->details->{$field[ 'post_field_type' ]} );
                    }
                }
                ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>">
                <?php if ( isset( $field[ 'field_description' ] ) ) { ?>
                    <span class="description"><?php echo esc_html( $field[ 'field_description' ] ); ?></span>
                <?php }
            }

            if ( $show_title ) { ?>
                </label>
            <?php }
        }
    }

    global $better_events;
    $better_events = new TC_Better_Events();
}

/**
 * Deprecated function "tc_render_metabox".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'tickera_render_metabox' ) ) {

    function tickera_render_metabox( $post_id, $field_name ) {

        $events_columns = \Tickera\TC_Events::get_event_fields();

        foreach ( $events_columns as $events_column ) {

            if ( $events_column[ 'field_name' ] == $field_name ) {
                echo wp_kses( \Tickera\TC_Fields::render_post_type_field( '\Tickera\TC_Event', $events_column, $post_id, false ), wp_kses_allowed_html( 'tickera' ) );
            }
        }
    }
}
