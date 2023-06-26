<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Services;

use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

class AuthService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly ?User $user = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isLogged(): bool
    {
        return (bool) $this->user;
    }

    /**
     * @inheritDoc
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
}
