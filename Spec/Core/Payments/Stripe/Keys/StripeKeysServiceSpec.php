<?php

namespace Spec\Minds\Core\Payments\Stripe\Keys;

use Minds\Core\Payments\Stripe\Keys\StripeKeysService;
use PhpSpec\ObjectBehavior;

class StripeKeysServiceSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeKeysService::class);
    }
}
