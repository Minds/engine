<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Payments\Lago\Clients\CustomersClient;
use Minds\Core\Payments\Lago\Clients\SubscriptionsClient;
use Minds\Core\Payments\Lago\Enums\SubscriptionStatusEnum;
use Minds\Core\Payments\Lago\Types\Customer;
use Minds\Core\Payments\Lago\Types\Subscription;

class Manager
{
    public function __construct(
        private readonly CustomersClient $customersClient,
        private readonly SubscriptionsClient $subscriptionsClient,
    ) {
    }

    /**
     * @param Customer $customer
     * @return Customer
     * @throws GuzzleException
     */
    public function createCustomer(Customer $customer): Customer
    {
        return $this->customersClient->createCustomer($customer);
    }

    /**
     * @param Subscription $subscription
     * @return Subscription
     * @throws GuzzleException
     */
    public function createSubscription(Subscription $subscription): Subscription
    {
        return $this->subscriptionsClient->createSubscription($subscription);
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int|null $mindsCustomerId
     * @param string|null $planCodeId
     * @param SubscriptionStatusEnum|null $status
     * @return Subscription[]
     * @throws GuzzleException
     */
    public function getSubscriptions(
        int $page = 1,
        int $perPage = 12,
        ?int $mindsCustomerId = null,
        ?string $planCodeId = null,
        ?SubscriptionStatusEnum $status = null,
    ): array {
        return iterator_to_array(
            iterator: $this->subscriptionsClient->getSubscriptions(
                page: $page,
                perPage: $perPage,
                mindsCustomerId: $mindsCustomerId,
                planCodeId: $planCodeId,
                status: $status
            )
        );
    }
}
