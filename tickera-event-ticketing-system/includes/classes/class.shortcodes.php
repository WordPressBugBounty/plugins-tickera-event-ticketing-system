<?php
/**
 * Shortcodes
 */

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Shortcodes' ) ) {

    class TC_Shortcodes extends TC {

        /**
         * Register Shortcodes
         *
         * TC_Shortcodes constructor.
         */
        function __construct() {

            add_shortcode( 'tc_cart', array( $this, 'tc_cart_page' ) );
            add_shortcode( 'tc_additional_fields', array( $this, 'tc_additional_fields' ) );
            add_shortcode( 'tc_additional_buyer_fields', array( $this, 'tc_additional_buyer_fields' ) );
            add_shortcode( 'tc_additional_owner_fields', array( $this, 'tc_additional_owner_fields' ) );
            add_shortcode( 'tc_additional_fields_edd', array( $this, 'tc_additional_fields_edd' ) );
            add_shortcode( 'tc_process_payment', array( $this, 'tc_process_payment_page' ) );
            add_shortcode( 'tc_ipn', array( $this, 'tc_ipn_page' ) );
            add_shortcode( 'tc_order_history', array( $this, 'tc_order_history_page' ) );
            add_shortcode( 'tc_payment', array( $this, 'tc_payment_page' ) );
            add_shortcode( 'tc_order_confirmation', array( &$this, 'tc_order_confirmation_page' ) );
            add_shortcode( 'tc_order_details', array( $this, 'tc_order_details_page' ) );
            add_shortcode( 'ticket', array( $this, 'ticket_cart_button' ) );
            add_shortcode( 'tc_ticket', array( $this, 'ticket_cart_button' ) );
            add_shortcode( 'ticket_price', array( $this, 'ticket_price' ) );
            add_shortcode( 'tc_ticket_price', array( $this, 'ticket_price' ) );
            add_shortcode( 'tickets_sold', array( $this, 'tickets_sold' ) );
            add_shortcode( 'tickets_left', array( $this, 'tickets_left' ) );
            add_shortcode( 'event', array( $this, 'event' ) );
            add_shortcode( 'tc_event', array( $this, 'event' ) );
            add_shortcode( 'event_tickets_sold', array( $this, 'event_tickets_sold' ) );
            add_shortcode( 'event_tickets_left', array( $this, 'event_tickets_left' ) );
            add_shortcode( 'tc_event_date', array( $this, 'event_date' ) );
            add_shortcode( 'tc_event_location', array( $this, 'event_location' ) );
            add_shortcode( 'tc_event_terms', array( $this, 'event_terms' ) );
            add_shortcode( 'tc_event_sponsors_logo', array( $this, 'event_sponsors_logo' ) );
            add_shortcode( 'tc_event_logo', array( $this, 'event_logo' ) );
        }

        /**
         * Render Event's Tickets Table Shortcode
         * @param $atts
         * @return false|string
         *
         * Exclude Ticket seat type from the table
         * @since 3.5.1.8
         */
        function event( $atts ) {

            ob_start();
            global $tc, $post;

            extract( shortcode_atts( array(
                'id' => false,
                'event_table_class' => 'event_tickets tickera',
                'display_type' => 'table',
                'show_event_title' => false,
                'ticket_type_title' => __( 'Ticket Type', 'tickera-event-ticketing-system' ),
                'show_price' => false,
                'price_title' => __( 'Price', 'tickera-event-ticketing-system' ),
                'cart_title' => __( 'Cart', 'tickera-event-ticketing-system' ),
                'soldout_message' => __( 'Tickets are sold out.', 'tickera-event-ticketing-system' ),
                'quantity_title' => __( 'Qty.', 'tickera-event-ticketing-system' ),
                'quantity' => false,
                'type' => 'cart',
                'open_method' => 'regular',
                'title' => __( 'Add to Cart', 'tickera-event-ticketing-system' ),
                'wrapper' => '' ), $atts ) );

            $id = ( empty( $id ) || ! $id ) ? (int) $post->ID : (int) $id;

            $event = new \Tickera\TC_Event( $id );
            $event_tickets = $event->get_event_ticket_types( 'publish', false, true, false );

            if ( count( $event_tickets ) > 0 ) {

                if ( $event->details->post_status == 'publish' ) : ?>
                    <div class="tickera">
                        <?php if ( 'table' == $display_type ) : ?>
                            <div class="tc-event-table-wrap">
                                <table class="<?php echo esc_attr( $event_table_class ); ?>">
                                    <tr>
                                        <?php do_action( 'tc_event_col_title_before_ticket_title' ); ?>
                                        <th><?php echo esc_html( $ticket_type_title ); ?></th>
                                        <?php do_action( 'tc_event_col_title_before_ticket_price' ); ?>
                                        <th><?php echo esc_html( $price_title ); ?></th>
                                        <?php if ( $quantity ) { ?>
                                            <?php do_action( 'tc_event_col_title_before_quantity' ); ?>
                                            <th><?php echo esc_html( $quantity_title ); ?></th>
                                        <?php } ?>
                                        <?php do_action( 'tc_event_col_title_before_cart_title' ); ?>
                                        <th><?php echo esc_html( $cart_title ); ?></th>
                                    </tr>
                                    <?php
                                    foreach ( $event_tickets as $event_ticket_id ) {
                                        $event_ticket = new \Tickera\TC_Ticket( (int) $event_ticket_id );
                                        if ( \Tickera\TC_Ticket::is_sales_available( (int) $event_ticket_id ) ) : ?>
                                            <tr>
                                            <?php do_action( 'tc_event_col_value_before_ticket_type', (int) $event_ticket_id ); ?>
                                            <td data-column="<?php esc_attr_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?>"><?php echo esc_html( apply_filters( 'tc_tickets_table_title', $event_ticket->details->post_title, $event_ticket_id ) ); ?></td>
                                            <?php do_action( 'tc_event_col_value_before_ticket_price', (int) $event_ticket_id ); ?>
                                            <td data-column="<?php esc_attr_e( 'Price', 'tickera-event-ticketing-system' ); ?>"><?php
                                                echo esc_html( do_shortcode( sprintf(
                                                    /* translators: %d: Ticket type ID */
                                                    '[ticket_price id="%d"]',
                                                    (int) $event_ticket->details->ID
                                                ) ) );
                                            ?></td>
                                            <?php if ( $quantity ) { ?>
                                                <?php do_action( 'tc_event_col_value_before_quantity', (int) $event_ticket_id ); ?>
                                                <td data-column="<?php esc_attr_e( 'Quantity', 'tickera-event-ticketing-system' ); ?>"><?php
                                                    echo wp_kses( tickera_quantity_selector( (int) $event_ticket->details->ID, true ), wp_kses_allowed_html( 'tickera_quantity_selector' ) );
                                                ?></td>
                                            <?php } ?>
                                            <?php do_action( 'tc_event_col_value_before_cart_title', (int) $event_ticket_id ); ?>
                                            <td data-column="<?php esc_attr_e( 'Cart', 'tickera-event-ticketing-system' ); ?>"><?php
                                                echo wp_kses(
                                                        do_shortcode( sprintf(
                                                            /* translators: 1: Ticket type ID 2: Add to cart button action type (Cart or Buy Now) 3: Add to cart label 4: Sold out message 5: Open method */
                                                            '[ticket id="%1$d" type="%2$s" title="%3$s" soldout_message="%4$s" open_method="%5$s"]',
                                                            (int) $event_ticket->details->ID,
                                                            sanitize_text_field( $type ),
                                                            sanitize_text_field( $title ),
                                                            sanitize_text_field( $soldout_message ),
                                                            sanitize_text_field( $open_method )
                                                        ) ),
                                                        wp_kses_allowed_html( 'tickera_add_to_cart' )
                                                    );
                                            ?></td>
                                            </tr><?php
                                        endif;
                                    }
                                    ?>
                                </table>
                            </div><!-- .tc-event-table-wrap -->
                        <?php else :
                            $sales_available = [];
                            ?>
                            <div class="tc-event-dropdown-wrap">
                                <?php if ( $show_event_title ) : ?>
                                    <h3><?php echo esc_html( $event->details->post_title ); ?></h3>
                                <?php endif; ?>
                                <div class="inner-wrap">
                                    <select class="ticket-type-id">
                                        <?php foreach ( $event_tickets as $event_ticket_id ) {
                                            $event_ticket = new \Tickera\TC_Ticket( (int) $event_ticket_id );
                                            if ( \Tickera\TC_Ticket::is_sales_available( (int) $event_ticket_id ) ) :
                                                $sales_available[] = (int) $event_ticket_id;
                                                ?>
                                                <option value="<?php echo esc_attr( (int) $event_ticket_id ); ?>"><?php echo esc_html( $event_ticket->details->post_title ) . ( ( $show_price ) ? ' - ' . esc_html( do_shortcode( '[ticket_price id="' . (int) $event_ticket->details->ID . '"]' ) ) : '' ); ?></option>
                                            <?php endif;
                                        } ?>
                                    </select>
                                    <div class="actions">
                                        <?php foreach ( $event_tickets as $event_ticket_id ) {
                                            if ( in_array( $event_ticket_id, $sales_available ) ) {
                                                echo wp_kses( '<div class="add-to-cart" id="ticket-type-' . (int) $event_ticket_id .'">' . wp_kses( do_shortcode( '[ticket id="' . $event_ticket_id . '" type="' . sanitize_text_field( $type ) . '" title="' . sanitize_text_field( $title ) . '" soldout_message="' . sanitize_text_field( $soldout_message ) . '" open_method="' . sanitize_text_field( $open_method ) . '" quantity="' . $quantity . '"]' ), wp_kses_allowed_html( 'tickera_add_to_cart' ) ) . '</div>', wp_kses_allowed_html( 'tickera' ) );
                                            }
                                        } ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div><!-- tickera -->
                    <?php
                    return ob_get_clean();
                endif;
            }
        }

        /**
         * Render a link that will allow the customer to add ticket and its availability
         *
         * @param $attributes
         * @return string|void
         */
        function ticket_cart_button( $attributes ) {
            return TC_Shortcodes::render_ticket_cart_button( $attributes );
        }

        /**
         * Render the ticket_cart_button shortcode and attributes.
         *
         * @param $attributes
         * @return string|void
         *
         * @since 3.5.4.6
         */
        public static function render_ticket_cart_button( $attributes ) {

            global $tc;
            $tc_general_settings = get_option( 'tickera_general_setting', false );

            $id = isset( $attributes[ 'id' ] ) ? (int) $attributes[ 'id' ] : false;
            $title = isset( $attributes[ 'title' ] ) ? sanitize_text_field( $attributes[ 'title' ] ) : __( 'Add to Cart', 'tickera-event-ticketing-system' );
            $show_price = isset( $attributes[ 'show_price' ] ) ? (bool) $attributes[ 'show_price' ] : false;
            $price_position = isset( $attributes[ 'price_position' ] ) ? sanitize_text_field( $attributes[ 'price_position' ] ) : 'after';
            $price_wrapper = isset( $attributes[ 'price_wrapper' ] ) ? sanitize_text_field( $attributes[ 'price_wrapper' ] ) : 'span';
            $price_wrapper_class = isset( $attributes[ 'price_wrapper_class' ] ) ? sanitize_text_field( $attributes[ 'price_wrapper_class' ] ) : 'price';
            $soldout_message = isset( $attributes[ 'soldout_message' ] ) ? sanitize_text_field( $attributes[ 'soldout_message' ] ) : __( 'Tickets are sold out.', 'tickera-event-ticketing-system' );
            $type = isset( $attributes[ 'type' ] ) ? sanitize_text_field( $attributes[ 'type' ] ) : 'cart';
            $open_method = isset( $attributes[ 'open_method' ] ) ? sanitize_text_field( $attributes[ 'open_method' ] ) : 'regular';
            $quantity = isset( $attributes[ 'quantity' ] ) ? (bool) $attributes[ 'quantity' ] : false;
            $wrapper = isset( $attributes[ 'wrapper' ] ) ? sanitize_text_field( $attributes[ 'wrapper' ] ) : '';

            $ticket_type = new \Tickera\TC_Ticket( $id, 'publish' );
            $event_id = get_post_meta( $id, 'event_name', true );

            if ( $id && \Tickera\TC_Ticket::is_sales_available( $id )
                && isset( $ticket_type->details->ID ) && 'publish' == get_post_status( $event_id ) ) {

                $nonce = wp_nonce_field( 'tickera_add_to_cart_ajax', 'nonce', true, false );

                // Check if ticket still exists
                $with_price_content = ( $show_price ) ? ' <span class="' . esc_attr( $price_wrapper_class ) . '">' . esc_html( do_shortcode( '[ticket_price id="' . (int) $id . '"]' ) ) . '</span> ' : '';

                if ( is_array( $tc->get_cart_cookie() ) && array_key_exists( $id, $tc->get_cart_cookie() ) ) {
                    $button = sprintf( '<' . sanitize_text_field( $price_wrapper ) . ' class="tc_in_cart">%s <a href="%s">%s</a></' . sanitize_text_field( $price_wrapper ) . '>', apply_filters( 'tc_ticket_added_to_message', __( 'Ticket added to', 'tickera-event-ticketing-system' ) ), esc_url( $tc->get_cart_slug( true ) ), apply_filters( 'tc_ticket_added_to_cart_message', __( 'Cart', 'tickera-event-ticketing-system' ) ) );

                } else {

                    if ( $ticket_type->is_sold_ticket_exceeded_limit_level() === false ) {

                        if ( isset( $tc_general_settings[ 'force_login' ] ) && 'yes' == $tc_general_settings[ 'force_login' ] && ! is_user_logged_in() ) {
                            $button = '<form class="cart_form">' . $nonce . ( 'before' == $price_position ? $with_price_content : '' ) . '<a href="' . esc_url( apply_filters( 'tc_force_login_url', wp_login_url( get_permalink() ), get_permalink() ) ) . '" class="add_to_cart_force_login" id="ticket_' . (int) $id . '"><span class="title">' . esc_html( $title ) . '</span></a>' . wp_kses_post( 'after' == $price_position ? $with_price_content : '' ) . '<input type="hidden" name="ticket_id" class="ticket_id" value="' . esc_attr( $id ) . '"/>' . '</form>';

                        } else {

                            $button = '<form class="cart_form">' . $nonce . wp_kses(( true == $quantity ? tickera_quantity_selector( $id, true, false ) : '' ), wp_kses_allowed_html( 'tickera_quantity_selector' ) ) . ( ( 'before' == $price_position ) ? $with_price_content : '' ) . '<a href="#" class="add_to_cart" data-button-type="' . esc_attr( $type ) . '" data-open-method="' . esc_attr( $open_method ) . '" id="ticket_' . esc_attr( $id ) . '"><span class="title">' . esc_html( $title ) . '</span></a>' . ( ( 'after' == $price_position ) ? $with_price_content : '' ) . '<input type="hidden" name="ticket_id" class="ticket_id" value="' . esc_attr( $id ) . '"/>' . '</form>';
                        }

                    } else {
                        $button = '<span class="tc_tickets_sold">' . sanitize_text_field( $soldout_message ) . '</span>';
                    }
                }

                if ( $id && 'tc_tickets' == get_post_type( $id ) ) {
                    return $button;

                } else {
                    return __( 'Unknown ticket ID', 'tickera-event-ticketing-system' );
                }

            } else {
                return '';
            }
        }

        function ticket_price( $atts ) {
            global $tc;
            extract( shortcode_atts( array(
                'id' => ''
            ), $atts ) );

            $ticket = new \Tickera\TC_Ticket( (int) $id, 'publish' );
            return apply_filters( 'tc_cart_currency_and_format', tickera_get_ticket_price( $ticket->details->ID ) );
        }

        function event_tickets_sold( $atts ) {

            global $post;

            extract( shortcode_atts( array(
                'event_id' => ''
            ), $atts ) );

            if ( empty( $event_id ) ) {
                $event_id = $post->ID;
            }
            return tickera_get_event_tickets_count_sold( $event_id );
        }

        function event_date( $atts ) {
            global $post;

            extract( shortcode_atts( array(
                'event_id' => '',
                'id' => '',
            ), $atts ) );

            if ( empty( $id ) && empty( $event_id ) ) {
                $id = $post->ID;
            }

            if ( ! empty( $event_id ) ) {
                $id = $event_id;
            } elseif ( ! empty( $id ) ) {
                $id = $id;
            }

            $event = new \Tickera\TC_Event( $id );

            return $event->get_event_date();
        }

        function event_location( $atts ) {
            global $post;
            extract( shortcode_atts( array(
                'id' => ''
            ), $atts ) );

            if ( empty( $id ) ) {
                $id = $post->ID;
            }

            $event = new \Tickera\TC_Event( $id );

            return $event->details->event_location;
        }

        function event_terms( $atts ) {
            global $post;
            extract( shortcode_atts( array(
                'id' => ''
            ), $atts ) );

            if ( empty( $id ) ) {
                $id = $post->ID;
            }

            $event = new \Tickera\TC_Event( $id );
            return apply_filters( 'tc_shortcode_event_terms', wpautop( $event->details->event_terms ), $event->details->event_terms );
        }

        function event_sponsors_logo( $atts ) {
            global $post;
            extract( shortcode_atts( array(
                'id' => '',
                'class' => 'event_sponsors_logo',
                'width' => 'auto',
                'height' => 'auto'
            ), $atts ) );

            if ( empty( $id ) ) {
                $id = $post->ID;
            }

            $event = new \Tickera\TC_Event( $id );
            $img_scr = $event->details->sponsors_logo_file_url;

            if ( ! empty( $img_scr ) ) {
                return '<img src="' . esc_attr( $img_scr ) . '" width="' . esc_attr( $width ) . '" height="' . esc_attr( $height ) . '" class="' . esc_attr( $class ) . '" />';
            } else {
                return '';
            }
        }

        function event_logo( $atts ) {
            global $post;
            extract( shortcode_atts( array(
                'id' => '',
                'class' => 'event_logo',
                'width' => 'auto',
                'height' => 'auto'
            ), $atts ) );

            if ( empty( $id ) ) {
                $id = $post->ID;
            }

            $event = new \Tickera\TC_Event( $id );
            $img_scr = $event->details->event_logo_file_url;

            if ( ! empty( $img_scr ) ) {
                return '<img src="' . esc_attr( $img_scr ) . '" width="' . esc_attr( $width ) . '" height="' . esc_attr( $height ) . '" class="' . esc_attr( $class ) . '" />';
            } else {
                return '';
            }
        }

        function event_tickets_left( $atts ) {
            global $post;
            extract( shortcode_atts( array(
                'event_id' => ''
            ), $atts ) );

            if ( empty( $event_id ) ) {
                $event_id = $post->ID;
            }
            return tickera_get_event_tickets_count_left( $event_id );
        }

        function tickets_sold( $atts ) {
            extract( shortcode_atts( array(
                'ticket_type_id' => ''
            ), $atts ) );
            return tickera_get_tickets_count_sold( $ticket_type_id );
        }

        function tickets_left( $atts ) {
            extract( shortcode_atts( array(
                'ticket_type_id' => ''
            ), $atts ) );
            return tickera_get_tickets_count_left( $ticket_type_id );
        }

        /**
         * Render frontend Cart Page Elements.
         *
         * @param $atts
         * @return string
         */
        function tc_cart_page( $atts ) {

            global $tc;

            ob_start();
            $tc->session->start();

            $theme_file = locate_template( [ 'shortcode-cart-contents.php' ] );

            if ( '' != $theme_file ) {
                include $theme_file;

            } else {
                include $tc->plugin_dir . 'includes/templates/shortcode-cart-contents.php';
            }

            $tc->session->close();
            return wpautop( ob_get_clean(), false );
        }

        /**
         * Render frontend Cart Page Elements with additional fields.
         *
         * @param $atts
         * @return string
         */
        function tc_additional_fields( $atts ) {

            global $tc;

            ob_start();
            $tc->session->start();

            $theme_file = locate_template( [ 'shortcode-cart-additional-info-fields.php' ] );

            if ( '' != $theme_file ) {
                include $theme_file;

            } else {
                include $tc->plugin_dir . 'includes/templates/shortcode-cart-additional-info-fields.php';
            }

            $tc->session->close();
            return wpautop( ob_get_clean(), false );
        }

        /**
         * Render additional buyer fields.
         *
         * @param $atts
         * @return string
         */
        function tc_additional_buyer_fields( $atts ) {

            global $tc;

            ob_start();
            $tc->session->start();

            $theme_file = locate_template( [ 'shortcode-cart-additional-buyer-fields.php' ] );

            if ( '' != $theme_file ) {
                include $theme_file;

            } else {
                include $tc->plugin_dir . 'includes/templates/shortcode-cart-additional-buyer-fields.php';
            }

            $tc->session->close();
            return wpautop( ob_get_clean(), false );
        }

        /**
         * Render additional owner fields.
         *
         * @param $atts
         * @return string
         */
        function tc_additional_owner_fields( $atts ) {

            global $tc;

            ob_start();
            $tc->session->start();

            $theme_file = locate_template( [ 'shortcode-cart-additional-owner-fields.php' ] );

            if ( '' != $theme_file ) {
                include $theme_file;

            } else {
                include $tc->plugin_dir . 'includes/templates/shortcode-cart-additional-owner-fields.php';
            }

            $tc->session->close();
            return wpautop( ob_get_clean(), false );
        }

        /**
         * Render frontend Cart Page Elements with additional fields.
         *
         * @param $atts
         * @return string
         */
        function tc_additional_fields_edd( $atts ) {
            global $tc;
            ob_start();
            $tc->session->start();

            include $tc->plugin_dir . 'includes/templates/shortcode-cart-additional-info-fields-edd.php';

            $tc->session->close();
            return wpautop( ob_get_clean(), false );
        }

        function tc_process_payment_page( $atts ) {
            global $tc;
            ob_start();
            $tc->session->start();

            include( $tc->plugin_dir . 'includes/templates/page-process-payment.php' );

            $tc->session->close();
            return wpautop( ob_get_clean(), true );
        }

        function tc_ipn_page( $atts ) {
            global $tc;
            ob_start();
            $tc->session->start();

            include( $tc->plugin_dir . 'includes/templates/page-ipn.php' );

            $tc->session->close();
            return wpautop( ob_get_clean(), true );
        }

        function tc_order_history_page( $atts ) {
            global $tc;
            ob_start();
            $tc->session->start();

            include( $tc->plugin_dir . 'includes/templates/shortcode-order-history-contents.php' );

            $tc->session->close();
            return wpautop( ob_get_clean(), true );
        }

        function tc_payment_page( $atts ) {
            global $tc;
            ob_start();
            $tc->session->start();

            include( $tc->plugin_dir . 'includes/templates/page-payment.php' );

            $tc->session->close();
            return wpautop( ob_get_clean(), true );
        }

        function tc_order_confirmation_page( $atts ) {
            global $tc;
            ob_start();
            $tc->session->start();

            include( $tc->plugin_dir . 'includes/templates/page-confirmation.php' );

            $tc->session->close();
            return wpautop( ob_get_clean(), true );
        }

        function tc_order_details_page( $atts ) {
            global $tc, $wp;
            ob_start();
            $tc->session->start();

            $theme_file = locate_template( [ 'page-order.php' ] );

            if ( '' != $theme_file ) {
                include $theme_file;

            } else {
                include( $tc->plugin_dir . 'includes/templates/page-order.php' );
            }

            $tc->session->close();
            return wpautop( ob_get_clean(), true );
        }
    }

    $tc_shortcodes = new TC_Shortcodes();
}
