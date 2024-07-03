<?php

namespace Minds\Core\Entities\EventStreams;

use Minds\Core\Counters;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Sockets\Events as SocketEvents;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Log\Logger;
use Minds\Core\Votes\Enums\VoteEnum;
use Minds\Core\Votes\MySqlRepository as VotesMySqlRepository;

/**
 * Subscribes to metric change events.
 */
class MetricChangeStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?SocketEvents $socketEvents = null,
        private ?Counters $counters = null,
        private ?VotesMySqlRepository $votesMySqlRepository = null,
        private ?ExperimentsManager $experiments = null,
        private ?Logger $logger = null
    ) {
        $this->socketEvents ??= new SocketEvents();
        $this->counters ??= new Counters();
        $this->votesMySqlRepository ??= Di::_()->get(VotesMySqlRepository::class);
        $this->experiments ??= Di::_()->get('Experiments\Manager');
        $this->logger ??= Di::_()->get('Logger');
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
     * @return ActionEventsTopic - topic.
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
        return '(vote_up|vote_up_removed)';
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

        if (!$entity) {
            return true;
        }

        $guid = $this->getGuid($entity);

        switch ($event->getAction()) {
            case 'vote_up_removed':
            case 'vote_up':
                $count = isset($entity?->tenant_id) && (bool) $entity->tenant_id ?
                    $this->votesMySqlRepository->getCount($guid, VoteEnum::UP) :
                    $this->counters->get($guid, 'thumbs:up', false);

                $this->emitViaSockets(
                    guid: $guid,
                    key: 'thumbs:up:count',
                    value: $count
                );
                break;
        }

        return true;
    }

    /**
     * Emits event via sockets.
     * @param string $guid - guid to emitting for.
     * @param string $key - metrics key e.g. `thumbs:up:count`.
     * @param integer $value - value we want to emit to sockets.
     * @return self
     */
    private function emitViaSockets(string $guid, string $key, int $value): self
    {
        $roomName = "entity:metrics:$guid";

        $this->socketEvents
            ->setRoom($roomName) // send it to this group.
            ->emit($roomName, json_encode([$key => $value]));

        $this->logger->info("Emitting to $roomName: [$key => $value]");
        return $this;
    }

    /**
     * Get GUID to emit to for an entity.
     * @param mixed $entity - entity to get GUID for.
     * @return string guid.
     */
    private function getGuid(mixed $entity): string
    {
        if (method_exists($entity, 'getEntityGuid') && $entity->getEntityGuid()) {
            return $entity->getEntityGuid();
        }

        return (string) $entity->getGuid();
    }
}
