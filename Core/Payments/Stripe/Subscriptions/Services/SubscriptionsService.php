<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Subscriptions\Services;

use Minds\Core\Payments\Stripe\StripeClient;
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
}
