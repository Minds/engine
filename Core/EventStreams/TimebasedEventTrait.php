<?php
namespace Minds\Core\EventStreams;

trait TimebasedEventTrait
{
    protected int $timestamp = 0;

    /**
     * The event timestamp
     * @param int $timestamp
     * @return self
     */
    public function setTimestamp(int $timestamp): self
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
