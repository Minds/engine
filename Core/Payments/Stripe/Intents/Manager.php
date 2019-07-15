<?php

namespace Minds\Core\Payments\Stripe\Intents;

class Manager
{

    /**
     * Add a payment intent to stripe
     * @param Intent $intent
     * @return string
     */
    public function add(Intent $intent): string
    {
        $params = [
            'amount' => $intent->getAmount(),
            'currency' => $intent->getCurrency(),
            'setup_future_usage' => 'off_session',
            'payment_method_types' => [
                'card',
            ],
            'application_fee_amount' => $intent->getServiceFee(),
            'transfer_data' => [
                'destination' => $order->getStripeAccountId(),
            ],
        ];

        $stripeIntent = \Stripe\PaymentIntent::create($params);

        return $stripeIntent->id;
    }

}