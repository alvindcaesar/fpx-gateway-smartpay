<?php

/**
 * @link              https://codeneondigital.com/
 * @since             0.0.1
 * @package           FPX_Payment_for_WPSmartPay
 *
 * @wordpress-plugin
 * Plugin Name:       FPX Payment for WPSmartPay (Beta Version)
 * Plugin URI:        https://codeneondigital.com/
 * Description:       Accept payment by using Financial Process Exchange (FPX) gateway.
 * Version:           0.0.1-beta1
 * Author:            Codeneon Digital
 * Author URI:        https://codeneondigital.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       smartpay-fpx-gateway
 * Domain Path:       /languages
 */

use SmartPay\Foundation\PaymentGateway;
use SmartPay\Models\Payment;
use SmartPay\Models\Product;

if (!defined('ABSPATH')) {
  die;
}


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_fpx_setting_link');

function add_fpx_setting_link($links)
{
  $mylinks = array(
    '<a href="' . admin_url('admin.php?page=smartpay-setting&tab=gateways') . '">Settings</a>',
  );
  return array_merge($links, $mylinks);
}

add_action('admin_notices', 'billplz_test_mode');

function billplz_test_mode()
{
  global $smartpay_options;

  if (smartpay_is_test_mode() &&  !empty($smartpay_options['billplz_sandbox_secret_key'])) {
    echo __(sprintf(
      '<div class="notice notice-warning">
            <p><strong>WPSmartPay: You are using Billplz in test mode. </strong> Make sure to switch to Live Mode when you\'re ready to accept real payments.</p>
        </div>',
    ), 'smartpay-fpx-gateway');
  }
}

add_filter('smartpay_gateways', 'register_gateway', 110);

function register_gateway(array $gateways = array()): array
{
  // Check the gateway exist or not
  $gateways['billplz'] = array(
    'admin_label' => 'Billplz',
    'checkout_label' => 'Billplz',
    'gateway_icon' => plugin_dir_url(__FILE__) . 'assets/Billplz.jpg'
  );
  return $gateways;
}

add_filter('smartpay_get_available_payment_gateways', 'register_to_available_gateway_on_setting', 111);

function register_to_available_gateway_on_setting(array $availableGateways = array()): array
{
  $availableGateways['billplz'] = array(
    'label' => 'Billplz'
  );
  return $availableGateways;
}

add_filter('smartpay_settings_sections_gateways', 'gateway_section', 110);

function gateway_section(array $sections = array()): array
{
  $sections['billplz'] = __('Billplz', 'smartpay-fpx-gateway');
  return $sections;
}

add_filter('smartpay_settings_gateways', 'gateway_settings', 110);

function gateway_settings(array $settings): array
{
  $gateway_settings = array(
    array(
      'id' => 'billplz_settings',
      'name' => '<h4 class="text-uppercase text-info my-1">' . __('Billplz Settings', 'smartpay-fpx-gateway') . '</h4>',
      'desc' => __('Configure your Billplz Settings', 'smartpay-fpx-gateway'),
      'type' => 'header'
    ),

    array(
      'id'   => 'billplz_secret_key',
      'name'  => __('Secret Key', 'smartpay-fpx-gateway'),
      'desc'  => __('Enter secret key', 'smartpay-fpx-gateway'),
      'type'  => 'text',
    ),

    array(
      'id'   => 'billplz_collection_id',
      'name'  => __('Collection ID', 'smartpay-fpx-gateway'),
      'desc'  => __('Enter your Collection ID', 'smartpay-fpx-gateway'),
      'type'  => 'text',
    ),

    array(
      'id'   => 'billplz_xsignature_key',
      'name'  => __('X-Signature Key', 'smartpay-fpx-gateway'),
      'desc'  => __('Enter your X-Signature key', 'smartpay-fpx-gateway'),
      'type'  => 'text',
    ),

    array(
      'id' => 'billplz_sandbox_settings',
      'name' => '<h4 class="text-uppercase text-info my-1">' . __('Billplz Sandbox Settings', 'smartpay-fpx-gateway') . '</h4>',
      'desc' => __('Configure your Billplz Sandbox Settings', 'smartpay-fpx-gateway'),
      'type' => 'header'
    ),

    array(
      'id'   => 'billplz_sandbox_secret_key',
      'name'  => __('Sandbox Secret Key', 'smartpay-example-gateway'),
      'desc'  => __('Enter your sandbox secret key, found in your Developers > API keys', 'smartpay-fpx-gateway'),
      'type'  => 'text',
    ),

    array(
      'id'   => 'billplz_sandbox_collection_id',
      'name'  => __('Sandbox Collection ID', 'smartpay-fpx-gateway'),
      'desc'  => __('Enter your sandbox Collection ID', 'smartpay-fpx-gateway'),
      'type'  => 'text',
    ),

    array(
      'id'   => 'billplz_sandbox_xsignature_key',
      'name'  => __('Sandbox X-Signature Key', 'smartpay-fpx-gateway'),
      'desc'  => __('Enter your Sandbox X-Signature key', 'smartpay-fpx-gateway'),
      'type'  => 'text',
    ),
  );

  return array_merge($settings, ['billplz' => $gateway_settings]);
}

