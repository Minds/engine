<?php
//ojm add emailDelegate
namespace Spec\Minds\Core\Rewards\Withdraw;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Blockchain\Transactions\Manager as TransactionsManager;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Blockchain\Wallets\OffChain\Balance as OffchainBalance;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Config;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Rewards\Withdraw\Delegates;
use Minds\Core\Rewards\Withdraw\Manager;
use Minds\Core\Rewards\Withdraw\Repository;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var TransactionsManager */
    protected $txManager;

    /** @var OffchainTransactions */
    protected $offChainTransactions;

    /** @var Config */
    protected $config;

    /** @var Ethereum */
    protected $eth;

    /** @var Repository */
    protected $repository;

    /** @var OffchainBalance */
    protected $offChainBalance;

    /** @var Delegates\NotificationsDelegate */
    protected $notificationsDelegate;

    /** @var Delegates\EmailDelegate */
    protected $emailDelegate;

    /** @var Delegates\RequestHydrationDelegate */
    protected $requestHydrationDelegate;

    public function let(
        TransactionsManager $txManager,
        OffchainTransactions $offChainTransactions,
        Config $config,
        Ethereum $eth,
        Repository $repository,
        OffchainBalance $offChainBalance,
        Delegates\NotificationsDelegate $notificationsDelegate,
        Delegates\EmailDelegate $emailDelegate,
        Delegates\RequestHydrationDelegate $requestHydrationDelegate
    ) {
        $this->beConstructedWith(
            $txManager,
            $offChainTransactions,
            $config,
            $eth,
            $repository,
            $offChainBalance,
            $notificationsDelegate,
            $emailDelegate,
            $requestHydrationDelegate
        );

        $this->txManager = $txManager;
        $this->offChainTransactions = $offChainTransactions;
        $this->config = $config;
        $this->eth = $eth;
        $this->repository = $repository;
        $this->offChainBalance = $offChainBalance;
        $this->notificationsDelegate = $notificationsDelegate;
        $this->emailDelegate = $emailDelegate;
        $this->requestHydrationDelegate = $requestHydrationDelegate;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_check()
    {
        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'contracts' => [
                    'withdraw' => [
                        'limit_exemptions' => [1001],
                    ],
                ],
            ]);

        $this->repository->getList([
            'user_guid' => 1000,
            'from' => strtotime('-1 day'),
        ])
            ->shouldBeCalled()
            ->willReturn([
                'withdrawals' => [],
            ]);

        $this
            ->check(1000)
            ->shouldReturn(true);
    }

    public function it_should_check_and_fail(
        Request $request
    ) {
        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'contracts' => [
                    'withdraw' => [
                        'limit_exemptions' => [1001],
                    ],
                ],
            ]);

        $this->repository->getList([
            'user_guid' => 1000,
            'from' => strtotime('-1 day'),
        ])
            ->shouldBeCalled()
            ->willReturn([
                'withdrawals' => [
                    $request,
                ],
            ]);

        $this
            ->check(1000)
            ->shouldReturn(false);
    }

    public function it_should_check_bypassing_limits()
    {
        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'contracts' => [
                    'withdraw' => [
                        'limit_exemptions' => [1001],
                    ],
                ],
            ]);

        $this
            ->check(1001)
            ->shouldReturn(true);
    }

    public function it_should_get_list(
        Request $request
    ) {
        $opts = [
            'user_guid' => 1000,
            'hydrate' => true,
            'admin' => true,
        ];

        $this->repository->getList($opts)
            ->shouldBeCalled()
            ->willReturn([
                'withdrawals' => [
                    $request,
                ],
                'load-next' => 'phpspec',
            ]);

        $this->requestHydrationDelegate->hydrate($request)
            ->shouldBeCalled()
            ->willReturn($request);

        $this->requestHydrationDelegate->hydrateForAdmin($request)
            ->shouldBeCalled()
            ->willReturn($request);

        $this
            ->getList($opts)
            ->shouldReturnAnInstanceOf(Response::class);
    }

    public function it_should_get(
        Request $requestRef,
        Request $request
    ) {
        $requestRef->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $requestRef->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(123456789);

        $requestRef->getTx()
            ->shouldBeCalled()
            ->willReturn('0xf00847');

        $this->repository->getList([
            'user_guid' => 1000,
            'timestamp' => 123456789,
            'tx' => '0xf00847',
            'limit' => 1,
        ])
            ->shouldBeCalled()
            ->willReturn([
                'withdrawals' => [
                    $request,
                ],
                'load-next' => 'phpspec',
            ]);

        $this->requestHydrationDelegate->hydrate($request)
            ->shouldBeCalled()
            ->willReturn($request);

        $this
            ->get($requestRef, true)
            ->shouldReturn($request);
    }

    public function it_should_request(
        Request $request
    ) {
        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $request->getTx()
            ->shouldBeCalled()
            ->willReturn('0xf00847');

        $request->getAddress()
            ->shouldBeCalled()
            ->willReturn('0x303456');

        $request->getTimestamp()
            ->shouldBeCalled()
            ->willReturn(123456789);

        $request->getGas()
            ->shouldBeCalled()
            ->willReturn('100000000000');

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'contracts' => [
                    'withdraw' => [
                        'limit_exemptions' => [1001],
                    ],
                ],
            ]);

        $this->repository->getList([
            'user_guid' => 1000,
            'from' => strtotime('-1 day'),
        ])
            ->shouldBeCalled()
            ->willReturn([
                'withdrawals' => [],
            ]);

        $this->offChainBalance->setUser(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->offChainBalance);

        $this->offChainBalance->getAvailable()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(1000, 18));

        $request->setStatus('pending')
            ->shouldBeCalled()
            ->willReturn($request);

        $this->repository->add($request)
            ->shouldBeCalled();

        $this->txManager->add(Argument::type(Transaction::class))
            ->shouldBeCalled();

        $this->notificationsDelegate->onRequest($request)
            ->shouldBeCalled();

        $this->emailDelegate->onRequest($request)
            ->shouldBeCalled();

        $this
            ->request($request)
            ->shouldReturn(true);
    }

    public function it_should_throw_during_request_if_got_past_allowance(
        Request $request
    ) {
        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'contracts' => [
                    'withdraw' => [
                        'limit_exemptions' => [1001],
                    ],
                ],
            ]);

        $this->repository->getList([
            'user_guid' => 1000,
            'from' => strtotime('-1 day'),
        ])
            ->shouldBeCalled()
            ->willReturn([
                'withdrawals' => [],
            ]);

        $this->offChainBalance->setUser(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->offChainBalance);

        $this->offChainBalance->getAvailable()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(5, 18));

        $this
            ->shouldThrow(new Exception('You can only request 5 tokens.'))
            ->duringRequest($request);
    }

    public function it_should_throw_during_request_if_already_withdrawn(
        Request $request
    ) {
        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'contracts' => [
                    'withdraw' => [
                        'limit_exemptions' => [1001],
                    ],
                ],
            ]);

        $this->repository->getList([
            'user_guid' => 1000,
            'from' => strtotime('-1 day'),
        ])
            ->shouldBeCalled()
            ->willReturn([
                'withdrawals' => [
                    $request,
                ],
            ]);

        $this
            ->shouldThrow(new Exception('A withdrawal has already been requested in the last 24 hours'))
            ->duringRequest($request);
    }

    public function it_should_confirm(
        Request $request,
        Transaction $transaction
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAddress()
            ->shouldBeCalled()
            ->willReturn('0x303456');

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $request->getGas()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(1, 18));

        $transaction->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'address' => '0x303456',
                'amount' => BigNumber::toPlain(10, 18),
                'gas' => BigNumber::toPlain(1, 18),
            ]);

        $this->offChainTransactions
            ->setUser(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->setType('withdraw')
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->setAmount((string) BigNumber::toPlain(10, 18)->neg())
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->create()
            ->shouldBeCalled()
            ->willReturn(true);

        $request->setStatus('pending_approval')
            ->shouldBeCalled()
            ->willReturn($request);

        $this->repository->add($request)
            ->shouldBeCalled();

        $this->notificationsDelegate->onConfirm($request)
            ->shouldBeCalled();

        $this->emailDelegate->onConfirm($request)
            ->shouldBeCalled();

        $this
            ->confirm($request, $transaction)
            ->shouldReturn(true);
    }

    public function it_should_add_tx_back_if_locked_during_confirm(
        Request $request,
        Transaction $transaction
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAddress()
            ->shouldBeCalled()
            ->willReturn('0x303456');

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $request->getGas()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(1, 18));

        $transaction->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'address' => '0x303456',
                'amount' => BigNumber::toPlain(10, 18),
                'gas' => BigNumber::toPlain(1, 18),
            ]);

        $this->offChainTransactions
            ->setUser(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->setType('withdraw')
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->setAmount((string) BigNumber::toPlain(10, 18)->neg())
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->create()
            ->shouldBeCalled()
            ->willThrow(new LockFailedException());

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->confirm($request, $transaction)
            ->shouldReturn(false);
    }

    public function it_should_throw_during_confirm_if_not_pending(
        Request $request,
        Transaction $transaction
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('rejected');

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('Request is not pending'))
            ->duringConfirm($request, $transaction);
    }

    public function it_should_throw_during_confirm_if_amount_is_negative(
        Request $request,
        Transaction $transaction
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18)->neg());

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('The withdraw amount must be positive'))
            ->duringConfirm($request, $transaction);
    }

    public function it_should_throw_during_confirm_if_user_does_not_match(
        Request $request,
        Transaction $transaction
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $transaction->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1001);

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('The user who requested this operation does not match the transaction'))
            ->duringConfirm($request, $transaction);
    }

    public function it_should_throw_during_confirm_if_address_does_not_match(
        Request $request,
        Transaction $transaction
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAddress()
            ->shouldBeCalled()
            ->willReturn('0x303456');

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $transaction->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'address' => '0x998877',
                'amount' => BigNumber::toPlain(10, 18),
                'gas' => BigNumber::toPlain(1, 18),
            ]);

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('The address does not match the transaction'))
            ->duringConfirm($request, $transaction);
    }

    public function it_should_throw_during_confirm_if_amount_does_not_match(
        Request $request,
        Transaction $transaction
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAddress()
            ->shouldBeCalled()
            ->willReturn('0x303456');

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $transaction->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'address' => '0x303456',
                'amount' => BigNumber::toPlain(50, 18),
                'gas' => BigNumber::toPlain(1, 18),
            ]);

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('The amount request does not match the transaction'))
            ->duringConfirm($request, $transaction);
    }

    public function it_should_throw_during_confirm_if_gas_does_not_match(
        Request $request,
        Transaction $transaction
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAddress()
            ->shouldBeCalled()
            ->willReturn('0x303456');

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $request->getGas()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(1, 18));

        $transaction->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $transaction->getData()
            ->shouldBeCalled()
            ->willReturn([
                'address' => '0x303456',
                'amount' => BigNumber::toPlain(10, 18),
                'gas' => BigNumber::toPlain(2, 18),
            ]);

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('The gas requested does not match the transaction'))
            ->duringConfirm($request, $transaction);
    }

    public function it_should_fail(
        Request $request
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->setStatus('failed')
            ->shouldBeCalled()
            ->willReturn($request);

        $this->repository->add($request)
            ->shouldBeCalled();

        $this->notificationsDelegate->onFail($request)
            ->shouldBeCalled();

        $this->emailDelegate->onFail($request)
            ->shouldBeCalled();

        $this
            ->fail($request)
            ->shouldReturn(true);
    }

    public function it_should_throw_during_fail_if_not_pending(
        Request $request
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('rejected');

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('Request is not pending'))
            ->duringFail($request);
    }

    public function it_should_approve(
        Request $request
    ) {
        $this->config->get('blockchain')
            ->shouldBeCalled()
            ->willReturn([
                'server_gas_price' => 100,
                'contracts' => [
                    'withdraw' => [
                        'wallet_pkey' => '',
                        'wallet_address' => '',
                        'contract_address' => '',
                    ],
                ],
            ]);

        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending_approval');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAddress()
            ->shouldBeCalled()
            ->willReturn('0x303456');

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $request->getGas()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(1, 18));

        $this->eth->encodeContractMethod(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn('~encoded_contract_method~');

        $this->eth->sendRawTransaction(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn('0xf00847');

        $request->setStatus('approved')
            ->shouldBeCalled()
            ->willReturn($request);

        $request->setCompletedTx('0xf00847')
            ->shouldBeCalled()
            ->willReturn($request);

        $request->setCompleted(true)
            ->shouldBeCalled()
            ->willReturn($request);

        $this->repository->add($request)
            ->shouldBeCalled();

        $this->notificationsDelegate->onApprove($request)
            ->shouldBeCalled();

        $this->emailDelegate->onApprove($request)
            ->shouldBeCalled();

        $this
            ->approve($request)
            ->shouldReturn(true);
    }

    public function it_should_throw_during_approve_if_not_pending_approval(
        Request $request
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('Request is not pending approval'))
            ->duringApprove($request);
    }

    public function it_should_reject(
        Request $request
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending_approval');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $this->offChainTransactions
            ->setUser(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->setType('withdraw_refund')
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->setAmount((string) BigNumber::toPlain(10, 18))
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->create()
            ->shouldBeCalled()
            ->willReturn(true);

        $request->setStatus('rejected')
            ->shouldBeCalled()
            ->willReturn($request);

        $this->repository->add($request)
            ->shouldBeCalled();

        $this->notificationsDelegate->onReject($request)
            ->shouldBeCalled();

        $this->emailDelegate->onReject($request)
            ->shouldBeCalled();

        $this
            ->reject($request)
            ->shouldReturn(true);
    }

    public function it_should_throw_during_reject_if_locked(
        Request $request
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending_approval');

        $request->getUserGuid()
            ->shouldBeCalled()
            ->willReturn(1000);

        $request->getAmount()
            ->shouldBeCalled()
            ->willReturn(BigNumber::toPlain(10, 18));

        $this->offChainTransactions
            ->setUser(Argument::type(User::class))
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->setType('withdraw_refund')
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->setAmount((string) BigNumber::toPlain(10, 18))
            ->shouldBeCalled()
            ->willReturn($this->offChainTransactions);

        $this->offChainTransactions
            ->create()
            ->shouldBeCalled()
            ->willThrow(new LockFailedException());

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('Cannot refund rejected withdrawal tokens'))
            ->duringReject($request);
    }

    public function it_should_throw_during_reject_if_not_pending_approval(
        Request $request
    ) {
        $request->getStatus()
            ->shouldBeCalled()
            ->willReturn('pending');

        $this->repository->add($request)
            ->shouldNotBeCalled();

        $this
            ->shouldThrow(new Exception('Request is not pending approval'))
            ->duringReject($request);
    }
}
