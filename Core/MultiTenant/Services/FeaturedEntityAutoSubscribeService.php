<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Manager as GroupsMembershipManager;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Services\PostSubscriptionsService;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

/**
 * Service for automatically subscribing a new user to a networks featured users
 * and automatically joining featured groups.
 */
class FeaturedEntityAutoSubscribeService
{
    public function __construct(
        private FeaturedEntityService $featuredEntityService,
        private PostSubscriptionsService $postSubscriptionsService,
        private GroupsMembershipManager $groupsMembershipManager,
        private EntitiesBuilder $entitiesBuilder
    ) {
    }

    /**
     * Automatically subscribe a user to featured users / join featured groups.
     * @param User $subject - subject user.
     * @param int $tenantId - id of the tenant that the user is on.
     * @return void
     */
    public function autoSubscribe(
        User $subject,
        int $tenantId
    ): void {
        $featuredEntities = $this->featuredEntityService->getAllFeaturedEntities($tenantId);

        foreach ($featuredEntities as $featuredEntity) {
            if ($featuredEntity instanceof FeaturedUser) {
                $this->handleFeaturedUser($featuredEntity, $subject);
            } elseif ($featuredEntity instanceof FeaturedGroup) {
                $this->handleFeaturedGroup($featuredEntity, $subject);
            }
        }
    }

    /**
     * Handle a featured user - auto-subscribing the subject to the featured user if appropriate.
     * @param FeaturedUser $featuredUser - featured user.
     * @param User $subject - subject user.
     * @return void
     */
    public function handleFeaturedUser(FeaturedUser $featuredUser, User $subject): void
    {
        if (!$featuredUser->autoSubscribe) {
            return;
        }

        $subject->subscribe($featuredUser->entityGuid);

        // Post notifications
        if ($featuredUser->autoPostSubscription) {
            $featuredUserEntity = $this->entitiesBuilder->single($featuredUser->entityGuid);

            $this->postSubscriptionsService->withEntity($featuredUserEntity)
                ->withUser($subject)
                ->subscribe(PostSubscriptionFrequencyEnum::ALWAYS);
        }
    }

    /**
     * Handle a featured group - making the subject auto-join the featured group if appropriate.
     * Existing members, moderators and owners are left untouched, so their level
     * and any site membership link are never overwritten.
     * @param FeaturedGroup $featuredGroup - featured group.
     * @param User $subject - subject user.
     * @return bool - true if the subject was added to the group.
     */
    public function handleFeaturedGroup(FeaturedGroup $featuredGroup, User $subject): bool
    {
        if (!$featuredGroup->autoSubscribe) {
            return false;
        }

        $group = $this->entitiesBuilder->single($featuredGroup->entityGuid);

        if (!$group instanceof Group) {
            return false;
        }

        try {
            $membership = $this->groupsMembershipManager->getMembership($group, $subject);
            if ($membership->isBanned()) {
                return false; // Do not join banned members
            }
            if (!$membership->isAwaiting()) {
                return false; // Already a member, moderator or owner.
            }
        } catch (NotFoundException $e) {
            // This is ok
        }

        return $this->groupsMembershipManager->joinGroup(
            group: $group,
            user: $subject,
            membershipLevel: GroupMembershipLevelEnum::MEMBER
        );
    }
}
