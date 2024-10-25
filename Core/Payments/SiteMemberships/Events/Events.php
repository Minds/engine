<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Events;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipOnlyModeService;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Entities\User;

class Events
{
    public function __construct(
        private ?SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService = null,
        private ?SiteMembershipOnlyModeService $siteMembershipOnlyModeService = null,
        private ?Config $config = null
    ) {
    }

    public function register(): void
    {
        Dispatcher::register('export:extender', 'all', function ($event) {
            $params = $event->getParameters();
            $export = $event->response() ?: [];
            $user = $params['entity'];

            if (!$user instanceof User) {
                return;
            }

            if ($user->fullExport && (bool) $user->tenant_id) {
                $export['has_active_site_membership'] = $this->getSiteMembershipSubscriptionsService()
                  ->hasActiveSiteMembershipSubscription(user: $user);
            }

            $event->setResponse($export);
        });

        Dispatcher::register('acl:read:blacklist', 'all', function ($event) {
            $this->handleAclBlacklistEvent($event);
        });

        Dispatcher::register('acl:write:blacklist', 'all', function ($event) {
            $this->handleAclBlackListEvent($event);
        });
    }

    /**
     * Handles ACL blacklist event, to prevent access to the entity if the user requires
     * a site membership subscription, and does not have one.
     * @param mixed $event - the event to handle.
     * @return void
     */
    private function handleAclBlacklistEvent(mixed &$event): void
    {
        $params = $event->getParameters();
        $user = $params['user'];

        if (!$this->getConfig()->get('tenant_id')) {
            $event->setResponse(false);
            return;
        }

        if ($user && $user?->getGuid() === $params['entity']->getGuid()) {
            $event->setResponse(false);
            return;
        }

        $event->setResponse(
            $this->getSiteMembershipOnlyModeService()
                ->shouldRestrictAccess(user: $user)
        );
    }

    /**
     * Gets site membership subscription service.
     * @return SiteMembershipSubscriptionsService - Site membership subscription service.
     */
    private function getSiteMembershipSubscriptionsService(): SiteMembershipSubscriptionsService
    {
        return $this->siteMembershipSubscriptionsService ??= Di::_()->get(SiteMembershipSubscriptionsService::class);
    }

    /**
     * Gets site membership only mode service.
     * @return SiteMembershipOnlyModeService
     */
    private function getSiteMembershipOnlyModeService(): SiteMembershipOnlyModeService
    {
        return $this->siteMembershipOnlyModeService ??= Di::_()->get(SiteMembershipOnlyModeService::class);
    }

    /**
     * Gets config.
     * @return Config
     */
    private function getConfig(): Config
    {
        return $this->config ??= Di::_()->get(Config::class);
    }
}
