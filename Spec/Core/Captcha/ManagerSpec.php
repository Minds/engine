<?php

namespace Spec\Minds\Core\Captcha;

use Minds\Core\Captcha\Manager;
use Minds\Core\Captcha\Captcha as CaptchaModel;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
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
        $captcha = $this->build('abfu21')->getWrappedObject();
        $captcha->setClientText('abfu21');
        $this->verify($captcha)
            ->shouldBe(true);
    }

    public function it_should_verify_a_captcha_with_fail()
    {
        $captcha = $this->build();
        $captcha->setClientText('abfu21');
        $this->verify($captcha)
            ->shouldBe(false);
    }
}
