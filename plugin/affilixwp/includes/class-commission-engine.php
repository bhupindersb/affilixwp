<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_Engine {

    public static function add_manual_test_commission($buyer_user_id, $order_amount = 100) {
        global $wpdb;

        $table = $wpdb->prefix . 'affilixwp_commissions';

        // Prevent duplicate test commissions
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE source_user_id = %d AND source = %s",
            $buyer_user_id,
            'manual_test'
        ));

        if ($exists) {
            return;
        }

        // Get referral chain
        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer_user_id, level 
             FROM {$wpdb->prefix}affilixwp_referrals
             WHERE referred_user_id = %d",
            $buyer_user_id
        ));

        foreach ($referrals as $ref) {
            $commission_rate = ($ref->level === 1) ? 0.10 : 0.05;
            $commission = $order_amount * $commission_rate;

            $wpdb->insert(
                $table,
                [
                    'affiliate_id'       => $ref->referrer_user_id,
                    'referrer_user_id'   => $ref->referrer_user_id,
                    'source_user_id'     => $buyer_user_id,
                    'order_amount'       => $order_amount,
                    'commission_amount'  => $commission,
                    'level'              => $ref->level,
                    'status'             => 'pending',
                    'source'             => 'manual_test',
                    'created_at'         => current_time('mysql'),
                ],
                [
                    '%d','%d','%d','%f','%f','%d','%s','%s','%s'
                ]
            );
        }
    }
}
