<?php

namespace Minds\Core\Feeds\Activity;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Boost\V3\Manager as BoostManager;
use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Session;
use Minds\Entities\EntityInterface;

/**
 * Responsible to register events regarding an Activity entity on Minds
 */
class Events
{
    private ?BoostManager $boostManager = null;
    private ?ExperimentsManager $experimentsManager = null;

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
        $boostManager = $this->getBoostManager();
        $experimentsManager = $this->getExperimentsManager();
        $this->eventsDispatcher->register("acl:write:blacklist", "activity", function (Event $event) use ($boostManager, $experimentsManager): void {
            $params = $event->getParameters();

            /**
             * @type EntityInterface $entity
             */
            $entity = $params['entity'];

            $experimentsManager->setUser(Session::getLoggedinUser());

            // Stop if flag is off
            if (!$experimentsManager->isOn("epic-293-dynamic-boost")) {
                return;
            }

            if (count($boostManager->getBoosts(limit: 1, targetStatus: BoostStatus::APPROVED, entityGuid: $entity->getGuid()))) {
                $event->setResponse(true);
            }
        });
    }

    private function getBoostManager(): BoostManager
    {
        return $this->boostManager ??= Di::_()->get(BoostManager::class);
    }

    private function getExperimentsManager(): ExperimentsManager
    {
        return $this->experimentsManager ??= new ExperimentsManager();
    }
}
