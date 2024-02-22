<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Security\Rbac\Models\Role;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\DeleteQuery;
use Selective\Database\InsertQuery;
use Selective\Database\SelectQuery;

class SiteMembershipRolesRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;
    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $loggerMock;
    private Collaborator $configMock;

    public function let(
        MySQLClient $mysqlClient,
        Logger      $logger,
        Config      $config,
        Connection  $mysqlMasterConnectionHandler,
        Connection  $mysqlReaderConnectionHandler,
        PDO         $mysqlMasterConnection,
        PDO         $mysqlReaderConnection,
    ): void {
        $this->mysqlHandlerMock = $mysqlClient;

        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($mysqlMasterConnection);
        $mysqlMasterConnectionHandler->getPdo()->willReturn($mysqlMasterConnection);
        $this->mysqlClientWriterHandlerMock = $mysqlMasterConnectionHandler;


        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($mysqlReaderConnection);
        $mysqlReaderConnectionHandler->getPdo()->willReturn($mysqlReaderConnection);
        $this->mysqlClientReaderHandlerMock = $mysqlReaderConnectionHandler;

        $this->loggerMock = $logger;
        $this->configMock = $config;

        $this->beConstructedThrough('buildForUnitTests', [
            $this->mysqlHandlerMock->getWrappedObject(),
            $this->configMock->getWrappedObject(),
            $this->loggerMock->getWrappedObject(),
            $this->mysqlClientWriterHandlerMock->getWrappedObject(),
            $this->mysqlClientReaderHandlerMock->getWrappedObject(),
        ]);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(SiteMembershipRolesRepository::class);
    }

    public function it_should_store_site_membership_roles(
        InsertQuery  $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->willReturn(1);

        $pdoStatementMock->execute(['role_id' => 1])
            ->shouldBeCalledOnce();

        $insertQueryMock->into('minds_site_membership_tiers_role_assignments')
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set(Argument::that(
            fn (array $cols): bool => $cols['tenant_id'] === 1 &&
            $cols['membership_tier_guid'] === 1 &&
            $cols['role_id']->getValue() === ':role_id'
        ))
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);
        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $this->storeSiteMembershipRoles(1, [new Role(1, 'role_id', [])])
            ->shouldReturn(true);
    }

    public function it_should_get_site_membership_roles(
        SelectQuery  $selectQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->willReturn(1);

        $pdoStatementMock->rowCount()->willReturn(1);
        $pdoStatementMock->execute()
            ->shouldBeCalledOnce();
        $pdoStatementMock->fetchAll()
            ->shouldBeCalledOnce()
            ->willReturn([['role_id' => 'role_id']]);

        $selectQueryMock->from('minds_site_membership_tiers_role_assignments')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns(['role_id'])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', '=', 1)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('membership_tier_guid', '=', 1)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $this->getSiteMembershipRoles(1)
            ->shouldBeArray();
    }

    public function it_should_delete_site_membership_roles(
        DeleteQuery  $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->willReturn(1);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce();

        $deleteQueryMock->from('minds_site_membership_tiers_role_assignments')
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('tenant_id', '=', 1)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->where('membership_tier_guid', '=', 1)
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $deleteQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->delete()
            ->shouldBeCalledOnce()
            ->willReturn($deleteQueryMock);

        $this->deleteSiteMembershipRoles(1)
            ->shouldReturn(true);
    }
}
