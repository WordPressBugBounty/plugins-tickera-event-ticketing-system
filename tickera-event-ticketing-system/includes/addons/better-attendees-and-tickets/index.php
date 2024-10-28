<?php
/**
 * Better Attendees and Tickets
 * Better attendees and tickets presentation for Tickera
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Addons\TC_Better_Attendees_and_Tickets' ) ) {

    class TC_Better_Attendees_and_Tickets {

        var $version = '1.0';
        var $title = 'Better Attendees and Tickets';
        var $name = 'better-attendees-and-tickets';
        var $checkin_eligible_order_statuses = [];

        function __construct() {

            $general_settings = get_option( 'tickera_general_setting' );

            /*
             * Hide order statuses that are ineligible for checkins.
             * Option can be found in General > Miscellaneous.
             */
            $hide_checkin_ineligible = ( isset( $general_settings[ 'hide_checkin_ineligible_tickets' ] ) && $general_settings[ 'hide_checkin_ineligible_tickets' ] ) ? $general_settings[ 'hide_checkin_ineligible_tickets' ] : 'no';
            $this->checkin_eligible_order_statuses = ( 'no' == $hide_checkin_ineligible )
                ? apply_filters( 'tc_checkin_eligible_order_statuses', tickera_get_order_statuses() )
                : apply_filters( 'tc_checkin_eligible_order_statuses', [ 'order_paid' => ( tickera_get_order_statuses() )[ 'order_paid' ] ] );

            add_filter( 'tc_tickets_instances_post_type_args', array( $this, 'tc_tickets_instances_post_type_args' ) );
            add_filter( 'manage_tc_tickets_instances_posts_columns', array( $this, 'manage_tc_tickets_instances_columns' ) );
            add_action( 'manage_tc_tickets_instances_posts_custom_column', array( $this, 'manage_tc_tickets_instances_posts_custom_column' ), 10, 2 );
            add_filter( 'manage_edit-tc_tickets_instances_sortable_columns', array( $this, 'manage_tc_tickets_instances_sortable_columns' ) );
            add_action( 'add_meta_boxes', array( $this, 'add_tickets_instances_metaboxes' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_and_styles' ) );
            add_filter( 'bulk_actions-edit-tc_tickets_instances', array( $this, 'remove_edit_bulk_action' ) );
            add_action( 'save_post', array( $this, 'save_tickets_instances_meta' ), 10, 3 );
            add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
            add_action( 'wp_untrash_post_status', array( $this, 'untrash_post_status' ), 10, 2 );

            if ( is_admin() ) {

                add_filter( 'page_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
                add_action( 'pre_get_posts', array( $this, 'init_table_by_order_status' ) );

                add_filter( 'posts_join', array( $this, 'extended_search_join' ) );
                add_filter( 'posts_where', array( $this, 'extended_search_where' ) );

                add_action( 'restrict_manage_posts', array( $this, 'add_events_filter' ) );
                add_action( 'restrict_manage_posts', array( $this, 'add_order_status_filter' ) );

                add_action( 'pre_get_posts', array( $this, 'pre_get_posts_reorder' ) );
                add_action( 'pre_get_posts', array( $this, 'pre_get_posts_events_filter' ) );
                add_action( 'pre_get_posts', array( $this, 'pre_get_posts_order_status_filter' ) );

                add_filter( 'posts_request', array( $this, 'posts_request' ) );
            }

            add_action( 'wp_ajax_search_event_filter', array( &$this, 'search_event_filter' ) );
        }

        function search_event_filter() {

            if ( $_POST && isset( $_POST[ 's' ] ) && isset( $_POST[ 'nonce' ] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'tc_ajax_nonce' ) ) {

                $keyword = sanitize_text_field( $_POST[ 's' ] );
                $count = 0;
                $options = '<option value="0">' . esc_html__( 'All Events', 'tickera-event-ticketing-system' ) . '</option>';

                $events_search = new \Tickera\TC_Events_Search( $keyword, '', '', 'any', 'post_title', 'DESC', [ 'post_title' ] );
                foreach ( $events_search->get_results() as $event ) {
                    $options .= '<option value="' . (int) $event->ID . '">' . esc_html( $event->post_title . ' [#' . $event->ID . ']' ) . '</option>';
                    $count++;
                }

                wp_send_json( [
                    'count' => $count,
                    'options_html' => $options,
                ] );
            }
        }

        /**
         * Remove Edit Bulk Action
         * @param $actions
         * @return mixed
         */
        function remove_edit_bulk_action( $actions ) {
            unset( $actions[ 'edit' ] );
            return $actions;
        }

        /**
         * Initialize Data Table Sorting
         * @param $query
         * @return mixed
         */
        function pre_get_posts_reorder( $query ) {
            global $post_type, $pagenow;
            if ( 'edit.php' == $pagenow && 'tc_tickets_instances' == $post_type ) {
                $order_by = ( $_GET && isset( $_GET[ 'orderby' ] ) ) ? sanitize_key( $_GET[ 'orderby' ] ) : 'date';
                $order = ( $_GET && isset( $_GET[ 'order' ] ) ) ? sanitize_key( $_GET[ 'order' ] ) : 'DESC';
                $query->set( 'orderby', $order_by );
                $query->set( 'order', $order );
            }
        }

        /**
         * Logical Conditions for Custom Events Filter
         * @param $query
         * @return mixed
         */
        function pre_get_posts_events_filter( $query ) {

            global $post_type, $pagenow;

            if ( 'edit.php' == $pagenow && ( 'tc_tickets_instances' == $post_type || 'tc_tickets' == $post_type ) ) {

                if ( isset( $_REQUEST[ 'tc_event_filter' ] ) && ( 'tc_tickets_instances' == $query->query[ 'post_type' ] || 'tc_tickets' == $query->query[ 'post_type' ] ) ) {

                    if ( $_REQUEST[ 'tc_event_filter' ] !== '0' ) {
                        add_filter( 'posts_where', array( $this, 'pre_get_posts_events_filter_where' ) );
                    }
                }
            }
        }

        /**
         * Extended Logical Conditions for Events Filter
         * @param $where
         * @param $query
         * @return string
         */
        function pre_get_posts_events_filter_where( $where ) {

            global $wpdb, $post_type;

            if ( 'tc_tickets' == $post_type ) {
                $meta_key = 'event_name';
                $where .= " AND " . $wpdb->posts . ".ID IN (SELECT post_id FROM " . $wpdb->postmeta . " WHERE " . $wpdb->postmeta . ".meta_key = '$meta_key' AND " . $wpdb->postmeta . ".meta_value = " . sanitize_text_field( $_REQUEST[ 'tc_event_filter' ] ) . ")";

            } elseif ( 'tc_tickets_instances' == $post_type ) {
                $meta_key = 'event_id';
                $where .= " AND " . $wpdb->posts . ".ID IN (SELECT post_id FROM " . $wpdb->postmeta . " WHERE " . $wpdb->postmeta . ".meta_key = '$meta_key' AND " . $wpdb->postmeta . ".meta_value = " . sanitize_text_field( $_REQUEST[ 'tc_event_filter' ] ) . ")";
            }

            return $where;
        }

        /**
         * Logical Conditions for Order Status Filter
         * @param $query
         * @return mixed
         */
        function pre_get_posts_order_status_filter( $query ) {
            global $post_type, $pagenow;
            if ( 'edit.php' == $pagenow && 'tc_tickets_instances' == $post_type && 'tc_tickets_instances' == $query->query[ 'post_type' ]
                && isset( $_REQUEST[ 'tc_order_status_filter' ] ) && $_REQUEST[ 'tc_order_status_filter' ] !== '0' ) {
                add_filter( 'posts_where', array( $this, 'pre_get_posts_order_status_filter_where' ), 10, 2 );
            }
        }

        /**
         * Extended Logical Conditions for Order Status Filter
         * @param $where
         * @return string
         */
        function pre_get_posts_order_status_filter_where( $where ) {
            global $wpdb;
            $where .= " AND " . $wpdb->posts . ".post_parent IN (SELECT " . $wpdb->posts . ".ID FROM " . $wpdb->posts . " WHERE " . $wpdb->posts . ".post_status = '" . sanitize_text_field( $_REQUEST[ 'tc_order_status_filter' ] ) . "')";
            return $where;
        }

        /**
         * Initialize attendees table base from checkin eligible order statuses.
         * @param $query
         */
        function init_table_by_order_status( $query ) {
            global $post_type, $pagenow;
            if ( 'edit.php' == $pagenow && 'tc_tickets_instances' == $post_type && 'tc_tickets_instances' == $query->query[ 'post_type' ] ) {
                add_filter( 'posts_where', array( $this, 'init_table_by_order_status_where_clause' ), 10, 2 );
            }
        }

        /**
         * Extended Logical Conditions to show/hide Checkin eligible tickets.
         * Checkin eligible base from order statuses.
         * @param $where
         * @param $query
         * @return string
         */
        function init_table_by_order_status_where_clause( $where, $query ) {
            global $wpdb;
            $order_statuses = apply_filters( 'tc_tickets_instances_init_table_by_order_statuses', array_keys( $this->checkin_eligible_order_statuses ) );
            $order_statuses = implode( '\',\'', $order_statuses );
            $where .= " AND " . $wpdb->posts . ".post_parent IN (SELECT " . $wpdb->posts . ".ID FROM " . $wpdb->posts . " WHERE " . $wpdb->posts . ".post_status IN ('trash','" . $order_statuses . "'))";
            return $where;
        }

        /**
         * Generate an array of Events
         * @return array
         */
        function events_filter_list() {
            $tc_events_search = new \Tickera\TC_Events_Search( '', '', '-1' );

            $tc_events_filter = [];
            foreach ( array_values( $tc_events_search->get_results() ) as $event ) {
                $tc_events_filter[ $event->ID ] = $event->post_title;
            }

            return $tc_events_filter;
        }

        /**
         * Custom Events Filter
         */
        function add_events_filter() {

            global $post_type;

            if ( 'tc_tickets_instances' == $post_type || 'tc_tickets' == $post_type ) : ?>

                <?php
                $tc_events_search = new \Tickera\TC_Events_Search( '', '', '10' );
                $init_event_filter_options = apply_filters( 'tc_init_event_filter_options', $tc_events_search->get_results() );
                $currently_selected = isset( $_REQUEST[ 'tc_event_filter' ] ) ? (int) $_REQUEST[ 'tc_event_filter' ] : '';
                $selected_event = $currently_selected ? get_post( $currently_selected ) : [];

                if ( $init_event_filter_options && $selected_event ) {
                    $selected_event = ( false !== array_search( $selected_event->ID, wp_list_pluck( $init_event_filter_options, 'ID' ) ) ) ? $init_event_filter_options : array_merge( [ $selected_event ], $init_event_filter_options );

                } elseif ( ! $init_event_filter_options && $selected_event ) {
                    $selected_event = [ $selected_event ];
                }

                $events_search = $selected_event ? $selected_event : $init_event_filter_options;
                ?>
                <select name="tc_event_filter">
                    <option value="0"><?php esc_html_e( 'All Events', 'tickera-event-ticketing-system' ); ?></option>
                    <?php foreach ( $events_search as $event ) :
                        $event_id = (int) $event->ID; ?>
                        <option value="<?php echo esc_attr( (int) $event_id ); ?>" <?php selected( $currently_selected, $event_id, true ); ?>><?php echo esc_html( apply_filters( 'tc_event_select_name', $event->post_title . ' [#' . $event->ID . ']', $event_id ) ); ?></option>
                    <?php endforeach; ?>
                </select>

            <?php endif; ?>
            <?php // Open PHP for succeeding source codes
        }

        /**
         * Custom Order Status Filter
         */
        function add_order_status_filter() {

            global $post_type;

            if ( 'tc_tickets_instances' == $post_type ) {
                $currently_selected = isset( $_REQUEST[ 'tc_order_status_filter' ] ) ? sanitize_text_field( $_REQUEST[ 'tc_order_status_filter' ] ) : ''; ?>
                <select name="tc_order_status_filter">
                    <option value="0"><?php esc_html_e( 'All Order Statuses', 'tickera-event-ticketing-system' ); ?></option>
                    <?php foreach ( $this->checkin_eligible_order_statuses as $order_status => $order_status_label ) { ?>
                        <option value="<?php echo esc_attr( $order_status ); ?>" <?php selected( $currently_selected, $order_status, true ); ?>><?php echo esc_html( $order_status_label ); ?></option>
                    <?php } ?>
                </select>
                <?php
            }
        }

        /**
         * @param $post_id
         * @param $post
         * @param $update
         */
        function save_tickets_instances_meta( $post_id, $post, $update ) {

            if ( isset( $post->post_type ) && 'tc_tickets_instances' == $post->post_type && isset( $_POST[ 'api_key' ] ) ) {

                $ticket_instance = new \Tickera\TC_Ticket_Instance( (int) $post_id );
                $ticket_type = new \Tickera\TC_Ticket( $ticket_instance->details->ticket_type_id );
                $ticket_event_id = $ticket_type->get_ticket_event( $ticket_instance->details->ticket_type_id );

                $api_key = new \Tickera\TC_API_Key( sanitize_text_field( $_POST[ 'api_key' ] ) );
                $checkin = new \Tickera\TC_Checkin_API( $api_key->details->api_key, apply_filters( 'tc_checkin_request_name', 'tickera_scan' ), 'return', $ticket_instance->details->ticket_code, false );
                $checkin_result = $checkin->ticket_checkin( false );

                if ( isset( $checkin_result[ 'status' ] ) && 1 == $checkin_result[ 'status' ] ) {
                    $message_type = 'updated';
                    $message = __( 'Ticket checked in successfully.', 'tickera-event-ticketing-system' );

                } else {

                    if ( 11 == $checkin_result ) {
                        tickera_redirect( 'post.php?post=' . $post_id . '&action=edit&message=11' );

                    } else if ( 403 == $checkin_result ) {
                        tickera_redirect( 'post.php?post=' . $post_id . '&action=edit&message=403' );

                    } else {
                        tickera_redirect( 'post.php?post=' . $post_id . '&action=edit&message=11' );
                    }
                }
            }
        }

        function posts_request( $query ) {

            global $post_type, $pagenow, $wpdb;

            if ( 'edit.php' == $pagenow && ( 'tc_tickets_instances' == $post_type || ( isset( $_GET[ 'post_type' ] ) && 'tc_tickets_instances' == $_GET[ 'post_type' ] ) ) && isset( $_REQUEST[ 's' ] ) && isset( $_REQUEST[ 's' ] ) && $_REQUEST[ 's' ] ) {

                if ( apply_filters( 'tc_tickets_instances_extensive_search', true ) ) {

                    // Remove extra spaces
                    $query = preg_replace( '/\s+/', ' ', $query );

                    // Insert posts table alias
                    $query = preg_replace(
                        "/FROM " . $wpdb->posts . " LEFT/",
                        "FROM {$wpdb->posts} as p LEFT",
                        $query
                    );

                    $query = preg_replace(
                        "/FROM " . $wpdb->posts . " INNER/",
                        "FROM {$wpdb->posts} as p INNER",
                        $query
                    );

                    $query = str_replace( $wpdb->posts . '.ID,', 'p.ID,', $query );
                    $query = str_replace( $wpdb->posts . '.ID =', 'p.ID =', $query );
                    $query = str_replace( $wpdb->posts . '.ID FROM ' . $wpdb->posts . ' as p' , 'p.ID FROM ' . $wpdb->posts . ' as p', $query );
                    $query = str_replace( 'GROUP BY ' . $wpdb->posts . '.ID', 'GROUP BY p.ID ', $query );
                    $query = str_replace( 'AND ' . $wpdb->posts . '.ID IN', 'AND p.ID IN', $query );

                    $query = str_replace( $wpdb->posts . '.post_parent', 'p.post_parent', $query );
                    $query = str_replace( $wpdb->posts . '.post_date', 'p.post_date', $query );
                    $query = str_replace( $wpdb->posts . '.*', 'p.*', $query );

                } else {
                    return $query;

                }
            }

            return $query;
        }

        /**
         * Extends Search Filter with Table Posts
         * @param $join
         * @return string
         */
        function extended_search_join( $join ) {
            global $post_type, $pagenow, $wpdb;
            if ( 'edit.php' == $pagenow && ( 'tc_tickets_instances' == $post_type || ( isset( $_GET[ 'post_type' ] ) && 'tc_tickets_instances' == $_GET[ 'post_type' ] ) ) && isset( $_REQUEST[ 's' ] ) && $_REQUEST[ 's' ] ) {

                $join = rtrim( $join, ' ' );

                if ( apply_filters( 'tc_tickets_instances_extensive_search', true ) ) {
                    $join .= ' LEFT JOIN ' . $wpdb->postmeta . ' as pm ON p.ID = pm.post_id ';

                } else {
                    $join .= ' LEFT JOIN ' . $wpdb->postmeta . ' as pm4 ON ' . $wpdb->posts . '.ID = ' . 'pm4.post_id ';
                }
            }

            return $join;
        }

        /**
         * Extends Search Filter with Custom WHERE Clause
         * @param $where
         * @return string|string[]|null
         */
        function extended_search_where( $where ) {

            global $post_type, $pagenow, $wpdb;

            if ( 'edit.php' == $pagenow && ( 'tc_tickets_instances' == $post_type || ( isset( $_GET[ 'post_type' ] ) && 'tc_tickets_instances' == $_GET[ 'post_type' ] ) ) && isset( $_REQUEST[ 's' ] ) && $_REQUEST[ 's' ] ) {

                $search_filter = isset( $_GET[ 's' ] ) ? strtolower( sanitize_text_field( $_GET[ 's' ] ) ) : '';

                $meta_keys = apply_filters( 'tc_tickets_instances_extended_search_meta_keys', [
                    'first_name',
                    'last_name',
                    'owner_email',
                    'ticket_code',
                    'tc_cart_info',
                ]);

                $meta_keys = "'" . implode( "','", $meta_keys ) . "'";

                if ( apply_filters( 'tc_tickets_instances_extensive_search', true ) ) {

                    $where = preg_replace(
                        "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                        "
                        (p.post_parent LIKE '%" . $search_filter . "%')
                        OR
                        (SELECT p4.post_title FROM {$wpdb->posts} as p4 WHERE p4.ID=p.post_parent) LIKE '%" . $search_filter ."%'
                        OR 
                        (SELECT LOWER( GROUP_CONCAT(pm2.meta_value SEPARATOR ' ') ) FROM {$wpdb->postmeta} as pm2 WHERE pm2.post_id=p.ID AND pm2.meta_key IN (" . $meta_keys . ")) LIKE '%" . $search_filter . "%'
                        OR
                        (SELECT LOWER( GROUP_CONCAT(pm3.meta_value SEPARATOR ' ') ) FROM {$wpdb->postmeta} as pm3 WHERE pm3.post_id=p.post_parent AND pm3.meta_key IN (" . $meta_keys . ")) LIKE '%" . $search_filter . "%'
                        ",
                        $where
                    );

                    $where = preg_replace(
                        "/\(\s*" . $wpdb->posts . ".post_excerpt\s+LIKE\s*(\'[^\']+\')\s*\)/",
                        "(p.post_excerpt LIKE '%" . $search_filter . "%')",
                        $where
                    );

                    $where = preg_replace(
                        "/\(\s*" . $wpdb->posts . ".post_content\s+LIKE\s*(\'[^\']+\')\s*\)/",
                        "(p.post_content LIKE '%" . $search_filter . "%')",
                        $where
                    );

                    $where = str_replace(  $wpdb->posts . '.', 'p.', $where );
                    $where = str_replace(  $wpdb->postmeta . '.', 'pm.', $where );
                    $where .= " AND pm.meta_key='ticket_code'";

                } else {

                    $search_filters = preg_replace( '/\s+/', ' ', $search_filter );
                    $search_filters = explode( ' ', strtolower( $search_filters ) );

                    $where = preg_replace(
                        "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                        "($wpdb->posts.post_parent LIKE '%" . $search_filter . "%' AND pm4.meta_key = 'ticket_code') OR ((pm4.meta_value LIKE '%" . $search_filter . "%' OR LOWER(pm4.meta_value) IN ('" . implode( "','", $search_filters ) . "')) AND pm4.meta_key IN (" . $meta_keys . "))", $where
                    );

                    $where = preg_replace(
                        "/\(\s*" . $wpdb->posts . ".post_excerpt\s+LIKE\s*(\'[^\']+\')\s*\)/",
                        "$wpdb->posts.post_excerpt LIKE '%" . $search_filter . "%' AND pm4.meta_key = 'ticket_code'",
                        $where
                    );

                    $where = preg_replace(
                        "/\(\s*" . $wpdb->posts . ".post_content\s+LIKE\s*(\'[^\']+\')\s*\)/",
                        "$wpdb->posts.post_content LIKE '%" . $search_filter . "%' AND pm4.meta_key = 'ticket_code'",
                        $where
                    );
                }
            }

            return $where;
        }

        /**
         * @param $messages
         * @return mixed
         */
        function post_updated_messages( $messages ) {

            $post = get_post();
            $post_type = get_post_type( $post );
            $post_type_object = get_post_type_object( $post_type );

            $messages[ 'tc_tickets_instances' ] = array(
                0 => '', // Unused. Messages start at index 1.
                1 => __( 'Attendee updated.', 'tickera-event-ticketing-system' ),
                2 => __( 'Custom field updated.', 'tickera-event-ticketing-system' ),
                3 => __( 'Custom field deleted.', 'tickera-event-ticketing-system' ),
                4 => __( 'Check-in records updated.', 'tickera-event-ticketing-system' ),
                /* translators: %s: date and time of the revision */
                5 => isset( $_GET[ 'revision' ] )
                    ? sprintf(
                        /* translators: %s: Formatted datetime timestamp of a revision. */
                        __( 'Attendee data restored to revision from %s', 'tickera-event-ticketing-system' ),
                        wp_post_revision_title( (int) $_GET[ 'revision' ], false )
                    )
                    : false,
            6 => __( 'Attendee data published.', 'tickera-event-ticketing-system' ),
            7 => __( 'Attendee data saved.', 'tickera-event-ticketing-system' ),
            8 => __( 'Attendee data submitted.', 'tickera-event-ticketing-system' ),
            9 => sprintf(
                /* translators: 1: Formatted datetime timestamp of a scheduled ticket instance. */
                __( 'Attendee data scheduled for: <strong>%1$s</strong>.', 'tickera-event-ticketing-system' ),
                date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) )
            ),
            10 => __( 'Attendee data draft updated.', 'tickera-event-ticketing-system' ),
            11 => __( 'Ticket is invalid or expired', 'tickera-event-ticketing-system' ),
            403 => __( 'Insufficient permissions. This API key cannot check in this ticket.', 'tickera-event-ticketing-system' )
        );
        return $messages;
    }

    /**
     * Publish tickets once untrashed.
     *
     * @param $new_status
     * @param $post_id
     * @return string
     */
        function untrash_post_status( $new_status, $post_id ) {
            if ( 'tc_tickets_instances' == get_post_type( $post_id ) ) {
                return 'publish';
            }
        }

        /**
         * @param $actions
         * @param $post
         * @return mixed
         */
        function post_row_actions( $actions, $post ) {

            global $post_type;

            if ( 'tc_tickets_instances' == $post_type ) {
                unset( $actions[ 'view' ] );
                unset( $actions[ 'edit' ] );
                unset( $actions[ 'inline hide-if-no-js' ] );
            }

            return $actions;
        }

        /**
         * Enqueue scripts and styles
         */
        function admin_enqueue_scripts_and_styles() {
            global $post_type;
            if ( 'tc_tickets_instances' == $post_type ) {
                wp_enqueue_style( 'tc-better-attendees-and-tickets', plugins_url( 'css/admin.css', __FILE__ ) );
            }
        }

        /**
         * Change tickets instances post type arguments
         * @param $args
         * @return mixed|void
         */
        function tc_tickets_instances_post_type_args( $args ) {
            $args[ 'show_in_menu' ] = 'edit.php?post_type=tc_events';
            $args[ 'show_ui' ] = true;
            $args[ 'has_archive' ] = false;
            $args[ 'public' ] = false;
            $args[ 'supports' ] = [ 'title' ];
            return apply_filters( 'tc_tickets_instances_post_type_args_val', $args );
        }

        /**
         * Add table column titles
         * @param $columns
         * @return mixed
         */
        function manage_tc_tickets_instances_columns( $columns ) {

            $tickets_instances_columns = \Tickera\TC_Tickets_Instances::get_tickets_instances_fields();

            foreach ( $tickets_instances_columns as $tickets_instances_column ) {
                if ( isset( $tickets_instances_column[ 'table_visibility' ] ) && $tickets_instances_column[ 'table_visibility' ] == true && $tickets_instances_column[ 'field_name' ] !== 'post_title' ) {
                    $columns[ $tickets_instances_column[ 'field_name' ] ] = $tickets_instances_column[ 'field_title' ];
                }
            }

            unset( $columns[ 'date' ] );
            unset( $columns[ 'title' ] );

            return $columns;
        }

        /**
         * Add table column values
         * @param $name
         * @param $post_id
         */
        function manage_tc_tickets_instances_posts_custom_column( $name, $post_id ) {

            $tickets_instances_columns = \Tickera\TC_Tickets_Instances::get_tickets_instances_fields();

            foreach ( $tickets_instances_columns as $tickets_instances_column ) {

                if ( isset( $tickets_instances_column[ 'table_visibility' ] ) && true == $tickets_instances_column[ 'table_visibility' ] && $tickets_instances_column[ 'field_name' ] !== 'post_title' ) {

                    if ( $name == $tickets_instances_column[ 'field_name' ] ) {

                        $post_field_type = \Tickera\TC_Tickets_Instances::check_field_property( $tickets_instances_column[ 'field_name' ], 'post_field_type' );
                        $field_id = $tickets_instances_column[ 'id' ];
                        $field_name = $tickets_instances_column[ 'field_name' ];

                        $ticket_instance_obj = new \Tickera\TC_Ticket_Instance( $post_id );
                        $ticket_instance_object = apply_filters( 'tc_ticket_instance_object_details', $ticket_instance_obj->details );

                        if ( isset( $post_field_type ) && 'post_meta' == $post_field_type ) {

                            if ( isset( $field_id ) ) {
                                echo wp_kses_post( apply_filters( 'tc_ticket_instance_field_value', $ticket_instance_object->ID, $ticket_instance_object->{$field_name}, $post_field_type, ( isset( $tickets_instances_column[ 'field_id' ] ) ? $tickets_instances_column[ 'field_id' ] : '' ), $field_id ) );

                            } else {
                                echo wp_kses_post( apply_filters( 'tc_ticket_instance_field_value', $ticket_instance_object->ID, $ticket_instance_object->{$field_name}, $post_field_type, ( isset( $tickets_instances_column[ 'field_id' ] ) ? $tickets_instances_column[ 'field_id' ] : '' ) ) );
                            }

                        } elseif ( isset( $post_field_type ) && 'post_status' == $post_field_type ) {

                            $order_status = get_post( $ticket_instance_object->post_parent );

                            if ( isset( $order_status->post_status ) && ! empty( $order_status->post_status ) ) {

                                if ( strrpos( $order_status->post_status, '_' ) ) {
                                    $new_value = str_replace( '_', ' ', $order_status->post_status );

                                } else {
                                    $new_value = str_replace( '-', ' ', $order_status->post_status );
                                }

                                $tc_post_status_color = array(
                                    'order_fraud' => 'tc_order_fraud',
                                    'order_received' => 'tc_order_received',
                                    'order_paid' => 'tc_order_paid',
                                    'order_cancelled' => 'tc_order_cancelled',
                                    'order_refunded' => 'tc_order_fraud',
                                    'wc-cancelled' => 'tc_order_cancelled',
                                    'wc-completed' => 'tc_order_paid',
                                    'wc-processing' => 'tc_order_received',
                                    'wc-pending' => 'tc_order_received',
                                    'wc-on-hold' => 'tc_order_hold',
                                    'wc-refunded' => 'tc_order_fraud',
                                    'wc-failed' => 'tc_order_fraud'
                                );

                                $color = isset( $tc_post_status_color[ $order_status->post_status ] ) ? $tc_post_status_color[ $order_status->post_status ] : 'tc_order_received';
                                echo wp_kses_post( sprintf(
                                    /* translators: 1: Order status color identifier 2: Order status name */
                                    __( '<span class="%1$s">%2$s</span>', 'tickera-event-ticketing-system' ),
                                    esc_attr( $color ),
                                    esc_html( ucwords( $new_value ) )
                                ) );
                            }

                        } else {

                            if ( isset( $field_id ) ) {
                                echo wp_kses_post( apply_filters( 'tc_ticket_instance_field_value', $ticket_instance_object->ID, ( isset( $ticket_instance_object->{$post_field_type} ) ? $ticket_instance_object->{$post_field_type} : $ticket_instance_object->{$field_name} ), $post_field_type, $tickets_instances_column[ 'field_name' ], $field_id ) );

                            } else {
                                echo wp_kses_post( apply_filters( 'tc_ticket_instance_field_value', $ticket_instance_object->ID, ( isset( $ticket_instance_object->{$post_field_type} ) ? $ticket_instance_object->{$post_field_type} : $ticket_instance_object->{$field_name} ), $post_field_type, $tickets_instances_column[ 'field_name' ] ) );
                            }
                        }
                    }
                }
            }
        }

        /**
         * Sortable table columns
         *
         * @param $columns
         * @return array
         *
         * @since 3.5.1.5
         */
        function manage_tc_tickets_instances_sortable_columns( $columns ) {

            $sortable_columns = [];
            $excluded_fields = [ 'date', 'title', 'ticket_links', 'ID' ];
            $tickets_instances_columns = \Tickera\TC_Tickets_Instances::get_tickets_instances_fields();

            foreach ( $tickets_instances_columns as $tickets_instances_column ) {
                if ( isset( $tickets_instances_column[ 'table_visibility' ] ) && $tickets_instances_column[ 'table_visibility' ] == true && $tickets_instances_column[ 'field_name' ] !== 'post_title' && !in_array( $tickets_instances_column[ 'field_name' ], $excluded_fields ) ) {
                    $sortable_columns[ $tickets_instances_column[ 'field_name' ] ] = $tickets_instances_column[ 'field_name' ];
                }
            }

            return $sortable_columns;
        }

        /**
         * Metabox for Check Ins
         */
        function add_tickets_instances_metaboxes() {
            add_meta_box( 'attendees-checkin-details-tc-metabox-wrapper', __( 'Check-in List', 'tickera-event-ticketing-system' ), '\Tickera\Addons\tickera_attendees_check_in_details_metabox', 'tc_tickets_instances', 'normal' );
        }
    }

    global $TC_Better_Attendees_and_Tickets;
    $TC_Better_Attendees_and_Tickets = new TC_Better_Attendees_and_Tickets();
}

