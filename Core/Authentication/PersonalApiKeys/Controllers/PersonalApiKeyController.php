<?php
namespace Minds\Core\Authentication\PersonalApiKeys\Controllers;

use Minds\Core\Authentication\PersonalApiKeys\Enums\PersonalApiKeyScopeEnum;
use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Authentication\PersonalApiKeys\Services\PersonalApiKeyManagementService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class PersonalApiKeyController
{
    public function __construct(
        private readonly PersonalApiKeyManagementService $managementService,
    ) {
    }

    /**
     * @return PersonalApiKey[]
     */
    #[Query]
    #[Logged]
    public function listPersonalApiKeys(
        #[InjectUser] User $loggedInUser,
    ): array
    {
        return $this->managementService->getList(user: $loggedInUser);
    }

    #[Query]
    #[Logged]
    public function getPersonalApiKey(
        string $id,
        #[InjectUser] User $loggedInUser,
    ): ?PersonalApiKey
    {
        return $this->managementService->getById(id: $id, user: $loggedInUser);
    }


    /**
     * @param PersonalApiKeyScopeEnum[] $scopes
     */
    #[Mutation]
    #[Logged]
    public function createPersonalApiKey(
        string $name,
        array $scopes,
        int $expireInDays = null,
        #[InjectUser] User $loggedInUser,
    ): PersonalApiKey {
        return $this->managementService->create(
            user: $loggedInUser,
            name: $name,
            scopes: $scopes,
            expireInDays: $expireInDays, // Optional
        );
    }

    #[Mutation]
    #[Logged]
    public function deletePersonalApiKey(
        string $id,
        #[InjectUser] User $loggedInUser,
    ): bool
    {
        return $this->managementService->deleteById($id, $loggedInUser);
    }

}
