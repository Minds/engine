<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\SiteMemberships\Exceptions\NoSiteMembershipFoundException;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services\PaywalledEntityService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\Activity;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Annotations\Query;

class SiteMembershipReaderController
{
    public function __construct(
        private readonly SiteMembershipReaderService $siteMembershipReaderService,
        private readonly PaywalledEntityService $paywalledEntityService,
        private readonly EntitiesBuilder $entitiesBuilder
    ) {
    }

    /**
     * @return SiteMembership[]
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     */
    #[Query]
    public function siteMemberships(): array
    {
        return $this->siteMembershipReaderService->getSiteMemberships();
    }

    /**
     * @param string $membershipGuid
     * @return SiteMembership
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws NoSiteMembershipFoundException
     * @throws ApiErrorException
     */
    #[Query]
    public function siteMembership(
        string $membershipGuid
    ): SiteMembership {
        return $this->siteMembershipReaderService->getSiteMembership((int)$membershipGuid);
    }

    /**
     * Gets the lowest price site membership for an activity.
     * @param string $activityGuid - The activity guid to get the lowest price site membership for.
     * @param bool|null $externalOnly - Whether to only check for external site memberships.
     * @throws NotFoundException - If the activity is not found, or the entity is not an activity.
     * @return SiteMembership|null - The lowest price site membership for the activity, or null if no site membership is found.
     */
    #[Query]
    public function lowestPriceSiteMembershipForActivity(
        string $activityGuid,
        ?bool $externalOnly = false
    ): ?SiteMembership {
        $activity = $this->entitiesBuilder->single($activityGuid);

        if (!$activity || !($activity instanceof Activity)) {
            throw new NotFoundException();
        }

        if (!$activity->hasSiteMembership()) {
            return null;
        }

        return $this->paywalledEntityService->lowestPriceSiteMembershipForActivity($activity, $externalOnly);
    }
}
