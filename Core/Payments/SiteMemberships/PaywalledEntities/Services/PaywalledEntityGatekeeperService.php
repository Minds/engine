<?php
namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities\Services;

use Minds\Core\Payments\SiteMemberships\PaywalledEntities\PaywalledEntitiesRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Psr\SimpleCache\CacheInterface;

class PaywalledEntityGatekeeperService
{
    public function __construct(
        private PaywalledEntitiesRepository $paywalledEntitiesRepository,
        private SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService,
        private CacheInterface $cache,
    ) {
        
    }

    /**
     * Determines if a user is able to access the paywalled post
     */
    public function canAccess(Activity $entity, ?User $user =  null): bool
    {
        if (!$user) {
            return false;
        }

        // Owner of the post will always have access
        if ($user->getGuid() === $entity->getOwnerGuid() || $user->isAdmin()) {
            return true;
        }

        $userMembershipGuids = $this->getMembershipGuidsForUser($user);

        if (!$userMembershipGuids) {
            return false;
        }

        $entityMembershipGuids = $this->getMembershipGuidsForActivity($entity);

        // User has a membership AND the activity post is set to all
        if (in_array(-1, $entityMembershipGuids, true) && $userMembershipGuids) {
            return true;
        }

        // User is in a membership set for the entity
        if (array_intersect($entityMembershipGuids, $userMembershipGuids)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the membership guids that have been paired to the activity post
     */
    private function getMembershipGuidsForActivity(Activity $activity): ?array
    {
        return $this->paywalledEntitiesRepository->getMembershipsFromEntity((int) $activity->getGuid());
    }

    /**
     * Returns all the valid membership guids that a user has
     */
    private function getMembershipGuidsForUser(User $user): ?array
    {
        $guids = array_map(
            function (SiteMembershipSubscription $subscription) {
                return $subscription->membershipGuid;
            },
            array_filter(
                $this->siteMembershipSubscriptionsService->getSiteMembershipSubscriptions($user),
                function (SiteMembershipSubscription $subscription) {
                    return !$subscription->validToTimestamp || $subscription->validToTimestamp > time();
                }
            )
        );

        if (empty($guids)) {
            return null;
        }

        return $guids;
    }

}
