<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Factories;

use Minds\Core\Guid;
use Minds\Core\MultiTenant\Types\NetworkUser;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Factory;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;

class NetworkUserFactory
{
    #[Factory(name: 'NetworkUserInput')]
    public function createTenant(
        ?string $username = null,
        ?int $tenantId = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): NetworkUser {
        return new NetworkUser(
            guid: (int) Guid::build(),
            username: $username,
            tenantId: $tenantId,
            plainPassword: openssl_random_pseudo_bytes(128)
        );
    }
}
