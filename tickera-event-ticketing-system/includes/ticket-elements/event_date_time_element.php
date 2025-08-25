<?php

namespace Tickera\Ticket\Element;
use _PhpScoper5ca3692350464\GuzzleHttp\Pool;
use Tickera\TC_Ticket_Template_Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\Tickera\Ticket\Element\tc_event_date_time_element' ) ) {

    class tc_event_date_time_element extends TC_Ticket_Template_Elements {

        var $element_name = 'tc_event_date_time_element';
        var $element_title = 'Event Date & Time';
        var $font_awesome_icon = '<span class="tti-date_schedule_calendar_event_icon-1"></span>';

        function admin_content() {
            ob_start();
            $this->get_font_sizes();
            $this->get_font_style();
            $this->get_font_colors();
            $this->get_cell_alignment();
            $this->get_display_format();
            $this->get_element_margins();
            return apply_filters( 'tc_ticket_admin_content', ob_get_clean() );
        }

        function get_display_format() { ?>
            <label><?php _e( 'Display Format', 'tickera-event-ticketing-system' ) ?></label>
            <select name="<?php echo esc_attr( $this->element_name . '_display_format_post_meta' ) ?>">
                <option value="0" <?php selected( isset( $this->template_metas[ $this->element_name . '_display_format' ] ) ? $this->template_metas[ $this->element_name . '_display_format' ] : 'left', 0, true ); ?>><?php _e( 'Start/End Date & Time', 'tickera-event-ticketing-system' ) ?></option>
                <option value="1" <?php selected( isset( $this->template_metas[ $this->element_name . '_display_format' ] ) ? $this->template_metas[ $this->element_name . '_display_format' ] : 'left', 1, true ); ?>><?php _e( 'Start Date & Time', 'tickera-event-ticketing-system' ) ?></option>
                <option value="2" <?php selected( isset( $this->template_metas[ $this->element_name . '_display_format' ] ) ? $this->template_metas[ $this->element_name . '_display_format' ] : 'left', 2, true ); ?>><?php _e( 'End Date & Time', 'tickera-event-ticketing-system' ) ?></option>
            </select>

        <?php }

        function on_creation() {
            $this->element_title = apply_filters( 'tc_event_date_time_element_title', __( 'Event Date & Time', 'tickera-event-ticketing-system' ) );
        }

        /**
         * Render Event Date & Time
         *
         * Display Format
         * 0 Start/End Date & Time
         * 1 Start Date & Time
         * 2 End Date & Time
         *
         * @param $event_id
         * @return string
         */
        function get_event_date( $event_id ) {

            $display_format = get_post_meta( $this->id, $this->element_name . '_display_format', true );
            $display_format = $display_format ? $display_format : 0;

            $event_start_date = get_post_meta( $event_id, 'event_date_time', true );
            $event_end_date = get_post_meta( $event_id, 'event_end_date_time', true );

            $start_date = date_i18n( get_option( 'date_format' ), strtotime( $event_start_date ) );
            $start_time = date_i18n( get_option( 'time_format' ), strtotime( $event_start_date ) );

            $end_date = date_i18n( get_option( 'date_format' ), strtotime( $event_end_date ) );
            $end_time = date_i18n( get_option( 'time_format' ), strtotime( $event_end_date ) );

            switch ( $display_format ) {

                case 0:
                    if ( ! empty( $event_end_date ) ) {

                        if ( $start_date == $end_date ) {

                            if ( $start_time == $end_time ) {
                                $event_date = $start_date . ' ' . $start_time;

                            } else {
                                $event_date = $start_date . ' ' . $start_time . ' - ' . $end_time;
                            }

                        } else {

                            if ( $start_time == $end_time ) {
                                $event_date = $start_date . ' - ' . $end_date . ' ' . $start_time;

                            } else {
                                $event_date = $start_date . ' ' . $start_time . ' - ' . $end_date . ' ' . $end_time;
                            }
                        }

                    } else {
                        $event_date = $start_date . ' ' . $start_time;
                    }
                    break;

                case 1:
                    $event_date = ( ! empty( $event_start_date ) ) ? $start_date . ' ' . $start_time : '';
                    break;

                case 2:
                    $event_date = ( ! empty( $event_end_date ) ) ? $end_date . ' ' . $end_time : '';
                    break;

                default:
                    $event_date = $start_date . ' ' . $start_time;
            }

            return $event_date;
        }

        function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {

            if ( $ticket_instance_id ) {
                $ticket_instance = new \Tickera\TC_Ticket( (int) $ticket_instance_id );
                $ticket = new \Tickera\TC_Ticket();
                $event_id = $ticket->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );
                $event_date = $this->get_event_date( $event_id );
                return '<br/>' . apply_filters( 'tc_event_date_time_element_ticket_type', $event_date, apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ), $ticket_instance_id );

            } else {

                if ( $ticket_type_id ) {
                    $ticket_type = new \Tickera\TC_Ticket( (int) $ticket_type_id );
                    $event_id = $ticket_type->get_ticket_event( $ticket_type_id );
                    $event_date = $this->get_event_date( $event_id );
                    return '<br/>' . $event_date;

                } else {
                    return '<br/>' . apply_filters( 'tc_event_date_time_element_default', date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), time(), false ) );
                }
            }
        }
    }

    \Tickera\tickera_register_template_element( 'Tickera\Ticket\Element\tc_event_date_time_element', __( 'Event Date & Time', 'tickera-event-ticketing-system' ) );
}