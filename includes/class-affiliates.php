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
            $wpdb->prepare("SELECT id FROM $table WHERE user_id = %d", $user_id)
        );

        if ($exists) {
            return;
        }

        // referral_code is NOT NULL and UNIQUE in the table schema.
        // Generate a collision-checked code before inserting the affiliate.
        do {
            $referral_code = strtoupper(wp_generate_password(10, false, false));
            $collision = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM $table WHERE referral_code = %s", $referral_code)
            );
        } while ($collision);

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'       => (int) $user_id,
                'referral_code' => $referral_code,
                'status'        => 'active',
                'created_at'    => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            error_log('AffilixWP: unable to create affiliate record for user ' . (int) $user_id . ': ' . $wpdb->last_error);
        }
    }
}
