<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Dashboard {

    public static function render() {
        global $wpdb;

        $commissions = $wpdb->prefix . 'affilixwp_commissions';

        /* ---------------- PAGINATION ---------------- */
        $per_page = 10;
        $page     = max(1, (int) ($_GET['paged'] ?? 1));
        $offset   = ($page - 1) * $per_page;

        /* ---------------- FILTERS ---------------- */
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

        /* ---------------- CSV EXPORT ---------------- */
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename=affilixwp-commissions.csv');

            $rows = $wpdb->get_results("
                SELECT * FROM $commissions
                WHERE $where
                ORDER BY created_at DESC
            ");

            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date','Affiliate','Buyer','Order Amount','Commission','Status']);

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

        /* ---------------- KPIs ---------------- */
        $total_revenue = (float) $wpdb->get_var("SELECT SUM(order_amount) FROM $commissions");
        $total_commissions = (float) $wpdb->get_var("SELECT SUM(commission_amount) FROM $commissions");
        $active_affiliates = (int) $wpdb->get_var("SELECT COUNT(DISTINCT referrer_user_id) FROM $commissions");
        $total_conversions = (int) $wpdb->get_var("SELECT COUNT(*) FROM $commissions");

        /* ---------------- DATA ---------------- */
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

        $total_rows  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $commissions WHERE $where");
        $total_pages = ceil($total_rows / $per_page);
        ?>

        <div class="wrap">
            <h1>AffilixWP Dashboard</h1>
            <p class="description">Affiliate & Multi-Level Commission Overview</p>

            <style>
                .affx-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin:20px 0; }
                .affx-card { background:#fff; border:1px solid #ddd; padding:20px; border-radius:8px; }
                .affx-muted { color:#666; font-size:13px; }
                .affx-status { padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; text-transform:capitalize; }
                .affx-status.pending { background:#FEF3C7; color:#92400E; }
                .affx-status.approved { background:#DCFCE7; color:#166534; }
                .affx-status.paid { background:#DBEAFE; color:#1E40AF; }
                .filter-row { display:flex; justify-content:space-between; align-items:center; gap:10px; margin:15px 0; }
            </style>

            <!-- KPI CARDS -->
            <div class="affx-grid">
                <div class="affx-card"><h2>₹ <?php echo number_format($total_revenue,2); ?></h2><div class="affx-muted">Total Revenue</div></div>
                <div class="affx-card"><h2>₹ <?php echo number_format($total_commissions,2); ?></h2><div class="affx-muted">Total Commissions</div></div>
                <div class="affx-card"><h2><?php echo $active_affiliates; ?></h2><div class="affx-muted">Active Affiliates</div></div>
                <div class="affx-card"><h2><?php echo $total_conversions; ?></h2><div class="affx-muted">Conversions</div></div>
            </div>

            <h2>Recent Commissions</h2>

            <!-- FILTER BAR -->
            <div class="filter-row">
                <form method="get">
                    <input type="hidden" name="page" value="affilixwp">
                    <input type="date" name="from" value="<?php echo esc_attr($_GET['from'] ?? ''); ?>">
                    <input type="date" name="to" value="<?php echo esc_attr($_GET['to'] ?? ''); ?>">
                    <input type="number" name="affiliate" placeholder="Affiliate ID" value="<?php echo esc_attr($_GET['affiliate'] ?? ''); ?>">
                    <button class="button">Filter</button>
                </form>

                <a class="button button-secondary" href="<?php echo esc_url(add_query_arg('export','csv')); ?>">
                    Export CSV
                </a>
            </div>

            <!-- TABLE -->
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Affiliate</th>
                        <th>Buyer</th>
                        <th>Order Amount</th>
                        <th>Commission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recent): foreach ($recent as $row):

                    $affiliate = get_user_by('id', $row->referrer_user_id);
                    $buyer     = get_user_by('id', $row->referred_user_id);

                    $affiliate_name = $affiliate ? $affiliate->display_name : 'User #' . $row->referrer_user_id;
                    $buyer_name     = $buyer ? $buyer->display_name : 'User #' . $row->referred_user_id;
                ?>
                    <tr>
                        <td><?php echo esc_html(date('Y-m-d', strtotime($row->created_at))); ?></td>
                        <td><strong><?php echo esc_html($affiliate_name); ?></strong></td>
                        <td><?php echo esc_html($buyer_name); ?></td>
                        <td>₹<?php echo number_format($row->order_amount,2); ?></td>
                        <td>₹<?php echo number_format($row->commission_amount,2); ?></td>
                        <td><span class="affx-status <?php echo esc_attr($row->status); ?>"><?php echo esc_html($row->status); ?></span></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6">No commissions found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <?php for ($i=1;$i<=$total_pages;$i++): ?>
                        <a class="button <?php echo $i===$page?'button-primary':''; ?>"
                           href="<?php echo esc_url(add_query_arg('paged',$i)); ?>">
                           <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php
        self::render_leaderboard();
    }

    private static function render_leaderboard() {
        global $wpdb;

        $table = $wpdb->prefix . 'affilixwp_commissions';

        $leaders = $wpdb->get_results("
            SELECT referrer_user_id, COUNT(*) conversions, SUM(commission_amount) total_commission
            FROM $table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY referrer_user_id
            ORDER BY total_commission DESC
            LIMIT 10
        ");

        echo '<h2 style="margin-top:30px;">🏆 Top Affiliates (Last 30 Days)</h2>';
        echo '<table class="widefat striped"><thead><tr><th>#</th><th>Affiliate</th><th>Conversions</th><th>Total Commission</th></tr></thead><tbody>';

        $rank = 1;
        foreach ($leaders as $row) {
            $user = get_user_by('id', $row->referrer_user_id);
            echo '<tr>
                <td>'.$rank++.'</td>
                <td>'.esc_html($user ? $user->display_name : 'User #'.$row->referrer_user_id).'</td>
                <td>'.intval($row->conversions).'</td>
                <td><strong>₹'.number_format($row->total_commission,2).'</strong></td>
            </tr>';
        }

        echo '</tbody></table>';
    }
}
