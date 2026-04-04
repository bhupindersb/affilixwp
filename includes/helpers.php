<?php
function affilixwp_log_event($action, $context = '') {
    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'affilixwp_audit_logs',
        [
            'user_id'    => get_current_user_id(),
            'action'     => sanitize_text_field($action),
            'context'    => maybe_serialize($context),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ],
        ['%d', '%s', '%s', '%s']
    );
}
