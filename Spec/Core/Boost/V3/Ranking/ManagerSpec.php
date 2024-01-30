<?php

namespace Spec\Minds\Core\Boost\V3\Ranking;

use Cassandra\Timeuuid;
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

    // public function it_should_work_out_ranks()
    // {
    //     $this->repositoryMock->getBoostShareRatios()
    //         ->willYield([
    //             (new BoostShareRatio(
    //                 guid: "1234",
    //                 targetAudienceShares: [
    //                     BoostTargetAudiences::CONTROVERSIAL => 0.5,
    //                     BoostTargetAudiences::SAFE => 0.25,
    //                 ],
    //                 targetLocation: BoostTargetLocation::NEWSFEED,
    //                 targetSuitability: 1, // SAFE
    //             )),
    //             (new BoostShareRatio(
    //                 guid: "1235",
    //                 targetAudienceShares: [
    //                     BoostTargetAudiences::CONTROVERSIAL => 0.5,
    //                     BoostTargetAudiences::SAFE => 0.25,
    //                 ],
    //                 targetLocation: BoostTargetLocation::NEWSFEED,
    //                 targetSuitability: 1, // SAFE
    //             ))
    //         ]);

    //     // initial views
    //     $this->scrollMock->request(Argument::that(function ($prepared) {
    //         $query = $prepared->build();
    //         return count($query['values']) === 4; // doesn't include lt time
    //     }))
    //         ->willYield([
    //             [
    //                 'uuid' => new Timeuuid(time()),
    //                 'campaign' => 'urn:boost:1234',
    //             ],
    //             [
    //                 'uuid' => new Timeuuid(time()),
    //                 'campaign' => 'urn:boost:1234',
    //             ],
    //             [
    //                 'uuid' => new Timeuuid(time()),
    //                 'campaign' => 'urn:boost:1235',
    //             ]
    //         ]);

    //     // cleanup views
    //     $this->scrollMock->request(Argument::that(function ($prepared) {
    //         $query = $prepared->build();
    //         return count($query['values']) === 5; // includes lt time
    //     }))
    //         ->willYield([

    //         ]);

    //     $this->repositoryMock->beginTransaction()->shouldBeCalled();

    //     // RANK saves for "1234"
    //     $this->repositoryMock->addBoostRanking(Argument::that(function ($boostRank) {
    //         return $boostRank->getGuid() === '1234'
    //             && $boostRank->getRanking(BoostTargetAudiences::CONTROVERSIAL) === 0.75
    //             && $boostRank->getRanking(BoostTargetAudiences::SAFE) === 0.375;
    //     }))
    //         ->shouldBeCalled()
    //         ->willReturn(true);

    //     // RANK saves for "1235" - should have a higher rank
    //     $this->repositoryMock->addBoostRanking(Argument::that(function ($boostRank) {
    //         return $boostRank->getGuid() === '1235'
    //             && $boostRank->getRanking(BoostTargetAudiences::CONTROVERSIAL) === 1.5
    //             && $boostRank->getRanking(BoostTargetAudiences::SAFE) === 0.75;
    //     }))
    //         ->shouldBeCalled()
    //         ->willReturn(true);

    //     $this->repositoryMock->commitTransaction()->shouldBeCalled();

    //     $this->calculateRanks();
    // }
}
