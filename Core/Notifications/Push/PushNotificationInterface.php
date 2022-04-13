<?php

namespace Minds\Core\Notifications\Push;

use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;

interface PushNotificationInterface
{
    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @return string|null
     */
    public function getLink(): ?string;

    /**
     * @return string|null
     */
    public function getIcon(): ?string;

    /**
     * @return string|null
     */
    public function getMedia(): ?string;

    /**
     * @return DeviceSubscription
     */
    public function getDeviceSubscription(): DeviceSubscription;
}
