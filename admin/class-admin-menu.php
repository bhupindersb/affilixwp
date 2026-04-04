<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu() {

        // MAIN MENU
        add_menu_page(
            'AffilixWP',
            'AffilixWP',
            'manage_options',
            'affilixwp',
            ['AffilixWP_Admin_Dashboard', 'render'],
            'dashicons-networking'
        );

        // LICENSE PAGE
        add_submenu_page(
            'affilixwp',
            'License',
            'License',
            'manage_options',
            'affilixwp-license',
            [$this, 'license_page']
        );

        // 🚫 DO NOT REGISTER PAYOUTS HERE
        // Payouts are registered ONLY in AffilixWP_Admin_Payouts
    }

    public function license_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $status = get_option('affilixwp_license_status', 'inactive');
        ?>
        <div class="wrap">
            <h1>AffilixWP License</h1>

            <p>
                <strong>Status:</strong>
                <span style="color:<?php echo $status === 'active' ? 'green' : 'red'; ?>">
                    <?php echo esc_html(ucfirst($status)); ?>
                </span>
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('affilixwp_save_license', 'affilixwp_license_nonce'); ?>
                <input type="hidden" name="action" value="affilixwp_save_license">

                <input
                    type="text"
                    name="license_key"
                    value="<?php echo esc_attr(get_option('affilixwp_license_key', '')); ?>"
                    class="regular-text"
                    required
                >

                <?php submit_button('Save License'); ?>
            </form>
            <hr>
            <h2>Payout Settings</h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('affilixwp_settings');
                do_settings_sections('affilixwp_settings');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Minimum Payout Amount</th>
                        <td>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="affilixwp_min_payout"
                                value="<?php echo esc_attr(get_option('affilixwp_min_payout', 500)); ?>"
                            />
                            <p class="description">
                                Affiliates must reach this amount before payouts are processed.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Payout Settings'); ?>
            </form>

        </div>
        <?php
    }
}
