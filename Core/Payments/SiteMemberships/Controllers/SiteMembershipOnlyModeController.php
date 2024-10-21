<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Controllers;

use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipOnlyModeService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Controller for site membership only mode related queries.
 */
class SiteMembershipOnlyModeController
{
    public function __construct(
        private SiteMembershipOnlyModeService $siteMembershipOnlyModeService,
    ) {
    }

    /**
     * Whether the membership gate.
     * @return bool
     */
    #[Query]
    #[Logged]
    public function shouldShowMembershipGate(
        #[InjectUser] User $loggedInUser = null,
    ): bool {
        return $this->siteMembershipOnlyModeService->shouldRestrictAccess(user: $loggedInUser);
    }
}
