<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Delegates;

use Minds\Core\Email\V2\Campaigns\Recurring\MobileAppPreviewReady\MobileAppPreviewReadyEmailer;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Services\RolesService;

/**
 * Mobile app preview ready email delegate.
 */
class MobileAppPreviewReadyEmailDelegate
{
    public function __construct(
        private readonly MobileAppPreviewReadyEmailer $mobileAppPreviewReadyEmailer,
        private readonly RolesService $rolesService,
        private readonly Logger $logger
    ) {
    }

    /**
     * Sends an email for when the mobile app preview is ready to all network
     * owners and admins.
     * @return void
     */
    public function onMobileAppPreviewReady(): void
    {
        $ownerRoleEdges = iterator_to_array($this->rolesService->getUsersByRole(
            roleId: RolesEnum::OWNER->value,
            limit: 12
        )) ?? [];

        $adminRoleEdges = iterator_to_array($this->rolesService->getUsersByRole(
            roleId: RolesEnum::ADMIN->value,
            limit: 12
        )) ?? [];

        $uniqueUserRoleEdges = array_values(array_unique(
            array_merge($ownerRoleEdges, $adminRoleEdges),
            SORT_REGULAR
        ));

        foreach ($uniqueUserRoleEdges as $userRoleEdge) {
            try {
                $this->mobileAppPreviewReadyEmailer->setUser($userRoleEdge->getUser())
                    ->queue();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
