<?php
/**
 * This subscription will listen for changes in entities (create, update, delete) and send to the activitypub processor
 * You can test by running `php cli.php EventStreams --subscription=Core\\ActivityPub\\Subscriptions\\ActivityPubEntitiesOpsSubscription`
 */
namespace Minds\Core\ActivityPub\Subscriptions;

use Minds\Common\Access;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\ActivityPub\Services\EmitActivityService;
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
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;

class ActivityPubEntitiesOpsSubscription implements SubscriptionInterface
{
    public function __construct(
        protected ?EmitActivityService $emitActivityService = null,
        protected ?ObjectFactory $objectFactory = null,
        protected ?ActorFactory $actorFactory = null,
        protected ?EntitiesBuilder $entitiesBuilder = null,
        protected ?Logger $logger = null
    ) {
        $this->emitActivityService ??= Di::_()->get(EmitActivityService::class);
        $this->objectFactory ??= Di::_()->get(ObjectFactory::class);
        $this->actorFactory ??= Di::_()->get(ActorFactory::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
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

        $entity = $this->entitiesBuilder->getByUrn($event->getEntityUrn());

        if (!$entity instanceof EntityInterface) {
            // Entity not found
            return true; // Acknowledge as its likely this entity has been deleted
        }

        // We are only concerned with the below entities
        switch (get_class($entity)) {
            case Activity::class:
                if ((int) $entity->getAccessId() !== Access::PUBLIC) {
                    return true; // Not a public post, we will not emit out
                }
                // no break
            case Comment::class:
                break;
            default:
                return true;
        }

        $object = $this->objectFactory->fromEntity($entity);
        $owner = $this->entitiesBuilder->single($entity->getOwnerGuid());

        if (!$owner instanceof User) {
            return true; // Bad user, we will skip
        }

        $actor = $this->actorFactory->fromEntity($owner);

        if ($object instanceof AnnounceType) {
            // TODO
            var_dump('announce type');
            return false;
        }

        $activity = match ($event->getOp()) {
            EntitiesOpsEvent::OP_CREATE => new CreateType(),
            EntitiesOpsEvent::OP_UPDATE => new UpdateType(),
            EntitiesOpsEvent::OP_DELETE => new DeleteType(),
        };

        $activity->id = $object->id . '/activity';
        $activity->actor = $actor;
        $activity->object = $object;

        $this->emitActivityService->emitActivity($activity, $owner);

        return true; // Return true to acknowledge the event from the stream (stop it being redelivered)
    }

}
