<?php
namespace Spec\Minds\Core\Rewards\Withdraw\Admin;

use Minds\Common\Repository\Response;
use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Config;
use Minds\Core\Log\Logger;
use Minds\Core\Rewards\Withdraw\Manager as WithdrawManager;
use Minds\Core\Rewards\Withdraw\Admin\Manager;
use Minds\Core\Rewards\Withdraw\Repository;
use Minds\Core\Rewards\Withdraw\Request;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var WithdrawManager */
    protected $withdrawManager;

    /** @var Ethereum */
    protected $eth;

    /** @var Repository */
    protected $repository;

    /** @var Logger */
    protected $logger;

    /** @var Config */
    protected $config;


    public function let(
        WithdrawManager $withdrawManager,
        Ethereum $eth,
        Repository $repository,
        Logger $logger,
        Config $config
    ) {
        $this->withdrawManager = $withdrawManager;
        $this->eth = $eth;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->config = $config;

        $this->beConstructedWith(
            $withdrawManager,
            $eth,
            $repository,
            $logger,
            $config
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_from_withdraw_manager(
        Request $request
    ) {
        $this->withdrawManager->get($request, false)->shouldBeCalled();
        $this->get($request);
    }

    public function it_should_add_a_missing_withdrawal()
    {
        $txid = '0x2eb1afa1c9ae20cd016079fbe0997478567254d132e3601441c0fbb39bc91d43';

        $addressHex = '0x000000000000000000000000000000000000dEaD';
        $userGuidHex = '000000000000000000000000000000000000000000000000110b55efd240200e';
        $gasHex = '00000000000000000000000000000000000000000000000000484dc369134800';
        $amountHex = '000000000000000000000000000000000000000000000000892e932084bc8000';

        $this->eth->request('eth_getTransactionReceipt', [ $txid ])
            ->shouldBeCalled()
            ->willReturn([
                'logs' => [
                    [
                        'data' => [
                            $addressHex,
                            $userGuidHex,
                            $gasHex,
                            $amountHex
                        ]
                    ]
                ]
            ]);

        $this->withdrawManager->request(Argument::that(function ($request) {
            return is_numeric($request->getTimestamp()) &&
                $request->getAmount() === '9885000000000000000' &&
                $request->getUserGuid() === 1228169811901554702;// &&
            $request->getTx() === "0x2eb1afa1c9ae20cd016079fbe0997478567";
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->addMissingWithdrawal($txid);
    }

    public function it_should_force_blockchain_confirmation(
        Request $request
    ) {
        $request->getTx()->shouldBeCalled()->willReturn('0x000000000000000000');
        $request->getAmount()->shouldBeCalledTimes(2)->willReturn('123');
        $request->getAddress()->shouldBeCalledTimes(2)->willReturn('0x000000000000000001');
        $request->getTimestamp()->shouldBeCalled()->willReturn(123);
        $request->getUserGuid()->shouldBeCalled()->willReturn('321');
        $request->getCompletedTx()->shouldBeCalled()->willReturn('');
        $request->getGas()->shouldBeCalled()->willReturn('100');


        $this->withdrawManager->confirm($request, Argument::that(function ($transaction) {
            return $transaction->getTx() === '0x000000000000000000' &&
            $transaction->getContract() === 'withdraw' &&
            $transaction->getAmount() === '123' &&
            $transaction->getWalletAddress() === '0x000000000000000001' &&
            $transaction->getTimestamp() === 123 &&
            $transaction->getUserGuid() === '321' &&
            $transaction->getCompleted() === false &&
            $transaction->getData() === [
                'amount' => '123',
                'gas' => '100',
                'address' => '0x000000000000000001'
            ];
        }))->shouldBeCalled();

        $this->forceConfirmation($request);
    }

    public function it_should_redispatch_a_completed_tx(Request $request)
    {
        $completedTx = '0x000000000000000000';
        $walletAddress = '0x2';
        $contractAddress = '0x3';

        $request->getCompletedTx()->shouldBeCalled()->willReturn($completedTx);
        $request->getAddress()->shouldBeCalled()->willReturn('123');
        $request->getUserGuid()->shouldBeCalled()->willReturn(1000000);
        $request->getGas()->shouldBeCalled()->willReturn(1000000);
        $request->getAmount()->shouldBeCalled()->willReturn(1000000);
        
        $this->eth->request('eth_getTransactionByHash', [ $completedTx ])
            ->shouldBeCalled()
            ->willReturn(null);

        $this->config->get('blockchain')->shouldBeCalled()->willReturn([
            'contracts' => [
                'withdraw' => [
                    'wallet_pkey' => '0x1',
                    'wallet_address' => $walletAddress,
                    'contract_address' => $contractAddress
                ]
            ],
            'server_gas_price' => '10'
        ]);

        $encodedContractMethod = '0x0hex';

        $this->eth->encodeContractMethod(
            'complete(address,uint256,uint256,uint256)',
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn($encodedContractMethod);

        $responseTxid = '0x4';
        $this->eth->sendRawTransaction(
            "0x1",
            [
                "from" => $walletAddress,
                "to" => $contractAddress,
                "gasLimit" => "0x154a4",
                "gasPrice" => "0x2540be400",
                "data" => $encodedContractMethod
            ]
        )->shouldBeCalled()->willReturn($responseTxid);

        $request->setCompletedTx($responseTxid)->shouldBeCalled();
        $this->repository->add($request)->shouldBeCalled();

        $this->redispatchCompleted($request);
    }

    public function it_should_run_garbage_collection()
    {
        $request1 = (new Request());
        $request2 = (new Request());

        $response = new Response([
            $request1,
            $request2
        ]);

        $this->withdrawManager->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn($response);
        
        $this->withdrawManager->fail($request1)->shouldBeCalled();
        $this->withdrawManager->fail($request2)->shouldBeCalled();

        $this->runGarbageCollection();
    }

    public function it_should_garbage_collect_a_single_withdrawal()
    {
        $request = new Request();
        $this->withdrawManager->fail($request)->shouldBeCalled();
        $this->runGarbageCollectionSingle($request);
    }
}
