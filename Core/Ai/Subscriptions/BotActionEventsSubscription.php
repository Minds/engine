<?php
declare(strict_types=1);

namespace Minds\Core\Ai\Subscriptions;

use Minds\Core\Ai\Services\CommentProcessorService;
use Minds\Core\Comments\Manager as CommentManager;
use Minds\Core\Comments\Comment;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;

/**
 * Test: php engine/cli.php EventStreams --subscription=Core\\Ai\\Subscriptions\\BotActionEventsSubscription
 */
class BotActionEventsSubscription implements SubscriptionInterface
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
        return new ActionEventsTopic();
    }

    public function getTopicRegex(): string
    {
        return '(comment|tag)';
    }

    /**
     * @param EventInterface $event
     * @return bool
     * @throws ServerErrorException
     * @throws NotImplementedException
     */
    public function consume(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        switch ($event->getAction()) {
            case ActionEvent::ACTION_TAG:
                $taggedUser = $event->getEntity();
                
                if (!$taggedUser instanceof User) {
                    return true; // Bad user found
                }

                $activity = $this->entitiesResolver->single($event->getActionData()['tag_in_entity_urn']);

                if (!$activity instanceof Activity) {
                    return true; // Bad activity found
                }

                return $this->commentProcessorService->onActivityTag($activity, $taggedUser);
                break;
            case ActionEvent::ACTION_COMMENT:

                $comment = $this->commentManager->getByUrn($event->getActionData()['comment_urn']);

                if (!$comment instanceof Comment) {
                    return false; // Bad comment found
                }

                return $this->commentProcessorService->onComment($comment);
                
                break;
        }

        return false; // TODO: change to true
    }
}
