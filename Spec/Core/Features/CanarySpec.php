<?php

namespace Spec\Minds\Core\Features;

use Minds\Common\Cookie;
use Minds\Core\Features\Canary;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CanarySpec extends ObjectBehavior
{
    /** @var Cookie */
    protected $cookie;

    public function let(
        Cookie $cookie
    ) {
        $this->cookie = $cookie;
        $this->beConstructedWith($cookie);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Canary::class);
    }

    public function it_should_set_cookie_enabled()
    {
        $this->cookie->setName('canary')
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setValue(1)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setExpire(0)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setSecure(true)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setHttpOnly(true)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setPath('/')
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->create()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setCookie(true)
            ->shouldReturn(true);
    }

    public function it_should_set_cookie_disabled()
    {
        $this->cookie->setName('canary')
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setValue(0)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setExpire(0)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setSecure(true)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setHttpOnly(true)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setPath('/')
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->create()
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setCookie(false)
            ->shouldReturn(true);
    }
}
