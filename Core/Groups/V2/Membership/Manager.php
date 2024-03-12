<?php
namespace Minds\Core\Groups\V2\Membership;

use DateTime;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\Membership as LegacyMembership;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Experiments;
use Minds\Core\Recommendations\Algorithms\SuggestedGroups\SuggestedGroupsRecommendationsAlgorithm;
use Minds\Core\Security\ACL;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\GroupOperationException;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\UserErrorException;

class Manager
{
    public function __construct(
        protected Repository $repository,
        protected EntitiesBuilder $entitiesBuilder,
        protected ACL $acl,
        protected SuggestedGroupsRecommendationsAlgorithm $groupRecsAlgo,
    ) {
    }

    /**
     * Returns the membership model
     */
    public function getMembership(Group $group, User $user): Membership
    {
        return $this->repository->get($group->getGuid(), $user->getGuid());
    }

    /**
     * Returns a count of members
     */
    public function getMembersCount(Group $group): int
    {
        return $this->repository->getCount($group->getGuid());
    }

    /**
     * Get a groups members.
     * @param Group $group - group to get members for.
     * @param GroupMembershipLevelEnum $membershipLevel - filter by membership level, defaults to only members.
     * @param bool $membershipLevelGte - whether to show matches greater than the provided membership level as well
     * @param int $limit - limit the number of results.
     * @param int $offset - offset the results.
     * @param int|string &$loadNext - passed reference to a $loadNext variable.
     * @return iterable<Membership>
     */
    public function getMembers(
        Group $group,
        GroupMembershipLevelEnum $membershipLevel = null,
        bool $membershipLevelGte = false,
        int $limit = 12,
        int $offset = 0,
        int|string &$loadNext = 0
    ): iterable {
        foreach ($this->repository->getList(
            groupGuid: $group->getGuid(),
            limit: $limit,
            offset: $offset,
            membershipLevel: $membershipLevel,
            membershipLevelGte: $membershipLevelGte
        ) as $membership) {
            $loadNext = ++$offset;

            $user = $this->buildUser($membership->userGuid);

            if (!$user) {
                continue;
            }

            $membership->setUser($user);

            yield $membership;
        }
    }

    /**
     * @return iterable<User>
     */
    public function getRequests(
        Group $group,
        int $limit = 12,
        int $offset = 0,
        int &$loadNext = 0
    ): iterable {
        foreach (
            $this->repository->getList(
                groupGuid: $group->getGuid(),
                membershipLevel: GroupMembershipLevelEnum::REQUESTED,
                limit: $limit,
                offset: $offset
            ) as $membership
        ) {
            $loadNext = ++$offset;

            $user = $this->buildUser($membership->userGuid);

            if (!$user) {
                continue;
            }

            yield $user;
        }
    }

    /**
     * Returns a list of groups a user is a member of
     * @param User $user - user to get groups for.
     * @param GroupMembershipLevelEnum $membershipLevel - filter by membership level, defaults to only members.
     * @param bool $membershipLevelGte - whether to show matches greater than the provided membership level as well
     * @param int $limit - limit the number of results.
     * @param int $offset - offset the results.
     * @param int|string &$loadNext - passed reference to a $loadNext variable.
     * @return iterable<Group>
     */
    public function getGroups(
        User $user,
        GroupMembershipLevelEnum $membershipLevel = null,
        bool $membershipLevelGte = false,
        int $limit = 12,
        int $offset = 0,
        int &$loadNext = 0
    ): iterable {
        foreach (
            $this->repository->getList(
                userGuid: $user->getGuid(),
                limit: $limit,
                offset: $offset,
                membershipLevel: $membershipLevel,
                membershipLevelGte: $membershipLevelGte
            ) as $membership
        ) {
            $loadNext = ++$offset;

            $group = $this->buildGroup($membership->groupGuid);

            if (!$group) {
                continue;
            }

            yield $group;
        }
    }

    /**
     * Returns all the guids for groups a user is a member of
     */
    public function getGroupGuids(User $user, int $limit = 500): array
    {
        return array_map(function ($membership) {
            return $membership->groupGuid;
        }, iterator_to_array($this->repository->getList(
            userGuid: $user->getGuid(),
            limit: $limit
        )));
    }

    /**
     * Alters the users membership level. Use this for promoting users to moderator or owner.
     */
    public function modifyMembershipLevel(Group $group, User $user, User $actor, GroupMembershipLevelEnum $membershipLevel = null): bool
    {
        // Get the users membership
        $userMembership = $this->repository->get($group->getGuid(), $user->getGuid());

        // Get the Actors membership level. They must be at least an owner
        $actorMembership = $this->repository->get($group->getGuid(), $actor->getGuid());

        if (!$actorMembership->isOwner()) {
            throw new ForbiddenException();
        }

        // Update the membership level to a member
        $userMembership->membershipLevel = $membershipLevel;

        return $this->repository->updateMembershipLevel($userMembership);
    }

