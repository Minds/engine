<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Core\Referrals\Referral;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ReferralSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Referral::class);
    }
}
