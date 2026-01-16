<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Referral_Tracker {

    const COOKIE = 'affilixwp_referrer';

    public function __construct() {
        add_action('init', [$this, 'capture_referral']);
        add_action('user_register', [$this, 'record_referral'], 20);
    }

    /**
     * Capture ?ref={USER_ID}
     */
    public function capture_referral() {

        if (empty($_GET['ref'])) {
            return;
        }

        $referrer_user_id = (int) $_GET['ref'];

        if ($referrer_user_id <= 0 || !get_user_by('id', $referrer_user_id)) {
            return;
        }

        setcookie(
            self::COOKIE,
            $referrer_user_id,
            time() + (30 * DAY_IN_SECONDS),
            COOKIEPATH,
            COOKIE_DOMAIN
        );

        $_COOKIE[self::COOKIE] = $referrer_user_id;
    }

    /**
     * Create referral rows on registration
     */
    public function record_referral($new_user_id) {

        if (empty($_COOKIE[self::COOKIE])) {
            return;
        }

        global $wpdb;

        $referrer_user_id = (int) $_COOKIE[self::COOKIE];

        if ($referrer_user_id <= 0 || $referrer_user_id === $new_user_id) {
            return;
        }

        $referrals = $wpdb->prefix . 'affilixwp_referrals';

        // Prevent duplicate
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $referrals WHERE referred_user_id = %d AND level = 1",
                $new_user_id
            )
        );

        if ($exists) {
            return;
        }

        /**
         * LEVEL 1
         */
        $wpdb->insert(
            $referrals,
            [
                'referrer_user_id' => $referrer_user_id,
                'referred_user_id' => $new_user_id,
                'level'            => 1,
                'created_at'       => current_time('mysql'),
            ],
            ['%d','%d','%d','%s']
        );

        /**
         * LEVEL 2 (if exists)
         */
        $parent = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT referrer_user_id FROM $referrals
                 WHERE referred_user_id = %d AND level = 1",
                $referrer_user_id
            )
        );

        if ($parent) {
            $wpdb->insert(
                $referrals,
                [
                    'referrer_user_id' => (int) $parent->referrer_user_id,
                    'referred_user_id' => $new_user_id,
                    'level'            => 2,
                    'created_at'       => current_time('mysql'),
                ],
                ['%d','%d','%d','%s']
            );
        }

        // Cleanup
        setcookie(self::COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        unset($_COOKIE[self::COOKIE]);
    }
}
