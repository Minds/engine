<?php

namespace Spec\Minds\Core\Blockchain\LiquidityPositions;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\LiquidityPositions\Manager;
use Minds\Core\Blockchain\Uniswap;
use Minds\Core\Blockchain\Uniswap\UniswapBurnEntity;
use Minds\Core\Blockchain\Uniswap\UniswapLiquidityPositionEntity;
use Minds\Core\Blockchain\Uniswap\UniswapMintEntity;
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
                ->setReserve0(BigDecimal::of(1))
                ->setReserve1(BigDecimal::of(2))
                ->setReserveUSD(BigDecimal::of(2))
                ->setId('0xPAIR1'),
            (new UniswapPairEntity())
                ->setTotalSupply(BigDecimal::of(0.5))
                ->setReserve0(BigDecimal::of(0.5))
                ->setReserve1(BigDecimal::of(1))
                ->setReserveUSD(BigDecimal::of(1))
                ->setId('0xPAIR2'),
        ];
        $this->uniswapClient->getPairs(['0xPAIR1', '0xPAIR2'])
            ->willReturn($pairs);

        $uniswapUser = new UniswapUserEntity();

        $liquidityPosition = new UniswapLiquidityPositionEntity();
        $liquidityPosition->setPair($pairs[0])
            ->setLiquidityTokenBalance(BigDecimal::of(0.75)); // 50% of total (pairs totalSupply added up)
        $uniswapUser->setLiquidityPositions([$liquidityPosition]);
    
        // Calculation below give us 25% of all tokens at time of mint
        // so we calculate a 100% yield
        $uniswapUser->setMints([
            (new UniswapMintEntity()) // 20% of pool
                ->setAmount0(BigDecimal::of(0.25))
                ->setAmount1(BigDecimal::of(0.5))
                ->setAmountUSD(BigDecimal::of(0.5))
                ->setPair($pairs[0]),
            (new UniswapMintEntity()) // 5% of pool
                ->setAmount0(BigDecimal::of(0.125)) // 50% of 0.5 (the pair2 amount)
                ->setAmount1(BigDecimal::of(0.25))
                ->setAmountUSD(BigDecimal::of(0.25))
                ->setPair($pairs[1])
        ]);

        // We burn 5% of our initially provided supply, giving us 20% in total
        $uniswapUser->setBurns([
            (new UniswapBurnEntity()) // 5% of pool
                ->setAmount0(BigDecimal::of(0.125)) // 50% of 0.5 (the pair2 amount)
                ->setAmount1(BigDecimal::of(0.25))
                ->setAmountUSD(BigDecimal::of(0.25))
                ->setPair($pairs[1])
        ]);

        $this->uniswapClient->getUser('0xSpec')
            ->willReturn($uniswapUser);

        $summary = $this->setUser($user)
            ->getSummary();

        $summary->getTokenSharePct()
            ->shouldBe(0.5); // 50pc

        //

        $summary->getProvidedLiquidity()
            ->getMinds() // This is amount0/reserve0
            ->toFloat()
            ->shouldBe((float) 0.25); // 20% of total liquidity

        $summary->getProvidedLiquidity()
            ->getUsd()
            ->toFloat()
            ->shouldBe((float) 0.5);

        //

        $summary->getCurrentLiquidity()
            ->getMinds()
            ->toFloat()
            ->shouldBe((float) 0.75);

        $summary->getCurrentLiquidity()
            ->getUsd()
            ->toFloat()
            ->shouldBe((float) 1.5);

        //

        $summary->getTotalLiquidity()
            ->getMinds()
            ->toFloat()
            ->shouldBe((float) 1.5);
    
        $summary->getTotalLiquidity()
            ->getUsd()
            ->toFloat()
            ->shouldBe((float) 3);

        //

        $summary->getYieldLiquidity()
            ->getMinds()
            ->toFloat()
            ->shouldBe((float) 0.5); // We gained 0.5 (100% yield)

        $summary->getYieldLiquidity()
            ->getUsd()
            ->toFloat()
            ->shouldBe((float) 1); // We gained 1 (100% yield)

        //

        $summary->getShareOfLiquidity()
            ->getMinds()
            ->toFloat()
            ->shouldBe((float) 0.5);

        $summary->getShareOfLiquidity()
            ->getUsd()
            ->toFloat()
            ->shouldBe((float) 0.5);
    }
}
