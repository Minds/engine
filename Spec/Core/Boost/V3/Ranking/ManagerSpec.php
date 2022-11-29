<?php

namespace Spec\Minds\Core\Boost\V3\Ranking;

use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Ranking\BoostShareRatio;
use Minds\Core\Boost\V3\Ranking\Manager;
use Minds\Core\Boost\V3\Ranking\Repository;
use Minds\Core\Data\Cassandra\Scroll;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    private $repositoryMock;

    /** @var Scroll */
    private $scrollMock;

    public function let(Repository $repositoryMock, Scroll $scrollMock)
    {
        $this->beConstructedWith($repositoryMock, $scrollMock);
        $this->repositoryMock = $repositoryMock;
        $this->scrollMock = $scrollMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_work_out_ranks()
    {
        $this->repositoryMock->getBoostShareRatios()
            ->willYield([
                (new BoostShareRatio(
                    guid: "1234",
                    targetAudienceShares: [
                        BoostTargetAudiences::OPEN => 0.5,
                        BoostTargetAudiences::SAFE => 0.25,
                    ],
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: 1, // SAFE
                ))
            ]);

        $this->scrollMock->request(Argument::any())
            ->willYield([]);

        $this->repositoryMock->addBoostRanking(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->calculateRanks();
    }
}
