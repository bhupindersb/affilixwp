<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Metrics {

    /**
     * Get last 30 days revenue + commissions
     */
    public static function get_chart_data() {
        global $wpdb;

        $table = $wpdb->prefix . 'affilixwp_commissions';

        $results = $wpdb->get_results("
            SELECT 
                DATE(created_at) as day,
                SUM(purchase_amount) as revenue,
                SUM(commission_amount) as commission
            FROM $table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", ARRAY_A);

        $days = [];
        $revenue = [];
        $commission = [];

        foreach ($results as $row) {
            $days[] = $row['day'];
            $revenue[] = (float) $row['revenue'];
            $commission[] = (float) $row['commission'];
        }

        return [
            'days' => $days,
            'revenue' => $revenue,
            'commission' => $commission,
        ];
    }
}
