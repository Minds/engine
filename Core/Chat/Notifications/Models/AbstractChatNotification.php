<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications\Models;

use Minds\Core\Config\Config;
use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\PushNotificationInterface;

abstract class AbstractChatNotification implements PushNotificationInterface
{
    protected ?int $notificationRecipientGuid;
    protected ?Config $config;

    protected DeviceSubscription $deviceSubscription;

    public function __construct(
        protected readonly ?int $chatRoomGuid,
        public ?string $title,
        protected readonly ?string $body,
        protected readonly ?string $icon
    ) {
    }

    protected function getEnvBasedUri(string $route): string
    {
        $queryParams = http_build_query([
            'utm_source' => 'minds',
            'utm_medium' => 'push-notification',
            'utm_content' => 'chat',
        ]);
        return $this->config->get('site_url') . $route . '?' . $queryParams;
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
