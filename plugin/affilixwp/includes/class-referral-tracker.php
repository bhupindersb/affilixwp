<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Referral_Tracker {

    const COOKIE = 'affilixwp_ref_code';

    public function __construct() {
        add_action('init', [$this, 'capture_referral']);
        add_action('user_register', [$this, 'record_referral'], 20);
    }

    /**
     * Capture ?ref=REFERRAL_CODE
     */
    public function capture_referral() {

        if (empty($_GET['ref'])) {
            return;
        }

        $ref_code = sanitize_text_field($_GET['ref']);

        // Basic sanity check
        if (strlen($ref_code) < 5) {
            return;
        }

        setcookie(
            self::COOKIE,
            $ref_code,
            time() + (30 * DAY_IN_SECONDS),
            COOKIEPATH,
            COOKIE_DOMAIN
        );

        $_COOKIE[self::COOKIE] = $ref_code;
    }

    /**
     * Create referral records on registration
     */
    public function record_referral($new_user_id) {

        if (empty($_COOKIE[self::COOKIE])) {
            return;
        }

        global $wpdb;

        $ref_code = sanitize_text_field($_COOKIE[self::COOKIE]);

        $affiliates = $wpdb->prefix . 'affilixwp_affiliates';
        $referrals  = $wpdb->prefix . 'affilixwp_referrals';

        // Find referrer by referral_code
        $affiliate = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $affiliates WHERE referral_code = %s AND status = 'active'",
                $ref_code
            )
        );

        if (!$affiliate) {
            return;
        }

        $referrer_user_id = (int) $affiliate->user_id;

        if ($referrer_user_id === $new_user_id) {
            return;
        }

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
