<?php

namespace Spec\Minds\Core\Security\RateLimits;

use Minds\Core\Security\RateLimits\InteractionsLimiter;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Core\Security\RateLimits\RateLimit;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class InteractionsLimiterSpec extends ObjectBehavior
{
    public $kvLimiter;

    public function let(
        KeyValueLimiter $kvLimiter,
    ) {
        $this->kvLimiter = $kvLimiter;
        $this->beConstructedWith($this->kvLimiter);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(InteractionsLimiter::class);
    }

    public function it_should_check_and_increment()
    {
        $interaction = 'comment';
        $userGuid = 'guid';

        $this->kvLimiter
            ->setKey($interaction)->shouldBeCalled()->willReturn($this->kvLimiter);
        $this->kvLimiter
            ->setValue($userGuid)->shouldBeCalled()->willReturn($this->kvLimiter);
        $this->kvLimiter
            // todo specific argument
            ->setRateLimits([
                (new RateLimit)
                    ->setKey('comment')
                    ->setSeconds(300)
                    ->setMax(75),
                (new RateLimit)
                    ->setKey('comment')
                    ->setSeconds(86400)
                    ->setMax(500),
            ])->shouldBeCalled()->willReturn($this->kvLimiter);
        $this->kvLimiter
            ->checkAndIncrement()->shouldBeCalled();
        
        // $this->shouldNotThrow()->duringCheckAndIncrement($interaction, $userGuid);
        $this->checkAndIncrement($userGuid, $interaction);
    }

    public function it_should_return_remaining_attempts()
    {
        $interaction = 'comment';
        $userGuid = 'guid';

        $this->kvLimiter
            ->setKey($interaction)->shouldBeCalled()->willReturn($this->kvLimiter);
        $this->kvLimiter
            ->setValue($userGuid)->shouldBeCalled()->willReturn($this->kvLimiter);
        
        $this->kvLimiter
            ->setRateLimits([
                (new RateLimit)
                    ->setKey($interaction)
                    ->setSeconds(300)
                    ->setMax(75),
                (new RateLimit)
                    ->setKey($interaction)
                    ->setSeconds(86400)
                    ->setMax(500),
            ])->shouldBeCalled()->willReturn($this->kvLimiter);

        $this->kvLimiter
            ->getRateLimitsWithRemainings()->shouldBeCalled()->willReturn(
                [(new RateLimit)
                    ->setKey($interaction)
                    ->setSeconds(300)
                    ->setMax(75)
                    ->setRemaining(10),
                (new RateLimit)
                    ->setKey($interaction)
                    ->setSeconds(86400)
                    ->setMax(500)
                    ->setRemaining(20),]
            );
        
        $this->getRemainingAttempts($userGuid, $interaction)->shouldReturn(10);
    }
}
