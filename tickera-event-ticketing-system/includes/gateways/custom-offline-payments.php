<?php
/**
 * Custom Offline Payments Gateway
 */

namespace Tickera\Gateway;
use Tickera\TC_Gateway_API;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Gateway\TC_Gateway_Custom_Offline_Payments' ) ) {

    class TC_Gateway_Custom_Offline_Payments extends TC_Gateway_API {

        var $plugin_name = 'custom_offline_payments';
        var $admin_name = '';
        var $public_name = '';
        var $method_img_url = '';
        var $admin_img_url = '';
        var $force_ssl = false;
        var $ipn_url;
        var $automatically_activated = false;
        var $skip_payment_screen = false;

        /**
         * Support for older payment gateway API
         */
        function on_creation() {
            $this->init();
        }

        function init() {
            global $tc;

            $this->skip_payment_screen = apply_filters( $this->plugin_name . '_skip_payment_screen', $this->skip_payment_screen );
            $this->admin_name = $this->get_option( 'admin_name', __( 'Offline Payment', 'tickera-event-ticketing-system' ) );
            $this->public_name = $this->get_option( 'public_name', __( 'Cash on Delivery', 'tickera-event-ticketing-system' ) );

            $this->method_img_url = apply_filters( 'tc_gateway_method_img_url', $tc->plugin_url . 'images/gateways/custom-offline-payments.png', $this->plugin_name );
            $this->admin_img_url = apply_filters( 'tc_gateway_admin_img_url', $tc->plugin_url . 'images/gateways/small-custom-offline-payments.png', $this->plugin_name );

            add_action( 'tc_order_created', array( &$this, 'send_payment_instructions' ), 10, 5 );
            add_filter( $this->plugin_name . '_instructions', array( &$this, 'modify_instruction_message' ), 10, 2 );
        }

        function payment_form( $cart ) {
            return $this->get_option( 'info' );
        }

        function modify_instruction_message( $message, $order_id ) {

            if ( ! is_int( $order_id ) ) {
                $order = tickera_get_order_id_by_name( $order_id );
                $order = new \Tickera\TC_Order( $order->ID );

            } else {
                $order = new \Tickera\TC_Order( $order_id );
            }

            $payment_info = get_post_meta( $order->id, 'tc_payment_info', true );
            $cart_info = get_post_meta( $order->id, 'tc_cart_info', true );
            $buyer_name = $cart_info[ 'buyer_data' ][ 'first_name_post_meta' ] . ' ' . $cart_info[ 'buyer_data' ][ 'last_name_post_meta' ];

            $placeholders = array( 'ORDER_ID', 'ORDER_TOTAL', 'BUYER_NAME' );
            $placeholder_values = array( strtoupper( $order->details->post_title ), apply_filters( 'tc_cart_currency_and_format', $payment_info[ 'total' ] ), $buyer_name );

            $message = str_replace( $placeholders, $placeholder_values, $message );

            return $message;
        }

        function send_payment_instructions( $order_id, $status, $cart_contents, $cart_info, $payment_info ) {

            global $order_instructions_sent;

            if ( $payment_info[ 'gateway_private_name' ] == $this->admin_name ) {

                $send_instructions = $this->get_option( 'instructions_email', 'no' ) == 'yes';

                if ( 'yes' == $send_instructions && 'order_received' == $status ) {

                    add_filter( 'wp_mail_content_type', function() {
                        return 'text/html';
                    } );

                    add_filter( 'wp_mail_from', 'tickera_client_email_from_email', 999 );
                    add_filter( 'wp_mail_from_name', 'tickera_client_email_from_name', 999 );

                    $to = $this->buyer_info( 'email' );
                    $message = apply_filters( $this->plugin_name . '_instructions', $this->get_option( 'instructions' ), $order_id );
                    $subject = $this->get_option( 'instructions_email_subject' );

                    if ( $order_instructions_sent !== $this->buyer_info( 'email' ) && ! empty( trim( $message ) ) ) {
                        @wp_mail( sanitize_email( $to ), sanitize_text_field( stripslashes( $subject ) ), wp_kses_post( apply_filters( 'tc_order_created_client_email_message', stripcslashes( wpautop( $message ) ) ) ), apply_filters( 'tc_order_created_client_email_headers', '' ) );
                        $order_instructions_sent = $this->buyer_info( 'email' );
                    }
                }
            }
        }

        function process_payment( $cart ) {

            global $tc;

            tickera_final_cart_check( $cart );
            $this->save_cart_info();
            $order_id = $tc->generate_order_id();

            $payment_info = [];
            $payment_info[ 'currency' ] = $tc->get_cart_currency();
            $payment_info = $this->save_payment_info( $payment_info );
            $tc->create_order( $order_id, $this->cart_contents(), $this->cart_info(), $payment_info, false );
            tickera_redirect( $tc->get_confirmation_slug( true, $order_id ), true );
        }

        function order_confirmation( $order, $payment_info = '', $cart_info = '' ) {

            global $tc;

            $session = $tc->session->get();
            $tc_payment_info = isset( $session[ 'tc_payment_info' ] ) ? tickera_sanitize_array( $session[ 'tc_payment_info' ] ) : tickera_sanitize_array( $payment_info );
            $total = (float) $tc_payment_info[ 'total' ];
            $automatic_status = $this->get_option( 'automatic_status' );
            $paid = false;

            // Get default status for 100% discount and/or free orders
            if ( $total > 0 ) {
                $paid = ( 'order_paid' == $automatic_status ) ? true : false;
            }

            $order = tickera_get_order_id_by_name( $order );
            $tc->update_order_payment_status( $order->ID, $paid );
        }

        /**
         * Generate Order Confirmation Page upon success checkout
         *
         * @param $order
         * @param string $cart_info
         * @return string
         */
        function order_confirmation_message( $order, $cart_info = '' ) {

            global $tc;

            $order = tickera_get_order_id_by_name( $order );
            $order = new \Tickera\TC_Order( $order->ID );
            $content = '';

            switch ( $order->details->post_status ) {

                case 'order_received':
                    $content .= '<p>' . wp_kses_post( sprintf( /* translators: 1: Payment gateway name 2: Order total amount */ __( 'Your payment via %1$s for this order totaling <strong>%2$s</strong> is not yet complete.', 'tickera-event-ticketing-system' ), esc_html( $this->public_name ), esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) ) ) ) . '</p>';
                    $content .= '<p>' . wp_kses_post( __( 'Current order status: <strong>Pending Payment</strong>', 'tickera-event-ticketing-system' ) ) . '</p>';
                    break;

                case 'order_fraud':
                    $content .= '<p>' . esc_html__( 'Your payment is under review. We will back to you soon.', 'tickera-event-ticketing-system' ) . '</p>';
                    break;

                case 'order_paid':
                    $content .= '<p>' . wp_kses_post( sprintf( /* translators: 1: Payment gateway name 2: Order total amount */ __( 'Your payment via %1$s for this order totaling <strong>%2$s</strong> is complete.', 'tickera-event-ticketing-system' ), esc_html( $this->public_name ), esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) ) ) ) . '</p>';
                    break;

                case 'order_cancelled':
                    $content .= '<p>' . wp_kses_post( sprintf( /* translators: 1: Payment gateway name 2: Order total amount */ __( 'Your payment via %1$s for this order totaling <strong>%2$s</strong> is cancelled.', 'tickera-event-ticketing-system' ), esc_html( $this->public_name ), esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) ) ) ) . '</p>';
                    break;

                case 'order_refunded':
                    $content .= '<p>' . wp_kses_post( sprintf( /* translators: 1: Payment gateway name 2: Order total amount */  __( 'Your payment via %1$s for this order totaling <strong>%2$s</strong> is refunded.', 'tickera-event-ticketing-system' ), esc_html( $this->public_name ), esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) ) ) ) . '</p>';
                    break;

            }

            $content = wp_kses_post( apply_filters( 'tc_order_confirmation_message_content', $content, $order, $this->plugin_name ) );
            $content .= '<br /><br />' . wp_kses_post( apply_filters( $this->plugin_name . '_instructions', $tc->get_setting( 'gateways->custom_offline_payments->instructions' ), $order->details->ID ) );

            $tc->remove_order_session_data();
            $tc->maybe_skip_confirmation_screen( $this, $order );

            return $content;
        }

        function gateway_admin_settings( $settings, $visible ) {
            ?>
            <div id="<?php echo esc_attr( $this->plugin_name ); ?>" class="postbox" <?php echo wp_kses_post( ! $visible ? 'style="display:none;"' : '' ); ?>>
                <h3>
                    <span><?php echo esc_html( sprintf( /* translators: %s: Custom Offline Payment Gateway admin name */ __( '%s Settings', 'tickera-event-ticketing-system' ), esc_html( wp_unslash( $this->admin_name ) ) ) ); ?></span>
                    <span class="description"><?php esc_html_e( 'A simple pass-thru payment method for accepting offline payments (Cash on Delivery, Money Orders, Bank Deposits, Cheques etc.)', 'tickera-event-ticketing-system' ) ?></span>
                </h3>
                <div class="inside">
                    <?php
                    $fields = array(
                        'public_name' => array(
                            'title' => __( 'Public title', 'tickera-event-ticketing-system' ),
                            'type' => 'text',
                            'description' => __( 'Enter the title of this payment method that will be visible to customers on the front end (eg. Cash on delivery, Checque payment, etc.)', 'tickera-event-ticketing-system' ),
                            'default' => $this->public_name
                        ),
                        'admin_name' => array(
                            'title' => __( 'Admin title', 'tickera-event-ticketing-system' ),
                            'type' => 'text',
                            'description' => __( 'Enter the title of this pamyment method that will be used in the back end (admin) area of your website', 'tickera-event-ticketing-system' ),
                            'default' => $this->admin_name
                        ),
                        'info' => array(
                            'title' => __( 'Payment method additional info', 'tickera-event-ticketing-system' ),
                            'type' => 'wp_editor',
                            'description' => __( 'Additional information that will be visible to the customers on the front end when they select this payment method', 'tickera-event-ticketing-system' )
                        ),
                        'instructions' => array(
                            'title' => __( 'Payment instruction', 'tickera-event-ticketing-system' ),
                            'type' => 'wp_editor',
                            'description' => __( 'The instruction that will be displayed to customers once they finish the checkout using this payment method. You can use the following placeholders: ORDER_ID, ORDER_TOTAL, BUYER_NAME', 'tickera-event-ticketing-system' )
                        ),
                        'instructions_email' => array(
                            'title' => __( 'Send instruction via email', 'tickera-event-ticketing-system' ),
                            'type' => 'select',
                            'options' => array(
                                'no' => __( 'No', 'tickera-event-ticketing-system' ),
                                'yes' => __( 'Yes', 'tickera-event-ticketing-system' )
                            ),
                            'default' => 'no',
                            'description' => __( 'Choose Yes if you want to send an email with the payment instruction to the customer once they finish their order. The e-mail will be sent only the order has a status "Order Received".', 'tickera-event-ticketing-system' )
                        ),
                        'instructions_email_subject' => array(
                            'title' => __( 'Instruction email subject', 'tickera-event-ticketing-system' ),
                            'type' => 'text',
                            'default' => __( 'Payment instruction', 'tickera-event-ticketing-system' )
                        ),
                        'automatic_status' => array(
                            'title' => __( 'Automatic payment status', 'tickera-event-ticketing-system' ),
                            'type' => 'select',
                            'options' => array(
                                'order_received' => __( 'Order Received', 'tickera-event-ticketing-system' ),
                                'order_paid' => __( 'Order Paid', 'tickera-event-ticketing-system' )
                            ),
                            'default' => 'order_received',
                            'description' => __( 'Set the payment status for the orders made using this payment method. If you want customers to receive their tickets immediately upon finishing the checkout using this payment method, select "Order Paid"', 'tickera-event-ticketing-system' )
                        ),
                        'admin_gateway' => array(
                            'title' => __( 'Visible to admins only', 'tickera-event-ticketing-system' ),
                            'type' => 'select',
                            'options' => array(
                                'no' => __( 'No', 'tickera-event-ticketing-system' ),
                                'yes' => __( 'Yes', 'tickera-event-ticketing-system' )
                            ),
                            'default' => 'no',
                            'description' => __( 'Select whether you want this payment method to be available to website admins only', 'tickera-event-ticketing-system' )
                        ),
                    );

                    $form = new \Tickera\TC_Form_Fields_API( $fields, 'tc', 'gateways', $this->plugin_name );
                    ?>
                    <table class="form-table">
                        <?php $form->admin_options(); ?>
                    </table>
                </div>
            </div>
            <?php
        }
    }

    \Tickera\tickera_register_gateway_plugin( 'Tickera\Gateway\TC_Gateway_Custom_Offline_Payments', 'custom_offline_payments', __( 'Offline Payments', 'tickera-event-ticketing-system' ) );
}