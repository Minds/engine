<?php

namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\Activity;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;

class NotificationsDelegate
{
    /** @var EventsDispatcher */
    private $eventsDispatcher;

    /** @var ActionEventsTopic */
    private $actionEventsTopic;

    /**
     * @param EventsDispatcher $eventsDispatcher
     */
    public function __construct($eventsDispatcher = null, ActionEventsTopic $actionEventsTopic = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?? Di::_()->get('EventsDispatcher');
        $this->actionEventsTopic = $actionEventsTopic ?? new ActionEventsTopic();
    }

    /**
     * On adding a new post
     * @param Activity $activity
     * @return void
     */
    public function onAdd(Activity $activity): void
    {
        if ($activity->isRemind() || $activity->isQuotedPost()) {
            $remind = $activity->getRemind();

            if ($activity->getOwnerGuid() != $remind->getOwnerGuid()) { // Don't send to self
                $this->eventsDispatcher->trigger('notification', 'remind', [
                    'to' => [$remind->getOwnerGuid()],
                    'notification_view' => 'remind',
                    'params' => [
                        'title' => $remind->getTitle() ?: $remind->getMessage(),
                        'is_quoted_post' => $activity->isQuotedPost(),
                        'message' => $activity->getMessage(),
                    ],
                    'entity' => $activity->isQuotedPost() ? $activity : $remind
                ]);
            }

            // New style events system
            $actionEvent = new ActionEvent();
            $actionEvent
                ->setUser($activity->getOwnerEntity())
                ->setEntity($remind)
                ->setAction($activity->isRemind() ? ActionEvent::ACTION_REMIND : ActionEvent::ACTION_QUOTE)
                ->setActionData([
                    ($activity->isRemind() ? 'remind_urn' : 'quote_urn') => $activity->getUrn(),
                ]);
            $this->actionEventsTopic->send($actionEvent);
        }
    }
}
