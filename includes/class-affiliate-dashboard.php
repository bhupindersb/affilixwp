<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Affiliate_Dashboard {

    public function __construct() {
        add_shortcode('affilixwp_dashboard', [$this, 'render_shortcode']);
    }

    public function render_shortcode() {

        if (!is_user_logged_in()) {
            return '<p>You must be logged in to view your affiliate dashboard.</p>';
        }

        $user_id = get_current_user_id();

        ob_start();
        include AFFILIXWP_PATH . 'templates/affiliate-dashboard.php';
        return ob_get_clean();
    }
}