/**
 * Generate Template with Logical Conditions for Check Ins Metabox
 *
 * Deprecated function "tc_attendees_check_in_details_metabox".
 * @since 3.5.3.0
 */
if ( ! function_exists( 'Tickera\Addons\tickera_attendees_check_in_details_metabox' ) ) {

    function tickera_attendees_check_in_details_metabox() {

        $ticket_instance = new \Tickera\TC_Ticket_Instance( (int) $_GET[ 'post' ] );
        $ticket_type = new \Tickera\TC_Ticket( $ticket_instance->details->ticket_type_id );
        $ticket_event_id = $ticket_type->get_ticket_event( $ticket_instance->details->ticket_type_id );
        $ticket_checkins = $ticket_instance->get_ticket_checkins();
        $ticket_checkouts = $ticket_instance->get_ticket_checkouts();

        if ( isset( $_GET[ 'checkin_action' ] ) && 'delete_checkin' == $_GET[ 'checkin_action' ] && check_admin_referer( 'delete_checkin' ) && ! isset( $_POST[ 'api_key' ] ) ) {

            $entry_to_delete = sanitize_text_field( $_GET[ 'checkin_entry' ] );
            $checkin_row = 0;

            if ( $ticket_checkins ) {

                // Remove an entry from the checkin object
                foreach ( $ticket_checkins as $ticket_key => $ticket_checkin ) {
                    if ( $ticket_checkin[ 'date_checked' ] == $entry_to_delete ) {
                        unset( $ticket_checkins[ $ticket_key ] );
                    }
                    $checkin_row++;
                }

                // Remove an entry from the check-out object
                foreach ( (array) $ticket_checkouts as $ticket_key => $ticket_checkout ) {
                    if ( isset( $ticket_checkout[ 'ref_checked_in' ] ) && $ticket_checkout[ 'ref_checked_in' ] == $entry_to_delete ) {
                        unset( $ticket_checkouts[ $ticket_key ] );
                    }
                }

                update_post_meta( $ticket_instance->details->ID, 'tc_checkins', tickera_sanitize_array( $ticket_checkins, false, true ) );
                update_post_meta( $ticket_instance->details->ID, 'tc_checkouts', tickera_sanitize_array( $ticket_checkouts, false, true ) );

                do_action( 'tc_check_in_deleted', $ticket_instance->details->ID, $ticket_checkins );
                $message_type = 'updated';
                $message = __( 'Check-in record deleted successfully.', 'tickera-event-ticketing-system' );
            }
        }

        $ticket_checkins = $ticket_instance->get_ticket_checkins(); ?>

        <?php if ( isset( $message ) ) { ?>
            <div id="message" class="<?php echo esc_attr( $message_type ); ?> fade"><p><?php echo esc_html( $message ); ?></p></div>
        <?php } ?>

        <table class="checkins-table widefat shadow-table">
            <thead>
            <tr valign="top">
                <th><?php esc_html_e( 'Date & Time', 'tickera-event-ticketing-system' ); ?></th>
                <th><?php esc_html_e( 'Status', 'tickera-event-ticketing-system' ); ?></th>
                <th><?php esc_html_e( 'API Key', 'tickera-event-ticketing-system' ); ?></th>
                <?php if ( current_user_can( 'manage_options' ) || current_user_can( 'delete_tickets_cap' ) ) { ?>
                    <th><?php esc_html_e( 'Delete', 'tickera-event-ticketing-system' ); ?></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php
            if ( $ticket_checkins ) {
                arsort( $ticket_checkins );
                foreach ( $ticket_checkins as $ticket_checkin ) { ?>
                    <tr class="alternate">
                    <td><?php echo esc_html( tickera_format_date( $ticket_checkin[ 'date_checked' ] ) ); ?></td>
                    <td><?php echo wp_kses_post( apply_filters( 'tc_checkins_status', $ticket_checkin[ 'status' ] ) ); ?></td>
                    <td><?php echo wp_kses_post( apply_filters( 'tc_checkins_api_key_id', $ticket_checkin[ 'api_key_id' ] ) ); ?></td>
                    <?php if ( current_user_can( 'manage_options' ) || current_user_can( 'delete_checkins_cap' ) ) { ?>
                        <td><?php
                            echo wp_kses_post( sprintf(
                                /* translators: %s: Delete checkin url. */
                                __( '<a class="tc_delete_link" href="%s">Delete</a>', 'tickera-event-ticketing-system' ),
                                esc_url( wp_nonce_url( admin_url( 'post.php?post=' . (int) $_GET[ 'post' ] . '&action=edit&checkin_action=delete_checkin&checkin_entry=' . (int) $ticket_checkin[ 'date_checked' ] ), 'delete_checkin' ) )
                            ) );
                            ?></td>
                    <?php } ?>
                    </tr><?php
                }

            } else { ?>
                <tr>
                <td colspan="4"><?php esc_html_e( "There are no any check-ins for this ticket yet.", "tickera-event-ticketing-system" ); ?></td>
                </tr><?php
            } ?>
            </tbody>
        </table>

        <?php
        $current_user = wp_get_current_user();
        $current_user_name = $current_user->user_login;
        $staff_api_keys_num = 0;
        $has_api_records = false;

        $wp_api_keys_search_all_result = [];
        $wp_api_keys_search_result = [];
        $wp_api_keys_search_all = new \Tickera\TC_API_Keys_Search( '', '', '', -1 );

        /*
         * Identify event ids from serialize event_name meta_values.
         * Collect all event api keys if user can manage option
         */
        if ( ( current_user_can( 'manage_options' ) ) ) {

            foreach ( $wp_api_keys_search_all->get_results() as $api_key ) {

                $event_ids = get_post_meta( $api_key->ID, 'event_name', true );

                if ( is_array( $event_ids ) ) {

                    if ( is_array( reset( $event_ids ) ) ) {

                        // Nested Arrays
                        if ( in_array( 'all', reset( $event_ids ) ) ) {
                            $has_api_records = true;
                            $wp_api_keys_search_all_result[] = $api_key;
                        }

                    } else {

                        // Regular Array
                        if ( in_array( 'all', $event_ids ) ) {
                            $has_api_records = true;
                            $wp_api_keys_search_all_result[] = $api_key;
                        }
                    }

                } elseif ( 'all' == $event_ids ) {

                    // String
                    $has_api_records = true;
                    $wp_api_keys_search_all_result[] = $api_key;
                }
            }
        }

        /*
         * Identify event ids from serialize event_name meta_values.
         * Filtered by ticket's event ID
         */
        foreach ( $wp_api_keys_search_all->get_results() as $api_key ) {

            $event_ids = get_post_meta( $api_key->ID, 'event_name', true );

            if ( is_array( $event_ids ) ) {

                if ( is_array( reset( $event_ids ) ) ) {

                    // Nested Arrays
                    if ( in_array( $ticket_event_id, reset( $event_ids ) ) ) {
                        $has_api_records = true;
                        $wp_api_keys_search_result[] = $api_key;
                    }

                } else {

                    // Regular Array
                    if ( in_array( $ticket_event_id, $event_ids ) ) {
                        $has_api_records = true;
                        $wp_api_keys_search_result[] = $api_key;
                    }
                }

            } elseif ( $ticket_event_id == $event_ids ) {

                // String
                $has_api_records = true;
                $wp_api_keys_search_result[] = $api_key;
            }
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            foreach ( $wp_api_keys_search_result as $api_key ) {
                $api_key_obj = new \Tickera\TC_API_Key( $api_key->ID );
                if ( ( $api_key_obj->details->api_username == $current_user_name ) ) {
                    $staff_api_keys_num++;
                }
            }
        }

        if ( $has_api_records && ( current_user_can( 'manage_options' ) || ( ! current_user_can( 'manage_options' ) && $staff_api_keys_num > 0 ) ) ) { ?>
            <form action="" method="post" enctype="multipart/form-data">
                <table class="checkin-table">
                    <tbody>
                    <tr valign="top">
                        <th scope="row"><label for="api_key"><?php esc_html_e( 'API Key', 'tickera-event-ticketing-system' ) ?></label></th>
                        <td>
                            <select name="api_key">
                                <?php foreach ( $wp_api_keys_search_result as $api_key ) {
                                    $api_key_obj = new \Tickera\TC_API_Key( $api_key->ID );
                                    if ( current_user_can( 'manage_options' ) || ( $api_key_obj->details->api_username == $current_user_name ) ) { ?>
                                        <option value="<?php echo esc_attr( $api_key->ID ); ?>"><?php echo esc_html( $api_key_obj->details->api_key_name ); ?></option><?php
                                    }
                                }
                                if ( current_user_can( 'manage_options' ) ) {
                                    foreach ( $wp_api_keys_search_all_result as $api_key ) {
                                        $api_key_obj = new \Tickera\TC_API_Key( $api_key->ID );
                                        if ( current_user_can( 'manage_options' ) || ( $api_key_obj->details->api_username == $current_user_name ) ) { ?>
                                            <option value="<?php echo esc_attr( $api_key->ID ); ?>"><?php echo esc_html( $api_key_obj->details->api_key_name ); ?></option><?php
                                        }
                                    }
                                } ?>
                            </select>
                            <input type="submit" name="check_in_ticket" id="check_in_ticket" class="button button-primary" value="<?php esc_html_e( 'Check In', 'tickera-event-ticketing-system' ); ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
        <?php }
    }
}
