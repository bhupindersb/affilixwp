<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Admin_Payouts_Summary {

    public static function render() {
        global $wpdb;
        $table = $wpdb->prefix . 'affilixwp_commissions';

        $rows = $wpdb->get_results("
            SELECT
                referrer_user_id,
                SUM(CASE WHEN status='approved' THEN commission_amount ELSE 0 END) AS pending,
                SUM(CASE WHEN status='paid' THEN commission_amount ELSE 0 END) AS paid
            FROM $table
            GROUP BY referrer_user_id
        ");
        ?>

        <div class="wrap">
            <h1>Affiliate Payouts</h1>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Affiliate</th>
                        <th>Pending</th>
                        <th>Paid</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($rows as $r): 
                    $user = get_user_by('id', $r->referrer_user_id);
                    $name = $user ? $user->display_name : 'User #' . $r->referrer_user_id;
                    $balance = $r->pending;
                ?>
                    <tr>
                        <td><?php echo esc_html($name); ?></td>
                        <td>₹<?php echo number_format($r->pending, 2); ?></td>
                        <td>₹<?php echo number_format($r->paid, 2); ?></td>
                        <td><strong>₹<?php echo number_format($balance, 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>
        </div>
        <?php
    }
}
