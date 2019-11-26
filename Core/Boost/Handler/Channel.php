<?php

namespace Minds\Core\Boost\Handler;

use Minds\Interfaces;

/**
 * Channel boost handler
 */
class Channel implements Interfaces\BoostHandlerInterface
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
