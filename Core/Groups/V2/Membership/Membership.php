<?php
namespace Minds\Core\Groups\V2\Membership;

use DateTime;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Entities\ExportableInterface;
use Minds\Entities\User;

class Membership implements ExportableInterface
{
    private User $user;

    public function __construct(
        public readonly int $groupGuid,
        public readonly int $userGuid,
        public readonly DateTime $createdTimestamp,
        public GroupMembershipLevelEnum $membershipLevel,
        public readonly ?int $siteMembershipGuid = null,
    ) {
    }

    /**
     * Helper function to determine if the user has the member role
     */
    public function isMember(): bool
    {
        return in_array($this->membershipLevel, [
            GroupMembershipLevelEnum::MEMBER,
            GroupMembershipLevelEnum::MODERATOR,
            GroupMembershipLevelEnum::OWNER,
        ], true);
    }

    /**
     * Helper function to determine if the user has the owner role
     */
    public function isOwner(): bool
    {
        return in_array($this->membershipLevel, [
            GroupMembershipLevelEnum::OWNER,
        ], true);
    }

    /**
     * Helper function to determine if a user is awaiting a decision
     * on their membership request.
     * @return bool
     */
    public function isAwaiting(): bool
    {
        return in_array($this->membershipLevel, [
            GroupMembershipLevelEnum::REQUESTED,
        ], true);
    }

    /**
     * Helper function to determine if the user has the moderator role
     */
    public function isModerator(): bool
    {
        return in_array($this->membershipLevel, [
            GroupMembershipLevelEnum::MODERATOR,
            GroupMembershipLevelEnum::OWNER,
        ], true);
    }

    /**
     * Helper function to determine if the user has the moderator role
     */
    public function isBanned(): bool
    {
        return $this->membershipLevel === GroupMembershipLevelEnum::BANNED;
    }

    /**
     * Sets the user entity for efficiency
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Returns the user entity (if it was set)
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function export(array $extra = []): array
    {
        $export = $this->user->export();
        $export['is:moderator'] = $this->isModerator();
        $export['is:owner'] = $this->isOwner();
        $export['is:member'] = $this->isMember();
        $export['is:awaiting'] = $this->membershipLevel === GroupMembershipLevelEnum::REQUESTED;

        return $export;
    }
}
