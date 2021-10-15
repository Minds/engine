<?php

namespace Spec\Minds\Core\Security\RateLimits;

use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Data\Redis\Client;
use Minds\Core\Log\Logger;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class KeyValueLimiterSpec extends ObjectBehavior
{
    public $config;
    public $redis;
    public $logger;
    public $jwt;

    public function let(
        Config $config,
        Client $redis,
        Logger $logger,
        Jwt $jwt
    ) {
        $this->config = $config;
        $this->redis = $redis;
        $this->logger = $logger;
        $this->jwt = $jwt;

        $this->config->get('cypress')
            ->willReturn([
                'shared_key' => '123'
            ]);

        $this->jwt->setKey(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->jwt);
        
        $this->beConstructedWith($config, $redis, $logger, $jwt);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(KeyValueLimiter::class);
    }

    public function it_should_not_bypass_with_no_cookie()
    {
        $_COOKIE['rate_limit_bypass'] = null;
        $this->verifyBypass()->shouldBe(false);
    }

    public function it_should_not_bypass_with_a_non_decodable_cookie()
    {
        $_COOKIE['rate_limit_bypass'] = '123';
        
        $this->logger->warn(Argument::any())
            ->shouldBeCalled();

        $this->jwt->decode('123')
            ->shouldBeCalled()
            ->willThrow(new Exception('Invalid JWT'));

        $this->verifyBypass()->shouldBe(false);
    }

    public function it_should_bypass_with_a_decodable_cookie()
    {
        $_COOKIE['rate_limit_bypass'] = '123';
        
        $this->logger->warn(Argument::any())
            ->shouldBeCalled();

        $this->jwt->decode('123')
            ->shouldBeCalled()
            ->willReturn([ 'data' => 1626266716 ]);

        $this->verifyBypass()->shouldBe(false);
    }
}
