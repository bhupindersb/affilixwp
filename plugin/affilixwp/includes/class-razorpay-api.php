<?php
public function create_subscription(WP_REST_Request $request) {

    $plan_id = $request->get_param('planId');
    $user_id = (int) $request->get_param('wpUserId');

    if (!$plan_id || !$user_id) {
        return new WP_REST_Response([
            'error' => 'Missing parameters'
        ], 400);
    }

    $key    = get_option('affilixwp_razorpay_key');
    $secret = get_option('affilixwp_razorpay_secret');

    if (!$key || !$secret) {
        return new WP_REST_Response([
            'error' => 'Razorpay keys not configured'
        ], 500);
    }

    $payload = [
        'plan_id'         => $plan_id,
        'total_count'     => 12, // or 0 for infinite
        'customer_notify' => 1,
        'notes' => [
            'wp_user_id' => $user_id
        ]
    ];

    $response = wp_remote_post(
        'https://api.razorpay.com/v1/subscriptions',
        [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("$key:$secret"),
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($payload),
            'timeout' => 30,
        ]
    );

    if (is_wp_error($response)) {
        return new WP_REST_Response([
            'error' => $response->get_error_message()
        ], 500);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['id'])) {
        return new WP_REST_Response([
            'error' => 'Failed to create Razorpay subscription',
            'razorpay_response' => $body
        ], 500);
    }

    return new WP_REST_Response([
        'id' => $body['id']
    ], 200);
}

