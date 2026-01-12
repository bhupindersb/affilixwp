<?php
if (!defined('ABSPATH')) {
    exit;
}

class AffilixWP_License_Validator {

    /**
     * Validate license with remote API
     *
     * @return bool
     */
    public static function validate() {

        $license_key = get_option('affilixwp_license_key');

        error_log('[AffilixWP] License validation started');

        if (empty($license_key)) {
            error_log('[AffilixWP] No license key found');
            update_option('affilixwp_license_status', 'inactive');
            return false;
        }

        /**
         * Use cached result if available
         */
        $cached = get_transient('affilixwp_license_check');
        if ($cached !== false) {
            error_log('[AffilixWP] Using cached license result: ' . ($cached ? 'VALID' : 'INVALID'));
            return (bool) $cached;
        }

        /**
         * Call license API
         */
        $response = wp_remote_post(
            'https://www.beveez.tech/api/license/validate',
            [
                'timeout' => 15,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode([
                    'license_key' => sanitize_text_field($license_key),
                    'domain'      => home_url(),
                ]),
            ]
        );

        if (is_wp_error($response)) {
            error_log('[AffilixWP] License API error: ' . $response->get_error_message());

            // Cache failure briefly to avoid hammering API
            set_transient('affilixwp_license_check', false, HOUR_IN_SECONDS);
            update_option('affilixwp_license_status', 'inactive');

            return false;
        }

        $body = wp_remote_retrieve_body($response);
        error_log('[AffilixWP] License API response: ' . $body);

        $data = json_decode($body, true);

        if (is_array($data) && !empty($data['valid'])) {
            error_log('[AffilixWP] License VALID');

            update_option('affilixwp_license_status', 'active');
            set_transient('affilixwp_license_check', true, 12 * HOUR_IN_SECONDS);

            return true;
        }

        error_log('[AffilixWP] License INVALID');

        update_option('affilixwp_license_status', 'inactive');
        set_transient('affilixwp_license_check', false, 12 * HOUR_IN_SECONDS);

        return false;
    }
}
