<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\Lago\Enums\SubscriptionStatusEnum;
use Minds\Core\Payments\Lago\Manager;
use Minds\Core\Payments\Lago\Types\Subscription;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class SubscriptionsController
{
    public function __construct(
        private readonly Manager $manager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param Subscription $subscription
     * @return Subscription
     * @throws GuzzleException
     */
    #[Mutation]
    #[Logged]
    public function createSubscription(
        Subscription $subscription
    ): Subscription {
        return $this->manager->createSubscription($subscription);
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
    #[Query]
    public function getSubscriptions(
        int $page = 1,
        int $perPage = 12,
        ?int $mindsCustomerId = null,
        ?string $planCodeId = null,
        ?SubscriptionStatusEnum $status = null,
    ): array {
        return $this->manager->getSubscriptions(
            page: $page,
            perPage: $perPage,
            mindsCustomerId: $mindsCustomerId,
            planCodeId: $planCodeId,
            status: $status
        );
    }
}
