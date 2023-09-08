<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Controllers;

use Minds\Core\Admin\Manager;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;

class AccountsController
{
    public function __construct(
        private readonly Manager $manager
    ) {
    }

    /**
     * @param string $currentUsername
     * @param string|null $newUsername
     * @param string|null $newEmail
     * @param bool $resetMFA
     * @return array<string>
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function updateAccount(
        string $currentUsername,
        ?string $newUsername = null,
        ?string $newEmail = null,
        bool $resetMFA = false,
        #[InjectUser] ?User $loggedInUser = null,
    ): array {
        $this->manager->updateAccount(
            currentUsername: $currentUsername,
            newUsername: $newUsername,
            newEmail: $newEmail,
            resetMFA: $resetMFA,
        );

        return [];
    }
}
