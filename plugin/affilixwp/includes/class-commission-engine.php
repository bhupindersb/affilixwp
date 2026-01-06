<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_Engine {

    /**
     * Canonical purchase handler
     */
    public static function record_purchase($buyer_user_id, $order_amount = 100, $reference = 'manual_test') {
        global $wpdb;

        $commissions_table = $wpdb->prefix . 'affilixwp_commissions';
        $referrals_table   = $wpdb->prefix . 'affilixwp_referrals';

        // Prevent duplicate processing
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $commissions_table 
             WHERE source_user_id = %d AND reference = %s",
            $buyer_user_id,
            $reference
        ));

        if ($exists) {
            return;
        }

        // Fetch referral chain
        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer_user_id, level 
             FROM $referrals_table
             WHERE referred_user_id = %d",
            $buyer_user_id
        ));

        foreach ($referrals as $ref) {
            $rate = ($ref->level == 1) ? 0.10 : 0.05;
            $commission = $order_amount * $rate;

            $wpdb->insert(
                $commissions_table,
                [
                    'affiliate_id'       => $ref->referrer_user_id,
                    'referrer_user_id'   => $ref->referrer_user_id,
                    'source_user_id'     => $buyer_user_id,
                    'order_amount'       => $order_amount,
                    'commission_amount'  => $commission,
                    'level'              => $ref->level,
                    'status'             => 'pending',
                    'reference'          => $reference,
                    'created_at'         => current_time('mysql'),
                ],
                [
                    '%d','%d','%d','%f','%f','%d','%s','%s','%s'
                ]
            );
        }
    }

    /**
     * Backward-compatible helper for testing
     */
    public static function add_manual_test_commission($buyer_user_id) {
        self::record_purchase($buyer_user_id, 100, 'manual_test');
    }
}