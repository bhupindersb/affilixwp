<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Payout_Engine {

    public static function get_payable_affiliates() {
        global $wpdb;
        $table = $wpdb->prefix . 'affilixwp_commissions';

        return $wpdb->get_results("
            SELECT referrer_user_id, SUM(commission_amount) AS amount
            FROM $table
            WHERE status='approved'
            GROUP BY referrer_user_id
            HAVING amount > 0
        ");
    }

    public static function mark_paid($user_id) {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}affilixwp_commissions
                 SET status='paid', paid_at=NOW()
                 WHERE referrer_user_id=%d AND status='approved'",
                $user_id
            )
        );
    }
}
