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

    public function let(
        ReceiptRepository $repositoryMock,
    ) {
        $this->beConstructedWith($repositoryMock);
        $this->repositoryMock = $repositoryMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ReceiptService::class);
    }

    public function it_should_update_last_timestamp_for_a_room(User $userMock)
    {
        $roomGuid = 1234567890123456;
        $userGuid = 2234567890123456;
        $messageGuid = 3234567890123456;

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

    public function it_should_return_false_if_update_last_timestamp_for_a_room_fails(User $userMock)
    {
        $roomGuid = 1234567890123456;
        $userGuid = 2234567890123456;
        $messageGuid = 3234567890123456;

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

    public function it_should_return_a_count_of_all_unread_messages()
    {
        $userGuid = 1234567890123456;

        $user = new User();
        $user->set('guid', $userGuid);

        $this->repositoryMock->getAllUnreadMessagesCount($userGuid)
            ->willReturn(12);

        $this->getAllUnreadMessagesCount($user)
            ->shouldBe(12);
    }
}
