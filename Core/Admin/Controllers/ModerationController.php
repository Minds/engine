<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Controllers;

use Minds\Core\Admin\Services\ModerationService;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Right;

/**
 * GraphQL Controller to handle various moderation actions.
 */
class ModerationController
{
    public function __construct(
        private readonly ModerationService $service
    ) {
    }

    /**
     * Ban or unban a given user.
     * @param string $subjectGuid - the guid of the user to ban or unban.
     * @param bool $banState - desired resulting user ban state.
     * @return bool true on success.
     */
    #[Mutation]
    #[Logged]
    #[Right('PERMISSION_CAN_MODERATE_CONTENT')]
    public function setUserBanState(
        string $subjectGuid,
        bool $banState
    ): bool {
        return $this->service->setUserBanState($subjectGuid, $banState);
    }

    /**
     * Delete an entity.
     * @param string $entityUrn - the URN of the entity to delete.
     * @return bool true on success.
     */
    #[Mutation]
    #[Logged]
    #[Right('PERMISSION_CAN_MODERATE_CONTENT')]
    public function deleteEntity(
        string $subjectUrn,
    ): bool {
        return $this->service->deleteEntity($subjectUrn);
    }
}
