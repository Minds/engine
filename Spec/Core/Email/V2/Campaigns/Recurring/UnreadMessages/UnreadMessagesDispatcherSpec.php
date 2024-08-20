<?php

namespace Spec\Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages;

use Minds\Core\Chat\Enums\ChatRoomMemberStatusEnum;
use Minds\Core\Chat\Enums\ChatRoomNotificationStatusEnum;
use Minds\Core\Chat\Repositories\ReceiptRepository;
use Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages\UnreadMessages;
use Minds\Entities\User;
use Minds\Core\Email\V2\Campaigns\Recurring\UnreadMessages\UnreadMessagesDispatcher;
use Minds\Core\Email\V2\Common\Message;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class UnreadMessagesDispatcherSpec extends ObjectBehavior
{
    private Collaborator $unreadMessagesEmailerMock;
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $multiTenantDataServiceMock;
    private Collaborator $receiptRepositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $loggerMock;

    public function let(
        UnreadMessages $unreadMessagesEmailerMock,
        MultiTenantBootService $multiTenantBootServiceMock,
        MultiTenantDataService $multiTenantDataServiceMock,
        ReceiptRepository $receiptRepositoryMock,
        EntitiesBuilder $entitiesBuilderMock,
        Logger $loggerMock
    ) {
        $this->beConstructedWith(
            $unreadMessagesEmailerMock,
            $multiTenantBootServiceMock,
            $multiTenantDataServiceMock,
            $receiptRepositoryMock,
            $entitiesBuilderMock,
            $loggerMock
        );
        $this->unreadMessagesEmailerMock = $unreadMessagesEmailerMock;
        $this->multiTenantBootServiceMock = $multiTenantBootServiceMock;
        $this->multiTenantDataServiceMock = $multiTenantDataServiceMock;
        $this->receiptRepositoryMock = $receiptRepositoryMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->loggerMock = $loggerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UnreadMessagesDispatcher::class);
    }

    public function it_should_dispatch_for_a_tenant(
        User $user1,
        User $user2
    ): void {
        $tenantId = 1;
        $createdAfterTimestamp = strtotime('-24 hours');

        $user1->getUsername()->willReturn('user1');
        $user2->getUsername()->willReturn('user2');

        $this->multiTenantBootServiceMock->bootFromTenantId($tenantId)
            ->shouldBeCalled();

        $this->receiptRepositoryMock->getAllUsersWithUnreadMessages(
            memberStatuses: [ ChatRoomMemberStatusEnum::ACTIVE, ChatRoomMemberStatusEnum::INVITE_PENDING ],
            createdAfterTimestamp: $createdAfterTimestamp,
            excludeRoomsWithNotificationStatus: [ ChatRoomNotificationStatusEnum::MUTED ]
        )
            ->shouldBeCalled()
            ->willReturn([
                ['user_guid' => '123'],
                ['user_guid' => '456'],
            ]);

        $this->entitiesBuilderMock->single('123')
            ->shouldBeCalled()
            ->willReturn($user1);

        $this->entitiesBuilderMock->single('456')
            ->shouldBeCalled()
            ->willReturn($user2);

        $this->unreadMessagesEmailerMock->withArgs(
            user: $user1,
            createdAfterTimestamp: $createdAfterTimestamp
        )
            ->shouldBeCalled()
            ->willReturn($this->unreadMessagesEmailerMock);

        $this->unreadMessagesEmailerMock->withArgs(
            user: $user2,
            createdAfterTimestamp: $createdAfterTimestamp
        )
            ->shouldBeCalled()
            ->willReturn($this->unreadMessagesEmailerMock);
    
        $this->unreadMessagesEmailerMock->send()
            ->shouldBeCalled();

        $this->unreadMessagesEmailerMock->send()
            ->shouldBeCalled();

        $this->dispatchForTenant($tenantId, $createdAfterTimestamp);
    }
}
