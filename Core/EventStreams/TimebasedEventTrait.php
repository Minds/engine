<?php
namespace Minds\Core\EventStreams;

use Minds\Entities\EntityInterface;
use Minds\Entities\User;

trait TimebasedEventTrait
{
    protected int $timestamp = 0;

    /**
     * The event timestamp
     * @param int $timestamp
     * @return self
     */
    public function setTimestamp(int $timestamp): EventInterface
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}