function get_billplz_url()
{
  if (smartpay_is_test_mode()) {
    $url = 'https://www.billplz-sandbox.com/api/v3/bills';
  } else {
    $url = 'https://www.billplz.com/api/v3/bills';
  }
  return $url;
}

function get_billplz_secret_key()
{
  global $smartpay_options;
  if (smartpay_is_test_mode()) {
    $secret_key = base64_encode($smartpay_options['billplz_sandbox_secret_key']);
  } else {
    $secret_key = base64_encode($smartpay_options['billplz_secret_key']);
  }
  return $secret_key;
}

function get_billplz_collection_id()
{
  global $smartpay_options;
  if (smartpay_is_test_mode()) {
    $collection_id = $smartpay_options['billplz_sandbox_collection_id'];
  } else {
    $collection_id = $smartpay_options['billplz_collection_id'];
  }
  return $collection_id;
}

function get_billplz_xsignature_key()
{
  global $smartpay_options;
  if (smartpay_is_test_mode()) {
    $xsignature_key = $smartpay_options['billplz_sandbox_xsignature_key'];
  } else {
    $xsignature_key = $smartpay_options['billplz_xsignature_key'];
  }
  return $xsignature_key;
}


add_action('smartpay_billplz_ajax_process_payment', 'ajax_process_payment');

function ajax_process_payment($paymentData)
{

  $payment = smartpay_insert_payment($paymentData);


  if (!$payment->id) {
    die('Can\'t insert payment.');
  }

  $payment_price = number_format($paymentData['amount'], 2);

  $return_url   = add_query_arg('payment-id', $payment->id, smartpay_get_payment_success_page_uri());
  $callback_url = site_url('wp-json/fpx-smartpay/v1/process-callback');

  $args = array(
    'headers' => array(
      'Authorization' => 'Basic ' . get_billplz_secret_key() . ':',
    ),
    'body' => array(
      'collection_id' => get_billplz_collection_id(),
      'email'         => $paymentData['email'],
      'name'          => $paymentData['customer']['first_name'] . ' ' . $paymentData['customer']['last_name'],
      'amount'        => $payment_price * 100,
      'redirect_url'  => $return_url,
      'callback_url'  => $callback_url,
      'description'   => 'Payment ID #' . $payment->id
    )
  );

  $response = wp_remote_post(get_billplz_url(), $args);
  $apiBody  = json_decode(wp_remote_retrieve_body($response));
  $bill_url = $apiBody->url;

  $content  = '<p class="text-center">Redirecting to Billplz...</p>';
  $content .= '<script>window.location.replace("' . $bill_url . '");</script>';

  echo $content;
}
add_action('rest_api_init', 'fpx_smartpay_callback_url_endpoint');

function fpx_smartpay_callback_url_endpoint()
{
  register_rest_route(
    'fpx-smartpay/v1',  // Namespace
    'process-callback', // Endpoint
    array(
      'methods'             => 'POST',
      'callback'            => 'fpx_smartpay_callback',
      'permission_callback' => '__return_true'
    )
  );
}

function fpx_smartpay_callback($request_data)
{

  $params         = $request_data->get_params();
  $x_signature    = get_billplz_xsignature_key();
  $transaction_id = $params['id'];
  $x_sign         = $params['x_signature'];

  unset($params['x_signature']);

  $arr = array();
  foreach ($params as $k => $v) {
    array_push($arr, ($k . $v));
  }

  sort($arr);

  $new     = implode('|', $arr);

  $hash    = hash_hmac('sha256', $new, $x_signature);

  $payment = Payment::find();

  if (($params['state'] == 'paid') && ($hash == $x_sign)) {
    $payment->updateStatus('completed');
    $payment->setTransactionId($transaction_id);
  }
}


function fpx_process_payment_url()
{

  if (empty($_GET)) {
    return;
  }

  $x_signature = get_billplz_xsignature_key();
  $url         = $_SERVER['QUERY_STRING'];

  parse_str($url, $query);

  ksort($query);

  $payment_id     = $query['payment-id'];
  $x_sign         = $query['billplz']['x_signature'];
  $transaction_id = $query['billplz']['id'];

  unset($query['billplz']['x_signature']);
  unset($query['payment-id']);

  $a = array();
  foreach ($query as $key => $value) {
    foreach ($value as $sub_key => $sub_val) {
      array_push($a, ($key . $sub_key . $sub_val));
    }
  }

  sort($a);

  $new     = implode("|", $a);

  $hash    = hash_hmac('sha256', $new, $x_signature);

  $payment = Payment::find($payment_id);

  if (isset($_GET['payment-id']) && ($hash == $x_sign)) {
    $payment->updateStatus('completed');
    $payment->setTransactionId($transaction_id);
  }
}

add_shortcode('fpx_payment_confirmation', 'fpx_process_payment_url');
