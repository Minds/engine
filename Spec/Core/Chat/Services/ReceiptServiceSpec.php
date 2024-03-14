<?php

namespace Spec\Minds\Core\Chat\Services;

use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Entities\ChatRoom;
use Minds\Core\Chat\Repositories\ReceiptRepository;
use Minds\Core\Chat\Services\ReceiptService;
use Minds\Core\Guid;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ReceiptServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;

    function let(
        ReceiptRepository $repositoryMock,
    )
    {
        $this->beConstructedWith($repositoryMock);
        $this->repositoryMock = $repositoryMock;
    }    

    function it_is_initializable()
    {
        $this->shouldHaveType(ReceiptService::class);
    }

    function it_should_update_last_timestamp_for_a_room(User $userMock)
    {
        $roomGuid = Guid::build();
        $userGuid = Guid::build();
        $messageGuid = Guid::build();

        $messageMock = new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $userGuid,
            plainText: 'not a real message'
        );

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->repositoryMock->updateReceipt($roomGuid, $messageGuid, $userGuid)
            ->shouldBeCalled()
            ->willReturn(true);
        
        $this->updateReceipt($messageMock, $userMock)
            ->shouldBe(true);
    }

    function it_should_return_false_if_update_last_timestamp_for_a_room_fails(User $userMock)
    {
        $roomGuid = Guid::build();
        $userGuid = Guid::build();
        $messageGuid = Guid::build();

        $messageMock = new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $userGuid,
            plainText: 'not a real message'
        );

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->repositoryMock->updateReceipt($roomGuid, $messageGuid, $userGuid)
            ->shouldBeCalled()
            ->willReturn(false);
        
        $this->updateReceipt($messageMock, $userMock)
            ->shouldBe(false);
    }

    function it_should_return_a_count_of_all_unread_messages()
    {
        $userGuid = (int) Guid::build();

        $user = new User();
        $user->set('guid', $userGuid);

        $this->repositoryMock->getAllUnreadMessagesCount($userGuid)
            ->willReturn(12);

        $this->getAllUnreadMessagesCount($user)
            ->shouldBe(12);
    }
}
