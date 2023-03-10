<?php

namespace Spec\Minds\Core\Feeds\HideEntities;

use Minds\Core\Data\MySQL\Client;
use Minds\Core\Feeds\HideEntities\HideEntity;
use Minds\Core\Feeds\HideEntities\Repository;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $mysqlClientMock;

    /** @var PDO */
    protected $mysqlMasterMock;

    /** @var PDO */
    protected $mysqlReplicaMock;

    public function let(Client $mysqlClientMock, PDO $pdoMock)
    {
        $this->beConstructedWith($mysqlClientMock);
        $this->mysqlClientMock = $mysqlClientMock;

        $mysqlClientMock->getConnection(Client::CONNECTION_MASTER)->willReturn($pdoMock);
        $this->mysqlMasterMock = $pdoMock;

        $mysqlClientMock->getConnection(Client::CONNECTION_REPLICA)->willReturn($pdoMock);
        $this->mysqlReplicaMock = $pdoMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add_hidden_entity(PDOStatement $pdoStmtMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStmtMock);

        $pdoStmtMock->execute([
            'user_guid' => '1234',
            'entity_guid' => '1235',
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $hideEntity = new HideEntity('1234', '1235');

        $this->add($hideEntity)->shouldBe(true);
    }

    public function it_should_return_count(PDOStatement $pdoStmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStmtMock);

        $pdoStmtMock->execute([
            'user_guid' => '1234',
        ])
            ->shouldBeCalled();

        $pdoStmtMock->fetchAll()
            ->willReturn([
                [
                    'c' => 10
                ]
            ]);

        $this->count('1234')->shouldBe(10);
    }

    public function it_should_return_count_with_gt_time(PDOStatement $pdoStmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStmtMock);

        $pdoStmtMock->execute([
            'user_guid' => '1234',
            'gt' => '2022-12-22T00:00:00+00:00'
        ])
            ->shouldBeCalled();

        $pdoStmtMock->fetchAll()
            ->willReturn([
                [
                    'c' => 10
                ]
            ]);

        $this->count('1234', 1671667200)->shouldBe(10);
    }
}
