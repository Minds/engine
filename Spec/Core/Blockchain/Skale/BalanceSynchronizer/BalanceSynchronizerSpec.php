<?php

namespace Spec\Minds\Core\Blockchain\Skale\BalanceSynchronizer;

use Minds\Core\Blockchain\Skale\BalanceSynchronizer\BalanceSynchronizer;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\DifferenceCalculator;
use Minds\Core\Blockchain\Skale\BalanceSynchronizer\SyncExcludedUserException;
use Minds\Core\Blockchain\Skale\Tools as SkaleTools;
use Minds\Core\Blockchain\Wallets\OffChain\Balance as OffchainBalance;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

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
    
    /** @var OffchainTransactions */
    private $offchainTransactions;

    public function let(
        SkaleTools $skaleTools,
        DifferenceCalculator $differenceCalculator,
        EntitiesBuilder $entitiesBuilder,
        OffchainBalance $offchainBalance,
        OffchainTransactions $offchainTransactions,
        Config $config
    ) {
        $this->skaleTools = $skaleTools;
        $this->differenceCalculator = $differenceCalculator;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->offchainBalance = $offchainBalance;
        $this->offchainTransactions = $offchainTransactions;
        $this->config = $config;

        $this->beConstructedWith(
            $skaleTools,
            $differenceCalculator,
            $entitiesBuilder,
            $offchainBalance,
            $offchainTransactions,
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

    public function it_should_sync_if_skale_balance_too_high_via_sync_skale(
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

        $adjustmentResult = $this->syncSkale();
        $adjustmentResult->getTxHash()->shouldBe($txHash);
        $adjustmentResult->getDifferenceWei()->shouldBe('900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }

    public function it_should_sync_if_skale_balance_too_low_via_sync_skale(
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

        $adjustmentResult = $this->syncSkale();
        $adjustmentResult->getTxHash()->shouldBe($txHash);
        $adjustmentResult->getDifferenceWei()->shouldBe('-900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }

    public function it_should_NOT_sync_if_skale_balance_matches_offchain_via_sync_skale(
        User $user,
        User $balanceSyncUser
    ) {
        $skaleBalance = '100';
        $offchainBalance = '100';
        $txHash = '0x11';

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

        $this->syncSkale()->shouldBe(null);
    }

    public function it_should_reset_a_users_balance(
        User $user,
        User $balanceSyncUser
    ) {
        $skaleBalance = '100';
        $txHash = '0x123';
   
        $userGuid = '123';
        $user->getGuid()->shouldBeCalled()->willReturn($userGuid);
        $this->user = $user;

        $balanceSyncUserGuid = '321';

        $this->config->get('blockchain')
            ->shouldBeCalledTimes(3)
            ->willReturn([
                'skale' => [
                    'development_mode' => true,
                    'sync_excluded_users' => [],
                    'balance_sync_user_guid' => $balanceSyncUserGuid
                ]
            ]);
        
        $this->entitiesBuilder->single($balanceSyncUserGuid)
            ->shouldBeCalled()
            ->willReturn($balanceSyncUser);

        $this->skaleTools->getTokenBalance(
            user: $user,
            address: null,
            useCache: false
        )
            ->shouldBeCalled()
            ->willReturn($skaleBalance);
        
        $this->skaleTools->sendTokens(
            sender: Argument::any(),
            receiver: Argument::any(),
            receiverAddress: Argument::any(),
            amountWei: Argument::any(),
            checkSFuel: Argument::any(),
            waitForConfirmation: Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($txHash);
        
        $this->resetBalance()->shouldBe($txHash);
    }

    public function it_should_NOT_reset_a_sync_excluded_users_balance(User $user)
    {
        $username = 'testuser';
        $userGuid = '123';
        $user->getGuid()->shouldBeCalled()->willReturn($userGuid);
        $user->getUsername()->shouldBeCalled()->willReturn($username);
        $this->user = $user;

        $balanceSyncUserGuid = '321';

        $this->config->get('blockchain')
            ->shouldBeCalledTimes(2)
            ->willReturn([
                'skale' => [
                    'development_mode' => true,
                    'sync_excluded_users' => [$userGuid],
                    'balance_sync_user_guid' => $balanceSyncUserGuid
                ]
            ]);
        
        $this->entitiesBuilder->single($balanceSyncUserGuid)
            ->shouldNotBeCalled();
        
        $this->shouldThrow(SyncExcludedUserException::class)
            ->duringResetBalance();
    }

    public function it_should_NOT_reset_a_users_balance_outside_of_development_mode()
    {
        $this->config->get('blockchain')
            ->shouldBeCalledTimes(1)
            ->willReturn([
                'skale' => [
                    'development_mode' => false
                ]
            ]);

        $this->entitiesBuilder->single(Argument::any())
            ->shouldNotBeCalled();
        
        $this->shouldThrow(ServerErrorException::class)
            ->duringResetBalance();
    }

    public function it_should_NOT_reset_a_users_balance_if_balance_is_zero(
        User $user,
        User $balanceSyncUser
    ) {
        $skaleBalance = '0';
   
        $userGuid = '123';
        $user->getGuid()->shouldBeCalled()->willReturn($userGuid);
        $this->user = $user;

        $balanceSyncUserGuid = '321';

        $this->config->get('blockchain')
            ->shouldBeCalledTimes(3)
            ->willReturn([
                'skale' => [
                    'development_mode' => true,
                    'sync_excluded_users' => [],
                    'balance_sync_user_guid' => $balanceSyncUserGuid
                ]
            ]);
        
        $this->entitiesBuilder->single($balanceSyncUserGuid)
            ->shouldBeCalled()
            ->willReturn($balanceSyncUser);

        $this->skaleTools->getTokenBalance(
            user: $user,
            address: null,
            useCache: false
        )
            ->shouldBeCalled()
            ->willReturn($skaleBalance);
        
        $this->skaleTools->sendTokens(
            sender: Argument::any(),
            receiver: Argument::any(),
            receiverAddress: Argument::any(),
            amountWei: Argument::any(),
            checkSFuel: Argument::any(),
            waitForConfirmation: Argument::any()
        )
            ->shouldNotBeCalled();
        
        $this->resetBalance()->shouldBe('');
    }

    public function it_should_exclude_an_excluded_user_from_sync_via_sync_skale(
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
            ->duringSyncSkale();
    }

    public function it_should_dry_run_sync_via_sync_skale(User $user)
    {
        $skaleBalance = '100';
        $offchainBalance = '1000';
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

        $adjustmentResult = $this->syncSkale(dryRun: true);
        $adjustmentResult->getTxHash()->shouldBe("");
        $adjustmentResult->getDifferenceWei()->shouldBe('-900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }

    public function it_should_sync_if_skale_balance_too_low_via_sync_offchain(User $user)
    {
        $skaleBalance = '1000';
        $offchainBalance = '100';
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

        $this->offchainTransactions->setType('test')
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setUser($this->user)
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setAmount('900')
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setBypassSkaleMirror(true)
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);
    
        $this->offchainTransactions->setData([
            'amount' => '900',
            'receiver_guid' => $this->user->getGuid(),
            'context' => 'direct_credit'
        ])
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->create()
            ->shouldBeCalled();

        $adjustmentResult = $this->syncOffchain();
        $adjustmentResult->getTxHash()->shouldBe("");
        $adjustmentResult->getDifferenceWei()->shouldBe('900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }

    public function it_should_sync_if_skale_balance_too_high_via_sync_offchain(User $user)
    {
        $skaleBalance = '100';
        $offchainBalance = '1000';
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

        $this->offchainTransactions->setType('test')
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setUser($this->user)
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setAmount('-900')
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->setBypassSkaleMirror(true)
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);
    
        $this->offchainTransactions->setData([
            'amount' => '-900',
            'receiver_guid' => $this->user->getGuid(),
            'context' => 'direct_credit'
        ])
            ->shouldBeCalled()
            ->willReturn($this->offchainTransactions);

        $this->offchainTransactions->create()
            ->shouldBeCalled();

        $adjustmentResult = $this->syncOffchain();
        $adjustmentResult->getTxHash()->shouldBe("");
        $adjustmentResult->getDifferenceWei()->shouldBe('-900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }

    public function it_should_NOT_sync_if_skale_balance_matches_offchain_via_sync_offchain(User $user)
    {
        $skaleBalance = '100';
        $offchainBalance = '100';
        $userGuid = 'testuser';

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

        $this->offchainTransactions->create()
            ->shouldNotBeCalled();

        $this->syncOffchain()->shouldBe(null);
    }

    public function it_should_exclude_an_excluded_user_from_sync_via_sync_offchain(User $user)
    {
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

        $this->offchainTransactions->create()
            ->shouldNotBeCalled();

        $this->shouldThrow(SyncExcludedUserException::class)
            ->duringSyncSkale();
    }

    public function it_should_dry_run_sync_via_sync_offchain(User $user)
    {
        $skaleBalance = '100';
        $offchainBalance = '1000';
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

        $this->offchainTransactions->create()
            ->shouldNotBeCalled();

        $adjustmentResult = $this->syncOffchain(true);
        $adjustmentResult->getTxHash()->shouldBe("");
        $adjustmentResult->getDifferenceWei()->shouldBe('-900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }

    public function it_should_ignore_excluded_users_with_param_sync_offchain(User $user)
    {
        $skaleBalance = '100';
        $offchainBalance = '1000';
        $username = 'testuser';

        $user->getUsername()
            ->shouldBeCalled()
            ->willReturn($username);

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

        $this->offchainTransactions->create()
            ->shouldNotBeCalled();

        $adjustmentResult = $this->syncOffchain(true, true);
        $adjustmentResult->getTxHash()->shouldBe("");
        $adjustmentResult->getDifferenceWei()->shouldBe('-900');
        $adjustmentResult->getUsername()->shouldBe($username);
    }
}
