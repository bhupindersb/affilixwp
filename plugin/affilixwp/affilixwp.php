<?php
/**
 * Plugin Name: AffilixWP
 * Description: Affiliate & multi-level commission tracking for WordPress.
 * Version: 0.2.24
 * Author: AffilixWP
 */

if (!defined('ABSPATH')) exit;

add_action('admin_init', function () {
    if (current_user_can('manage_options')) {
        delete_site_transient('update_plugins');
    }
});


define('AFFILIXWP_PATH', plugin_dir_path(__FILE__));
define('AFFILIXWP_URL', plugin_dir_url(__FILE__));
define('AFFILIXWP_VERSION', '0.2.24');

require_once AFFILIXWP_PATH . 'includes/class-activator.php';
require_once AFFILIXWP_PATH . 'includes/class-referrals.php';
require_once AFFILIXWP_PATH . 'includes/class-commissions.php';
require_once AFFILIXWP_PATH . 'includes/class-stripe-webhook.php';
require_once AFFILIXWP_PATH . 'includes/class-updater.php';
require_once AFFILIXWP_PATH . 'includes/class-license-validator.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliates.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-engine.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliate-dashboard.php';
require_once AFFILIXWP_PATH . 'includes/class-referral-tracker.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-api.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-rest.php';


// ✅ Ensure REST routes are always registered
add_action('init', function () {
    if (class_exists('AffilixWP_Commission_REST')) {
        add_action('rest_api_init', ['AffilixWP_Commission_REST', 'register']);
    }
});


if (is_admin()) {
    require_once AFFILIXWP_PATH . 'admin/class-admin-menu.php';
}

register_activation_hook(__FILE__, ['AffilixWP_Activator', 'activate']);

register_activation_hook(__FILE__, function () {

    if (!get_option('affilixwp_api_secret')) {
        add_option(
            'affilixwp_api_secret',
            wp_generate_password(32, false)
        );
    }

    add_option('affilixwp_license_key', '');
    add_option('affilixwp_license_status', 'inactive');
});


add_action('plugins_loaded', function () {
    new AffilixWP_Referrals();
    new AffilixWP_Commissions();
    new AffilixWP_Stripe_Webhook();
    new AffilixWP_Affiliates();
    new AffilixWP_Affiliate_Dashboard();
    new AffilixWP_Referral_Tracker();
    new AffilixWP_Commission_API();


    if (is_admin()) {
        new AffilixWP_Admin_Menu();
        new AffilixWP_Updater(__FILE__);
    }
});


add_action('admin_init', function () {
    AffilixWP_License_Validator::validate();
});



add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['affilixwp_test_commission'])) {
        $buyer_user_id = (int) $_GET['affilixwp_test_commission'];
        AffilixWP_Commission_Engine::add_manual_test_commission($buyer_user_id);
    }
});

add_action('wp_enqueue_scripts', function () {

    if (!is_user_logged_in()) {
        return;
    }

    wp_enqueue_script(
        'razorpay-checkout',
        'https://checkout.razorpay.com/v1/checkout.js',
        [],
        null,
        true
    );

    wp_enqueue_script(
        'affilixwp-checkout',
        AFFILIXWP_URL . 'assets/js/checkout.js',
        ['razorpay-checkout'],
        AFFILIXWP_VERSION,
        true
    );

    wp_localize_script('affilixwp-checkout', 'AffilixWP', [
        'wp_user_id'    => get_current_user_id(),
        'api_url'       => 'https://www.beveez.tech/api',
        'razorpay_key'  => 'rzp_test_Rz1RCeqZkvRXeE', // 🔁 YOUR PUBLIC KEY
    ]);
});
