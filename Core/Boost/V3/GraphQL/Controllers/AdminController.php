<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\GraphQL\Controllers;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Right;

/**
 * Boosts Admin GraphQL Controller.
 */
class AdminController
{
    public function __construct(
        private readonly BoostManager $boostManager
    ) {
    }

    /**
     * Cancel all Boosts on a given entity.
     * @param string $guid - the entity GUID for which to cancel all Boosts.
     * @return bool - true if the Boosts were successfully cancelled.
     */
    #[Mutation]
    #[Logged]
    #[Right('PERMISSION_CAN_MODERATE_CONTENT')]
    public function cancelBoosts(
        string $entityGuid,
        #[InjectUser] User $loggedInUser = null // Do not add in docblock as it will break GraphQL
    ): bool {
        return $this->boostManager->cancelByEntityGuid(
            entityGuid: (string) $entityGuid,
            statuses: [BoostStatus::APPROVED, BoostStatus::PENDING]
        );
    }
}
