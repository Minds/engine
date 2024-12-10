<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Notifications\Models;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Entities\User;

/**
 * Notification for image messages.
 */
class ImageMessageNotification extends PlainTextMessageNotification
{
    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function fromEntity(
        ChatMessage $chatMessage,
        ChatRoom $chatRoom,
        User $sender
    ): self {
        return new self(
            chatRoomGuid: $chatMessage->roomGuid,
            title: $chatRoom->name ?: $sender->getName(),
            body: '📷 Image',
            icon: $sender->getIconURL('large')
        );
    }
}
