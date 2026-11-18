<?php
if (!defined('ABSPATH')) exit;

class AffilixWP_Commission_Engine {

    public static function record_purchase($buyer_user_id, $order_amount, $reference) {
        global $wpdb;

        $buyer_user_id = absint($buyer_user_id);
        $order_amount = round((float) $order_amount, 2);
        $reference = sanitize_text_field($reference);

        if (!$buyer_user_id || $order_amount <= 0 || $reference === '') {
            return false;
        }

        $commissions = $wpdb->prefix . 'affilixwp_commissions';
        $referrals   = $wpdb->prefix . 'affilixwp_referrals';
        $affiliates  = $wpdb->prefix . 'affilixwp_affiliates';

        // One reference represents one purchase; a retry must not duplicate
        // either level of commission.
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $commissions WHERE reference = %s", $reference)
        );
        if ($exists) {
            return true;
        }

        $chain = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT referrer_user_id, level
                 FROM $referrals
                 WHERE referred_user_id = %d
                 ORDER BY level ASC",
                $buyer_user_id
            )
        );

        if (empty($chain)) {
            return true;
        }

        $wpdb->query('START TRANSACTION');

        foreach ($chain as $ref) {
            $referrer_user_id = absint($ref->referrer_user_id);
            if (!$referrer_user_id) {
                $wpdb->query('ROLLBACK');
                return false;
            }

            $affiliate_id = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM $affiliates WHERE user_id = %d", $referrer_user_id)
            );
            $rate = ((int) $ref->level === 1) ? 0.10 : 0.05;
            $commission = round($order_amount * $rate, 2);

            $inserted = $wpdb->insert(
                $commissions,
                [
                    'affiliate_id'      => $affiliate_id ? (int) $affiliate_id : 0,
                    'referrer_user_id'  => $referrer_user_id,
                    'source_user_id'    => $buyer_user_id,
                    'referred_user_id'  => $buyer_user_id,
                    'amount'            => $commission,
                    'order_amount'      => $order_amount,
                    'commission_amount' => $commission,
                    'level'             => (int) $ref->level,
                    'status'            => 'pending',
                    'reference'         => $reference,
                    'created_at'        => current_time('mysql'),
                ],
                ['%d', '%d', '%d', '%d', '%f', '%f', '%f', '%d', '%s', '%s', '%s']
            );

            if ($inserted === false) {
                $wpdb->query('ROLLBACK');
                error_log('AffilixWP: commission insert failed: ' . $wpdb->last_error);
                return false;
            }
        }

        $wpdb->query('COMMIT');
        return true;
    }
}
