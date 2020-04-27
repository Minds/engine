<?php

namespace Minds\Core\Feeds\Scheduled;

class EntityTimeCreated
{
    /**
     * EntityTimeCreated constructor.
     *
     */
    public function __construct()
    {
    }

    public function validate($entity, $time_created, $time_sent)
    {
        if ($time_created > strtotime('+3 Months')) {
            throw new \InvalidParameterException();
        }

        if ($time_created < strtotime('+2 Minutes')) {
            $time_created = $time_sent;
        }

        $entity->setTimeCreated($time_created);
        $entity->setTimeSent($time_sent);
    }
}
