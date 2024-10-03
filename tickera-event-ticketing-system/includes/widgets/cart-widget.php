<?php

namespace Tickera\Widget;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Widget\TC_Cart_Widget' ) ) {

    class TC_Cart_Widget extends \WP_Widget {

        function __construct() {
            $widget_ops = array( 'classname' => 'widget widget_recent_entries tc_cart_widget', 'description' => __( 'Displays tickets added to cart', 'tickera-event-ticketing-system' ) );
            parent::__construct( 'TC_Cart_Widget', __( 'Tickets Cart', 'tickera-event-ticketing-system' ), $widget_ops );
        }

        function form( $instance ) {
            $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'button_title' => '' ) );
            $title = $instance[ 'title' ];
            $button_title = $instance[ 'button_title' ]; ?>
            <p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'tickera-event-ticketing-system' ); ?>: <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( ! isset( $title ) ? esc_html__( 'Cart', 'tickera-event-ticketing-system' ) : esc_attr( $title ) ); ?>"/></label></p>
            <p><label for="<?php echo esc_attr( $this->get_field_id( 'button_title' ) ); ?>"><?php esc_html_e( 'Cart Button Title', 'tickera-event-ticketing-system' ); ?>: <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'button_title' ) ); ?>" type="text" value="<?php echo esc_attr( ! isset( $button_title ) ? esc_html__( 'Go to Cart', 'tickera-event-ticketing-system' ) : esc_attr( $button_title ) ); ?>"/></label></p>
            <?php
        }

        function update( $new_instance, $old_instance ) {
            $instance = $old_instance;
            $instance[ 'title' ] = $new_instance[ 'title' ];
            $instance[ 'button_title' ] = $new_instance[ 'button_title' ];
            return $instance;
        }

        function widget( $args, $instance ) {
            global $tc;

            $cart_url = trailingslashit( $tc->get_cart_slug( true ) );
            $show_widget_on_cart_page = apply_filters( 'tc_show_cart_widget_on_cart_page', false );

            if ( ( tickera_current_url() !== $cart_url ) || $show_widget_on_cart_page ) {

                extract( $args, EXTR_SKIP );
                echo wp_kses_post( $before_widget );

                $title = empty( $instance[ 'title' ] ) ? ' ' : apply_filters( 'tc_cart_widget_title', $instance[ 'title' ] );
                $button_title = empty( $instance[ 'button_title' ] ) ? '' : apply_filters( 'tc_cart_widget_button_title', $instance[ 'button_title' ] );

                if ( ! empty( $title ) ) {
                    echo wp_kses_post( $before_title . $title . $after_title );
                }

                // Cart Contents
                $cart_contents = $tc->get_cart_cookie();
                if ( ! empty( $cart_contents ) ) {
                    do_action( 'tc_cart_before_ul', $cart_contents ); ?>
                    <ul class='tc_cart_ul'>
                        <?php foreach ( $cart_contents as $ticket_type => $ordered_count ) :
                            $ticket = new \Tickera\TC_Ticket( $ticket_type ); ?>
                            <li id='tc_ticket_type_<?php echo esc_attr( (int) $ticket_type ); ?>'>
                                <?php echo wp_kses_post( apply_filters( 'tc_cart_widget_item', ( $ordered_count . ' x ' . $ticket->details->post_title . ' (' . apply_filters( 'tc_cart_currency_and_format', tickera_get_ticket_price( $ticket->details->ID ) * $ordered_count ) . ')' ) ), $ordered_count, $ticket->details->post_title, tickera_get_ticket_price( $ticket->details->ID ) ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php
                    do_action( 'tc_cart_after_ul', $cart_contents );

                } else {
                    do_action( 'tc_cart_before_empty' ); ?>
                    <ul class='tc_cart_ul'>
                        <li><span class='tc_empty_cart'><?php esc_html_e( 'The cart is empty', 'tickera-event-ticketing-system' ); ?></span></li>
                    </ul>
                    <?php
                    do_action( 'tc_cart_after_empty' );
                }
                ?>
                <button class='tc_widget_cart_button' data-url='<?php echo esc_attr( $cart_url ); ?>'><?php echo wp_kses_post( $button_title ); ?></button>
                <div class='tc-clearfix'></div>
                <?php
                echo wp_kses_post( $after_widget );
            }
        }
    }

    add_action( 'widgets_init', function () {
        register_widget( 'Tickera\Widget\TC_Cart_Widget' );
    } );
}
