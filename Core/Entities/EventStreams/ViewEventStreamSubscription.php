<?php

namespace Minds\Core\Entities\EventStreams;

use Minds\Core\EventStreams\BatchSubscriptionInterface;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\ViewsTopic;

class ViewEventStreamSubscription implements BatchSubscriptionInterface
{
    public function getSubscriptionId(): string
    {
        return 'views';
    }

    public function getTopic(): ViewsTopic
    {
        return new ViewsTopic();
    }

    public function getTopicRegex(): string
    {
        return '*';
    }

    public function consume(EventInterface $event): bool
    {
        return true;
    }

    public function consumeBatch(array $messages): bool
    {
        foreach ($messages as $message) {
            error_log(print_r($message->getDataAsString(), true));
            $this->getTopic()->markMessageAsProcessed($message);
        }

        return true; // Return true to awknowledge the batch from the stream
    }
}
