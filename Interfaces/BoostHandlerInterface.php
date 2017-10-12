<?php
namespace Minds\Interfaces;

/**
 * Interface for Boost Handlers
 */
interface BoostHandlerInterface
{
    /**
     * Boost an entity, place in a review queue first
     * @param object|int $entity - the entity to boost
     * @param int        $impressions
     * @return bool
     */
    public function boost($entity, $impressions);



    /**
     * Accept a boost
     * @param object|int $entity
     * @param int        $impressions
     * @return bool
     */
    public function accept($entity, $impressions);

    /**
     * Return a boost
     * @return array
     */
    public function getBoost();
}
