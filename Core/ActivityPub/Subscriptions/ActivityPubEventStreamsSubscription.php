<?php
/**
 * This subscription will deliver Minds events to the Fediverse
 * You can test by running `php cli.php EventStreams --subscription=Core\\ActivityPub\\Subscriptions\\ActivityPubEventStreamsSubscription`
 */
namespace Minds\Core\ActivityPub\Subscriptions;

use Minds\Core\ActivityPub\Factories\ActorFactory;
use Minds\Core\ActivityPub\Factories\ObjectFactory;
use Minds\Core\ActivityPub\Manager;
use Minds\Core\ActivityPub\Services\EmitActivityService;
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
use Minds\Entities\User;

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
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            $this->logger->info('Skipping as not an action event');
            return false;
        }

        $this->logger->info('Action event type: ' . $event->getAction());

        /** @var User */
        $user = $event->getUser();

        if ($user->getSource() === FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            $this->logger->info("Skipping: {$user->getGuid()} is a federated user action");
            return true; // Do not reprocess activitypub events
        }

        /** @var mixed */
        $entity = $event->getEntity();

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

                $like = new LikeType();
                $like->id = $this->manager->getTransientId();
                $like->actor = $actor;
                $like->object = $object;

                $method = $event->getAction() === ActionEvent::ACTION_VOTE_UP ? 'emitLike' : 'emitUndoLike';
                $this->emitActivityService->$method($like, $user);
                return true;
                break;
            default:
                return true; // Noop (nothing to do)
        }
    }

}
