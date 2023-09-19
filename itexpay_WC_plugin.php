<?php

/**
 * @package ItexPay WC_Plugin
 * @version 1.0.0
 */
/*
Plugin Name: ItexPay WC_Plugin
Plugin URI: http://wordpress.org/plugins/itexpay_plugin/
Description: This Plugin is not just for web acquiring. You can now access all our Itex services in one click! Thanks to ITEX | WOOCOMMERCE
Author: ITEX Integrated Services
Version: 1.0.0
Author URI: https://iisysgroup.com
*/



global $wpdb;
function init_ItexPay_gateway_class() {

    /**
     * Check if WooCommerce is activated
     */
    if ( ! function_exists( 'is_woocommerce_activated' ) ) {
        function is_woocommerce_activated() {
            if ( class_exists( 'woocommerce' ) ) {
                return true;
            }
            else {
                return false;
            }
        }
    }


    if (is_woocommerce_activated()) {

        /**
         * WooCommerce ITEXPay Payment Gateway class.
         *
         * Extendeds by WooCommerce payment gateways to handle payments.
         *
         * @class       WC_Gateway_ItexPay
         * @extends     WC_Payment_Gateway
         * @version     1.0.0
         */
        class WC_Gateway_ItexPay extends WC_Payment_Gateway
        {

            /**
             * Constructor for ItexPay gateway.
             */
            public function __construct()
            {
                $this->id = 'itexpay';

                $this->title = __('ItexPay', 'woocommerce');
                $this->method_description = "Card Payments | bank transfers | USSD payments | QR codes, etc";

                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();

                // Use Current Payment Gateway mode
                if ($this->get_option('current_mode') == "0") {
                    $this->title = $this->get_option('Test_Title');
                    $this->description = $this->get_option('Test_Description');
                    $GLOBALS['Public_Key'] = $this->get_option('Test_Public_Key');
                    $GLOBALS['Private_Key'] = $this->get_option('Test_Private_Key');
                    $GLOBALS['Encryption_Key'] = $this->get_option('Test_Encryption_Key');
                } elseif ($this->get_option('current_mode') == "1") {
                    $this->title = $this->get_option('Live_Title');
                    $this->description = $this->get_option('Live_Description');
                    $GLOBALS['Public_Key'] = $this->get_option('Live_Public_Key');
                    $GLOBALS['Private_Key'] = $this->get_option('Live_Private_Key');
                    $GLOBALS['Encryption_Key'] = $this->get_option('Live_Encryption_Key');
                }


                // Actions hook.
                add_action('woocommerce_api_callback', array($this, 'check_response'));

            }


            /**
             * Initialise ItexPay Settings Form Fields.
             */
            public function init_form_fields()
            {

                // ItexPay Woocommerce Admin settings Form
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable ItexPay', 'woocommerce'),
                        'default' => 'no',
                    ),
                    'Test_Title' => array(
                        'title' => __('Test Title', 'woocommerce'),
                        'type' => 'safe_text',
                        'description' => __('this controls the title which the user sees during live checkout.', 'woocommerce'),
                        'default' => _x('ItexPay', 'Check payment method', 'woocommerce'),
                        'desc_tip' => true,
                    ),
                    'Test_Description' => array(
                        'title' => __('Test Description', 'woocommerce'),
                        'type' => 'text',
                        'css' => 'width:50em;',
                        'description' => __('Payment method description that the customer will see on your live checkout.', 'woocommerce'),
                        'default' => __('This is a test transaction. Switch to live in the admin settings!', 'woocommerce'),
                        'desc_tip' => true,
                    ),
                    'Test_Public_Key' => array(
                        'title' => 'Test Public Key',
                        'type' => 'text',
                        'css' => 'width:50em;',
                        'description' => 'Enter your Test Public Key',
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'Test_Private_Key' => array(
                        'title' => __('Test Private Key', 'woocommerce'),
                        'type' => 'text',
                        'css' => 'width:50em;',
                        'description' => __('Enter your ItexPay Test Private Key', 'woocommerce'),
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'Test_Encryption_Key' => array(
                        'title' => 'Test Encryption Key',
                        'type' => 'text',
                        'css' => 'width:50em;',
                        'description' => 'Enter your Test encryption Key',
                        'default' => '',
                        'desc_tip' => true,
                    ),

                    'Live_Title' => array(
                        'title' => __('Live Title', 'woocommerce'),
                        'type' => 'safe_text',
                        'description' => __('This controls the title which the user sees during live checkout.', 'woocommerce'),
                        'default' => _x('ItexPay', 'Check payment method', 'woocommerce'),
                        'desc_tip' => true,
                    ),
                    'Live_Description' => array(
                        'title' => __('Live Description', 'woocommerce'),
                        'type' => 'text',
                        'css' => 'width:50em;',
                        'description' => __('Payment method description that the customer will see on your live checkout.', 'woocommerce'),
                        'default' => __('', 'woocommerce'),
                        'desc_tip' => true,
                    ),
                    'Live_Public_Key' => array(
                        'title' => 'Live Public Key',
                        'type' => 'text',
                        'css' => 'width:50em;',
                        'description' => 'Enter your Live Public Key',
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'Live_Private_Key' => array(
                        'title' => __('Live Private Key', 'woocommerce'),
                        'type' => 'text',
                        'css' => 'width:50em;',
                        'description' => __('Enter your ItexPay Live Private Key', 'woocommerce'),
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'Live_Encryption_Key' => array(
                        'title' => 'Live Encryption Key',
                        'type' => 'text',
                        'css' => 'width:50em;',
                        'description' => 'Enter your Live encryption Key',
                        'default' => '',
                        'desc_tip' => true, 23
                    ),

                    'current_mode' => array(
                        'css' => 'width:8em;',
                        'title' => __('Current GateWay Mode', 'woocommerce'),
                        'type' => 'select',
                        'options' => array('Test Mode', 'Live Mode'),
                        'description' => 'Select your gateway mode and save changes',
                        'default' => 'Test Mode',
                        'desc_tip' => true,
                    ),
                );
            }

            /**
             * Process the payment and return the result.
             *
             * @param int $order_id Order ID.
             * @return array
             */
            public function process_payment($order_id)
            {

                $order = new WC_Order($order_id);

                // Get the Customer billing details
                $email = $order->get_billing_email();
                $phone_number = $order->get_billing_phone();
                $first_name = $order->get_billing_first_name();
                $last_name = $order->get_billing_last_name();
                $amount = $order->get_total();

                $rand = rand(10000000, 200000000);
                $args2 = array(
                    'headers' => array('Content-Type' => 'application/json', "Authorization" => $GLOBALS['Public_Key']),
                    'body' => '{
                "amount":"' . $amount . '",
                "currency":"NGN",
                "customer":{
                "email":"' . $email . '",
                "first_name":"' . $first_name . '",
                "last_name":"' . $last_name . '",
                "phone_number":"' . $phone_number . '"
                },
                "redirecturl":"http://localhost/wordpress/?wc-api=callback&order_id=' . $order_id . '",
                "reference":"' . $rand . '"
            }'
                );

                $response = wp_remote_post('https://staging.itexpay.com/api/pay', $args2);
                $body = wp_remote_retrieve_body($response);

                $authorization_url = json_decode($body)->authorization_url;

                return array(
                    'result' => 'success',
                    'redirect' => $authorization_url,
                );
            }


            /**
             * Get Payment Callback
             * Update Order Status
             */
            public function check_response()
            {

                if (isset($_GET['order_id'], $_GET['code'])) {

                    $orderid_paymentid = $_GET['order_id'];

                    $str = $orderid_paymentid;
                    $arr = explode("?", $str);

                    $order_id = $arr[0];
                    $arr1 = $arr[1];

                    $arr2 = explode("=", $arr1);

                    $paymentid = $arr2[1];

                    $desc = $_GET['desc'];
                    $linkingreference = $_GET['linkingreference'];
                    $code = $_GET['code'];

                    $publickey = $GLOBALS['Public_Key'];
//                $privatekey = $GLOBALS['Private_Key'];
//                $encrkey = $GLOBALS['Encryption_Key'];

                    $response =
                        wp_remote_get('https://staging.itexpay.com/api/v1/transaction/charge/status?publickey=' . $publickey . '&paymentid=' . $paymentid . '
');

                    $body = wp_remote_retrieve_body($response);
                    $decodeBody = json_decode($body, true);

                    $fetchedPaymentid = $decodeBody['transaction']['paymentid'];
                    $fetchedLinkingreference = $decodeBody['transaction']['linkingreference'];

                    // Verify transaction before updating order
                    if ($fetchedPaymentid == $paymentid && $fetchedLinkingreference == $linkingreference && $code == 00) {

                        $order = wc_get_order($order_id);

                        $new_status = 'wc-processing'; // Replace with the desired status.
                        $note = $paymentid;

                        $order->update_status($new_status, $note);
                        $order->save();
                        $url_thank_you = $this->get_return_url($order);
                        header("Location: $url_thank_you");
                    } else {
                        header("Location: http://localhost/wordpress/index.php/checkout/");
                    }
                }
            }
        }
    }
    else{
        function wc_not_activated_notice() {
            echo "<div style='color: red; font-size: larger; border: 2px solid black; padding: 10px; margin:10px; text-align: center'>Something not right; Woocommerce is not activated. You need to activate Woocommerce for this Plugin to function Properly.</div>";
        }
        add_action( 'admin_notices', 'wc_not_activated_notice' );
    }

}


function add_ItexPay_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_ItexPay';
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_ItexPay_gateway_class' );
add_action( 'plugins_loaded', 'init_ItexPay_gateway_class' );
