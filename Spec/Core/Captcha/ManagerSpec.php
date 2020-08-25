<?php

namespace Spec\Minds\Core\Captcha;

use Minds\Core\Captcha\Manager;
use Minds\Core\Captcha\Captcha as CaptchaModel;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var KeyValueLimiter */
    private $keyValueLimiter;

    public function let(KeyValueLimiter $keyValueLimiter)
    {
        $this->beConstructedWith(null, null, null, null, $keyValueLimiter);
        $this->keyValueLimiter = $keyValueLimiter;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_build_a_captcha()
    {
        $captcha = $this->build();
        $jwtToken = $captcha->getJwtToken();
        $jwtToken->shouldBeString();
    }

    public function it_should_verify_a_captcha()
    {
        $this->keyValueLimiter
            ->setKey('captcha-jwt')
            ->willReturn($this->keyValueLimiter);
        $this->keyValueLimiter
            ->setValue(Argument::any())
            ->willReturn($this->keyValueLimiter);
        $this->keyValueLimiter
            ->setMax(1)
            ->willReturn($this->keyValueLimiter);
        $this->keyValueLimiter
            ->setSeconds(300)
            ->willReturn($this->keyValueLimiter);
        $this->keyValueLimiter
            ->checkAndIncrement()
            ->willReturn(true);

        $captcha = $this->build('abfu21')->getWrappedObject();
        $captcha->setClientText('abfu21');
        $this->verify($captcha)
            ->shouldBe(true);
    }

    public function it_should_verify_a_captcha_with_fail()
    {
        $this->keyValueLimiter
            ->setKey('captcha-jwt')
            ->willReturn($this->keyValueLimiter);
        $this->keyValueLimiter
            ->setValue(Argument::any())
            ->willReturn($this->keyValueLimiter);
        $this->keyValueLimiter
            ->setMax(1)
            ->willReturn($this->keyValueLimiter);
        $this->keyValueLimiter
            ->setSeconds(300)
            ->willReturn($this->keyValueLimiter);
        $this->keyValueLimiter
            ->checkAndIncrement()
            ->willReturn(true);

        $captcha = $this->build();
        $captcha->setClientText('abfu21');
        $this->verify($captcha)
            ->shouldBe(false);
    }
}
