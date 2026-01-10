<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_API {

    public function __construct() {
        add_action('rest_api_init', function () {
            register_rest_route('affilixwp/v1', '/record-commission', [
                'methods'  => 'POST',
                'callback' => [$this, 'handle_commission'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    private function verify_secret($request) {
        $secret = $request->get_header('x-affilixwp-secret');
        $stored = get_option('affilixwp_api_secret');

        if (!$secret || !$stored) {
            return false;
        }

        return hash_equals(trim($stored), trim($secret));
    }

    public function handle_commission($request) {

        if (!$this->verify_secret($request)) {
            return new WP_Error('unauthorized', 'Invalid secret', ['status' => 403]);
        }

        $buyer_user_id = (int) $request->get_param('buyer_user_id');
        $amount        = (float) $request->get_param('amount');
        $reference     = sanitize_text_field($request->get_param('reference'));

        if (!$buyer_user_id || !$amount || !$reference) {
            return new WP_Error('invalid', 'Invalid data', ['status' => 400]);
        }

        AffilixWP_Commission_Engine::record_purchase(
            $buyer_user_id,
            $amount,
            $reference
        );

        return [
            'success' => true,
            'buyer_user_id' => $buyer_user_id,
            'amount' => $amount,
            'reference' => $reference
        ];
    }
}
