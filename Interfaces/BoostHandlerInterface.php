<?php
namespace Minds\Interfaces;

/**
 * Interface for Boost Handlers
 */
interface BoostHandlerInterface
{
    /**
     * @param mixed $entity
     * @return boolean
     */
    public function validateEntity($entity);
}
