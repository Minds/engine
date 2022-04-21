<?php

namespace Minds\Core\Notifications\Push;

use Minds\Core\Notifications\Push\DeviceSubscriptions\DeviceSubscription;

/**
 *
 */
interface PushNotificationInterface
{
    /**
     * @return string|null
     */
    public function getUserGuid(): ?string;
    
    /**
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * @return string|null
     */
    public function getBody(): ?string;

    /**
     * @return string|null
     */
    public function getUri(): ?string;

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

    /**
     * Return the grouping id of the push notification
     * NOTE: this is not the collapsing key which replaces messages
     * @return string
     */

    public function getGroup(): string;

    /**
     * @return string
     */
    public function getMergeKey(): string;

    /**
     * @return int
     */
    public function getUnreadCount(): int;
}
