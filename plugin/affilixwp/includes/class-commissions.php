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

            $wpdb->insert($commissions_table, [
                'referrer_user_id' => $ref->referrer_user_id,
                'referred_user_id' => $user_id,
                'order_id' => $order_id,
                'level' => $ref->level,
                'commission_amount' => $commission,
                'order_amount' => $amount,
            ]);
        }
    }
}
