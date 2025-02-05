<?php
declare(strict_types=1);

namespace Minds\Core\Ai\Subscriptions;

use Minds\Core\Ai\Services\ChatProcessorService;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Notifications\Events\ChatNotificationEvent;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver as EntitiesResolver;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\ChatNotificationsTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;
use NotImplementedException;

/**
 * Test: php engine/cli.php EventStreams --subscription=Core\\Ai\\Subscriptions\\ChatBotEventsSubscription
 */
class ChatBotEventsSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?ChatProcessorService $chatProcessorService = null,
        private ?EntitiesResolver $entitiesResolver = null,
        private ?Logger $logger = null,
    ) {
        $this->chatProcessorService ??= Di::_()->get(ChatProcessorService::class);
        $this->entitiesResolver ??= Di::_()->get(EntitiesResolver::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function getSubscriptionId(): string
    {
        return "chat-bot";
    }

    public function getTopic(): TopicInterface
    {
        return new ChatNotificationsTopic();
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
        if (!$event instanceof ChatNotificationEvent) {
            return false;
        }

        $chatMessage = $this->entitiesResolver->single($event->entityUrn);

        if (!$chatMessage instanceof ChatMessage) {
            return true; // Probably deleted
        }

        return $this->chatProcessorService->onMessage($chatMessage);
    }
}
