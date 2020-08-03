<?php

namespace Spec\Minds\Core\Wire\SupportTiers;

use Minds\Core\Wire\SupportTiers\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
