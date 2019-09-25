<?php

namespace Spec\Minds\Core\Analytics\Dashboards;

use Minds\Core\Analytics\Dashboards\TrafficDashboard;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TrafficDashboardSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(TrafficDashboard::class);
    }
}
