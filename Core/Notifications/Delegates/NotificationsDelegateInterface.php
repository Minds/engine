<?php
namespace Minds\Core\Notifications\Delegates;

use Minds\Core\Notifications\Notification;

interface NotificationsDelegateInterface
{
    /**
     * @param Notification $notification
     * @return void
     */
    public function onAdd(Notification $notification): void;
}
