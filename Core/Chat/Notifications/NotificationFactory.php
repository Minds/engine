<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Notifications\Models\AbstractChatNotification;
use Minds\Core\Chat\Notifications\Models\PlainTextMessageNotification;
use Minds\Core\Chat\Notifications\Models\RichEmbedMessageNotification;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class NotificationFactory
{
    public function __construct(
        private readonly EntitiesBuilder $entitiesBuilder,
    ) {
    }

    public function createNotification(
        string $notificationClass,
        ChatMessage $chatEntity,
        ChatRoom $chatRoom,
    ): AbstractChatNotification {
        $sender = $this->entitiesBuilder->single($chatEntity->getOwnerGuid());

        if (!$sender instanceof User) {
            throw new ServerErrorException('Invalid sender');
        }

        return match ($notificationClass) {
            PlainTextMessageNotification::class => (new PlainTextMessageNotification())->fromEntity(
                chatMessage: $chatEntity,
                chatRoom: $chatRoom,
                sender: $sender,
            ),
            RichEmbedMessageNotification::class => (new RichEmbedMessageNotification())->fromEntity(
                chatMessage: $chatEntity,
                chatRoom: $chatRoom,
                sender: $sender
            ),
            default => throw new \InvalidArgumentException('Invalid notification class'),
        };
    }
}
