<?php

namespace Spec\Minds\Core\Rewards;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Token;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Rewards\Contributions\Manager as ContributionsManager;
use Minds\Core\Blockchain\Transactions\Repository as TxRepository;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Blockchain\LiquidityPositions;
use Minds\Core\Blockchain\LiquidityPositions\LiquidityCurrencyValues;
use Minds\Core\Blockchain\LiquidityPositions\LiquidityPositionSummary;
use Minds\Core\Blockchain\Services\BlockFinder;
use Minds\Core\Blockchain\Util;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain\UniqueOnChainAddress;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Rewards\Contributions\ContributionSummary;
use Minds\Core\Rewards\Repository as RewardsRepository;
use Minds\Core\Rewards\RewardEntry;
use Minds\Core\Rewards\RewardsQueryOpts;
use Minds\Entities\User;

class ManagerSpec extends ObjectBehavior
{
    /** @var ContributionsManager */
    private $contributions;

    /** @var Transactions */
    private $transactions;

    /** @var TxRepository */
    private $txRepository;

    /** @var RewardsRepository */
    private $repository;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var LiquidityPositions\Manager */
    private $liquidityPositionManager;

    /** @var UniqueOnChain\Manager */
    private $uniqueOnChainManager;

    /** @var BlockFinder */
    private $blockFinder;

    /** @var Token */
    private $token;

    public function let(
        ContributionsManager $contributions,
        Transactions $transactions,
        TxRepository $txRepository,
        RewardsRepository $repository,
        EntitiesBuilder $entitiesBuilder,
        LiquidityPositions\Manager $liquidityPositionManager,
        UniqueOnChain\Manager $uniqueOnChainManager,
        BlockFinder $blockFinder,
        Token $token
    ) {
        $this->beConstructedWith(
            $contributions,
            $transactions,
            $txRepository,
            null, // $eth
            null, // $config
            $repository, // $repository
            $entitiesBuilder, // $entitiesBuilder
            $liquidityPositionManager, // $liquidityPositionManager
            $uniqueOnChainManager, // $uniqueOnChainManager
            $blockFinder,
            $token, // $token
            null // $logger
        );
        $this->contributions = $contributions;
        $this->transactions = $transactions;
        $this->txRepository = $txRepository;
        $this->repository = $repository;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->liquidityPositionManager = $liquidityPositionManager;
        $this->uniqueOnChainManager = $uniqueOnChainManager;
        $this->blockFinder = $blockFinder;
        $this->token = $token;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Rewards\Manager');
    }

    public function it_should_add_reward_entry_to_repository()
    {
        $rewardEntry = new RewardEntry();

        $this->repository->add($rewardEntry)
            ->shouldBeCalled();

        $this->add($rewardEntry);
    }

    public function it_should_calculate_rewards()
    {
        // Mock of our users
        $user1 = (new User);
        $user1->guid = '123';
        $user1->setPhoneNumberHash('phone_hash');
        $user1->setEthWallet('0xAddresss');

        $this->entitiesBuilder->single('123')
            ->willReturn($user1);

        // Engagement
        $this->contributions->getSummaries(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                (new ContributionSummary)
                    ->setUserGuid('123')
                    ->setScore(10)
                    ->setAmount(2),
            ]);
        $this->repository->add(Argument::that(function ($rewardEntry) {
            return $rewardEntry->getUserGuid() === '123'
                && $rewardEntry->getScore()->toFloat() === (float) 30
                && $rewardEntry->getRewardType() === 'engagement';
        }))->shouldBeCalled();

        // Liquidity
        $this->liquidityPositionManager->setDateTs(Argument::any())
                ->willReturn($this->liquidityPositionManager);

        $this->liquidityPositionManager->setChainId(Util::BASE_CHAIN_ID)
                ->willReturn($this->liquidityPositionManager);

        $this->liquidityPositionManager->setChainId(Util::ETHEREUM_CHAIN_ID)
                ->willReturn($this->liquidityPositionManager);

        $this->liquidityPositionManager->getAllProvidersSummaries()
                ->willReturn([
                    (new LiquidityPositionSummary())
                        ->setUserGuid('123')
                        ->setProvidedLiquidity(
                            (new LiquidityCurrencyValues())
                                ->setUsd(BigDecimal::of(10))
                                ->setMinds(BigDecimal::of(10))
                        ),
                ]);

        // Add to the repository
        $this->repository->add(Argument::that(function ($rewardEntry) {
            return (string) $rewardEntry->getUserGuid() === '123'
                && (string) $rewardEntry->getScore() === '60'
                && $rewardEntry->getRewardType() === 'liquidity';
        }))->shouldBeCalled()->willReturn(true);

        // Holding
        $this->blockFinder->getBlockByTimestamp(Argument::any(), Argument::type('integer'))
            ->willReturn(1);

        $this->uniqueOnChainManager->getAll(Argument::any())
            ->willReturn([
                (new UniqueOnChainAddress)
                    ->setAddress('0xAddresss')
                    ->setUserGuid('123')
            ]);
        
        $this->token->fromTokenUnit("10")
                ->willReturn(10);
        $this->token->balanceOf('0xAddresss', 1, Argument::type('integer'))
                ->willReturn("10");

        $this->repository->add(Argument::that(function ($rewardEntry) {
            return (string) $rewardEntry->getUserGuid() === '123'
                && (string) $rewardEntry->getScore() === "60"
                && $rewardEntry->getRewardType() === 'holding';
        }))->shouldBeCalled();

