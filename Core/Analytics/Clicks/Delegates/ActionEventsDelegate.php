<?php
declare(strict_types=1);

namespace Minds\Core\Analytics\Clicks\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;

/**
 * Handles dispatching action events.
 */
class ActionEventsDelegate
{
    public function __construct(
        private ?ActionEventsTopic $actionEventsTopic = null
    ) {
        $this->actionEventsTopic ??= Di::_()->get('EventStreams\Topics\ActionEventsTopic');
    }

    /**
     * Called on click - fires off action event.
     * @param EntityInterface $entity - subject entity.
     * @param User $user - triggering user.
     * @return void
     */
    public function onClick(EntityInterface $entity, ?User $user = null): void
    {
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_CLICK)
            ->setEntity($entity);

        if ($user) {
            $actionEvent->setUser($user);
        }

        $this->actionEventsTopic->send($actionEvent);
    }
}
