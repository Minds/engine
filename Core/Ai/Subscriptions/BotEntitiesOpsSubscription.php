<?php
declare(strict_types=1);

namespace Minds\Core\Ai\Subscriptions;

use Minds\Core\Ai\Services\CommentProcessorService;
use Minds\Core\Comments\Manager as CommentManager;
use Minds\Core\Comments\Comment;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Ops\EntitiesOpsEvent;
use Minds\Core\Entities\Ops\EntitiesOpsTopic;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;

/**
 * Test: php engine/cli.php EventStreams --subscription=Core\\Ai\\Subscriptions\\BotEntitiesOpsSubscription
 */
class BotEntitiesOpsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?CommentManager $commentManager = null,
        private ?CommentProcessorService $commentProcessorService = null,
        private ?EntitiesResolver $entitiesResolver = null,
        private ?Logger $logger = null,
    ) {
        $this->commentManager = $manager ?? Di::_()->get('Comments\Manager');
        $this->commentProcessorService ??= Di::_()->get(CommentProcessorService::class);
        $this->entitiesResolver ??= Di::_()->get(EntitiesResolver::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function getSubscriptionId(): string
    {
        return "chat-bot";
    }

    public function getTopic(): TopicInterface
    {
        return new EntitiesOpsTopic();
    }

    public function getTopicRegex(): string
    {
        return '.*';
    }

    /**
     * @param EventInterface $event
     * @return bool
     * @throws ServerErrorException
     * @throws NotImplementedException
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof EntitiesOpsEvent) {
            return false;
        }

        if ($event->getOp() !== EntitiesOpsEvent::OP_CREATE) {
            return true; // We only work with create events
        }

        $entity = $this->entitiesResolver->single($event->getEntityUrn());
        
        if (!$entity) {
            if ($event->getTimestamp() > time() - 300) {
                return false; // Neg ack. Retry, may be replication lag.
            }
            // Entity not found
            return true; // Awknowledge as its likely this entity has been deleted
        }

        switch (get_class($entity)) {
            case Activity::class:
                return $this->commentProcessorService->onActivity($entity);
                break;
            case Comment::class:
                return $this->commentProcessorService->onComment($entity);
                break;
        }

        return true;
    }
}
