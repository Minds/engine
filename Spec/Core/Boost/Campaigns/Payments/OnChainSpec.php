<?php

namespace Spec\Minds\Core\Boost\Campaigns\Payments;

use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Blockchain\Transactions\Manager as TransactionsManager;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Boost\Campaigns\Payments\OnChain;
use Minds\Core\Boost\Campaigns\Payments\Payment;
use Minds\Core\Boost\Campaigns\Payments\Repository;
use Minds\Core\Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OnChainSpec extends ObjectBehavior
{
    /** @var Config */
    protected $config;
    /** @var Ethereum */
    protected $eth;
    /** @var Repository */
    protected $repository;
    /** @var TransactionsManager */
    protected $txManager;

    public function let(Config $config, Ethereum $eth, Repository $repository, TransactionsManager $txManager)
    {
        $this->beConstructedWith($config, $eth, $repository, $txManager);

        $this->config = $config;
        $this->eth = $eth;
        $this->repository = $repository;
        $this->txManager = $txManager;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OnChain::class);
    }

    public function it_should_throw_an_exception_if_transaction_already_exists(Payment $payment)
    {
        $tx = 'some_tx_value';
        $payment->getTx()->shouldBeCalled()->willReturn($tx);
        $this->txManager->exists($tx)->shouldBeCalled()->willReturn(true);
        $this->shouldThrow(\Exception::class)->duringRecord($payment);
    }

    public function it_should_record_a_transaction(Payment $payment)
    {
        $tx = 'some_tx_value';
        $payment->getTx()->shouldBeCalled()->willReturn($tx);
        $this->txManager->exists($tx)->shouldBeCalled()->willReturn(false);
        $payment->getAmount()->shouldBeCalled()->willReturn(1);
        $payment->getSource()->shouldBeCalled()->willReturn('0x1234');
        $payment->getTimeCreated()->shouldBeCalled()->willReturn(1570191034);
        $payment->getOwnerGuid()->shouldBeCalled()->willReturn();
        $payment->export()->shouldBeCalled()->willReturn([]);

        $this->repository->add($payment)->shouldBeCalled();
        $this->txManager->add(Argument::type(Transaction::class))->shouldBeCalled();

        $this->record($payment);
    }

    public function it_should_throw_an_exception_if_invalid_token_contract_address_on_refund(Payment $payment)
    {
        $blockchainConfig = [
            'token_address' => null,
            'contracts' => [
                'boost_campaigns' => [
                    'wallet_address' => '0x123abc',
                    'wallet_pkey' => '0x123abc'
                ]
            ]
        ];

        $this->config->get('blockchain')->willReturn($blockchainConfig);
        $this->shouldThrow(\Exception::class)->duringRefund($payment);
    }

    public function it_should_throw_an_exception_if_invalid_wallet_address_on_refund(Payment $payment)
    {
        $blockchainConfig = [
            'token_address' => '0x123abc',
            'contracts' => [
                'boost_campaigns' => [
                    'wallet_address' => null,
                    'wallet_pkey' => '0x123abc'
                ]
            ]
        ];

        $this->config->get('blockchain')->willReturn($blockchainConfig);
        $this->shouldThrow(\Exception::class)->duringRefund($payment);
    }

    public function it_should_throw_an_exception_if_invalid_destination_wallet_address_on_refund(Payment $payment)
    {
        $blockchainConfig = [
            'token_address' => '0x123abc',
            'contracts' => [
                'boost_campaigns' => [
                    'wallet_address' => '0x123abc',
                    'wallet_pkey' => '0x123abc'
                ]
            ]
        ];

        $this->config->get('blockchain')->willReturn($blockchainConfig);
        $payment->getSource()->shouldBeCalled()->willReturn(null);
        $this->shouldThrow(\Exception::class)->duringRefund($payment);
    }

    public function it_should_throw_an_exception_if_positive_amount_on_refund(Payment $payment)
    {
        $blockchainConfig = [
            'token_address' => '0x123abc',
            'contracts' => [
                'boost_campaigns' => [
                    'wallet_address' => '0x123abc',
                    'wallet_pkey' => '0x123abc'
                ]
            ]
        ];

        $this->config->get('blockchain')->willReturn($blockchainConfig);
        $payment->getSource()->shouldBeCalled()->willReturn('0xabc123');
        $payment->getAmount()->shouldBeCalled()->willReturn(2);
        $this->shouldThrow(\Exception::class)->duringRefund($payment);
    }

    public function it_should_record_a_refund(Payment $payment)
    {
        $blockchainConfig = [
            'token_address' => '0x123abc',
            'contracts' => [
                'boost_campaigns' => [
                    'wallet_address' => '0x123abc',
                    'wallet_pkey' => '0x123abc'
                ]
            ]
        ];

        $this->config->get('blockchain')->willReturn($blockchainConfig);
        $payment->getSource()->shouldBeCalled()->willReturn('0xabc123');
        $payment->getAmount()->shouldBeCalled()->willReturn(-2);
        $this->eth->sendRawTransaction(Argument::type('string'), Argument::type('array'))->willReturn('0xdef456');
        $this->eth->encodeContractMethod('transfer(address,uint256)', [
            '0xabc123', '0x1bc16d674ec80000'
        ])->shouldBeCalled()->willReturn('');
        $payment->setTx('0xdef456')->shouldBeCalled();
        $payment->getTx()->shouldBeCalled()->willReturn('0xdef456');
        $payment->getTimeCreated()->shouldBeCalled()->willReturn(1570191034);
        $payment->getOwnerGuid()->shouldBeCalled()->willReturn(1234);
        $payment->export()->shouldBeCalled()->willReturn([]);

        $this->repository->add($payment)->shouldBeCalled();
        $this->txManager->add(Argument::type(Transaction::class), false)->shouldBeCalled();

        $this->refund($payment);
    }
}
