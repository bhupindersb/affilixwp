<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_REST {

    public static function register() {
        register_rest_route('affilixwp/v1', '/commission', [
            'methods'  => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(WP_REST_Request $request) {
        global $wpdb;

        /* ----------------------------
           🔐 Verify API secret
        ----------------------------- */
        $secret   = $_SERVER['HTTP_X_AFFILIXWP_SECRET'] ?? '';
        $expected = get_option('affilixwp_api_secret');

        if (!$secret || !$expected || !hash_equals($expected, $secret)) {
            return new WP_Error(
                'forbidden',
                'Invalid API secret',
                ['status' => 403]
            );
        }

        /* ----------------------------
           📦 Read payload
        ----------------------------- */
        $buyer_user_id = (int) $request->get_param('buyer_user_id');
        $amount        = (float) $request->get_param('amount');
        $reference     = sanitize_text_field($request->get_param('reference'));

        if (!$buyer_user_id || !$amount || !$reference) {
            return new WP_Error(
                'invalid_request',
                'Missing required parameters',
                ['status' => 400]
            );
        }

        $table = $wpdb->prefix . 'affilixwp_commissions';

        /* ----------------------------
           🛑 Prevent duplicates
        ----------------------------- */
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE reference = %s",
                $reference
            )
        );

        if ($exists) {
            return [
                'status'  => 'duplicate',
                'message' => 'Commission already recorded',
            ];
        }

        /* ----------------------------
           🔗 Referral chain
        ----------------------------- */
        $referrals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT referrer_user_id, level
                 FROM {$wpdb->prefix}affilixwp_referrals
                 WHERE referred_user_id = %d",
                $buyer_user_id
            )
        );

        foreach ($referrals as $ref) {
            $rate = ((int) $ref->level === 1) ? 0.10 : 0.05;
            $commission = round($amount * $rate, 2);

            $wpdb->insert(
                $table,
                [
                    'affiliate_id'      => 0,
                    'referrer_user_id'  => (int) $ref->referrer_user_id,
                    'referred_user_id'  => $buyer_user_id,
                    'order_amount'      => $amount,
                    'commission_amount' => $commission,
                    'level'             => (int) $ref->level,
                    'status'            => 'pending',
                    'reference'         => $reference,
                    'created_at'        => current_time('mysql'),
                ]
            );
        }

        return [
            'status'  => 'success',
            'message' => 'Commission recorded',
        ];
    }
}
