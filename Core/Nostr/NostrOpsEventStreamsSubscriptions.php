<?php
/**
 * This subscription will sync comments to the Nostr table
 * You can test by running `php cli.php EventStreams --subscription=Core\\Nostr\\NostrOpsEventStreamsSubscriptions`
 */

namespace Minds\Core\Comments;

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
    private SearchRepository $searchRepository;
    private RelationalRepository $repository;

    private Logger $logger;

    private Resolver $entitiesResolver;
    private EntitiesBuilder $entitiesBuilder;

    public function __construct(
        Manager $manager = null,
        RelationalRepository $repository = null,
        SearchRepository $searchRepository = null,
        Resolver $entitiesResolver = null,
        EntitiesBuilder $entitiesBuilder = null,
        Logger $logger = null
    ) {
        $this->manager = $manager ?? Di::_()->get('Comments\Manager');
        $this->searchRepository = $searchRepository ?? new SearchRepository();
        $this->repository = $repository ?? new RelationalRepository();
        $this->entitiesResolver ??= new Resolver();
        $this->entitiesBuilder ??= new EntitiesBuilder();
        $this->logger ??= Di::_()->get('Logger');
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
                $logger->info("Activity");
                $user = $entity->getOwnerEntity();
                break;
            case User::class:
                $logger->info("User");
                $user = $entity;
                break;
            default:
                return true; // Will not sync anything else
        }

        $delegatePublicKey = $this->manager->getPublicKeyFromUser($user);
        $nip26DelegateToken = $this->keys->getNip26DelegationToken($delegatePublicKey);

        if (!$nip26DelegateToken) {
            $logger->info("No NIP26 Delegate Token found for user {$user->getUrn()}");
            return true;
        }

        switch ($event->getOp()) {
            case EntitiesOpsEvent::OP_CREATE:
            case EntitiesOpsEvent::OP_UPDATE:
                $logger->info("Create or Update");
                $event = $this->manager->buildNostrEvent($entity);
                $this->manager->addEvent($event);
                break;
            case EntitiesOpsEvent::OP_DELETE:
                $logger->info("Delete");
                $this->manager->removeEvent($entity);
                break;
        }
    }
}
