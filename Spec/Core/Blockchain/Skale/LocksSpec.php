<?php

namespace Spec\Minds\Core\Blockchain\Skale;

use Minds\Core\Blockchain\Skale\Locks;
use PhpSpec\ObjectBehavior;
use Minds\Core\Data\Locks\Redis as RedisLocks;

class LocksSpec extends ObjectBehavior
{
    /** @var RedisLocks */
    private $locks;

    public function let(
        RedisLocks $locks
    ) {
        $this->locks = $locks;
        
        $this->beConstructedWith($locks);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Locks::class);
    }

    public function it_should_check_if_is_locked()
    {
        $guid = '123';
        $expectedCacheKey = "skale:balance:$guid";
        
        $this->locks->setKey($expectedCacheKey)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        
        $this->locks->isLocked()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->isLocked($guid)->shouldBe(true);
    }

    public function it_should_check_if_is_NOT_locked()
    {
        $guid = '123';
        $expectedCacheKey = "skale:balance:$guid";
        
        $this->locks->setKey($expectedCacheKey)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        
        $this->locks->isLocked()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->isLocked($guid)->shouldBe(false);
    }

    public function it_should_lock_with_default_ttl()
    {
        $guid = '123';
        $expectedCacheKey = "skale:balance:$guid";
        
        $this->locks->setKey($expectedCacheKey)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        
        $this->locks->setTtl(120)
            ->shouldBeCalled()
            ->willReturn($this->locks);

        $this->locks->lock()
            ->shouldBeCalled()
            ->willReturn("1");

        $this->lock($guid)->shouldBe("1");
    }

    public function it_should_lock_with_non_default_ttl()
    {
        $guid = '123';
        $expectedCacheKey = "skale:balance:$guid";
        $ttl = 300;

        $this->locks->setKey($expectedCacheKey)
            ->shouldBeCalled()
            ->willReturn($this->locks);
        
        $this->locks->setTtl($ttl)
            ->shouldBeCalled()
            ->willReturn($this->locks);

        $this->locks->lock()
            ->shouldBeCalled()
            ->willReturn("1");

        $this->lock($guid, $ttl)->shouldBe("1");
    }

    public function it_should_unlock()
    {
        $guid = '123';
        $expectedCacheKey = "skale:balance:$guid";

        $this->locks->setKey($expectedCacheKey)
            ->shouldBeCalled()
            ->willReturn($this->locks);

        $this->locks->unlock()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->unlock($guid)->shouldBe(true);
    }
}
