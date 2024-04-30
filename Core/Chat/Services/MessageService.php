<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use Minds\Core\Chat\Delegates\AnalyticsDelegate;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Events\Sockets\ChatEvent;
use Minds\Core\Chat\Events\Sockets\Enums\ChatEventTypeEnum;
use Minds\Core\Chat\Exceptions\ChatMessageNotFoundException;
use Minds\Core\Chat\Exceptions\ChatRoomNotFoundException;
use Minds\Core\Chat\Notifications\Events\ChatNotificationEvent;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Chat\Types\ChatMessageNode;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\Topics\ChatNotificationsTopic;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Sockets\Events as SocketEvents;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PDOException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly RoomRepository $roomRepository,
        private readonly ReceiptService $receiptService,
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly SocketEvents $socketEvents,
        private readonly ChatNotificationsTopic $chatNotificationsTopic,
        private readonly RichEmbedService $chatRichEmbedService,
        private readonly AnalyticsDelegate $analyticsDelegate,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param int $roomGuid
     * @param User $user
     * @param string $message
     * @return ChatMessageEdge
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function addMessage(
        int $roomGuid,
        User $user,
        string $message
    ): ChatMessageEdge {
        $plainText = trim($message); // TODO: strengthen message validation to avoid multiple new lines

        if (empty($plainText)) {
            throw new GraphQLException(message: "Message cannot be empty", code: 400);
        }

        if (
            !$this->roomRepository->isUserMemberOfRoom(
                roomGuid: $roomGuid,
                user: $user
            )
        ) {
            throw new GraphQLException(message: "You are not a member of this room", code: 403);
        }

        $messageType = ChatMessageTypeEnum::TEXT;

        if ($richEmbed = $this->chatRichEmbedService->parseFromText($plainText) ?? null) {
            $messageType = ChatMessageTypeEnum::RICH_EMBED;
        }

        $chatMessage = new ChatMessage(
            roomGuid: $roomGuid,
            guid: (int) Guid::build(),
            senderGuid: (int) $user->getGuid(),
            plainText: $plainText,
            richEmbed: $richEmbed,
            messageType: $messageType
        );

        try {
            // Open transaction so we only send message along with a read receipt
            $this->messageRepository->beginTransaction();

            // Save the message
            $this->messageRepository->addMessage($chatMessage);

            // Add a rich embed if required.
            if ($chatMessage->richEmbed && $chatMessage->messageType === ChatMessageTypeEnum::RICH_EMBED) {
                $this->messageRepository->addRichEmbed(
                    roomGuid: $roomGuid,
                    messageGuid: $chatMessage->guid,
                    chatRichEmbed: $chatMessage->richEmbed
                );
            }

            // Add the receipt to ourself
            $this->receiptService->updateReceipt($chatMessage, $user);

            // Commit
            $this->messageRepository->commitTransaction();

            $this->socketEvents
                ->setRoom("chat:$roomGuid")
                ->emit(
                    "chat:$roomGuid",
                    json_encode(new ChatEvent(
                        type: ChatEventTypeEnum::NEW_MESSAGE,
                        metadata: [
                            'senderGuid' => (string) $user->getGuid(),
                        ],
                    ))
                );

            $this->chatNotificationsTopic->send(
                (new ChatNotificationEvent(
                    entityUrn: $chatMessage->getUrn(),
                    fromGuid: $chatMessage->senderGuid
                ))
                ->setTimestamp($chatMessage->createdAt->getTimestamp())
            );
        } catch (PDOException $e) {
            $this->messageRepository->rollbackTransaction();
        }

        $this->handleSendMessageAnalyticsEvent(
            user: $user,
            chatMessage: $chatMessage,
            roomGuid: $roomGuid
        );

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
     * @param User $user
     * @param int $first
     * @param string|null $after
     * @param string|null $before
     * @param bool $hasMore
     * @return array<ChatMessageNode>
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function getMessages(
        int $roomGuid,
        User $user,
        int $first = 12,
        ?string $after = null,
        ?string $before = null
    ): array {
        if (!$this->roomRepository->isUserMemberOfRoom(
            roomGuid: $roomGuid,
            user: $user,
            targetStatuses: [
                ChatRoomMemberStatusEnum::ACTIVE->name,
                ChatRoomMemberStatusEnum::INVITE_PENDING->name
            ]
        )) {
            throw new GraphQLException(message: "You are not a member of this room", code: 403);
        }

        ['messages' => $messages, 'hasMore' => $hasMore] = $this->messageRepository->getMessagesByRoom(
            roomGuid: $roomGuid,
            limit: $first,
            after: $after ? base64_decode($after, true) : null,
            before: $before ? base64_decode($before, true) : null,
        );

        usort($messages, fn (ChatMessage $a, ChatMessage $b): bool => $a->createdAt > $b->createdAt);

        return [
            'edges' => array_map(
                fn (ChatMessage $message) => new ChatMessageEdge(
                    node: new ChatMessageNode(
                        chatMessage: $message,
                        sender: new UserEdge(
                            user: $this->entitiesBuilder->single($message->senderGuid),
                            cursor: ''
                        )
                    ),
                    cursor: base64_encode((string) $message->guid)
                ),
                $messages
            ),
            'hasMore' => $hasMore
        ];
    }

    /**
     * Returns a single message
     * @param int $roomGuid
     * @param int $messageGuid
     * @param User|null $user
     * @return ChatMessage
     * @throws ChatMessageNotFoundException
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function getMessage(
        int $roomGuid,
        int $messageGuid,
        ?User $user = null,
        bool $skipPermissionCheck = false
    ): ChatMessage {
        if (
            !$skipPermissionCheck &&
            $user &&
            !$this->roomRepository->isUserMemberOfRoom(
                roomGuid: $roomGuid,
                user: $user,
                targetStatuses: [
                    ChatRoomMemberStatusEnum::ACTIVE->name,
                    ChatRoomMemberStatusEnum::INVITE_PENDING->name
                ]
            ) &&
            !$user->isAdmin()
        ) {
            throw new GraphQLException(message: "You are not a member of this room", code: 403);
        }

        return $this->messageRepository->getMessageByGuid($roomGuid, $messageGuid);
    }

    /**
     * @param int $roomGuid
     * @param int $messageGuid
     * @param User $loggedInUser
     * @return bool
     * @throws ChatMessageNotFoundException
     * @throws GraphQLException
     * @throws ServerErrorException
     */
    public function deleteMessage(
        int $roomGuid,
        int $messageGuid,
        User $loggedInUser
    ): bool {
        $message = $this->getMessage($roomGuid, $messageGuid, $loggedInUser);
        if (!$loggedInUser->isAdmin() && $message->senderGuid !== (int) $loggedInUser->getGuid()) {
            throw new GraphQLException(message: 'You are not allowed to delete this message', code: 403);
        }
        $this->messageRepository->beginTransaction();
        try {
            if (!$this->receiptService->deleteAllMessageReadReceipts($roomGuid, $messageGuid)) {
                $this->messageRepository->rollbackTransaction();
                throw new ServerErrorException(message: 'Failed to delete message', code: 500);
            }

            if ($message->messageType === ChatMessageTypeEnum::RICH_EMBED) {
                if (!$this->messageRepository->deleteRichEmbed($roomGuid, $messageGuid)) {
                    $this->messageRepository->rollbackTransaction();
                    throw new ServerErrorException(message: 'Failed to delete rich embed data for message', code: 500);
                }
            }

            if (!$this->messageRepository->deleteChatMessage(
                roomGuid: $roomGuid,
                messageGuid: $messageGuid
            )) {
                $this->messageRepository->rollbackTransaction();
                throw new ServerErrorException(message: 'Failed to delete message', code: 500);
            }

            $this->messageRepository->commitTransaction();
            return true;
        } catch (ServerErrorException $e) {
            throw new GraphQLException(message: 'Failed to delete message', code: 500);
        }
    }

    /**
     * Handle analytics event firing on message send.
     * @param User $user - the message sender.
     * @param ChatMessage $chatMessage - the message.
     * @param int $roomGuid - the room guid.
     * @return void
     */
    private function handleSendMessageAnalyticsEvent(
        User $user,
        ChatMessage $chatMessage,
        int $roomGuid
    ) {
        try {
            ['chatRooms' => $chatRooms] = $this->roomRepository->getRoomsByMember(
                user: $user,
                targetMemberStatuses: [
                    ChatRoomMemberStatusEnum::ACTIVE->name,
                    ChatRoomMemberStatusEnum::INVITE_PENDING->name
                ],
                limit: 1,
                roomGuid: $roomGuid
            );

            $chatRoom = $chatRooms[0] ?? throw new ChatRoomNotFoundException();

            $this->analyticsDelegate->onMessageSend(
                actor: $user,
                message: $chatMessage,
                chatRoom: $chatRoom->chatRoom
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
