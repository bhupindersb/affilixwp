<?php
/**
 * Plugin Name: AffilixWP
 * Description: Affiliate & multi-level commission tracking for WordPress.
 * Version: 0.3.11
 * Author: AffilixWP
 */

if (!defined('ABSPATH')) exit;

define('AFFILIXWP_PATH', plugin_dir_path(__FILE__));
define('AFFILIXWP_URL', plugin_dir_url(__FILE__));
define('AFFILIXWP_VERSION', '0.3.11');

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


if (is_admin()) {
    require_once AFFILIXWP_PATH . 'admin/class-dashboard.php';
    require_once AFFILIXWP_PATH . 'admin/class-admin-menu.php';
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

    if (is_admin()) {
        new AffilixWP_Admin_Menu();
        new AffilixWP_Updater(__FILE__);
    }
});

/**
 * License validation (admin only)
 */
add_action('admin_init', function () {
    AffilixWP_License_Validator::validate();
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

