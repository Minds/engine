<?php

namespace Spec\Minds\Core\Blockchain\Uniswap;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Uniswap\Client;
use Minds\Core\Http;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ClientSpec extends ObjectBehavior
{
    /** @var Http\Curl\Json\Client */
    protected $http;

    public function let(Http\Curl\Json\Client $http)
    {
        $this->beConstructedWith($http);
        $this->http = $http;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Client::class);
    }

    public function it_should_return_user()
    {
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
                    'liquidityPositions' => [
                        [
                            'id' => '0xuser-0xpair',
                            'liquidityTokenBalance' => '1.25',
                            'pair' => [
                                'id' => '0xpair',
                                'totalSupply' => '12.50',
                                'reserve0' => '100.12',
                                'reserve1' => '50.6',
                                'reserveUSD' => '50.6',
                            ]
                        ]
                    ]
                ],
                'mints' => [],
                'burns' => [],
            ],
        ]);
        
        $uniswapUser = $this->getUser('0xUser');

        $uniswapUser->getId()
            ->shouldBe('0xuser');
        $uniswapUser->getLiquidityPositions()
            ->shouldHaveCount(1);
        $uniswapUser->getLiquidityPositions()[0]
            ->getId()
            ->shouldBe('0xuser-0xpair');
        $uniswapUser->getLiquidityPositions()[0]
            ->getLiquidityTokenBalance()
            ->toFloat()
            ->shouldBe(1.25);
        $uniswapUser->getLiquidityPositions()[0]
            ->getPair()
            ->getId()
            ->shouldBe('0xpair');
        $uniswapUser->getLiquidityPositions()[0]
            ->getPair()
            ->getTotalSupply()
            ->toFloat()
            ->shouldBe(12.5);
    }

    public function it_should_return_pairs()
    {
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
