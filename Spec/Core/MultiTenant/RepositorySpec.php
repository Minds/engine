<?php

namespace Spec\Minds\Core\MultiTenant;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Repository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private $mysqlClientMock;
    private $mysqlMasterMock;
    private $mysqlReplicaMock;

    public function let(MySQLClient $mysqlClient, PDO $mysqlMasterMock, PDO $mysqlReplicaMock)
    {
        $this->beConstructedWith($mysqlClient, Di::_()->get(Config::class), Di::_()->get('Logger'));
        $this->mysqlClientMock = $mysqlClient;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }


    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_return_expired_trial_tenants(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($stmtMock);
    
        $stmtMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                [
                    'tenant_id' => 1,
                    'plan' => 'TEAM',
                    'domain' => null,
                    'owner_guid' => 123,
                    'root_user_guid' => null,
                    'color_scheme' => null,
                    'federation_disabled' => null,
                ],
                [
                    'tenant_id' => 2,
                    'plan' => 'TEAM',
                    'domain' => null,
                    'owner_guid' => 123,
                    'root_user_guid' => null,
                    'color_scheme' => null,
                    'federation_disabled' => null,
                ]
                ]);

        $result = $this->getExpiredTrialsTenants();
        $result->shouldHaveCount(2);
        $result[0]->id->shouldBe(1);
        $result[1]->id->shouldBe(2);
    }

    public function it_should_return_suspended_tenants(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($stmtMock);
    
        $stmtMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                [
                    'tenant_id' => 1,
                    'plan' => 'TEAM',
                    'domain' => null,
                    'owner_guid' => 123,
                    'root_user_guid' => null,
                    'color_scheme' => null,
                    'federation_disabled' => null,
                ],
                [
                    'tenant_id' => 2,
                    'plan' => 'TEAM',
                    'domain' => null,
                    'owner_guid' => 123,
                    'root_user_guid' => null,
                    'color_scheme' => null,
                    'federation_disabled' => null,
                ]
                ]);

        $result = $this->getSuspendedTenants();
        $result->shouldHaveCount(2);
        $result[0]->id->shouldBe(1);
        $result[1]->id->shouldBe(2);
    }

    public function it_should_suspend_a_tenant(PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($stmtMock);

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $stmtMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);
        $this->suspendTenant(1)->shouldBe(true);
    }

    public function it_should_delete_a_tenant(PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())
            ->shouldBeCalled()
            ->willReturn($stmtMock);

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->inTransaction()
            ->willReturn(false);
        
        $this->mysqlMasterMock->beginTransaction()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->mysqlMasterMock->commit()
            ->shouldBeCalled()
            ->willReturn(true);

        $stmtMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);
        $this->deleteTenant(1)->shouldBe(true);
    }
}
