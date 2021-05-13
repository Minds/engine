<?php
/**
 * Interface subscriptions will use
 */
namespace Minds\Core\EventStreams;

use Minds\Core\EventStreams\Topics\TopicInterface;

interface SubscriptionInterface
{
    /**
     * @return string
     */
    public function getSubscriptionId(): string;

    /**
     * The topic we are subscribing to
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface;

    /**
     * The regex of the topics we want to subscribe to
     */
    public function getTopicRegex(): string;

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool;
}
