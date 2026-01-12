<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_affilixwp_save_license', [$this, 'handle_license_save']);
        add_action('admin_notices', [$this, 'license_notice']);
    }

    public function register_menu() {

        add_menu_page(
            'AffilixWP',
            'AffilixWP',
            'manage_options',
            'affilixwp',
            ['AffilixWP_Admin_Dashboard', 'render'],
            'dashicons-networking'
        );

        add_submenu_page(
            'affilixwp',
            'License',
            'License',
            'manage_options',
            'affilixwp-license',
            [$this, 'license_page']
        );
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
                    placeholder="AFFILIXWP-XXXX-XXXX-XXXX"
                    required
                >

                <?php submit_button('Save License'); ?>
            </form>
        </div>
        <?php
    }

    public function handle_license_save() {

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if (
            !isset($_POST['affilixwp_license_nonce']) ||
            !wp_verify_nonce($_POST['affilixwp_license_nonce'], 'affilixwp_save_license')
        ) {
            wp_die('Security check failed');
        }

        $license = sanitize_text_field($_POST['license_key']);

        update_option('affilixwp_license_key', $license);
        delete_transient('affilixwp_license_check');

        AffilixWP_License_Validator::validate(true);

        wp_safe_redirect(
            add_query_arg(
                ['license_saved' => '1'],
                admin_url('admin.php?page=affilixwp-license')
            )
        );
        exit;
    }

    public function license_notice() {

        if (get_option('affilixwp_license_status') === 'active') {
            return;
        }

        echo '<div class="notice notice-warning">
            <p><strong>AffilixWP:</strong> License inactive. Updates & commissions are disabled.</p>
        </div>';
    }
}
