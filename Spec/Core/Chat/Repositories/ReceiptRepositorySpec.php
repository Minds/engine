<?php

namespace Spec\Minds\Core\Chat\Repositories;

use Minds\Core\Chat\Repositories\ReceiptRepository;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ReceiptRepositorySpec extends ObjectBehavior
{
    protected Collaborator $mysqlClientMock;
    protected Collaborator $mysqlMasterMock;
    protected Collaborator $mysqlReplicaMock;
    protected Collaborator $configMock;

    public function let(
        MySQL\Client $mysqlClientMock,
        Logger $loggerMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
        Config $configMock,
    ) {
        $this->beConstructedWith($mysqlClientMock, $configMock, $loggerMock);

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->shouldBeCalled()
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->shouldBeCalled()
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    
        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ReceiptRepository::class);
    }

    public function it_should_update_receipt(PDOStatement $stmtMock)
    {
        $roomGuid = (int) Guid::build();
        $userGuid = (int) Guid::build();
        $messageGuid = (int) Guid::build();

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($stmtMock);

        $this->configMock->get('tenant_id')
            ->willReturn(null);

        $stmtMock->execute([
            'tenant_id' => -1,
            'room_guid' => $roomGuid,
            'message_guid' => $messageGuid,
            'message_guid_2' => $messageGuid,
            'member_guid' => $userGuid,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateReceipt($roomGuid, $messageGuid, $userGuid)
            ->shouldBe(true);
    }

    public function it_should_update_receipt_for_tenants(PDOStatement $stmtMock)
    {
        $roomGuid = (int) Guid::build();
        $userGuid = (int) Guid::build();
        $messageGuid = (int) Guid::build();

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($stmtMock);

        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $stmtMock->execute([
            'tenant_id' => 1,
            'room_guid' => $roomGuid,
            'message_guid' => $messageGuid,
            'message_guid_2' => $messageGuid,
            'member_guid' => $userGuid,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateReceipt($roomGuid, $messageGuid, $userGuid)
            ->shouldBe(true);
    }

    public function it_should_return_total_unread_count_for_user(PDOStatement $stmtMock)
    {
        $userGuid = (int) Guid::build();

        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);


        $stmtMock->execute([
            'tenant_id' => -1,
            'member_guid' => $userGuid,
            'member_status' => 'ACTIVE'
        ])
            ->shouldBeCalled()
            ->willReturn(true);
        
        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'unread_count' => 12
            ]
        ]);

        $this->getAllUnreadMessagesCount($userGuid)
            ->shouldBe(12);
    }


    public function it_should_return_total_unread_count_for_user_on_tenant(PDOStatement $stmtMock)
    {
        $userGuid = (int) Guid::build();

        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);

        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $stmtMock->execute([
            'tenant_id' => 1,
            'member_guid' => $userGuid,
            'member_status' => 'ACTIVE'
        ])
            ->shouldBeCalled()
            ->willReturn(true);
        
        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'unread_count' => 12
            ]
        ]);

        $this->getAllUnreadMessagesCount($userGuid)
            ->shouldBe(12);
    }
}
