<?php

namespace Minds\Core\Entities\EventStreams;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\Log\Logger;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Sockets\Events as SocketEvents;
use Minds\Helpers\Counters;

/**
 * Subscribes to metric change events.
 */
class MetricChangeStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?Logger $logger = null,
        private ?SocketEvents $socketEvents = null
    ) {
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->socketEvents ??= new SocketEvents();
    }

    /**
     * Returns subscription id.
     * @return string subscription id.
     */
    public function getSubscriptionId(): string
    {
        return 'action-metrics-emission';
    }
    
    /**
     * Returns topic.
     * @return ActionEvent - topic.
     */
    public function getTopic(): ActionEventsTopic
    {
        return new ActionEventsTopic();
    }

    /**
     * Returns topic regex, scoping subscription to metrics events we want to subscribe to.
     * @return string topic regex.
     */
    public function getTopicRegex(): string
    {
        return '(vote_up|vote_down)';
    }

    /**
     * Called on event receipt.
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $entity = $event->getEntity();
        $entityGuid = $entity->getGuid();

        switch ($event->getAction()) {
            case 'vote_up':
                $count = Counters::get($entityGuid, 'thumbs:up');
                $this->emitViaSockets(
                    entityGuid: $entityGuid,
                    key: 'thumbs:up:count',
                    value: $count
                );
                break;
            case 'vote_down':
                $count = Counters::get($entityGuid, 'thumbs:down');
                $this->emitViaSockets(
                    entityGuid: $entityGuid,
                    key: 'thumbs:down:count',
                    value: $count
                );
                break;
        }

        return true;
    }

    /**
     * Emits event via sockets.
     * @param string $entityGuid - guid of entity we are emitting for.
     * @param string $key - metrics key e.g. `thumbs:count:count`.
     * @param integer $value - value we want to emit to sockets.
     * @return self
     */
    private function emitViaSockets(string $entityGuid, string $key, int $value): self
    {
        $roomName = "entity:metrics:$entityGuid";

        (new SocketEvents())
            ->setRoom($roomName) // send it to this group.
            ->emit($roomName, json_encode([$key => $value]));

        return $this;
    }
}
