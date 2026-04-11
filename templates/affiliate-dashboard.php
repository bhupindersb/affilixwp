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
);

$ref_url = home_url('/?ref=' . ($affiliate->referral_code ?? ''));
$logo_url = AFFILIXWP_URL . 'assets/images/logo.png';
$current_user = wp_get_current_user();
?>

<style>
/* Optional fallback styles if Tailwind is not loaded */
</style>

<?php
/**
 * To remove header/footer only on dashboard page:
 * 1. Create a WP page template (template-affiliate-dashboard.php)
 * 2. Omit get_header() / get_footer() in that template
 * 3. Render do_shortcode('[affilixwp_dashboard]') inside it
 *
 * Tailwind note:
 * Enqueue Tailwind CSS only on dashboard page via wp_enqueue_style,
 * or compile plugin-specific dashboard.css using Tailwind.
 */
?>

<div class="min-h-screen bg-slate-50 grid lg:grid-cols-[280px_1fr] affx-shell">
    <aside class="bg-slate-900 text-white p-8 flex flex-col gap-8 affx-sidebar">
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

    <main class="p-6 lg:p-10 affx-main">
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
