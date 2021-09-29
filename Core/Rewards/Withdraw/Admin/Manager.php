<?php
/**
 * Admin Withdrawal Manager
 * @package Minds\Core\Rewards\Withdraw\Admin
 */
namespace Minds\Core\Rewards\Withdraw\Admin;

use Exception;
use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Blockchain\Util;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Rewards\Withdraw\Repository;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Core\Util\BigNumber;
use Minds\Core\Rewards\Withdraw\Manager as WithdrawManager;

class Manager
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

    public function __construct(
        WithdrawManager $withdrawManager = null,
        Ethereum $eth = null,
        Repository $repository = null,
        Logger $logger = null,
        Config $config = null
    ) {
        $this->withdrawManager = $withdrawManager ?? Di::_()->get('Rewards\Withdraw\Manager');
        $this->eth = $eth ?? Di::_()->get('Blockchain\Services\Ethereum');
        $this->repository = $repository ?? Di::_()->get('Rewards\Withdraw\Repository');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Gets transaction from request.
     * @param Request $request
     * @param boolean $hydrate
     * @return Request|null
     */
    public function get(Request $request, $hydrate = false): ?Request
    {
        return $this->withdrawManager->get($request, $hydrate);
    }

    /**
     * Adds a missing transaction in when the transaction hasn't been picked up at all
     * by the system.
     * @param string $tx - transaction id.
     * @return void
     */
    public function addMissingWithdrawal(string $txid = ''): void
    {
        // get and parse data from blockchain.
        $response = $this->eth->request('eth_getTransactionReceipt', [ $txid ]);
        $logs = $response['logs'];
        $log = $logs[0];

        list($address, $userGuid, $gas, $amount) = Util::parseData($log['data'], [Util::ADDRESS, Util::NUMBER, Util::NUMBER, Util::NUMBER]);

        $userGuid = BigNumber::fromHex($userGuid)->toInt();
        $gas = (string) BigNumber::fromHex($gas);
        $amount = (string) BigNumber::fromHex($amount);

        // make a fresh withdrawal request and push it into the system.
        $withdrawRequest = new Request();
        $withdrawRequest->setUserGuid($userGuid)
            ->setGas($gas)
            ->setAmount($amount)
            ->setAddress($address)
            ->setTimestamp(time())
            ->setTx($txid);

        $this->withdrawManager->request($withdrawRequest);
    }

    /**
     * Force a withdrawal stuck in 'pending' state to 'pending_approval' manually.
     * User must manually ensure that the transaction is appropriately confirmed onchain.
     * @param Request $request
     * @return void
     */
    public function forceConfirmation(Request $request = null): void
    {
        // cast request into transaction object
        $transaction = $this->buildTransactionFromRequest($request);
        
        // manually confirm transaction to push to pending_approval state.
        $this->withdrawManager->confirm($request, $transaction);
    }

    /**
     * Repairs a completed withdrawal that is not registered onchain
     * by re-dispatching a stuck transaction.
     * @param Request $request
     * @return ?string - string or null
     */
    public function redispatchCompleted(Request $request = null): ?string
    {
        // if we're in an approved state with no txid, something has gone wrong.
        if (!$request->getCompletedTx()) {
            $this->logger->error('repairCompletedWithdrawal fn called with missing completed_tx field for user' . $request->getUserGuid());
            throw new Exception('Unable to re-dispatch non-existent completed_tx');
        }
        
        // request tx from eth network.
        $tx = $this->eth->request('eth_getTransactionByHash', [ $request->getCompletedTx() ]);
        
        // if its dispatched on the eth network, the tx has correctly completed.
        if ($tx) {
            throw new Exception('Transaction already written to chain');
        }

        // dispatch a new transaction to replace the old one.
        $newTx = $this->eth->sendRawTransaction($this->config->get('blockchain')['contracts']['withdraw']['wallet_pkey'], [
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
        
        // if successful, update db.
        if ($newTx) {
            $request->setCompletedTx($newTx);
            $this->repository->add($request);
            return $newTx;
        }

        return null;
    }

    /**
     * Collects pending transactions older than 72 hours and fails them.
     * @return void
     */
    public function runGarbageCollection(): void
    {
        $response = $this->withdrawManager->getList([
            'status' => 'pending',
            'to' => strtotime('72 hours ago'),
            'limit' => 500,
        ]);

        foreach ($response as $request) {
            $this->withdrawManager->fail($request);
        }
    }

    /**
     * Fails / garbage collects a single transaction.
     * @param Request|null $request
     * @return boolean
     */
    public function runGarbageCollectionSingle(Request $request = null): bool
    {
        return $this->withdrawManager->fail($request);
    }

    /**
     * Builds a Transaction object from a Request object.
     * @param Request $request - Request object.
     * @return Transaction - Transaction object.
     */
    private function buildTransactionFromRequest(Request $request): Transaction
    {
        return (new Transaction())
            ->setTx($request->getTx())
            ->setContract('withdraw')
            ->setAmount($request->getAmount())
            ->setWalletAddress($request->getAddress())
            ->setTimestamp($request->getTimestamp())
            ->setUserGuid($request->getUserGuid())
            ->setCompleted(!!$request->getCompletedTx())
            ->setData([
                'amount' => $request->getAmount(),
                'gas' => $request->getGas(),
                'address' => $request->getAddress(),
            ]);
    }
}
