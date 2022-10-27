<?php

namespace Spec\Minds\Core\Payments\Stripe\Checkout;

use Minds\Core\Payments\Stripe\Checkout\Controller;
use PhpSpec\ObjectBehavior;

class ControllerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }
}
