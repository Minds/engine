<?php
/**
 * This subscription is only used for testing purposes.
 * Make a new one with a unique subscription id if you wish to use this topic
 * You can test by running `php cli.php EventStreams --subscription=Core\\Entities\\Ops\\TestEntitiesOpsEventStreamsSubscription`
 */
namespace Minds\Core\Entities\Ops;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;

class TestEntitiesOpsEventStreamsSubscription implements SubscriptionInterface
{
    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'test-entities-ops';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new EntitiesOpsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return '.*'; // Everything
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof EntitiesOpsEvent) {
            return false;
        }

        error_log(print_r($event, true));
       
        return true; // Return true to awknowledge the event from the stream (stop it being redelivered)
    }
}
