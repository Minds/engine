<?php
/**
 * TimeCreatedDelegate
 * @author juanmsolaro
 */

namespace Minds\Core\Blogs\Delegates;

use Minds\Core\Feeds\Scheduled\EntityTimeCreated;

class TimeCreatedDelegate
{
    /** @var Core\Feeds\Scheduled\EntityTimeCreated $entityTimeCreated */
    protected $entityTimeCreated;

    /**
     * TimeCreatedDelegate constructor.
     * @param Save $save
     */
    public function __construct()
    {
        $this->entityTimeCreated = new EntityTimeCreated();
    }

    /**
     * Validates time_created date and sets it to activity
     * @param $entity
     * @param string $time_created
     * @return bool
     */
    public function onAdd($entity, $time_created, $time_sent)
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
        $this->entityTimeCreated->validate($entity, $time_created, $time_sent);
        return true;
    }
}
