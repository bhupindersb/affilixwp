<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Affiliates {

    public function __construct() {
        add_action('user_register', [$this, 'create_affiliate'], 10, 1);
    }

    public function create_affiliate($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'affilixwp_affiliates';

        // Safety: avoid duplicates
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d",
                $user_id
            )
        );

        if ($exists) {
            return;
        }

        // Generate referral code
        $referral_code = strtoupper(wp_generate_password(8, false, false));

        $wpdb->insert(
            $table,
            [
                'user_id'       => $user_id,
                'referral_code' => $referral_code,
                'status'        => 'active',
            ],
            ['%d', '%s', '%s']
        );
    }
}
