<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_License_Validator {

    public static function validate() {
        $license_key = get_option('affilixwp_license_key');

        error_log('AffilixWP: Starting license validation');
        error_log('License key: ' . $license_key);

        if (!$license_key) {
            error_log('No license key found');
            return false;
        }

        delete_transient('affilixwp_license_check');

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
            error_log('License API error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        error_log('License API raw response: ' . $body);

        $data = json_decode($body, true);

        if (!empty($data['valid'])) {
            error_log('License VALID');
            set_transient('affilixwp_license_check', true, 12 * HOUR_IN_SECONDS);
            update_option('affilixwp_license_status', 'active');
            return true;
        }

        error_log('License INVALID');
        update_option('affilixwp_license_status', 'inactive');
        set_transient('affilixwp_license_check', false, 12 * HOUR_IN_SECONDS);
        return false;
    }

}
