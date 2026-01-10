<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_API {

    public function __construct() {
        add_action('wp_ajax_affilixwp_record_commission', [$this, 'handle']);
        add_action('wp_ajax_nopriv_affilixwp_record_commission', [$this, 'handle']);
    }

    public function handle() {
        // 🔐 Verify secret
        $secret = $_SERVER['HTTP_X_AFFILIXWP_SECRET'] ?? '';
        if (!hash_equals(get_option('affilixwp_api_secret'), $secret)) {
            wp_send_json_error('Unauthorized', 403);
        }

        $buyer_user_id = (int) ($_POST['buyer_user_id'] ?? 0);
        $amount        = (float) ($_POST['amount'] ?? 0);
        $reference     = sanitize_text_field($_POST['reference'] ?? '');

        if (!$buyer_user_id || !$amount || !$reference) {
            wp_send_json_error('Invalid data', 400);
        }

        AffilixWP_Commission_Engine::record_purchase(
            $buyer_user_id,
            $amount,
            $reference
        );

        wp_send_json_success(['recorded' => true]);
    }
}
