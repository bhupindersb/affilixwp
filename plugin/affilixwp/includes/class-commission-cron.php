<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_Cron {

    public static function init() {
        add_action('affilixwp_daily_cron', [__CLASS__, 'auto_approve']);
    }

    public static function auto_approve() {
        global $wpdb;

        $delay = (int) get_option('affilixwp_approval_delay_days', 14);
        $table = $wpdb->prefix . 'affilixwp_commissions';

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table
                 SET status = 'approved'
                 WHERE status = 'pending'
                 AND created_at <= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $delay
            )
        );
    }
}
