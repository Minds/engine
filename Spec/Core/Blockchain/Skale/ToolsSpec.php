<?php

namespace Spec\Minds\Core\Blockchain\Skale;

use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\Skale\Transaction\Manager as TransactionManager;
use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Blockchain\Services\Skale;
use Minds\Core\Blockchain\Skale\Keys;
use Minds\Core\Blockchain\Skale\Tools;
use Minds\Core\Blockchain\Wallets\Skale\Balance;
use Minds\Entities\User;
use Prophecy\Argument;

class ToolsSpec extends ObjectBehavior
{
    /** @var Keys */
    private $keys;

    /** @var Balance */
    private $balance;

    /** @var TransactionManager */
    private $transactionManager;

    /** @var Skale */
    private $skaleClient;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Config */
    private $config;

    public function let(
        Keys $keys,
        Balance $balance,
        TransactionManager $transactionManager,
        Skale $skaleClient,
        EntitiesBuilder $entitiesBuilder,
        Config $config
    ) {
        $this->keys = $keys;
        $this->balance = $balance;
        $this->transactionManager = $transactionManager;
        $this->skaleClient = $skaleClient;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->config = $config;

        $this->beConstructedWith(
            $keys,
            $balance,
            $transactionManager,
            $skaleClient,
            $entitiesBuilder,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Tools::class);
    }

    public function it_should_get_token_balance_for_user(User $user)
    {
        $walletAddress = '0x123';

        $this->keys->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->keys);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($walletAddress);
        
        $this->balance->getTokenBalance(
            address: $walletAddress,
            useCache: true
        )
            ->shouldBeCalled()
            ->willReturn('1');

