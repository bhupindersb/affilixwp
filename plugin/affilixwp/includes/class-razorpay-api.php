<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Razorpay_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes() {

        register_rest_route('affilixwp/v1', '/razorpay/create-subscription', [
            'methods' => 'POST',
            'callback' => [$this, 'create_subscription'],
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);

        register_rest_route('affilixwp/v1', '/razorpay/verify-payment', [
            'methods' => 'POST',
            'callback' => [$this, 'verify_payment'],
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);

        register_rest_route('affilixwp/v1', '/razorpay/cancel-subscription', [
            'methods' => 'POST',
            'callback' => [$this, 'cancel_subscription'],
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);
    }

    public function create_subscription(WP_REST_Request $r) {

        $plan = get_option('affilixwp_razorpay_plan_id');
        $key  = get_option('affilixwp_razorpay_key');
        $sec  = get_option('affilixwp_razorpay_secret');
        $uid  = get_current_user_id();

        $res = wp_remote_post(
            'https://api.razorpay.com/v1/subscriptions',
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode("$key:$sec"),
                    'Content-Type'  => 'application/json'
                ],
                'body' => wp_json_encode([
                    'plan_id' => $plan,
                    'total_count' => 0,
                    'customer_notify' => 1,
                    'notes' => ['wp_user_id' => $uid]
                ])
            ]
        );

        $body = json_decode(wp_remote_retrieve_body($res), true);
        return new WP_REST_Response(['id' => $body['id']], 200);
    }

    public function verify_payment(WP_REST_Request $r) {

        $secret = get_option('affilixwp_razorpay_secret');
        $payload = $r['razorpay_payment_id'] . '|' . $r['razorpay_subscription_id'];

        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $r['razorpay_signature'])) {
            return new WP_REST_Response(['success' => false], 403);
        }

        update_user_meta(get_current_user_id(), 'affilixwp_subscription_status', 'active');
        return new WP_REST_Response(['success' => true], 200);
    }

    public function cancel_subscription() {

        $uid = get_current_user_id();
        $sub = get_user_meta($uid, 'affilixwp_subscription_id', true);

        if (!$sub) return;

        $key = get_option('affilixwp_razorpay_key');
        $sec = get_option('affilixwp_razorpay_secret');

        wp_remote_post(
            "https://api.razorpay.com/v1/subscriptions/$sub/cancel",
            ['headers' => ['Authorization' => 'Basic ' . base64_encode("$key:$sec")]]
        );

        update_user_meta($uid, 'affilixwp_subscription_status', 'cancelled');
    }
}
