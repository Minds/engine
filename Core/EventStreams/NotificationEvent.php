<?php
namespace Minds\Core\EventStreams;

use Minds\Core\Notifications\Notification;

class NotificationEvent implements EventInterface
{
    /** @var Notification */
    protected $notification;

    /** @var int */
    protected $timestamp;

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

    /**
     * The event timestamp
     * @param int $timestamp
     * @return self
     */
    public function setTimestamp(int $timestamp): EventInterface
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}
