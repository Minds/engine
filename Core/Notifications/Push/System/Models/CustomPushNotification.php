<?php

namespace Minds\Core\Notifications\Push\System\Models;

use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setTitle(string $title)
 * @method self setBody(string $body)
 * @method self setUri(string $uri)
 * @method self setIcon(string $icon)
 * @method self setMedia(string $media)
 * @method self setDeviceSubscription(DeviceSubscription $deviceSubscription)
 */
class CustomPushNotification implements PushNotificationInterface
{
    use MagicAttributes;

    private string $userGuid;
    private string $title;
    private ?string $body;
    private ?string $uri;
    private ?string $icon;
    private ?string $media;
    private DeviceSubscription $deviceSubscription;

    /**
     * @return string
     */
    public function getUserGuid(): string
    {
        return $this->deviceSubscription->getUserGuid();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon ?? '';
    }

    /**
     * @return string|null
     */
    public function getMedia(): ?string
    {
        return $this->media ?? '';
    }

    /**
     * @return DeviceSubscription
     */
    public function getDeviceSubscription(): DeviceSubscription
    {
        return $this->deviceSubscription;
    }

    public function getGroup(): string
    {
        return md5($this->getTitle());
    }

    public function getMergeKey(): string
    {
        return $this->getGroup();
    }

    public function getUnreadCount(): int
    {
        return 1;
    }
}
