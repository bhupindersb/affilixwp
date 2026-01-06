<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Referrals {

    public function __construct() {
        add_action('init', [$this, 'capture_referral']);
        add_action('user_register', [$this, 'handle_user_registration']);
    }

    public function capture_referral() {
        if (!isset($_GET['ref'])) {
            return;
        }

        $ref = sanitize_text_field($_GET['ref']);

        setcookie(
            'affilixwp_ref',
            $ref,
            time() + (30 * DAY_IN_SECONDS),
            COOKIEPATH,
            COOKIE_DOMAIN
        );
    }

    public function handle_user_registration($user_id) {
        if (!isset($_COOKIE['affilixwp_ref'])) {
            return;
        }

        $referral_code = sanitize_text_field($_COOKIE['affilixwp_ref']);

        global $wpdb;
        $affiliates = $wpdb->prefix . 'affilixwp_affiliates';
        $referrals  = $wpdb->prefix . 'affilixwp_referrals';

        // Find referrer
        $referrer = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $affiliates WHERE referral_code = %s",
                $referral_code
            )
        );

        if (!$referrer) {
            return;
        }

        // Level 1
        $wpdb->insert($referrals, [
            'referrer_id'       => $referrer->user_id,
            'referred_user_id'  => $user_id,
            'level'             => 1,
        ]);

        // Level 2 (if exists)
        $parent = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $referrals WHERE referred_user_id = %d AND level = 1",
                $referrer->user_id
            )
        );

        if ($parent) {
            $wpdb->insert($referrals, [
                'referrer_id'       => $parent->referrer_id,
                'referred_user_id'  => $user_id,
                'level'             => 2,
            ]);
        }

        // Cleanup cookie
        setcookie('affilixwp_ref', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
}
