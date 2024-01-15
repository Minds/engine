<?php
/**
 * This subscription will listen for changes in entities (create, update, delete) and send to the activitypub processor
 * You can test by running `php cli.php EventStreams --subscription=Core\\ActivityPub\\Subscriptions\\ActivityPubEntitiesOpsSubscription`
 */
namespace Minds\Core\ActivityPub\Subscriptions;

use Minds\Common\Access;
use Minds\Core\ActivityPub\Enums\ActivityFactoryOpEnum;
use Minds\Core\ActivityPub\Exceptions\MissingEntityException;
use Minds\Core\ActivityPub\Exceptions\NotImplementedException;
use Minds\Core\ActivityPub\Factories\ActivityFactory;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\ActivityPub\Services\EmitActivityService;
use Minds\Core\ActivityPub\Services\FederationEnabledService;
use Minds\Core\ActivityPub\Types\Activity\AnnounceType;
use Minds\Core\ActivityPub\Types\Activity\CreateType;
use Minds\Core\ActivityPub\Types\Activity\DeleteType;
use Minds\Core\ActivityPub\Types\Activity\UpdateType;
use Minds\Core\Comments\Comment;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\FederatedEntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;

class ActivityPubEntitiesOpsSubscription implements SubscriptionInterface
{
    public function __construct(
        protected ?EmitActivityService $emitActivityService = null,
        protected ?ObjectFactory $objectFactory = null,
        protected ?ActorFactory $actorFactory = null,
        protected ?ActivityFactory $activityFactory = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?FederationEnabledService $federationEnabledService = null,
        protected ?Logger $logger = null
    ) {
        $this->emitActivityService ??= Di::_()->get(EmitActivityService::class);
        $this->objectFactory ??= Di::_()->get(ObjectFactory::class);
        $this->actorFactory ??= Di::_()->get(ActorFactory::class);
        $this->activityFactory ??= Di::_()->get(ActivityFactory::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->federationEnabledService ??= Di::_()->get(FederationEnabledService::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'activitypub-entities';
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

        if (!$this->federationEnabledService->isEnabled()) {
            $this->logger->info('Skipping as federation is disabled');
            return true;
        }

        // We may have a serialized entity (eg. if we no longer have the deleted record)
        if ($serializedEntity = $event->getEntitySerialized()) {
            $entity = unserialize($serializedEntity);
        } else {
            $entity = $this->entitiesBuilder->getByUrn($event->getEntityUrn());
        }

        if (!$entity instanceof FederatedEntityInterface) {
            // Entity not found
            return true; // Acknowledge as its likely this entity has been deleted
        }
    
        $loggerPrefix = $entity->getUrn() . ':';

        if ($entity->getSource() === FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            $this->logger->info($loggerPrefix . ' Skipping. Federated entity.');
            return true;
        }

        // We are only concerned with the below entities
        switch (get_class($entity)) {
            case Activity::class:
                if ((int) $entity->getAccessId() !== Access::PUBLIC) {
                    $this->logger->info($loggerPrefix . ' Skipping. Not public.');
                    return true; // Not a public post, we will not emit out
                }
                // no break
            case Comment::class:
                break;
            default:
                $this->logger->info($loggerPrefix . ' Skipping. Unsupported entity');
                return true;
        }

        $owner = $this->entitiesBuilder->single($entity->getOwnerGuid());

        if (!$owner instanceof User) {
            $this->logger->info($loggerPrefix . ' Skipping. Owner not found');
            return true; // Bad user, we will skip
        }

        if ($owner->getSource() === FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            $this->logger->info($loggerPrefix . ' Skipping. Owner is a federated users');
            return true; // Do not re-process activitypub posts
        }

        $op = match ($event->getOp()) {
            EntitiesOpsEvent::OP_CREATE => ActivityFactoryOpEnum::CREATE,
            EntitiesOpsEvent::OP_UPDATE => ActivityFactoryOpEnum::UPDATE,
            EntitiesOpsEvent::OP_DELETE => ActivityFactoryOpEnum::DELETE,
        };

        if ($op === ActivityFactoryOpEnum::UPDATE) {
            return true; // we will not issue updates for now due to load concerns
        }

        try {
            $activity = $this->activityFactory->fromEntity(
                op: $op,
                entity: $entity,
                actor: $owner
            );
        } catch (NotFoundException|MissingEntityException|NotImplementedException|ForbiddenException $e) {
            $this->logger->info($loggerPrefix . ' Skipping.  (' . $e->getMessage() . ')');
            return true;
        }

        $this->emitActivityService->emitActivity($activity, $owner);

        $this->logger->info($loggerPrefix . ' Success (' . $event->getOp() . ')');

        return true; // Return true to acknowledge the event from the stream (stop it being redelivered)
    }

}
