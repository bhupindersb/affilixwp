<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Dashboard {

    public static function render() {
        global $wpdb;

        $commissions = $wpdb->prefix . 'affilixwp_commissions';
        $referrals   = $wpdb->prefix . 'affilixwp_referrals';

        // KPIs
        $total_revenue = (float) $wpdb->get_var("
            SELECT SUM(order_amount) FROM $commissions
        ");

        $total_commissions = (float) $wpdb->get_var("
            SELECT SUM(commission_amount) FROM $commissions
        ");

        $active_affiliates = (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT referrer_user_id) FROM $referrals
        ");

        $total_conversions = (int) $wpdb->get_var("
            SELECT COUNT(*) FROM $commissions
        ");

        // Recent commissions
        $recent = $wpdb->get_results("
            SELECT * FROM $commissions
            ORDER BY created_at DESC
            LIMIT 10
        ");
        ?>
        <div class="wrap">
            <h1>AffilixWP Dashboard</h1>
            <p class="description">Affiliate & Multi-Level Commission Overview</p>

            <style>
                .affx-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin:20px 0; }
                .affx-card { background:#fff; border:1px solid #ddd; padding:20px; border-radius:8px; }
                .affx-card h2 { margin:0 0 6px; font-size:24px; }
                .affx-muted { color:#666; font-size:13px; }
                table.affx-table { width:100%; border-collapse:collapse; margin-top:20px; }
                table.affx-table th, table.affx-table td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
                table.affx-table th { background:#fafafa; }
            </style>

            <!-- KPI CARDS -->
            <div class="affx-grid">
                <div class="affx-card">
                    <h2>₹ <?php echo number_format($total_revenue, 2); ?></h2>
                    <div class="affx-muted">Total Revenue</div>
                </div>

                <div class="affx-card">
                    <h2>₹ <?php echo number_format($total_commissions, 2); ?></h2>
                    <div class="affx-muted">Total Commissions</div>
                </div>

                <div class="affx-card">
                    <h2><?php echo $active_affiliates; ?></h2>
                    <div class="affx-muted">Active Affiliates</div>
                </div>

                <div class="affx-card">
                    <h2><?php echo $total_conversions; ?></h2>
                    <div class="affx-muted">Conversions</div>
                </div>
            </div>

            <!-- RECENT COMMISSIONS -->
            <h2>Recent Commissions</h2>

            <table class="affx-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Buyer</th>
                        <th>Affiliate</th>
                        <th>Level</th>
                        <th>Order</th>
                        <th>Commission</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent): foreach ($recent as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->created_at); ?></td>
                            <td>#<?php echo esc_html($row->source_user_id); ?></td>
                            <td>#<?php echo esc_html($row->referrer_user_id); ?></td>
                            <td>L<?php echo esc_html($row->level); ?></td>
                            <td>₹ <?php echo number_format($row->order_amount, 2); ?></td>
                            <td>₹ <?php echo number_format($row->commission_amount, 2); ?></td>
                            <td><?php echo esc_html($row->reference); ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7">No commissions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
        <?php
    }
}
