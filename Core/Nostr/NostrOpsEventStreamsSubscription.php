<?php
/**
 * This subscription will sync comments to the Nostr table
 * You can test by running `php cli.php EventStreams --subscription=Core\\Nostr\\NostrOpsEventStreamsSubscription`
 */

namespace Minds\Core\Nostr;

use Minds\Common\Urn;
use Minds\Entities\Activity;
use Minds\Entities\User;

use Minds\Core\Di\Di;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Resolver;
use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;

class NostrOpsEventStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        protected ?Manager $manager = null,
        protected ?Resolver $entitiesResolver = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Keys $keys = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Nostr\Manager');
        $this->entitiesResolver ??= new Resolver();
        $this->entitiesBuilder ??= new EntitiesBuilder();
        $this->keys ??= new Keys();
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'nostr-sync';
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
        if (!$event instanceof EntitiesOpsEvent) {
            return true;
        }

        $entity = $this->entitiesResolver->setOpts(['cache' => false])
            ->single(new Urn($event->getEntityUrn()));

        if (!$entity) {
            // Entity not found
            return true; // Acknowledge as its likely this entity has been deleted
        }

        switch (get_class($entity)) {
            case Activity::class:
                /** @var User */
                $user = $this->entitiesBuilder->single($entity->getOwnerGuid());
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

        $nostrEventId = $this->manager->getNostrEventFromActivityId($entity->getGUID());

        switch ($event->getOp()) {
            case EntitiesOpsEvent::OP_CREATE:
                $nostrEvent = $this->manager->buildNostrEvent($entity);
                $this->manager->addEvent($nostrEvent);
                $this->manager->addActivityToNostrId($entity, $nostrEvent->getId());
                return true;
            case EntitiesOpsEvent::OP_UPDATE:
                $nostrEvent = $this->manager->buildNostrEvent($entity);
                $this->manager->addEvent($nostrEvent);
                $this->manager->addActivityToNostrId($entity, $nostrEvent->getId());
                $this->manager->deleteNostrEvents([$nostrEventId]);
                return true;
            case EntitiesOpsEvent::OP_DELETE:
                $this->manager->deleteNostrEvents([$nostrEventId]);
                return true;
        }

        return false;
    }
}
