<?php

namespace Spec\Minds\Core\Boost\LiquiditySpot;

use Minds\Core\Boost\LiquiditySpot\Controller;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }
}
