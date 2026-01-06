<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_License_Validator {

    public static function validate() {
        $license_key = get_option('affilixwp_license_key');
        if (!$license_key) return false;

        $cached = get_transient('affilixwp_license_check');
        if ($cached !== false) {
            return $cached;
        }

        $response = wp_remote_post(
            'https://www.beveez.tech/api/license/validate',
            [
                'timeout' => 15,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode([
                    'license_key' => $license_key,
                    'domain' => home_url(),
                ]),
            ]
        );

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['valid'])) {
            set_transient('affilixwp_license_check', true, 12 * HOUR_IN_SECONDS);
            update_option('affilixwp_license_status', 'active');
            return true;
        }

        update_option('affilixwp_license_status', 'inactive');
        set_transient('affilixwp_license_check', false, 12 * HOUR_IN_SECONDS);
        return false;
    }
}
