<?php
/**
 * Boost Entity Interface
 */

namespace Minds\Entities\Boost;

use Minds\Entities\Entity;

interface BoostEntityInterface
{
    /**
   * Set the entity to boost
   * @param mixed $entity
   * @return $this
   * @todo   Create an interface for setEntity parameters
   */
  public function setEntity($entity);

  /**
   * Get the entity
   * @return Entity
   */
  public function getEntity();

  /**
   * Set the state of the boost
   * @param string $state
   * @return $this
   */
  public function setState($state);

  /**
   * Return the state of the boost
   * @return string
   */
  public function getState();
}
