<?php

namespace Spec\Minds\Core\Boost\V3\Onchain;

use PhpSpec\ObjectBehavior;
use Minds\Core\Blockchain\Services\Ethereum as EthereumService;
use Minds\Core\Boost\V3\Enums\BoostAdminAction;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Onchain\AdminTransactionProcessor;
use Minds\Core\Config\Config;
use Minds\Core\Util\BigNumber;
use Minds\Exceptions\ServerErrorException;
use Prophecy\Argument;

class AdminTransactionProcessorSpec extends ObjectBehavior
{
    /** @var EthereumService */
    private $ethereumService;

    /** @var Config */
    private $config;

    public function let(
        EthereumService $ethereumService,
        Config $config
    ) {
        $this->ethereumService = $ethereumService;
        $this->config = $config;

        $this->beConstructedWith($ethereumService, $config);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(AdminTransactionProcessor::class);
    }

    public function it_should_send_an_accept_transaction(Boost $boost): void
    {
        $resultTxId = '0x123';
        $paymentTxId = '0x234';
        $boostGuid = '1234';
        $walletPkey = '0x999';
        $walletAddress = '0x2a7';
        $contractAddress = '0x5a0';
        $encodedData = '0x123123123';

        $boost->getPaymentTxId()
            ->shouldBeCalled()
            ->willReturn($paymentTxId);

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn($boostGuid);

        $boost->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn(144);

        $this->ethereumService->request('eth_getTransactionReceipt', [ $paymentTxId ])
            ->shouldBeCalled()
            ->willReturn([
                'status' => '0x1',
                'logs' => [
                    0 => ['data' => "0x000000000000000000000000000000000000000000000007ce66c50e28400000"],
                    1 => ['data' => "0x000000000000000000000000000000000000000000000007ce66c50e28400000"],
                    2 => [],
                    3 => ['data' => BigNumber::_($boostGuid)->toHex() ]
                ]
            ]);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'server_gas_price' => '1',
                'contracts' => [
                    'boost' => [
                        'wallet_pkey' => $walletPkey,
                        'wallet_address' => $walletAddress,
                        'contract_address' => $contractAddress
                    ]
                ]
            ]);

        $this->ethereumService->encodeContractMethod(
            'accept(uint256)',
            [ BigNumber::_($boostGuid)->toHex(true) ]
        )
            ->shouldBeCalled()
            ->willReturn($encodedData);

        $this->ethereumService->sendRawTransaction(
            $walletPkey,
            Argument::that(function ($arg) use ($walletAddress, $contractAddress, $encodedData) {
                return $arg['from'] === $walletAddress &&
                    $arg['to'] === $contractAddress &&
                    $arg['gasLimit'] === BigNumber::_(200000)->toHex(true) &&
                    //$arg['gasPrice'] === '0x1' &&
                    $arg['data'] === $encodedData;
            })
        )
            ->shouldBeCalled()
            ->willReturn($resultTxId);

        $this->send($boost, BoostAdminAction::ACCEPT)
            ->shouldBe($resultTxId);
    }

    public function it_should_send_a_reject_transaction(Boost $boost): void
    {
        $resultTxId = '0x123';
        $paymentTxId = '0x234';
        $boostGuid = '1234';
        $walletPkey = '0x999';
        $walletAddress = '0x2a7';
        $contractAddress = '0x5a0';
        $encodedData = '0x123123123';

        $boost->getPaymentTxId()
            ->shouldBeCalled()
            ->willReturn($paymentTxId);

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn($boostGuid);

        $boost->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn(144);

        $this->ethereumService->request('eth_getTransactionReceipt', [ $paymentTxId ])
            ->shouldBeCalled()
            ->willReturn([
                'status' => '0x1',
                'logs' => [
                    0 => ['data' => "0x000000000000000000000000000000000000000000000007ce66c50e28400000"],
                    1 => ['data' => "0x000000000000000000000000000000000000000000000007ce66c50e28400000"],
                    2 => [],
                    3 => ['data' => BigNumber::_($boostGuid)->toHex() ]
                ]
            ]);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'server_gas_price' => '1',
                'contracts' => [
                    'boost' => [
                        'wallet_pkey' => $walletPkey,
                        'wallet_address' => $walletAddress,
                        'contract_address' => $contractAddress
                    ]
                ]
            ]);

        $this->ethereumService->encodeContractMethod(
            'reject(uint256)',
            [ BigNumber::_($boostGuid)->toHex(true) ]
        )
            ->shouldBeCalled()
            ->willReturn($encodedData);

        $this->ethereumService->sendRawTransaction(
            $walletPkey,
            Argument::that(function ($arg) use ($walletAddress, $contractAddress, $encodedData) {
                return $arg['from'] === $walletAddress &&
                    $arg['to'] === $contractAddress &&
                    $arg['gasLimit'] === BigNumber::_(200000)->toHex(true) &&
                    //$arg['gasPrice'] === '0x1' &&
                    $arg['data'] === $encodedData;
            })
        )
            ->shouldBeCalled()
            ->willReturn($resultTxId);

        $this->send($boost, BoostAdminAction::REJECT)
            ->shouldBe($resultTxId);
    }

    public function it_should_NOT_send_an_accept_transaction_that_has_no_status_onchain(Boost $boost): void
    {
        $paymentTxId = '0x234';
        $walletPkey = '0x999';

        $boost->getPaymentTxId()
            ->shouldBeCalled()
            ->willReturn($paymentTxId);

        $this->ethereumService->request('eth_getTransactionReceipt', [ $paymentTxId ])
            ->shouldBeCalled()
            ->willReturn([]);

        $this->ethereumService->sendRawTransaction($walletPkey, Argument::any())
            ->shouldNotBeCalled();

        $this->send($boost, BoostAdminAction::ACCEPT)
            ->shouldBe('');
    }

    public function it_should_NOT_send_an_accept_transaction_that_has_no_matching_boost_guid(Boost $boost): void
    {
        $paymentTxId = '0x234';
        $boostGuid = '1234';
        $walletPkey = '0x999';

        $boost->getPaymentTxId()
            ->shouldBeCalled()
            ->willReturn($paymentTxId);

        $boost->getGuid()
            ->shouldBeCalled()
            ->willReturn($boostGuid);

        $boost->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn(144);

        $this->ethereumService->request('eth_getTransactionReceipt', [ $paymentTxId ])
            ->shouldBeCalled()
            ->willReturn([
                'status' => '0x1',
                'logs' => [
                    0 => ['data' => "0x000000000000000000000000000000000000000000000007ce66c50e28400000"],
                    1 => ['data' => "0x000000000000000000000000000000000000000000000007ce66c50e28400000"],
                    2 => [],
                    3 => ['data' => BigNumber::_('999999')->toHex() ]
                ]
            ]);

        $this->ethereumService->sendRawTransaction($walletPkey, Argument::any())
            ->shouldNotBeCalled()
            ->willReturn();

        $this->send($boost, BoostAdminAction::ACCEPT)
            ->shouldBe('');
    }

    public function it_should_NOT_send_an_accept_transaction_that_a_mismatch_between_blockchain_and_stored_amounts(Boost $boost): void
    {
        $paymentTxId = '0x234';
        $boostGuid = '1234';
        $walletPkey = '0x999';

        $boost->getPaymentTxId()
            ->shouldBeCalled()
            ->willReturn($paymentTxId);

        $boost->getPaymentAmount()
            ->shouldBeCalled()
            ->willReturn(144);

        $this->ethereumService->request('eth_getTransactionReceipt', [ $paymentTxId ])
            ->shouldBeCalled()
            ->willReturn([
                'status' => '0x1',
                'logs' => [
                    0 => ['data' => "0x123"],
                    1 => ['data' => "0x123"],
                    2 => [],
                    3 => ['data' => BigNumber::_($boostGuid)->toHex() ]
                ]
            ]);

        $this->ethereumService->sendRawTransaction($walletPkey, Argument::any())
            ->shouldNotBeCalled()
            ->willReturn();

        $this->shouldThrow(new ServerErrorException('Amount mismatch between blockchain and server'))->duringSend($boost, BoostAdminAction::ACCEPT);
    }
}
