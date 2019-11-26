<?php

namespace Spec\Minds\Core\Boost\Network;

use Minds\Core\Boost\Network\Metrics;
use PhpSpec\ObjectBehavior;

class MetricsSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Metrics::class);
    }
}
