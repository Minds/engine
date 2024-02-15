<?php

namespace Spec\Minds\Core\Payments\SiteMemberships\PaywalledEntities;

use Minds\Core\Config\Config;
use Minds\Core\Payments\SiteMemberships\PaywalledEntities\PaywalledEntitiesRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class PaywalledEntitiesRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(
        Config $configMock,
        MySQLClient $mysqlClientMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock
    ) {
        $this->beConstructedWith($mysqlClientMock, $configMock, Di::_()->get('Logger'));

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PaywalledEntitiesRepository::class);
    }

    public function it_should_save_membership_mappings(PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->inTransaction()->willReturn(false);
        $this->mysqlMasterMock->beginTransaction()->willReturn(true);

        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");
        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($stmtMock);

        $stmtMock->execute([
            'entity_guid' => 123,
            'membership_guid' => 456
        ])->willReturn(true);

        $stmtMock->execute([
            'entity_guid' => 123,
            'membership_guid' => 789
        ])->willReturn(true);

        $this->mapMembershipsToEntity(123, [456, 789])
            ->shouldBe(true);
    }

    public function it_should_return_available_memberships_for_an_entity(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->quote(Argument::any())->willReturn("");
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);

        $stmtMock->execute([
            'entity_guid' => 123,
        ])->willReturn(true);

        $stmtMock->rowCount()->willReturn(3);

        $stmtMock->fetchAll(PDO::FETCH_COLUMN)->willReturn([1,2,3]);

        $this->getMembershipsFromEntity(123)
            ->shouldBe([
                1,2,3
            ]);
    }
}
