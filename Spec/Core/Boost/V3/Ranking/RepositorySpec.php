<?php

namespace Spec\Minds\Core\Boost\V3\Ranking;

use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Ranking\BoostRanking;
use Minds\Core\Boost\V3\Ranking\BoostShareRatio;
use Minds\Core\Boost\V3\Ranking\Repository;
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
    protected $mysqlMasterMock;

    /** @var PDO */
    protected $mysqlReplicaMock;

    public function let(Client $mysqlClientMock, PDO $pdoMock, Config $configMock)
    {
        $this->beConstructedWith($mysqlClientMock, $configMock, Di::_()->get('Logger'));
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

    public function it_should_save_boost_ranking(PDOStatement $pdoStmtMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStmtMock);

        $pdoStmtMock->execute([
            'tenant_id' => -1,
            'guid' => '1234',
            'ranking_open' => 1.5,
            'ranking_safe' => 0.5,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $boostRanking = new BoostRanking(-1, '1234');
        $boostRanking->setRank(BoostTargetAudiences::CONTROVERSIAL, 1.5)
            ->setRank(BoostTargetAudiences::SAFE, 0.5);

        $this->addBoostRanking($boostRanking)->shouldBe(true);
    }

    public function it_should_return_boost_ratios(PDOStatement $pdoStmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStmtMock);

        $pdoStmtMock->execute()->shouldBeCalled();

        $pdoStmtMock->fetchAll()->willReturn([
            [
                'tenant_id' => -1,
                'guid' => '1234',
                'share_ratio_open_audience' => 0.5,
                'share_ratio_safe_audience' => 0.75,
                'target_location' => BoostTargetLocation::NEWSFEED,
                'target_suitability' => 1
            ],
            [
                'tenant_id' => 123,
                'guid' => '1235',
                'share_ratio_open_audience' => 0.5,
                'share_ratio_safe_audience' => 0.25,
                'target_location' => BoostTargetLocation::NEWSFEED,
                'target_suitability' => 1
            ],
        ]);

        $this->getBoostShareRatios()
            ->shouldYieldLike(new \ArrayIterator([
                new BoostShareRatio(
                    tenantId: -1,
                    guid: '1234',
                    targetAudienceShares: [
                        BoostTargetAudiences::CONTROVERSIAL => 0.5,
                        BoostTargetAudiences::SAFE => 0.75
                    ],
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: 1
                ),
                new BoostShareRatio(
                    tenantId: 123,
                    guid: '1235',
                    targetAudienceShares: [
                        BoostTargetAudiences::CONTROVERSIAL => 0.5,
                        BoostTargetAudiences::SAFE => 0.25
                    ],
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: 1
                ),
            ]));
    }

    public function it_should_return_single_boost_ratio(PDOStatement $pdoStmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStmtMock);

        $pdoStmtMock->execute(['guid' => '1234'])->shouldBeCalled();

        $pdoStmtMock->fetchAll()->willReturn([
            [
                'tenant_id' => -1,
                'guid' => '1234',
                'share_ratio_open_audience' => 0.5,
                'share_ratio_safe_audience' => 0.75,
                'target_location' => BoostTargetLocation::NEWSFEED,
                'target_suitability' => 1
            ],
        ]);

        $this->getBoostShareRatiosByGuid('1234')
            ->shouldBeLike(
                new BoostShareRatio(
                    tenantId: -1,
                    guid: '1234',
                    targetAudienceShares: [
                        BoostTargetAudiences::CONTROVERSIAL => 0.5,
                        BoostTargetAudiences::SAFE => 0.75
                    ],
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: 1
                ),
            );
    }
}
