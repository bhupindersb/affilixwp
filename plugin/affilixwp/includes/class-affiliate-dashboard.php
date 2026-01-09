<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Affiliate_Dashboard {

    public function __construct() {
        add_shortcode('affilixwp_dashboard', [$this, 'render_dashboard']);
    }

    public function render_dashboard() {

        if (!is_user_logged_in()) {
            return '<p>You must be logged in to view your affiliate dashboard.</p>';
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // Check if user is an affiliate
        $affiliate = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}affilixwp_affiliates WHERE user_id = %d AND status = 'active'",
                $user_id
            )
        );

        if (!$affiliate) {
            return '<p>You are not registered as an affiliate.</p>';
        }

        // Referral link
        $referral_link = home_url('/?ref=' . $affiliate->referral_code);

        // Earnings
        $totals = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                    SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END) AS approved,
                    SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) AS pending
                FROM {$wpdb->prefix}affilixwp_commissions
                WHERE referrer_user_id = %d
                ",
                $user_id
            )
        );

        // Commission history
        $commissions = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$wpdb->prefix}affilixwp_commissions
                WHERE referrer_user_id = %d
                ORDER BY created_at DESC
                LIMIT 50
                ",
                $user_id
            )
        );

        ob_start();
        ?>

        <div class="affilixwp-dashboard">

            <h2>Affiliate Dashboard</h2>

            <div class="affilixwp-box">
                <strong>Your Referral Link:</strong><br>
                <input type="text" readonly value="<?php echo esc_url($referral_link); ?>" style="width:100%;">
            </div>

            <div class="affilixwp-stats">
                <p><strong>Approved Earnings:</strong> <?php echo number_format((float)$totals->approved, 2); ?></p>
                <p><strong>Pending Earnings:</strong> <?php echo number_format((float)$totals->pending, 2); ?></p>
            </div>

            <h3>Commission History</h3>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($commissions): ?>
                    <?php foreach ($commissions as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->created_at); ?></td>
                            <td><?php echo number_format($row->commission_amount, 2); ?></td>
                            <td><?php echo esc_html($row->level); ?></td>
                            <td><?php echo esc_html(ucfirst($row->status)); ?></td>
                            <td><?php echo esc_html($row->reference); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No commissions yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

        </div>

        <?php
        return ob_get_clean();
    }
}
