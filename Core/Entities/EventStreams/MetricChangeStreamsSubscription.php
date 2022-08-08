<?php

namespace Minds\Core\Entities\EventStreams;

use Minds\Core\Di\Di;
use Minds\Core\Entities\GuidLinkResolver;
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
        private ?SocketEvents $socketEvents = null,
        private ?GuidLinkResolver $guidLinkResolver = null
    ) {
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->socketEvents ??= new SocketEvents();
        $this->guidLinkResolver ??= new GuidLinkResolver();
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
        return '(vote_up|vote_down|vote_up_removed|vote_down_removed)';
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
        $guids = [(string) $entity->getGuid()];

        if (method_exists($entity, 'getEntityGuid') && $entity->getEntityGuid()) {
            $guids[] = (string) $entity->getEntityGuid();
        } else {
            $guids[] = $this->guidLinkResolver->resolve($guids[0]);
        }

        $guids = array_filter($guids);

        switch ($event->getAction()) {
            case 'vote_up_removed':
            case 'vote_up':
                $count = Counters::get($guids[0], 'thumbs:up', false);
                $this->emitViaSockets(
                    guids: $guids,
                    key: 'thumbs:up:count',
                    value: $count
                );
                break;
            case 'vote_down_removed':
            case 'vote_down':
                $count = Counters::get($guids[0], 'thumbs:down', false);
                $this->emitViaSockets(
                    guids: $guids,
                    key: 'thumbs:down:count',
                    value: $count
                );
                break;
        }

        return true;
    }

    /**
     * Emits event via sockets.
     * @param array $guids - guids we are emitting for.
     * @param string $key - metrics key e.g. `thumbs:count:count`.
     * @param integer $value - value we want to emit to sockets.
     * @return self
     */
    private function emitViaSockets(array $guids, string $key, int $value): self
    {
        foreach ($guids as $guid) {
            $roomName = "entity:metrics:$guid";

            (new SocketEvents())
                ->setRoom($roomName) // send it to this group.
                ->emit($roomName, json_encode([$key => $value]));
        }

        return $this;
    }
}