    /**
     * Joins, or requests to join, a group
     */
    public function joinGroup(
        Group $group,
        User $user,
        GroupMembershipLevelEnum $membershipLevel = null
    ): bool {
        $membership = new Membership(
            groupGuid: $group->getGuid(),
            userGuid: $user->getGuid(),
            createdTimestamp: new DateTime(),
            membershipLevel: $membershipLevel ?:
                ($group->isPublic() ? GroupMembershipLevelEnum::MEMBER : GroupMembershipLevelEnum::REQUESTED),
        );

        $joined = $this->repository->add($membership);

        // Purge recs cache
        $this->groupRecsAlgo->setUser($user)->purgeCache();

        return $joined;
    }

    /**
     * Leaves a group and deletes the marker
     */
    public function leaveGroup(Group $group, User $user): bool
    {
        $membership = $this->repository->get($group->getGuid(), $user->getGuid());

        // Do not allow a banned user to leave
        if ($membership->membershipLevel === GroupMembershipLevelEnum::BANNED) {
            throw new UserErrorException("You can not leave a group that you have already been banned from");
        }

        return $this->repository->delete($membership);
    }

    /**
     * Cancels a users request to join a group.
     * @return bool true on success.
     */
    public function cancelRequest(Group $group, User $user): bool
    {
        $userMembership = null;

        try {
            // TODO: Check if this check is still needed when we remove legacy writes.
            $userMembership = $this->repository->get($group->getGuid(), $user->getGuid());
        } catch(NotFoundException $e) {
            // do nothing.
        }

        if (!$userMembership || !$userMembership->isAwaiting()) {
            throw new GroupOperationException('Cannot cancel as there is no pending membership request.');
        }


        return $this->repository->delete($userMembership);
    }

    /**
     * Accepts a user into a group
     */
    public function acceptUser(Group $group, User $user, User $actor): bool
    {
        // Get the users membership
        $userMembership = $this->repository->get($group->getGuid(), $user->getGuid());

        if ($userMembership->membershipLevel !== GroupMembershipLevelEnum::REQUESTED) {
            throw new UserErrorException("User is not in the REQUESTED membership state");
        }

        // Get the Actors membership level. They must be at least a moderator
        $actorMembership = $this->repository->get($group->getGuid(), $actor->getGuid());

        if (!$actorMembership->isModerator()) {
            throw new ForbiddenException();
        }

        // Update the membership level to a member
        $userMembership->membershipLevel = GroupMembershipLevelEnum::MEMBER;

        return $this->repository->updateMembershipLevel($userMembership);
    }

    /**
     * Removes a user from a group
     */
    public function removeUser(Group $group, User $user, User $actor): bool
    {
        // Get the users membership
        $userMembership = $this->repository->get($group->getGuid(), $user->getGuid());

        // Get the Actors membership level. They must be at least a moderator
        $actorMembership = $this->repository->get($group->getGuid(), $actor->getGuid());

        if (!$actorMembership->isModerator()) {
            throw new ForbiddenException();
        }

        return $this->repository->delete($userMembership);
    }

    /**
     * Bans a user from a group
     */
    public function banUser(Group $group, User $user, User $actor): bool
    {
        // Get the users membership
        $userMembership = $this->repository->get($group->getGuid(), $user->getGuid());

        // Get the Actors membership level. They must be at least a moderator
        $actorMembership = $this->repository->get($group->getGuid(), $actor->getGuid());

        if (!$actorMembership->isModerator()) {
            throw new ForbiddenException();
        }

        // Set to banned
        $userMembership->membershipLevel = GroupMembershipLevelEnum::BANNED;

        return $this->repository->updateMembershipLevel($userMembership);
    }

    /**
     * Removes the ban, resets the user back to a member
     */
    public function unbanUser(Group $group, User $user, User $actor): bool
    {
        // Get the users membership
        $userMembership = $this->repository->get($group->getGuid(), $user->getGuid());

        if ($userMembership->membershipLevel !== GroupMembershipLevelEnum::BANNED) {
            throw new UserErrorException("User is not banned");
        }

        // Get the Actors membership level. They must be at least a moderator
        $actorMembership = $this->repository->get($group->getGuid(), $actor->getGuid());

        if (!$actorMembership->isModerator()) {
            throw new ForbiddenException();
        }

        // Set to banned
        $userMembership->membershipLevel = GroupMembershipLevelEnum::MEMBER;

        return $this->repository->updateMembershipLevel($userMembership);
    }

    /**
     * Returns whether the user is banned from the group
     */
    public function isBanned(User $user, Group $group): bool
    {
        $userMembership = $this->repository->get($group->getGuid(), $user->getGuid());

        if ($userMembership) {
            return $userMembership->membershipLevel === GroupMembershipLevelEnum::BANNED;
        } else {
            return false;
        }

    }

    /**
     * Helper function to build a user entity
     */
    private function buildUser(int $userGuid): ?User
    {
        $user = $this->entitiesBuilder->single($userGuid);

        if (!$user instanceof User || !$this->acl->read($user)) {
            return null;
        }

        return $user;
    }

    /**
     * Helper function to build a group entity
     */
    private function buildGroup(int $groupGuid): ?Group
    {
        $group = $this->entitiesBuilder->single($groupGuid);

        if (!$group instanceof Group || !$this->acl->read($group)) {
            return null;
        }

        return $group;
    }

}
