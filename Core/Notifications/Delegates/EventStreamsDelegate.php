<?php
/**
 *
 */
namespace Minds\Core\Notifications\Delegates;

use Minds\Core\EventStreams\NotificationEvent;
use Minds\Core\EventStreams\Topics\NotificationsTopic;
use Minds\Core\Notifications\Notification;

class EventStreamsDelegate implements NotificationsDelegateInterface
{
    /** @var NotificationsTopic */
    protected $notificationsTopic;

    public function __construct(NotificationsTopic $notificationsTopic = null)
    {
        $this->notificationsTopic = $notificationsTopic;
    }

    /**
     * @param Notification $notification
     * @return void
     */
    public function onAdd(Notification $notification): void
    {
        $notificationEvent = new NotificationEvent();
        $notificationEvent->setNotification($notification);
        $this->getNotificationsTopic()->send($notificationEvent);
    }

    /**
     * @return NotificationsTopic
     */
    protected function getNotificationsTopic(): NotificationsTopic
    {
        if (!$this->notificationsTopic) {
            $this->notificationsTopic = new NotificationsTopic();
        }
        return $this->notificationsTopic;
    }
}
