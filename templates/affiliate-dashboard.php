<?php
/**
 * AffilixWP Affiliate Dashboard Template
 * Save logo in: /wp-content/plugins/affilixwp/assets/images/logo.png
 */

global $wpdb;

$user_id = get_current_user_id();
$table = $wpdb->prefix . 'affilixwp_commissions';

$total = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(commission_amount) FROM $table WHERE referrer_user_id = %d",
        $user_id
    )
);

$pending = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(commission_amount) FROM $table WHERE referrer_user_id = %d AND status = 'pending'",
        $user_id
    )
);

$paid = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(commission_amount) FROM $table WHERE referrer_user_id = %d AND status = 'paid'",
        $user_id
    )
);

$commissions = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table WHERE referrer_user_id = %d ORDER BY created_at DESC LIMIT 50",
        $user_id
    )
);

$affiliate = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT referral_code FROM {$wpdb->prefix}affilixwp_affiliates WHERE user_id = %d",
        $user_id
    )
);


$ref_url = home_url('/?ref=' . ($affiliate->referral_code ?? ''));
$logo_url = AFFILIXWP_URL . 'assets/images/logo-affilixwp.svg';
$current_user = wp_get_current_user();
?>

<style>
.affx-shell {
    display: grid;
    grid-template-columns: 280px 1fr;
    min-height: 100vh;
    background: #f4f7fb;
    font-family: Inter, sans-serif;
}

.affx-sidebar {
    background: #111827;
    color: #fff;
    padding: 30px 24px;
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.affx-brand {
    display: flex;
    align-items: center;
    gap: 14px;
}

.affx-brand img {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    object-fit: contain;
    background: #fff;
    padding: 6px;
}

.affx-brand h2 {
    font-size: 20px;
    margin: 0;
}

.affx-menu a {
    display: block;
    color: #d1d5db;
    text-decoration: none;
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 10px;
}

.affx-menu a.active,
.affx-menu a:hover {
    background: rgba(255,255,255,.08);
    color: #fff;
}

.affx-main {
    padding: 40px;
}

.affx-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.affx-user {
    background: #fff;
    padding: 12px 18px;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0,0,0,.05);
}

.affx-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(240px,1fr));
    gap: 24px;
}

.affx-card {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 8px 25px rgba(0,0,0,.06);
}

.affx-card h3 {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.affx-card h2 {
    font-size: 34px;
    margin: 10px 0 0;
}

.affx-section {
    margin-top: 32px;
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 8px 25px rgba(0,0,0,.05);
}

.affx-ref-box {
    display: flex;
    gap: 12px;
    margin-top: 16px;
}

.affx-ref-box input {
    flex: 1;
    padding: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.affx-copy-btn {
    background: #4f46e5;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 0 20px;
    cursor: pointer;
}

.affx-table {
    width: 100%;
    border-collapse: collapse;
}

.affx-table th,
.affx-table td {
    padding: 16px;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
}

.affx-status {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
}

.affx-status.pending { background:#FEF3C7; color:#92400E; }
.affx-status.paid { background:#DBEAFE; color:#1E40AF; }
.affx-status.approved { background:#DCFCE7; color:#166534; }

@media(max-width: 991px){
    .affx-shell { grid-template-columns: 1fr; }
    .affx-sidebar { min-height: auto; }
    .affx-main { padding: 20px; }
}
</style>

<div class="affx-shell">
    <aside class="affx-sidebar">
        <div class="affx-brand">
            <img src="<?php echo esc_url($logo_url); ?>" alt="AffilixWP Logo">
            <div>
                <h2>AffilixWP</h2>
                <small>Affiliate Panel</small>
            </div>
        </div>

        <nav class="affx-menu">
            <a href="#" class="active">Dashboard</a>
            <a href="#referral">Referral Link</a>
            <a href="#history">Commission History</a>
        </nav>
    </aside>

    <main class="affx-main">
        <div class="affx-topbar">
            <div>
                <h1>Welcome back, <?php echo esc_html($current_user->display_name); ?></h1>
                <p>Track earnings and manage your referrals.</p>
            </div>
            <div class="affx-user">Affiliate ID: <?php echo esc_html($user_id); ?></div>
        </div>

        <div class="affx-cards">
            <div class="affx-card">
                <h3>Total Earnings</h3>
                <h2>₹<?php echo number_format($total, 2); ?></h2>
            </div>
            <div class="affx-card">
                <h3>Pending</h3>
                <h2>₹<?php echo number_format($pending, 2); ?></h2>
            </div>
            <div class="affx-card">
                <h3>Paid</h3>
                <h2>₹<?php echo number_format($paid, 2); ?></h2>
            </div>
        </div>

        <section class="affx-section" id="referral">
            <h3>Your Referral Link</h3>
            <div class="affx-ref-box">
                <input type="text" value="<?php echo esc_url($ref_url); ?>" id="affx-ref-input" readonly>
                <button class="affx-copy-btn" onclick="copyRef()">Copy</button>
            </div>
        </section>

        <section class="affx-section" id="history">
            <h3>Commission History</h3>
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
                            <td><span class="affx-status <?php echo esc_attr($row->status); ?>"><?php echo esc_html(ucfirst($row->status)); ?></span></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4">No commissions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<script>
function copyRef() {
    const input = document.getElementById('affx-ref-input');
    input.select();
    document.execCommand('copy');
    alert('Referral link copied');
}
</script>
