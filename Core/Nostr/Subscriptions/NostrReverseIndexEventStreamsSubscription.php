<?php

namespace Minds\Core\Nostr\Subscriptions;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Nostr\Manager;
use Minds\Exceptions\ServerErrorException;

/**
 * Listens to EntitiesOpsEvent events and generates a new Nostr hash to link to the related entity
 */
class NostrReverseIndexEventStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?Manager $manager = null
    ) {
        $this->manager ??= new Manager();
    }

    public function getSubscriptionId(): string
    {
        return 'nostr-to-minds-reverse-index';
    }

    public function getTopic(): TopicInterface
    {
        return new EntitiesOpsTopic();
    }

    public function getTopicRegex(): string
    {
        return ".*";
    }

    /**
     * For every entity op event we create a new nostr hash
     * in the 'nostr_hashes_to_entities' correlation table
     * @param EventInterface $event
     * @return bool
     * @throws ServerErrorException
     * @throws Exception
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof EntitiesOpsEvent) {
            return false;
        }

        if ($event->getOp() == EntitiesOpsEvent::OP_DELETE) {
            return true;
        }

        $this->manager->addNostrHashLinkToEntity(
            new Urn($event->getEntityUrn())
        );

        return true;
    }
}
