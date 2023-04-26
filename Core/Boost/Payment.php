<?php

namespace Minds\Core\Boost;

use Minds\Core;
use Minds\Core\Blockchain\Services;
use Minds\Core\Boost\Network\Boost;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments;
use Minds\Core\Util\BigNumber;
use Minds\Entities\Boost\Peer;
use Minds\Entities\User;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Exceptions\UserErrorException;

class Payment
{
    /** @var Payments\Stripe\Stripe */
    protected $stripePayments;

    /** @var Config */
    protected $config;

    /** @var Core\Blockchain\Transactions\Manager */
    protected $txManager;

    /** @var Core\Blockchain\Transactions\Repository */
    protected $txRepository;

    /** @var Pending */
    protected $boostPending;

    /** @var Lock */
    protected $locks;

    /** @var Services\Ethereum */
    protected $eth;

    public function __construct(
        $stripePayments = null,
        $eth = null,
        $txManager = null,
        $txRepository = null,
        $config = null,
        $locks = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?CashPaymentProcessor $cashPaymentProcessor = null
    ) {
        $this->stripePayments = $stripePayments ?: Di::_()->get('StripePayments');
        $this->eth = $eth ?: Di::_()->get('Blockchain\Services\Ethereum');
        $this->txManager = $txManager ?: Di::_()->get('Blockchain\Transactions\Manager');
        $this->txRepository = $txRepository ?: Di::_()->get('Blockchain\Transactions\Repository');
        $this->config = $config ?: Di::_()->get('Config');
        $this->locks = $locks ?: Di::_()->get('Database\Locks');
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->cashPaymentProcessor ??= new CashPaymentProcessor();
    }

    /**
     * Returns the User object representing the minds channel that receives the boost funds when request accepted by the Admin team
     * @return User
     */
    private function getMindsBoostWalletUser(): User
    {
        return $this->entitiesBuilder->single($this->config->get('boost')["offchain_wallet_guid"]);
    }

    /**
     * @param Network|Peer|Network\Boost|Peer\Boost $boost
     * @param $payload
     * @return null
     * @throws \Exception
     */
    public function pay($boost, $payload)
    {
        $currency = method_exists($boost, 'getMethod') ?
            $boost->getMethod() : $boost->getBidType();

        switch ($currency) {
            case 'cash':
                $handler = method_exists($boost, 'getHandler')
                    ? $boost->getHandler()
                    : $boost->getBidType();

                if ($handler === 'peer' || $boost instanceof Peer) {
                    throw new \Exception('USD boost offers are not supported');
                }

                if (!isset($payload['payment_method_id'])) {
                    throw new UserErrorException('Payment method ID must be supplied');
                }

                return $this->cashPaymentProcessor->setupNetworkBoostStripePayment(
                    $payload['payment_method_id'],
                    $boost
                );
            case 'tokens':
                switch ($payload['method']) {
                    case 'offchain':
                        if ($boost->getHandler() === 'peer' && !$boost->getDestination()->getPhoneNumberHash()) {
                            throw new \Exception('Boost target should participate in the Rewards program.');
                        }

                        /** @var Core\Blockchain\Wallets\OffChain\Cap $cap */
                        $cap = Di::_()->get('Blockchain\Wallets\OffChain\Cap')
                            ->setUser($boost->getOwner())
                            ->setContract('boost');

                        if (!$cap->isAllowed($boost->getBid())) {
                            throw new \Exception('You are not allowed to spend that amount of coins.');
                        }

                        $txData = [
                            'amount' => (string) $boost->getBid(),
                            'guid' => (string) $boost->getGuid(),
                            'handler' => (string) $boost->getHandler(),
                        ];

                        if ($boost->getHandler() === 'peer') {
                            $txData['sender_guid'] = (string) $boost->getOwner()->guid;
                            $txData['receiver_guid'] = (string) $boost->getDestination()->guid;
                        }

                        /** @var Core\Blockchain\Wallets\OffChain\Transactions $sendersTx */
                        $sendersTx = Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
                        $tx = $sendersTx
                            ->setAmount((string) BigNumber::_($boost->getBid())->neg())
                            ->setType('boost')
                            ->setUser($boost->getOwner())
                            ->setData($txData)
                            ->create();

                        return $tx->getTx();
                    case 'onchain':
                        if ($boost->getHandler() === 'peer' && !$boost->getDestination()->getEthWallet()) {
                            throw new \Exception('Boost target should participate in the Rewards program.');
                        }

                        $txData = [
                            'amount' => (string) $boost->getBid(),
                            'guid' => (string) $boost->getGuid(),
                            'handler' => (string) $boost->getHandler()
                        ];

                        if ($boost->getHandler() === 'peer') {
                            $txData['sender_guid'] = (string) $boost->getOwner()->guid;
                            $txData['receiver_guid'] = (string) $boost->getDestination()->guid;
                        }

                        $sendersTx = new Core\Blockchain\Transactions\Transaction();
                        $sendersTx
                            ->setUserGuid($boost->getOwner()->guid)
                            ->setWalletAddress($payload['address'])
                            ->setContract('boost')
                            ->setTx($payload['txHash'])
                            ->setAmount((string) BigNumber::_($boost->getBid())->neg())
                            ->setTimestamp(time())
                            ->setCompleted(false)
                            ->setData($txData);
                        $this->txManager->add($sendersTx);

                        if ($boost->getHandler() === 'peer') {
                            $receiversTx = new Core\Blockchain\Transactions\Transaction();
                            $receiversTx
                                ->setUserGuid($boost->getDestination()->guid)
                                ->setWalletAddress($payload['address'])
                                ->setContract('boost')
                                ->setTx($payload['txHash'])
                                ->setAmount($boost->getBid())
                                ->setTimestamp(time())
                                ->setCompleted(false)
                                ->setData([
                                    'amount' => (string) $boost->getBid(),
                                    'guid' => (string) $boost->getGuid(),
                                    'handler' => (string) $boost->getHandler(),
                                    'sender_guid' => (string) $boost->getOwner()->guid,
                                    'receiver_guid' => (string) $boost->getDestination()->guid,
                                ]);
                            $this->txManager->add($receiversTx);
                        }

                        return $payload['txHash'];
                }
        }

        throw new \Exception('Payment Method not supported');
    }

