<?php

namespace Spec\Minds\Core\Blockchain\LiquidityPositions;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\LiquidityPositions\Manager;
use Minds\Core\Blockchain\Uniswap;
use Minds\Core\Blockchain\Uniswap\UniswapLiquidityPositionEntity;
use Minds\Core\Blockchain\Uniswap\UniswapPairEntity;
use Minds\Core\Blockchain\Uniswap\UniswapUserEntity;
use Minds\Core\Config\Config;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Uniswap\Client */
    protected $uniswapClient;

    /** @var Config */
    protected $config;

    public function let(Uniswap\Client $uniswapClient, Config $config)
    {
        $this->beConstructedWith($uniswapClient, $config);
        $this->config = $config;
        $this->uniswapClient = $uniswapClient;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_provide_new_instance_when_user_is_set()
    {
        $user = new User();
        $this->setUser($user)->shouldHaveType(Manager::class);
    }

    public function it_should_return_liquidity_share(User $user)
    {
        $user->getEthWallet()
            ->willReturn('0xSpec');

        $this->config->get('blockchain')
            ->willReturn([
                'liquidity_positions' => [
                    'approved_pairs' => [
                        '0xPAIR1',
                        '0xPAIR2'
                    ]
                ]
            ]);

        $pairs = [
            (new UniswapPairEntity())
                ->setTotalSupply(BigDecimal::of(1))
                ->setId('0xPAIR1'),
            (new UniswapPairEntity())
                ->setTotalSupply(BigDecimal::of(0.5))
                ->setId('0xPAIR2'),
        ];
        $this->uniswapClient->getPairs(['0xPAIR1', '0xPAIR2'])
            ->willReturn($pairs);

        $uniswapUser = new UniswapUserEntity();
        $liquidityPosition = new UniswapLiquidityPositionEntity();
        $liquidityPosition->setPair($pairs[0])
            ->setLiquidityTokenBalance(BigDecimal::of(0.75));
        $uniswapUser->setLiquidityPositions([$liquidityPosition]);
        $this->uniswapClient->getUser('0xSpec')
            ->willReturn($uniswapUser);

        $this->setUser($user)
            ->getSummary()
            ->getTokenSharePct()
            ->shouldBe(0.5); // 50pc
    }
}
