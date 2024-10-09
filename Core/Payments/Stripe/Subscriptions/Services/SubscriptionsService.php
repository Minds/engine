<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Subscriptions\Services;

use Minds\Core\Payments\Stripe\StripeClient;
use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\Subscription;

class SubscriptionsService
{
    public function __construct(
        private readonly StripeClient $stripeClient,
    ) {
    }

    /**
     * @param string $subscriptionId
     * @return Subscription
     * @throws ApiErrorException
     */
    public function retrieveSubscription(string $subscriptionId): Subscription
    {
        return $this->stripeClient
            ->subscriptions
            ->retrieve($subscriptionId);
    }

    /**
     * Returns subscriptions for a user
     */
    public function getSubscriptions(
        string $customerId,
        string $priceId = null,
        string $status = null,
    ): Collection {
        $opts = [
            'customer' => $customerId
        ];

        if ($priceId) {
            $opts['price'] = $priceId;
        }

        if ($status) {
            $opts['status'] = $status;
        }

        return $this->stripeClient
            ->subscriptions
            ->all($opts);
    }

    /**
     * Creates a subscription for a user
     */
    public function createSubscription(
        string $customerId,
        string $paymentMethodId,
        array $items,
        int $trialDays = 0,
        array $metadata = [],
    ): Subscription {
        return $this->stripeClient
            ->subscriptions
            ->create([
                'customer' => $customerId,
                'default_payment_method' => $paymentMethodId,
                'trial_period_days' => $trialDays,
                'items' => $items,
                'metadata' => $metadata
            ]);
    }

    /**
     * @param string $subscriptionId
     * @param array $metadata
     * @return Subscription
     * @throws ApiErrorException
     */
    public function updateSubscription(string $subscriptionId, array $metadata = []): Subscription
    {
        return $this->stripeClient
            ->subscriptions
            ->update(
                $subscriptionId,
                [
                    'metadata' => $metadata,
                ]
            );
    }

    /**
     * Cancels a stripe subscription
     */
    public function cancelSubscription(string $subscriptionId): Subscription
    {
        return $this->stripeClient
            ->subscriptions
            ->cancel($subscriptionId);
    }
}