    /**
     * @param Boost|Peer $boost
     * @return bool
     * @throws LockFailedException
     */
    public function charge($boost)
    {
        $currency = method_exists($boost, 'getMethod') ?
            $boost->getMethod() : $boost->getBidType();

        switch ($currency) {
            case 'points':
                return true; // Already charged
            case 'cash':
                return $this->cashPaymentProcessor->capturePaymentIntent(
                    $boost->getTransactionId()
                );
            case 'tokens':
                $method = '';
                $txIdMeta = '';

                if (stripos($boost->getTransactionId(), '0x') === 0) {
                    $method = 'onchain';
                } elseif (stripos($boost->getTransactionId(), 'oc:') === 0) {
                    $method = 'offchain';
                } elseif (stripos($boost->getTransactionId(), 'creditcard:') === 0) {
                    $method = 'creditcard';
                    $txIdMeta = explode(':', $boost->getTransactionId(), 2)[1];
                }

                switch ($method) {
                    case 'onchain':
                        $eth = Di::_()->get('Blockchain\Services\Ethereum');
                        $receipt = $eth->request('eth_getTransactionReceipt', [ $boost->getTransactionId() ]);

                        if (!$receipt || !isset($receipt['status'])) {
                            return false; //too soon
                        }

                        if ($receipt['status'] === '0x1') {
                            $guid = (string) BigNumber::fromHex($receipt['logs'][3]['data']);
                            return $boost->getGuid() === $guid;
                        }
                        return false;
                        break;
                    case 'offchain':
                        /** @var Core\Blockchain\Wallets\OffChain\Transactions $receiversTx */
                        $receiversTx = Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
                        $receiversTx
                            ->setAmount($boost->getBid())
                            ->setType('boost');
                        if ($boost->getHandler() === 'peer') {
                            $receiversTx->setUser($boost->getDestination())
                                ->setData([
                                    'amount' => (string) $boost->getBid(),
                                    'guid' => (string) $boost->getGuid(),
                                    'sender_guid' => (string) $boost->getOwner()->guid,
                                    'receiver_guid' => (string) $boost->getDestination()->guid,
                                ])
                                ->create();
                        } else {
                            /** @var Core\Blockchain\Wallets\OffChain\Transactions $receiversTx */
                            $receiversTx->setUser($this->getMindsBoostWalletUser())
                                ->setData([
                                    'amount' => (string) $boost->getBid(),
                                    'guid' => (string) $boost->getGuid(),
                                    'sender_guid' => (string) $boost->getOwner()->guid,
                                    'receiver_guid' => (string) $this->getMindsBoostWalletUser()->guid,
                                ])
                                ->create();
                        }

                        break;
                }

                return true; // Already charged
        }

        throw new \Exception('Payment Method not supported');
    }

