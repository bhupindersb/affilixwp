<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Dashboard_Actions {

    public static function init() {
        add_action('admin_post_affilixwp_mark_paid', [__CLASS__, 'mark_paid']);
    }

    public static function mark_paid() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if (!isset($_POST['commission_ids']) || !is_array($_POST['commission_ids'])) {
            wp_redirect(wp_get_referer());
            exit;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'affilixwp_commissions';

        $ids = array_map('intval', $_POST['commission_ids']);
        $ids_sql = implode(',', $ids);

        $wpdb->query(
            "UPDATE $table
             SET status = 'paid',
                 paid_at = NOW()
             WHERE id IN ($ids_sql)
             AND status = 'approved'"
        );

        wp_redirect(add_query_arg('paid', '1', wp_get_referer()));
        exit;
    }
}
