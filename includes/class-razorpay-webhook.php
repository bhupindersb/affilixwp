<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Razorpay_Webhook {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register']);
    }

    public function register() {

        register_rest_route('affilixwp/v1', '/razorpay/webhook', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function handle(WP_REST_Request $request) {

        $payload   = $request->get_body();
        $signature = $request->get_header('X-Razorpay-Signature');
        $secret    = get_option('affilixwp_razorpay_webhook_secret');

        if (!$secret) {
            return new WP_REST_Response(['error' => 'Webhook secret missing'], 400);
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            return new WP_REST_Response(['error' => 'Invalid signature'], 403);
        }

        $event = json_decode($payload, true);

        if (empty($event['event'])) {
            return new WP_REST_Response(['error' => 'Invalid payload'], 400);
        }

        switch ($event['event']) {

            case 'subscription.activated':
                $this->subscription_activated($event);
                break;

            case 'subscription.charged':
                $this->subscription_charged($event);
                break;

            case 'subscription.cancelled':
                $this->subscription_cancelled($event);
                break;
        }

        return new WP_REST_Response(['status' => 'ok'], 200);
    }

    private function subscription_activated($event) {

        $sub = $event['payload']['subscription']['entity'];
        $wp_user_id = $sub['notes']['wp_user_id'] ?? null;

        if ($wp_user_id) {
            update_user_meta($wp_user_id, 'affilixwp_subscription_id', $sub['id']);
            update_user_meta($wp_user_id, 'affilixwp_subscription_status', 'active');
        }
    }

    private function subscription_charged($event) {

        $sub = $event['payload']['subscription']['entity'];
        $wp_user_id = $sub['notes']['wp_user_id'] ?? null;

        if (!$wp_user_id) return;

        /**
         * 🔒 Idempotency (one commission per billing cycle)
         */
        $reference = sprintf(
            'razorpay_sub_%s_%s',
            $sub['id'],
            $sub['current_start']
        );

        // Subscription amount (from plan or charge)
        $amount = ($sub['plan']['amount'] ?? 0) / 100;

        if ($amount <= 0) {
            return;
        }

        /**
         * 🔥 RECORD COMMISSION (CANONICAL)
         */
        AffilixWP_Commission_Engine::record_purchase(
            (int) $wp_user_id,
            (float) $amount,
            $reference
        );

        update_user_meta($wp_user_id, 'affilixwp_last_payment', current_time('mysql'));
        update_user_meta($wp_user_id, 'affilixwp_subscription_status', 'paid');
    }

    private function subscription_cancelled($event) {

        $sub = $event['payload']['subscription']['entity'];
        $wp_user_id = $sub['notes']['wp_user_id'] ?? null;

        if ($wp_user_id) {
            update_user_meta($wp_user_id, 'affilixwp_subscription_status', 'cancelled');
        }
    }
}