        // Calculation of tokens
        $this->repository->getIterator(Argument::any())
                ->willReturn([
                    (new RewardEntry())
                        ->setUserGuid('123')
                        ->setRewardType('engagement')
                        ->setScore(BigDecimal::of(25))
                        ->setSharePct(0.5),
                ]);
        $this->repository->update(Argument::that(function ($rewardEntry) {
            return $rewardEntry->getUserGuid() === '123'
                && $rewardEntry->getTokenAmount()
                && $rewardEntry->getTokenAmount()->toFloat() === (float) 2000 // 50% of all available rewards in pool
                && $rewardEntry->getRewardType() === 'engagement';
        }), ['token_amount'])->shouldBeCalled()
            ->willReturn(true);

        $this->calculate();
    }

    public function it_should_calculate_holding_rewards_when_balance_already_populated(
        UniqueOnChainAddress $address1
    ) {
        // Mock of our users
        $user1 = (new User);
        $user1->guid = '123';
        $user1->setPhoneNumberHash('phone_hash');
        $user1->setEthWallet('0xAddresss');

        $this->entitiesBuilder->single('123')
            ->willReturn($user1);

        // Engagement
        $this->contributions->getSummaries(Argument::any())
            ->shouldBeCalled()
            ->willReturn([
                (new ContributionSummary)
                    ->setUserGuid('123')
                    ->setScore(10)
                    ->setAmount(2),
            ]);
        $this->repository->add(Argument::that(function ($rewardEntry) {
            return $rewardEntry->getUserGuid() === '123'
                && $rewardEntry->getScore()->toFloat() === (float) 30
                && $rewardEntry->getRewardType() === 'engagement';
        }))->shouldBeCalled();

        // Liquidity
        $this->liquidityPositionManager->setChainId(Util::BASE_CHAIN_ID)
            ->willReturn($this->liquidityPositionManager);
        $this->liquidityPositionManager->setChainId(Util::ETHEREUM_CHAIN_ID)
            ->willReturn($this->liquidityPositionManager);

        $this->liquidityPositionManager->setDateTs(Argument::any())
                ->willReturn($this->liquidityPositionManager);

        $this->liquidityPositionManager->getAllProvidersSummaries()
                ->willReturn([
                    (new LiquidityPositionSummary())
                        ->setUserGuid('123')
                        ->setProvidedLiquidity(
                            (new LiquidityCurrencyValues())
                                ->setUsd(BigDecimal::of(10))
                                ->setMinds(BigDecimal::of(10))
                        ),
                ]);

        // Add to the repository
        $this->repository->add(Argument::that(function ($rewardEntry) {
            return (string) $rewardEntry->getUserGuid() === '123'
                && (string) $rewardEntry->getScore() === '60'
                && $rewardEntry->getRewardType() === 'liquidity';
        }))->shouldBeCalled();

        // Holding
        $this->blockFinder->getBlockByTimestamp(Argument::any(), Argument::type('integer'))
            ->willReturn(1);

        $address1->getAddress()->willReturn('0xAddresss');
        $address1->getUserGuid()->willReturn('123');

        $this->uniqueOnChainManager->getAll(Argument::any())
            ->willReturn([
                $address1
            ]);
        
        $this->token->fromTokenUnit("10")
            ->shouldBeCalled()
            ->willReturn(10);

        $this->token->balanceOf('0xAddresss', 1, Argument::type('integer'))
            ->shouldBeCalled()
            ->willReturn('10');

        $this->repository->add(Argument::that(function ($rewardEntry) {
            return (string) $rewardEntry->getUserGuid() === '123'
                && (string) $rewardEntry->getScore() === "60"
                && $rewardEntry->getRewardType() === 'holding';
        }))->shouldBeCalled();

        // Calculation of tokens
        $this->repository->getIterator(Argument::any())
                ->willReturn([
                    (new RewardEntry())
                        ->setUserGuid('123')
                        ->setRewardType('engagement')
                        ->setScore(BigDecimal::of(25))
                        ->setSharePct(0.5),
                ]);
        $this->repository->update(Argument::that(function ($rewardEntry) {
            return $rewardEntry->getUserGuid() === '123'
                && $rewardEntry->getTokenAmount()
                && $rewardEntry->getTokenAmount()->toFloat() === (float) 2000 // 50% of all available rewards in pool
                && $rewardEntry->getRewardType() === 'engagement';
        }), ['token_amount'])->shouldBeCalled()
            ->willReturn(true);

        $opts = new RewardsQueryOpts();
        $opts->setDateTs(time());
        $opts->setRecalculate(true);

        $this->calculate($opts);
    }

    public function it_should_allow_max_multiplier_of_3_in_365_days()
    {
        $rewardEntry = new RewardEntry();
        $rewardEntry->setMultiplier(BigDecimal::of('1'));

        $i = 0;
        while ($i < 730) {
            ++$i;
            $multiplier = $this->calculateMultiplier($rewardEntry);
            $rewardEntry->setMultiplier($multiplier->getWrappedObject());
        }

        $multiplier->toFloat()->shouldBe((float) 3);
    }

    public function it_should_not_payout_if_already_payout_tx()
    {
        $this->repository->getIterator(Argument::any())
            ->willReturn([
                (new RewardEntry())
                        ->setUserGuid('123')
                        ->setRewardType('engagement')
                        ->setScore(BigDecimal::of(25))
                        ->setSharePct(0.5)
                        ->setTokenAmount(BigDecimal::of(1))
                        ->setPayoutTx('oc:123')
            ]);

        $this->txRepository->add(Argument::any())
            ->shouldNotBeCalled();

        $this->issueTokens(null, false);
    }

}
