<?php

namespace Tickera;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

if ( ! class_exists( 'Tickera\TC_Settings_General' ) ) {

    class TC_Settings_General {

        function __construct() {}

        function TC_Settings_General() {
            $this->__construct();
        }

        function get_settings_general_sections() {

            $sections = array(
                array(
                    'name' => 'store_settings',
                    'title' => __( 'Store Settings', 'tickera-event-ticketing-system' ),
                    'description' => '',
                ),
                array(
                    'name' => 'page_settings',
                    'title' => __( 'Pages', 'tickera-event-ticketing-system' ),
                    'description' => '',
                ),
                array(
                    'name' => 'menu_settings',
                    'title' => __( 'Menu', 'tickera-event-ticketing-system' ),
                    'description' => '',
                ),
                array(
                    'name' => 'miscellaneous_settings',
                    'title' => __( 'Miscellaneous', 'tickera-event-ticketing-system' ),
                    'description' => '',
                )
            );

            return apply_filters( 'tc_settings_general_sections', $sections );
        }

        function get_settings_general_fields() {

            $license_settings_default_fields = [];

            if ( ! defined( 'TC_LCK' ) && ! defined( 'TC_NU' ) ) {
                $license_settings_default_fields = array(
                    array(
                        'field_name' => 'license_key',
                        'field_title' => __( 'License Key', 'tickera-event-ticketing-system' ),
                        'field_type' => 'option',
                        'default_value' => '',
                        'section' => 'license',
                        'tooltip' => __( 'License Key is required if you want to have plugin updates. You can obtain the key from you account page.', 'tickera-event-ticketing-system' ),
                    ),
                );
            }

            $store_settings_default_fields = array(
                array(
                    'field_name' => 'currencies',
                    'field_title' => __( 'Currency', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_global_currencies',
                    'default_value' => 'USD',
                    'tooltip' => __( 'Currency used for display purposes. You have to match gateway currency with the one you have entered here.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'currency_symbol',
                    'field_title' => __( 'Currency symbol', 'tickera-event-ticketing-system' ),
                    'field_type' => 'option',
                    'default_value' => '$',
                    'tooltip' => __( 'Enter desired currency symbol (eg. $) which will be shown instead of the currency ISO code (eg. USD)', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'currency_position',
                    'field_title' => __( 'Currency position', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_currency_positions',
                    'tooltip' => '',
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'price_format',
                    'field_title' => __( 'Price format', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_price_formats',
                    'tooltip' => '',
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'show_tax_rate',
                    'field_title' => __( 'Show tax in cart', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'Show tax in customer\'s cart. You should hide tax if you won\'t be using it.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'tax_rate',
                    'field_title' => __( 'Tax rate (%)', 'tickera-event-ticketing-system' ),
                    'field_type' => 'option',
                    'default_value' => '0',
                    'tooltip' => __( 'Empty or zero means that no tax will be applied on orders', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_tax_rate',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    ),
                    'required' => true,
                    'number' => true
                ),
                array(
                    'field_name' => 'tax_inclusive',
                    'field_title' => __( 'Prices inclusive of tax', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'All prices set for tickets will be inclusive of tax.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_tax_rate',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'tax_before_fees',
                    'field_title' => __( 'Apply tax before fees', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'As default, tax is applied based from the total amount (including fees). Setting the option to yes, tax will be applied based from the subtotal amount (excluding fees).', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_tax_rate',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'tax_label',
                    'field_title' => __( 'Tax label', 'tickera-event-ticketing-system' ),
                    'field_type' => 'option',
                    'default_value' => 'Tax',
                    'tooltip' => __( 'Enter the label you would like to use for the tax on your website.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_tax_rate',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'use_global_fees',
                    'field_title' => __( 'Use global fees', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'If set to Yes, each ticket type will have the same ticket fee. If set to No, ticket fees could be defined for each ticket individually.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'global_fee_type',
                    'field_title' => __( 'Global fee type', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_global_fee_type',
                    'default_value' => 'percentage',
                    'tooltip' => __( 'Set the type for global fees.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'use_global_fees',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'global_fee_scope',
                    'field_title' => __( 'Global fee scope', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_global_fee_scope',
                    'default_value' => 'ticket',
                    'tooltip' => __( 'Set the scope to which global fees should be applied. ', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'use_global_fees',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'global_fee_value',
                    'field_title' => __( 'Global fee (value)', 'tickera-event-ticketing-system' ),
                    'field_type' => 'option',
                    'default_value' => '0',
                    'tooltip' => __( 'Example: 10. Value would be percentage of fixed based on the option selected above.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'use_global_fees',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    ),
                    'required' => true,
                    'number' => true
                ),
                array(
                    'field_name' => 'show_fees',
                    'field_title' => __( 'Show fees', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'Select whether to show fees in cart or not. You may hide fees if you won\'t be using using this feature.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'fees_label',
                    'field_title' => __( 'Fees label', 'tickera-event-ticketing-system' ),
                    'field_type' => 'option',
                    'default_value' => 'Fees',
                    'tooltip' => __( 'Set the label for the fees that will be used in the cart', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_fees',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'force_login',
                    'field_title' => __( 'Force login', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Enable this option to force users register and log in to be able to purchase and/or download tickets', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'show_owner_fields',
                    'field_title' => __( 'Show attendee fields', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'Collect attendee information on checkout. If this option is disabled, attendee info fields will not be displayed and attendee info won\'t be collected', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'show_attendee_first_and_last_name_fields',
                    'field_title' => __( 'Show attendee first and last name fields', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'Show/hide attendee first and last name fields on checkout. If this option is disabled, attendee first and last name will not be collected. ', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_owner_fields',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'first_name_field_required',
                    'field_title' => __( 'First name field required', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'Require attendee first name field on checkout.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_attendee_first_and_last_name_fields',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'last_name_field_required',
                    'field_title' => __( 'Last name field required', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'Require attendee last name field on checkout.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_attendee_first_and_last_name_fields',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'show_owner_email_field',
                    'field_title' => __( 'Show attendee email field', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Show/hide email field for attendees on the checkout. Must be enabled if you want to deliver emails to attendees', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings',
                    'conditional' => array(
                        'field_name' => 'show_owner_fields',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'email_verification_buyer_owner',
                    'field_title' => __( 'E-mail verification', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Enable to verify if buyer and attendee emails are same or not', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'show_discount_field',
                    'field_title' => __( 'Show discount code', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'Show / Hide discount code field on the cart page. Disable if you won\'t be using adny discount codes.', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'skip_payment_summary_page',
                    'field_title' => __( 'Skip payment page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Skip payment page if there is only one payment gateway active (works only with selected payment gateways like 2Checkout, PayPal standard, Free Orders, PayUMoney and VoguePay).', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
                array(
                    'field_name' => 'allow_global_ticket_checkout',
                    'field_title' => __( 'Allow ticket check-out', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Globally allow ticket check-out. When an attendee initially scans a ticket, it will be recorded as check-in. Scanning the ticket for the second time will mark the ticket as check-out. Useful if you need to keep track on number of attendees currently in the venue in Checkinera apps', 'tickera-event-ticketing-system' ),
                    'section' => 'store_settings'
                ),
            );

            $store_settings_default_fields = apply_filters( 'tc_general_settings_store_fields', $store_settings_default_fields );

            $pages_settings_default_fields = array(
                array(
                    'field_name' => 'tc_cart_page_id',
                    'field_title' => __( 'Cart page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_cart_page_settings',
                    'default_value' => get_option( 'tickera_cart_page_id', -1 ),
                    'tooltip' => __( 'Users will be able to see their cart contents, insert buyer and attendee info on this page. <strong>You can add this page to the site menu for easier accessibility.</strong>', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings'
                ),
                array(
                    'field_name' => 'tc_payment_page_id',
                    'field_title' => __( 'Payment page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_payment_page_settings',
                    'default_value' => get_option( 'tickera_payment_page_id', -1 ),
                    'tooltip' => __( 'Payment method selection page. <br /><strong>Do NOT add this page directly to the site menu. It will be automatically used by the plugin automatically.</strong>', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings'
                ),
                array(
                    'field_name' => 'tc_confirmation_page_id',
                    'field_title' => __( 'Payment confirmation page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_confirmation_page_settings',
                    'default_value' => get_option( 'tickera_confirmation_page_id', -1 ),
                    'tooltip' => __( 'This page will be shown after completed payment. Information about payment status and link to order page will be visible on confimation page. <br /><strong>Do NOT add this page directly to the site menu. It will be used by the plugin automatically.</strong>', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings'
                ),
                array(
                    'field_name' => 'tc_order_page_id',
                    'field_title' => __( 'Order details page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_order_page_settings',
                    'default_value' => get_option( 'tickera_order_page_id', -1 ),
                    'tooltip' => __( 'The page where buyers will be able to check order status and / or download their ticket(s). <br /><strong>Do NOT add this page directly to the site menu. It will be used by the plugin automatically.</strong>', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings'
                ),
                array(
                    'field_name' => 'tc_process_payment_use_virtual',
                    'field_title' => __( 'Use virtual process payment page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'If you\'re experiencing errors with process payment page, set this option to "No" and set "Process Payment Page".', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings'
                ),
                array(
                    'field_name' => 'tc_process_payment_page_id',
                    'field_title' => __( 'Process payment page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_process_payment_page_settings',
                    'default_value' => get_option( 'tickera_process_payment_page_id', -1 ),
                    'tooltip' => __( 'This page is used by the plugin internally to process payments. <br /><strong>Do NOT add this page directly to the site menu!</strong>', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings',
                    'conditional' => array(
                        'field_name' => 'tc_process_payment_use_virtual',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'show'
                    )
                ),
                array(
                    'field_name' => 'tc_ipn_page_use_virtual',
                    'field_title' => __( 'Use virtual IPN (instant payment notification) page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'If you\'re experiencing errors with IPN page, set this option to "No" and select the "IPN Payment Page".', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings'
                ),
                array(
                    'field_name' => 'tc_ipn_page_id',
                    'field_title' => __( 'IPN (instant payment notification) page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_ipn_page_settings',
                    'default_value' => get_option( 'tickera_process_payment_page_id', -1 ),
                    'tooltip' => __( 'This page is used by the plugin internally to receive payment status from payment gateways like PayPal Standard, VoguePay, 2Checkout. <br /><strong>Do NOT add this page directly to the site menu!</strong>', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings',
                    'conditional' => array(
                        'field_name' => 'tc_ipn_page_use_virtual',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'show'
                    )
                ),
                array(
                    'field_name' => 'tc_pages_id',
                    'field_title' => __( 'Pages', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_get_pages_settings',
                    'default_value' => '',
                    'tooltip' => __( 'Create pages required by the plugin', 'tickera-event-ticketing-system' ),
                    'section' => 'page_settings'
                ),
            );

            $pages_settings_default_fields = apply_filters( 'tc_general_settings_page_fields', $pages_settings_default_fields );

            $menu_settings_default_fields = array(
                array(
                    'field_name' => 'show_cart_menu_item',
                    'field_title' => __( 'Show cart menu', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Show link to Cart in Menu on the front automatically.', 'tickera-event-ticketing-system' ),
                    'section' => 'menu_settings'
                ),
            );

            $menu_settings_default_fields = apply_filters( 'tc_general_settings_menu_fields', $menu_settings_default_fields );

            $miscellaneous_settings_default_fields = array(
                array(
                    'field_name' => 'use_order_details_pretty_links',
                    'field_title' => __( 'Order details pretty links', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'yes',
                    'tooltip' => __( 'Choose whether you want to use pretty permalinks for order details page or not. Set to "No" in case that you see 404 page for order details (this could be caused by a third-party plugin or a theme). ', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings'
                ),
                array(
                    'field_name' => 'show_events_as_front_page',
                    'field_title' => __( 'Show events on the front page', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'If this option is set to "Yes", events archive will be shown as a front page. IMPORTANT: "Your latest posts" must be selected in the Settings > Reading section.', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings'
                ),
                array(
                    'field_name' => 'ticket_template_auto_pagebreak',
                    'field_title' => __( 'Multipage ticket template', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Allow ticket template to show on more than one page in the PDF.', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings'
                ),
                array(
                    'field_name' => 'create_and_force_new_session_path',
                    'field_title' => __( 'Create and force new session path', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Set it to Yes if you are having issues with checkout page (redirect loop for example) or if the cart page is empty after adding a ticket to the cart.', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings'
                ),
                array(
                    'field_name' => 'show_age_check',
                    'field_title' => __( 'Age confirmation checkbox', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Get a confirmation of an age of a customer in order to comply with GPDR', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings'
                ),
                array(
                    'field_name' => 'age_text',
                    'field_title' => __( 'Age confirmation message', 'tickera-event-ticketing-system' ),
                    'field_type' => 'option',
                    'default_value' => 'I hereby declare that I am 16 years or older',
                    'tooltip' => __( 'A message that will appear next to the age confirmation checkbox', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings',
                    'conditional' => array(
                        'field_name' => 'show_age_check',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'age_error_text',
                    'field_title' => __( 'Failed age check message', 'tickera-event-ticketing-system' ),
                    'field_type' => 'option',
                    'default_value' => 'Only customers aged 16 or older are permitted for purchase on this website',
                    'tooltip' => __( 'A message that will appear if the age check has failed', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings',
                    'conditional' => array(
                        'field_name' => 'show_age_check',
                        'field_type' => 'radio',
                        'value' => 'no',
                        'action' => 'hide'
                    )
                ),
                array(
                    'field_name' => 'ean_13_checker',
                    'field_title' => __( 'EAN-13 code converter', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'If you\'re using EAN-13 barcode with Serial Ticket Codes add-on on your tickets it is suggested to enable this option.', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings',
                ),
                array(
                    'field_name' => 'google_maps_api_key',
                    'field_title' => __( 'Google Maps API Key', 'tickera-event-ticketing-system' ),
                    'field_type' => 'option',
                    'default_value' => '',
                    'tooltip' => __( 'If you\'re using Google Maps element on your ticket template, you will need to obtain Google Maps API Key from <a href="https://developers.google.com/maps/documentation/maps-static/intro">Google</a> (new Google\'s requirements)', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings',
                ),
                array(
                    'field_name' => 'hide_checkin_ineligible_tickets',
                    'field_title' => __( 'Hide tickets ineligible for check-in', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'If set to Yes, all the tickets that do not have required order status for check-in eligibility will be hidden from the Attendees & Tickets area', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings',
                ),
                array(
                    'field_name' => 'disable_ticket_download_hash',
                    'field_title' => __( 'Disable Ticket Download Hash', 'tickera-event-ticketing-system' ),
                    'field_type' => 'function',
                    'function' => 'tickera_yes_no',
                    'default_value' => 'no',
                    'tooltip' => __( 'Set it to yes if you are having issues with the ticket download. Ticket download link is using hash and in some cases is being invalidated due to a constant variable (e.g AUTH_KEY) is configured to regenerate over time.', 'tickera-event-ticketing-system' ),
                    'section' => 'miscellaneous_settings',
                )
            );

            $miscellaneous_settings_default_fields = apply_filters( 'tc_general_settings_miscellaneous_fields', $miscellaneous_settings_default_fields );

            $default_fields = array_merge( $store_settings_default_fields, $pages_settings_default_fields );
            $default_fields = array_merge( $menu_settings_default_fields, $default_fields );
            $default_fields = array_merge( $miscellaneous_settings_default_fields, $default_fields );

            if ( ! defined( 'TC_LCK' ) && ! defined( 'TC_NU' ) ) {
                $default_fields = array_merge( $license_settings_default_fields, $default_fields );
            }

            return apply_filters( 'tc_settings_general_fields', $default_fields );
        }
    }
}
