<?php

/**
 * Blockchain Withdraw Events
 *
 * @author mark
 */

namespace Minds\Core\Blockchain\Events;

use Exception;
use Minds\Core\Blockchain\Transactions\Repository as TransactionsRepository;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Blockchain\Util;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Rewards\Withdraw\Manager;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Core\Util\BigNumber;

class WithdrawEvent implements BlockchainEventInterface
{
    /** @var array */
    public static $eventsMap = [
        '0x317c0f5ab60805d3e3fb6aaa61ccb77253bbb20deccbbe49c544de4baa4d7f8f' => 'onRequest',
        'blockchain:fail' => 'withdrawFail',
    ];

    /** @var Manager */
    protected $manager;

    /** @var TransactionsRepository **/
    protected $txRepository;

    /** @var Config */
    protected $config;

    /**
     * WithdrawEvent constructor.
     * @param Manager $manager
     * @param TransactionsRepository $txRepository
     * @param Config $config
     */
    public function __construct($manager = null, $txRepository = null, $config = null)
    {
        $this->txRepository = $txRepository ?: Di::_()->get('Blockchain\Transactions\Repository');
        $this->manager = $manager ?: Di::_()->get('Rewards\Withdraw\Manager');
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @return array
     */
    public function getTopics()
    {
        return array_keys(static::$eventsMap);
    }

    /**
     * @param $topic
     * @param array $log
     * @param $transaction
     * @throws Exception
     */
    public function event($topic, array $log, $transaction)
    {
        $method = static::$eventsMap[$topic];

        if ($log['address'] != $this->config->get('blockchain')['contracts']['withdraw']['contract_address']) {
            throw new Exception('Event does not match address');
        }

        if (method_exists($this, $method)) {
            $this->{$method}($log, $transaction);
        } else {
            throw new Exception('Method not found');
        }
    }

    public function onRequest($log, Transaction $transaction)
    {
        $address = $log['address'];

        if ($address != $this->config->get('blockchain')['contracts']['withdraw']['contract_address']) {
            $this->withdrawFail($log, $transaction);
            throw new Exception('Incorrect address sent the withdraw event');
        }

        $tx = $log['transactionHash'];
        list($address, $user_guid, $gas, $amount) = Util::parseData($log['data'], [Util::ADDRESS, Util::NUMBER, Util::NUMBER, Util::NUMBER]);
        $user_guid = BigNumber::fromHex($user_guid)->toInt();
        $gas = (string) BigNumber::fromHex($gas);
        $amount = (string) BigNumber::fromHex($amount);

        try {
            $request = $this->manager->get(
                (new Request())
                    ->setUserGuid($user_guid)
                    ->setTimestamp($transaction->getTimestamp())
                    ->setTx($tx)
            );

            if (!$request) {
                throw new \Exception('Unknown withdrawal');
            }

            if ((string) $address !== (string) $request->getAddress()) {
                throw new \Exception('Wrong address value');
            } elseif ((string) $gas !== (string) $request->getGas()) {
                throw new \Exception('Wrong gas value');
            } elseif ((string) $amount !== (string) $request->getAmount()) {
                throw new \Exception('Wrong amount value');
            }

            $this->manager->confirm($request, $transaction);
        } catch (Exception $e) {
            $this->manager->fail(
                (new Request())
                    ->setUserGuid($user_guid)
                    ->setTimestamp($transaction->getTimestamp())
                    ->setTx($tx)
            );

            error_log($e);
        }
    }

    public function withdrawFail($log, $transaction)
    {
        if ($transaction->getContract() !== 'withdraw') {
            throw new Exception("Failed but not a withdrawal");
        }

        $transaction->setFailed(true);

        $this->txRepository->update($transaction, [ 'failed' ]);
    }
}
