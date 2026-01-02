<?php
global $wpdb;
$table = $wpdb->prefix . 'affilixwp_commissions';

$total = $wpdb->get_var("SELECT SUM(commission_amount) FROM $table");
?>

<div class="wrap">
    <h1>AffilixWP Dashboard</h1>
    <p><strong>Total Commissions:</strong> $<?php echo number_format($total, 2); ?></p>
</div>
