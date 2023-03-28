<?php

namespace Spec\Minds\Core\Referrals;

use Minds\Common\Cookie;
use Minds\Core\Referrals\ReferralCookie;
use Zend\Diactoros\ServerRequest;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ReferralCookieSpec extends ObjectBehavior
{
    /** @var Cookie */
    private Collaborator $cookie;

    public function let(
        Cookie $cookie
    ) {
        $this->cookie = $cookie;
        $this->beConstructedWith($this->cookie);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ReferralCookie::class);
    }

    public function it_should_set_a_referral_cookie_from_a_referral_param()
    {
        $referrer = 'mark';
        $_COOKIE['referrer'] = null;

        $this->cookie->setName('referrer')
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setValue($referrer)
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setExpire(Argument::that(function($arg) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->setPath('/')
            ->shouldBeCalled()
            ->willReturn($this->cookie);

        $this->cookie->create()
            ->shouldBeCalled();

        $this->withRouterRequest(
            (new ServerRequest())
                ->withQueryParams(['referrer' => $referrer])
        )->create();
    }

    public function it_should_not_set_a_referral_cookie_when_no_request_is_set()
    {
        $this->cookie->setName('referrer')
            ->shouldNotBeCalled();

        $this->create();
    }

    public function it_should_not_set_cookie_if_no_param_is_present()
    {
        $this->cookie->setName('referrer')
            ->shouldNotBeCalled();

        $this->withRouterRequest((new ServerRequest()));

        $this->create();
    }
}
