<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_notices', [$this, 'license_notice']);
        add_action('admin_init', [$this, 'handle_commission_test']);
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

        add_submenu_page(
            'affilixwp',
            'License',
            'License',
            'manage_options',
            'affilixwp-license',
            [$this, 'render_page']
        );
    }

    public function render_page() {

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
                    value="<?php echo esc_attr(get_option('affilixwp_license_key')); ?>"
                    class="regular-text"
                    required
                >

                <?php submit_button('Save License'); ?>
            </form>
        </div>
        <?php
    }

    public function license_notice() {

        if (get_option('affilixwp_license_status') !== 'active') {
            echo '<div class="notice notice-warning">
                <p><strong>AffilixWP:</strong> License inactive. Updates are disabled.</p>
            </div>';
        }
    }

    public function handle_commission_test() {

        if (
            !isset($_POST['test_user_id'], $_POST['test_amount']) ||
            !check_admin_referer('affilixwp_test_commission')
        ) {
            return;
        }

        require_once AFFILIXWP_PATH . 'includes/class-commission-engine.php';

        AffilixWP_Commission_Engine::record_purchase(
            (int) $_POST['test_user_id'],
            (float) $_POST['test_amount'],
            'manual_test'
        );
    }
}
