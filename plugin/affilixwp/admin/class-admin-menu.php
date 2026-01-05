<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Menu {

  public function __construct() {
    add_action('admin_menu', [$this, 'register_menu']);
    add_action('admin_init', [$this, 'handle_license_form']);
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
    $license_key = get_option('affilixwp_license_key');
    $license_status = get_option('affilixwp_license_status');
    ?>
    <div class="wrap">
      <h1>AffilixWP License</h1>

      <form method="post">
        <?php wp_nonce_field('affilixwp_activate_license'); ?>

        <table class="form-table">
          <tr>
            <th>License Key</th>
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

        <?php submit_button('Activate License'); ?>
      </form>

      <p>
        Status:
        <strong><?php echo esc_html($license_status); ?></strong>
      </p>
    </div>
    <?php
  }

  public function handle_license_form() {
    if (
      !isset($_POST['affilixwp_license_key']) ||
      !check_admin_referer('affilixwp_activate_license')
    ) {
      return;
    }

    $license_key = sanitize_text_field($_POST['affilixwp_license_key']);
    update_option('affilixwp_license_key', $license_key);

    $response = wp_remote_post(
      'https://www.beveez.tech/api/license/verify',
      [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode([
          'licenseKey' => $license_key,
          'domain' => home_url(),
        ]),
        'timeout' => 15,
      ]
    );

    if (!is_wp_error($response)) {
      $data = json_decode(wp_remote_retrieve_body($response), true);
      update_option(
        'affilixwp_license_status',
        !empty($data['valid']) ? 'active' : 'inactive'
      );
    }
  }
}
