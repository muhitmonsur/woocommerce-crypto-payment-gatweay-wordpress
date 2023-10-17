<?php

return array(
    'enable_log' => array(
        'title' => __('Enable Log', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'checkbox',
    ),
    'title' => array(
        'title' => __('Title', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'text',
        'default' => __('USDT,BTC,ETH,Binance Pay', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
    ),
    'description' => array(
        'title' => __('Description', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'textarea',
        'default' => __('Crypto payment by ABC Crypto Checkout (Payerurl)', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
    ),
    'section_credentials_title' => array(
        'title' => __('Payerurl API Credentials', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'title',
        'class' => ''
    ),
    'payerurl_public_key' => array(
        'title' => __('Public key', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'password',
    ),
    'payerurl_secret_key' => array(
        'title' => __('Secret key', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'description' => __('<span style="color:red">Please test the credentials</span>', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'password',
    ),
    'payerurl_api_test_button' => array(
        'title' => __('Test API', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'class' => 'payerurl_test_api_creds',
        'description' => __('<span style="color:red">Before using this plugin, we are advising to test your credentials</span>', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'button',
        'name' => "Test Credentials"
    ),
    'section_fee_settings_title' => array(
        'title' => __('Fee Settings', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'title',
        'class' => ''
    ),
    'enable_fee_cart' => array(
        'title' => __('Enable fee in cart page', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'checkbox',
    ),
    'payerurl_fee_title' => array(
        'title' => __('Fee title', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'text',
        'description' => 'Title text for checkout page',
        'desc_tip' => true,
    ),
    'payerurl_fee_type' => array(
        'title' => __('Fee type', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'select',
        'options' => ['percentage' => 'Percentage', 'fixed' => 'Fixed'],
    ),
    'payerurl_fee_amount' => array(
        'title' => __('Fee:', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'text',
        'description' => 'Do you want to add extra fees with item total e.g: subtotal: $100, fee: $1 , Grand total: $101',
        'desc_tip' => true,
    ),
    'section_advanced_settings_title' => array(
        'title' => __('Advanced Settings', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'title',
        'class' => ''
    ),
    'payerurl_brand_logo' => array(
        'title' => __('Logo (W:125 H:40, Max:25kb, .png)', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'image',
        'description' => __('Upload a company logo (W:125 H:40), Max:25kb, .png', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
    ),
    'after_payment_order_status' => array(
        'title' => __('After payment done I want the order status will', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'select',
        'options' => wc_get_order_statuses(),
        'description' => 'Order status will change automatically to Processing from pending payment, after payment done.',
        'desc_tip' => true,
    ),
    'payerurl_tolerate_amount_type' => array(
        'title' => __('Tolerate amount type', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'select',
        'options' => ['percentage' => 'Percentage', 'fixed' => 'Fixed'],
    ),
    'payerurl_tolerate_amount' => array(
        'title' => __('Tolerate amount', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'),
        'type' => 'text',
        'description' => 'The tolerate amount is the sum that the consumer is permitted to pay less for any item, but the item order will processing regular way.',
        'desc_tip' => true,
    ),
);
