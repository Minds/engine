<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipManagementService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;

class SiteMembershipManagementController
{
    public function __construct(
        private readonly SiteMembershipManagementService $siteMembershipManagementService
    )
    {
    }

    /**
     * @param SiteMembership $siteMembershipInput
     * @param User $loggedInUser
     * @return SiteMembership
     * @throws ServerErrorException
     * @throws InvalidArgumentException
     */
    #[Mutation]
    #[Logged]
    #[Security('is_granted("ROLE_ADMIN", loggedInUser)')]
    public function siteMembership(
        SiteMembership     $siteMembershipInput,
        #[InjectUser] User $loggedInUser
    ): SiteMembership
    {
        return $this->siteMembershipManagementService->storeSiteMembership(
            siteMembership: $siteMembershipInput,
        );
    }
}
