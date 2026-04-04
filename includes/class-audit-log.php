<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Audit_Log {

    public static function log($action, $message) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'affilixwp_audit_logs',
            [
                'action' => $action,
                'message' => $message,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ]
        );
    }
}
