<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Chat\Types\ChatMessageNode;
use Minds\Core\Guid;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly RoomRepository $roomRepository
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

        $this->roomRepository->isUserMemberOfRoom(
            roomGuid: $roomGuid,
            user: $user->getGuid()
        );

        $this->messageRepository->addMessage($chatMessage);

        return new ChatMessageNode($chatMessage);
    }

    /**
     * @param int $roomGuid
     * @param int $limit
     * @param int $offset
     * @return array<ChatMessageNode>
     * @throws ServerErrorException
     */
    public function getMessages(
        int $roomGuid,
        int $limit,
        int $offset
    ): array {
        $messages = iterator_to_array($this->messageRepository->getMessagesByRoom($roomGuid));

        return array_map(fn (ChatMessage $message) => new ChatMessageEdge(node: new ChatMessageNode(chatMessage: $message)), usort($messages));
    }
}
