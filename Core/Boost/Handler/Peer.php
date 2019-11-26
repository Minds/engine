<?php

namespace Minds\Core\Boost\Handler;

use Minds\Interfaces;

/**
 * Peer Boost Handler
 */
class Peer implements Interfaces\BoostHandlerInterface
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
