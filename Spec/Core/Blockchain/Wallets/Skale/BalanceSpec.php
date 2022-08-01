<?php

namespace Spec\Minds\Core\Blockchain\Wallets\Skale;

use Minds\Core\Blockchain\Skale\Token;
use Minds\Core\Blockchain\Wallets\Skale\Balance;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class BalanceSpec extends ObjectBehavior
{
    private $token;
    private $cache;

    public function let(Token $token, PsrWrapper $cache)
    {
        $this->token = $token;
        $this->cache = $cache;

        $this->beConstructedWith($token, $cache);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Balance::class);
    }

    public function it_should_get_the_balance_from_cache(User $user)
    {
        $address = '0x123';

        $this->cache->get('skale:minds:balance:0x123')
            ->shouldBeCalled()
            ->willReturn(serialize(10 ** 18));

        $this->getTokenBalance($address)->shouldReturn((string) 10 ** 18);
    }

    public function it_should_get_the_balance_from_network(User $user)
    {
        $address = '0x123';
        $cacheKey = 'skale:minds:balance:0x123';
        $balance = '1000000000000000000';

        $this->cache->get($cacheKey)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->token->balanceOf($address)
            ->shouldBeCalled()
            ->willReturn($balance);

        $this->cache->set($cacheKey, serialize($balance), 60)
            ->shouldBeCalled();

        $this->getTokenBalance($address)->shouldReturn($balance);
    }

    public function it_should_not_store_null_values_in_cache(User $user)
    {
        $address = '0x123';
        $cacheKey = 'skale:minds:balance:0x123';
        $balance = null;

        $this->cache->get($cacheKey)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->token->balanceOf($address)
            ->shouldBeCalled()
            ->willReturn($balance);

        $this->cache->set($cacheKey, null, 60)
            ->shouldBeCalledTimes(0);

        $this->getTokenBalance($address)->shouldReturn(null);
    }
}
