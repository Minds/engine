<?php
declare(strict_types=1);

namespace Minds\Core\Config\Events;

use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipOnlyModeService;
use Minds\Core\Session;

/**
 * Config events.
 */
class Events
{
    public function __construct(
        private readonly EventsDispatcher $eventsDispatcher,
    ) {
    }

    /**
     * Register events.
     * @return void
     */
    public function register(): void
    {
        /**
         * Extend the config with tenant membership related fields.
         */
        $this->eventsDispatcher->register(
            event: 'config:extender',
            namespace: 'config',
            handler: function (Event $event): void {
                $config = $event->response() ?: [];

                if (!isset($config['tenant_id']) || $config['tenant_id'] < 1) {
                    return;
                }
        
                $user = Session::getLoggedinUser();
        
                $totalActiveMemberships = 0;
                $shouldShowMembershipGate = false;
        
                try {
                    $totalActiveMemberships = $this->getSiteMembershipRepository()->getTotalSiteMemberships() ?? 0;
                    $shouldShowMembershipGate = $this->getSiteMembershipOnlyModeService()->shouldRestrictAccess($user);
                } catch (\Exception $e) {
                    $this->getLogger()->error($e->getMessage());
                }
        
                $config['tenant']['total_active_memberships'] = $totalActiveMemberships;
                $config['tenant']['should_show_membership_gate'] = $shouldShowMembershipGate;
            
                $event->setResponse($config);
            }
        );
    }

    /**
     * Get the site membership repository.
     * @return SiteMembershipRepository
     */
    private function getSiteMembershipRepository(): SiteMembershipRepository
    {
        return Di::_()->get(SiteMembershipRepository::class);
    }

    /**
     * Get the site membership only mode service.
     * @return SiteMembershipOnlyModeService
     */
    private function getSiteMembershipOnlyModeService(): SiteMembershipOnlyModeService
    {
        return Di::_()->get(SiteMembershipOnlyModeService::class);
    }

    /**
     * Get the logger.
     * @return Logger
     */
    private function getLogger(): Logger
    {
        return Di::_()->get('Logger');
    }
}
