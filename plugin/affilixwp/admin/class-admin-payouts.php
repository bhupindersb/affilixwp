<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Payouts {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_affilixwp_update_commission', [$this, 'handle_action']);
    }

    public function register_menu() {
        add_submenu_page(
            'affilixwp',
            'Payouts',
            'Payouts',
            'manage_options',
            'affilixwp-payouts',
            [$this, 'render']
        );
    }

    public function render() {
        global $wpdb;

        $table = $wpdb->prefix . 'affilixwp_commissions';

        $rows = $wpdb->get_results("
            SELECT * FROM $table
            WHERE status IN ('pending','approved')
            ORDER BY created_at ASC
        ");
        ?>
        <div class="wrap">
            <h1>Affiliate Payouts</h1>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Affiliate</th>
                        <th>Order Amount</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows): foreach ($rows as $row):

                    $user = get_user_by('id', $row->referrer_user_id);
                    $name = $user ? $user->display_name : 'User #' . $row->referrer_user_id;
                ?>
                    <tr>
                        <td><?php echo esc_html($row->created_at); ?></td>
                        <td><?php echo esc_html($name); ?></td>
                        <td>₹<?php echo number_format($row->order_amount, 2); ?></td>
                        <td><strong>₹<?php echo number_format($row->commission_amount, 2); ?></strong></td>
                        <td><?php echo esc_html(ucfirst($row->status)); ?></td>
                        <td>
                            <?php if ($row->status === 'pending'): ?>
                                <?php $this->action_button($row->id, 'approve', 'Approve'); ?>
                            <?php endif; ?>

                            <?php if ($row->status === 'approved'): ?>
                                <?php $this->action_button($row->id, 'pay', 'Mark Paid'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6">No pending payouts.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function action_button($id, $action, $label) {
        ?>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
            <?php wp_nonce_field('affilixwp_payout_action'); ?>
            <input type="hidden" name="action" value="affilixwp_update_commission">
            <input type="hidden" name="commission_id" value="<?php echo (int)$id; ?>">
            <input type="hidden" name="payout_action" value="<?php echo esc_attr($action); ?>">
            <button class="button button-primary"><?php echo esc_html($label); ?></button>
        </form>
        <?php
    }

    public function handle_action() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('affilixwp_payout_action');

        global $wpdb;
        $table = $wpdb->prefix . 'affilixwp_commissions';

        $id = (int) $_POST['commission_id'];
        $action = sanitize_text_field($_POST['payout_action']);

        if ($action === 'approve') {
            $wpdb->update($table, ['status' => 'approved'], ['id' => $id]);
        }

        if ($action === 'pay') {
            $wpdb->update(
                $table,
                [
                    'status'  => 'paid',
                    'paid_at' => current_time('mysql'),
                    'paid_by' => get_current_user_id()
                ],
                ['id' => $id]
            );
        }

        wp_safe_redirect(admin_url('admin.php?page=affilixwp-payouts'));
        exit;
    }
}
