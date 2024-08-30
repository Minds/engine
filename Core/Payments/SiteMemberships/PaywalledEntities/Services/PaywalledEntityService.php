<?php
namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\PaywalledEntitiesRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipReaderService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\Activity;
use Psr\SimpleCache\CacheInterface;

class PaywalledEntityService
{
    public function __construct(
        private PaywalledEntitiesRepository $paywalledEntitiesRepository,
        private SiteMembershipReaderService $siteMembershipReaderService,
        private CacheInterface $cache,
        private EntitiesBuilder $entitiesBuilder,
        private Logger $logger
    ) {
        
    }

    /**
     * Determines if a user is able to access the paywalled post
     */
    public function getLowestMembershipGuid(Activity $entity): ?int
    {
        $entityMembershipGuids = $this->getMembershipGuidsForActivity($entity);

        if (!$entityMembershipGuids) {
            return null;
        }
        
        $siteMemberships = $this->siteMembershipReaderService->getSiteMemberships();
        
        // Sort lowest to highest
        usort($siteMemberships, function (SiteMembership $a, SiteMembership $b) {
            return $a->membershipPriceInCents <=> $b->membershipPriceInCents;
        });

        $siteMembershipsGuids = array_map(function (SiteMembership $siteMembership) {
            return $siteMembership->membershipGuid;
        }, $siteMemberships);

        if ($entityMembershipGuids[0] === -1) {
            return $siteMembershipsGuids[0];
        }

        $intersect = array_values(array_intersect($siteMembershipsGuids, $entityMembershipGuids));

        return $intersect[0];
    }


    /**
     * Gets the lowest price site membership for a given activity.
     * @param Activity $activity - The activity to get the lowest price site membership for.
     * @param bool $externalOnly - Whether to only consider external site memberships.
     * @return SiteMembership|null - The lowest price site membership for the activity, or null if no site membership is found.
     */
    public function lowestPriceSiteMembershipForActivity(Activity $activity, bool $externalOnly = false): ?SiteMembership
    {
        $entityMembershipGuids = $this->getMembershipGuidsForActivity($activity);

        if (!$entityMembershipGuids) {
            return null;
        }

        $siteMemberships = $this->siteMembershipReaderService->getSiteMemberships();

        if ($externalOnly) {
            $siteMemberships = array_filter($siteMemberships, fn (SiteMembership $siteMembership) => $siteMembership->isExternal);
        }

        // Sort lowest to highest price
        usort($siteMemberships, function (SiteMembership $a, SiteMembership $b) {
            return $a->membershipPriceInCents <=> $b->membershipPriceInCents;
        });

        foreach ($siteMemberships as $siteMembership) {
            if (in_array($siteMembership->membershipGuid, $entityMembershipGuids, true)) {
                return $siteMembership;
            }
        }

        return null;
    }

    /**
     * Returns the membership guids that have been paired to the activity post
     * @return int[]|null
     */
    public function getMembershipGuidsForActivity(Activity $activity): ?array
    {
        return $this->paywalledEntitiesRepository->getMembershipsFromEntity((int) $activity->getGuid());
    }

}
