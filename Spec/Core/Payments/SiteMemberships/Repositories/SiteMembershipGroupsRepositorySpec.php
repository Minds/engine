<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Entities\Group;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Selective\Database\Connection;
use Selective\Database\DeleteQuery;
use Selective\Database\InsertQuery;
use Selective\Database\SelectQuery;

class SiteMembershipGroupsRepositorySpec extends ObjectBehavior
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
        $this->shouldBeAnInstanceOf(SiteMembershipGroupsRepository::class);
    }

    public function it_should_store_site_membership_groups(
        GroupNode    $groupNodeMock,
        Group        $groupMock,
        InsertQuery  $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->willReturn(1);

        $groupMock->getGuid()->willReturn('group_guid');
        $groupNodeMock->getEntity()->willReturn($groupMock);

        $pdoStatementMock->execute(['group_guid' => 'group_guid'])
            ->shouldBeCalledOnce();

        $insertQueryMock->into('minds_site_membership_tiers_group_assignments')
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set(Argument::that(
            fn (array $cols): bool => $cols['tenant_id'] === 1 &&
            $cols['membership_tier_guid'] === 1 &&
            $cols['group_guid']->getValue() === ':group_guid'
        ))
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);
        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $this->storeSiteMembershipGroups(1, [$groupNodeMock])
            ->shouldReturn(true);
    }

    public function it_should_get_site_membership_groups(
        SelectQuery  $selectQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->willReturn(1);

        $pdoStatementMock->rowCount()->willReturn(1);
        $pdoStatementMock->execute()
            ->shouldBeCalledOnce();
        $pdoStatementMock->fetchAll()
            ->shouldBeCalledOnce()
            ->willReturn([['group_guid' => 'group_guid']]);

        $selectQueryMock->from('minds_site_membership_tiers_group_assignments')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns(['group_guid'])
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

        $this->getSiteMembershipGroups(1)
            ->shouldBeArray();
    }

    public function it_should_delete_site_membership_groups(
        DeleteQuery  $deleteQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $this->configMock->get('tenant_id')->willReturn(1);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce();

        $deleteQueryMock->from('minds_site_membership_tiers_group_assignments')
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

        $this->deleteSiteMembershipGroups(1)
            ->shouldReturn(true);
    }
}
