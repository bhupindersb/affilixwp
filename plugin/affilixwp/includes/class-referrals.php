<?php
class AffilixWP_Referrals {

    public function __construct() {
        add_action('user_register', [$this, 'capture_referral']);
    }

    public function capture_referral($user_id) {
        if (empty($_COOKIE['affilixwp_referrer'])) return;

        $referrer_id = intval($_COOKIE['affilixwp_referrer']);
        if ($referrer_id === $user_id) return;

        global $wpdb;
        $table = $wpdb->prefix . 'affilixwp_referrals';

        // Level 1
        $wpdb->insert($table, [
            'referrer_user_id' => $referrer_id,
            'referred_user_id' => $user_id,
            'level' => 1
        ]);

        // Level 2 (if exists)
        $parent_referrer = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT referrer_user_id FROM $table WHERE referred_user_id = %d AND level = 1",
                $referrer_id
            )
        );

        if ($parent_referrer) {
            $wpdb->insert($table, [
                'referrer_user_id' => $parent_referrer,
                'referred_user_id' => $user_id,
                'level' => 2
            ]);
        }
    }
}
