<?php

namespace Spec\Minds\Core\Blockchain\Uniswap;

use Brick\Math\BigDecimal;
use Minds\Core\Config;
use Minds\Core\Blockchain\Uniswap\Client;
use Minds\Core\Blockchain\Services\BlockFinder;
use Minds\Core\Blockchain\Util;
use Minds\Core\Http;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ClientSpec extends ObjectBehavior
{
    /** @var Http\Curl\Json\Client */
    protected $http;

    /** @var BlockFinder */
    protected $blockFinder;

    /** @var Config */
    protected $config;

    public function let(Http\Curl\Json\Client $http, BlockFinder $blockFinder, Config $config)
    {
        $this->beConstructedWith($http, $blockFinder, $config);
        $this->http = $http;
        $this->blockFinder = $blockFinder;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_should_return_user()
    {
        $this->config->get('uniswap')
            ->willReturn(['graph_urls' => [Util::BASE_CHAIN_ID => 'http://localhost:80']]);

        $this->http->post(
            Argument::type('string'),
            Argument::that(
                function ($body) {
                    return $body['variables']['id'] === '0xuser'; // NOTE lower case
                }
            ),
            Argument::type('array')
        )
        ->willReturn([
            'data' => [
                'user' => [
                    'id' => '0xuser',
                    'usdSwaped' => "0",
                ],
                'mints' => [],
                'burns' => [],
            ],
        ]);

        $this->blockFinder->getBlockByTimestamp(Argument::any(), Argument::type('integer'))
            ->willReturn(1);
        
        $uniswapUser = $this->getUser('0xUser');

        $uniswapUser->getId()
            ->shouldBe('0xuser');
    }

    public function it_should_return_pairs()
    {
        $this->config->get('uniswap')
            ->willReturn(['graph_urls' => [Util::BASE_CHAIN_ID => 'http://localhost:80']]);

        $this->http->post(
            Argument::type('string'),
            Argument::that(
                function ($body) {
                    return $body['variables']['ids'] === ['0xpair1', '0xpair2']; // NOTE lower case
                }
            ),
            Argument::type('array')
        )
        ->willReturn([
            'data' => [
                'pairs' => [
                    [
                        'id' => '0xpair1',
                        'totalSupply' => '12.50',
                        'reserve0' => '100.12',
                        'reserve1' => '50.6',
                        'reserveUSD' => '50.6',
                    ],
                    [
                        'id' => '0xpair2',
                        'totalSupply' => '24.102',
                        'reserve0' => '100.12',
                        'reserve1' => '50.6',
                        'reserveUSD' => '50.6',
                    ]
                ]
            ],
        ]);

        $this->blockFinder->getBlockByTimestamp(Argument::any(), Argument::type('integer'))
            ->willReturn(1);

        $pairs = $this->getPairs(['0xPair1', '0xPair2']);
        $pairs[0]->getId()
            ->shouldBe('0xpair1');
        $pairs[0]->getTotalSupply()
            ->toFloat()
            ->shouldBe(12.5);
        $pairs[1]->getId()
            ->shouldBe('0xpair2');
        $pairs[1]->getTotalSupply()
            ->toFloat()
            ->shouldBe(24.102);
    }
}
