<?php

namespace Spec\Minds\Core\Chat\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use Minds\Core\Chat\Controllers\ChatController;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Enums\ChatRoomInviteRequestActionEnum;
use Minds\Core\Chat\Enums\ChatRoomRoleEnum;
use Minds\Core\Chat\Enums\ChatRoomTypeEnum;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Chat\Types\ChatMessageNode;
use Minds\Core\Chat\Types\ChatMessagesConnection;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Chat\Types\ChatRoomMemberEdge;
use Minds\Core\Chat\Types\ChatRoomMembersConnection;
use Minds\Core\Chat\Types\ChatRoomNode;
use Minds\Core\Chat\Types\ChatRoomsConnection;
use Minds\Core\Feeds\GraphQL\Types\UserEdge;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Guid;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use Spec\Minds\Common\Traits\CommonMatchers;

class ChatControllerSpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $roomServiceMock;
    private Collaborator $messageServiceMock;
    private Collaborator $receiptServiceMock;

    private ReflectionClass $chatRoomEdgeMockFactory;
    private ReflectionClass $chatRoomNodeMockFactory;

    private ReflectionClass $chatMessageEdgeMockFactory;
    private ReflectionClass $chatMessageNodeMockFactory;
    private ReflectionClass $chatMessageMockFactory;
    private ReflectionClass $userEdgeMockFactory;

    private ReflectionClass $chatRoomMemberEdgeMockFactory;
    private ReflectionClass $userNodeMockFactory;

    public function let(
        RoomService $roomServiceMock,
        MessageService $messageServiceMock,
        ReceiptService $receiptServiceMock,
    ) {
        $this->beConstructedWith($roomServiceMock, $messageServiceMock, $receiptServiceMock);
        $this->roomServiceMock = $roomServiceMock;
        $this->messageServiceMock = $messageServiceMock;
        $this->receiptServiceMock = $receiptServiceMock;

        /**
         * Chat room edge related factories
         */
        $this->chatRoomEdgeMockFactory = new ReflectionClass(ChatRoomEdge::class);
        $this->chatRoomNodeMockFactory = new ReflectionClass(ChatRoomNode::class);

        /**
         * Chat message edge related factories
         */
        $this->chatMessageEdgeMockFactory = new ReflectionClass(ChatMessageEdge::class);
        $this->chatMessageNodeMockFactory = new ReflectionClass(ChatMessageNode::class);
        $this->chatMessageMockFactory = new ReflectionClass(ChatMessage::class);
        $this->userEdgeMockFactory = new ReflectionClass(UserEdge::class);

        /**
         * Chat room member edge related factories
         */
        $this->chatRoomMemberEdgeMockFactory = new ReflectionClass(ChatRoomMemberEdge::class);
        $this->userNodeMockFactory = new ReflectionClass(UserNode::class);
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(ChatController::class);
    }

    public function it_should_submit_a_read_receipt(
        ChatRoomEdge $chatRoomMock
    ): void {
        $roomGuid = (int) Guid::build();
        $messageGuid = (int) Guid::build();

        $loggedInUser = new User();

        $this->roomServiceMock->getRoom($roomGuid, $loggedInUser)
            ->willReturn($chatRoomMock);

        $messageMock = new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: (int) Guid::build(),
            plainText: 'not a real message'
        );

        $this->messageServiceMock->getMessage($roomGuid, $messageGuid, $loggedInUser)
            ->willReturn($messageMock);

        $this->receiptServiceMock->updateReceipt($messageMock, $loggedInUser)
            ->willReturn(true);

        $response = $this->readReceipt($roomGuid, $messageGuid, $loggedInUser);
        $response->shouldBeAnInstanceOf(ChatRoomEdge::class);
        $response->unreadMessagesCount->shouldBe(0);
    }


    public function it_should_not_submit_a_read_receipt_if_not_in_room(ChatRoomEdge $chatRoomMock)
    {
        $roomGuid = (int) Guid::build();
        $messageGuid = (int) Guid::build();

        $loggedInUser = new User();

        $this->roomServiceMock->getRoom($roomGuid, $loggedInUser)
            ->willThrow(new ForbiddenException());

        $this->messageServiceMock->getMessage($roomGuid, $messageGuid)
            ->shouldNotBeCalled();

        $this->receiptServiceMock->updateReceipt(Argument::any(), $loggedInUser)
            ->shouldNotBeCalled();

        $this->shouldThrow(ForbiddenException::class)->duringReadReceipt($roomGuid, $messageGuid, $loggedInUser);
    }

    public function it_should_get_chat_room_list(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->getRoomsByMember(
            $loggedInUserMock,
            12,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'edges' => [
                    $this->generateChatRoomEdgeMock(
                        chatRoomNodeMock: $this->generateChatRoomNodeMock(
                            $this->generateChatRoomMock(
                                guid: 123,
                                createdByGuid: 456,
                                roomType: ChatRoomTypeEnum::ONE_TO_ONE,
                                createdAt: null
                            )
                        ),
                        cursor: base64_encode('123')
                    )
                ],
                'hasMore' => false
            ]);

        /**
         * @var ChatRoomsConnection $result
         */
        $result = $this->getChatRoomList(
            12,
            null,
            $loggedInUserMock
        );

        $result->shouldBeAnInstanceOf(ChatRoomsConnection::class);
        $result->getEdges()[0]->shouldBeAnInstanceOf(ChatRoomEdge::class);
        $result->getEdges()[0]->getCursor()->shouldEqual(base64_encode('123'));
        $result->getPageInfo()->shouldBeAnInstanceOf(PageInfo::class);
        $result->getPageInfo()->getHasNextPage()->shouldEqual(false);
        $result->getPageInfo()->getHasPreviousPage()->shouldEqual(false);
        $result->getPageInfo()->getStartCursor()->shouldEqual(null);
        $result->getPageInfo()->getEndCursor()->shouldEqual(base64_encode('123'));
    }

    private function generateChatRoomEdgeMock(
        ChatRoomNode $chatRoomNodeMock,
        string|null $cursor = null
    ): ChatRoomEdge {
        $mock = $this->chatRoomEdgeMockFactory->newInstanceWithoutConstructor();
        $this->chatRoomEdgeMockFactory->getProperty('node')->setValue($mock, $chatRoomNodeMock);
        $this->chatRoomEdgeMockFactory->getProperty('cursor')->setValue($mock, $cursor);

        return $mock;
    }

    private function generateChatRoomNodeMock(
        ChatRoom $chatRoomMock
    ): ChatRoomNode {
        $mock = $this->chatRoomNodeMockFactory->newInstanceWithoutConstructor();
        $this->chatRoomNodeMockFactory->getProperty('chatRoom')->setValue($mock, $chatRoomMock);

        return $mock;
    }

    private function generateChatRoomMock(
        int $guid,
        int $createdByGuid,
        ChatRoomTypeEnum $roomType = ChatRoomTypeEnum::ONE_TO_ONE,
        DateTimeInterface|null $createdAt = null
    ): ChatRoom {
        return new ChatRoom(
            guid: $guid,
            roomType: $roomType,
            createdByGuid: $createdByGuid,
            createdAt: $createdAt
        );
    }

    public function it_should_get_chat_room_guids(
        User $loggedInUserMock
    ): void {
        
        $result = $this->getChatRoomGuids($loggedInUserMock);

        $result->shouldBeArray();
        $result->shouldBeSameAs([]);
    }

    public function it_should_get_chat_room(
        User $loggedInUserMock
    ): void {
        $chatRoomEdgeMock = $this->generateChatRoomEdgeMock(
            chatRoomNodeMock: $this->generateChatRoomNodeMock(
                chatRoomMock: $this->generateChatRoomMock(
                    123,
                    456,
                    ChatRoomTypeEnum::ONE_TO_ONE,
                    createdAt: new DateTimeImmutable()
                )
            ),
            cursor: base64_encode('123')
        );

        $this->roomServiceMock->getRoom(
            123,
            $loggedInUserMock
        )
            ->shouldBeCalledOnce()
            ->willReturn($chatRoomEdgeMock);

        /**
         * @var ChatRoomEdge $result
         */
        $result = $this->getChatRoom(
            "123",
            $loggedInUserMock
        );

        $result->shouldBeAnInstanceOf(ChatRoomEdge::class);
        $result->getNode()->shouldBeAnInstanceOf(ChatRoomNode::class);
        $result->getNode()->chatRoom->shouldBeAnInstanceOf(ChatRoom::class);
        $result->getNode()->chatRoom->getGuid()->shouldEqual($chatRoomEdgeMock->getNode()->chatRoom->getGuid());
        $result->getNode()->chatRoom->getOwnerGuid()->shouldEqual($chatRoomEdgeMock->getNode()->chatRoom->getOwnerGuid());
        $result->getNode()->chatRoom->roomType->shouldEqual($chatRoomEdgeMock->getNode()->chatRoom->roomType);
        $result->getNode()->chatRoom->createdAt->shouldEqual($chatRoomEdgeMock->getNode()->chatRoom->createdAt);
    }

    public function it_should_get_chat_messages(
        User $loggedInUserMock
    ): void {
        $messageDateTimeMock = new DateTimeImmutable();
        $this->messageServiceMock->getMessages(
            123,
            $loggedInUserMock,
            12,
            null,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'edges' => [
                    $this->generateChatMessageEdgeMock(
                        chatMessageNodeMock: $this->generateChatMessageNodeMock(
                            chatMessageMock: $this->generateChatMessageMock(
                                roomGuid: 123,
                                messageGuid: 1,
                                senderGuid: 456,
                                messagePlainText: "test message",
                                createdAt: $messageDateTimeMock
                            ),
                            userEdgeMock: $this->generateUserEdgeMock(
                                userMock: $loggedInUserMock->getWrappedObject(),
                                cursorMock: ""
                            )
                        ),
                        cursorMock: base64_encode('123')
                    )
                ],
                'hasMore' => false
            ]);

        /**
         * @var ChatMessagesConnection $result
         */
        $result = $this->getChatMessages(
            "123",
            $loggedInUserMock,
            12,
            null,
            null
        );

        $result->shouldBeAnInstanceOf(ChatMessagesConnection::class);
        $result->getEdges()[0]->shouldBeAnInstanceOf(ChatMessageEdge::class);
        $result->getEdges()[0]->getNode()->shouldBeAnInstanceOf(ChatMessageNode::class);
        $result->getEdges()[0]->getNode()->getGuid()->shouldEqual("1");
        $result->getEdges()[0]->getNode()->getRoomGuid()->shouldEqual("123");
        $result->getEdges()[0]->getNode()->getPlainText()->shouldEqual("test message");
        $result->getEdges()[0]->getNode()->getTimeCreatedISO8601()->shouldEqual($messageDateTimeMock->format('c'));

        $result->getPageInfo()->shouldBeAnInstanceOf(PageInfo::class);
        $result->getPageInfo()->getHasPreviousPage()->shouldEqual(false);
        $result->getPageInfo()->getHasNextPage()->shouldEqual(false);
        $result->getPageInfo()->getStartCursor()->shouldEqual(base64_encode('123'));
        $result->getPageInfo()->getEndCursor()->shouldEqual(base64_encode('123'));
    }

    private function generateChatMessageEdgeMock(
        ChatMessageNode $chatMessageNodeMock,
        string $cursorMock = ""
    ): ChatMessageEdge {
        $mock = $this->chatMessageEdgeMockFactory->newInstanceWithoutConstructor();
        $this->chatMessageEdgeMockFactory->getProperty('node')->setValue($mock, $chatMessageNodeMock);
        $this->chatMessageEdgeMockFactory->getProperty('cursor')->setValue($mock, $cursorMock);

        return $mock;
    }

    private function generateChatMessageNodeMock(
        ChatMessage $chatMessageMock,
        UserEdge $userEdgeMock
    ): ChatMessageNode {
        $mock = $this->chatMessageNodeMockFactory->newInstanceWithoutConstructor();
        $this->chatMessageNodeMockFactory->getProperty('chatMessage')->setValue($mock, $chatMessageMock);
        $this->chatMessageNodeMockFactory->getProperty('sender')->setValue($mock, $userEdgeMock);

        return $mock;
    }

    private function generateChatMessageMock(
        int $roomGuid,
        int $messageGuid,
        int $senderGuid,
        string $messagePlainText = "",
        DateTimeInterface|null $createdAt = null
    ): ChatMessage {
        return new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $senderGuid,
            plainText: $messagePlainText,
            createdAt: $createdAt
        );
    }

    private function generateUserEdgeMock(
        User $userMock,
        string $cursorMock = ""
    ): UserEdge {
        $mock = $this->userEdgeMockFactory->newInstanceWithoutConstructor();
        $this->userEdgeMockFactory->getProperty('user')->setValue($mock, $userMock);
        $this->userEdgeMockFactory->getProperty('cursor')->setValue($mock, $cursorMock);

        return $mock;
    }

    public function it_should_get_chat_room_members(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->getRoomMembers(
            123,
            $loggedInUserMock,
            null,
            null,
            true
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'edges' => [
                    $this->generateChatRoomMemberEdgeMock(
                        userNodeMock: $this->generateUserNodeMock(
                            $loggedInUserMock->getWrappedObject()
                        ),
                        role: ChatRoomRoleEnum::OWNER,
                        cursor: base64_encode('123')
                    )
                ],
                'hasMore' => false
            ]);

        /**
         * @var ChatRoomMembersConnection $result
         */
        $result = $this->getChatRoomMembers(
            $loggedInUserMock,
            "123",
            null,
            null,
            null,
            true
        );

        $result->shouldBeAnInstanceOf(ChatRoomMembersConnection::class);
    }

    private function generateChatRoomMemberEdgeMock(
        UserNode $userNodeMock,
        ChatRoomRoleEnum $role = ChatRoomRoleEnum::OWNER,
        string $cursor = ""
    ): ChatRoomMemberEdge {
        $mock = $this->chatRoomMemberEdgeMockFactory->newInstanceWithoutConstructor();
        $this->chatRoomMemberEdgeMockFactory->getProperty('node')->setValue($mock, $userNodeMock);
        $this->chatRoomMemberEdgeMockFactory->getProperty('role')->setValue($mock, $role);
        $this->chatRoomMemberEdgeMockFactory->getProperty('cursor')->setValue($mock, $cursor);

        return $mock;
    }

    private function generateUserNodeMock(
        User $userMock
    ): UserNode {
        $mock = $this->userNodeMockFactory->newInstanceWithoutConstructor();
        $this->userNodeMockFactory->getProperty('user')->setValue($mock, $userMock);

        return $mock;
    }

    public function it_should_create_chat_room(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->createRoom(
            $loggedInUserMock,
            ['123', '456'],
            ChatRoomTypeEnum::ONE_TO_ONE,
            null,
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateChatRoomEdgeMock(
                    chatRoomNodeMock: $this->generateChatRoomNodeMock(
                        chatRoomMock: $this->generateChatRoomMock(
                            123,
                            456,
                            ChatRoomTypeEnum::ONE_TO_ONE,
                            new DateTimeImmutable()
                        )
                    ),
                    cursor: base64_encode('123')
                )
            );

        /**
         * @var ChatRoomEdge $result
         */
        $result = $this->createChatRoom(
            $loggedInUserMock,
            ['123', '456'],
            ChatRoomTypeEnum::ONE_TO_ONE
        );

        $result->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_create_a_group_chat_room(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->createRoom(
            $loggedInUserMock,
            [],
            ChatRoomTypeEnum::GROUP_OWNED,
            123,
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateChatRoomEdgeMock(
                    chatRoomNodeMock: $this->generateChatRoomNodeMock(
                        chatRoomMock: $this->generateChatRoomMock(
                            123,
                            456,
                            ChatRoomTypeEnum::GROUP_OWNED,
                            new DateTimeImmutable()
                        )
                    ),
                    cursor: base64_encode('123')
                )
            );

        /**
         * @var ChatRoomEdge $result
         */
        $result = $this->createChatRoom(
            $loggedInUserMock,
            [],
            ChatRoomTypeEnum::GROUP_OWNED,
            '123'
        );

        $result->shouldBeAnInstanceOf(ChatRoomEdge::class);
    }

    public function it_should_get_chat_room_members_count(): void
    {
        $this->roomServiceMock->getRoomTotalMembers(123)
            ->shouldBeCalledOnce()
            ->willReturn(3);

        $this->getChatRoomMembersCount(123)
            ->shouldEqual(3);
    }

    public function it_should_create_chat_message(
        User $loggedInUserMock
    ): void {
        $this->messageServiceMock->addMessage(
            123,
            $loggedInUserMock,
            "test message"
        )
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->generateChatMessageEdgeMock(
                    chatMessageNodeMock: $this->generateChatMessageNodeMock(
                        chatMessageMock: $this->generateChatMessageMock(
                            123,
                            1,
                            456,
                            "test message",
                            new DateTimeImmutable()
                        ),
                        userEdgeMock: $this->generateUserEdgeMock(
                            userMock: $loggedInUserMock->getWrappedObject()
                        )
                    ),
                    cursorMock: base64_encode('123')
                )
            );

        $this->createChatMessage(
            "test message",
            "123",
            $loggedInUserMock,
        )
            ->shouldBeAnInstanceOf(ChatMessageEdge::class);
    }

    public function it_should_get_chat_room_invite_requests(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->getRoomInviteRequestsByMember(
            $loggedInUserMock,
            12,
            null
        )
            ->shouldBeCalledOnce()
            ->willReturn([
                'edges' => [
                    $this->generateChatRoomEdgeMock(
                        chatRoomNodeMock: $this->generateChatRoomNodeMock(
                            $this->generateChatRoomMock(
                                guid: 123,
                                createdByGuid: 456,
                                roomType: ChatRoomTypeEnum::ONE_TO_ONE,
                                createdAt: null
                            )
                        ),
                        cursor: base64_encode('123')
                    )
                ],
                'hasMore' => false
            ]);

        /**]
         * @var ChatRoomsConnection $result
         */
        $result = $this->getChatRoominviteRequests(
            $loggedInUserMock,
            12,
            null
        );

        $result->shouldBeAnInstanceOf(ChatRoomsConnection::class);
    }

    public function it_should_get_total_room_invite_requests(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->getTotalRoomInviteRequestsByMember($loggedInUserMock)
            ->shouldBeCalledOnce()
            ->willReturn(3);

        $this->getTotalRoomInviteRequests(
            $loggedInUserMock
        )
            ->shouldEqual(3);
    }

    public function it_should_reply_to_room_invite_request(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->replyToRoomInviteRequest(
            $loggedInUserMock,
            123,
            ChatRoomInviteRequestActionEnum::ACCEPT
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->replyToRoomInviteRequest(
            "123",
            ChatRoomInviteRequestActionEnum::ACCEPT,
            $loggedInUserMock
        )
            ->shouldEqual(true);
    }

    public function it_should_get_chat_unread_messages_count(
        User $loggedInUserMock
    ): void {
        $this->receiptServiceMock->getAllUnreadMessagesCount($loggedInUserMock)
            ->shouldBeCalledOnce()
            ->willReturn(3);

        $this->getChatUnreadMessagesCount($loggedInUserMock)
            ->shouldEqual(3);
    }

    public function it_should_delete_chat_message(
        User $loggedInUserMock
    ): void {
        $this->messageServiceMock->deleteMessage(
            123,
            1,
            $loggedInUserMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->deleteChatMessage(
            "123",
            "1",
            $loggedInUserMock
        )
            ->shouldEqual(true);
    }

    public function it_should_delete_chat_room(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->deleteChatRoomByRoomGuid(
            123,
            $loggedInUserMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->deleteChatRoom(
            "123",
            $loggedInUserMock
        )
            ->shouldEqual(true);
    }

    public function it_should_leave_chat_room(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->leaveChatRoom(
            123,
            $loggedInUserMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->leaveChatRoom(
            "123",
            $loggedInUserMock
        )
            ->shouldEqual(true);
    }

    public function it_should_remove_member_from_chat_room(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->removeMemberFromChatRoom(
            123,
            456,
            $loggedInUserMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->removeMemberFromChatRoom(
            "123",
            "456",
            $loggedInUserMock
        )
            ->shouldEqual(true);
    }

    public function it_should_delete_chat_room_and_block_user(
        User $loggedInUserMock
    ): void {
        $this->roomServiceMock->deleteChatRoomAndBlockUser(
            123,
            $loggedInUserMock
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->deleteChatRoomAndBlockUser(
            "123",
            $loggedInUserMock
        )
            ->shouldEqual(true);
    }
}
