<?php

namespace Spec\Minds\Core\Boost\V3\Insights;

use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetAudiences;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Insights\Manager;
use Minds\Core\Boost\V3\Insights\Repository;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var Repository */
    private $repositoryMock;

    public function let(Repository $repositoryMock)
    {
        $this->beConstructedWith($repositoryMock);
        $this->repositoryMock = $repositoryMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_calculate_estimate()
    {
        $this->repositoryMock->getEstimate(BoostTargetAudiences::SAFE, BoostTargetLocation::NEWSFEED, BoostPaymentMethod::CASH)
            ->willReturn([
                '24h_bids' => 10,
                '24h_views' => 1000,
            ]);

        $estimate = $this->getEstimate(BoostTargetAudiences::SAFE, BoostTargetLocation::NEWSFEED, BoostPaymentMethod::CASH, 10, 1);
        $estimate['views']->shouldBeLike([
            'low' => 200,
            'high' => 1000,
        ]);
    }
}