    public function refund($boost)
    {
        $currency = method_exists($boost, 'getMethod') ?
            $boost->getMethod() : $boost->getBidType();

        if (in_array($currency, [ 'onchain', 'offchain' ], true)) {
            $currency = 'tokens';
        }

        switch ($currency) {
            case 'points':
                return true;
                break;
            case 'cash':
                return $this->cashPaymentProcessor->cancelPaymentIntent(
                    $boost->getTransactionId()
                );
            case 'tokens':
                $method = '';
                $txIdMeta = '';

                if (stripos($boost->getTransactionId(), '0x') === 0) {
                    $method = 'onchain';
                } elseif (stripos($boost->getTransactionId(), 'oc:') === 0) {
                    $method = 'offchain';
                } elseif (stripos($boost->getTransactionId(), 'creditcard:') === 0) {
                    $method = 'creditcard';
                    $txIdMeta = explode(':', $boost->getTransactionId(), 2)[1];
                }

                switch ($method) {
                    case 'onchain':
                        if ($boost->getHandler() === 'peer') {
                            // Already refunded
                            return true;
                        }

                        //get the transaction
                        $boostTransaction = $this->txRepository->get($boost->getOwner()->guid, $boost->getTransactionId());

                        //send the tokens back to the booster
                        $txHash = $this->eth->sendRawTransaction($this->config->get('blockchain')['boost_wallet_pkey'], [
                            'from' => $this->config->get('blockchain')['boost_wallet_address'],
                            'to' => $this->config->get('blockchain')['boost_address'],
                            'gasLimit' => BigNumber::_(200000)->toHex(true),
                            'data' => $this->eth->encodeContractMethod('reject(uint256)', [
                                BigNumber::_($boost->getGuid())->toHex(true)
                            ])
                        ]);

                        $refundTransaction = new Core\Blockchain\Transactions\Transaction();
                        $refundTransaction
                            ->setUserGuid($boost->getOwner()->guid)
                            ->setWalletAddress($boostTransaction->getWalletAddress())
                            ->setContract('boost')
                            ->setTx($txHash)
                            ->setAmount((string) BigNumber::_($boostTransaction->getAmount())->neg())
                            ->setTimestamp(time())
                            ->setCompleted(false)
                            ->setData([
                                'amount' => (string) $boost->getBid(),
                                'guid' => (string) $boost->getGuid(),
                                'handler' => (string) $boost->getHandler(),
                                'refund' => true,
                            ]);

                        $this->txManager->add($refundTransaction);
                        break;

                    case 'offchain':

                        $this->locks->setKey("boost:refund:{$boost->getGuid()}");
                        if ($this->locks->isLocked()) {
                            throw new LockFailedException();
                        }

                        $this->locks
                            ->setTTL(86400) //lock for 1 day
                            ->lock();

                        $txData = [
                            'amount' => (string) $boost->getBid(),
                            'guid' => (string) $boost->getGuid(),
                        ];

                        if ($boost->getHandler() === 'peer') {
                            $txData['sender_guid'] = (string) $boost->getOwner()->guid;
                            $txData['receiver_guid'] = (string) $boost->getDestination()->guid;
                        }

                        /** @var Core\Blockchain\Wallets\OffChain\Transactions $sendersTx */
                        $sendersTx = Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
                        $sendersTx
                            ->setAmount($boost->getBid())
                            ->setType('boost_refund')
                            ->setUser($boost->getOwner())
                            ->setData($txData)
                            ->create();

                        break;
                }



                return true;
        }

        throw new \Exception('Payment Method not supported');
    }
}
