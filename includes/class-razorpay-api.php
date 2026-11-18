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

    public function create_subscription(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $plan_id = trim((string) get_option('affilixwp_razorpay_plan_id'));
        $key = trim((string) get_option('affilixwp_razorpay_key'));
        $secret = (string) get_option('affilixwp_razorpay_secret');

        if (!$plan_id || !$key || !$secret) {
            return new WP_REST_Response(['error' => 'Razorpay is not fully configured.'], 503);
        }

        $payload = [
            'plan_id' => $plan_id,
            'total_count' => 12,
            'customer_notify' => 1,
            'notes' => ['wp_user_id' => (string) $user_id],
        ];

        $response = wp_remote_post('https://api.razorpay.com/v1/subscriptions', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($key . ':' . $secret),
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            error_log('AffilixWP Razorpay subscription request failed: ' . $response->get_error_message());
            return new WP_REST_Response(['error' => 'Unable to contact the payment provider. Please try again.'], 502);
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!in_array($status, [200, 201], true) || empty($body['id'])) {
            error_log('AffilixWP Razorpay subscription creation failed with HTTP ' . $status);
            return new WP_REST_Response(['error' => 'Unable to create the subscription. Please check the payment settings.'], 502);
        }

        update_user_meta($user_id, 'affilixwp_subscription_id', sanitize_text_field($body['id']));
        update_user_meta($user_id, 'affilixwp_subscription_status', 'pending');
        update_user_meta($user_id, 'affilixwp_subscription_plan_id', $plan_id);

        return new WP_REST_Response(['id' => sanitize_text_field($body['id'])], 201);
    }

    public function verify_payment(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $payment_id = sanitize_text_field((string) $request->get_param('razorpay_payment_id'));
        $subscription_id = sanitize_text_field((string) $request->get_param('razorpay_subscription_id'));
        $signature = sanitize_text_field((string) $request->get_param('razorpay_signature'));
        $secret = (string) get_option('affilixwp_razorpay_secret');
        $stored_subscription_id = (string) get_user_meta($user_id, 'affilixwp_subscription_id', true);

        if (!$payment_id || !$subscription_id || !$signature || !$secret) {
            return new WP_REST_Response(['success' => false, 'error' => 'Missing payment verification details.'], 400);
        }

        // A logged-in user may only verify the subscription created for their account.
        if (!$stored_subscription_id || !hash_equals($stored_subscription_id, $subscription_id)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Subscription does not belong to this account.'], 403);
        }

        // Razorpay subscription checkout signature payload: payment_id|subscription_id.
        $expected = hash_hmac('sha256', $payment_id . '|' . $subscription_id, $secret);
        if (!hash_equals($expected, $signature)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Payment signature is invalid.'], 403);
        }

        update_user_meta($user_id, 'affilixwp_payment_id', $payment_id);
        update_user_meta($user_id, 'affilixwp_subscription_status', 'authenticated');

        // Activation is confirmed by the signed subscription.activated/charged webhook.
        return new WP_REST_Response(['success' => true, 'status' => 'pending_activation'], 200);
    }

    public function cancel_subscription() {
        $user_id = get_current_user_id();
        $subscription_id = (string) get_user_meta($user_id, 'affilixwp_subscription_id', true);
        $key = (string) get_option('affilixwp_razorpay_key');
        $secret = (string) get_option('affilixwp_razorpay_secret');

        if (!$subscription_id || !$key || !$secret) {
            return new WP_REST_Response(['success' => false, 'error' => 'No cancellable subscription was found.'], 404);
        }

        $response = wp_remote_post(
            'https://api.razorpay.com/v1/subscriptions/' . rawurlencode($subscription_id) . '/cancel',
            [
                'headers' => ['Authorization' => 'Basic ' . base64_encode($key . ':' . $secret)],
                'body' => wp_json_encode(['cancel_at_cycle_end' => 0]),
                'timeout' => 30,
            ]
        );

        if (is_wp_error($response) || !in_array((int) wp_remote_retrieve_response_code($response), [200, 201], true)) {
            return new WP_REST_Response(['success' => false, 'error' => 'The subscription could not be cancelled.'], 502);
        }

        update_user_meta($user_id, 'affilixwp_subscription_status', 'cancellation_requested');
        return new WP_REST_Response(['success' => true, 'status' => 'cancellation_requested'], 200);
    }
}
