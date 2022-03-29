<?php

namespace Spec\Minds\Core\Blockchain\TokenPrices;

use Minds\Core\Blockchain\TokenPrices\Manager;
use Minds\Core\Blockchain\Uniswap;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    protected $uniswapClient;
    protected $config;
    protected $cache;

    public function let(Uniswap\Client $uniswapClient, Config $config, PsrWrapper $cache)
    {
        $this->beConstructedWith($uniswapClient, $config, $cache);
        $this->uniswapClient = $uniswapClient;
        $this->config = $config;
        $this->cache = $cache;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_prices()
    {
        $this->config->get('blockchain')
            ->willReturn([
                'token_address' => '0x...',
            ]);
        $this->uniswapClient->getTokenUsdPrices('0x...')
            ->willReturn([
                'eth' => '2521',
                'token' => '0.15',
            ]);

        $this->cache->get('blockchain::token-balance::0x...')
            ->willReturn(null);

        $this->cache->set('blockchain::token-balance::0x...', serialize([
            'eth' => '2521',
            'token' => '0.15',
        ]), 300)->shouldBeCalled();

        $this->getPrices()
            ->shouldBe([
                'eth' => '2521',
                'minds' => '0.15'
            ]);
    }

    public function it_should_return_cached_prices()
    {
        $this->config->get('blockchain')
            ->willReturn([
                'token_address' => '0x...',
            ]);
        $this->uniswapClient->getTokenUsdPrices('0x...')
            ->shouldNotBeCalled();
        
        $this->cache->get('blockchain::token-balance::0x...')
            ->willReturn(serialize([
                'eth' => '2521',
                'token' => '0.15'
            ]));

        $this->getPrices()
            ->shouldBe([
                'eth' => '2521',
                'minds' => '0.15'
            ]);
    }
}
