<?php

namespace Spec\Minds\Core\Boost\V3\Insights;

use Minds\Core\Boost\V3\Insights\Controller;
use Minds\Core\Boost\V3\Insights\Manager;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;

class ControllerSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $managerMock;

    public function let(Manager $managerMock)
    {
        $this->beConstructedWith($managerMock);
        $this->managerMock = $managerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    public function it_should_request_estimate(ServerRequest $request)
    {
        $request->getQueryParams()
            ->willReturn([
                'daily_bid' => 10,
                'duration' => 1,
                'audience' => 1,
                'payment_method' => 1,
            ]);

        $this->managerMock->getEstimate(1, 1, 1, 10, 1)
            ->willReturn([]);

        $this->getEstimate($request);
    }
}
