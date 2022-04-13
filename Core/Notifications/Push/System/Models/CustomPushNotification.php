<?php

namespace Minds\Core\Notifications\Push\System\Models;

use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;
use Minds\Core\Notifications\Push\PushNotificationInterface;
use Minds\Traits\MagicAttributes;

/**
 * @method self setTitle(string $title)
 * @method self setMessage(string $title)
 * @method self setLink(string $link)
 * @method self setIcon(string $icon)
 * @method self setMedia(string $media)
 * @method self setDeviceSubscription(DeviceSubscription $deviceSubscription)
 */
class CustomPushNotification implements PushNotificationInterface
{
    use MagicAttributes;

    private string $title;
    private ?string $message;
    private ?string $link;
    private ?string $icon;
    private ?string $media;
    private DeviceSubscription $deviceSubscription;

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
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon
    }

    /**
     * @return string|null
     */
    public function getMedia(): ?string
    {
        return $this->media;
    }

    /**
     * @return DeviceSubscription
     */
    public function getDeviceSubscription(): DeviceSubscription
    {
        return $this->deviceSubscription;
    }
}
