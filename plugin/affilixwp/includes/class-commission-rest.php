<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_REST {

    public static function register() {
        register_rest_route('affilixwp/v1', '/commission', [
            'methods'  => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true', // secret-based auth
        ]);
    }

    public static function handle($request) {
        global $wpdb;

        /* ----------------------------
           🔐 Verify API secret
        ----------------------------- */
        $headers = $request->get_headers();
        $secret  = $headers['x-affilixwp-secret'][0] ?? '';

        if ($secret !== get_option('affilixwp_api_secret')) {
            return new WP_Error(
                'forbidden',
                'Invalid API secret',
                ['status' => 403]
            );
        }

        /* ----------------------------
           📥 Read payload
        ----------------------------- */
        $buyer_user_id = (int) $request->get_param('buyer_user_id');
        $amount        = (float) $request->get_param('amount');
        $reference     = sanitize_text_field($request->get_param('reference'));

        if (!$buyer_user_id || !$amount || !$reference) {
            return new WP_Error(
                'invalid_request',
                'Missing buyer_user_id, amount or reference',
                ['status' => 400]
            );
        }

        $table = $wpdb->prefix . 'affilixwp_commissions';

        /* ----------------------------
           🛑 Prevent duplicates
        ----------------------------- */
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE reference = %s",
                $reference
            )
        );

        if ($exists) {
            return [
                'status' => 'duplicate',
                'reference' => $reference
            ];
        }

        /* ----------------------------
           🔗 Fetch referral chain
        ----------------------------- */
        $referrals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT referrer_user_id, level
                 FROM {$wpdb->prefix}affilixwp_referrals
                 WHERE referred_user_id = %d",
                $buyer_user_id
            )
        );

        if (!$referrals) {
            return [
                'status' => 'no_referrals',
                'buyer_user_id' => $buyer_user_id
            ];
        }

        /* ----------------------------
           💰 Create commissions
        ----------------------------- */
        foreach ($referrals as $ref) {
            $rate = ($ref->level == 1) ? 0.10 : 0.05;
            $commission = round($amount * $rate, 2);

            $wpdb->insert($table, [
                'affiliate_id'      => 0,
                'referrer_user_id'  => (int) $ref->referrer_user_id,
                'referred_user_id'  => $buyer_user_id,
                'order_amount'      => $amount,
                'commission_amount' => $commission,
                'level'             => (int) $ref->level,
                'status'            => 'pending',
                'created_at'        => current_time('mysql'),
                'reference'         => $reference,
            ]);
        }

        return [
            'status' => 'success',
            'buyer_user_id' => $buyer_user_id,
            'amount' => $amount,
            'reference' => $reference
        ];
    }
}
