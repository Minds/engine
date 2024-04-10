<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications\Models;

use Minds\Core\Config\Config;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\PushNotificationInterface;

abstract class AbstractChatNotification implements PushNotificationInterface
{
    protected readonly ?int $chatRoomGuid;
    protected ?int $notificationRecipientGuid;
    protected readonly ?string $title;
    protected readonly ?string $body;
    protected readonly ?string $icon;
    protected ?Config $config;

    protected DeviceSubscription $deviceSubscription;

    protected function getEnvBasedUri(string $route): string
    {
        return $this->config->get('site_url') . $route;
    }

    public function setDeviceSubscription(DeviceSubscription $deviceSubscription): void
    {
        $this->deviceSubscription = $deviceSubscription;
    }

    public function setNotificationRecipient(int $userGuid): void
    {
        $this->notificationRecipientGuid = $userGuid;
    }
}
