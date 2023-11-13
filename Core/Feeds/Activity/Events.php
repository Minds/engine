<?php

namespace Minds\Core\Feeds\Activity;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;

/**
 * Responsible to register events regarding an Activity entity on Minds
 */
class Events
{
    public function __construct(
        private ?EventsDispatcher $eventsDispatcher = null,
    ) {
        $this->eventsDispatcher ??= Di::_()->get("EventsDispatcher");
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->activityEditEvent();
    }

    /**
     * Registers event handlers related to the edit of an activity
     * @return void
     */
    private function activityEditEvent(): void
    {
        $this->eventsDispatcher->register("acl:write:blacklist", "activity", function (Event $event): void {
            $boostManager = new BoostManager();

            $params = $event->getParameters();

            /**
             * @type EntityInterface $entity
             */
            $entity = $params['entity'];
    
            if ($entity->getGuid() && count($boostManager->getBoosts(limit: 1, targetStatus: BoostStatus::APPROVED, entityGuid: $entity->getGuid()))) {
                $event->setResponse(true);
            }
        });
    }
}