        $this->getTokenBalance($user)
            ->shouldBe('1');
    }

    public function it_should_get_token_balance_for_address(User $user)
    {
        $walletAddress = '0x123';
        
        $this->balance->getTokenBalance(
            address: $walletAddress,
            useCache: true
        )
            ->shouldBeCalled()
            ->willReturn('1');

        $this->getTokenBalance(address: $walletAddress)
            ->shouldBe('1');
    }

    public function it_should_get_token_balance_for_user_without_cache(User $user)
    {
        $walletAddress = '0x123';

        $this->keys->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->keys);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($walletAddress);
        
        $this->balance->getTokenBalance(
            address: $walletAddress,
            useCache: false
        )
            ->shouldBeCalled()
            ->willReturn('1');

        $this->getTokenBalance($user, null, false)
            ->shouldBe('1');
    }

    public function it_should_get_sfuel_balance_for_user(User $user)
    {
        $walletAddress = '0x123';

        $this->keys->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->keys);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($walletAddress);
        
        $this->balance->getSFuelBalance(
            address: $walletAddress,
            useCache: true
        )
            ->shouldBeCalled()
            ->willReturn('1');

        $this->getSFuelBalance($user)
            ->shouldBe('1');
    }

    public function it_should_get_sfuel_balance_for_address(User $user)
    {
        $walletAddress = '0x123';
        
        $this->balance->getSFuelBalance(
            address: $walletAddress,
            useCache: true
        )
            ->shouldBeCalled()
            ->willReturn('1');

        $this->getSFuelBalance(address: $walletAddress)
            ->shouldBe('1');
    }

    public function it_should_get_sfuel_balance_for_user_without_cache(User $user)
    {
        $walletAddress = '0x123';

        $this->keys->withUser($user)
            ->shouldBeCalled()
            ->willReturn($this->keys);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($walletAddress);
        
        $this->balance->getSFuelBalance(
            address: $walletAddress,
            useCache: false
        )
            ->shouldBeCalled()
            ->willReturn('1');

        $this->getSFuelBalance($user, null, false)
            ->shouldBe('1');
    }

    public function it_should_send_tokens_to_an_address(User $sender)
    {
        $receiverAddress = '0x123';
        $resultHash = '0x000001';

        $amountWei = '123';
        $defaultDistibutorGuid = '100000000000000000';

        // check low balance threshold

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'default_sfuel_distributor_guid' => $defaultDistibutorGuid,
                    'sfuel_low_threshold' => '1'
                ]
            ]);

        $this->keys->withUser($sender)
            ->shouldBeCalled()
            ->willReturn($this->keys);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($receiverAddress);
        
        $this->balance->getSFuelBalance(
            address: $receiverAddress,
            useCache: true
        )
            ->shouldBeCalled()
            ->willReturn('99');

        // send

        $this->transactionManager->withUsers(
            $sender,
            null,
            $receiverAddress
        )
            ->shouldBeCalled()
            ->willReturn($this->transactionManager);
    
        $this->transactionManager->sendTokens(
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($resultHash);
        
        // await confirmation

        $this->skaleClient->request(
            'eth_getTransactionReceipt',
            [
                $resultHash
            ]
        )
            ->shouldBeCalled()
            ->willReturn([
                'blockHash' => '0xblockhash'
            ]);

        $this->sendTokens($sender, null, $receiverAddress, $amountWei)
            ->shouldBe($resultHash);
    }

    public function it_should_send_tokens_to_a_user(User $sender, User $receiver)
    {
        $receiverAddress = '0x123';
        $resultHash = '0x000001';

        $amountWei = '123';
        $defaultDistibutorGuid = '100000000000000000';

        // check low balance threshold

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'default_sfuel_distributor_guid' => $defaultDistibutorGuid,
                    'sfuel_low_threshold' => '1'
                ]
            ]);

        $this->keys->withUser($sender)
            ->shouldBeCalled()
            ->willReturn($this->keys);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($receiverAddress);
        
        $this->balance->getSFuelBalance(
            address: $receiverAddress,
            useCache: true
        )
            ->shouldBeCalled()
            ->willReturn('99');

        // send

        $this->transactionManager->withUsers(
            $sender,
            $receiver,
            null
        )
            ->shouldBeCalled()
            ->willReturn($this->transactionManager);
    
        $this->transactionManager->sendTokens(
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($resultHash);
        
        // await confirmation

        $this->skaleClient->request(
            'eth_getTransactionReceipt',
            [
                $resultHash
            ]
        )
            ->shouldBeCalled()
            ->willReturn([
                'blockHash' => '0xblockhash'
            ]);

        $this->sendTokens($sender, $receiver, null, $amountWei)
            ->shouldBe($resultHash);
    }

    public function it_should_send_sfuel_to_a_user_if_not_enough_balance_for_token_tx(User $sender, User $receiver)
    {
        $receiverAddress = '0x123';
        $resultHash = '0x000001';
        $sfuelResultHash = '0x000002';
        $amountWei = '123';
        $defaultDistibutorGuid = '100000000000000000';

        // check low balance threshold
        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'default_sfuel_distributor_guid' => $defaultDistibutorGuid,
                    'sfuel_low_threshold' => '999'
                ]
            ]);
        
        $this->entitiesBuilder->single($defaultDistibutorGuid)
            ->shouldBeCalled()
            ->willReturn($sender);

        $this->keys->withUser($sender)
            ->shouldBeCalled()
            ->willReturn($this->keys);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($receiverAddress);
        
        $this->balance->getSFuelBalance(
            address: $receiverAddress,
            useCache: true
        )
            ->shouldBeCalled()
            ->willReturn('99');

        // send sfuel
        $this->transactionManager->withUsers(
            sender: $sender,
            receiver: Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($this->transactionManager);

        $this->transactionManager->sendSFuel(null)
            ->shouldBeCalled()
            ->willReturn($sfuelResultHash);

        // await sfuel tx
        $this->skaleClient->request(
            'eth_getTransactionReceipt',
            [
                $sfuelResultHash
            ]
        )
            ->shouldBeCalled()
            ->willReturn([
                'blockHash' => '0xblockhash'
            ]);

        // send tokens

        $this->transactionManager->withUsers(
            $sender,
            $receiver,
            null
        )
            ->shouldBeCalled()
            ->willReturn($this->transactionManager);
    
        $this->transactionManager->sendTokens(
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($resultHash);
        
        // await confirmation

        $this->skaleClient->request(
            'eth_getTransactionReceipt',
            [
                $resultHash
            ]
        )
            ->shouldBeCalled()
            ->willReturn([
                'blockHash' => '0xblockhash'
            ]);

        $this->sendTokens($sender, $receiver, null, $amountWei)
            ->shouldBe($resultHash);
    }

    public function it_should_send_sfuel_to_user(User $sender, User $receiver)
    {
        $sfuelResultHash = '0x000001';

        // check low balance threshold
        $this->config->get('blockchain')
        ->shouldBeCalled()
        ->willReturn([
            'skale' => [
                'default_sfuel_distributor_guid' => '00000000000001',
            ]
        ]);

        // send sfuel
        $this->transactionManager->withUsers(
            sender: $sender,
            receiver: Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($this->transactionManager);

        $this->transactionManager->sendSFuel(null)
            ->shouldBeCalled()
            ->willReturn($sfuelResultHash);

        // await sfuel tx
        $this->skaleClient->request(
            'eth_getTransactionReceipt',
            [
                $sfuelResultHash
            ]
        )
            ->shouldBeCalled()
            ->willReturn([
                'blockHash' => '0xblockhash'
            ]);

        $this->sendSFuel($sender, $receiver, null, null)
            ->shouldBe($sfuelResultHash);
    }
}
