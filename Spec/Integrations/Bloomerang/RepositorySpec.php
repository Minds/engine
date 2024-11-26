<?php

namespace Spec\Minds\Integrations\Bloomerang;

use Minds\Core\Config\Config;
use Minds\Integrations\Bloomerang\Repository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    private Collaborator $configMock;

    public function let(
        MySQLClient $mysqlClientMock,
        Config $configMock,
        Logger $loggerMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
    ) {
        $this->beConstructedWith($mysqlClientMock, $configMock, $loggerMock);

        $this->configMock = $configMock;

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->shouldBeCalled()
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->shouldBeCalled()
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_return_group_id_to_membership_guids(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);
        ;

        $this->configMock->get('tenant_id')->willReturn(1);

        $stmtMock->execute([
            'tenant_id' => 1
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'bloomerang_group_id' => 1,
                    'site_membership_guid' => 123,
                ],
                [
                    'bloomerang_group_id' => 2,
                    'site_membership_guid' => 456,
                ],
            ]);

        $result = $this->getGroupIdToSiteMembershipGuidMap();
        $result->shouldHaveCount(2);
    }
}
