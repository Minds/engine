<?php

namespace Spec\Minds\Core\Security\RateLimits;

use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Minds\Core\Security\RateLimits\RateLimit;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Data\Redis\Client as RedisServer;
use Minds\Core\Security\RateLimits\RateLimitExceededException;

class KeyValueLimiterSpec extends ObjectBehavior
{
    public $config;
    public $redis;
    public $logger;
    public $jwt;
    protected $redisServer;
    const FAKE_VALUE = 'value';

    public function let(
        RedisServer $redis,
        Config $config,
        Logger $logger,
        Jwt $jwt
    ) {
        $this->redis = $redis;
        $this->config = $config;
        $this->logger = $logger;
        $this->jwt = $jwt;

        $this->jwt->setKey(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->jwt);

        $this->beConstructedWith($redis, $config, $logger, $jwt);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(KeyValueLimiter::class);
    }

    public function it_should_allow_action_if_rate_limit_wasnt_hit()
    {
        $this->mockRateLimitCounts([0]);

        $rateLimit = (new RateLimit())->setKey(0)->setMax(10)->setSeconds(500);
        $this->setKey(0)
            ->setValue(self::FAKE_VALUE)
            ->setRateLimits([$rateLimit]);
        $this->mockIncrement($rateLimit);

        $this->shouldNotThrow()->duringCheckAndIncrement();
    }

    public function it_should_not_allow_action_if_rate_limit_was_hit()
    {
        $this->mockRateLimitCounts([10]);

        $this->setKey('key')
            ->setValue(self::FAKE_VALUE)
            ->setRateLimits([(new RateLimit())->setKey('fake_key')->setMax(10)->setSeconds(500)]);

        $this->shouldThrow(new RateLimitExceededException())->duringCheckAndIncrement();
    }

    public function it_should_support_legacy_rate_limits()
    {
        $this->mockRateLimitCounts([10]);

        /**
         * set a low max so the rate limit will hit
         */
        $this->setKey('key')
            ->setValue(self::FAKE_VALUE)
            ->setMax(10)
            ->setSeconds(500);

        $this->shouldThrow(new RateLimitExceededException())->duringCheckAndIncrement();

        /**
         * set a higher max so the rate limit won't get hit
         */
        $rateLimit = (new RateLimit())->setKey('key')->setMax(20)->setSeconds(500);
        
        $this->mockRateLimitCounts([10]);
        $this->setKey('key')
            ->setValue(self::FAKE_VALUE)
            ->setMax(20)
            ->setSeconds(500);
        $this->mockIncrement($rateLimit);

        $this->shouldNotThrow()->duringCheckAndIncrement();
    }

    public function it_should_not_bypass_with_no_cookie()
    {
        $_COOKIE['rate_limit_bypass'] = null;

        $rateLimit = (new RateLimit())->setKey(0)->setMax(10)->setSeconds(500);
        $this->mockRateLimitCounts([0]);
        $this->setKey(0)
            ->setValue(self::FAKE_VALUE)
            ->setRateLimits([$rateLimit]);
        $this->mockIncrement($rateLimit);

        $this->checkAndIncrement()->shouldReturn(true);
    }

    public function it_should_not_bypass_with_a_non_decodable_cookie()
    {
        $_COOKIE['rate_limit_bypass'] = '123';
        
        $this->logger->warn(Argument::any())
            ->shouldBeCalled();

        $this->jwt->decode('123')
            ->shouldBeCalled()
            ->willThrow(new Exception('Invalid JWT'));

        $rateLimit = (new RateLimit())->setKey(0)->setMax(10)->setSeconds(500);
        $this->mockRateLimitCounts([0]);
        $this->setKey(0)
            ->setValue(self::FAKE_VALUE)
            ->setRateLimits([$rateLimit]);
        $this->mockIncrement($rateLimit);

        $this->checkAndIncrement()->shouldReturn(true);
    }

    public function it_should_bypass_with_a_decodable_cookie()
    {
        $_COOKIE['rate_limit_bypass'] = '123';
        
        $this->logger->warn(Argument::any())
            ->shouldBeCalled();

        $this->jwt->decode('123')
            ->shouldBeCalled()
            ->willReturn([ 'data' => time(), 'timestamp_ms' => time() * 1000 ]);

        $this->redis->mget()
            ->shouldNotBeCalled();

        $this->checkAndIncrement()->shouldReturn(true);
    }

    public function it_should_return_rate_limits_with_remainings()
    {
        $rateLimit = (new RateLimit())->setKey(0)->setMax(10)->setSeconds(500);
        $counts = [2, 1];
        $this->mockRateLimitCounts($counts);
        $this->setKey(0)
            ->setValue(self::FAKE_VALUE)
            ->setRateLimits([$rateLimit, $rateLimit]);

        $this->getRateLimitsWithRemainings()
            ->shouldReturn([$rateLimit, $rateLimit]);
        // FIXME: how to check the attributes of the returned RateLimit instances?
        // foreach ($this->getRateLimitsWithRemainings() as $index => $rateLimitWithRemaining) {
        //     echo '===>';
        //     if ($rateLimitWithRemaining->getRemaining() !== $counts[$index]) {
        //         throw new Exception('no');
        //     }
        // }
    }

    private function mockRateLimitCounts(array $counts)
    {
        $this->redis->mget(Argument::any())
            ->shouldBeCalled()
            ->willReturn($counts);
    }

    private function mockIncrement(RateLimit $rateLimit)
    {
        $recordKey = $this->generateRecordKey($rateLimit);

        $this->redis->multi()
            ->shouldBeCalled()
            ->willReturn($this->redis);
        $this->redis->incr($recordKey)
            ->shouldBeCalled()
            ->willReturn($this->redis);
        $this->redis->expire($recordKey, $rateLimit->getSeconds())
            ->shouldBeCalled()
            ->willReturn($this->redis);
        $this->redis->exec()
            ->shouldBeCalled()
            ->willReturn(null);
    }

    private function generateRecordKey(RateLimit $rateLimit)
    {
        $resetPeriod = round(time() / $rateLimit->getSeconds());
        $fakeValue = self::FAKE_VALUE;
        return "ratelimit:{$rateLimit->getKey()}-{$fakeValue}:{$rateLimit->getSeconds()}:{$resetPeriod}";
    }
}
