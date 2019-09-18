<?php

namespace Spec\Minds\Core\Pro\Domain;

use Minds\Common\Cookie;
use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Pro\Domain\Security;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SecuritySpec extends ObjectBehavior
{
    /** @var Cookie */
    protected $cookie;

    /** @var Jwt */
    protected $jwt;

    /** @var Config */
    protected $config;

    public function let(
        Cookie $cookie,
        Jwt $jwt,
        Config $config
    ) {
        $this->cookie = $cookie;
        $this->jwt = $jwt;
        $this->config = $config;

        $this->beConstructedWith($cookie, $jwt, $config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Security::class);
    }

    public function it_should_set_up()
    {
        $this->jwt->randomString()
            ->shouldBeCalled()
            ->willReturn('~random~');

        $this->config->get('oauth')
            ->shouldBeCalled()
            ->willReturn([
                'encryption_key' => 'phpspec'
            ]);

        $this->jwt->setKey('phpspec')
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->jwt->encode(Argument::type('array'), Argument::type('int'), Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn('~encoded~');

        $this->cookie->setName(Security::JWT_COOKIE_NAME)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setName(Security::XSRF_COOKIE_NAME)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setValue('~encoded~')
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setValue('~random~')
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setExpire(Argument::type('int'))
            ->shouldBeCalledTimes(2)
            ->willReturn($this->cookie);

        $this->cookie->setPath('/')
            ->shouldBeCalledTimes(2)
            ->willReturn($this->cookie);

        $this->cookie->setHttpOnly(false)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->cookie);

        $this->cookie->create()
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        $this
            ->setUp('phpspec.test')
            ->shouldReturn('~encoded~');
    }
}
