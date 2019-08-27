<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Core\Referrals\Referral;
use PhpSpec\ObjectBehavior;

class ReferralSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Referral::class);
    }
}
