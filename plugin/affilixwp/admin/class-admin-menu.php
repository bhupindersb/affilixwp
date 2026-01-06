<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'handle_license_save']);
        add_action('admin_notices', [$this, 'license_notice']);
    }

    public function register_menu() {
        add_menu_page(
            'AffilixWP',
            'AffilixWP',
            'manage_options',
            'affilixwp',
            [$this, 'render_page'],
            'dashicons-admin-network'
        );
    }

    public function render_page() {
        $license_key   = get_option('affilixwp_license_key', '');
        $status        = get_option('affilixwp_license_status', 'inactive');
        $plan          = get_option('affilixwp_license_plan', '—');
        $sites_used    = get_option('affilixwp_license_sites', 0);

        ?>
        <div class="wrap">
            <h1>AffilixWP License</h1>

            <p>
                <strong>Status:</strong>
                <span style="color:<?php echo $status === 'active' ? 'green' : 'red'; ?>">
                    <?php echo ucfirst($status); ?>
                </span>
            </p>

            <p><strong>Plan:</strong> <?php echo esc_html($plan); ?></p>
            <p><strong>Sites Used:</strong> <?php echo esc_html($sites_used); ?></p>

            <form method="post">
                <?php wp_nonce_field('affilixwp_save_license'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">License Key</th>
                        <td>
                            <input
                                type="text"
                                name="affilixwp_license_key"
                                value="<?php echo esc_attr($license_key); ?>"
                                class="regular-text"
                            />
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save License'); ?>
            </form>
        </div>
        <?php
    }


    public function handle_license_save() {
        if (
            !isset($_POST['affilixwp_license_key']) ||
            !check_admin_referer('affilixwp_license_save')
        ) {
            return;
        }

        $license_key = sanitize_text_field($_POST['affilixwp_license_key']);
        update_option('affilixwp_license_key', $license_key);

        $response = wp_remote_post(
            'https://www.beveez.tech/api/license/validate',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => wp_json_encode([
                    'licenseKey' => $license_key,
                    'domain'     => home_url(),
                ]),
                'timeout' => 15,
            ]
        );

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['valid'])) {
            update_option('affilixwp_license_status', 'active');
            update_option('affilixwp_last_valid', time());
        } else {
            update_option('affilixwp_license_status', 'inactive');
        }
    }

    public function license_notice() {
        $status     = get_option('affilixwp_license_status', 'inactive');
        $last_valid = (int) get_option('affilixwp_last_valid', 0);

        // ⏳ Grace period: 7 days
        if (
            $status !== 'active' &&
            (time() - $last_valid) < 7 * DAY_IN_SECONDS
        ) {
            return;
        }

        if ($status !== 'active') {
            echo '<div class="notice notice-warning">
                <p><strong>AffilixWP:</strong> License inactive. Updates are disabled.</p>
            </div>';
        }
    }
}
