<?php

namespace Spec\Minds\Core\Payments\Stripe;

use Minds\Core\Payments\Stripe\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
