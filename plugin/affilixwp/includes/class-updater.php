<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Updater {

    private $plugin_file;
    private $plugin_slug;
    private $version;
    private $license_key;
    private $api_url;

    public function __construct($plugin_file) {

        // Absolute plugin file
        $this->plugin_file = $plugin_file;

        // 🚨 MUST be exact: folder/plugin-file.php
        $this->plugin_slug = 'affilixwp/affilixwp.php';

        $this->version = AFFILIXWP_VERSION;
        $this->license_key = get_option('affilixwp_license_key');
        $this->api_url = 'https://www.beveez.tech/api/update/check';

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
    }

    public function check_for_update($transient) {

        if (empty($transient->checked)) {
            return $transient;
        }

        $response = wp_remote_post($this->api_url, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'slug'       => 'affilixwp',
                'version'    => $this->version,
                'licenseKey' => $this->license_key,
                'domain'     => home_url(),
            ]),
        ]);

        if (is_wp_error($response)) {
            error_log('AffilixWP update error: ' . $response->get_error_message());
            return $transient;
        }

        $data = json_decode(wp_remote_retrieve_body($response));

        // 🔍 Debug (temporary)
        error_log('AffilixWP update API response: ' . wp_remote_retrieve_body($response));

        if (
            !empty($data->new_version) &&
            version_compare($this->version, $data->new_version, '<')
        ) {
            $transient->response[$this->plugin_slug] = (object) [
                'slug'        => 'affilixwp',
                'plugin'      => $this->plugin_slug,
                'new_version' => $data->new_version,
                'url'         => $data->homepage,
                'package'     => $data->download_url,
            ];
        }

        return $transient;
    }

    public function plugin_info($false, $action, $args) {

        if ($action !== 'plugin_information' || $args->slug !== 'affilixwp') {
            return false;
        }

        return (object) [
            'name'        => 'AffilixWP',
            'slug'        => 'affilixwp',
            'version'     => $this->version,
            'author'      => 'AffilixWP',
            'homepage'    => 'https://www.beveez.tech/affilixwp',
            'sections'    => [
                'description' => 'Affiliate & multi-level commission tracking for WordPress.',
            ],
        ];
    }
}
