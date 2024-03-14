<?php

namespace Spec\Minds\Core\Chat\Controllers;

use Minds\Core\Chat\Controllers\ChatController;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Chat\Services\RoomService;
use Minds\Core\Chat\Types\ChatRoomEdge;
use Minds\Core\Guid;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ChatControllerSpec extends ObjectBehavior
{
    private Collaborator $roomServiceMock;
    private Collaborator $messageServiceMock;
    private Collaborator $receiptServiceMock;

    public function let(
        RoomService $roomServiceMock,
        MessageService $messageServiceMock,
        ReceiptService $receiptServiceMock,
    ) {
        $this->beConstructedWith($roomServiceMock, $messageServiceMock, $receiptServiceMock);
        $this->roomServiceMock = $roomServiceMock;
        $this->messageServiceMock = $messageServiceMock;
        $this->receiptServiceMock = $receiptServiceMock;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(ChatController::class);
    }

    public function it_should_submit_a_read_receipt(ChatRoomEdge $chatRoomMock)
    {
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

        $this->messageServiceMock->getMessage($roomGuid, $messageGuid)
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
}
