<?php
class AffilixWP_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
    }

    public function menu() {
        add_menu_page(
            'AffilixWP',
            'AffilixWP',
            'manage_options',
            'affilixwp',
            [$this, 'dashboard'],
            'dashicons-groups'
        );
    }

    public function dashboard() {
        include AFFILIXWP_PATH . 'admin/dashboard.php';
    }
}
