<?php
global $wpdb;

$user_id = get_current_user_id();
$table = $wpdb->prefix . 'affilixwp_commissions';

// Earnings
$total = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(commission_amount) FROM $table WHERE referrer_user_id = %d",
        $user_id
    )
);

$pending = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(commission_amount) FROM $table 
         WHERE referrer_user_id = %d AND status = 'pending'",
        $user_id
    )
);

$paid = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(commission_amount) FROM $table 
         WHERE referrer_user_id = %d AND status = 'paid'",
        $user_id
    )
);

// Commissions
$commissions = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table 
         WHERE referrer_user_id = %d 
         ORDER BY created_at DESC 
         LIMIT 50",
        $user_id
    )
);

// Referral link
$referral_link = add_query_arg(
    ['ref' => $user_id],
    home_url('/')
);
?>

<style>
.affx-dashboard { max-width:1100px; margin:40px auto; }
.affx-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; }
.affx-card { background:#fff; border:1px solid #ddd; padding:20px; border-radius:8px; }
.affx-card h2 { margin:0; font-size:24px; }
.affx-muted { color:#666; font-size:13px; }
.affx-link { background:#f9fafb; padding:10px; border:1px dashed #ccc; word-break:break-all; }
.affx-table { width:100%; border-collapse:collapse; margin-top:20px; }
.affx-table th, .affx-table td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
.affx-status { padding:4px 10px; border-radius:999px; font-size:12px; text-transform:capitalize; }
.affx-status.pending { background:#FEF3C7; color:#92400E; }
.affx-status.approved { background:#DCFCE7; color:#166534; }
.affx-status.paid { background:#DBEAFE; color:#1E40AF; }
</style>

<div class="affx-dashboard">
    <h1>Affiliate Dashboard</h1>

    <!-- SUMMARY -->
    <div class="affx-grid">
        <div class="affx-card">
            <h2>₹<?php echo number_format($total, 2); ?></h2>
            <div class="affx-muted">Total Earnings</div>
        </div>

        <div class="affx-card">
            <h2>₹<?php echo number_format($pending, 2); ?></h2>
            <div class="affx-muted">Pending</div>
        </div>

        <div class="affx-card">
            <h2>₹<?php echo number_format($paid, 2); ?></h2>
            <div class="affx-muted">Paid</div>
        </div>
    </div>

    <!-- REFERRAL LINK -->
    <h2 style="margin-top:30px;">Your Referral Link</h2>
    <div class="affx-link"><?php echo esc_url($referral_link); ?></div>

    <!-- COMMISSIONS -->
    <h2 style="margin-top:30px;">Commission History</h2>

    <table class="affx-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Order Amount</th>
                <th>Commission</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($commissions): foreach ($commissions as $row): ?>
            <tr>
                <td><?php echo esc_html(date('Y-m-d', strtotime($row->created_at))); ?></td>
                <td>₹<?php echo number_format($row->order_amount, 2); ?></td>
                <td>₹<?php echo number_format($row->commission_amount, 2); ?></td>
                <td>
                    <span class="affx-status <?php echo esc_attr($row->status); ?>">
                        <?php echo esc_html($row->status); ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="4">No commissions yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
