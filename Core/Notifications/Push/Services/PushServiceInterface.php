<?php
namespace Minds\Core\Notifications\Push\Services;

use Minds\Core\Notifications\Push\PushNotification;

interface PushServiceInterface
{
    public function send(PushNotification $pushNotification): bool;
}
