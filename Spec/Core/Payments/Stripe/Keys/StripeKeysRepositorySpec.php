<?php

namespace Spec\Minds\Core\Payments\Stripe\Keys;

use Minds\Core\Payments\Stripe\Keys\StripeKeysRepository;
use PhpSpec\ObjectBehavior;

class StripeKeysRepositorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(StripeKeysRepository::class);
    }
}
