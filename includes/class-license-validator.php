<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_License_Validator {

    /**
     * Validate license (cached + remote)
     */
    public static function validate($force = false) {

        $license_key = trim(get_option('affilixwp_license_key', ''));

        if (empty($license_key)) {
            update_option('affilixwp_license_status', 'inactive');
            return false;
        }

        if (!$force) {
            $cached = get_transient('affilixwp_license_check');
            if ($cached !== false) {
                return (bool) $cached;
            }
        }

        $response = wp_remote_post(
            'https://www.beveez.tech/api/license/validate',
            [
                'timeout' => 15,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode([
                    'license_key' => $license_key,
                    'domain'      => home_url(),
                ]),
            ]
        );

        if (is_wp_error($response)) {
            set_transient('affilixwp_license_check', false, HOUR_IN_SECONDS);
            update_option('affilixwp_license_status', 'inactive');
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['valid'])) {

            update_option('affilixwp_license_status', 'active');
            update_option('affilixwp_license_plan', sanitize_text_field($data['plan'] ?? 'pro'));
            update_option('affilixwp_last_valid', time());

            set_transient('affilixwp_license_check', true, 12 * HOUR_IN_SECONDS);
            return true;
        }

        update_option('affilixwp_license_status', 'inactive');
        set_transient('affilixwp_license_check', false, 6 * HOUR_IN_SECONDS);
        return false;
    }
}
