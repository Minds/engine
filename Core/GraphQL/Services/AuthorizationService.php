<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL\Services;

use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    /**
     * @inheritDoc
     */
    public function isAllowed(string $right, mixed $subject = null): bool
    {
        return match ($right) {
            'ROLE_ADMIN' => $subject instanceof User && $subject->isAdmin(),
            default => false,
        };
    }
}
