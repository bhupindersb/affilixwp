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
        </div>
        <?php
    }
}
