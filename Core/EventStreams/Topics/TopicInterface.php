<?php
/**
 * Abstract topic, provides access to the Pulsar client
 */
namespace Minds\Core\EventStreams\Topics;

use Minds\Core\EventStreams\EventInterface;

interface TopicInterface
{
    /**
     * Send event to the stream
     * @param EventInterface $event
     * @return bool
     */
    public function send(EventInterface $event): bool;

    /**
     * Consume event fro topic
     * @param string $subscriptionId
     * @param callable $callback
     * @return void
     */
    public function consume(string $subscriptionId, callable $callback): void;
}
