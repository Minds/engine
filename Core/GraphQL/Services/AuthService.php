<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Services;

use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

class AuthService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly ActiveSession $activeSession
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isLogged(): bool
    {
        return (bool) $this->activeSession->getUser();
    }

    /**
     * @inheritDoc
     */
    public function getUser(): ?User
    {
        return $this->activeSession->getUser();
    }
}
