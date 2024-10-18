<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Shortcode_Builder' ) ) {

    class TC_Shortcode_Builder {

        /**
         * TC_Shortcode_Builder constructor.
         * @param bool $init
         */
        function __construct( $init = true ) {

            if ( ! $init ) {
                return;
            }

            global $post;

            if ( isset( $post ) && $post->post_type == 'tc_tickets' ) {
                return;
            }

            if ( isset( $_GET[ 'post' ] ) ) {
                $post_type = get_post_type( (int) $_GET[ 'post' ] );
                if ( $post_type == 'tc_tickets' ) {
                    return;
                }
            }

            if ( isset( $_GET[ 'page' ] ) && ( $_GET[ 'page' ] == 'tc_events' || $_GET[ 'page' ] == 'tc_ticket_types' || $_GET[ 'page' ] == 'tc_settings' ) ) {
                return;
            }

            add_action( 'media_buttons', array( $this, 'media_buttons' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
            add_action( 'in_admin_footer', array( $this, 'show_shortcodes' ) );
        }

        public function show_shortcodes() {

            if ( did_action( 'media_buttons' ) == 0 ) {
                return;
            }

            echo wp_kses( self::form(), wp_kses_allowed_html( 'tickera' ) );
        }

        function form() {

            $shortcodes = array(
                'tc_ticket' => __( 'Ticket / Add to cart button', 'tickera-event-ticketing-system' ),
                'tc_event' => __( 'Event Tickets', 'tickera-event-ticketing-system' ),
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
            );

            $shortcodes = apply_filters( 'tc_shortcodes', $shortcodes );
            ob_start();
            ?>
            <div id="tc-shortcode-builder-wrap" style="display:none">
            <form id="tc-shortcode-builder">
                <div class="tc-title-wrap">
                    <h3><?php esc_html_e( 'Add Shortcode', 'tickera-event-ticketing-system' ); ?></h3>
                    <div class="tc-close"><!-- Being used at builders handler --></div>
                </div><!-- .tc-title-wrap -->
                <div class="tc-shortcode-wrap">
                    <select name="shortcode-select" id="tc-shortcode-select">
                        <?php foreach ( $shortcodes as $shortcode => $label ) : ?>
                            <option value="<?php echo esc_attr( $shortcode ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="tc-shortcode-atts">
                        <h3><?php esc_html_e( 'Shortcode Attributes', 'tickera-event-ticketing-system' ); ?></h3>
                        <?php
                        foreach ( $shortcodes as $shortcode => $label ) {
                            $func = 'show_' . $shortcode . '_attributes';

                            if ( method_exists( $this, $func ) ) {
                                call_user_func( array( &$this, $func ) );
                            }

                            if ( function_exists( $func ) ) {
                                call_user_func( $func );
                            }
                        }
                        ?>
                    </div>
                    <p class="submit">
                        <input class="button-primary" type="submit" value="<?php esc_html_e( 'Insert Shortcode', 'tickera-event-ticketing-system' ); ?>"/>
                    </p>
                </div><!-- .tc-shortcode-wrap -->
            </form>
            </div><?php
            return ob_get_clean();
        }

        public function show_tc_order_history_attributes() { ?>
            <table id="tc-order-history-shortcode" class="shortcode-table" style="display:none">
            <tr>
                <th scope="row"><?php esc_html_e( 'Without extra attributes', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <?php esc_html_e( 'Just insert a shortcode in the post / page and it will show order history of the current logged in user.', 'tickera-event-ticketing-system' ); ?>
                </td>
            </tr>
            </table><?php
        }

        public function show_tc_ticket_attributes() { ?>
            <table id="tc-ticket-shortcode" class="shortcode-table" style="display:none">
            <tr>
                <th scope="row"><?php esc_html_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="id">
                        <?php
                        $wp_tickets_search = new \Tickera\TC_Tickets_Search( '', '', -1 );
                        foreach ( $wp_tickets_search->get_results() as $ticket_type ) : $ticket = new \Tickera\TC_Ticket( $ticket_type->ID ); ?>
                            <option value="<?php echo esc_attr( $ticket->details->ID ); ?>"><?php echo esc_html( $ticket->details->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Link Title', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <input type="text" name="title" value="" placeholder="<?php esc_attr_e( 'Add to Cart', 'tickera-event-ticketing-system' ); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Soldout Message', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <input type="text" name="soldout_message" value="" placeholder="<?php esc_attr_e( 'Tickets are sold out.', 'tickera-event-ticketing-system' ); ?>"/><br/>
                    <span class="description"><?php esc_html_e( 'The message which will be shown when all tickets are sold.', 'tickera-event-ticketing-system' ); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Show Price', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="show_price" data-default-value="false">
                        <option value="false"><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?></option>
                        <option value="true"><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Price Position', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="price_position" data-default-value="after">
                        <option value="after"><?php esc_html_e( 'After', 'tickera-event-ticketing-system' ); ?></option>
                        <option value="before"><?php esc_html_e( 'Before', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Show Quantity Selector', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="quantity" data-default-value="">
                        <option value=""><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?></option>
                        <option value="true"><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Link Type', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="type" data-default-value="cart">
                        <option value="cart"><?php esc_html_e( 'Cart', 'tickera-event-ticketing-system' ); ?></option>
                        <option value="buynow"><?php esc_html_e( 'Buy Now', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                    <span class="description"><?php esc_html_e( 'If Buy Now is selected, after clicking on the link, user will be redirected automatically to the cart page.', 'tickera-event-ticketing-system' ); ?></span>
                </td>
            </tr>
            </table><?php
        }

        public function show_tc_event_attributes() {
            global $post; ?>
            <table id="tc-event-shortcode" class="shortcode-table" style="display:none">
            <?php if ( $post && isset( $post->post_type ) && 'tc_events' !== $post->post_type ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td>
                        <select name="id">
                            <?php
                            $wp_events_search =  new \Tickera\TC_Events_Search( '', '', -1 );
                            foreach ( $wp_events_search->get_results() as $event ) : $event = new \Tickera\TC_Event( $event->ID ); ?>
                                <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td><?php esc_html_e( 'Current Event', 'tickera-event-ticketing-system' ); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th scope="row"><?php esc_html_e( 'Display Type', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="display_type" class="display_type has_conditional">
                        <option value="table"><?php esc_html_e( 'Table (Default)', 'tickera-event-ticketing-system' ); ?></option>
                        <option value="dropdown"><?php esc_html_e( 'Dropdown', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="tc_conditional" data-condition-field_name="display_type" data-condition-field_type="select" data-condition-value="table" data-condition-action="hide">
                <th scope="row"><?php esc_html_e( 'Show Event Title', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="show_event_title">
                        <option value="true"><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?></option>
                        <option value=""><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="tc_conditional" data-condition-field_name="display_type" data-condition-field_type="select" data-condition-value="table" data-condition-action="hide">
                <th scope="row"><?php esc_html_e( 'Show Price', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="show_price">
                        <option value="true"><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?></option>
                        <option value=""><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Link Title', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <input type="text" name="title" value="" placeholder="<?php esc_attr_e( 'Add to Cart', 'tickera-event-ticketing-system' ); ?>"/>
                </td>
            </tr>
            <tr class="tc_conditional" data-condition-field_name="display_type" data-condition-field_type="select" data-condition-value="dropdown" data-condition-action="hide">
                <th scope="row"><?php esc_html_e( 'Ticket Type Column Title', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <input type="text" name="ticket_type_title" value="" placeholder="<?php esc_attr_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?>"/>
                </td>
            </tr>
            <tr class="tc_conditional" data-condition-field_name="display_type" data-condition-field_type="select" data-condition-value="dropdown" data-condition-action="hide">
                <th scope="row"><?php esc_html_e( 'Price Column Title', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <input type="text" name="price_title" value="" placeholder="<?php esc_attr_e( 'Price', 'tickera-event-ticketing-system' ); ?>"/>
                </td>
            </tr>
            <tr class="tc_conditional" data-condition-field_name="display_type" data-condition-field_type="select" data-condition-value="dropdown" data-condition-action="hide">
                <th scope="row"><?php esc_html_e( 'Quantity Column Title', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <input type="text" name="quantity_title" value="" placeholder="<?php esc_attr_e( 'Qty.', 'tickera-event-ticketing-system' ); ?>"/>
                </td>
            </tr>
            <tr class="tc_conditional" data-condition-field_name="display_type" data-condition-field_type="select" data-condition-value="dropdown" data-condition-action="hide">
                <th scope="row"><?php esc_html_e( 'Cart Column Title', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <input type="text" name="cart_title" value="" placeholder="<?php esc_attr_e( 'Cart', 'tickera-event-ticketing-system' ); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Soldout Message', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <input type="text" name="soldout_message" value="" placeholder="<?php esc_attr_e( 'Tickets are sold out.', 'tickera-event-ticketing-system' ); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Show Quantity Selector', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="quantity" class="quantity">
                        <option value=""><?php esc_html_e( 'No', 'tickera-event-ticketing-system' ); ?></option>
                        <option value="true"><?php esc_html_e( 'Yes', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Link Type', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="type" data-default-value="cart">
                        <option value="cart"><?php esc_html_e( 'Cart', 'tickera-event-ticketing-system' ); ?></option>
                        <option value="buynow"><?php esc_html_e( 'Buy Now', 'tickera-event-ticketing-system' ); ?></option>
                    </select>
                    <span class="description"><?php esc_html_e( 'If Buy Now is selected, after clicking on the link, user will be redirected automatically to the cart page.', 'tickera-event-ticketing-system' ); ?></span>
                </td>
            </tr>
            </table><?php
        }

        public function show_event_tickets_sold_attributes() {
            global $post; ?>
            <table id="event-tickets-sold-shortcode" class="shortcode-table" style="display:none">
            <?php if ( $post && isset( $post->post_type ) && 'tc_events' !== $post->post_type ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td>
                        <select name="event_id">
                            <?php
                            $wp_events_search =  new \Tickera\TC_Events_Search( '', '', -1 );
                            foreach ( $wp_events_search->get_results() as $event ) : $event = new \Tickera\TC_Event( $event->ID ); ?>
                                <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td><?php esc_html_e( 'Current Event', 'tickera-event-ticketing-system' ); ?></td>
                </tr>
            <?php endif; ?>
            </table><?php
        }

        public function show_tc_event_date_attributes() {
            global $post; ?>
            <table id="tc-event-date-shortcode" class="shortcode-table" style="display:none">
            <?php if ( $post && isset( $post->post_type ) && 'tc_events' !== $post->post_type ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td>
                        <select name="event_id">
                            <?php
                            $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 );
                            foreach ( $wp_events_search->get_results() as $event ) : $event = new \Tickera\TC_Event( $event->ID ); ?>
                                <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td><?php esc_html_e( 'Current Event', 'tickera-event-ticketing-system' ); ?></td>
                </tr>
            <?php endif; ?>
            </table><?php
        }

        public function show_tc_event_location_attributes() {
            global $post; ?>
            <table id="tc-event-location-shortcode" class="shortcode-table" style="display:none">
            <?php if ( $post && isset( $post->post_type ) && 'tc_events' !== $post->post_type ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td>
                        <select name="id">
                            <?php
                            $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 );
                            foreach ( $wp_events_search->get_results() as $event ) : $event = new \Tickera\TC_Event( $event->ID ); ?>
                                <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td><?php esc_html_e( 'Current Event', 'tickera-event-ticketing-system' ); ?></td>
                </tr>
            <?php endif; ?>
            </table><?php
        }

        public function show_tc_event_terms_attributes() {
            global $post; ?>
            <table id="tc-event-terms-shortcode" class="shortcode-table" style="display:none">
            <?php if ( $post && isset( $post->post_type ) && 'tc_events' !== $post->post_type ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td>
                        <select name="id">
                            <?php
                            $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 );
                            foreach ( $wp_events_search->get_results() as $event ) : $event = new \Tickera\TC_Event( $event->ID ); ?>
                                <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td><?php esc_html_e( 'Current Event', 'tickera-event-ticketing-system' ); ?></td>
                </tr>
            <?php endif; ?>
            </table><?php
        }

        public function show_tc_event_logo_attributes() {
            global $post; ?>
            <table id="tc-event-logo-shortcode" class="shortcode-table" style="display:none">
            <?php if ( $post && isset( $post->post_type ) && 'tc_events' !== $post->post_type ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td>
                        <select name="id">
                            <?php
                            $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 );
                            foreach ( $wp_events_search->get_results() as $event ) : $event = new \Tickera\TC_Event( $event->ID ); ?>
                                <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td><?php esc_html_e( 'Current Event', 'tickera-event-ticketing-system' ); ?></td>
                </tr>
            <?php endif; ?>
            </table><?php
        }

        public function show_tc_event_sponsors_logo_attributes() {
            global $post; ?>
            <table id="tc-event-sponsors-logo-shortcode" class="shortcode-table" style="display:none">
            <?php if ( $post && isset( $post->post_type ) && 'tc_events' !== $post->post_type ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td>
                        <select name="id">
                            <?php
                            $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 );
                            foreach ( $wp_events_search->get_results() as $event ) : $event = new \Tickera\TC_Event( $event->ID ); ?>
                                <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td><?php esc_html_e( 'Current Event', 'tickera-event-ticketing-system' ); ?></td>
                </tr>
            <?php endif; ?>
            </table><?php
        }

        public function show_event_tickets_left_attributes() {
            global $post; ?>
            <table id="event-tickets-left-shortcode" class="shortcode-table" style="display:none">
            <?php if ( $post && isset( $post->post_type ) && 'tc_events' !== $post->post_type ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td>
                        <select name="event_id">
                            <?php
                            $wp_events_search = new \Tickera\TC_Events_Search( '', '', -1 );
                            foreach ( $wp_events_search->get_results() as $event ) : $event = new \Tickera\TC_Event( $event->ID ); ?>
                                <option value="<?php echo esc_attr( $event->details->ID ); ?>"><?php echo esc_html( $event->details->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Event', 'tickera-event-ticketing-system' ); ?></th>
                    <td><?php esc_html_e( 'Current Event', 'tickera-event-ticketing-system' ); ?></td>
                </tr>
            <?php endif; ?>
            </table><?php
        }

        public function show_tickets_sold_attributes() { ?>
            <table id="tickets-sold-shortcode" class="shortcode-table" style="display:none">
            <tr>
                <th scope="row"><?php esc_html_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="ticket_type_id">
                        <?php
                        $wp_tickets_search = new \Tickera\TC_Tickets_Search( '', '', -1 );
                        foreach ( $wp_tickets_search->get_results() as $event ) : $ticket = new \Tickera\TC_Ticket( $event->ID ); ?>
                            <option value="<?php echo esc_attr( $ticket->details->ID ); ?>"><?php echo esc_html( $ticket->details->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            </table><?php
        }

        public function show_tickets_left_attributes() { ?>
            <table id="tickets-left-shortcode" class="shortcode-table" style="display:none">
            <tr>
                <th scope="row"><?php esc_html_e( 'Ticket Type', 'tickera-event-ticketing-system' ); ?></th>
                <td>
                    <select name="ticket_type_id">
                        <?php
                        $wp_tickets_search = new \Tickera\TC_Tickets_Search( '', '', -1 );
                        foreach ( $wp_tickets_search->get_results() as $event ) : $ticket = new \Tickera\TC_Ticket( $event->ID ); ?>
                            <option value="<?php echo esc_attr( $ticket->details->ID ); ?>"><?php echo esc_html( $ticket->details->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            </table><?php
        }

        public function enqueue_styles_scripts() {
            global $tc;
            $screen = get_current_screen();
            if ( isset( $screen->post_type ) && ! empty( $screen->post_type ) ) {
                wp_enqueue_style( $tc->name . '-colorbox', $tc->plugin_url . 'css/colorbox/colorbox.css', false, $tc->version );
                wp_enqueue_script( $tc->name . '-colorbox', $tc->plugin_url . 'js/jquery.colorbox-min.js', false, $tc->version );
                wp_enqueue_script( $tc->name . '-shortcode-builders-script', $tc->plugin_url . 'js/builders/shortcode-builder.js', array( $tc->name . '-colorbox' ), $tc->version );
            }
        }

        /**
         * Exclude "Tickera" Shortcode Builder Button in Tickera > Events > "Event terms and conditions" metabox
         * @param $editor_id
         *
         * @since 3.5.4.4
         */
        public function media_buttons( $editor_id ) {
            global $tc;
            if ( 'event_terms' != $editor_id ) : ?>
                <a href="javascript:;" class="button tc-shortcode-builder-button" title="<?php echo esc_attr( $tc->title . ' ' . __( 'Shortcodes', 'tickera-event-ticketing-system' ) ); ?>"><span class="wp-media-buttons-icon dashicons dashicons-tickets-alt"></span> <?php echo esc_html( $tc->title ); ?></a>
            <?php endif;
        }
    }

    $shortcode_builder = new TC_Shortcode_Builder();
}
