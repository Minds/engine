<?php
namespace Minds\Core\Authentication\PersonalApiKeys\Services;

use DateTimeImmutable;
use Minds\Core\Authentication\PersonalApiKeys\PersonalApiKey;
use Minds\Core\Authentication\PersonalApiKeys\Repositories\PersonalApiKeyRepository;

class PersonalApiKeyAuthService
{
    //const API_KEY_HEADER = 'X-'

    public function __construct(
        private PersonalApiKeyRepository $repository,
        private PersonalApiKeyHashingService $hashingService
    ) {
        
    }

    /**
     * Returns a Personal Api Key from the client secret
     */
    public function getKeyBySecret(string $secret): ?PersonalApiKey
    {
        $secretHash = $this->hashingService->hashSecret($secret);

        return $this->repository->getBySecretHash($secretHash);
    }

    /**
     * Validate that the personal api is valid
     */
    public function validateKey(PersonalApiKey $personalApiKey): bool
    {
        return !$personalApiKey->timeExpires || $personalApiKey->timeExpires > new DateTimeImmutable('now');
    }
}
