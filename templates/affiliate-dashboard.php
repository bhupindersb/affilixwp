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
$affiliate = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT referral_code FROM {$wpdb->prefix}affilixwp_affiliates WHERE user_id = %d",
        $user_id
    )
);

$ref_url = home_url('/?ref=' . $affiliate->referral_code);
?>

<style>
.affx-dashboard {
    max-width: 1200px;
    margin: 40px auto;
    font-family: 'Inter', sans-serif;
}

/* HEADER */
.affx-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.affx-title {
    font-size:28px;
    font-weight:600;
}

/* CARDS */
.affx-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
}

.affx-card {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color:#fff;
    padding:20px;
    border-radius:16px;
    box-shadow:0 10px 25px rgba(0,0,0,0.1);
    position:relative;
    overflow:hidden;
}

.affx-card small {
    opacity:0.8;
}

.affx-card h2 {
    margin:10px 0 0;
    font-size:26px;
}

/* REF LINK */
.affx-ref-box {
    display:flex;
    margin-top:30px;
    border:1px solid #eee;
    border-radius:12px;
    overflow:hidden;
}

.affx-ref-box input {
    flex:1;
    padding:12px;
    border:none;
    background:#f9fafb;
}

.affx-copy-btn {
    background:#111827;
    color:#fff;
    border:none;
    padding:0 20px;
    cursor:pointer;
}

.affx-copy-btn:hover {
    background:#000;
}

/* TABLE */
.affx-table {
    width:100%;
    border-collapse:collapse;
    margin-top:30px;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
}

.affx-table th {
    background:#f3f4f6;
    text-align:left;
    padding:12px;
    font-size:13px;
    color:#555;
}

.affx-table td {
    padding:12px;
    border-bottom:1px solid #eee;
}

.affx-table tr:hover {
    background:#fafafa;
}

/* STATUS */
.affx-status {
    padding:5px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:500;
}

.affx-status.pending { background:#FEF3C7; color:#92400E; }
.affx-status.paid { background:#DBEAFE; color:#1E40AF; }
.affx-status.approved { background:#DCFCE7; color:#166534; }

/* TOAST */
.affx-toast {
    position:fixed;
    bottom:20px;
    right:20px;
    background:#111;
    color:#fff;
    padding:10px 16px;
    border-radius:8px;
    opacity:0;
    transition:0.3s;
}
.affx-toast.show { opacity:1; }
</style>

<div class="affx-dashboard">

    <div class="affx-header">
        <div class="affx-title">Affiliate Dashboard test</div>
    </div>

    <!-- CARDS -->
    <div class="affx-grid">
        <div class="affx-card">
            <small>Total Earnings</small>
            <h2>₹<?php echo number_format($total, 2); ?></h2>
        </div>

        <div class="affx-card" style="background:linear-gradient(135deg,#f59e0b,#f97316)">
            <small>Pending</small>
            <h2>₹<?php echo number_format($pending, 2); ?></h2>
        </div>

        <div class="affx-card" style="background:linear-gradient(135deg,#10b981,#059669)">
            <small>Paid</small>
            <h2>₹<?php echo number_format($paid, 2); ?></h2>
        </div>
    </div>

    <!-- REFERRAL -->
    <h3 style="margin-top:30px;">Your Referral Link</h3>

    <div class="affx-ref-box">
        <input type="text" value="<?php echo esc_url($ref_url); ?>" id="affx-ref-input" readonly>
        <button class="affx-copy-btn" onclick="copyRef()">Copy</button>
    </div>

    <!-- TABLE -->
    <h3 style="margin-top:30px;">Commission History</h3>

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
                <td><?php echo esc_html(date('M d, Y', strtotime($row->created_at))); ?></td>
                <td>₹<?php echo number_format($row->order_amount, 2); ?></td>
                <td><strong>₹<?php echo number_format($row->commission_amount, 2); ?></strong></td>
                <td>
                    <span class="affx-status <?php echo esc_attr($row->status); ?>">
                        <?php echo esc_html(ucfirst($row->status)); ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="4">No commissions yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<div id="affx-toast" class="affx-toast">Copied!</div>

<script>
function copyRef() {
    const input = document.getElementById("affx-ref-input");
    input.select();
    document.execCommand("copy");

    const toast = document.getElementById("affx-toast");
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 2000);
}
</script>