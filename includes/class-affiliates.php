<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Affiliates {

    public function __construct() {
        add_action('user_register', [$this, 'create_affiliate'], 5);
    }

    public function create_affiliate($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'affilixwp_affiliates';

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d",
                $user_id
            )
        );

        if ($exists) {
            return;
        }

        $wpdb->insert(
            $table,
            [
                'user_id'    => $user_id,
                'status'     => 'active',
                'created_at' => current_time('mysql'),
            ],
            ['%d','%s','%s']
        );
    }
}
