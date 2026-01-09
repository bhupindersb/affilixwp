<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_REST {

    public static function register() {
        register_rest_route('affilixwp/v1', '/record-purchase', [
            'methods'  => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle($request) {
        global $wpdb;

        // 🔐 Verify secret
        $secret = $request->get_header('X-AffilixWP-Secret');
        if ($secret !== get_option('affilixwp_api_secret')) {
            return new WP_Error('unauthorized', 'Invalid secret', ['status' => 403]);
        }

        $user_id  = (int) $request['user_id'];
        $amount   = (float) $request['amount'];
        $reference = sanitize_text_field($request['reference']);

        if (!$user_id || !$amount || !$reference) {
            return new WP_Error('invalid', 'Missing parameters');
        }

        $table = $wpdb->prefix . 'affilixwp_commissions';

        // 🛑 Prevent duplicate commissions
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE reference = %s",
            $reference
        ));

        if ($exists) {
            return ['status' => 'duplicate'];
        }

        // 🔗 Get referral chain
        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer_user_id, level
             FROM {$wpdb->prefix}affilixwp_referrals
             WHERE referred_user_id = %d",
            $user_id
        ));

        foreach ($referrals as $ref) {
            $rate = $ref->level == 1 ? 0.10 : 0.05;
            $commission = round($amount * $rate, 2);

            $wpdb->insert($table, [
                'affiliate_id'       => 0,
                'referrer_user_id'   => $ref->referrer_user_id,
                'referred_user_id'   => $user_id,
                'order_amount'       => $amount,
                'commission_amount'  => $commission,
                'level'              => $ref->level,
                'status'             => 'pending',
                'created_at'         => current_time('mysql'),
                'reference'          => $reference,
            ]);
        }

        return ['status' => 'success'];
    }
}
