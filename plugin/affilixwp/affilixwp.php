<?php
/**
 * Plugin Name: AffilixWP
 * Description: Affiliate & multi-level commission tracking for WordPress.
 * Version: 0.2.9
 * Author: AffilixWP
 */

if (!defined('ABSPATH')) exit;

define('AFFILIXWP_PATH', plugin_dir_path(__FILE__));
define('AFFILIXWP_URL', plugin_dir_url(__FILE__));
define('AFFILIXWP_VERSION', '0.2.9');

require_once AFFILIXWP_PATH . 'includes/class-activator.php';
require_once AFFILIXWP_PATH . 'includes/class-referrals.php';
require_once AFFILIXWP_PATH . 'includes/class-commissions.php';
require_once AFFILIXWP_PATH . 'includes/class-stripe-webhook.php';
require_once AFFILIXWP_PATH . 'includes/class-updater.php';
require_once AFFILIXWP_PATH . 'includes/class-license-validator.php';
require_once AFFILIXWP_PATH . 'includes/class-affiliates.php';
require_once AFFILIXWP_PATH . 'includes/class-commission-engine.php';

if (is_admin()) {
    require_once AFFILIXWP_PATH . 'admin/class-admin-menu.php';
}

register_activation_hook(__FILE__, ['AffilixWP_Activator', 'activate']);

register_activation_hook(__FILE__, function () {
    add_option('affilixwp_license_key', '');
    add_option('affilixwp_license_status', 'inactive');
});

add_action('plugins_loaded', function () {
    new AffilixWP_Referrals();
    new AffilixWP_Commissions();
    new AffilixWP_Stripe_Webhook();
    new AffilixWP_Affiliates();

    if (is_admin()) {
        new AffilixWP_Admin_Menu();
        new AffilixWP_Updater(__FILE__);
    }
});

add_action('admin_init', function () {
    AffilixWP_License_Validator::validate();
});
