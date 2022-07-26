<?php

namespace Spec\Minds\Core\Blockchain\Skale\Transaction;

use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\Services\Skale;
use Minds\Core\Blockchain\Skale\Keys;
use Minds\Core\Config;
use Minds\Core\Blockchain\Skale\Transaction\Manager;
use Minds\Entities\User;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Skale */
    private $skaleClient;
    
    /** @var Keys */
    private $keys;

    /** @var Config */
    private $config;

    private $tokenAddress = '0x00';

    public function let(
        Skale $skaleClient,
        Keys $keys,
        Config $config
    ) {
        $config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'skale' => [
                    'minds_token_address' => $this->tokenAddress
                ]
            ]);
        
        $this->skaleClient = $skaleClient;
        $this->keys = $keys;
        $this->config = $config;
        
        $this->beConstructedWith(
            $skaleClient,
            $keys,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_new_instance_with_sender_and_receiver_address(
        User $sender,
    ) {
        $receiverAddress = '0x01';
        $instance = $this->withUsers($sender, null, $receiverAddress);
        $instance->getSender()->shouldBe($sender);
        $instance->getReceiverAddress()->shouldBe($receiverAddress);
    }

    public function it_should_return_new_instance_with_sender_and_receiver_user(
        User $sender,
        User $receiver
    ) {
        $receiverAddress = '0x01';

        $this->keys->withUser($receiver)
            ->shouldBeCalled()
            ->willReturn($this->keys);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($receiverAddress);
    
        $instance = $this->withUsers($sender, $receiver, null);
        $instance->getSender()->shouldBe($sender);
        $instance->getReceiverAddress()->shouldBe($receiverAddress);
    }

    public function it_should_send_tokens(
        User $sender
    ) {
        $amountWei = '100000';
        $receiverAddress = '0x01';
        $senderAddress = '0x02';
        $senderXpriv = '~xpriv~';
        $responseHash = '0x3';
        $encodedData = [1,2,3];
        $tokenAddress = $this->tokenAddress;
        $gasLimitHex = '0xc850';

        $this->keys->withUser($sender)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->keys);

        $this->keys->getSecp256k1PrivateKeyAsHex()
            ->shouldBeCalled()
            ->willReturn($senderXpriv);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($senderAddress);

        $this->skaleClient->encodeContractMethod('transfer(address,uint256)', [
            $receiverAddress,
            '0x186a0'
        ])
            ->shouldBeCalled()
            ->willReturn($encodedData);

        $this->skaleClient->sendRawTransaction(
            $senderXpriv,
            Argument::that(function ($arg) use (
                $senderAddress,
                $tokenAddress,
                $gasLimitHex,
                $encodedData
            ) {
                return $arg['from'] === $senderAddress &&
                    $arg['to'] === $tokenAddress &&
                    $arg['gasLimit'] === $gasLimitHex &&
                    $arg['data'] === $encodedData;
            })
        )
            ->shouldBeCalled()
            ->willReturn($responseHash);

        $instance = $this->withUsers($sender, null, $receiverAddress);

        $instance->sendTokens($amountWei)->shouldBe($responseHash);
    }

    public function it_should_send_sfuel_with_default_amount(
        User $sender
    ) {
        $receiverAddress = '0x01';
        $senderAddress = '0x02';
        $senderXpriv = '~xpriv~';
        $responseHash = '0x3';
        $gasLimitHex = '0x5208';

        $this->keys->withUser($sender)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->keys);

        $this->keys->getSecp256k1PrivateKeyAsHex()
            ->shouldBeCalled()
            ->willReturn($senderXpriv);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($senderAddress);

        $this->skaleClient->sendRawTransaction(
            $senderXpriv,
            Argument::that(function ($arg) use (
                $senderAddress,
                $receiverAddress,
                $gasLimitHex,
            ) {
                return $arg['from'] === $senderAddress &&
                    $arg['to'] === $receiverAddress &&
                    $arg['gasLimit'] === $gasLimitHex &&
                    $arg['value'] === '220000000000';
            })
        )
            ->shouldBeCalled()
            ->willReturn($responseHash);

        $instance = $this->withUsers($sender, null, $receiverAddress);

        $instance->sendSFuel()->shouldBe($responseHash);
    }

    public function it_should_send_sfuel_with_custom_amount(
        User $sender
    ) {
        $amountWei = '1000000000';
        $receiverAddress = '0x01';
        $senderAddress = '0x02';
        $senderXpriv = '~xpriv~';
        $responseHash = '0x3';
        $gasLimitHex = '0x5208';

        $this->keys->withUser($sender)
            ->shouldBeCalledTimes(2)
            ->willReturn($this->keys);

        $this->keys->getSecp256k1PrivateKeyAsHex()
            ->shouldBeCalled()
            ->willReturn($senderXpriv);

        $this->keys->getWalletAddress()
            ->shouldBeCalled()
            ->willReturn($senderAddress);

        $this->skaleClient->sendRawTransaction(
            $senderXpriv,
            Argument::that(function ($arg) use (
                $senderAddress,
                $receiverAddress,
                $gasLimitHex,
                $amountWei
            ) {
                return $arg['from'] === $senderAddress &&
                    $arg['to'] === $receiverAddress &&
                    $arg['gasLimit'] === $gasLimitHex &&
                    $arg['value'] === $amountWei;
            })
        )
            ->shouldBeCalled()
            ->willReturn($responseHash);

        $instance = $this->withUsers($sender, null, $receiverAddress);

        $instance->sendSFuel($amountWei)->shouldBe($responseHash);
    }
}
