<?php

namespace Spec\Minds\Core\Boost\Network;

use PhpSpec\ObjectBehavior;

class MetricsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Boost\Network\Metrics');
    }

    public function it_should_get_backlog_count()
    {
        $this->getBacklogCount('newsfeed', '123')->shouldReturn(-1);
    }

    public function it_should_get_priority_backlog_count()
    {
        $this->getPriorityBacklogCount('newsfeed', '123')->shouldReturn(-1);
    }

    public function it_should_get_backlog_impressions_sum()
    {
        $this->getBacklogImpressionsSum('newsfeed')->shouldReturn(-1);
    }
}
