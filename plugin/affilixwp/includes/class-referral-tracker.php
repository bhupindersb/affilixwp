<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Referral_Tracker {

    const COOKIE_NAME = 'affilixwp_ref';
    const COOKIE_LIFETIME = 30; // days

    public function __construct() {
        add_action('init', [$this, 'capture_referral']);
        add_action('user_register', [$this, 'handle_user_registration']);
    }

    /**
     * Capture ?ref=CODE and store in cookie
     */
    public function capture_referral() {

        if (isset($_GET['ref']) && !empty($_GET['ref'])) {
            $ref = sanitize_text_field($_GET['ref']);

            setcookie(
                self::COOKIE_NAME,
                $ref,
                time() + (DAY_IN_SECONDS * self::COOKIE_LIFETIME),
                COOKIEPATH,
                COOKIE_DOMAIN
            );

            $_COOKIE[self::COOKIE_NAME] = $ref;
        }
    }

    /**
     * On user registration, create referral records
     */
    public function handle_user_registration($user_id) {

        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return;
        }

        global $wpdb;

        $referral_code = sanitize_text_field($_COOKIE[self::COOKIE_NAME]);

        // Find affiliate by referral code
        $affiliate = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}affilixwp_affiliates WHERE referral_code = %s AND status = 'active'",
                $referral_code
            )
        );

        if (!$affiliate) {
            return;
        }

        $referrer_user_id = (int) $affiliate->user_id;

        // Level 1 referral
        $wpdb->insert(
            "{$wpdb->prefix}affilixwp_referrals",
            [
                'referrer_user_id' => $referrer_user_id,
                'referred_user_id' => $user_id,
                'level'            => 1,
                'created_at'       => current_time('mysql'),
                'referrer_id'      => $affiliate->id,
            ]
        );

        // Check for level 2
        $parent_referrer = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}affilixwp_referrals WHERE referred_user_id = %d AND level = 1",
                $referrer_user_id
            )
        );

        if ($parent_referrer) {
            $wpdb->insert(
                "{$wpdb->prefix}affilixwp_referrals",
                [
                    'referrer_user_id' => $parent_referrer->referrer_user_id,
                    'referred_user_id' => $user_id,
                    'level'            => 2,
                    'created_at'       => current_time('mysql'),
                    'referrer_id'      => $parent_referrer->referrer_id,
                ]
            );
        }

        // Clean up cookie
        setcookie(self::COOKIE_NAME, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
}
