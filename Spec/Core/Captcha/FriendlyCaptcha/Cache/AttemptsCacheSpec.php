<?php

namespace Spec\Minds\Core\Captcha\FriendlyCaptcha\Cache;

use Minds\Common\IpAddress;
use Minds\Core\Captcha\FriendlyCaptcha\Cache\AttemptsCache;
use Minds\Core\Data\cache\PsrWrapper;
use PhpSpec\ObjectBehavior;

class AttemptsCacheSpec extends ObjectBehavior
{
    /** @var PsrWrapper */
    private $cache;

    /** @var IpAddress */
    private $ipAddress;

    public function let(
        PsrWrapper $cache,
        IpAddress $ipAddress
    ) {
        $this->beConstructedWith($cache, $ipAddress);
        $this->cache = $cache;
        $this->ipAddress = $ipAddress;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AttemptsCache::class);
    }

    public function it_should_get_count_if_cache_has_no_attempts()
    {
        $ipHash = 'ipHash';

        $this->ipAddress->getHash()
            ->shouldBeCalled()
            ->willReturn($ipHash);

        $this->cache->get("friendly-captcha-attempts:$ipHash")
            ->shouldBeCalled()
            ->willReturn(null);

        $this->getCount()
            ->shouldBe(0);
    }

    public function it_should_get_count_if_cache_has_1_attempt()
    {
        $ipHash = 'ipHash';

        $this->ipAddress->getHash()
            ->shouldBeCalled()
            ->willReturn($ipHash);

        $this->cache->get("friendly-captcha-attempts:$ipHash")
            ->shouldBeCalled()
            ->willReturn('1');

        $this->getCount()
            ->shouldBe(1);
    }

    public function it_should_get_count_if_cache_has_many_attempts()
    {
        $ipHash = 'ipHash';

        $this->ipAddress->getHash()
            ->shouldBeCalled()
            ->willReturn($ipHash);

        $this->cache->get("friendly-captcha-attempts:$ipHash")
            ->shouldBeCalled()
            ->willReturn('99');

        $this->getCount()
            ->shouldBe(99);
    }

    public function it_should_increment_cache_to_1_when_count_is_0()
    {
        $ipHash = 'ipHash';
        $cacheKey = "friendly-captcha-attempts:$ipHash";

        $this->ipAddress->getHash()
            ->shouldBeCalled()
            ->willReturn($ipHash);

        $this->cache->get($cacheKey)
            ->shouldBeCalled()
            ->willReturn(null);
        
        $this->cache->set(
            $cacheKey,
            1,
            $this->CACHE_TIME_SECONDS
        )->shouldBeCalled();

        $this->increment()->shouldBe($this);
    }

    public function it_should_increment_cache_to_2_when_count_is_1()
    {
        $ipHash = 'ipHash';
        $cacheKey = "friendly-captcha-attempts:$ipHash";

        $this->ipAddress->getHash()
            ->shouldBeCalled()
            ->willReturn($ipHash);

        $this->cache->get($cacheKey)
            ->shouldBeCalled()
            ->willReturn('1');
        
        $this->cache->set(
            $cacheKey,
            2,
            $this->CACHE_TIME_SECONDS
        )->shouldBeCalled();

        $this->increment()->shouldBe($this);
    }

    public function it_should_increment_cache_to_100_when_count_is_99()
    {
        $ipHash = 'ipHash';
        $cacheKey = "friendly-captcha-attempts:$ipHash";

        $this->ipAddress->getHash()
            ->shouldBeCalled()
            ->willReturn($ipHash);

        $this->cache->get($cacheKey)
            ->shouldBeCalled()
            ->willReturn('99');
        
        $this->cache->set(
            $cacheKey,
            100,
            $this->CACHE_TIME_SECONDS
        )->shouldBeCalled();

        $this->increment()->shouldBe($this);
    }
}
