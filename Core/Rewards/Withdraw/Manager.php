<?php
/**
 * Manages reward withdrawals to the blockchain
 */
namespace Minds\Core\Rewards\Withdraw;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Blockchain\Transactions\Manager as TransactionsManager;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Blockchain\Wallets\OffChain\Balance as OffchainBalance;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Config;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;

class Manager
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

    public function __construct(
        $txManager = null,
        $offChainTransactions = null,
        $config = null,
        $eth = null,
        $repository = null,
        $offChainBalance = null,
        $notificationsDelegate = null,
        $emailDelegate = null,
        $requestHydrationDelegate = null
    ) {
        $this->txManager = $txManager ?: Di::_()->get('Blockchain\Transactions\Manager');
        $this->offChainTransactions = $offChainTransactions ?: Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
        $this->config = $config ?: Di::_()->get('Config');
        $this->eth = $eth ?: Di::_()->get('Blockchain\Services\Ethereum');
        $this->repository = $repository ?: new Repository();
        $this->offChainBalance = $offChainBalance ?: Di::_()->get('Blockchain\Wallets\OffChain\Balance');
        $this->notificationsDelegate = $notificationsDelegate ?: new Delegates\NotificationsDelegate();
        $this->emailDelegate = $emailDelegate ?: new Delegates\EmailDelegate();
        $this->requestHydrationDelegate = $requestHydrationDelegate ?: new Delegates\RequestHydrationDelegate();
    }

    /**
     * Checks if a withdrawal has been made in the last 24 hours
     * @param $userGuid
     * @return boolean
     */
    public function check($userGuid)
    {
        if (
            isset($this->config->get('blockchain')['contracts']['withdraw']['limit_exemptions'])
            && in_array($userGuid, $this->config->get('blockchain')['contracts']['withdraw']['limit_exemptions'], true)
        ) {
            return true;
        }

        $previousRequests = $this->repository->getList([
            'user_guid' => $userGuid,
            'from' => strtotime('-1 day')
        ]);

        return !$previousRequests
            || !isset($previousRequests['withdrawals'])
            || count($previousRequests['withdrawals']) === 0;
    }

    /**
     * @param array $opts
     * @return Response
     * @throws Exception
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'hydrate' => false,
            'admin' => false,
        ], $opts);

        $requests = $this->repository->getList($opts);

        $response = new Response();

        foreach ($requests['withdrawals'] ?? [] as $request) {
            if ($opts['hydrate']) {
                $request = $this->requestHydrationDelegate->hydrate($request);
            }

            if ($opts['admin']) {
                $request = $this->requestHydrationDelegate->hydrateForAdmin($request);
            }

            $response[] = $request;
        }

        $response
            ->setPagingToken(base64_encode($requests['load-next'] ?? ''));

        return $response;
    }

    /**
     * @param Request $request
     * @param bool $hydrate
     * @return Request|null
     * @throws Exception
     */
    public function get(Request $request, $hydrate = false): ?Request
    {
        if (
            !$request->getUserGuid() ||
            !$request->getTimestamp() ||
            !$request->getTx()
        ) {
            throw new Exception('Missing request keys');
        }

        $requests = $this->repository->getList([
            'user_guid' => $request->getUserGuid(),
            'timestamp' => $request->getTimestamp(),
            'tx' => $request->getTx(),
            'limit' => 1,
        ]);

        /** @var Request|null $request */
        $request = $requests['withdrawals'][0] ?? null;

        if ($request && $hydrate) {
            $request = $this->requestHydrationDelegate->hydrate($request);
        }

        return $request;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws Exception
     */
    public function request($request): bool
    {
        if (!$this->check($request->getUserGuid())) {
            throw new Exception('A withdrawal has already been requested in the last 24 hours');
        }

        $user = new User();
        $user->guid = (string) $request->getUserGuid();

        // Check how much tokens the user can request

        $available = BigNumber::_(
            $this->offChainBalance
                ->setUser($user)
                ->getAvailable()
        );

        if ($available->lt($request->getAmount())) {
            throw new Exception(sprintf(
                "You can only request %s tokens.",
                round(BigNumber::fromPlain($available, 18)->toDouble(), 4)
            ));
        }

        // Set request status

        $request
            ->setStatus('pending');

        // Setup transaction entity

        $transaction = new Transaction();
        $transaction
            ->setTx($request->getTx())
            ->setContract('withdraw')
            ->setAmount($request->getAmount())
            ->setWalletAddress($request->getAddress())
            ->setTimestamp($request->getTimestamp())
            ->setUserGuid($request->getUserGuid())
            ->setData([
                'amount' => $request->getAmount(),
                'gas' => $request->getGas(),
                'address' => $request->getAddress(),
            ]);

        // Update

        $this->repository->add($request);
        $this->txManager->add($transaction);

        // Notify

        $this->notificationsDelegate->onRequest($request);

        // Email

        $this->emailDelegate->onRequest($request);

        //

        return true;
    }

    /**
     * @param Request $request
     * @param Transaction $transaction - the transaction we store
     * @return bool
     * @throws Exception
     */
    public function confirm(Request $request, Transaction $transaction): bool
    {
        if ($request->getStatus() !== 'pending') {
            throw new Exception('Request is not pending');
        }

        if (BigNumber::_($request->getAmount())->lt(0)) {
            throw new Exception('The withdraw amount must be positive');
        }

        if ((string) $request->getUserGuid() !== (string) $transaction->getUserGuid()) {
            throw new Exception('The user who requested this operation does not match the transaction');
        }

        if (strtolower($request->getAddress()) !== strtolower($transaction->getData()['address'])) {
            throw new Exception('The address does not match the transaction');
        }

        if ($request->getAmount() != $transaction->getData()['amount']) {
            throw new Exception('The amount request does not match the transaction');
        }

        if ($request->getGas() != $transaction->getData()['gas']) {
            throw new Exception('The gas requested does not match the transaction');
        }

        $user = new User;
        $user->guid = (string) $request->getUserGuid();

        // Withhold user tokens

        try {
            $this->offChainTransactions
                ->setUser($user)
                ->setType('withdraw')
                ->setAmount((string) BigNumber::_($request->getAmount())->neg())
                ->create();
        } catch (LockFailedException $e) {
            $this->txManager->add($transaction);
            return false;
        }

        // Set request status

        $request
            ->setStatus('pending_approval');

        // Update

        $this->repository->add($request);

        // Notify

        $this->notificationsDelegate->onConfirm($request);

        // Email

        $this->emailDelegate->onConfirm($request);

        //

        return true;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws Exception
     */
    public function fail(Request $request): bool
    {
        if ($request->getStatus() !== 'pending') {
            throw new Exception('Request is not pending');
        }

        $user = new User;
        $user->guid = (string) $request->getUserGuid();

        // Set request status

        $request
            ->setStatus('failed');

        // Update

        $this->repository->add($request);

        // Notify

        $this->notificationsDelegate->onFail($request);

        // Email

        $this->emailDelegate->onFail($request);

        //

        return true;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws Exception
     */
    public function approve(Request $request): bool
    {
        if ($request->getStatus() !== 'pending_approval') {
            throw new Exception('Request is not pending approval');
        }

        // Send blockchain transaction

        $txHash = $this->eth->sendRawTransaction($this->config->get('blockchain')['contracts']['withdraw']['wallet_pkey'], [
            'from' => $this->config->get('blockchain')['contracts']['withdraw']['wallet_address'],
            'to' => $this->config->get('blockchain')['contracts']['withdraw']['contract_address'],
            'gasLimit' => BigNumber::_(87204)->toHex(true),
            'gasPrice' => BigNumber::_($this->config->get('blockchain')['server_gas_price'] * 1000000000)->toHex(true),
            'data' => $this->eth->encodeContractMethod('complete(address,uint256,uint256,uint256)', [
                $request->getAddress(),
                BigNumber::_($request->getUserGuid())->toHex(true),
                BigNumber::_($request->getGas())->toHex(true),
                BigNumber::_($request->getAmount())->toHex(true),
            ])
        ]);

        // Set request status

        $request
            ->setStatus('approved')
            ->setCompletedTx($txHash)
            ->setCompleted(true);

        // Update

        $this->repository->add($request);

        // Notify

        $this->notificationsDelegate->onApprove($request);

        // Email

        $this->emailDelegate->onApprove($request);

        //

        return true;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws Exception
     */
    public function reject(Request $request): bool
    {
        if ($request->getStatus() !== 'pending_approval') {
            throw new Exception('Request is not pending approval');
        }

        $user = new User;
        $user->guid = (string) $request->getUserGuid();

        // Refund tokens

        try {
            $this->offChainTransactions
                ->setUser($user)
                ->setType('withdraw_refund')
                ->setAmount((string) BigNumber::_($request->getAmount()))
                ->create();
        } catch (LockFailedException $e) {
            throw new Exception('Cannot refund rejected withdrawal tokens');
        }

        // Set request status

        $request
            ->setStatus('rejected');

        // Update

        $this->repository->add($request);

        // Notify

        $this->notificationsDelegate->onReject($request);

        // Email

        $this->emailDelegate->onReject($request);

        //

        return true;
    }
}
