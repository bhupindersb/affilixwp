<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Dashboard {

    public static function render() {
        global $wpdb;

        $per_page = 10;
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $offset = ($page - 1) * $per_page;
        

        $commissions = $wpdb->prefix . 'affilixwp_commissions';
        $referrals   = $wpdb->prefix . 'affilixwp_referrals';

        $where = '1=1';

        if (!empty($_GET['from'])) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $_GET['from']);
        }
        if (!empty($_GET['to'])) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $_GET['to'] . ' 23:59:59');
        }
        if (!empty($_GET['affiliate'])) {
            $where .= $wpdb->prepare(" AND referrer_user_id = %d", (int) $_GET['affiliate']);
        }

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="affilixwp-commissions.csv"');

            $rows = $wpdb->get_results("
                SELECT * FROM $commissions
                WHERE $where
                ORDER BY created_at DESC
            ");

            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Date',
                'Affiliate ID',
                'Buyer ID',
                'Order Amount',
                'Commission',
                'Status'
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->created_at,
                    $r->referrer_user_id,
                    $r->referred_user_id,
                    $r->order_amount,
                    $r->commission_amount,
                    $r->status
                ]);
            }

            fclose($out);
            exit;
        }


        // KPIs
        $total_revenue = (float) $wpdb->get_var("
            SELECT SUM(order_amount) FROM wp_affilixwp_commissions;
        ");

        $total_commissions = (float) $wpdb->get_var("
            SELECT SUM(commission_amount) FROM wp_affilixwp_commissions;
        ");

        $active_affiliates = (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT referrer_user_id) FROM wp_affilixwp_commissions;
        ");

        $total_conversions = (int) $wpdb->get_var("
            SELECT COUNT(*) FROM wp_affilixwp_commissions;
        ");

        // Recent commissions
        $recent = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $commissions
                WHERE $where    
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM $commissions");
        $total_pages = ceil($total_rows / $per_page);

        ?>
        <div class="wrap">
            <h1>AffilixWP Dashboard</h1>
            <p class="description">Affiliate & Multi-Level Commission Overview</p>

            <style>
                .flex { display: flex; align-items: flex-start; gap:40px; }
                .affx-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin:20px 0; }
                .affx-card { background:#fff; border:1px solid #ddd; padding:20px; border-radius:8px; }
                .affx-card h2 { margin:0 0 6px; font-size:24px; }
                .affx-muted { color:#666; font-size:13px; }
                table.affx-table { width:100%; border-collapse:collapse; margin-top:20px; }
                table.affx-table th, table.affx-table td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
                table.affx-table th { background:#fafafa; }
                .performance-section { width: 50% }
                .leaderboard-section { width: 50%; }
                .performance-section canvas { width: 100% !important; height: auto !important; }
                .affx-status {
                    padding: 4px 10px;
                    border-radius: 999px;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: capitalize;
                }
                .affx-status.pending { background:#FEF3C7; color:#92400E; }
                .affx-status.approved { background:#DCFCE7; color:#166534; }
                .affx-status.paid { background:#DBEAFE; color:#1E40AF; }
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

                <form method="get" style="margin:15px 0;">
                    <input type="hidden" name="page" value="affilixwp">

                    <input type="date" name="from" value="<?php echo esc_attr($_GET['from'] ?? ''); ?>">
                    <input type="date" name="to" value="<?php echo esc_attr($_GET['to'] ?? ''); ?>">

                    <input type="number" name="affiliate" placeholder="Affiliate ID"
                        value="<?php echo esc_attr($_GET['affiliate'] ?? ''); ?>">

                    <button class="button">Filter</button>
                </form>

                <a class="button button-secondary" href="<?php echo esc_url(add_query_arg('export', 'csv')); ?>">
                    Export CSV
                </a>

                <?php
                    echo '<table class="widefat striped">';
                    echo '<thead>
                    <tr>
                        <th>Date</th>
                        <th>Affiliate</th>
                        <th>Buyer</th>
                        <th>Order Amount</th>
                        <th>Commission</th>
                        <th>Status</th>
                    </tr>
                    </thead><tbody>';

                    if (!empty($recent)) {

                        foreach ($recent as $row) {

                            // Affiliate
                            $affiliate = get_user_by('id', (int) $row->referrer_user_id);
                            $affiliate_name = $affiliate
                                ? $affiliate->display_name
                                : 'User #' . $row->referrer_user_id;
                            $affiliate_link = $affiliate
                                ? admin_url('user-edit.php?user_id=' . $affiliate->ID)
                                : '';

                            // Buyer
                            $buyer = get_user_by('id', (int) $row->referred_user_id);
                            $buyer_name = $buyer
                                ? $buyer->display_name
                                : 'User #' . $row->referred_user_id;

                            $buyer_link = $buyer
                                ? admin_url('user-edit.php?user_id=' . $buyer->ID)
                                : '';    

                            echo '<tr>
                                <td>' . esc_html(date('Y-m-d', strtotime($row->created_at))) . '</td>
                                <td>
                                    <a href="<?php echo esc_url($affiliate_link); ?>">
                                        <strong><?php echo esc_html($affiliate_name); ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($buyer_link); ?>">
                                        <strong><?php echo esc_html($buyer_name); ?></strong>
                                    </a>
                                </td>
                                <td>₹' . number_format((float)$row->order_amount, 2) . '</td>
                                <td>₹' . number_format((float)$row->commission_amount, 2) . '</td>
                                <td>
                                    <span class="affx-status <?php echo esc_attr($row->status); ?>">
                                        <?php echo esc_html($row->status); ?>
                                    </span>
                                </td>
                            </tr>';
                        }

                    } else {
                        echo '<tr><td colspan="6">No commissions recorded yet.</td></tr>';
                    }

                    echo '</tbody></table>';

                    if ($total_pages > 1) {
                        echo '<div class="tablenav"><div class="tablenav-pages">';

                        for ($i = 1; $i <= $total_pages; $i++) {
                            $url = add_query_arg('paged', $i);
                            echo '<a class="button ' . ($i === $page ? 'button-primary' : '') . '" href="' . esc_url($url) . '">' . $i . '</a> ';
                        }

                        echo '</div></div>';
                    }

                ?>

            
        </div>
        <?php

        $metrics = AffilixWP_Metrics::get_chart_data();
        ?>
        <hr>
        <div class="wrap flex">
            <div class="performance-section">
            <h2>Performance (Last 30 Days)</h2>

            <div style="max-width:1000px">
                <canvas id="affilixwpCommissionChart" height="120"></canvas>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    const labels = <?php echo wp_json_encode($metrics['days']); ?>;

                    new Chart(document.getElementById('affilixwpCommissionChart'), {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Total Commissions',
                                data: <?php echo wp_json_encode($metrics['commission']); ?>,
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22,163,74,0.15)',
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            plugins: {
                                legend: { display: true }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });

                });
            </script>
            </div>
        <?php

        self::render_leaderboard();

    }

    private static function render_leaderboard() {
        global $wpdb;

        $table = $wpdb->prefix . 'affilixwp_commissions';

        $leaders = $wpdb->get_results("
            SELECT 
                referrer_user_id,
                COUNT(*) AS conversions,
                SUM(commission_amount) AS total_commission
            FROM {$wpdb->prefix}affilixwp_commissions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY referrer_user_id
            ORDER BY total_commission DESC
            LIMIT 10
        ");

        echo '<div class="leaderboard-section"><h2 style="margin-top:30px;">🏆 Top Affiliates (Last 30 Days)</h2>';

        if (empty($leaders)) {
            echo '<p>No commissions recorded yet.</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead>
        <tr>
        <th>#</th>
        <th>Affiliate</th>
        <th>Conversions</th>
        <th>Total Commission</th>
        </tr>
        </thead><tbody>';

        $rank = 1;

        foreach ($leaders as $row) {
            $user = get_user_by('id', (int)$row->referrer_user_id);
            $name = $user ? $user->display_name : 'User #' . $row->referrer_user_id;

            echo '<tr>
                <td>' . $rank++ . '</td>
                <td>' . esc_html($name) . '</td>
                <td>' . intval($row->conversions) . '</td>
                <td><strong>₹' . number_format($row->total_commission, 2) . '</strong></td>
            </tr>';
        }

        echo '</tbody></table></div></div>';
    }


}
