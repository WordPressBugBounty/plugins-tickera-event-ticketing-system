<?php
/**
 * Better Orders
 * Better orders presentation for Tickera
 */

namespace Tickera\Addons;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\Addons\TC_Better_Orders' ) ) {

    class TC_Better_Orders {

        var $version = '1.0';
        var $title = 'Better Orders';
        var $name = 'better-orders';

        function __construct() {

            global $post;

            if ( ! isset( $post ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order screen post ID is cast and used only to detect the current post type.
                $post_id = isset( $_GET[ 'post' ] ) ? (int) $_GET[ 'post' ] : '';
                $post_type = get_post_type( $post_id );

            } else {
                $post_type = get_post_type( $post );
            }

            if ( empty( $post_type ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order screen post type is sanitized and used only for screen setup.
                $post_type = isset( $_GET[ 'post_type' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'post_type' ] ) ) : '';
            }

            add_action( 'wp_trash_post', array( $this, 'trash_post' ) );
            add_action( 'delete_post', array( $this, 'delete_post' ) );
            add_action( 'untrash_post', array( $this, 'untrash_post' ) );

            add_filter( 'manage_tc_orders_posts_columns', array( $this, 'manage_tc_orders_columns' ) );
            add_action( 'manage_tc_orders_posts_custom_column', array( $this, 'manage_tc_orders_posts_custom_column' ), 10, 2 );
            add_action( 'add_meta_boxes', array( $this, 'add_orders_metaboxes' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_and_styles' ) );

            if ( $post_type == 'tc_orders' ) {
                add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
            }

            add_action( 'save_post', array( $this, 'save_orders_meta' ) );
            add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
            add_action( 'pre_get_posts', array( $this, 'pre_get_posts_reorder' ) );

            add_filter( 'posts_join', array( $this, 'extended_search_join' ) );
            add_filter( 'posts_where', array( $this, 'extended_search_where' ) );
            add_filter( 'posts_groupby', array( $this, 'extended_groupby' ) );
            add_action( 'restrict_manage_posts', array( $this, 'add_events_filter' ) );
            add_action( 'pre_get_posts', array( $this, 'pre_get_posts_events_filter' ) );
            add_filter( 'bulk_actions-edit-tc_orders', array( $this, 'remove_edit_bulk_action' ) );
        }

        /**
         * Delete associated ticket instances
         *
         * @param $post_id
         */
        function trash_post( $post_id ) {

            if ( 'tc_orders' == get_post_type( (int) $post_id ) ) {

                $ticket_instances = get_posts( [
                    'posts_per_page' => -1,
                    'post_type' => 'tc_tickets_instances',
                    'post_status' => [ 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit' ],
                    'post_parent' => (int) $post_id
                ]);

                foreach ( $ticket_instances as $ticket_instance ) {
                    $ticket_instance_instance = new \Tickera\TC_Ticket_Instance( $ticket_instance->ID );
                    $ticket_instance_instance->delete_ticket_instance( false );
                }
            }
        }

        function delete_post( $post_id ) {

            if ( 'tc_orders' == get_post_type( (int) $post_id ) ) {

                // Delete associated ticket instances
                $ticket_instances = get_posts( [
                    'posts_per_page' => -1,
                    'post_type' => 'tc_tickets_instances',
                    'post_status' => [ 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ],
                    'post_parent' => (int) $post_id
                ]);

                foreach ( $ticket_instances as $ticket_instance ) {
                    $ticket_instance_instance = new \Tickera\TC_Ticket_Instance( $ticket_instance->ID );
                    $ticket_instance_instance->delete_ticket_instance( true );
                }
            }
        }

        function untrash_post( $post_id ) {

            if ( 'tc_orders' == get_post_type( (int) $post_id ) ) {

                $ticket_instances = get_posts( [
                    'posts_per_page' => -1,
                    'post_type' => 'tc_tickets_instances',
                    'post_status' => 'trash',
                    'post_parent' => (int) $post_id
                ]);

                foreach ( $ticket_instances as $ticket_instance ) {
                    wp_untrash_post( $ticket_instance->ID );
                    wp_update_post( [ 'ID' => $ticket_instance->ID, 'post_status' => 'publish' ] );
                }
            }
        }

        function remove_edit_bulk_action( $actions ) {
            unset( $actions[ 'edit' ] );
            return $actions;
        }

        function pre_get_posts_reorder( $query ) {
            global $post_type, $pagenow;
            if ( $pagenow == 'edit.php' && $post_type == 'tc_orders' ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order list sorting parameter is sanitized before updating the query.
                $query->set( 'orderby', isset( $_REQUEST[ 'orderby' ] ) ? sanitize_key( wp_unslash( $_REQUEST[ 'orderby' ] ) ) : 'date' );
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order list sort direction is sanitized before updating the query.
                $query->set( 'order', isset( $_REQUEST[ 'order' ] ) ? sanitize_key( wp_unslash( $_REQUEST[ 'order' ] ) ) : 'DESC' );
            }
            return $query;
        }

        function pre_get_posts_events_filter( $query ) {

            global $post_type, $pagenow;

            if ( $pagenow == 'edit.php' && $post_type == 'tc_orders' ) {

                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order event filter only scopes the order list query.
                if ( isset( $_REQUEST[ 'tc_event_filter' ] ) && $query->query[ 'post_type' ] == 'tc_orders' ) {

                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order event filter is cast before use.
                    $tc_tc_event_filter = (int) $_REQUEST[ 'tc_event_filter' ];

                    if ( $tc_tc_event_filter !== '0' ) {
                        add_filter( 'posts_where', array( $this, 'pre_get_posts_event_filter_where' ) );
                    }
                }
            }
            return $query;
        }

        function pre_get_posts_event_filter_where( $where ) {
            global $wpdb, $post_type, $pagenow;
            if ( $pagenow == 'edit.php' && $post_type == 'tc_orders' ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order event filter is sanitized before changing the list query.
                if ( isset( $_REQUEST[ 'tc_event_filter' ] ) && sanitize_text_field( wp_unslash( $_REQUEST[ 'tc_event_filter' ] ) ) != 0 ) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order event filter is cast before being added to the SQL condition.
                    $where .= " AND (" . $wpdb->postmeta . ".meta_key='tc_parent_event' AND " . $wpdb->postmeta . ".meta_value LIKE '%" . (int) $_REQUEST[ 'tc_event_filter' ] . "%')";
                }
            }
            return $where;
        }

        function add_events_filter() {
            global $post_type;
            if ( $post_type == 'tc_orders' ) {
                $wp_events_search = new \Tickera\TC_Events_Search( '', '', '-1' );
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order event filter is cast before selecting the current filter option.
                $currently_selected = isset( $_REQUEST[ 'tc_event_filter' ] ) ? (int) $_REQUEST[ 'tc_event_filter' ] : '';
                ?>
                <select name="tc_event_filter" class="tc-event-filter">
                    <option value="0"><?php esc_html_e( 'All Events', 'tickera-event-ticketing-system' ); ?></option>
                    <?php
                    foreach ( $wp_events_search->get_results() as $event ) {
                        $event_obj = new \Tickera\TC_Event( $event->ID );
                        $event_object = $event_obj->details;
                        ?>
                        <option value="<?php echo esc_attr( $event_object->ID ); ?>" <?php selected( $currently_selected, $event_object->ID, true ); ?>><?php echo esc_html( tickera_apply_filters( 'tickera_event_select_name', $event_object->post_title, $event_object->ID ) ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <?php
            }
        }

        function add_order_status_filter() {
            global $post_type;
            if ( $post_type == 'tc_orders' ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order status filter is sanitized before selecting the current filter option.
                $currently_selected = isset( $_REQUEST[ 'tc_order_status_filter' ] ) ? sanitize_key( wp_unslash( $_REQUEST[ 'tc_order_status_filter' ] ) ) : '';
                ?>
                <select name="tc_order_status_filter">
                    <option value="0"><?php esc_html_e( 'All Order Statuses', 'tickera-event-ticketing-system' ); ?></option>
                    <?php
                    $payment_statuses = tickera_apply_filters( 'tickera_csv_payment_statuses', tickera_get_order_statuses() );
                    $payment_statuses[ 'order_received' ] = __( 'Order Pending / Received', 'tickera-event-ticketing-system' );

                    foreach ( $payment_statuses as $payment_status_key => $payment_status_value ) { ?>
                        <option value="<?php echo esc_attr( $payment_status_key ); ?>" <?php selected( $currently_selected, $payment_status_key, true ); ?>><?php echo esc_attr( $payment_status_value ); ?></option>
                    <?php } ?>
                </select>
                <?php
            }
        }

        /**
         * Save Order Meta
         *
         * @param $post_id
         * @throws \Exception
         */
        function save_orders_meta( $post_id ) {

            $order_id = $post_id;

            // Make sure the edit comes from the order details page
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin order status field is handled by the order details save workflow.
            if ( ! isset( $_POST[ 'order_status_change' ] ) || get_post_type( $post_id ) !== 'tc_orders' ) {
                return;
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin order status is sanitized before updating the order.
            $post_status = sanitize_key( wp_unslash( $_POST[ 'order_status_change' ] ) );
            $order = new \Tickera\TC_Order( $order_id );

            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Original admin order status is sanitized before transition handling.
            $old_post_status = isset( $_POST[ 'original_post_status' ] ) ? sanitize_key( wp_unslash( $_POST[ 'original_post_status' ] ) ) : 'pending';

            if ( 'trash' == $post_status ) {
                $order->delete_order( false );

            } else {

                // Un-trash attendees & tickets
                if ( 'trash' == $old_post_status ) {
                    $order->untrash_order();
                }

                switch ( $post_status ) {

                    case 'order_cancelled':
                        $current_user = wp_get_current_user();

                        \Tickera\TC_Order::add_order_note( $order_id, sprintf(
                            /* translators: %s: Current logged in user name. */
                            __( 'Order cancelled by %s', 'tickera-event-ticketing-system' ),
                            $current_user->user_login
                        ) );

                        tickera_do_action( 'tickera_order_cancelled', $order_id, $old_post_status, $post_status );
                        break;

                    case 'order_refunded':
                        $current_user = wp_get_current_user();

                        \Tickera\TC_Order::add_order_note( $order_id, sprintf(
                            /* translators: %s: Current logged in user name. */
                            __( 'Order refunded by %s', 'tickera-event-ticketing-system' ),
                            $current_user->user_login
                        ) );

                        tickera_do_action( 'tickera_order_refunded', $order_id, $old_post_status, $post_status );
                        break;

                }

                remove_action( 'save_post', array( $this, 'save_orders_meta' ) );
                wp_update_post( [ 'ID' => (int) $order_id, 'post_status' => $post_status ] );
                add_action( 'save_post', array( $this, 'save_orders_meta' ) );
            }

            /**
             * Make sure that order status wasn't order_paid after the update
             * (so we don't send out duplicate confirmation e-mails)
             */

            if ( 'order_paid' == $post_status && $post_status !== $old_post_status ) {

                tickera_order_created_email( $order->details->post_name, $post_status, false, false, false, true );
                $payment_info = get_post_meta( $order_id, 'tc_payment_info', true );
                tickera_do_action( 'tickera_order_paid_change', $order_id, $post_status, '', '', $payment_info );

            } elseif ( 'order_refunded' == $post_status && $post_status !== $old_post_status ) {
                tickera_order_created_email( $order->details->post_name, $post_status );
            }

            // Update buyer e-mail
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin buyer email field is handled by the order details save workflow.
            if ( isset( $_POST[ 'customer_email' ] ) ) {
                $cart_info = get_post_meta( $post_id, 'tc_cart_info', true );
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin buyer email is sanitized before saving order cart data.
                $cart_info[ 'buyer_data' ][ 'email_post_meta' ] = sanitize_text_field( wp_unslash( $_POST[ 'customer_email' ] ) );
                update_post_meta( (int) $post_id, 'tc_cart_info', tickera_sanitize_array( $cart_info, false, true ) );
            }

            // Update buyer name
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin buyer name fields are handled by the order details save workflow.
            if ( isset( $_POST[ 'customer_first_name' ] ) && isset( $_POST[ 'customer_last_name' ] ) ) {
                $cart_info = get_post_meta( $post_id, 'tc_cart_info', true );
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin buyer first name is sanitized before saving order cart data.
                $cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] = sanitize_text_field( wp_unslash( $_POST[ 'customer_first_name' ] ) );
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Admin buyer last name is sanitized before saving order cart data.
                $cart_info[ 'buyer_data' ][ 'last_name_post_meta' ] = sanitize_text_field( wp_unslash( $_POST[ 'customer_last_name' ] ) );
                update_post_meta( (int) $post_id, 'tc_cart_info', tickera_sanitize_array( $cart_info, false, true ) );
            }
        }

        function extended_search_join( $join ) {
            global $pagenow, $wpdb;
            $joined = false;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order post type check only scopes extended search query changes.
            if ( is_admin() && $pagenow == 'edit.php' && isset( $_GET[ 'post_type' ] ) && sanitize_text_field( wp_unslash( $_GET[ 'post_type' ] ) ) == 'tc_orders' ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order event filter only determines whether the list query needs a meta join.
                if ( ( ( isset( $_REQUEST[ 'tc_event_filter' ] ) && sanitize_text_field( wp_unslash( $_REQUEST[ 'tc_event_filter' ] ) ) != 0 ) ) ) {
                    $joined = true;
                    $join .= ' LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
                }
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order search term only determines whether the list query needs a meta join.
                if ( ( isset( $_REQUEST[ 's' ] ) && sanitize_text_field( wp_unslash( $_REQUEST[ 's' ] ) ) != '' ) ) {
                    if ( ! $joined ) {
                        $join .= ' LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id';
                    }
                }
            }

            return $join;
        }

        function extended_search_where( $where ) {
            global $pagenow, $wpdb;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order search parameters only scope extended list-table search conditions.
            if ( is_admin() && $pagenow == 'edit.php' && isset( $_GET[ 'post_type' ] ) && sanitize_text_field( wp_unslash( $_GET[ 'post_type' ] ) ) == 'tc_orders' && isset( $_REQUEST[ 's' ] ) && sanitize_text_field( wp_unslash( $_REQUEST[ 's' ] ) ) != '' ) {
                $where = preg_replace(
                    "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/", "(" . $wpdb->posts . ".post_title LIKE $1) OR (" . $wpdb->postmeta . ".meta_value LIKE $1)", $where );
            }
            return $where;
        }

        function extended_groupby( $groupby ) {
            global $pagenow, $wpdb;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order search parameters only scope extended list-table grouping.
            if ( is_admin() && $pagenow == 'edit.php' && isset( $_GET[ 'post_type' ] ) && sanitize_text_field( wp_unslash( $_GET[ 'post_type' ] ) ) == 'tc_orders' && isset( $_REQUEST[ 's' ] ) && sanitize_text_field( wp_unslash( $_REQUEST[ 's' ] ) ) != '' ) {
                global $wpdb;
                $groupby = "{$wpdb->posts}.ID";
            }
            return $groupby;
        }

        function post_updated_messages( $messages ) {

            $post = get_post();
            $post_type = get_post_type( $post );
            $post_type_object = get_post_type_object( $post_type );

            $messages[ 'tc_orders' ] = array(
                0 => '', // Unused. Messages start at index 1.
                1 => __( 'Order updated.', 'tickera-event-ticketing-system' ),
                2 => __( 'Custom field updated.', 'tickera-event-ticketing-system' ),
                3 => __( 'Custom field deleted.', 'tickera-event-ticketing-system' ),
                4 => __( 'Order updated.', 'tickera-event-ticketing-system' ),
                /* translators: %s: date and time of the revision */
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin revision ID is cast and used only for the order updated message.
                5 => isset( $_GET[ 'revision' ] )
                    ? sprintf(
                        /* translators: %s: Formatted datetime timestamp of a revision. */
                        __( 'Order data restored to revision from %s', 'tickera-event-ticketing-system' ),
                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin revision ID is cast before loading the revision title.
                        wp_post_revision_title( (int) $_GET[ 'revision' ], false )
                    )
                    : false,
                6 => __( 'Order data published.', 'tickera-event-ticketing-system' ),
                7 => __( 'Order data saved.', 'tickera-event-ticketing-system' ),
                8 => __( 'Order data submitted.', 'tickera-event-ticketing-system' ),
                9 => sprintf(
                    /* translators: 1: Formatted datetime timestamp of a scheduled order. */
                    __( 'Order scheduled for: <strong>%1$s</strong>.', 'tickera-event-ticketing-system' ),
                    date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) )
                ),
                10 => __( 'Order draft updated.', 'tickera-event-ticketing-system' ),
            );
            return $messages;
        }

        function post_row_actions( $actions, $post ) {
            unset( $actions[ 'inline hide-if-no-js' ] );
            return $actions;
        }

        /**
         * Enqueue scripts and styles
         */
        function admin_enqueue_scripts_and_styles() {
            global $tc, $post, $post_type;
            if ( $post_type == 'tc_orders' ) {
                wp_enqueue_style( 'tc-orders', plugins_url( 'css/admin.css', __FILE__ ), [], $tc->version );
            }
        }

        /**
         * Add table column titles
         *
         * @param $columns
         * @return mixed
         */
        function manage_tc_orders_columns( $columns ) {

            $tickets_orders_columns = \Tickera\TC_Orders::get_order_fields();
            foreach ( $tickets_orders_columns as $tickets_orders_column ) {
                if ( isset( $tickets_orders_column[ 'table_visibility' ] ) && $tickets_orders_column[ 'table_visibility' ] == true && $tickets_orders_column[ 'field_name' ] !== 'post_title' ) {
                    $columns[ isset( $tickets_orders_column[ 'id' ] ) ? $tickets_orders_column[ 'id' ] : $tickets_orders_column[ 'field_name' ] ] = $tickets_orders_column[ 'field_title' ];
                }
            }
            unset( $columns[ 'date' ] );
            unset( $columns[ 'title' ] );
            return $columns;
        }

        /**
         * Add table column values
         *
         * @param $name
         * @param $post_id
         */
        function manage_tc_orders_posts_custom_column( $name, $post_id ) {
            global $post, $tc;
            $tickets_orders_columns = \Tickera\TC_Orders::get_order_fields();

            foreach ( $tickets_orders_columns as $tickets_orders_column ) {
                if ( isset( $tickets_orders_column[ 'table_visibility' ] ) && $tickets_orders_column[ 'table_visibility' ] == true && $tickets_orders_column[ 'field_name' ] !== 'post_title' ) {

                    $id = isset( $tickets_orders_column[ 'id' ] ) ? $tickets_orders_column[ 'id' ] : '';

                    if ( $tickets_orders_column[ 'field_name' ] == $name || ( $id == $name ) ) {

                        $post_field_type = \Tickera\TC_Orders::check_field_property( $tickets_orders_column[ 'field_name' ], 'post_field_type' );
                        $field_id = isset( $tickets_orders_column[ 'id' ] ) ? $tickets_orders_column[ 'id' ] : $tickets_orders_column[ 'field_name' ];
                        $field_name = $tickets_orders_column[ 'field_name' ];

                        $order_obj = new \Tickera\TC_Order( $post_id );
                        $order_object = tickera_apply_filters( 'tickera_order_object_details', $order_obj->details );

                        if ( isset( $post_field_type ) && $post_field_type == 'post_meta' ) {

                            if ( isset( $field_id ) ) {
                                echo wp_kses_post( tickera_apply_filters( 'tickera_order_field_value', $order_object->ID, $order_object->{$field_name}, $post_field_type, isset( $tickets_orders_column[ 'field_id' ] ) ? $tickets_orders_column[ 'field_id' ] : '', $field_id ) );

                            } else {
                                echo wp_kses_post( tickera_apply_filters( 'tickera_order_field_value', $order_object->ID, $order_object->{$field_name}, $post_field_type, $tickets_orders_column[ 'field_id' ] ) );
                            }

                        } else {

                            if ( isset( $field_id ) ) {
                                echo wp_kses_post( tickera_apply_filters( 'tickera_order_field_value', $order_object->ID, ( isset( $order_object->{$post_field_type} ) ? $order_object->{$post_field_type} : $order_object->{$field_name} ), $post_field_type, $tickets_orders_column[ 'field_name' ], $field_id ) );

                            } else {
                                echo wp_kses_post( tickera_apply_filters( 'tickera_order_field_value', $order_object->ID, ( isset( $order_object->{$post_field_type} ) ? $order_object->{$post_field_type} : $order_object->{$field_name} ), $post_field_type, $tickets_orders_column[ 'field_name' ] ) );
                            }
                        }
                    }
                }
            }
        }

        function add_orders_metaboxes() {
            global $pagenow, $typenow, $post;
            add_meta_box( 'order-details-tc-metabox-wrapper', __( 'Order Details', 'tickera-event-ticketing-system' ), '\Tickera\Addons\tickera_order_details_metabox', 'tc_orders', 'normal' );
        }

    }

    if ( tickera_apply_filters( 'tickera_bridge_for_woocommerce_is_active', false ) == true ) {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $woo is the prefix
        $woo_bridge_is_active = true;

    } else {

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        if ( is_plugin_active( 'bridge-for-woocommerce/bridge-for-woocommerce.php' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $woo is the prefix
            $woo_bridge_is_active = true;

        } else {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $woo is the prefix
            $woo_bridge_is_active = false;
        }
    }

    // Make sure not to load the add-on if Bridge for WooCommerce is active
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $woo is the prefix
    if ( ! $woo_bridge_is_active ) {
        new TC_Better_Orders();
    }
}

/**
 * Render Order Details under Order Page ( e.g. Subtotal, Discount, Fee, Tax, Total )
 *
 * Deprecated function "tc_order_details_metabox".
 * @since 3.5.3.0
 */
if ( ! function_exists( '\Tickera\Addons\tickera_order_details_metabox' ) ) {

        function tickera_order_details_metabox() {
            $orders = new \Tickera\TC_Orders();
            $fields = \Tickera\TC_Orders::get_order_fields();
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin order ID is cast before loading order details for the metabox.
            $order = new \Tickera\TC_Order( isset( $_REQUEST[ 'post' ] ) ? (int) $_REQUEST[ 'post' ] : 0 );
        ?>
        <form name="tc_order_details" method="post">
            <input type='hidden' id='order_id' value='<?php echo esc_attr( $order->details->ID ); ?>'/>
            <input type="hidden" name="hiddenField"/>
            <?php tickera_do_action( 'tickera_order_details_before_table' ); ?>
            <table class="order-table">
                <tbody>
                <?php foreach ( $fields as $field ) { ?>
                    <?php if ( $orders->is_valid_order_field_type( $field[ 'field_type' ] ) ) { ?>
                        <tr valign="top">
                        <?php if ( $field[ 'field_type' ] !== 'separator' ) { ?>
                            <th scope="row"><label for="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"><?php echo esc_html( $field[ 'field_title' ] ); ?></label></th>
                        <?php } ?>
                        <td <?php echo wp_kses_post( ( $field[ 'field_type' ] == 'separator' ) ? 'colspan = "2"' : '' ); ?>>
                            <?php

                            tickera_do_action( 'tickera_before_orders_field_type_check' );

                            if ( 'ID' == $field[ 'field_type' ] ) {
                                echo esc_html( $order->details->{$field[ 'post_field_type' ]} );

                            } elseif ( 'function' == $field[ 'field_type' ] ) {

                                $array_of_arguments = [ $field[ 'field_name' ] ];

                                if ( isset( $order->details->ID ) ) {
                                    $array_of_arguments[] = $order->details->ID;
                                }

                                if ( isset( $field[ 'id' ] ) ) {
                                    $array_of_arguments[] = $field[ 'id' ];
                                }

                                call_user_func_array( $field[ 'function' ], $array_of_arguments );

                            } elseif ( 'text' == $field[ 'field_type' ] ) {

                                $value = '';

                                if ( isset( $order ) ) {
                                    if ( $field[ 'post_field_type' ] == 'post_meta' ) {
                                        $value = isset( $order->details->{$field[ 'field_name' ]} ) ? $order->details->{$field[ 'field_name' ]} : '';

                                    } else {
                                        $value = $order->details->{$field[ 'post_field_type' ]};
                                    }
                                }
                                ?>
                                <input type="text" class="regular-<?php echo esc_attr( $field[ 'field_type' ] ); ?>" value="<?php echo esc_attr( $value ); ?>" id="<?php echo esc_attr( $field[ 'field_name' ] ); ?>" name="<?php echo esc_attr( $field[ 'field_name' ] . '_' . $field[ 'post_field_type' ] ); ?>"><?php
                            }

                            if ( $field[ 'field_type' ] == 'separator' ) : ?>
                                <hr/>
                            <?php endif;
                            tickera_do_action( 'tickera_after_orders_field_type_check' ); ?>
                        </td>
                        </tr><?php
                    }
                }
                tickera_do_action( 'tickera_after_order_details_fields' ); ?>
                </tbody>
            </table>
            <?php submit_button( __( 'Save Changes', 'tickera-event-ticketing-system' ), 'primary', 'tc_order_save_changes', false ); ?>
            <?php tickera_do_action( 'tickera_order_details_after_table' ); ?>
        </form>
        <?php
    }
}
