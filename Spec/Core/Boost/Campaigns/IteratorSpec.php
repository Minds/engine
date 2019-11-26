<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Core\Boost\Campaigns\Iterator;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IteratorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Iterator::class);
    }
}
