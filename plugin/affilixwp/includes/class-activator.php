<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Activator {

    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $affiliates_table  = $wpdb->prefix . 'affilixwp_affiliates';
        $referrals_table   = $wpdb->prefix . 'affilixwp_referrals';
        $commissions_table = $wpdb->prefix . 'affilixwp_commissions';

        /**
         * Affiliates table
         */
        $sql_affiliates = "
        CREATE TABLE $affiliates_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            referral_code VARCHAR(50) NOT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY referral_code (referral_code)
        ) $charset_collate;
        ";

        /**
         * Referrals table
         */
        $sql_referrals = "
        CREATE TABLE $referrals_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_id BIGINT UNSIGNED NOT NULL,
            referred_user_id BIGINT UNSIGNED NOT NULL,
            level TINYINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY referrer_id (referrer_id),
            KEY referred_user_id (referred_user_id)
        ) $charset_collate;
        ";

        /**
         * Commissions table
         */
        $sql_commissions = "
        CREATE TABLE $commissions_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT UNSIGNED NOT NULL,
            source_user_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            level TINYINT UNSIGNED NOT NULL,
            status ENUM('pending','approved','paid') DEFAULT 'pending',
            reference VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY source_user_id (source_user_id)
        ) $charset_collate;
        ";

        dbDelta($sql_affiliates);
        dbDelta($sql_referrals);
        dbDelta($sql_commissions);
    }
}
