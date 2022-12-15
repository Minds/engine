<?php

namespace Spec\Minds\Core\Boost\V3\Insights;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Insights\Repository;
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

    public function it_should_return_estimate_from_db(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::type('string'))->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'target_audience' => 1,
            'target_location' => 1,
            'payment_method' => 1,
        ])->shouldBeCalled();
        
        $pdoStatementMock->fetchAll()
            ->willReturn([
                [
                    '24h_bids' => 10,
                    '24_views' => 1000,
                ]
            ]);

        $this->getEstimate(BoostTargetAudiences::SAFE, BoostTargetLocation::NEWSFEED, BoostPaymentMethod::CASH)
            ->shouldBe([
                '24h_bids' => 10,
                '24_views' => 1000,
            ]);
    }
}
