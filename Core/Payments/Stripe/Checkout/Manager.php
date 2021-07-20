<?php

namespace Minds\Core\Payments\Stripe\Checkout;

use Stripe\Checkout\Session;

class Manager
{
    /**
     * Get a checkout session id
     * @param Order $order
     * @return CheckoutSession
     */
    public function checkout(Order $order): CheckoutSession
    {
        $lineItems = [];

        $lineItems[] = [
            'name' => $order->getName(),
            'amount' => $order->getAmount(),
            'currency' => $order->getCurrency(),
            'quantity' => $order->getQuantity(),
        ];

        $session = Session::create([
            'payment_method_types' => [
                'card',
            ],
            'line_items' => $lineItems,
            'payment_intent_data' => [
              'application_fee_amount' => $order->getServiceFee(),
              'transfer_data' => [
                'destination' => $order->getStripeAccountId(),
              ],
            ],
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        return $session;
    }
}
