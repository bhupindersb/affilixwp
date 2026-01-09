<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_Engine {

    public static function record_purchase($buyer_user_id, $order_amount, $reference) {
        global $wpdb;

        $commissions = $wpdb->prefix . 'affilixwp_commissions';
        $referrals   = $wpdb->prefix . 'affilixwp_referrals';

        // Prevent duplicates
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $commissions WHERE reference = %s",
                $reference
            )
        );

        if ($exists) {
            return;
        }

        // Fetch referral chain
        $chain = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT referrer_user_id, level
                 FROM $referrals
                 WHERE referred_user_id = %d",
                $buyer_user_id
            )
        );

        foreach ($chain as $ref) {
            $rate = ($ref->level == 1) ? 0.10 : 0.05;
            $commission = round($order_amount * $rate, 2);

            $wpdb->insert(
                $commissions,
                [
                    'affiliate_id'      => $ref->referrer_user_id,
                    'referrer_user_id'  => $ref->referrer_user_id,
                    'source_user_id'    => $buyer_user_id,
                    'order_amount'      => $order_amount,
                    'commission_amount' => $commission,
                    'level'             => $ref->level,
                    'status'            => 'pending',
                    'reference'         => $reference,
                    'created_at'        => current_time('mysql'),
                ],
                ['%d','%d','%d','%f','%f','%d','%s','%s','%s']
            );
        }
    }
}
