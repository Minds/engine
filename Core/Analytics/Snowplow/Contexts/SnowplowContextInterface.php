<?php
namespace Minds\Core\Analytics\Snowplow\Contexts;

interface SnowplowContextInterface
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
}
