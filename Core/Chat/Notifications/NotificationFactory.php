<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Notifications\Models\AbstractChatNotification;
use Minds\Core\Chat\Notifications\Models\PlainTextMessageNotification;
use Minds\Core\EntitiesBuilder;

class NotificationFactory
{
    public function __construct(
        private readonly EntitiesBuilder $entitiesBuilder
    ) {
    }

    public function createNotification(
        string $notificationClass,
        ChatMessage|ChatRoom $chatEntity,
    ): AbstractChatNotification {
        return match ($notificationClass) {
            PlainTextMessageNotification::class => (new PlainTextMessageNotification())->fromEntity(
                chatMessage: $chatEntity,
                sender: $this->entitiesBuilder->single($chatEntity->getOwnerGuid()),
            ),
            default => throw new \InvalidArgumentException('Invalid notification class'),
        };
    }
}
