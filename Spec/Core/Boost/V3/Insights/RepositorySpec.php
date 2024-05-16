<?php

namespace Spec\Minds\Core\Boost\V3\Insights;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Insights\Repository;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Di\Di;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    /** @var Client */
    protected $mysqlClientMock;

    /** @var PDO */
    protected $mysqlReplicaMock;

    public function let(Client $mysqlClientMock, PDO $pdoMock, Config $configMock)
    {
        $this->beConstructedWith($mysqlClientMock, $configMock, Di::_()->get('Logger'));
        $this->mysqlClientMock = $mysqlClientMock;

        $mysqlClientMock->getConnection(Client::CONNECTION_REPLICA)->willReturn($pdoMock);
        $this->mysqlReplicaMock = $pdoMock;

        $mysqlClientMock->getConnection(Client::CONNECTION_MASTER)->willReturn($pdoMock);
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_return_historic_cpms(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->quote(Argument::type('string'))->willReturn('');
        $this->mysqlReplicaMock->prepare(Argument::type('string'))->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->shouldBeCalled();
        
        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'cpm' => 1.23,
                ],
                [
                    'cpm' => 1.45,
                ],
                [
                    'cpm' => 1.42
                ]
            ]);

        $this->mysqlClientMock->bindValuesToPreparedStatement(Argument::any(), [
            'target_audience' => 1,
            'target_location' => 1,
            'payment_method' => 1,
            'from_timestamp' => date('c', strtotime('3 days ago'))
        
        ])
            ->shouldBeCalled();

        $this->getHistoricCpms(BoostTargetAudiences::SAFE, BoostTargetLocation::NEWSFEED, BoostPaymentMethod::CASH)
            ->shouldBe([
                1.23,
                1.45,
                1.42
            ]);
    }
}
