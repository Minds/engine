<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipManagementService;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Annotations\UseInputType;

class SiteMembershipManagementController
{
    public function __construct(
        private readonly SiteMembershipManagementService $siteMembershipManagementService
    ) {
    }

    /**
     * @param SiteMembership $siteMembershipInput
     * @param User $loggedInUser
     * @return SiteMembership
     * @throws InvalidArgumentException
     * @throws ServerErrorException
     * @throws ApiErrorException
     */
    #[Mutation]
    #[Logged]
    #[Security('is_granted("ROLE_ADMIN", loggedInUser)')]
    public function siteMembership(
        SiteMembership     $siteMembershipInput,
        #[InjectUser] User $loggedInUser
    ): SiteMembership {
        return $this->siteMembershipManagementService->storeSiteMembership(
            siteMembership: $siteMembershipInput,
        );
    }

    #[Mutation]
    #[Logged]
    #[Security('is_granted("ROLE_ADMIN", loggedInUser)')]
    public function updateSiteMembership(
        #[UseInputType("SiteMembershipUpdateInput!")] SiteMembership $siteMembershipInput,
        #[InjectUser] User                                           $loggedInUser,
    ): SiteMembership {
        return $this->siteMembershipManagementService->updateSiteMembership(
            siteMembership: $siteMembershipInput,
        );
    }

    #[Mutation]
    #[Logged]
    #[Security('is_granted("ROLE_ADMIN", loggedInUser)')]
    public function archiveSiteMembership(
        string             $siteMembershipGuid,
        #[InjectUser] User $loggedInUser,
    ): bool {
        return $this->siteMembershipManagementService->archiveSiteMembership(
            siteMembershipGuid: (int)$siteMembershipGuid,
        );
    }
}
