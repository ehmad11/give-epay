<?php
/**
 * Plugin Name: Give - USAePay
 * Plugin URI: https://github.com/ehmad11/give-epay
 * Description: USAePay payment gateway add-on for GiveWP https://givewp.com/
 * Version: 0.1
 * Author: Muhammad Ahmed
 * Author URI: https://ehmad11.com
 *
 */

define('USAEPAY_PLUGIN_DIR', dirname(__FILE__));
include_once (USAEPAY_PLUGIN_DIR . '/usaepay.class.php');

add_filter('give_payment_gateways', function ()
{
    $gateways['usaepay_give'] = array(
        'admin_label' => esc_attr__('USAePay', 'give-epay') ,
        'checkout_label' => esc_attr__('USAePay', 'give-epay')
    );
    return $gateways;
});

add_action('give_gateway_usaepay_give', function ($purchase_data)
{

    $payment_data = array(
        'price' => $purchase_data['price'],
        'give_form_title' => $purchase_data['post_data']['give-form-title'],
        'give_form_id' => intval($purchase_data['post_data']['give-form-id']) ,
        'give_price_id' => isset($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : '',
        'date' => $purchase_data['date'],
        'user_email' => $purchase_data['user_email'],
        'purchase_key' => $purchase_data['purchase_key'],
        'currency' => give_get_currency() ,
        'user_info' => $purchase_data['user_info'],
        'status' => 'pending',
        'gateway' => 'usaepay_give'
    );

    $payment = give_insert_payment($payment_data);
    $tran = new umTransaction;

    $tran->key = "xxx";
    $tran->pin = "000";

    //$tran->ip = $_SERVER['REMOTE_ADDR'];
    $tran->card = $purchase_data['card_info']['card_number'];
    $tran->exp = $purchase_data['post_data']['card_expiry'];
    $tran->amount = $purchase_data['price'];
    $tran->invoice = $purchase_data['purchase_key'];
    $tran->cardholder = $purchase_data['card_info']['card_name'];
    $tran->street = $purchase_data['card_info']['card_address'];
    $tran->zip = $purchase_data['card_info']['card_zip'];
    $tran->description = "Donation";
    $tran->cvv2 = $purchase_data['card_info']['card_cvc'];

    if ($purchase_data['post_data']['_give_is_donation_recurring'] == "1")
    {
        $recshedule = "monthly";

        if ($purchase_data['post_data']['give-recurring-period-donors-choice'] == "day") $recshedule = "daily";
        if ($purchase_data['post_data']['give-recurring-period-donors-choice'] == "week") $recshedule = "weekly";
        if ($purchase_data['post_data']['give-recurring-period-donors-choice'] == "year") $recshedule = "annually";

        $tran->email = $purchase_data['user_email'];
        $tran->addcustomer = "yes";
        $tran->schedule = $recshedule;
    }

    /*
    // billing info
    $tran->billfname = $this->cart_data['billing_address']['first_name'];
    $tran->billlname = $this->cart_data['billing_address']['last_name'];
    $tran->billstreet = $this->cart_data['billing_address']['address'];
    $tran->billcity = $this->cart_data['billing_address']['city'];
    $tran->billstate = $this->cart_data['billing_address']['state'];
    $tran->billcountry = $this->cart_data['billing_address']['country'];
    $tran->billzip = $this->cart_data['billing_address']['post_code'];
    $tran->billphone = $this->cart_data['billing_address']['phone'];
    $tran->email = $this->cart_data['email_address'];
    
    // shipping info
    $tran->shipfname = $this->cart_data['shipping_address']['first_name'];
    $tran->shiplname = $this->cart_data['shipping_address']['last_name'];
    $tran->shipstreet = $this->cart_data['shipping_address']['address'];
    $tran->shipcity = $this->cart_data['shipping_address']['city'];
    $tran->shipstate = $this->cart_data['shipping_address']['state'];
    $tran->shipcountry = $this->cart_data['shipping_address']['country'];
    $tran->shipzip = $this->cart_data['shipping_address']['post_code'];
    */

    if ($tran->Process())
    {
        give_update_payment_status($payment, 'publish');
        give_send_to_success_page();
    }
    else
    {
        give_send_back_to_checkout('?error=1&payment-mode=' . $purchase_data['post_data']['give-gateway']);
    }
});