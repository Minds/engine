<?php

namespace Spec\Minds\Core\Feeds\Seen;

use ArrayIterator;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Feeds\Seen\Repository;
use Minds\Core\Feeds\Seen\SeenEntity;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private $mysqlClientMock;
    private $pdoMock;

    public function let(Client $mysqlClientMock, PDO $pdoMock)
    {
        $this->beConstructedWith($mysqlClientMock);
        $this->mysqlClientMock = $mysqlClientMock;

        $this->pdoMock = $pdoMock;
        $mysqlClientMock->getConnection(Argument::any())
            ->willReturn($pdoMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_add(PDOStatement $stmtMock)
    {
        $this->pdoMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $ts = time();
        $seenEntity = new SeenEntity('pseudoid', '123', $ts);

        $stmtMock->execute([
            'pseudo_id' => 'pseudoid',
            'entity_guid' => '123',
            'last_seen_timestamp' => date('c', $ts),
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->add($seenEntity)
            ->shouldBe(true);
    }

    public function it_should_return_list(PDOStatement $stmtMock)
    {
        $this->pdoMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'pseudo_id' => 'pseudoid',
        ])
            ->shouldBeCalled();

        $ts = time();

        $stmtMock->getIterator()
            ->willYield([
                [
                    'pseudo_id' => 'pseudoid',
                    'entity_guid' => '123',
                    'last_seen_timestamp' => date('c', $ts),
                ]
            ]);

        $this->getList('pseudoid', 10)
            ->shouldYieldLike(new ArrayIterator([
                new SeenEntity('pseudoid', '123', $ts)
            ]));
    }
}
