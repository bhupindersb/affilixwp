<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_Engine {

    const LEVEL_1_RATE = 0.10; // 10%
    const LEVEL_2_RATE = 0.05; // 5%

    public static function record_purchase($user_id, $amount, $source = 'manual') {
        global $wpdb;

        if (!$user_id || $amount <= 0) {
            return false;
        }

        // Fetch referral chain
        $referrals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}affilixwp_referrals 
                 WHERE referred_user_id = %d",
                $user_id
            )
        );

        if (empty($referrals)) {
            return false;
        }

        foreach ($referrals as $ref) {
            $rate = ($ref->level == 1)
                ? self::LEVEL_1_RATE
                : (($ref->level == 2) ? self::LEVEL_2_RATE : 0);

            if ($rate === 0) continue;

            $commission = round($amount * $rate, 2);

            // Prevent duplicate commissions
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}affilixwp_commissions
                     WHERE user_id = %d AND affiliate_id = %d AND source = %s",
                    $user_id,
                    $ref->referrer_user_id,
                    $source
                )
            );

            if ($exists) continue;

            $wpdb->insert(
                "{$wpdb->prefix}affilixwp_commissions",
                [
                    'affiliate_id' => $ref->referrer_user_id,
                    'user_id'      => $user_id,
                    'amount'       => $amount,
                    'commission'   => $commission,
                    'level'        => $ref->level,
                    'source'       => $source,
                    'created_at'   => current_time('mysql'),
                ],
                ['%d','%d','%f','%f','%d','%s','%s']
            );
        }

        return true;
    }
}
