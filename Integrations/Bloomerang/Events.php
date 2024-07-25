<?php
namespace Minds\Integrations\Bloomerang;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Entities\User;

class Events
{
    public function __construct(
        private EventsDispatcher $eventsDispatcher,
        private Config $config,
        private ?BloomerangConstituentService $service = null,
    ) {
        
    }

    public function register()
    {
        $this->eventsDispatcher->register('entity:save', 'user', [$this, 'onUserSaveFn']);
        $this->eventsDispatcher->register('site-membership:revalidate', 'all', [ $this, 'onSiteMembershipRevalidateFn' ]);
    }

    /**
     * Syncs a user based on an entity save event
     */
    public function onUserSaveFn(Event $event): void
    {
        $user = $event->getParameters()['entity'];
        $this->syncUser($user);
    }

    /**
     * Syncs a user when the 'site-membership:revalidate' hook is triggered
     */
    public function onSiteMembershipRevalidateFn(Event $event): void
    {
        $user = $event->getParameters()['user'];
        $this->syncUser($user);
    }

    protected function syncUser(User $user): void
    {
        /** @var Tenant */
        $tenant = $this->config->get('tenant');
        
        if (!$tenant->config->bloomerangApiKey) {
            return; // Bloomerang not configured, so short circuit
        }

        // Get the bloomerang service
        $service = $this->service ??= Di::_()->get(BloomerangConstituentService::class);

        // Sync the user
        $service->syncUser($user);
    }
}
