<?php

namespace Minds\Core\Nostr\Subscriptions;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;

/**
 *
 */
class NostrReverseIndexEventStreamsSubscription implements SubscriptionInterface
{

    public function getSubscriptionId(): string
    {
        return '';
    }

    public function getTopic(): TopicInterface
    {
        return new
    }

    public function getTopicRegex(): string
    {
        // TODO: Implement getTopicRegex() method.
    }

    public function consume(EventInterface $event): bool
    {
        // TODO: Implement consume() method.
    }
}
