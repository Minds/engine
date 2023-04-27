<?php
/**
 * TimeCreatedDelegate
 * @author juanmsolaro
 */

namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core\Feeds\Scheduled\EntityTimeCreated;
use Minds\Exceptions\AlreadyPublishedException;

class TimeCreatedDelegate
{
    public function __construct(private ?EntityTimeCreated $entityTimeCreated = null)
    {
        $this->entityTimeCreated ??= new EntityTimeCreated();
    }

    /**
     * Validates time_created date and set it to activity
     * @param $entity
     * @param string $time_created
     * @return bool
     */
    public function beforeAdd($entity, $time_created, $time_sent)
    {
        $this->entityTimeCreated->validate($entity, $time_created, $time_sent);
        return true;
    }

    /**
     * Validates time_created date and set it to activity
     * @param $entity
     * @param string $time_created
     * @return bool
     */
    public function onUpdate($entity, $time_created, $time_sent)
    {
        try {
            $this->entityTimeCreated->validate(
                entity: $entity,
                time_created: $time_created,
                time_sent: $time_sent,
                action: $this->entityTimeCreated::UPDATE_ACTION
            );
        } catch (AlreadyPublishedException $e) {
            // soft fail.
        }
        return true;
    }
}
