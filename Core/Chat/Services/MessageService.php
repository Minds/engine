<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Types\ChatMessageNode;
use Minds\Core\Guid;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
    ) {
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @param string $message
     * @return ChatMessageNode
     * @throws ServerErrorException
     */
    public function addMessage(
        int $roomGuid,
        User $user,
        string $message
    ): ChatMessageNode {
        $chatMessage = new ChatMessage(
            roomGuid: $roomGuid,
            guid: Guid::build(),
            senderGuid: (int) $user->getGuid(),
            plainText: $message,
        );

        $this->messageRepository->addMessage($chatMessage);

        return new ChatMessageNode($chatMessage);
    }

    /**
     * @param int $roomGuid
     * @param int $limit
     * @param int $offset
     * @return array<ChatMessageNode>
     */
    public function getMessages(
        int $roomGuid,
        int $limit,
        int $offset
    ): array {
        $messages = $this->messageRepository->getMessages($roomGuid, $limit, $offset);

        return array_map(fn (ChatMessage $message) => new ChatMessageNode(chatMessage: $message), $messages);
    }
}
