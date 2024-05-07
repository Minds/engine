<?php
namespace Minds\Core\Authentication\PersonalApiKeys\Services;

use DateTime;
use DateTimeImmutable;
use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Authentication\PersonalApiKeys\Repositories\PersonalApiKeyRepository;
use Minds\Core\Router\Enums\ApiScopeEnum;
use Minds\Entities\User;

class PersonalApiKeyManagementService
{
    public function __construct(
        private PersonalApiKeyRepository $repository,
        private PersonalApiKeyHashingService $hashingService,
    ) {
    }
    
    /**
     * @return PersonalApiKey[]
     */
    public function getList(
        User $user,
    ): array
    {
        return $this->repository->getList($user->getGuid());
    }

    /**
     * Creates a new Personal Api Key.
     * The secret will only ever be returned once from this function. We do not store the secret,
     * only a hash which we check against. 
     */
    public function create(
        User $user,
        string $name,
        /** @var ApiScopeEnum[] */
        array $scopes,
        int $expireInDays = null,
    ): PersonalApiKey {
        $secret = $this->hashingService->generateSecret();

        $personalApiKey = new PersonalApiKey(
            id: md5(openssl_random_pseudo_bytes(128)), // Yes, md5 is fine.. this is a public id only
            ownerGuid: $user->getGuid(),
            secretHash: $this->hashingService->hashSecret($secret),
            name: $name,
            scopes: $scopes,
            timeCreated: new DateTimeImmutable('now'),
            timeExpires: $expireInDays ? new DateTimeImmutable("+$expireInDays day") : null,
        );

        // The only time this will be exposed
        $personalApiKey->secret = $secret;

        $this->repository->add($personalApiKey);

        return $personalApiKey;
    }

    /**
     * Returns a single api key
     */
    public function getById(string $id, User $user): ?PersonalApiKey
    {
        return $this->repository->getById($id, $user->getGuid());
    }

    /**
     * Deletes a personal api key
     */
    public function deleteById(string $id, User $user): bool
    {
        return $this->repository->delete($id, $user->getGuid());
    }
}
