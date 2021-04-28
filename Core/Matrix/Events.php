<?php
namespace Minds\Core\Matrix;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;

class Events
{
    /** @var EventsDispatcher */
    protected $eventsDispatcher;

    /** @var Manager */
    protected $manager;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(EventsDispatcher $eventsDispatcher = null, Manager $manager = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?? Di::_()->get('EventsDispatcher');
        $this->manager = $manager ?? Di::_()->get('Matrix\Manager');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    public function register()
    {
        // Background event when an auth code is issued
        $this->eventsDispatcher->register('OAuth\Background', 'authorize', function (Event $event) {
            $params = $event->getParameters();

            $userGuid = $params['user_guid'];
            $clientId = $params['client_id'];

            if ($clientId !== 'matrix') {
                return;
            }

            $user = $this->entitiesBuilder->single($userGuid);

            if (!$user instanceof User) {
                return;
            }

            // Sync the avatar
            $this->manager->syncAccount($user);

            // Auto invite to support room
            $support = $this->entitiesBuilder->getByUserByIndex('support'); // TODO: make this a config
            $this->manager->createDirectRoom($user, $support);
        });
    }
}
