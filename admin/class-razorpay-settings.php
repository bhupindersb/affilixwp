<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Razorpay_Settings {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function menu() {
        add_submenu_page(
            'affilixwp',
            'Razorpay Settings',
            'Razorpay',
            'manage_options',
            'affilixwp-razorpay',
            [__CLASS__, 'page']
        );
    }

    public static function register_settings() {

        register_setting('affilixwp_razorpay', 'affilixwp_razorpay_key');
        register_setting('affilixwp_razorpay', 'affilixwp_razorpay_secret');
        register_setting('affilixwp_razorpay', 'affilixwp_razorpay_webhook_secret');
        register_setting('affilixwp_razorpay', 'affilixwp_razorpay_plan_id');
    }

    public static function page() {
        ?>
        <div class="wrap">
            <h1>Razorpay Settings</h1>

            <form method="post" action="options.php">
                <?php settings_fields('affilixwp_razorpay'); ?>

                <table class="form-table">

                    <tr>
                        <th>Razorpay Key ID</th>
                        <td>
                            <input type="text" class="regular-text"
                                name="affilixwp_razorpay_key"
                                value="<?php echo esc_attr(get_option('affilixwp_razorpay_key')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th>Razorpay Key Secret</th>
                        <td>
                            <input type="password" class="regular-text"
                                name="affilixwp_razorpay_secret"
                                value="<?php echo esc_attr(get_option('affilixwp_razorpay_secret')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th>Webhook Secret</th>
                        <td>
                            <input type="password" class="regular-text"
                                name="affilixwp_razorpay_webhook_secret"
                                value="<?php echo esc_attr(get_option('affilixwp_razorpay_webhook_secret')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th>Subscription Plan ID</th>
                        <td>
                            <input type="text" class="regular-text"
                                name="affilixwp_razorpay_plan_id"
                                value="<?php echo esc_attr(get_option('affilixwp_razorpay_plan_id')); ?>">
                        </td>
                    </tr>

                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
