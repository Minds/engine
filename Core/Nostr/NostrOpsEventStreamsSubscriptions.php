<?php
/**
 * This subscription will sync comments to the Nostr table
 * You can test by running `php cli.php EventStreams --subscription=Core\\Nostr\\NostrOpsEventStreamsSubscriptions`
 */

namespace Minds\Core\Nostr;

use Minds\Common\Urn;
use Minds\Entities\Activity;
use Minds\Entities\User;

use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Resolver;
use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;

class NostrOpsEventStreamsSubscriptions implements SubscriptionInterface
{
    protected Manager $manager;
    private Repository $repository;
    private Resolver $entitiesResolver;
    private EntitiesBuilder $entitiesBuilder;
    private Keys $keys;

    public function __construct(
        Manager $manager = null,
        Repository $repository = null,
        Resolver $entitiesResolver = null,
        EntitiesBuilder $entitiesBuilder = null,
        Logger $logger = null,
        Keys $keys = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Nostr\Manager');
        $this->repository = $repository ?? new Repository();
        $this->entitiesResolver ??= new Resolver();
        $this->entitiesBuilder ??= new EntitiesBuilder();
        $this->logger ??= new Logger();
        $this->keys ??= new Keys();
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'nostr-ops';
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
        return EntitiesOpsTopic::TOPIC_NAME;
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     */
    public function consume(EventInterface $event): bool
    {
        $hello = "world";
        if (!$event instanceof EntitiesOpsEvent) {
            return false;
        }

        $entity = $this->entitiesResolver->setOpts([
            'cache' => false
        ])->single(new Urn($event->getEntityUrn()));

        if (!$entity) {
            // Entity not found
            return true; // Acknowledge as its likely this entity has been deleted
        }

        switch (get_class($entity)) {
            case Activity::class:
                $user = $entity->getOwnerEntity();
                break;
            // case User::class: // TODO might be useful to sync user profile changes
            default:
                return true; // Will not sync anything else
        }

        $delegatePublicKey = $this->manager->getPublicKeyFromUser($user);
        $nip26DelegateToken = $this->keys->getNip26DelegationToken($delegatePublicKey);

        if (!$nip26DelegateToken) {
            return true;
        }

        $activityId = explode(':', $entity->getUrn())[2];
        $eventId = $this->manager->getNostrEventFromActivityId($activityId);

        switch ($event->getOp()) {
            case EntitiesOpsEvent::OP_CREATE:
                $event = $this->manager->buildNostrEvent($entity);
                $this->manager->addEvent($event);
                return true;
            case EntitiesOpsEvent::OP_UPDATE:
                $event = $this->manager->buildNostrEvent($entity);
                $this->manager->addEvent($event);
                $this->manager->deleteNostrEvents([$eventId]);
                return true;
            case EntitiesOpsEvent::OP_DELETE:
                $this->manager->deleteNostrEvents([$eventId]);
                return true;
        }
    }
}
