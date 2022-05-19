<?php

namespace Minds\Core\Notifications\Push\System\Builders;

use Minds\Core\Blogs\Blog;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Entities\Entity;
use Minds\Entities\Image;

/**
 * Interface for a push notification builder.
 */
interface EntityPushNotificationBuilderInterface
{
    /**
     * Build push notification.
     * @param Entity|Blog|Image $entity - entity to build for.
     * @return CustomPushNotification - returns custom push notification with relevant fields populated.
     */
    public function build(Entity|Blog|Image  $entity): CustomPushNotification;
}
