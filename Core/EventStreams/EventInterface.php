<?php
namespace Minds\Core\EventStreams;

interface EventInterface
{
    /**
     * @param int $timestamp
     * @return self
     */
    public function setTimestamp(int $timestamp): self;

    /**
     * @return int
     */
    public function getTimestamp(): int;
}
