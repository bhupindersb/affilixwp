<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Affiliate_Payout_Profile {

    public static function init() {
        add_action('show_user_profile', [__CLASS__, 'fields']);
        add_action('edit_user_profile', [__CLASS__, 'fields']);
        add_action('personal_options_update', [__CLASS__, 'save']);
        add_action('edit_user_profile_update', [__CLASS__, 'save']);
    }

    public static function fields($user) {
        if (!current_user_can('manage_options')) return;
        ?>
        <h2>AffilixWP Payout Details</h2>
        <table class="form-table">
            <tr>
                <th>UPI ID</th>
                <td><input type="text" name="affx_upi" value="<?php echo esc_attr(get_user_meta($user->ID,'affx_upi',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th>Bank Account</th>
                <td><input type="text" name="affx_bank" value="<?php echo esc_attr(get_user_meta($user->ID,'affx_bank',true)); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th>IFSC</th>
                <td><input type="text" name="affx_ifsc" value="<?php echo esc_attr(get_user_meta($user->ID,'affx_ifsc',true)); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    public static function save($user_id) {
        if (!current_user_can('manage_options')) return;

        update_user_meta($user_id, 'affx_upi', sanitize_text_field($_POST['affx_upi'] ?? ''));
        update_user_meta($user_id, 'affx_bank', sanitize_text_field($_POST['affx_bank'] ?? ''));
        update_user_meta($user_id, 'affx_ifsc', sanitize_text_field($_POST['affx_ifsc'] ?? ''));
    }
}
