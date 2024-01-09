<?php
/**
 * This subscription will deliver Minds events to the Fediverse
 * You can test by running `php cli.php EventStreams --subscription=Core\\ActivityPub\\Subscriptions\\ActivityPubEventStreamsSubscription`
 */
namespace Minds\Core\ActivityPub\Subscriptions;

use Minds\Core\ActivityPub\Exceptions\NotImplementedException;
use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Services\EmitActivityService;
use Minds\Core\ActivityPub\Types\Activity\FlagType;
use Minds\Core\ActivityPub\Types\Activity\FollowType;
use Minds\Core\ActivityPub\Types\Activity\LikeType;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\FederatedEntityInterface;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;

class ActivityPubEventStreamsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?ObjectFactory $objectFactory = null,
        protected ?Manager $manager = null,
        protected ?EmitActivityService $emitActivityService = null,
        protected ?ActorFactory $actorFactory = null,
        protected ?Logger $logger = null,
        protected ?Config $config = null,
    ) {
        $this->objectFactory = Di::_()->get(ObjectFactory::class);
        $this->manager ??= Di::_()->get(Manager::class);
        $this->emitActivityService ??= Di::_()->get(EmitActivityService::class);
        $this->actorFactory ??= Di::_()->get(ActorFactory::class);
        $this->logger ??= Di::_()->get('Logger');
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return 'activitypub-events';
    }

    /**
     * @return TopicInterface
     */
    public function getTopic(): TopicInterface
    {
        return new ActionEventsTopic();
    }

    /**
     * @return string
     */
    public function getTopicRegex(): string
    {
        return '.*'; // Notifications want all actions
    }

    /**
     * Called when there is a new event
     * @param EventInterface $event
     * @return bool
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws NotImplementedException
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            $this->logger->info('Skipping as not an action event');
            return false;
        }

        $this->logger->info('Action event type: ' . $event->getAction());

        /** @var User $user */
        $user = $event->getUser();

        if ($user->getSource() === FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            $this->logger->info("Skipping: {$user->getGuid()} is a federated user action");
            return true; // Do not reprocess activitypub events
        }

        /** @var EntityInterface $entity */
        $entity = $event->getEntity();

        if (!$entity instanceof FederatedEntityInterface) {
            $this->logger->info("Skipping: {$entity->getGuid()} is not a supported entity type");
            return true;
        }

        switch ($event->getAction()) {
            case ActionEvent::ACTION_SUBSCRIBE:
                $actor = $this->actorFactory->fromEntity($user);
                $object = $this->actorFactory->fromEntity($entity);

                $follow = new FollowType();
                $follow->id = $this->manager->getTransientId();
                $follow->actor = $actor;
                $follow->object = $object;

                $this->emitActivityService->emitFollow($follow, $user);
                
                return true;
            case ActionEvent::ACTION_VOTE_UP:
            case ActionEvent::ACTION_VOTE_UP_REMOVED:
                $actor = $this->actorFactory->fromEntity($user);
                $object = $this->objectFactory->fromEntity($entity);

                if (!isset($object->attributedTo)) {
                    return true; // No owner, so we will skip
                }

                $like = new LikeType();
                $like->id = $this->manager->getTransientId();
                $like->actor = $actor;
                $like->object = $object;

                if ($event->getAction() === ActionEvent::ACTION_VOTE_UP_REMOVED) {
                    $like->object = $object->id;
                    $this->emitActivityService->emitUndoLike($like, $user, $object->attributedTo);
                    return true;
                }

                $this->emitActivityService->emitLike($like, $user);
                return true;
            case ActionEvent::ACTION_UPHELD_REPORT:
                $this->logger->info('Skipping upheld report');

                $object = $this->objectFactory->fromEntity($entity);

                $flagType = new FlagType();
                $flagType->id = $this->manager->getTransientId();
                $flagType->actor = $this->actorFactory->buildMindsApplicationActor(); // new System Application Type
                $flagType->object = $object->id;

                $this->emitActivityService->emitFlag($flagType, $object->attributedTo);
                return true;

            default:
                $this->logger->info('Skipping as not a supported action');
                return true; // Noop (nothing to do)
        }
    }

}
