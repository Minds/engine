<?php

namespace Minds\Core\Boost\Handler;

use Minds\Interfaces\BoostHandlerInterface;
use Minds\Core;
use Minds\Entities;

/**
 * Newsfeed Boost handler
 */
class Newsfeed implements BoostHandlerInterface
{
    protected $handler = 'newsfeed';

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
            $entity instanceof Entities\Activity ||
            $entity instanceof Entities\Video ||
            $entity instanceof Entities\Image ||
            $entity instanceof Core\Blogs\Blog;
    }
}
