<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Entities\User;

/**
 * Service for site membership only mode.
 */
class SiteMembershipOnlyModeService
{
    public function __construct(
        private readonly SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService,
        private readonly RolesService $rolesService,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /**
     * Check if access should be restricted for a given user.
     * @param User|null $user - the user to check for.
     * @return bool whether access should be restricted.
     */
    public function shouldRestrictAccess(
        User $user = null
    ): bool {
        $tenantConfig = $this->config->get('tenant');
        $membersOnlyModeEnabled = $tenantConfig?->config?->membersOnlyModeEnabled;

        if (!$membersOnlyModeEnabled) {
            return false;
        }

        if (!$user) {
            return true;
        }

        try {
            if ($this->shouldBypass($user)) {
                return false;
            }

            return !$this->hasActiveSiteMembershipSubscription($user);
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
        return $user->isAdmin() || $this->rolesService->hasPermission($user, PermissionsEnum::CAN_MODERATE_CONTENT);
    }

    /**
     * Whether the user has an active site membership subscription.
     * @param User $user - the user to check for.
     * @return bool whether the user has an active site membership subscription.
     */
    private function hasActiveSiteMembershipSubscription(User $user): bool
    {
        return isset($user->membership_subscriptions_count) && is_numeric($user->membership_subscriptions_count) ?
            $user->membership_subscriptions_count > 0 :
            $this->siteMembershipSubscriptionsService->hasActiveSiteMembershipSubscription(user: $user);
    }
}
