<?php

namespace Minds\Core\Notifications\Push\System\Builders;

use Minds\Core\Blogs\Blog;
use Minds\Core\Notifications\Push\System\Models\CustomPushNotification;
use Minds\Entities\Entity;
use Minds\Entities\Image;
use Minds\Entities\Video;

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
    public function build(): CustomPushNotification;

    /**
     * Create new cloned instance with class-level variable entity.
     * @param Entity|Blog|Image|Video $entity - entity to construct new instance with.
     * @return static - cloned instance of $this.
     */
    public function withEntity(Entity|Blog|Image|Video $entity): self;
}
