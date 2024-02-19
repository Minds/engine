<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\PostSubscriptions\Helpers\Interfaces;

use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use Minds\Entities\Entity;

/**
 * Interface for the post notification dispatch process helper class.
 */
interface PostNotificationDispatchHelperInterface
{
    /**
     * Whether the post notification can be dispatched.
     * @param PostSubscription $postSubscription - post subscription to check.
     * @param Entity $forActivity - subject entity to check.
     * @return bool true if notification should be sent.
     */
    public function canDispatch(PostSubscription $postSubscription, Entity $forActivity): bool;
}
