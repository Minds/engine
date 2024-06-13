<?php

namespace Minds\Controllers\Cli\Payments;

use Minds\Cli;
use Minds\Core\Di\Di;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Interfaces;

class SiteMemberships extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct(
        private ?SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService = null,
        private ?GroupMembershipService $groupMembershipService = null,
    ) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->siteMembershipSubscriptionsService ??= Di::_()->get(SiteMembershipSubscriptionsService::class);
        $this->groupMembershipService ??= Di::_()->get(GroupMembershipService::class);
    }

    public function help($command = null)
    {
        $this->out('Syntax usage: payments inapppurchases [run]');
    }

    public function exec()
    {
        $this->help();
    }

    public function cleanupExpiredGroupMemberships()
    {
        $this->groupMembershipService->cleanupExpiredGroupMemberships();
    }
}
