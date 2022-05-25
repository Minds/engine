<?php

namespace Minds\Core\Notifications\Push\System\Models;

use Minds\Traits\MagicAttributes;

/**
 * @method int getTotalNotifications()
 * @method int getSuccessfulNotifications()
 * @method int getFailedNotifications()
 * @method int getSkippedNotifications()
 */
class AdminPushNotificationRequestCounters
{
    use MagicAttributes;
    
    private int $totalNotifications = 0;
    private int $successfulNotifications = 0;
    private int $failedNotifications = 0;
    private int $skippedNotifications = 0;

    public function increaseTotalNotifications(): self
    {
        $this->totalNotifications++;
        return $this;
    }

    public function increaseSuccessfulNotifications(): self
    {
        $this->successfulNotifications++;
        return $this;
    }

    public function increaseFailedNotifications(): self
    {
        $this->failedNotifications++;
        return $this;
    }

    public function increaseSkippedNotifications(): self
    {
        $this->skippedNotifications++;
        return $this;
    }
}
