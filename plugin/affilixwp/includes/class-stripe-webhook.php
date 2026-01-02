<?php
class AffilixWP_Stripe_Webhook {

    public function __construct() {
        add_action('rest_api_init', function () {
            register_rest_route('affilixwp/v1', '/stripe-webhook', [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function handle($request) {
        $payload = json_decode($request->get_body(), true);

        if ($payload['type'] !== 'checkout.session.completed') {
            return rest_ensure_response(['ignored' => true]);
        }

        $session = $payload['data']['object'];
        $user_id = intval($session['client_reference_id']);
        $amount = $session['amount_total'] / 100;
        $order_id = $session['id'];

        (new AffilixWP_Commissions())->record_commission(
            $user_id,
            $order_id,
            $amount
        );

        return rest_ensure_response(['success' => true]);
    }
}
