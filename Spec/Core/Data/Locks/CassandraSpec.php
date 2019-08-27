<?php

namespace Spec\Minds\Core\Data\Locks;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Locks\KeyNotSetupException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CassandraSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Data\Locks\Cassandra');
    }

    public function it_should_throw_if_calling_isLocked_but_no_key_is_set(Client $db)
    {
        $this->beConstructedWith($db);

        $this->shouldThrow(KeyNotSetupException::class)->during('isLocked');
    }

    public function it_should_check_if_its_locked(Client $db)
    {
        $this->beConstructedWith($db);

        $db->request(Argument::any())
            ->shouldBeCalled()
            ->willReturn([true]);

        $this->setKey('balance:123');

        $this->isLocked()->shouldReturn(true);
    }

    public function it_should_throw_if_calling_lock_but_no_key_is_set(Client $db)
    {
        $this->beConstructedWith($db);

        $this->shouldThrow(KeyNotSetupException::class)->during('lock');
    }


    public function it_should_lock(Client $db)
    {
        $this->beConstructedWith($db);

        $db->request(Argument::that(function ($query) {
            $query = $query->build();
            return $query['string'] === 'INSERT INTO locks(key) values(?) IF NOT EXISTS USING TTL ?'
                && $query['values'][0] === 'balance:123' && $query['values'][1] === 10;
        }))
            ->shouldBeCalled()
            ->willReturn([true]);

        $this->setKey('balance:123');
        $this->setTTL(10);

        $this->lock();
    }

    public function it_should_throw_if_calling_unlock_but_no_key_is_set(Client $db)
    {
        $this->beConstructedWith($db);

        $this->shouldThrow(KeyNotSetupException::class)->during('unlock');
    }

    public function it_should_unlock(Client $db)
    {
        $this->beConstructedWith($db);

        $db->request(Argument::that(function ($query) {
            $query = $query->build();
            return $query['string'] === 'DELETE FROM locks where key = ? IF EXISTS'
                && $query['values'][0] === 'balance:123';
        }))
            ->shouldBeCalled()
            ->willReturn([true]);

        $this->setKey('balance:123');
        $this->setTTL(10);

        $this->unlock();
    }
}
