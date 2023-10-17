<?php

if (!class_exists("WC_Payerurl")) {

    final class WC_Payerurl extends WC_Payment_Gateway
    {
        public static $logger = null;

        public function __construct()
        {
            $this->paymentURL = PAYERURL . '/payment';
            $this->id = PAYERURL_ID;
            $this->icon = PAYERURL_URL . 'assets/images/coin_high.png';
            $this->method_title = 'Payerurl';
            $this->method_description = '<span>ABC Crypto Checkout support all fiat currencies<br>Fast, secure and low cost <br><br><span style="color: blue;">Get your public key and Secret key &nbsp;<a href="https://dashboard.payerurl.com/register" target="_blank">Click here</a></span></span>';
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->enable_log = $this->get_option('enable_log', false);
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->payerurl_public_key = sanitize_text_field($this->get_option('payerurl_public_key', ''));
            $this->payerurl_secret_key = sanitize_text_field($this->get_option('payerurl_secret_key', ''));
            $this->payerurl_tolerate_amount_type = $this->get_option('payerurl_tolerate_amount_type', 'percentage');
            $this->payerurl_tolerate_amount = $this->get_option('payerurl_tolerate_amount', 1);
            $this->enable_fee_cart = $this->get_option('enable_fee_cart', 'no');
            $this->payerurl_fee_title = $this->get_option('payerurl_fee_title', '');
            $this->payerurl_fee_type = $this->get_option('payerurl_fee_type', 'percentage');
            $this->payerurl_fee_amount = $this->get_option('payerurl_fee_amount', 0);
            $this->payerurl_brand_logo = $this->get_option('payerurl_brand_logo', '');
            $this->after_payment_order_status = $this->get_option('after_payment_order_status', 'wc-processing');
            $this->order_button_text = __('Proceed to Payerurl', 'ABC-crypto-currency-payment-gateway-for-wooCommerce');

            if (empty(self::$logger)) self::$logger = wc_get_logger();

            if (is_admin()) {
                add_action(
                    'woocommerce_update_options_payment_gateways_' . $this->id,
                    array($this, 'process_admin_options')
                );
            }

            add_action('woocommerce_api_wc_payerurl', array($this, 'payerurl_response'));
            add_filter('woocommerce_payment_complete_order_status', array($this, 'payment_complete_order_status'), 99, 1);
        }

        public function init_form_fields()
        {
            $this->form_fields = include 'admin-form-fields.php';
        }

        public function process_admin_options()
        {
            return parent::process_admin_options();
        }

        public function payment_complete_order_status($status)
        {
            if (!empty($this->after_payment_order_status)) {
                $status = str_replace('wc-', '', $this->after_payment_order_status);
            }

            return $status;
        }

        public function add_payerurl_fee($cart)
        {
            $session = WC()->session->get('chosen_payment_method');
            if (empty($session) || $session != strval(PAYERURL_ID)) return;

            $is_enable_cart = $this->enable_fee_cart;
            if ($is_enable_cart == 'no' && is_cart()) return;

            $amount = (float)$this->payerurl_fee_amount;
            if (empty($amount) || !is_numeric($amount) || $amount <= 0) return;

            $type = $this->payerurl_fee_type;
            switch ($type) {
                case "percentage":
                    $fee = ($cart->get_cart_contents_total() + $cart->get_shipping_total()) * ($amount / 100);
                    break;
                case "fixed":
                    $fee = $amount;
                    break;
                default:
                    return;
            }

            $title = $this->payerurl_fee_title;
            $cart->add_fee($title, $fee);
        }

        public function process_payment($order_id)
        {
            $reqBody = self::getRequestBody($order_id);
            $signature = self::generateSignature($reqBody, $this->payerurl_secret_key);
            $authStr = self::getAuthStr($signature, $this->payerurl_public_key);
            $args = [
                'timeout' => 50,
                'body' => $reqBody,
                'headers' => self::getRequestHeader($authStr)
            ];

            if (!empty($this->enable_log)) self::log(json_encode($args));
            $response = wp_remote_post($this->paymentURL, $args);
            if (!empty($this->enable_log)) self::log(json_encode($response));

            $result = array('result' => 'error', 'redirect' => wc_get_checkout_url());
            if (is_wp_error($response)) {
                wc_add_notice(
                    __(
                        'An error occurred, We were unable to process your order, please try again.',
                        'ABC-crypto-currency-payment-gateway-for-wooCommerce'
                    ),
                    'error'
                );
                return $result;
            }

            $body = json_decode($response['body'], true);
            if ($response['response']['code'] !== 200) {
                wc_add_notice(
                    __(
                        "!Error: $body" . " contact us telegram:<a href='https://t.me/Payerurl' target='_blank' style='color:blue;'>Live support (@payerurl)</a>",
                        'ABC-crypto-currency-payment-gateway-for-wooCommerce'
                    ),
                    'error'
                );
                return $result;
            }

            if (isset($body['redirectTO'])) {
                $result = array(
                    'result' => 'success',
                    'redirect' => esc_url_raw($body['redirectTO'])
                );
            } else {
                wc_add_notice(
                    __(
                        'An error occurred, We were unable to process your order, please contact us telegram: <a href="https://t.me/Payerurl" target="_blank" style="color:blue;">Live support (@payerurl)</a>',
                        'ABC-crypto-currency-payment-gateway-for-wooCommerce'
                    ),
                    'error'
                );
            }

            return $result;
        }

        public function payerurl_response()
        {
            $input = self::extractResponseData();
            $headers = getallheaders();
            if (!empty($this->enable_log)) self::log(json_encode([$headers, $input]));

            $response = $this->isResponseValid($headers, $input);
            extract($response);
            if (!empty($status)) return wp_send_json($response);

            $input['order_id'] = $order->get_id();
            $signature = self::generateSignature($input, $this->payerurl_secret_key);
            if (!hash_equals($signature, $auth[1])) {
                return wp_send_json(
                    array_merge(
                        ['status' => 2030, 'message' => 'Signature doesn\'t match'],
                        $input
                    )
                );
            }

            $api_hash_link = "https://dashboard.payerurl.com/paymentoption/" . $input['transaction_id'];
            $link = filter_var($api_hash_link, FILTER_SANITIZE_URL);
            $format_link = sprintf(
                '<a href="%s" target="_blank" rel="noopener">%s</a>',
                $link,
                $input['transaction_id']
            );

            $order->add_order_note(
                sprintf(
                    'Txn. ID: %s<br/>Received Coin: %s %s<br/>
                    (%s %s)<br/>Time: %s UTC<br/>Note: %s',
                    $format_link,
                    $input['coin_rcv_amnt'],
                    $input['coin_rcv_amnt_curr'],
                    $input['confirm_rcv_amnt'],
                    $input['confirm_rcv_amnt_curr'],
                    $input['txn_time'],
                    $input['note']
                ),
                true
            );

            $received_amnt_with_tolerateAmount = $input['confirm_rcv_amnt'] + $this->tolerateAmount($order->get_total());
            WC()->cart->empty_cart();
            if (
                $input['status_code'] === 200 &&
                $input['confirm_rcv_amnt'] != 0 && $input['coin_rcv_amnt'] != 0 &&
                $received_amnt_with_tolerateAmount >= $order->get_total()
            ) {
                $order->payment_complete($input['transaction_id']);
                return wp_send_json(['status' => 2040, 'message' => 'Order updated successfuly']);
            } else {
                $order->set_transaction_id($input['transaction_id']);
                $order->update_status('on-hold');
                return wp_send_json(['status' => 2050, 'message' => 'Order On-Hold due to less amount paid by the customer']);
            }
        }

        public function generate_image_html($key, $data)
        {
            $field_key = $this->get_field_key($key);

            $defaults  = array(
                'title'             => '',
                'disabled'          => false,
                'class'             => '',
                'placeholder'       => '',
                'desc_tip'          => false,
                'description'       => '',
            );

            $data  = wp_parse_args($data, $defaults);
            $value = $this->get_option($key);

            ob_start();
?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>">
                        <?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); ?>
                    </label>
                </th>
                <td class="forminp">
                    <div class="payerurl-img-preview">
                        <?php
                        if (!empty($value)) {
                            echo wp_get_attachment_image($value);
                        }
                        ?>
                    </div>
                    <button class="button payerurl_admin_payment_settings_image_upload" data-field-id="payerurl-<?php echo esc_attr($field_key); ?>" data-media-frame-title="<?php echo esc_attr(__('Select an image to upload', 'ABC-crypto-currency-payment-gateway')); ?>" data-media-frame-button="<?php echo esc_attr(__('Use this image', 'ABC-crypto-currency-payment-gateway')); ?>" data-add-image-text="<?php echo esc_attr(__('Add/Edit image', 'ABC-crypto-currency-payment-gateway-for-wooCommerce')); ?>">
                        <?php echo esc_html__('Add image', 'ABC-crypto-currency-payment-gateway-for-wooCommerce'); ?>
                    </button>
                    <input type="hidden" name="<?php echo esc_attr($field_key); ?>" id="payerurl-<?php echo esc_attr($field_key); ?>" value="<?php echo esc_attr($value); ?>" />
                </td>
            </tr>
        <?php
            return ob_get_clean();
        }

        public function generate_button_html($key, $data)
        {
            $defaults  = array(
                'title' => '',
                'disabled' => false,
                'class' => '',
                'css' => '',
                'desc_tip' => false,
                'description' => '',
                'name' => '',
            );
            $data  = wp_parse_args($data, $defaults);

            ob_start();
        ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <button type="button" class="button <?php echo $data['class']; ?>">
                        <?php echo $data['name']; ?>
                    </button>

                    <?php
                    if (isset($data['description'])) {
                    ?>
                        <p class="description">
                            <?php echo $data['description']; ?>
                        </p>
                    <?php
                    }
                    ?>
                </th>
                <td class="forminp"></td>
            </tr>
<?php
            return ob_get_clean();
        }

        private function isResponseValid($headers, $input)
        {
            if (!isset($input['transaction_id']) || empty($input['transaction_id']))
                return ['status' => 2050, 'message' => 'Transaction ID not found'];

            $auth = self::getAuthFromResponse($headers);
            if (!is_array($auth))
                return ['status' => false, 'data' => ['status' => 2030, 'message' => 'Auth header not found']];
            if ($this->payerurl_public_key != $auth[0])
                return ['status' => 2030, 'message' => 'Public key doesn\'t match'];

            if (!isset($input['order_id']) || empty($input['order_id']))
                return ['status' => 2050, 'message' => 'Order ID not found'];
            $order = wc_get_order($input['order_id']);
            if (is_null($order)) return ['status' => 2050, 'message' => 'Order not found'];

            if ($input['status_code'] === 20000) {
                $order->update_status('cancelled');
                return ['status' => 20000, 'message' => 'Payment cancelled'];
            }

            return ['auth' => $auth, 'order' => $order];
        }

        private static function getAuthFromResponse($headers)
        {
            if (!array_key_exists('Authorization', $headers)) return false;
            $authStr = $headers['Authorization'];
            if (0 !== stripos($authStr, 'Bearer ')) return false;
            $authStr = sanitize_text_field(str_replace('Bearer ', '', $authStr));
            $authStr = base64_decode($authStr);
            return explode(':', $authStr);
        }

        private static function getAuthStr($signature, $pubKey)
        {
            return base64_encode(sprintf('%s:%s', $pubKey, $signature));
        }

        private static function generateSignature($input, $secret)
        {
            ksort($input);
            $input = array_map(function ($value) {
                return $value !== false && empty($value) ? null : $value;
            }, $input);
            $input = http_build_query($input);
            return hash_hmac('sha256', $input, $secret);
        }

        private static function getRequestHeader($authStr)
        {
            return [
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
                'Authorization' => sprintf('Bearer %s', $authStr),
            ];
        }

        private static function getRequestBody($order_id)
        {
            $order = new WC_Order($order_id);
            $args = array(
                'order_id' => $order->get_id(),
                'order_key' => $order->get_order_key(),
                'amount' => $order->get_total(),
                'currency' => strtolower(get_woocommerce_currency()),
                'billing_fname' => sanitize_text_field($order->get_billing_first_name()),
                'billing_lname' => sanitize_text_field($order->get_billing_last_name()),
                'billing_email' => sanitize_email($order->get_billing_email()),
                'redirect_to' => $order->get_checkout_order_received_url(),
                'cancel_url' => wc_get_checkout_url(),
                'type' => 'wp',
                'notify_url' => home_url('/wc-api/wc_payerurl')
            );

            $items = $order->get_items();
            $args['items'] = array_reduce($items, function ($carry, $item) {
                array_push($carry, [
                    "name" => sanitize_text_field($item->get_name()),
                    'qty' => $item->get_quantity(),
                    'price' => $item->get_total(),
                ]);
                return $carry;
            }, []);

            return $args;
        }

        private static function extractResponseData()
        {
            return [
                'ext_transaction_id' => self::getDataFromResponse('ext_transaction_id'),
                'transaction_id' => self::getDataFromResponse('transaction_id'),
                'status_code' => filter_var(self::getDataFromResponse('status_code'), FILTER_VALIDATE_INT),
                'note' => self::getDataFromResponse('note'),
                'confirm_rcv_amnt' => self::getDataFromResponse('confirm_rcv_amnt', 0),
                'confirm_rcv_amnt_curr' => strtoupper(self::getDataFromResponse('confirm_rcv_amnt_curr')),
                'coin_rcv_amnt' => self::getDataFromResponse('coin_rcv_amnt', 0),
                'coin_rcv_amnt_curr' => strtoupper(self::getDataFromResponse('coin_rcv_amnt_curr')),
                'txn_time' => self::getDataFromResponse('txn_time'),
                'order_id' => self::getDataFromResponse('order_id'),
            ];
        }

        private static function getDataFromResponse($key, $default = '')
        {
            return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : $default;
        }

        private function tolerateAmount($checkoutAmount)
        {
            if (
                empty($this->payerurl_tolerate_amount) ||
                !is_numeric($this->payerurl_tolerate_amount) ||
                $this->payerurl_tolerate_amount <= 0
            ) return 0;

            switch ($this->payerurl_tolerate_amount_type) {
                case "percentage":
                    return $checkoutAmount * ($this->payerurl_tolerate_amount / 100);
                case "fixed":
                    return $this->payerurl_tolerate_amount;
                default:
                    return 0;
            }
        }

        private static function log($message, $level = 'info')
        {
            $context = array('source' => 'payerurl');
            self::$logger->log($level, $message, $context);
        }

        public function testApiCreds()
        {
            if (empty($_POST["app_key"]) || empty($_POST["secret_key"])) {
                return wp_send_json_error([
                    'message' => __('Add the public and secret key', 'ABC-crypto-currency-payment-gateway-for-wooCommerce')
                ], 400);
            }

            $this->payerurl_public_key = sanitize_text_field($_POST["app_key"]);
            $this->payerurl_secret_key = sanitize_text_field($_POST["secret_key"]);

            $body = [
                'test' => $this->payerurl_public_key
            ];
            $signature = self::generateSignature($body, $this->payerurl_secret_key);
            $authStr = self::getAuthStr($signature, $this->payerurl_public_key);

            $args = [
                'timeout' => 45,
                'body' => $body,
                'headers' => self::getRequestHeader($authStr)
            ];

            $response = wp_remote_post(PAYERURL . "/api-secret-key-validation", $args);
            if (is_wp_error($response)) {
                return wp_send_json_error([
                    'message' => __('Server error', 'ABC-crypto-currency-payment-gateway-for-wooCommerce')
                ], 500);
            }

            $body = json_decode($response['body'], true);
            if ($response['response']['code'] !== 200) {
                return wp_send_json_error([
                    'message' => $body['message']
                ], 401);
            }

            return wp_send_json_success();
        }
    }
}
