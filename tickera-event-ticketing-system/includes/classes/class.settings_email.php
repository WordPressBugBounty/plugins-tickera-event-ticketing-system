<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Settings_Email' ) ) {

    class TC_Settings_Email {

        function __construct() {}

        function TC_Settings_Email() {
            $this->__construct();
        }

        /**
         * Declare Email Field Sections
         *
         * @return mixed|void
         */
        function get_settings_email_sections() {

            $general_settings = get_option( 'tickera_general_setting' );
            $owner_fields = isset( $general_settings[ 'show_owner_fields' ] ) ? $general_settings[ 'show_owner_fields' ] : 'no';
            $owner_email = isset( $general_settings[ 'show_owner_email_field' ] ) ? $general_settings[ 'show_owner_email_field' ] : 'no';

            return apply_filters( 'tc_settings_email_sections', array(

                    array(
                        'name' => 'attendee_order_completed_email',
                        'title' => __( 'Attendee Order Completed Email', 'tickera-event-ticketing-system' ),
                        'description' => '',
                        'note' => ( 'no' == $owner_fields || 'no' == $owner_email ) ? __('In order to be able to send emails to attendees, you will first need to go to Tickera Settings > General tab and set Show attendee fields as well as Show attendee email field options to Yes.', 'tickera-event-ticketing-system' ) : '',
                        'class' => ( 'no' == $owner_fields || 'no' == $owner_email ) ? 'tc-disable' : '',
                    ),
                    array(
                        'name' => 'client_order_placed_email',
                        'title' => __( 'Client Order Placed Email', 'tickera-event-ticketing-system' ),
                        'description' => '',
                    ),
                    array(
                        'name' => 'client_order_completed_email',
                        'title' => __( 'Client Order Completed Email', 'tickera-event-ticketing-system' ),
                        'description' => '',
                    ),
                    array(
                        'name' => 'admin_order_placed_email',
                        'title' => __( 'Admin Order Placed Email', 'tickera-event-ticketing-system' ),
                        'description' => '',
                    ),
                    array(
                        'name' => 'admin_order_completed_email',
                        'title' => __( 'Admin Order Completed Email', 'tickera-event-ticketing-system' ),
                        'description' => '',
                    ),
                    array(
                        'name' => 'admin_order_refunded_email',
                        'title' => __( 'Admin Order Refunded Email', 'tickera-event-ticketing-system' ),
                        'description' => '',
                    ),
                    array(
                        'name' => 'misc_email',
                        'title' => __( 'Miscellaneous', 'tickera-event-ticketing-system' ),
                        'description' => '',
                    )
                )
            );
        }

        /**
         * Declare Email Section Fields
         *
         * @return mixed|void
         */
        function get_settings_email_fields() {

            $client_order_placed_email_fields = apply_filters( 'client_order_placed_email_fields', array(

                    array(
                        'field_name' => 'client_send_placed_message',
                        'field_title' => __( 'Enable', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_yes_no_email',
                        'default_value' => 'no',
                        'tooltip' => __( 'Enable/disable emails sent to ticket buyer for placed/pending orders', 'tickera-event-ticketing-system' ),
                        'section' => 'client_order_placed_email'
                    ),
                    array(
                        'field_name' => 'client_order_placed_subject',
                        'field_title' => __( 'Subject', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => __( 'Order placed', 'tickera-event-ticketing-system' ),
                        'tooltip' => __( 'Subject of the email', 'tickera-event-ticketing-system' ),
                        'section' => 'client_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'client_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'client_order_from_placed_name',
                        'field_title' => __( 'From name', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'blogname' ),
                        'tooltip' => __( 'Enter a name you would like to use in the emails sent to your customers', 'tickera-event-ticketing-system' ),
                        'section' => 'client_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'client_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'client_order_from_placed_email',
                        'field_title' => __( 'From email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address you would like your emails to appear as "sent from". <br/>Please use valid email address if you want to allow your customers to contact you back.', 'tickera-event-ticketing-system' ),
                        'section' => 'client_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'client_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'client_order_placed_message',
                        'field_title' => __( 'Email content', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_get_client_order_message',
                        'default_value' => __( 'Hello, <br /><br />Your order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> is placed. <br /><br />You can track your order status here: DOWNLOAD_URL', 'tickera-event-ticketing-system' ),
                        'field_description' => sprintf(
                            /* translators: %s: Client order processing email's description placeholders. */
                            __( 'Body of the e-mail. You can use the following placeholders (%s)', 'tickera-event-ticketing-system' ),
                            apply_filters( 'tc_client_order_placed_message_placeholders_description', 'ORDER_ID, ORDER_TOTAL, DOWNLOAD_URL, BUYER_NAME' )
                        ),
                        'section' => 'client_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'client_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    )
                )
            );

            $client_order_completed_email_fields = apply_filters( 'client_order_completed_email_fields', array(

                    array(
                        'field_name' => 'client_send_message',
                        'field_title' => __( 'Enable', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_yes_no_email',
                        'default_value' => 'yes',
                        'tooltip' => __( 'Enable/disable emails sent to ticket buyer for completed orders. <br/>Make sure this is enabled if you want ticket buyer to receive the tickets upon finishing their order.', 'tickera-event-ticketing-system' ),
                        'section' => 'client_order_completed_email'
                    ),
                    array(
                        'field_name' => 'client_order_subject',
                        'field_title' => __( 'Subject', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => __( 'Order Completed', 'tickera-event-ticketing-system' ),
                        'tooltip' => __( 'Subject of the email', 'tickera-event-ticketing-system' ),
                        'section' => 'client_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'client_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'client_order_from_name',
                        'field_title' => __( 'From Name', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'blogname' ),
                        'tooltip' => __( 'Enter a name you would like to use in the emails sent to your customers', 'tickera-event-ticketing-system' ),
                        'section' => 'client_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'client_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'client_order_from_email',
                        'field_title' => __( 'From email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address you would like your emails to appear as "sent from". <br/>Please use valid email address if you want to allow your customers to contact you back.', 'tickera-event-ticketing-system' ),
                        'section' => 'client_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'client_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'client_order_message',
                        'field_title' => __( 'Email content', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_get_client_order_message',
                        'default_value' => __( 'Hello, <br /><br />Your order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> is completed. <br /><br />You can download your tickets DOWNLOAD_URL', 'tickera-event-ticketing-system' ),
                        'field_description' => sprintf(
                            /* translators: %s: Client order completed email's description placeholders. */
                            __( 'Body of the e-mail. You can use the following placeholders (%s)', 'tickera-event-ticketing-system' ),
                            apply_filters( 'tc_client_order_completed_message_placeholders_description', 'ORDER_ID, ORDER_TOTAL, DOWNLOAD_URL, BUYER_NAME. ORDER_DETAILS, EVENT_NAME, EVENT_LOCATION' )
                        ),
                        'section' => 'client_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'client_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    )
                )
            );

            $attendee_order_completed_email_fields = apply_filters( 'attendee_order_completed_email_fields', array(

                    array(
                        'field_name' => 'attendee_send_message',
                        'field_title' => __( 'Enable', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_yes_no_email',
                        'default_value' => 'no',
                        'tooltip' => __( 'Enable/disable emails sent to the attendees for completed orders. <br/>In order for these emails to get delivered, you must enable attendee email fields in Tickera Settings > General.', 'tickera-event-ticketing-system' ),
                        'section' => 'attendee_order_completed_email'
                    ),
                    array(
                        'field_name' => 'attendee_attach_ticket',
                        'field_title' => __( 'Enable attachment', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_yes_no_email',
                        'default_value' => 'no',
                        'tooltip' => __( 'Attach ticket as a file to the email', 'tickera-event-ticketing-system' ),
                        'section' => 'attendee_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'attendee_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'attendee_order_subject',
                        'field_title' => __( 'Subject', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => __( 'Your Ticket is here!', 'tickera-event-ticketing-system' ),
                        'tooltip' => __( 'Subject of the email', 'tickera-event-ticketing-system' ),
                        'section' => 'attendee_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'attendee_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'attendee_order_from_name',
                        'field_title' => __( 'From name', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'blogname' ),
                        'tooltip' => __( 'Enter a name you would like to use in the emails sent to your customers', 'tickera-event-ticketing-system' ),
                        'section' => 'attendee_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'attendee_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'attendee_order_from_email',
                        'field_title' => __( 'From email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address you would like your emails to appear as "sent from". ', 'tickera-event-ticketing-system' ),
                        'section' => 'attendee_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'attendee_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'attendee_order_message',
                        'field_title' => __( 'Email content', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_get_attendee_order_message',
                        'default_value' => __( 'Hello, <br /><br />You can download ticket for EVENT_NAME here DOWNLOAD_URL', 'tickera-event-ticketing-system' ),
                        'field_description' => sprintf(
                            /* translators: %s: Attendee order completed email's description placeholders. */
                            __( 'Body of the e-mail. You can use the following placeholders (%s)', 'tickera-event-ticketing-system' ),
                            apply_filters( 'tc_attendee_order_completed_message_placeholders_description', 'DOWNLOAD_LINK, DOWNLOAD_URL, EVENT_NAME, TICKET_TYPE, TICKET_CODE, FIRST_NAME, LAST_NAME, EVENT_LOCATION' )
                        ),
                        'section' => 'attendee_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'attendee_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    )
                )
            );

            $admin_order_placed_email_fields = apply_filters( 'admin_order_placed_email_fields', array(

                    array(
                        'field_name' => 'admin_send_placed_message',
                        'field_title' => __( 'Enable', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_yes_no_email',
                        'default_value' => 'no',
                        'tooltip' => __( 'Enable/disable emails sent to the admin email address for placed/pending orders.', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_placed_email'
                    ),
                    array(
                        'field_name' => 'admin_order_placed_subject',
                        'field_title' => __( 'Subject', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => __( 'New Order Placed', 'tickera-event-ticketing-system' ),
                        'tooltip' => __( 'Subject of the email', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_placed_from_name',
                        'field_title' => __( 'From name', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'blogname' ),
                        'tooltip' => __( 'This name will appear as sent from name in the e-mail', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_placed_from_email',
                        'field_title' => __( 'From email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address you would like your emails to appear as "sent from". ', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_placed_to_email',
                        'field_title' => __( 'To email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address to which you would like to deliver these emails. <br/>You can use multiple email addresses if needed, separated by comma. For example admin1@example.com,admin2@example.com,admin3@example.com', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_placed_message',
                        'field_title' => __( 'Email content', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_get_admin_order_message',
                        'default_value' => __( 'Hello, <br /><br />a new order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> has been placed. <br /><br />You can check the order details here ORDER_ADMIN_URL', 'tickera-event-ticketing-system' ),
                        'field_description' => __( 'Body of the e-mail. You can use the following placeholders (ORDER_ID, ORDER_TOTAL, ORDER_ADMIN_URL, BUYER_NAME)', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_placed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_placed_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    )
                )
            );

            $admin_order_completed_email_fields = apply_filters( 'admin_order_completed_email_fields', array(

                    array(
                        'field_name' => 'admin_send_message',
                        'field_title' => __( 'Enable', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_yes_no_email',
                        'default_value' => 'yes',
                        'tooltip' => __( 'Enable/disable emails sent to the admin email address for completed orders.', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_completed_email'
                    ),
                    array(
                        'field_name' => 'admin_order_subject',
                        'field_title' => __( 'Subject', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => __( 'New Order Completed', 'tickera-event-ticketing-system' ),
                        'tooltip' => __( 'Subject of the e-mail', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_from_name',
                        'field_title' => __( 'From name', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'blogname' ),
                        'tooltip' => __( 'This name will appear as sent from name in the e-mail', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_from_email',
                        'field_title' => __( 'From email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address you would like your emails to appear as "sent from". ', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_to_email',
                        'field_title' => __( 'To email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address to which you would like to deliver these emails. <br/>You can use multiple email addresses if needed, separated by comma. For example admin1@example.com,admin2@example.com,admin3@example.com', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_message',
                        'field_title' => __( 'Email content', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_get_admin_order_message',
                        'default_value' => __( 'Hello, <br /><br />a new order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> has been placed. <br /><br />You can check the order details here ORDER_ADMIN_URL', 'tickera-event-ticketing-system' ),
                        'field_description' => __( 'Body of the e-mail. You can use the following placeholders (ORDER_ID, ORDER_TOTAL, ORDER_ADMIN_URL, BUYER_NAME, ORDER_DETAILS)', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_completed_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    )
                )
            );

            $admin_order_refunded_email_fields = apply_filters( 'admin_order_refunded_email_fields', array(

                    array(
                        'field_name' => 'admin_send_refunded_message',
                        'field_title' => __( 'Enable', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_yes_no_email',
                        'default_value' => 'yes',
                        'tooltip' => __( 'Enable/disable emails sent to the admin email address for refunded orders', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_refunded_email'
                    ),
                    array(
                        'field_name' => 'admin_order_refunded_subject',
                        'field_title' => __( 'Subject', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => __( 'Order Refunded', 'tickera-event-ticketing-system' ),
                        'tooltip' => __( 'Subject of the e-mail', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_refunded_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_refunded_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_refunded_from_name',
                        'field_title' => __( 'From name', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'blogname' ),
                        'tooltip' => __( 'This name will appear as sent from name in the e-mail', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_refunded_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_refunded_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_refunded_from_email',
                        'field_title' => __( 'From email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address you would like your emails to appear as "sent from". ', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_refunded_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_refunded_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_refunded_to_email',
                        'field_title' => __( 'To email address', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => get_option( 'admin_email' ),
                        'tooltip' => __( 'Enter email address to which you would like to deliver these emails. <br/>You can use multiple email addresses if needed, separated by comma. For example admin1@example.com,admin2@example.com,admin3@example.com', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_refunded_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_refunded_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    ),
                    array(
                        'field_name' => 'admin_order_refunded_message',
                        'field_title' => __( 'Email content', 'tickera-event-ticketing-system' ),
                        'field_type' => 'function',
                        'function' => 'tickera_get_admin_order_message',
                        'default_value' => __( 'Hello, <br /><br />An order (ORDER_ID) totalling <strong>ORDER_TOTAL</strong> has been refunded. <br /><br />You can check the order details here ORDER_ADMIN_URL', 'tickera-event-ticketing-system' ),
                        'field_description' => __( 'Body of the e-mail. You can use the following placeholders (ORDER_ID, ORDER_TOTAL, ORDER_ADMIN_URL, BUYER_NAME, ORDER_DETAILS)', 'tickera-event-ticketing-system' ),
                        'section' => 'admin_order_refunded_email',
                        'conditional' => array(
                            'field_name' => 'admin_send_refunded_message',
                            'field_type' => 'radio',
                            'value' => 'no',
                            'action' => 'hide'
                        )
                    )
                )
            );

            $misc_email_fields = array(
                array(
                    'field_name' => 'email_send_type',
                    'field_title' => __( 'Send email via', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_email_send_type',
                    'default_value' => 'wp_mail',
                    'tooltip' => __( 'Select the way you want emails to be sent. If emails sent via default WP Mail option are not getting delivered, try switching to PHP mail option. <br/>If you have issues with delivering mails with both options, check our troubleshooting guide <a href = "https://tickera.com/tickera-documentation/faq/emails-not-delivered-ending-spam/">here</a>', 'tickera-event-ticketing-system' ),
                    'section' => 'misc_email'
                )
            );

            $default_fields = array_merge( $client_order_placed_email_fields, $attendee_order_completed_email_fields, $client_order_completed_email_fields, $admin_order_completed_email_fields, $admin_order_placed_email_fields, $admin_order_refunded_email_fields, $misc_email_fields );
            return apply_filters( 'tc_settings_email_fields', $default_fields );
        }
    }
}
