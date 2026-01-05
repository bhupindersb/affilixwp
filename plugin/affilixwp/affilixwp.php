<?php
/**
 * Plugin Name: AffilixWP
 * Description: Affiliate & multi-level commission tracking for WordPress.
 * Version: 0.1.2
 * Author: AffilixWP
 */

if (!defined('ABSPATH')) exit;

define('AFFILIXWP_PATH', plugin_dir_path(__FILE__));
define('AFFILIXWP_URL', plugin_dir_url(__FILE__));
define('AFFILIXWP_VERSION', '0.1.3');

require_once AFFILIXWP_PATH . 'includes/class-activator.php';
require_once AFFILIXWP_PATH . 'includes/class-referrals.php';
require_once AFFILIXWP_PATH . 'includes/class-commissions.php';
require_once AFFILIXWP_PATH . 'includes/class-stripe-webhook.php';

if (is_admin()) {
    require_once AFFILIXWP_PATH . 'admin/class-admin-menu.php';
}

register_activation_hook(__FILE__, ['AffilixWP_Activator', 'activate']);

add_action('plugins_loaded', function () {
    new AffilixWP_Referrals();
    new AffilixWP_Commissions();
    new AffilixWP_Stripe_Webhook();

    if (is_admin()) {
        new AffilixWP_Admin_Menu();
    }
});

add_option('affilixwp_license_key', '');
add_option('affilixwp_license_status', 'inactive');
