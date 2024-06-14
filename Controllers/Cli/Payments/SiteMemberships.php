<?php
declare(strict_types=1);

namespace Minds\Controllers\Cli\Payments;

use Minds\Core\Di\Di;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Cli\Controller;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipsRenewalsService;
use Minds\Exceptions\ServerErrorException;
use Minds\Interfaces\CliControllerInterface;
use Spec\Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsServiceSpec;
use Stripe\Exception\ApiErrorException;

class SiteMemberships extends Controller implements CliControllerInterface
{
    public function __construct(
        private ?SiteMembershipSubscriptionsServiceSpec $siteMembershipSubscriptionsService = null,
        private ?GroupMembershipService $groupMembershipService = null,
    ) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->siteMembershipSubscriptionsService ??= Di::_()->get(SiteMembershipSubscriptionsService::class);
        $this->groupMembershipService ??= Di::_()->get(GroupMembershipService::class);
    }

    public function exec()
    {
        $this->help();
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: SiteMemberships [run]');
    }


    /**
     * @return void
     * @throws ServerErrorException
     * @throws ApiErrorException
     */
    public function sync(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        /**
         * @var SiteMembershipsRenewalsService $siteMembershipsRenewalsService
         */
        $siteMembershipsRenewalsService = Di::_()->get(SiteMembershipsRenewalsService::class);

        $siteMembershipsRenewalsService->syncSiteMemberships();
    }

    public function cleanupExpiredGroupMemberships()
    {
        $this->groupMembershipService->cleanupExpiredGroupMemberships();
    }
}
