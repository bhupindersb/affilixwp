<?php
/**
 * AffilixWP Affiliate Dashboard Template (Trackit Style)
 * Save logo: /wp-content/plugins/affilixwp/assets/images/logo-affilixwp.svg
 */
if (!defined('ABSPATH')) exit;

global $wpdb;
$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$table = $wpdb->prefix . 'affilixwp_commissions';
$aff_table = $wpdb->prefix . 'affilixwp_affiliates';

$total = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(commission_amount),0) FROM $table WHERE referrer_user_id=%d", $user_id));
$pending = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(commission_amount),0) FROM $table WHERE referrer_user_id=%d AND status='pending'", $user_id));
$paid = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(commission_amount),0) FROM $table WHERE referrer_user_id=%d AND status='paid'", $user_id));
$referrals = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE referrer_user_id=%d", $user_id));

$affiliate = $wpdb->get_row($wpdb->prepare("SELECT referral_code FROM $aff_table WHERE user_id=%d", $user_id));
$ref_url = home_url('/?ref=' . ($affiliate->referral_code ?? ''));
$logo_url = AFFILIXWP_URL . 'assets/images/logo-affilixwp.svg';

$recent = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table WHERE referrer_user_id=%d ORDER BY created_at DESC LIMIT 10",
    $user_id
));
?>

<div class="affx-shell">
    <aside class="affx-sidebar">
        <div class="affx-brand">
            <img src="<?php echo esc_url($logo_url); ?>" alt="AffilixWP">
            <div>
                <h2>AffilixWP</h2>
                <small>Affiliate Panel</small>
            </div>
        </div>

        <div class="affx-search">
            <input type="text" placeholder="Search">
        </div>

        <nav class="affx-menu">
            <a href="#" class="active">Dashboard</a>
            <a href="#overview">Analytics</a>
            <a href="#referral">Referral Link</a>
            <a href="#payments">Payouts</a>
            <a href="#history">History</a>
        </nav>
    </aside>

    <main class="affx-main">
        <section class="affx-topbar">
            <div>
                <p class="eyebrow">Affiliate Program</p>
                <h1>Analytics Dashboard</h1>
                <p class="subtext">Welcome back, <?php echo esc_html($current_user->display_name); ?>. Track your latest affiliate insights.</p>
            </div>
            <button class="affx-primary-btn">+ Invite Affiliate</button>
        </section>

        <section class="affx-cards">
            <div class="affx-card">
                <span>Total Earnings</span>
                <h3>₹<?php echo number_format($total, 2); ?></h3>
            </div>
            <div class="affx-card">
                <span>Pending</span>
                <h3>₹<?php echo number_format($pending, 2); ?></h3>
            </div>
            <div class="affx-card">
                <span>Paid</span>
                <h3>₹<?php echo number_format($paid, 2); ?></h3>
            </div>
            <div class="affx-card">
                <span>Referrals</span>
                <h3><?php echo esc_html($referrals); ?></h3>
            </div>
        </section>

        <section class="affx-grid" id="overview">
            <div class="affx-panel affx-chart-panel">
                <div class="affx-panel-head">
                    <div>
                        <h3>Performance Overview</h3>
                        <p>Last 30 days</p>
                    </div>
                </div>
                <canvas id="affxRevenueChart"></canvas>
            </div>

            <div class="affx-panel affx-progress-panel">
                <h3>Progress</h3>
                <div class="affx-progress-circle">
                    <div>
                        <strong>84%</strong>
                        <span>Performance</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="affx-panel" id="referral">
            <div class="affx-panel-head">
                <h3>Your Referral Link</h3>
            </div>
            <div class="affx-ref-box">
                <input type="text" readonly value="<?php echo esc_url($ref_url); ?>" id="affx-ref-input">
                <button class="affx-primary-btn" onclick="copyRef()">Copy</button>
            </div>
        </section>

        <section class="affx-panel" id="history">
            <div class="affx-panel-head">
                <h3>Recent Payments</h3>
            </div>
            <div class="affx-table-wrap">
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
                    <?php if ($recent): foreach ($recent as $row): ?>
                        <tr>
                            <td><?php echo esc_html(date('d M Y', strtotime($row->created_at))); ?></td>
                            <td>₹<?php echo number_format((float)$row->order_amount, 2); ?></td>
                            <td>₹<?php echo number_format((float)$row->commission_amount, 2); ?></td>
                            <td><span class="affx-status <?php echo esc_attr($row->status); ?>"><?php echo esc_html(ucfirst($row->status)); ?></span></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4">No payments yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
function copyRef(){
  const input = document.getElementById('affx-ref-input');
  input.select();
  document.execCommand('copy');
  alert('Referral link copied');
}
</script>
