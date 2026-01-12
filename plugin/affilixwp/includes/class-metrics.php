<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Metrics {

    /**
     * Get dashboard metrics
     */
    public static function get_metrics($days = 30) {
        $cache_key = "affilixwp_metrics_{$days}";
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'referral_commissions';

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // 🔹 Total Revenue
        $total_revenue = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(purchase_amount) FROM $table WHERE purchase_date >= %s",
                $since
            )
        );

        // 🔹 Total Commissions
        $total_commissions = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(commission_amount) FROM $table WHERE purchase_date >= %s",
                $since
            )
        );

        // 🔹 Conversions
        $conversions = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE purchase_date >= %s",
                $since
            )
        );

        // 🔹 Active Affiliates
        $active_affiliates = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT referrer_username) FROM $table"
        );

        $metrics = [
            'revenue'      => round($total_revenue, 2),
            'commissions'  => round($total_commissions, 2),
            'conversions'  => $conversions,
            'affiliates'   => $active_affiliates,
        ];

        // Cache for 5 minutes
        set_transient($cache_key, $metrics, 5 * MINUTE_IN_SECONDS);

        return $metrics;
    }
}
