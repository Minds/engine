<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Services;

use Minds\Core\Comments\Manager as CommentManager;
use Minds\Core\Channels\Ban as ChannelsBanManager;
use Minds\Core\Comments\Comment;
use Minds\Core\Entities\Actions\Delete as DeleteAction;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;
use Minds\Exceptions\UserErrorException;

/**
 * Service for the handling of moderation actions.
 */
class ModerationService
{
    public function __construct(
        private readonly RolesService $rolesService,
        private readonly ChannelsBanManager $channelsBanManager,
        private readonly DeleteAction $deleteAction,
        private readonly CommentManager $commentManager,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly EntitiesResolver $entitiesResolver
    ) {
    }

    /**
     * Ban a user.
     * @param string $subjectGuid - GUID of the user to ban.
     * @throws UserErrorException on issue with input data.
     * @return bool true on success.
     */
    public function banUser(string $subjectGuid): bool
    {
        $subject = $this->buildUser($subjectGuid);
        
        if (!$subject) {
            throw new UserErrorException('No subject found for the given GUID');
        }

        if (!$this->canBeModerated($subject)) {
            throw new UserErrorException('You do not have permission to moderate against this user');
        }

        $this->channelsBanManager
            ->setUser($subject)
            ->ban('11'); // "Another Reason"

        return true;
    }

    /**
     * Delete an entity.
     * @param string $entityUrn - URN of the entity to delete.
     * @throws UserErrorException on issue with input data.
     * @return bool true on success.
     */
    public function deleteEntity(string $entityUrn): bool
    {
        $entity = $this->entitiesResolver->single($entityUrn);

        if (!$entity) {
            throw new UserErrorException('No entity found for the given GUID');
        }

        if ($entity instanceof User) {
            throw new UserErrorException('User deletion is not supported from this service');
        }

        $entityOwner = $entity->getOwnerGuid() ? $this->entitiesBuilder->single($entity->getOwnerGuid()) : null;

        if (!$entityOwner || !$entityOwner instanceof User) {
            throw new UserErrorException('No valid owner found for the given entity');
        }

        if (!$this->canBeModerated($entityOwner)) {
            throw new UserErrorException('You do not have permission to moderate content from the owner of this entity');
        }

        if ($entity instanceof Comment) {
            $this->commentManager->delete($entity);
        } else {
            $this->deleteAction->setEntity($entity)->delete();
        }

        return true;
    }

    /**
     * Whether a user can be, or have their content moderated.
     * @param User $subject - the user to check.
     * @return bool true if the user can be moderated.
     */
    private function canBeModerated(User $subject): bool
    {
        // admins cannot have their content moderated.
        if ($subject->isAdmin()) {
            return false;
        }
        
        // users who can moderate, cannot have their content moderated.
        if ($this->rolesService->hasPermission($subject, PermissionsEnum::CAN_MODERATE_CONTENT)) {
            return false;
        }

        return true;
    }

    /**
     * Build a user from a GUID.
     * @param string $userGuid - GUID of the user to build.
     * @return User|null the user, or null if not found.
     */
    private function buildUser(string $userGuid): ?User
    {
        $entity = $this->entitiesBuilder->single($userGuid);
        return $entity instanceof User ? $entity : null;
    }
}
