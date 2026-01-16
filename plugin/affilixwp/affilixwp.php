<?php
/**
 * Plugin Name: AffilixWP
 * Description: Affiliate & multi-level commission tracking for WordPress.
 * Version: 0.3.59
 * Author: AffilixWP
 */

if (!defined('ABSPATH')) exit;

define('AFFILIXWP_PATH', plugin_dir_path(__FILE__));
define('AFFILIXWP_URL', plugin_dir_url(__FILE__));
define('AFFILIXWP_VERSION', '0.3.59');

/**
 * Load core
 */
require_once AFFILIXWP_PATH . 'includes/class-activator.php';
require_once AFFILIXWP_PATH . 'includes/class-license-validator.php';
require_once AFFILIXWP_PATH . 'includes/class-updater.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliates.php';
require_once AFFILIXWP_PATH . 'includes/class-referral-tracker.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-api.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-engine.php';
require_once AFFILIXWP_PATH . 'includes/class-stripe-webhook.php';
require_once AFFILIXWP_PATH . 'includes/class-metrics.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliate-dashboard.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-cron.php';
require_once AFFILIXWP_PATH . 'admin/class-dashboard-actions.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliate-payout-profile.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliate-frontend.php';
require_once AFFILIXWP_PATH . 'includes/class-razorpay-api.php';
require_once AFFILIXWP_PATH . 'includes/class-razorpay-webhook.php';

/**
 * Load admin only
 */
if (is_admin()) {
    require_once AFFILIXWP_PATH . 'admin/class-dashboard.php';
    require_once AFFILIXWP_PATH . 'admin/class-admin-menu.php';
    require_once AFFILIXWP_PATH . 'admin/class-admin-payouts.php';
    require_once AFFILIXWP_PATH . 'admin/class-razorpay-settings.php';
}


/**
 * Activation
 */
register_activation_hook(__FILE__, ['AffilixWP_Activator', 'activate']);

/**
 * Boot
 */
add_action('plugins_loaded', function () {

    new AffilixWP_Affiliates();
    new AffilixWP_Referral_Tracker();
    new AffilixWP_Commission_API();
    new AffilixWP_Stripe_Webhook();
    new AffilixWP_Affiliate_Dashboard();
    new AffilixWP_Razorpay_API();
    new AffilixWP_Razorpay_Webhook();

    if (is_admin()) {
        new AffilixWP_Admin_Menu();
        new AffilixWP_Admin_Payouts();
        new AffilixWP_Updater(__FILE__);
        AffilixWP_Razorpay_Settings::init();
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

function affilixwp_enqueue_checkout_scripts() {

    // Razorpay Checkout
    wp_enqueue_script(
        'razorpay-checkout',
        'https://checkout.razorpay.com/v1/checkout.js',
        [],
        null,
        true
    );

    // Your checkout.js
    wp_enqueue_script(
        'affilixwp-checkout',
        plugin_dir_url(__FILE__) . 'assets/js/checkout.js',
        ['razorpay-checkout'],
        '1.0',
        true
    );

    // 👇 THIS IS CRITICAL
    wp_localize_script(
        'affilixwp-checkout',
        'AffilixWP',
        [
            'wp_user_id'   => get_current_user_id(),
            'api_url'      => rest_url('affilixwp/v1'),
            'razorpay_key' => get_option('affilixwp_razorpay_key'),
            'nonce'        => wp_create_nonce('wp_rest'),
        ]
    );

}
add_action('wp_enqueue_scripts', 'affilixwp_enqueue_checkout_scripts');



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

    $where = "status = 'pending'";

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
        SELECT *
        FROM $table
        WHERE $where
        ORDER BY created_at DESC
    ");

    if (headers_sent()) {
        wp_die('Headers already sent. Disable output before export.');
    }

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=affilixwp-commissions.csv');

    $out = fopen('php://output', 'w');

    fputcsv($out, [
        'Date',
        'Affiliate ID',
        'Buyer ID',
        'Order Amount',
        'Commission',
        'Status'
    ]);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r->created_at,
            $r->referrer_user_id,
            $r->referred_user_id,
            $r->order_amount,
            $r->commission_amount,
            $r->status
        ]);
    }

    fclose($out);
    exit;
}


AffilixWP_Commission_Cron::init();

if (!wp_next_scheduled('affilixwp_daily_cron')) {
    wp_schedule_event(time(), 'daily', 'affilixwp_daily_cron');
}

AffilixWP_Dashboard_Actions::init();

AffilixWP_Affiliate_Payout_Profile::init();

add_shortcode('affilixwp_dashboard', ['AffilixWP_Affiliate_Frontend', 'shortcode']);

add_action('admin_post_affilixwp_export_payouts', 'affilixwp_export_payouts_csv');

function affilixwp_export_payouts_csv() {

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('affilixwp_export_payouts');

    global $wpdb;
    $table = $wpdb->prefix . 'affilixwp_commissions';

    // Group approved commissions by affiliate
    $rows = $wpdb->get_results("
        SELECT
            referrer_user_id,
            SUM(commission_amount) AS total_commission,
            GROUP_CONCAT(id ORDER BY id ASC) AS commission_ids
        FROM $table
        WHERE status = 'pending'
        GROUP BY referrer_user_id
        HAVING total_commission > 0
    ");

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=affilixwp-approved-payouts.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Affiliate ID',
        'Affiliate Name',
        'Total Commission',
        'Commission IDs'
    ]);

    foreach ($rows as $row) {

        $user = get_user_by('id', (int)$row->referrer_user_id);
        $name = $user ? $user->display_name : 'User #' . $row->referrer_user_id;

        fputcsv($output, [
            $row->referrer_user_id,
            $name,
            number_format((float)$row->total_commission, 2, '.', ''),
            $row->commission_ids
        ]);
    }

    fclose($output);
    exit;
}

/**
 * Schedule daily payouts
 */
add_action('plugins_loaded', function () {

    AffilixWP_Commission_Cron::init();

    if (!wp_next_scheduled('affilixwp_daily_payouts')) {
        wp_schedule_event(time(), 'daily', 'affilixwp_daily_payouts');
    }
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('affilixwp_daily_payouts');
});

add_action('admin_init', function () {

    register_setting(
        'affilixwp_settings',
        'affilixwp_min_payout',
        [
            'type'              => 'number',
            'sanitize_callback' => 'floatval',
            'default'           => 500
        ]
    );
});

