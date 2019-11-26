<?php

namespace Minds\Core\Boost\Handler;

use Minds\Interfaces\BoostHandlerInterface;
use Minds\Core;
use Minds\Entities;

/**
 * Content Boost handler
 */
class Content implements BoostHandlerInterface
{
    protected $handler = 'content';

    /**
     * @param mixed $entity
     * @return bool
     */
    public function validateEntity($entity)
    {
        if (!$entity || !is_object($entity)) {
            return false;
        }

        return
            $entity instanceof Entities\User ||
            $entity instanceof Entities\Video ||
            $entity instanceof Entities\Image ||
            $entity instanceof Core\Blogs\Blog ||
            $entity instanceof Entities\Group;
    }
}
