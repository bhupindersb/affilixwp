<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_License_Page {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_affilixwp_save_license', [$this, 'save_license']);
    }

    public function register_menu() {
        add_submenu_page(
            'affilixwp', // parent slug (your main menu)
            'AffilixWP License',
            'License',
            'manage_options',
            'affilixwp-license',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        if (!current_user_can('manage_options')) return;

        $license_key = get_option('affilixwp_license_key', '');
        $license_status = get_option('affilixwp_license_status', 'inactive');
        ?>
        <div class="wrap">
            <h1>AffilixWP License</h1>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('affilixwp_save_license'); ?>
                <input type="hidden" name="action" value="affilixwp_save_license">

                <table class="form-table">
                    <tr>
                        <th scope="row">License Key</th>
                        <td>
                            <input type="text"
                                   name="license_key"
                                   value="<?php echo esc_attr($license_key); ?>"
                                   class="regular-text"
                                   placeholder="AFFILIXWP-XXXX-XXXX-XXXX" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <?php echo esc_html(ucfirst($license_status)); ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save License'); ?>
            </form>
        </div>
        <?php
    }

    public function save_license() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('affilixwp_save_license');

        $license_key = isset($_POST['license_key'])
            ? sanitize_text_field($_POST['license_key'])
            : '';

        update_option('affilixwp_license_key', $license_key);
        update_option('affilixwp_license_status', $license_key ? 'inactive' : 'inactive');

        wp_redirect(admin_url('admin.php?page=affilixwp-license&saved=1'));
        exit;
    }
}
