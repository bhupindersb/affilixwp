<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Razorpay_Webhook {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register']);
    }

    public function register() {
        register_rest_route('affilixwp/v1', '/razorpay/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle(WP_REST_Request $request) {
        $raw_body = $request->get_body();
        $signature = (string) $request->get_header('X-Razorpay-Signature');
        $secret = (string) get_option('affilixwp_razorpay_webhook_secret');

        if (!$secret || !$signature) {
            return new WP_REST_Response(['error' => 'Webhook configuration is missing.'], 400);
        }

        $expected = hash_hmac('sha256', $raw_body, $secret);
        if (!hash_equals($expected, $signature)) {
            return new WP_REST_Response(['error' => 'Invalid webhook signature.'], 403);
        }

        $event = json_decode($raw_body, true);
        if (!is_array($event) || empty($event['event'])) {
            return new WP_REST_Response(['error' => 'Invalid webhook payload.'], 400);
        }

        $event_id = sanitize_text_field((string) $request->get_header('X-Razorpay-Event-Id'));
        if (!$event_id) {
            $event_id = hash('sha256', $raw_body);
        }

        // add_option is atomic for a unique option name and prevents duplicate
        // processing when Razorpay retries the same delivery.
        $processed_key = 'affilixwp_rzp_event_' . substr(hash('sha256', $event_id), 0, 32);
        if (!add_option($processed_key, time(), '', false)) {
            return new WP_REST_Response(['status' => 'duplicate_ignored'], 200);
        }

        try {
            $subscription = $event['payload']['subscription']['entity'] ?? null;
            if (!is_array($subscription) || empty($subscription['id'])) {
                delete_option($processed_key);
                return new WP_REST_Response(['error' => 'Subscription entity is missing.'], 400);
            }

            $user_id = absint($subscription['notes']['wp_user_id'] ?? 0);
            if (!$user_id) {
                // Fall back to a previously linked subscription ID.
                $users = get_users([
                    'meta_key' => 'affilixwp_subscription_id',
                    'meta_value' => sanitize_text_field($subscription['id']),
                    'number' => 1,
                    'fields' => 'ID',
                ]);
                $user_id = !empty($users) ? absint($users[0]) : 0;
            }

            if (!$user_id) {
                delete_option($processed_key);
                return new WP_REST_Response(['error' => 'Could not map subscription to a WordPress user.'], 422);
            }

            update_user_meta($user_id, 'affilixwp_subscription_id', sanitize_text_field($subscription['id']));

            switch ($event['event']) {
                case 'subscription.activated':
                case 'subscription.resumed':
                    update_user_meta($user_id, 'affilixwp_subscription_status', 'active');
                    break;

                case 'subscription.charged':
                    $reference = sprintf(
                        'razorpay_sub_%s_%s',
                        sanitize_text_field($subscription['id']),
                        absint($subscription['current_start'] ?? time())
                    );
                    $amount_paise = absint($subscription['plan']['item']['amount'] ?? $subscription['plan']['amount'] ?? 0);
                    $amount = $amount_paise / 100;

                    if ($amount > 0) {
                        $recorded = AffilixWP_Commission_Engine::record_purchase($user_id, $amount, $reference);
                        if ($recorded === false) {
                            delete_option($processed_key);
                            return new WP_REST_Response(['error' => 'Commission processing failed.'], 500);
                        }
                    }

                    update_user_meta($user_id, 'affilixwp_last_payment', current_time('mysql'));
                    update_user_meta($user_id, 'affilixwp_subscription_status', 'active');
                    break;

                case 'subscription.cancelled':
                case 'subscription.completed':
                    update_user_meta($user_id, 'affilixwp_subscription_status', 'cancelled');
                    break;

                case 'subscription.halted':
                    update_user_meta($user_id, 'affilixwp_subscription_status', 'halted');
                    break;

                case 'subscription.paused':
                    update_user_meta($user_id, 'affilixwp_subscription_status', 'paused');
                    break;

                default:
                    // Keep unknown, validly signed events acknowledged; Razorpay
                    // can add event types without causing repeated deliveries.
                    break;
            }
        } catch (Throwable $error) {
            delete_option($processed_key);
            error_log('AffilixWP Razorpay webhook processing error: ' . $error->getMessage());
            return new WP_REST_Response(['error' => 'Webhook processing failed.'], 500);
        }

        return new WP_REST_Response(['status' => 'ok'], 200);
    }
}
