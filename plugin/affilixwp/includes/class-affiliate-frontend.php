<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Affiliate_Frontend {

    public static function shortcode() {
        if (!is_user_logged_in()) return 'Please log in';

        global $wpdb;
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'affilixwp_commissions';

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    SUM(CASE WHEN status='approved' THEN commission_amount ELSE 0 END) AS pending,
                    SUM(CASE WHEN status='paid' THEN commission_amount ELSE 0 END) AS paid
                 FROM $table
                 WHERE referrer_user_id=%d",
                $user_id
            )
        );

        ob_start();
        ?>
        <h3>My Affiliate Earnings</h3>
        <p><strong>Pending:</strong> ₹<?php echo number_format($stats->pending,2); ?></p>
        <p><strong>Paid:</strong> ₹<?php echo number_format($stats->paid,2); ?></p>
        <?php
        return ob_get_clean();
    }
}
