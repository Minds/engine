<?php

namespace Minds\Core\Boost\Handler;

use Minds\Interfaces\BoostHandlerInterface;

/**
 * Newsfeed Boost handler
 */
class Network implements BoostHandlerInterface
{
    /**
     * @param mixed $entity
     * @return boolean
     */
    public function validateEntity($entity)
    {
        return true;
    }
}
