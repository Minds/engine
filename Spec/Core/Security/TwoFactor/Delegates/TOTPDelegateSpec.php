<?php

namespace Spec\Minds\Core\Security\TwoFactor\Delegates;

use Minds\Core\Security\TwoFactor\Delegates\TOTPDelegate;
use Minds\Core\Security\TwoFactor\TwoFactorRequiredException;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TOTPDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(TOTPDelegate::class);
    }

    public function it_should_require_2fa(User $user)
    {
        $this->shouldThrow(TwoFactorRequiredException::class)->duringOnRequireTwoFactor($user);
    }
}
