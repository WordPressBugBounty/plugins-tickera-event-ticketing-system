<?php
/**
 * 2Checkout - Payment Gateway
 */

namespace Tickera\Gateway;
use Tickera\TC_Gateway_API;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\Gateway\TC_Gateway_2Checkout' ) ) {

    class TC_Gateway_2Checkout extends TC_Gateway_API {

        var $plugin_name = 'checkout';
        var $admin_name = '';
        var $public_name = '';
        var $method_img_url = '';
        var $admin_img_url = '';
        var $force_ssl = false;
        var $ipn_url;
        var $API_Username, $API_Password, $SandboxFlag, $returnURL, $API_Endpoint, $version, $currency, $locale;
        var $currencies = array();
        var $automatically_activated = false;
        var $skip_payment_screen = true;

        /**
         * Support for older payment gateway API
         */
        function on_creation() {
            $this->init();
        }

        function init() {
            global $tc;

            $this->admin_name = __( '2Checkout', 'tickera-event-ticketing-system' );
            $this->public_name = __( '2Checkout', 'tickera-event-ticketing-system' );

            $this->method_img_url = apply_filters( 'tc_gateway_method_img_url', $tc->plugin_url . 'images/gateways/2checkout.png', $this->plugin_name );
            $this->admin_img_url = apply_filters( 'tc_gateway_admin_img_url', $tc->plugin_url . 'images/gateways/small-2checkout.png', $this->plugin_name );

            $this->currency = $this->get_option( 'currency', 'USD', '2checkout' );
            $this->API_Username = $this->get_option( 'sid', '', '2checkout' );
            $this->API_Password = $this->get_option( 'secret_word', '', '2checkout' );
            $this->SandboxFlag = $this->get_option( 'mode', 'sandbox', '2checkout' );

            $currencies = array(
                "AED" => __( 'AED - United Arab Emirates Dirham', 'tickera-event-ticketing-system' ),
                "ARS" => __( 'ARS - Argentina Peso', 'tickera-event-ticketing-system' ),
                "AUD" => __( 'AUD - Australian Dollar', 'tickera-event-ticketing-system' ),
                "BRL" => __( 'BRL - Brazilian Real', 'tickera-event-ticketing-system' ),
                "CAD" => __( 'CAD - Canadian Dollar', 'tickera-event-ticketing-system' ),
                "CHF" => __( 'CHF - Swiss Franc', 'tickera-event-ticketing-system' ),
                "DKK" => __( 'DKK - Danish Krone', 'tickera-event-ticketing-system' ),
                "EUR" => __( 'EUR - Euro', 'tickera-event-ticketing-system' ),
                "GBP" => __( 'GBP - British Pound', 'tickera-event-ticketing-system' ),
                "HKD" => __( 'HKD - Hong Kong Dollar', 'tickera-event-ticketing-system' ),
                "INR" => __( 'INR - Indian Rupee', 'tickera-event-ticketing-system' ),
                "ILS" => __( 'ILS - Israeli New Shekel', 'tickera-event-ticketing-system' ),
                "LTL" => __( 'LTL - Lithuanian Litas', 'tickera-event-ticketing-system' ),
                "JPY" => __( 'JPY - Japanese Yen', 'tickera-event-ticketing-system' ),
                "MYR" => __( 'MYR - Malaysian Ringgit', 'tickera-event-ticketing-system' ),
                "MXN" => __( 'MXN - Mexican Peso', 'tickera-event-ticketing-system' ),
                "NOK" => __( 'NOK - Norwegian Krone', 'tickera-event-ticketing-system' ),
                "NZD" => __( 'NZD - New Zealand Dollar', 'tickera-event-ticketing-system' ),
                "PHP" => __( 'PHP - Philippine Peso', 'tickera-event-ticketing-system' ),
                "RON" => __( 'RON - Romanian New Leu', 'tickera-event-ticketing-system' ),
                "RUB" => __( 'RUB - Russian Ruble', 'tickera-event-ticketing-system' ),
                "SEK" => __( 'SEK - Swedish Krona', 'tickera-event-ticketing-system' ),
                "SGD" => __( 'SGD - Singapore Dollar', 'tickera-event-ticketing-system' ),
                "TRY" => __( 'TRY - Turkish Lira', 'tickera-event-ticketing-system' ),
                "USD" => __( 'USD - U.S. Dollar', 'tickera-event-ticketing-system' ),
                "ZAR" => __( 'ZAR - South African Rand', 'tickera-event-ticketing-system' ),
                "AFN" => __( 'AFN - Afghan Afghani', 'tickera-event-ticketing-system' ),
                "ALL" => __( 'ALL - Albanian Lek', 'tickera-event-ticketing-system' ),
                "AZN" => __( 'AZN - Azerbaijani an Manat', 'tickera-event-ticketing-system' ),
                "BSD" => __( 'BSD - Bahamian Dollar', 'tickera-event-ticketing-system' ),
                "BDT" => __( 'BDT - Bangladeshi Taka', 'tickera-event-ticketing-system' ),
                "BBD" => __( 'BBD - Barbados Dollar', 'tickera-event-ticketing-system' ),
                "BZD" => __( 'BZD - Belizean dollar', 'tickera-event-ticketing-system' ),
                "BMD" => __( 'BMD - Bermudian Dollar', 'tickera-event-ticketing-system' ),
                "BOB" => __( 'BOB - Bolivian Boliviano', 'tickera-event-ticketing-system' ),
                "BWP" => __( 'BWP - Botswana Pula', 'tickera-event-ticketing-system' ),
                "BND" => __( 'BND - Brunei Dollar', 'tickera-event-ticketing-system' ),
                "BGN" => __( 'BGN - Bulgarian Lev', 'tickera-event-ticketing-system' ),
                "CLP" => __( 'CLP - Chilean Peso', 'tickera-event-ticketing-system' ),
                "CNY" => __( 'CNY - Chinese Yuan Renminbi', 'tickera-event-ticketing-system' ),
                "COP" => __( 'COP - Colombian Peso', 'tickera-event-ticketing-system' ),
                "CRC" => __( 'CRC - Costa Rican Colon', 'tickera-event-ticketing-system' ),
                "HRK" => __( 'HRK - Croatian Kuna', 'tickera-event-ticketing-system' ),
                "CZK" => __( 'CZK - Czech Republic Koruna', 'tickera-event-ticketing-system' ),
                "DOP" => __( 'DOP - Dominican Peso', 'tickera-event-ticketing-system' ),
                "XCD" => __( 'XCD - East Caribbean Dollar', 'tickera-event-ticketing-system' ),
                "EGP" => __( 'EGP - Egyptian Pound', 'tickera-event-ticketing-system' ),
                "FJD" => __( 'FJD - Fiji Dollar', 'tickera-event-ticketing-system' ),
                "GTQ" => __( 'GTQ - Guatemala Quetzal', 'tickera-event-ticketing-system' ),
                "HNL" => __( 'HNL - Honduras Lempira', 'tickera-event-ticketing-system' ),
                "HUF" => __( 'HUF - Hungarian Forint', 'tickera-event-ticketing-system' ),
                "IDR" => __( 'IDR - Indonesian Rupiah', 'tickera-event-ticketing-system' ),
                "JMD" => __( 'JMD - Jamaican Dollar', 'tickera-event-ticketing-system' ),
                "KZT" => __( 'KZT - Kazakhstan Tenge', 'tickera-event-ticketing-system' ),
                "KES" => __( 'KES - Kenyan Shilling', 'tickera-event-ticketing-system' ),
                "LAK" => __( 'LAK - Laosian kip', 'tickera-event-ticketing-system' ),
                "MMK" => __( 'MMK - Myanmar Kyat', 'tickera-event-ticketing-system' ),
                "LBP" => __( 'LBP - Lebanese Pound', 'tickera-event-ticketing-system' ),
                "LRD" => __( 'LRD - Liberian Dollar', 'tickera-event-ticketing-system' ),
                "MOP" => __( 'MOP - Macanese Pataca', 'tickera-event-ticketing-system' ),
                "MVR" => __( 'MVR - Maldiveres Rufiyaa', 'tickera-event-ticketing-system' ),
                "MRO" => __( 'MRO - Mauritanian Ouguiya', 'tickera-event-ticketing-system' ),
                "MUR" => __( 'MUR - Mauritius Rupee', 'tickera-event-ticketing-system' ),
                "MAD" => __( 'MAD - Moroccan Dirham', 'tickera-event-ticketing-system' ),
                "NPR" => __( 'NPR - Nepalese Rupee', 'tickera-event-ticketing-system' ),
                "TWD" => __( 'TWD - New Taiwan Dollar', 'tickera-event-ticketing-system' ),
                "NIO" => __( 'NIO - Nicaraguan Cordoba', 'tickera-event-ticketing-system' ),
                "PKR" => __( 'PKR - Pakistan Rupee', 'tickera-event-ticketing-system' ),
                "PGK" => __( 'PGK - New Guinea kina', 'tickera-event-ticketing-system' ),
                "PEN" => __( 'PEN - Peru Nuevo Sol', 'tickera-event-ticketing-system' ),
                "PLN" => __( 'PLN - Poland Zloty', 'tickera-event-ticketing-system' ),
                "QAR" => __( 'QAR - Qatari Rial', 'tickera-event-ticketing-system' ),
                "WST" => __( 'WST - Samoan Tala', 'tickera-event-ticketing-system' ),
                "SAR" => __( 'SAR - Saudi Arabian riyal', 'tickera-event-ticketing-system' ),
                "SCR" => __( 'SCR - Seychelles Rupee', 'tickera-event-ticketing-system' ),
                "SBD" => __( 'SBD - Solomon Islands Dollar', 'tickera-event-ticketing-system' ),
                "KRW" => __( 'KRW - South Korean Won', 'tickera-event-ticketing-system' ),
                "LKR" => __( 'LKR - Sri Lanka Rupee', 'tickera-event-ticketing-system' ),
                "CHF" => __( 'CHF - Switzerland Franc', 'tickera-event-ticketing-system' ),
                "SYP" => __( 'SYP - Syrian Arab Republic Pound', 'tickera-event-ticketing-system' ),
                "THB" => __( 'THB - Thailand Baht', 'tickera-event-ticketing-system' ),
                "TOP" => __( 'TOP - Tonga Pa&#x27;anga', 'tickera-event-ticketing-system' ),
                "TTD" => __( 'TTD - Trinidad and Tobago Dollar', 'tickera-event-ticketing-system' ),
                "UAH" => __( 'UAH - Ukraine Hryvnia', 'tickera-event-ticketing-system' ),
                "VUV" => __( 'VUV - Vanuatu Vatu', 'tickera-event-ticketing-system' ),
                "VND" => __( 'VND - Vietnam Dong', 'tickera-event-ticketing-system' ),
                "XOF" => __( 'XOF - West African CFA Franc BCEAO', 'tickera-event-ticketing-system' ),
                "YER" => __( 'YER - Yemeni Rial', 'tickera-event-ticketing-system' ),
            );

            $this->currencies = $currencies;
        }

        function payment_form( $cart ) {

        }

        function process_payment( $cart ) {

            global $tc;
            tickera_final_cart_check( $cart );
            $this->save_cart_info();

            if ( $this->SandboxFlag == 'sandbox' ) {
                $url = 'https://www.2checkout.com/checkout/purchase';
            } else {
                $url = 'https://www.2checkout.com/checkout/purchase';
            }

            $order_id = $tc->generate_order_id();

            $params = array();
            $params[ 'total' ] = $this->total();
            $params[ 'sid' ] = $this->API_Username;
            $params[ 'cart_order_id' ] = $order_id;
            $params[ 'merchant_order_id' ] = $order_id;
            $params[ 'return_url' ] = $tc->get_confirmation_slug( true, $order_id );
            $params[ 'x_receipt_link_url' ] = $tc->get_confirmation_slug( true, $order_id );
            $params[ 'skip_landing' ] = '1';
            $params[ 'fixed' ] = 'Y';
            $params[ 'currency_code' ] = $this->currency;
            $params[ 'mode' ] = '2CO';
            $params[ 'card_holder_name' ] = $this->buyer_info( 'full_name' );
            $params[ 'email' ] = $this->buyer_info( 'email' );

            if ( $this->SandboxFlag == 'sandbox' ) {
                $params[ 'demo' ] = 'Y';
            }

            $params[ "li_0_type" ] = "product";
            $params[ "li_0_name" ] = $this->cart_items();
            $params[ "li_0_price" ] = $this->total();
            $params[ "li_0_tangible" ] = 'N';

            $param_list = array();

            foreach ( $params as $k => $v ) {
                $param_list[] = "{$k}=" . rawurlencode( $v );
            }

            $param_str = implode( '&', $param_list );
            $paid = false;
            $payment_info = $this->save_payment_info();
            $tc->create_order( $order_id, $this->cart_contents(), $this->cart_info(), $payment_info, $paid );

            tickera_redirect( "{$url}?{$param_str}", true, false );
            exit( 0 );
        }

        function order_confirmation( $order, $payment_info = '', $cart_info = '' ) {
            global $tc;

            $total = (float) $_REQUEST[ 'total' ];

            $hashSecretWord = $this->get_option( 'secret_word', '', '2checkout' ); //2Checkout Secret Word
            $hashSid = $this->get_option( 'sid', '', '2checkout' );
            $hashTotal = $total; // Sale total to validate against
            $hashOrder = sanitize_text_field( $_REQUEST[ 'order_number' ] ); // 2Checkout Order Number

            if ( $this->SandboxFlag == 'sandbox' ) {
                $StringToHash = strtoupper( md5( $hashSecretWord . $hashSid . 1 . $hashTotal ) );
            } else {
                $StringToHash = strtoupper( md5( $hashSecretWord . $hashSid . $hashOrder . $hashTotal ) );
            }

            if ( $StringToHash != $_REQUEST[ 'key' ] ) {
                $tc->update_order_status( $order->ID, 'order_fraud' );
            } else {
                $paid = true;
                $order = tickera_get_order_id_by_name( $order );
                $tc->update_order_payment_status( $order->ID, true );
            }

            $this->ipn();
        }

        function gateway_admin_settings( $settings, $visible ) {
            global $tc;
            ?>
            <div id="<?php echo esc_attr( $this->plugin_name ); ?>"
                 class="postbox" <?php echo wp_kses_post( ! $visible ? 'style="display:none;"' : '' ); ?>>
                <h3>
                    <span><?php echo wp_kses_post( sprintf( /* translators: %s: 2Checkout Payment Gateway admin name */ __( '%s Settings', 'tickera-event-ticketing-system' ), esc_html( $this->admin_name ) ) ); ?></span>
                    <span class="description"><?php echo wp_kses_post( __( 'Sell your tickets via <a target="_blank" href="https://www.2checkout.com/referral?r=95d26f72d1">2Checkout.com</a>', 'tickera-event-ticketing-system' ) ) ?></span>
                </h3>
                <div class="inside">
                    <?php
                    $fields = array(
                        'mode' => array(
                            'title' => __( 'Mode', 'tickera-event-ticketing-system' ),
                            'type' => 'select',
                            'options' => array(
                                'sandbox' => __( 'Sandbox / Test', 'tickera-event-ticketing-system' ),
                                'live' => __( 'Live', 'tickera-event-ticketing-system' )
                            ),
                            'default' => 'sandbox',
                        ),
                        'sid' => array(
                            'title' => __( 'Seller ID', 'tickera-event-ticketing-system' ),
                            'type' => 'text',
                            'description' => __( 'Login to your 2Checkout dashboard to obtain the seller ID and secret word. <a target="_blank" href="http://help.2checkout.com/articles/FAQ/Where-do-I-set-up-the-Secret-Word/">Instructions &raquo;</a>', 'tickera-event-ticketing-system' )
                        ),
                        'secret_word' => array(
                            'title' => __( 'Secret word', 'tickera-event-ticketing-system' ),
                            'type' => 'text',
                            'description' => '',
                            'default' => 'tango'
                        ),
                        'currency' => array(
                            'title' => __( 'Currency', 'tickera-event-ticketing-system' ),
                            'type' => 'select',
                            'options' => $this->currencies,
                            'default' => 'USD',
                        ),
                    );
                    $form = new \Tickera\TC_Form_Fields_API( $fields, 'tc', 'gateways', '2checkout' );
                    ?>
                    <table class="form-table">
                        <?php $form->admin_options(); ?>
                    </table>
                </div>
            </div>
            <?php
        }

        function ipn() {

            global $tc;

            if ( isset( $_REQUEST[ 'message_type' ] ) && $_REQUEST[ 'message_type' ] == 'INVOICE_STATUS_CHANGED' ) {

                $sale_id = sanitize_text_field( $_REQUEST[ 'sale_id' ] ); // Just for calculating hash
                $tco_vendor_order_id = sanitize_text_field( $_REQUEST[ 'vendor_order_id' ] ); // Order "name"
                $total = sanitize_text_field( $_REQUEST[ 'invoice_list_amount' ] );

                $order_id = tickera_get_order_id_by_name( $tco_vendor_order_id ); // Get order id from order name
                $order_id = $order_id->ID;
                $order = new \Tickera\TC_Order( $order_id );

                if ( ! $order ) {
                    header( 'HTTP/1.0 404 Not Found' );
                    header( 'Content-type: text/plain; charset=UTF-8' );
                    esc_html_e( 'Invoice not found', 'tickera-event-ticketing-system' );
                    exit;
                }

                $hash = md5( $sale_id . $this->get_option( 'sid', '', '2checkout' ) . sanitize_text_field( $_REQUEST[ 'invoice_id' ] ) . $this->get_option( 'sid', 'secret_word', '2checkout' ) );

                if ( $_REQUEST[ 'md5_hash' ] != strtolower( $hash ) ) {
                    header( 'HTTP/1.0 403 Forbidden' );
                    header( 'Content-type: text/plain; charset=UTF-8' );
                    esc_html_e( "2Checkout hash key doesn't match", 'tickera-event-ticketing-system' );
                    exit;
                }

                if ( strtolower( $_REQUEST[ 'invoice_status' ] ) != "deposited" ) {
                    header( 'HTTP/1.0 200 OK' );
                    header( 'Content-type: text/plain; charset=UTF-8' );
                    esc_html_e( 'Waiting for deposited invoice status.', 'tickera-event-ticketing-system' );
                    exit;
                }

                if ( intval( round( $total, 2 ) ) >= round( $order->details->tc_payment_info[ 'total' ], 2 ) ) {
                    $tc->update_order_payment_status( $order_id, true );
                    header( 'HTTP/1.0 200 OK' );
                    header( 'Content-type: text/plain; charset=UTF-8' );
                    esc_html_e( 'Order completed and verified.', 'tickera-event-ticketing-system' );
                    exit;
                } else {
                    $tc->update_order_status( $order_id, 'order_fraud' );
                    header( 'HTTP/1.0 200 OK' );
                    header( 'Content-type: text/plain; charset=UTF-8' );
                    esc_html_e( 'Fraudulent order detected and changed status.', 'tickera-event-ticketing-system' );
                    exit;
                }
            }
        }

    }

    \Tickera\tickera_register_gateway_plugin( 'Tickera\Gateway\TC_Gateway_2Checkout', 'checkout', __( '2Checkout', 'tickera-event-ticketing-system' ) );
}