<?php

namespace Spec\Minds\Core\Chat\Services;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Repositories\MessageRepository;
use Minds\Core\Chat\Repositories\RoomRepository;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class MessageServiceSpec extends ObjectBehavior
{
    private Collaborator $messageRepositoryMock;
    private Collaborator $roomRepositoryMock;
    private Collaborator $receiptServiceMock;
    private Collaborator $entitiesBuilderMock;

    public function let(
        MessageRepository $messageRepositoryMock,
        RoomRepository $roomRepositoryMock,
        ReceiptService $receiptServiceMock,
        EntitiesBuilder $entitiesBuilderMock,
    ) {
        $this->beConstructedWith($messageRepositoryMock, $roomRepositoryMock, $receiptServiceMock, $entitiesBuilderMock);
        $this->messageRepositoryMock = $messageRepositoryMock;
        $this->roomRepositoryMock  = $roomRepositoryMock;
        $this->receiptServiceMock = $receiptServiceMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MessageService::class);
    }

    public function it_should_submit_a_read_receipt_when_sending_a_message()
    {
        $roomGuid = (int) Guid::build();
        $user = new User();

        $this->roomRepositoryMock->isUserMemberOfRoom($roomGuid, $user)
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->messageRepositoryMock->beginTransaction()
            ->shouldBeCalled();

        $this->messageRepositoryMock->addMessage(Argument::type(ChatMessage::class))
            ->shouldBeCalled();

        $this->messageRepositoryMock->commitTransaction()
            ->shouldBeCalled();

        $this->receiptServiceMock->updateReceipt(Argument::type(ChatMessage::class), $user)
            ->shouldBeCalled()
            ->willReturn(true);

        $result = $this->addMessage(roomGuid: $roomGuid, user: $user, message: 'just for testing');
        $result->shouldBeAnInstanceOf(ChatMessageEdge::class);
    }
}
