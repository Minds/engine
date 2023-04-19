<?php

namespace Minds\Core\Feeds\Scheduled;

use Minds\Entities\Entity;
use Minds\Exceptions\AlreadyPublishedException;
use Minds\Exceptions\UserErrorException;

class EntityTimeCreated
{
    /** passed to validation function to indicate that it is to validate a create operation */
    const CREATE_ACTION = 1;

    /** passed to validation function to indicate that it is to validate an update operation */
    const UPDATE_ACTION = 2;

    /**
     * EntityTimeCreated constructor.
     *
     */
    public function __construct()
    {
    }

    /**
     * Validate an entities time created and set it on the entity if valid.
     * @param mixed $entity - entity.
     * @param int $time_created - the time created from the post.
     * @param int $time_sent - time the update was sent.
     * @param int $action - create or update action - defaults to create.
     * @throws UserErrorException when too far in the future.
     * @throws AlreadyPublishedException - when post is already published.
     * @return void
     */
    public function validate(
        mixed $entity,
        int $time_created,
        int $time_sent,
        int $action = self::CREATE_ACTION,
    ): void {
        if ($time_created > strtotime('+3 Months')) {
            throw new UserErrorException(
                'Time is too far in the future',
                400
            );
        }

        if ($action === self::UPDATE_ACTION && !$this->isScheduled($entity)) {
            throw new AlreadyPublishedException(
                'Cannot update the timestamp of a published post',
                400
            );
        }

        if ($time_created < strtotime('+2 Minutes')) {
            $time_created = $time_sent;
        }

        $entity->setTimeCreated($time_created);
        $entity->setTimeSent($time_sent);
    }

    /**
     * Check whether entity is scheduled.
     * @param Entity $entity - entity to check.
     * @return boolean true if entity is scheduled.
     */
    private function isScheduled(Entity $entity): bool
    {
        return $entity->getTimeCreated() > time();
    }
}
