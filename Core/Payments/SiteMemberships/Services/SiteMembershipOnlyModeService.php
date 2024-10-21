<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Entities\User;

/**
 * Service for site membership only mode.
 */
class SiteMembershipOnlyModeService
{
    public function __construct(
        private readonly SiteMembershipRepository $siteMembershipRepository,
        private readonly SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService,
        private readonly RolesService $rolesService,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /**
     * Check if access should be restricted for a given user.
     * @param User|null $user - the user to check for.
     * @param bool|null $hasActiveMemberships - whether there are active memberships on the network.
     * If not provided, it will be fetched from the repository.
     * @return bool whether access should be restricted.
     */
    public function shouldRestrictAccess(
        User $user = null,
        bool $hasActiveMemberships = null,
    ): bool {
        $tenantConfig = $this->config->get('tenant');

        if (!$user || !$tenantConfig || !$tenantConfig->config?->membersOnlyModeEnabled) {
            return false;
        }

        try {
            if ($hasActiveMemberships === null) {
                $hasActiveMemberships = ($this->siteMembershipRepository->getTotalSiteMemberships() ?? 0) > 0;
            }

            return $hasActiveMemberships &&
                !$this->shouldBypass($user) &&
                !$this->siteMembershipSubscriptionsService->hasActiveSiteMembershipSubscription(user: $user);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * Check whether the current user should bypass membership-only mode.
     * @return bool true if membership-only mode should be bypassed.
     */
    private function shouldBypass(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        try {
            $userRoles = $this->rolesService->getRoles($user);

            foreach ($userRoles as $role) {
                if (in_array($role->id, [RolesEnum::OWNER->value, RolesEnum::ADMIN->value, RolesEnum::MODERATOR->value], true)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }
}
