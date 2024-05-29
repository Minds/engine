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
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    protected Collaborator $repositoryMock;

    public function let(Repository $repositoryMock)
    {
        $this->beConstructedWith($repositoryMock);
        $this->repositoryMock = $repositoryMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_sync_views()
    {
        $this->repositoryMock->beginTransaction()->shouldBeCalled();

        $this->repositoryMock->incrementViews(-1, 123, Argument::type(DateTime::class), 2)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repositoryMock->incrementViews(-1, 456, Argument::type(DateTime::class), 1)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repositoryMock->incrementViews(1, 789, Argument::type(DateTime::class), 1)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->repositoryMock->commitTransaction()->shouldBeCalled();

        $this->incrementViews(-1, 123, time());
        $this->incrementViews(-1, 123, time());
        $this->incrementViews(-1, 456, time());
        $this->incrementViews(1, 789, time());

        $this->flush();
        $this->flush();
    }

    public function it_should_call_to_increment_clicks(Boost $boost): void
    {
        $dateTime = new DateTime();
        $boostGuid = '123';

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn($boostGuid);

        $this->repositoryMock->incrementClicks($boostGuid, $dateTime)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->incrementClicks($boost, $dateTime)->shouldBe(true);
    }
}
