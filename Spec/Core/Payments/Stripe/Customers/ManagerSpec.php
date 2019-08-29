<?php

namespace Spec\Minds\Core\Payments\Stripe\Customers;

use Minds\Core\Payments\Stripe\Customers\Manager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }
}
