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

        // ===== SUMMARY TOTALS =====
        $totals = $wpdb->get_row("
            SELECT
                SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) AS paid
            FROM $table
        ");

        $pending  = (float) ($totals->pending ?? 0);
        $approved = (float) ($totals->approved ?? 0);
        $paid     = (float) ($totals->paid ?? 0);
        $balance  = $pending + $approved;

        // ===== COMMISSIONS LIST =====
        $rows = $wpdb->get_results("
            SELECT * FROM $table
            WHERE status IN ('pending', 'approved')
            ORDER BY created_at ASC
        ");

        $min_payout = (float) get_option('affilixwp_min_payout', 500);

        ?>

        <div class="wrap">
            <h1>Affiliate Payouts</h1>

            <p style="margin:15px 0;">
                <a href="<?php echo esc_url(
                    wp_nonce_url(
                        admin_url('admin-post.php?action=affilixwp_export_payouts'),
                        'affilixwp_export_payouts'
                    )
                ); ?>" class="button button-primary">
                    Export Approved Payouts (CSV)
                </a>
            </p>

            <!-- SUMMARY CARDS -->
            <style>
                .affx-summary {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 16px;
                    margin: 20px 0;
                }
                .affx-box {
                    background: #fff;
                    border: 1px solid #ddd;
                    padding: 18px;
                    border-radius: 8px;
                }
                .affx-box h2 {
                    margin: 0;
                    font-size: 22px;
                }
                .affx-muted {
                    color: #666;
                    font-size: 13px;
                    margin-top: 4px;
                }

                .widefat td input {
                    margin-left: 8px;
                }
            </style>

            <div class="affx-summary">
                <div class="affx-box">
                    <h2>₹<?php echo number_format($pending, 2); ?></h2>
                    <div class="affx-muted">Pending</div>
                </div>

                <div class="affx-box">
                    <h2>₹<?php echo number_format($approved, 2); ?></h2>
                    <div class="affx-muted">Approved</div>
                </div>

                <div class="affx-box">
                    <h2>₹<?php echo number_format($paid, 2); ?></h2>
                    <div class="affx-muted">Paid</div>
                </div>

                <div class="affx-box">
                    <h2><strong>₹<?php echo number_format($balance, 2); ?></strong></h2>
                    <div class="affx-muted">Outstanding Balance</div>
                </div>
            </div>

            <?php

                // Pre-calc balances per affiliate
                $balances = [];
                foreach ($rows as $r) {
                    if (!isset($balances[$r->referrer_user_id])) {
                        $balances[$r->referrer_user_id] = 0;
                    }
                    if ($r->status !== 'paid') {
                        $balances[$r->referrer_user_id] += (float) $r->commission_amount;
                    }
                }
            ?>

            <!-- PAYOUT TABLE -->
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('affilixwp_bulk_paid'); ?>
                <input type="hidden" name="action" value="affilixwp_bulk_mark_paid">

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="affx-select-all"></th>
                            <th>Date</th>
                            <th>Affiliate</th>
                            <th>Order Amount</th>
                            <th>Commission</th>
                            <th>Status</th>
                            <th>Eligible?</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if ($rows): foreach ($rows as $row):

                        $user = get_user_by('id', $row->referrer_user_id);
                        $name = $user ? $user->display_name : 'User #' . $row->referrer_user_id;

                        $balance = $balances[$row->referrer_user_id] ?? 0;
                        $eligible = $balance >= $min_payout;
                    ?>
                        <tr>
                            <td>
                                <?php if ($row->status === 'approved'): ?>
                                    <input type="checkbox" name="commission_ids[]" value="<?php echo (int)$row->id; ?>">
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                            <td><?php echo esc_html($name); ?></td>
                            <td>₹<?php echo number_format($row->order_amount, 2); ?></td>
                            <td><strong>₹<?php echo number_format($row->commission_amount, 2); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($row->status)); ?></td>
                            <td><?php $balance = $balances[$row->referrer_user_id] ?? 0;
                                $eligible = ($balance >= $min_payout) ? 'Yes' : 'No';   echo esc_html($eligible); ?>
                            </td>
                            <td>
                                <?php if ($row->status === 'pending' && $balance >= $min_payout): ?>
                                    <?php $this->action_button($row->id, 'approve', 'Approve'); ?>
                                <?php endif; ?>
                                <?php if ($row->status === 'approved'): ?>
                                    <?php $this->action_button($row->id, 'pay', 'Mark as Paid'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7">No pending payouts.</td></tr>
                    <?php endif; ?>

                    </tbody>
                </table>
                <p style="margin-top:15px;">
                    <button class="button button-primary">Mark Selected as Paid</button>
                </p>
            </form>
        </div>
        <script>
            document.getElementById('affx-select-all')?.addEventListener('change', function(e){
                document.querySelectorAll('input[name="commission_ids[]"]').forEach(cb => {
                    cb.checked = e.target.checked;
                });
            });
        </script>
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

        $action = sanitize_text_field($_POST['payout_action'] ?? '');

        /* ===============================
        * BULK MARK AS PAID
        * =============================== */
        if ($action === 'bulk_pay') {

            if (empty($_POST['commission_ids']) || !is_array($_POST['commission_ids'])) {
                wp_safe_redirect(admin_url('admin.php?page=affilixwp-payouts'));
                exit;
            }

            foreach ($_POST['commission_ids'] as $id) {
                $id = (int) $id;

                // Only pay APPROVED commissions
                $wpdb->update(
                    $table,
                    [
                        'status'  => 'paid',
                        'paid_at' => current_time('mysql'),
                    ],
                    [
                        'id'     => $id,
                        'status' => 'approved',
                    ]
                );
            }

            wp_safe_redirect(admin_url('admin.php?page=affilixwp-payouts'));
            exit;
        }

        /* ===============================
        * SINGLE COMMISSION ACTIONS
        * =============================== */

        $id = (int) ($_POST['commission_id'] ?? 0);

        if (!$id) {
            wp_die('Invalid commission');
        }

        $commission = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id)
        );

        if (!$commission) {
            wp_die('Commission not found');
        }

        $min_payout = (float) get_option('affilixwp_min_payout', 500);

        // Calculate affiliate balance (excluding paid)
        $balance = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(commission_amount)
                FROM $table
                WHERE referrer_user_id = %d
                AND status != 'paid'",
                $commission->referrer_user_id
            )
        );

        /* ===============================
        * APPROVE
        * =============================== */
        if ($action === 'approve') {

            if ($commission->status !== 'pending') {
                wp_die('Invalid approval request');
            }

            if ($balance < $min_payout) {
                wp_die('Affiliate has not reached minimum payout');
            }

            $wpdb->update(
                $table,
                ['status' => 'approved'],
                ['id' => $id]
            );
        }

        /* ===============================
        * PAY (SINGLE)
        * =============================== */
        if ($action === 'pay') {

            if ($commission->status !== 'approved') {
                wp_die('Only approved commissions can be paid');
            }

            if ($balance < $min_payout) {
                wp_die('Minimum payout not reached');
            }

            $wpdb->update(
                $table,
                [
                    'status'  => 'paid',
                    'paid_at' => current_time('mysql'),
                ],
                ['id' => $id]
            );
        }

        wp_safe_redirect(admin_url('admin.php?page=affilixwp-payouts'));
        exit;
    }

}
