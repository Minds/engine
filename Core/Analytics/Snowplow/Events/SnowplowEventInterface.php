<?php
namespace Minds\Core\Analytics\Snowplow\Events;

use Minds\Core\Analytics\Snowplow\Contexts\SnowplowContextInterface;

interface SnowplowEventInterface
{
    /**
     * Return the schema of the event
     * @return string
     */
    public function getSchema(): string;

    /**
     * Return an array of the event data
     * @return array
     */
    public function getData(): array;

    /**
     * Returns the contexts attached to the event
     * @return SnowplowContextInterface[]
     */
    public function getContext(): ?array;
}
