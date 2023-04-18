<?php

namespace Spec\Minds\Core\Security\TwoFactor\Bypass;

use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Log\Logger;
use PhpSpec\ObjectBehavior;
use Minds\Core\Security\TwoFactor\Bypass\Manager;
use PhpSpec\Wrapper\Collaborator;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $config;
    private Collaborator $jwt;
    private Collaborator $logger;

    public function let(
        Config $config,
        Jwt $jwt,
        Logger $logger
    ) {
        $this->config = $config;
        $this->jwt = $jwt;
        $this->logger = $logger;
        $this->beConstructedWith($config, $jwt, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_true_for_valid_bypass()
    {
        $bypassKey = '~bypassKey~';
        $bypassCookieValue = '~bypassCookieValue~';
        $code = '927345';

        $_COOKIE['two_factor_bypass'] = $bypassCookieValue;

        $this->config->get('captcha')
            ->shouldBeCalled()
            ->willReturn(['bypass_key' => $bypassKey]);

        $this->jwt->setKey($bypassKey)
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->jwt->decode($bypassCookieValue)
            ->shouldBeCalled()
            ->willReturn(['data' => $code]);

        $this->canBypass($code)->shouldBe(true);
    }

    public function it_should_return_false_when_no_cookie_is_set(): void
    {
        $bypassKey = '~bypassKey~';
        $bypassCookieValue = '~bypassCookieValue~';
        $code = '927345';

        $_COOKIE['two_factor_bypass'] = null;

        $this->config->get('captcha')
            ->shouldNotBeCalled();

        $this->jwt->setKey($bypassKey)
            ->shouldNotBeCalled();

        $this->jwt->decode($bypassCookieValue)
            ->shouldNotBeCalled();

        $this->canBypass($code)->shouldBe(false);
    }

    public function it_should_return_false_if_decoded_value_is_invalid()
    {
        $bypassKey = '~bypassKey~';
        $bypassCookieValue = '~bypassCookieValue~';
        $code = '927345';
        $invalidCode = '123456';

        $_COOKIE['two_factor_bypass'] = $bypassCookieValue;

        $this->config->get('captcha')
            ->shouldBeCalled()
            ->willReturn(['bypass_key' => $bypassKey]);

        $this->jwt->setKey($bypassKey)
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->jwt->decode($bypassCookieValue)
            ->shouldBeCalled()
            ->willReturn(['data' => $invalidCode]);

        $this->canBypass($code)->shouldBe(false);
    }
}
