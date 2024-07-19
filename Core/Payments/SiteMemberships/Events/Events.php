<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Events;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Payments\SiteMemberships\Services\SiteMembershipSubscriptionsService;
use Minds\Entities\User;

class Events
{
    public function __construct(
      private ?SiteMembershipSubscriptionsService $siteMembershipSubscriptionsService = null
    ) {}

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
    }

    /**
     * Gets site membership subscription service.
     * @return SiteMembershipSubscriptionsService - Site membership subscription service.
     */
    private function getSiteMembershipSubscriptionsService(): SiteMembershipSubscriptionsService
    {
    	return $this->siteMembershipSubscriptionsService ??= Di::_()->get(SiteMembershipSubscriptionsService::class);
    }
}
