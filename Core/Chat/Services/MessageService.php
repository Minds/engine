<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Chat\Types\ChatMessageNode;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use Minds\Core\Guid;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly RoomRepository $roomRepository,
        private readonly EntitiesBuilder $entitiesBuilder
    ) {
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @param string $message
     * @return ChatMessageEdge
     * @throws ForbiddenException
     * @throws ServerErrorException
     */
    public function addMessage(
        int $roomGuid,
        User $user,
        string $message
    ): ChatMessageEdge {
        $chatMessage = new ChatMessage(
            roomGuid: $roomGuid,
            guid: (int) Guid::build(),
            senderGuid: (int) $user->getGuid(),
            plainText: $message,
        );

        if (
            !$this->roomRepository->isUserMemberOfRoom(
                roomGuid: $roomGuid,
                user: $user
            )
        ) {
            throw new ForbiddenException("You are not a member of this room");
        }

        $this->messageRepository->addMessage($chatMessage);

        return new ChatMessageEdge(
            node: new ChatMessageNode(
                chatMessage: $chatMessage,
                sender: new UserEdge(
                    user: $user,
                    cursor: ''
                )
            )
        );
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

        usort($messages, fn (ChatMessage $a, ChatMessage $b): bool => $a->createdAt > $b->createdAt);

        return array_map(
            fn (ChatMessage $message) => new ChatMessageEdge(
                node: new ChatMessageNode(
                    chatMessage: $message,
                    sender: new UserEdge(
                        user: $this->entitiesBuilder->single($message->senderGuid),
                        cursor: ''
                    )
                )
            ),
            $messages
        );
    }
}