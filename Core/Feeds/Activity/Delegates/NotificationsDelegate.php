<?php

namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\Activity;
use Minds\Core\Di\Di;

class NotificationsDelegate
{
    /** @var EventsDispatcher */
    private $eventsDispatcher;

    /**
     * @param EventsDispatcher $eventsDispatcher
     */
    public function __construct($eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?? Di::_()->get('EventsDispatcher');
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
        }
    }
}
