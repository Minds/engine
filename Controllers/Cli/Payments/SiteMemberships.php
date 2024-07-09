<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\Payments;

use Minds\Core\Di\Di;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Cli\Controller;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipsRenewalsService;
use Minds\Exceptions\ServerErrorException;
use Minds\Interfaces\CliControllerInterface;
use Stripe\Exception\ApiErrorException;

class SiteMemberships extends Controller implements CliControllerInterface
{
    public function __construct(
        private ?SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService = null,
        private ?GroupMembershipService $groupMembershipService = null,
        private ?MultiTenantBootService $multiTenantBootService = null,
        private ?MultiTenantDataService $multiTenantDataService = null,
    ) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->siteMembershipSubscriptionsService ??= Di::_()->get(SiteMembershipSubscriptionsService::class);
        $this->groupMembershipService ??= Di::_()->get(GroupMembershipService::class);
        $this->multiTenantBootService ??= Di::_()->get(MultiTenantBootService::class);
        $this->multiTenantDataService ??= Di::_()->get(MultiTenantDataService::class);
    }

    public function exec()
    {
        $this->help();
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: cli.php payments SiteMemberships [run]');
    }


    /**
     * Sync site memberships state with Stripe.
     *
     * Example usage:
     * ```
     * php cli.php payments SiteMemberships sync --tenant_id=123
     * ```
     * @return void
     * @throws ServerErrorException
     * @throws ApiErrorException
     */
    public function sync(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $tenantId = $this->getOpt('tenant_id') ? (int) $this->getOpt('tenant_id') : null;

        if ($tenantId) {
            $this->multiTenantBootService->bootFromTenantId($tenantId);
        }

        /**
         * @var SiteMembershipsRenewalsService $siteMembershipsRenewalsService
         */
        $siteMembershipsRenewalsService = Di::_()->get(SiteMembershipsRenewalsService::class);
        $siteMembershipsRenewalsService->syncSiteMemberships($tenantId);
    }

    /**
     * Keep the site membership group assignments in sync
     */
    public function syncGroupMemberships()
    {
        // Remove expired
        $this->groupMembershipService->cleanupExpiredGroupMemberships();
        // Join any groups thats users should be in
        foreach ($this->multiTenantDataService->getTenants(limit: 9999999) as $tenant) {
            $this->multiTenantBootService->bootFromTenantId($tenant->id);
            $this->siteMembershipSubscriptionsService->syncOutOfSyncSiteMemberships();
        }
    }
}
