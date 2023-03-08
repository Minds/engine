<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Events;

use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Security\ACL;

/**
 * Boost V3 events.
 */
class Events
{
    public function __construct(
        private ?EventsDispatcher $eventsDispatcher = null,
        private ?ACL $acl = null
    ) {
        $this->eventsDispatcher ??= Di::_()->get('EventsDispatcher');
        $this->acl ??= Di::_()->get('Security\ACL');
    }

    /**
     * Register boost events.
     * @return void
     */
    public function register(): void
    {
        // ACL read hook that checks boosted entity read access for given user.
        $this->eventsDispatcher->register('acl:read', 'boost', function (Event $event) {
            $params = $event->getParameters();
            $boost = $params['entity'];

            $entity = $boost->getEntity();
            $user = $params['user'];

            if (!$entity) {
                $event->setResponse(true);
                return;
            }

            $canRead = $this->acl->read($entity, $user);
            $event->setResponse($canRead);
        });
    }
}
