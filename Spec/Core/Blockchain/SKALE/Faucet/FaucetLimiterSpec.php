<?php

namespace Spec\Minds\Core\Blockchain\SKALE\Faucet;

use Minds\Core\Blockchain\SKALE\Faucet\FaucetLimiter;
use PhpSpec\ObjectBehavior;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Core\Security\RateLimits\RateLimitExceededException;
use Minds\Entities\User;
use Prophecy\Argument;

class FaucetLimiterSpec extends ObjectBehavior
{
    /** @var KeyValueLimiter */
    protected $kvLimiter;

    public function let(
        KeyValueLimiter $kvLimiter,
    ) {
        $this->beConstructedWith($kvLimiter);
        $this->kvLimiter = $kvLimiter;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FaucetLimiter::class);
    }

    public function it_should_return_true_if_no_limits_exceeded(
        User $user
    ) {
        $user->getPhoneNumberHash()->willReturn('123');
        $user->getGuid()->willReturn('321');

        $this->kvLimiter->setKey('skale:faucet:321')->shouldBeCalledTimes(1)->willReturn($this->kvLimiter);
        $this->kvLimiter->setKey('skale:faucet:0x123')->shouldBeCalledTimes(1)->willReturn($this->kvLimiter);
        $this->kvLimiter->setKey('skale:faucet:123')->shouldBeCalledTimes(1)->willReturn($this->kvLimiter);

        $this->kvLimiter->setValue(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->setSeconds(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->setMax(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->checkAndIncrement()->willReturn(true);

        $this->checkAndIncrement($user, '0x123')->shouldReturn(true);
    }

    public function it_should_throw_error_out_if_a_limit_is_exceeded(
        User $user,
    ) {
        $user->getPhoneNumberHash()->willReturn('123');
        $user->getEthWallet()->willReturn('0x123');
        $user->getGuid()->willReturn('321');

        $this->kvLimiter->setKey('skale:faucet:321')->shouldBeCalledTimes(1)->willReturn($this->kvLimiter);

        $this->kvLimiter->setValue(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->setSeconds(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->setMax(Argument::any())->willReturn($this->kvLimiter);
        $this->kvLimiter->checkAndIncrement()->willThrow(new RateLimitExceededException());

        $this
            ->shouldThrow(RateLimitExceededException::class)
            ->during("checkAndIncrement", [$user, '0x123']);
    }
}
