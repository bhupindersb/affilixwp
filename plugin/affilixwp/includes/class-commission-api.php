<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_API {

    public function __construct() {
        add_action('rest_api_init', function () {
            register_rest_route('affilixwp/v1', '/commission', [
                'methods'  => 'POST',
                'callback' => [$this, 'handle_commission'],
                'permission_callback' => [$this, 'verify_request'],
            ]);
        });
    }

    public function verify_request($request) {
        $secret = $request->get_header('x-affilixwp-secret');
        return hash_equals(get_option('affilixwp_api_secret'), $secret);
    }

    public function handle_commission($request) {

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

        return ['success' => true];
    }
}
