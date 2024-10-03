<?php
/**
 * Free Orders
 */

namespace Tickera\Gateway;
use Tickera\TC_Gateway_API;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Gateway\TC_Gateway_Free_Orders' ) ) {

    class TC_Gateway_Free_Orders extends TC_Gateway_API {

        var $plugin_name = 'free_orders';
        var $admin_name = '';
        var $public_name = '';
        var $method_img_url = '';
        var $admin_img_url = '';
        var $force_ssl = false;
        var $ipn_url;
        var $automatically_activated = true;
        var $skip_payment_screen = true;

        /**
         * Support for older payment gateway API
         */
        function on_creation() {
            $this->init();
        }

        function init() {
            global $tc;
            $this->skip_payment_screen = apply_filters( $this->plugin_name . '_skip_payment_screen', $this->skip_payment_screen );
            $this->admin_name = $this->get_option( 'admin_name', __( 'Free Orders', 'tickera-event-ticketing-system' ) );
            $this->public_name = $this->get_option( 'public_name', __( 'Free Orders', 'tickera-event-ticketing-system' ) );
            $this->method_img_url = apply_filters( 'tc_gateway_method_img_url', $tc->plugin_url . 'images/gateways/free-orders.png', $this->plugin_name );
            $this->admin_img_url = apply_filters( 'tc_gateway_admin_img_url', $tc->plugin_url . 'images/gateways/small-free-orders.png', $this->plugin_name );
            add_filter( 'tc_redirect_gateway_message', array( &$this, 'custom_redirect_message' ), 10, 1 );
        }

        function custom_redirect_message( $message ) {
            return __( 'Redirecting to the confirmation page...', 'tickera-event-ticketing-system' );
        }

        function payment_form( $cart ) {
            return get_option( 'info' );
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

            $session_payment_info = $tc->session->get( 'tc_payment_info' );
            $tc_payment_info = !is_null( $session_payment_info ) ? array_map( 'sanitize_text_field', $session_payment_info ) : array_map( 'sanitize_text_field', $payment_info );

            $total = (float) $tc_payment_info[ 'total' ];
            $zero_total_status = $this->get_option( 'zero_total_status' );
            $zero_total_status = ( $zero_total_status ) ? $zero_total_status : 'order_received';
            $paid = ( 'order_paid' == $zero_total_status ) ? true : false;

            // Get default status for 100% discount and/or free orders
            if ( $total == 0 ) {
                $paid = ( 'order_paid' == $zero_total_status ) ? true : false;
            }

            $order = tickera_get_order_id_by_name( $order );
            $tc->update_order_payment_status( $order->ID, $paid );
        }

        /**
         * Generate Order Confirmation Page upon success checkout
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
                    $content .= '<p>' . wp_kses_post( __( 'Current order status: <strong>Pending Review</strong>', 'tickera-event-ticketing-system' ) ) . '</p>';
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
                    $content .= '<p>' . wp_kses_post( sprintf( /* translators: 1: Payment gateway name 2: Order total amount */ __( 'Your payment via %1$s for this order totaling <strong>%2$s</strong> is refunded.', 'tickera-event-ticketing-system' ), esc_html( $this->public_name ), esc_html( apply_filters( 'tc_cart_currency_and_format', $order->details->tc_payment_info[ 'total' ] ) ) ) )  . '</p>';
                    break;

            }

            $content = wp_kses_post( apply_filters( 'tc_order_confirmation_message_content', $content, $order, $this->plugin_name ) );
            $content .= '<br /><br />' . $tc->get_setting( 'gateways->free_orders->instructions' );

            $tc->remove_order_session_data();
            $tc->maybe_skip_confirmation_screen( $this, $order );
            return $content;
        }

        function gateway_admin_settings( $settings, $visible ) {
            ?>
            <div id="<?php echo esc_attr( $this->plugin_name ); ?>" class="postbox" <?php echo wp_kses_post( ! $visible ? 'style="display:none;"' : '' ); ?>>
                <h3>
                    <span><?php echo esc_html( sprintf( /* translators: %s: Free Orders Payment Gateway admin name */ __( '%s Settings', 'tickera-event-ticketing-system' ), esc_attr( wp_unslash( $this->admin_name ) ) ) ); ?></span>
                    <span class="description"><?php esc_html_e( 'This method will be used automatically if the order total is 0 (zero). This is the only method which will be shown to buyers in this case - other payment options will be hidden.', 'tickera-event-ticketing-system' ) ?></span>
                </h3>
                <div class="inside">
                    <?php
                    $fields = array(
                        'public_name' => array(
                            'title' => __( 'Public title', 'tickera-event-ticketing-system' ),
                            'type' => 'text',
                            'description' => __( 'Enter the title of this payment method that will be visible to customers on the front end', 'tickera-event-ticketing-system' ),
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
                            'title' => __( 'Payment message', 'tickera-event-ticketing-system' ),
                            'type' => 'wp_editor',
                            'description' => __( 'A message displayed on the order confirmation page when this method is used on the checkout', 'tickera-event-ticketing-system' )
                        ),
                        'zero_total_status' => array(
                            'title' => __( 'Automatic payment status', 'tickera-event-ticketing-system' ),
                            'type' => 'select',
                            'options' => array(
                                'order_received' => __( 'Order Received', 'tickera-event-ticketing-system' ),
                                'order_paid' => __( 'Order Paid', 'tickera-event-ticketing-system' )
                            ),
                            'default' => 'order_received',
                            'description' => __( 'Set the payment status for the orders with 0 (zero) order total.  If you want customers to receive their tickets immediately upon finishing the checkout, select "Order Paid".', 'tickera-event-ticketing-system' )
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

    \Tickera\tickera_register_gateway_plugin( 'Tickera\Gateway\TC_Gateway_Free_Orders', 'free_orders', __( 'Free Orders', 'tickera-event-ticketing-system' ) );
}