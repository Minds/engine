<?php

namespace Spec\Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Core\Blockchain\Skale\BalanceSynchronizer\BalanceSynchronizer;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\DifferenceCalculator;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\SyncExcludedUserException;
use Minds\Core\Blockchain\Skale\Tools as SkaleTools;
use Minds\Core\Blockchain\Wallets\OffChain\Balance as OffchainBalance;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class BalanceSynchronizerSpec extends ObjectBehavior
{
    /** @var SkaleTools */
    private $skaleTools;

    /** @var DifferenceCalculator */
    private $differenceCalculator;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var OffchainBalance */
    private $offchainBalance;

    /** @var Config */
    private $config;

    public function let(
        SkaleTools $skaleTools,
        DifferenceCalculator $differenceCalculator,
        EntitiesBuilder $entitiesBuilder,
        OffchainBalance $offchainBalance,
        Config $config
    ) {
        $this->skaleTools = $skaleTools;
        $this->differenceCalculator = $differenceCalculator;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->offchainBalance = $offchainBalance;
        $this->config = $config;

        $this->beConstructedWith(
            $skaleTools,
            $differenceCalculator,
            $entitiesBuilder,
            $offchainBalance,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BalanceSynchronizer::class);
    }

    public function it_should_construct_new_instance(User $user)
    {
        $this->withUser($user)->shouldHaveType(BalanceSynchronizer::class);
    }

    public function it_should_get_instance_user(User $user)
    {
        $this->user = $user;
        $this->getUser()->shouldBe($user);
    }

    public function it_should_build_balance_calculator_with_balances(User $user)
    {
        $skaleBalance = '1000';
        $offchainBalance = '100';
        $this->user = $user;

        $this->skaleTools->getTokenBalance(
            user: $user,
            address: null,
            useCache: false
        )
            ->shouldBeCalled()
            ->willReturn($skaleBalance);

        $this->offchainBalance->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->offchainBalance);
        
        $this->offchainBalance->get()
            ->shouldBeCalled()
            ->willReturn($offchainBalance);

        $this->differenceCalculator->withBalances(
            offchainBalance: $offchainBalance,
            skaleTokenBalance: $skaleBalance
        )
            ->shouldBeCalled()
            ->willReturn(new DifferenceCalculator());

        $this->buildDifferenceCalculator();
    }

    public function it_should_sync_if_skale_balance_too_high(
        User $user,
        User $balanceSyncUser
    ) {
        $skaleBalance = '1000';
        $offchainBalance = '100';
        $balanceSyncUserGuid = '123';
        $txHash = '0x11';
        $username = 'testuser';
        $userGuid = '321';

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($username);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->user = $user;

        $this->skaleTools->getTokenBalance(
            user: $user,
            address: null,
            useCache: false
        )
            ->shouldBeCalled()
            ->willReturn($skaleBalance);

        $this->offchainBalance->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->offchainBalance);
        
        $this->offchainBalance->get()
            ->shouldBeCalled()
            ->willReturn($offchainBalance);

        $this->differenceCalculator->withBalances(
            offchainBalance: $offchainBalance,
            skaleTokenBalance: $skaleBalance
        )
            ->shouldBeCalled()
            ->willReturn(
                (new DifferenceCalculator())
                ->withBalances(
                    offchainBalance: $offchainBalance,
                    skaleTokenBalance: $skaleBalance
                )
            );

        $this->config->get('blockchain')->shouldBeCalled()->willReturn([
            'skale' => [
                'balance_sync_user_guid' => $balanceSyncUserGuid
            ]
        ]);

        $this->entitiesBuilder->single($balanceSyncUserGuid)
            ->shouldBeCalled()
            ->willReturn($balanceSyncUser);

        $this->skaleTools->sendTokens(
            amountWei: '900',
            sender: $user,
            receiver: $balanceSyncUser,
            receiverAddress: null,
            waitForConfirmation: false,
            checkSFuel: true
        )
            ->shouldBeCalled()
            ->willReturn($txHash);

        $adjustmentResult = $this->sync();
        $adjustmentResult->getTxHash()->shouldBe($txHash);
        $adjustmentResult->getDifferenceWei()->shouldBe('900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }

    public function it_should_sync_if_skale_balance_too_low(
        User $user,
        User $balanceSyncUser
    ) {
        $skaleBalance = '100';
        $offchainBalance = '1000';
        $balanceSyncUserGuid = '123';
        $txHash = '0x11';
        $username = 'testuser';
        $userGuid = '321';

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($username);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->user = $user;

        $this->skaleTools->getTokenBalance(
            user: $user,
            address: null,
            useCache: false
        )
            ->shouldBeCalled()
            ->willReturn($skaleBalance);

        $this->offchainBalance->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->offchainBalance);
        
        $this->offchainBalance->get()
            ->shouldBeCalled()
            ->willReturn($offchainBalance);

        $this->differenceCalculator->withBalances(
            offchainBalance: $offchainBalance,
            skaleTokenBalance: $skaleBalance
        )
            ->shouldBeCalled()
            ->willReturn(
                (new DifferenceCalculator())
                ->withBalances(
                    offchainBalance: $offchainBalance,
                    skaleTokenBalance: $skaleBalance
                )
            );

        $this->config->get('blockchain')->shouldBeCalled()->willReturn([
            'skale' => [
                'balance_sync_user_guid' => $balanceSyncUserGuid
            ]
        ]);

        $this->entitiesBuilder->single($balanceSyncUserGuid)
            ->shouldBeCalled()
            ->willReturn($balanceSyncUser);

        $this->skaleTools->sendTokens(
            amountWei: '900',
            sender: $balanceSyncUser,
            receiver: $user,
            receiverAddress: null,
            waitForConfirmation: false,
            checkSFuel: false
        )
            ->shouldBeCalled()
            ->willReturn($txHash);

        $adjustmentResult = $this->sync();
        $adjustmentResult->getTxHash()->shouldBe($txHash);
        $adjustmentResult->getDifferenceWei()->shouldBe('-900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }

    public function it_should_NOT_sync_if_skale_balance_matches_offchain(
        User $user,
        User $balanceSyncUser
    ) {
        $skaleBalance = '100';
        $offchainBalance = '100';
        $balanceSyncUserGuid = '123';
        $txHash = '0x11';
        $username = 'testuser';

        $this->user = $user;

        $this->skaleTools->getTokenBalance(
            user: $user,
            address: null,
            useCache: false
        )
            ->shouldBeCalled()
            ->willReturn($skaleBalance);

        $this->offchainBalance->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->offchainBalance);
        
        $this->offchainBalance->get()
            ->shouldBeCalled()
            ->willReturn($offchainBalance);

        $this->differenceCalculator->withBalances(
            offchainBalance: $offchainBalance,
            skaleTokenBalance: $skaleBalance
        )
            ->shouldBeCalled()
            ->willReturn(
                (new DifferenceCalculator())
                ->withBalances(
                    offchainBalance: $offchainBalance,
                    skaleTokenBalance: $skaleBalance
                )
            );

        $this->skaleTools->sendTokens(
            sender: $balanceSyncUser,
            receiver: $user,
            receiverAddress: null,
            amountWei: '900'
        )
            ->shouldNotBeCalled()
            ->willReturn($txHash);

        $this->sync()->shouldBe(null);
    }

    public function it_should_exclude_an_excluded_user_from_sync(
        User $user
    ) {
        $username = 'testuser';
        $userGuid = '321';

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($username);

        $user->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->user = $user;

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'sync_excluded_users' => [
                        '321'
                    ]
                ]
            ]);

        $this->skaleTools->getTokenBalance(
            user: $user,
            address: null,
            useCache: false
        )->shouldNotBeCalled();

        $this->shouldThrow(SyncExcludedUserException::class)
            ->duringSync();
    }
}
