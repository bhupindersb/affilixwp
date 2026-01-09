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

        $headers = $request->get_headers();
        error_log('AFFILIXWP DEBUG HEADERS: ' . print_r($headers, true));

        $secret = $request->get_header('x-affilixwp-secret');
        error_log('AFFILIXWP DEBUG SECRET HEADER: ' . var_export($secret, true));

        $stored = get_option('affilixwp_api_secret');
        error_log('AFFILIXWP DEBUG STORED SECRET: ' . $stored);

        return hash_equals($stored, (string) $secret);
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
