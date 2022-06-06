<?php
namespace Minds\Core\EventStreams;

use Minds\Core\Notifications\Notification;

class NotificationEvent implements EventInterface
{
    use TimebasedEventTrait;

    /** @var Notification */
    protected $notification;

    /**
     * @param Notification $notification
     * @return self
     */
    public function setNotification(Notification $notification): self
    {
        $this->notification = $notification;
        return $this;
    }

    /**
     * @return Notification
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }
}
