<?php

namespace Spec\Minds\Core\Boost\V3\Summaries;

use DateTime;
use Minds\Common\Urn;
use Minds\Core\Boost\V3\Common\ViewsScroller;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Enums\BoostTargetSuitability;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Summaries\Manager;
use Minds\Core\Boost\V3\Summaries\Repository;
use Minds\Core\Entities\Resolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    protected $viewsScrollerMock;
    protected $repositoryMock;
    protected $resolverMock;

    public function let(ViewsScroller $viewsScrollerMock, Repository $repositoryMock, Resolver $resolverMock)
    {
        $this->beConstructedWith($viewsScrollerMock, $repositoryMock, $resolverMock);
        $this->viewsScrollerMock = $viewsScrollerMock;
        $this->repositoryMock = $repositoryMock;
        $this->resolverMock = $resolverMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_sync_views()
    {
        $this->viewsScrollerMock->scroll(
            Argument::that(function ($timeUuid) {
                return true; // improve this
            }),
            Argument::that(function ($timeUuid) {
                return true; // improve this
            }),
        )
            ->shouldBeCalled()
            ->willYield([
                [
                    'campaign' => 'urn:boost:123'
                ],
                [
                    'campaign' => 'urn:boost:123'
                ],
                [
                    'campaign' => 'urn:boost:123'
                ],
                [
                    'campaign' => 'urn:boost:123'
                ],
                [
                    'campaign' => 'urn:boost:456'
                ]
            ]);

        $this->resolverMock->single(Argument::type(Urn::class))
            ->shouldBeCalled()
            ->willReturn(
                (new Boost(
                    entityGuid: '123',
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: BoostTargetSuitability::SAFE,
                    paymentMethod: BoostPaymentMethod::CASH,
                    paymentAmount: 10,
                    dailyBid: 10,
                    durationDays: 1
                ))->setGuid('123'),
                (new Boost(
                    entityGuid: '123',
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: BoostTargetSuitability::SAFE,
                    paymentMethod: BoostPaymentMethod::CASH,
                    paymentAmount: 10,
                    dailyBid: 10,
                    durationDays: 1
                ))->setGuid('123'),
                (new Boost(
                    entityGuid: '123',
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: BoostTargetSuitability::SAFE,
                    paymentMethod: BoostPaymentMethod::CASH,
                    paymentAmount: 10,
                    dailyBid: 10,
                    durationDays: 1
                ))->setGuid('123'),
                (new Boost(
                    entityGuid: '123',
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: BoostTargetSuitability::SAFE,
                    paymentMethod: BoostPaymentMethod::CASH,
                    paymentAmount: 10,
                    dailyBid: 10,
                    durationDays: 1
                ))->setGuid('123'),
                (new Boost(
                    entityGuid: '456',
                    targetLocation: BoostTargetLocation::NEWSFEED,
                    targetSuitability: BoostTargetSuitability::SAFE,
                    paymentMethod: BoostPaymentMethod::CASH,
                    paymentAmount: 10,
                    dailyBid: 10,
                    durationDays: 1
                ))->setGuid('456'),
            );

        $this->repositoryMock->beginTransaction()->shouldBeCalled();

        $this->repositoryMock->add('123', Argument::type(DateTime::class), 4)
            ->shouldBeCalled();

        $this->repositoryMock->add('456', Argument::type(DateTime::class), 1)
            ->shouldBeCalled();

        $this->repositoryMock->commitTransaction()->shouldBeCalled();

        $this->sync(new DateTime('midnight'));
    }
}
