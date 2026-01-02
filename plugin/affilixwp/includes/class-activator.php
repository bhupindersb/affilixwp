<?php
class AffilixWP_Activator {

    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $referrals_table = $wpdb->prefix . 'affilixwp_referrals';
        $commissions_table = $wpdb->prefix . 'affilixwp_commissions';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("
            CREATE TABLE $referrals_table (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                referrer_user_id BIGINT UNSIGNED NOT NULL,
                referred_user_id BIGINT UNSIGNED NOT NULL,
                level TINYINT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY referrer_user_id (referrer_user_id),
                KEY referred_user_id (referred_user_id)
            ) $charset_collate;
        ");

        dbDelta("
            CREATE TABLE $commissions_table (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                referrer_user_id BIGINT UNSIGNED NOT NULL,
                referred_user_id BIGINT UNSIGNED NOT NULL,
                order_id VARCHAR(100),
                level TINYINT NOT NULL,
                commission_amount DECIMAL(10,2),
                order_amount DECIMAL(10,2),
                status VARCHAR(20) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY referrer_user_id (referrer_user_id),
                KEY status (status),
                KEY created_at (created_at)
            ) $charset_collate;
        ");
    }
}
