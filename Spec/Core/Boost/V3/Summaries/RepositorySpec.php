<?php

namespace Spec\Minds\Core\Boost\V3\Summaries;

use DateTime;
use Minds\Core\Boost\V3\Summaries\Repository;
use Minds\Core\Data\MySQL\Client;
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

    public function let(Client $mysqlClientMock, PDO $pdoMock)
    {
        $this->beConstructedWith($mysqlClientMock);
        $this->mysqlClientMock = $mysqlClientMock;

        $mysqlClientMock->getConnection(Client::CONNECTION_MASTER)->willReturn($pdoMock);
        $this->mysqlMasterMock = $pdoMock;
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_write_to_table(PDOStatement $statement): void
    {
        $this->mysqlMasterMock->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $statement->execute([
            'guid' => '123',
            'date' => date('c', strtotime('midnight')),
            'views' => 125
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->add('123', new DateTime('midnight'), 125);
    }

    public function it_should_increment_clicks(PDOStatement $statement): void
    {
        $guid = '123';
        $dateTime = new DateTime();

        $this->mysqlMasterMock->prepare(Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn($statement);

        $statement->execute([
            'guid' => $guid,
            'date' => $dateTime->format('c'),
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->incrementClicks($guid, $dateTime);
    }
}
