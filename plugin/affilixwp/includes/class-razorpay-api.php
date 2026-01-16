<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Razorpay_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {

        register_rest_route('affilixwp/v1', '/razorpay/create-subscription', [
            'methods'  => 'POST',
            'callback' => [$this, 'create_subscription'],
            'permission_callback' => function () {
                return is_user_logged_in();
            }
        ]);
    }

    public function create_subscription(WP_REST_Request $request) {

        $plan_id = $request->get_param('planId');
        $user_id = (int) $request->get_param('wpUserId');

        if (!$plan_id || !$user_id) {
            return new WP_REST_Response([
                'error' => 'Missing parameters'
            ], 400);
        }

        // 🔹 TEMP response to confirm route works
        return new WP_REST_Response([
            'id' => 'sub_test_' . time()
        ], 200);
    }
}
