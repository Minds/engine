<?php

namespace Spec\Minds\Core\Data\Locks;

use Minds\Core\Config\Config;
use Minds\Core\Data\Redis\Client as RedisServer;
use Minds\Core\Data\Locks\KeyNotSetupException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RedisSpec extends ObjectBehavior
{
    private $redis;

    private $config;

    public function let(RedisServer $redis, Config $config)
    {
        $this->beConstructedWith($redis, $config);

        $this->redis = $redis;
        $this->config = $config;

        $config->get('redis')
            ->willReturn([
                'master' => 'phpspec-redis-fake',
            ]);

        $this->redis->connect(Argument::any())->shouldBeCalled();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Data\Locks\Redis');
    }

    public function it_should_throw_if_calling_isLocked_but_no_key_is_set()
    {
        $this->shouldThrow(KeyNotSetupException::class)->during('isLocked');
    }

    public function it_should_check_if_its_locked()
    {
        $this->redis->get("lock:balance:123")
            ->shouldBeCalled()
            ->willReturn(1);

        $this->setKey('balance:123');

        $this->isLocked()->shouldReturn(true);
    }

    public function it_should_throw_if_calling_lock_but_no_key_is_set()
    {
        $this->shouldThrow(KeyNotSetupException::class)->during('lock');
    }


    public function it_should_lock()
    {
        $this->redis->set("lock:balance:123", 1, [ 'ex' => 10, 'nx' => true ])
            ->shouldBeCalled()
            ->willReturn('OK');

        $this->setKey('balance:123');
        $this->setTTL(10);

        $this->lock();
    }

    public function it_should_throw_if_calling_unlock_but_no_key_is_set()
    {
        $this->shouldThrow(KeyNotSetupException::class)->during('unlock');
    }

    public function it_should_unlock()
    {
        $this->redis->delete("lock:balance:123")
            ->shouldBeCalled()
            ->willReturn('OK');

        $this->setKey('balance:123');
        $this->setTTL(10);

        $this->unlock();
    }
}
