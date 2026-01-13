<?php
/**
 * Plugin Name: AffilixWP
 * Description: Affiliate & multi-level commission tracking for WordPress.
 * Version: 0.3.30
 * Author: AffilixWP
 */

if (!defined('ABSPATH')) exit;

define('AFFILIXWP_PATH', plugin_dir_path(__FILE__));
define('AFFILIXWP_URL', plugin_dir_url(__FILE__));
define('AFFILIXWP_VERSION', '0.3.30');

/**
 * Load core
 */
require_once AFFILIXWP_PATH . 'includes/class-activator.php';
require_once AFFILIXWP_PATH . 'includes/class-license-validator.php';
require_once AFFILIXWP_PATH . 'includes/class-updater.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-api.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-engine.php';
require_once AFFILIXWP_PATH . 'includes/class-stripe-webhook.php';
require_once AFFILIXWP_PATH . 'includes/class-metrics.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliate-dashboard.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-cron.php';
require_once AFFILIXWP_PATH . 'admin/class-dashboard-actions.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliate-payout-profile.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliate-frontend.php';



if (is_admin()) {
    require_once AFFILIXWP_PATH . 'admin/class-dashboard.php';
    require_once AFFILIXWP_PATH . 'admin/class-admin-menu.php';
    require_once AFFILIXWP_PATH . 'admin/class-admin-payouts.php';
}


/**
 * Activation
 */
register_activation_hook(__FILE__, ['AffilixWP_Activator', 'activate']);

/**
 * Boot
 */
add_action('plugins_loaded', function () {

    new AffilixWP_Commission_API();
    new AffilixWP_Stripe_Webhook();
    new AffilixWP_Affiliate_Dashboard();


    if (is_admin()) {
        new AffilixWP_Admin_Menu();
        new AffilixWP_Admin_Payouts();
        new AffilixWP_Updater(__FILE__);
    }

});

/**
 * License validation (admin only)
 */
add_action('admin_init', function () {
    AffilixWP_License_Validator::validate();
    if (get_option('affilixwp_approval_delay_days') === false) {
        add_option('affilixwp_approval_delay_days', 14);
    }
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js',
        [],
        '4.4.1',
        true
    );
});

add_action('admin_post_affilixwp_export_commissions', 'affilixwp_export_commissions_csv');

function affilixwp_export_commissions_csv() {

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb;

    $table = $wpdb->prefix . 'affilixwp_commissions';

    $where = '1=1';

    if (!empty($_GET['from'])) {
        $where .= $wpdb->prepare(" AND created_at >= %s", $_GET['from']);
    }

    if (!empty($_GET['to'])) {
        $where .= $wpdb->prepare(" AND created_at <= %s", $_GET['to'] . ' 23:59:59');
    }

    if (!empty($_GET['affiliate'])) {
        $where .= $wpdb->prepare(" AND referrer_user_id = %d", (int) $_GET['affiliate']);
    }

    $rows = $wpdb->get_results("
        SELECT * FROM $table
        WHERE $where
        ORDER BY created_at DESC
    ");

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=affilixwp-commissions.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Date',
        'Affiliate ID',
        'Buyer ID',
        'Order Amount',
        'Commission',
        'Status'
    ]);

    foreach ($rows as $r) {
        fputcsv($output, [
            $r->created_at,
            $r->referrer_user_id,
            $r->referred_user_id,
            $r->order_amount,
            $r->commission_amount,
            $r->status
        ]);
    }

    fclose($output);
    exit;
}

AffilixWP_Commission_Cron::init();

if (!wp_next_scheduled('affilixwp_daily_cron')) {
    wp_schedule_event(time(), 'daily', 'affilixwp_daily_cron');
}

AffilixWP_Dashboard_Actions::init();

AffilixWP_Affiliate_Payout_Profile::init();

add_shortcode('affilixwp_dashboard', ['AffilixWP_Affiliate_Frontend', 'shortcode']);

