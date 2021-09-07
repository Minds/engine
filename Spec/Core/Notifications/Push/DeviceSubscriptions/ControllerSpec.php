<?php

namespace Spec\Minds\Core\Notifications\Push\DeviceSubscriptions;

use Minds\Core\Notifications\Push\DeviceSubscriptions\Controller;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ControllerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }
}
