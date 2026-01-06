<?php
class AffilixWP_Commissions {

    public function record_commission($user_id, $order_id, $amount) {
        global $wpdb;
        $referrals_table = $wpdb->prefix . 'affilixwp_referrals';
        $commissions_table = $wpdb->prefix . 'affilixwp_commissions';

        $referrals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $referrals_table WHERE referred_user_id = %d",
                $user_id
            )
        );

        foreach ($referrals as $ref) {
            $rate = ($ref->level === 1) ? 0.10 : 0.05;
            $commission = round($amount * $rate, 2);

            $wpdb->insert(
                $wpdb->prefix . 'affilixwp_commissions',
                [
                    'affiliate_id'      => $affiliate_id,
                    'referred_user_id'  => $user_id,
                    'amount'            => $amount,
                    'commission'        => $commission,
                    'level'             => $level,
                    'source'            => 'manual_test',
                    'created_at'        => current_time('mysql'),
                ],
                ['%d', '%d', '%f', '%f', '%d', '%s', '%s']
            );
        }
    }
}
