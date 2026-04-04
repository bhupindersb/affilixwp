<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_Cron {

    public static function init() {
        add_action('affilixwp_daily_payouts', [__CLASS__, 'run_payouts']);
    }

    /**
     * Run daily payouts
     */
    public static function run_payouts() {
        global $wpdb;

        $table = $wpdb->prefix . 'affilixwp_commissions';
        $min_payout = (float) get_option('affilixwp_min_payout', 500);

        // Group unpaid commissions by affiliate
        $rows = $wpdb->get_results("
            SELECT 
                referrer_user_id,
                SUM(commission_amount) AS total
            FROM $table
            WHERE status = 'pending'
            GROUP BY referrer_user_id
        ");

        if (empty($rows)) {
            error_log('[AffilixWP] No pending commissions.');
            return;
        }

        foreach ($rows as $row) {

            // Skip if threshold not met
            if ((float) $row->total < $min_payout) {
                continue;
            }

            // Mark all pending commissions for this affiliate as paid
            $wpdb->update(
                $table,
                [
                    'status'  => 'paid',
                    'paid_at' => current_time('mysql')
                ],
                [
                    'referrer_user_id' => (int) $row->referrer_user_id,
                    'status'           => 'pending'
                ]
            );

            error_log(
                sprintf(
                    '[AffilixWP] Paid affiliate #%d amount ₹%0.2f',
                    $row->referrer_user_id,
                    $row->total
                )
            );
        }
    }

}
