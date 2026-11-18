<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Activator {

    const DB_VERSION = '2.0.0';

    public static function activate() {
        self::upgrade_schema();
    }

    public static function maybe_upgrade() {
        if (get_option('affilixwp_db_version') !== self::DB_VERSION) {
            self::upgrade_schema();
        }
    }

    private static function upgrade_schema() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $affiliates_table  = $wpdb->prefix . 'affilixwp_affiliates';
        $referrals_table   = $wpdb->prefix . 'affilixwp_referrals';
        $commissions_table = $wpdb->prefix . 'affilixwp_commissions';

        $sql_affiliates = "CREATE TABLE $affiliates_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            referral_code VARCHAR(50) NOT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY referral_code (referral_code)
        ) $charset_collate;";

        // Keep referrer_id for compatibility with installations created by
        // earlier releases; referrer_user_id is the canonical column.
        $sql_referrals = "CREATE TABLE $referrals_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            referrer_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            referred_user_id BIGINT UNSIGNED NOT NULL,
            level TINYINT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY referrer_id (referrer_id),
            KEY referrer_user_id (referrer_user_id),
            KEY referred_user_id (referred_user_id)
        ) $charset_collate;";

        // Legacy columns remain available so existing installations can be
        // upgraded without dropping data or breaking older reporting code.
        $sql_commissions = "CREATE TABLE $commissions_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            referrer_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            source_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            referred_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            order_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            level TINYINT UNSIGNED NOT NULL,
            status ENUM('pending','approved','paid') DEFAULT 'pending',
            reference VARCHAR(100),
            paid_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY affiliate_id (affiliate_id),
            KEY referrer_user_id (referrer_user_id),
            KEY source_user_id (source_user_id),
            KEY referred_user_id (referred_user_id),
            KEY reference (reference)
        ) $charset_collate;";

        dbDelta($sql_affiliates);
        dbDelta($sql_referrals);
        dbDelta($sql_commissions);

        // Migrate existing referral rows without discarding the old column.
        $wpdb->query("UPDATE $referrals_table
            SET referrer_user_id = referrer_id
            WHERE referrer_user_id = 0 AND referrer_id > 0");

        // Older commission rows stored the affiliate's WP user ID in
        // affiliate_id and the purchase amount in amount. Preserve that data.
        $wpdb->query("UPDATE $commissions_table
            SET referrer_user_id = affiliate_id
            WHERE referrer_user_id = 0 AND affiliate_id > 0");
        $wpdb->query("UPDATE $commissions_table
            SET referred_user_id = source_user_id
            WHERE referred_user_id = 0 AND source_user_id > 0");
        $wpdb->query("UPDATE $commissions_table
            SET commission_amount = amount
            WHERE commission_amount = 0 AND amount > 0");

        update_option('affilixwp_db_version', self::DB_VERSION, false);
    }
}
