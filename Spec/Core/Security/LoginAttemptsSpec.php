<?php

namespace Spec\Minds\Core\Security;

use Minds\Core\Security\Exceptions\UserNotSetupException;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LoginAttemptsSpec extends ObjectBehavior
{
    private $keyValueLimiter;

    public function let(KeyValueLimiter $keyValueLimiter)
    {
        $this->beConstructedWith($keyValueLimiter);
        $this->keyValueLimiter = $keyValueLimiter;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Security\LoginAttempts');
    }

    // public function it_logFailure_should_throw_if_user_isnt_set()
    // {
    //     $this->shouldThrow(UserNotSetupException::class)->during('logFailure');
    // }

    // public function it_checkFailures_should_throw_if_user_isnt_set()
    // {
    //     $this->shouldThrow(UserNotSetupException::class)->during('checkFailures');
    // }

    // public function it_resetFailuresCount_should_throw_if_user_isnt_set()
    // {
    //     $this->shouldThrow(UserNotSetupException::class)->during('resetFailuresCount');
    // }

    // public function it_should_log_the_failure(User $user)
    // {
    //     $user->guid = '123';

    //     $user->get('guid')
    //         ->shouldBeCalled()
    //         ->willReturn('123');

    //     $user->getPrivateSetting(Argument::exact('login_failures'))
    //         ->shouldBeCalled()
    //         ->willReturn(0);

    //     $user->setPrivateSetting(Argument::that(function ($param) {
    //         return $param === 'login_failures' || $param === 'login_failure_1';
    //     }), Argument::any())
    //         ->shouldBeCalled()
    //         ->willReturn(true);


    //     $this->setUser($user);

    //     $this->logFailure()->shouldReturn(true);
    // }

    // public function it_should_return_false_if_attempts_limit_was_reached(User $user)
    // {
    //     $user->guid = '123';

    //     $user->get('guid')
    //         ->shouldBeCalled()
    //         ->willReturn('123');

    //     $user->getPrivateSetting(Argument::exact('login_failures'))
    //         ->shouldBeCalled()
    //         ->willReturn(5);

    //     $user->getPrivateSetting(Argument::containingString('login_failure_'))
    //         ->shouldBeCalled()
    //         ->willReturn(strtotime('-30 sec'));

    //     $this->setUser($user);

    //     $this->checkFailures()->shouldReturn(true);
    // }

    // public function it_should_return_true_if_attempts_limit_wasnt_reached(User $user)
    // {
    //     $user->guid = '123';

    //     $user->get('guid')
    //         ->shouldBeCalled()
    //         ->willReturn('123');

    //     $user->getPrivateSetting(Argument::exact('login_failures'))
    //         ->shouldBeCalled()
    //         ->willReturn(3);

    //     $this->setUser($user);

    //     $this->checkFailures()->shouldReturn(false);
    // }

    // public function it_should_reset_failures_count(User $user)
    // {
    //     $user->guid = '123';

    //     $user->get('guid')
    //         ->shouldBeCalled()
    //         ->willReturn('123');

    //     $user->getPrivateSetting(Argument::exact('login_failures'))
    //         ->shouldBeCalled()
    //         ->willReturn(3);

    //     $user->removePrivateSetting(Argument::containingString('login_failure_'))
    //         ->shouldBeCalledTimes(3)
    //         ->willReturn();

    //     $user->removePrivateSetting(Argument::exact('login_failures'))
    //         ->shouldBeCalled()
    //         ->willReturn();

    //     $this->setUser($user);

    //     $this->resetFailuresCount()->shouldReturn(true);
    // }
}
