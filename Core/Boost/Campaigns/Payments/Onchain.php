<?php

namespace Minds\Core\Boost\Campaigns\Payments;

use Exception;
use Minds\Core\Blockchain\Services\Ethereum;
use Minds\Core\Blockchain\Transactions\Manager as TransactionsManager;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

class Onchain
{
    /** @var Config */
    protected $config;
    /** @var Ethereum */
    protected $eth;
    /** @var Repository */
    protected $repository;
    /** @var TransactionsManager */
    protected $txManager;

    public function __construct(
        $config = null,
        $eth = null,
        $repository = null,
        $txManager = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->eth = $eth ?: Di::_()->get('Blockchain\Services\Ethereum');
        $this->repository = $repository ?: new Repository();
        $this->txManager = $txManager ?: Di::_()->get('Blockchain\Transactions\Manager');
    }

    /**
     * @param Payment $payment
     * @return bool
     * @throws Exception
     */
    public function record(Payment $payment)
    {
        if ($this->txManager->exists($payment->getTx())) {
            throw new Exception('Payment transaction already exists');
        }

        $transaction = new Transaction();
        $transaction
            ->setTx($payment->getTx())
            ->setContract('boost_campaign')
            ->setAmount((string) BigNumber::toPlain($payment->getAmount(), 18)->neg())
            ->setWalletAddress($payment->getSource())
            ->setTimestamp($payment->getTimeCreated())
            ->setUserGuid($payment->getOwnerGuid())
            ->setData([
                'payment' => $payment->export(),
            ]);

        $this->repository->add($payment);
        $this->txManager->add($transaction);

        return true;
    }

    /**
     * @param Payment $payment
     * @return Payment
     * @throws Exception
     */
    public function refund(Payment $payment)
    {
        $token = $this->config->get('blockchain')['token_address'];
        $wallet = $this->config->get('blockchain')['contracts']['boost_campaigns']['wallet_address'] ?? null;
        $walletKey = $this->config->get('blockchain')['contracts']['boost_campaigns']['wallet_pkey'] ?? null;

        if (!$token) {
            throw new Exception('Invalid token contract address used for refund');
        } elseif (!$wallet || !$walletKey) {
            throw new Exception('Invalid Boost Campaigns wallet address used as refund source');
        } elseif (!$payment->getSource()) {
            throw new Exception('Invalid Boost Campaign refund destination wallet address');
        } elseif ($payment->getAmount() > 0) {
            throw new Exception('Refunds can only happen on negative payment amounts');
        }

        $txWeiAmount = BigNumber::toPlain($payment->getAmount(), 18)->neg();

        $refundTx = $this->eth->sendRawTransaction($walletKey, [
            'from' => $wallet,
            'to' => $token,
            'gasLimit' => BigNumber::_(4612388)->toHex(true),
            'gasPrice' => BigNumber::_(10000000000)->toHex(true),
            'data' => $this->eth->encodeContractMethod('transfer(address,uint256)', [
                $payment->getSource(),
                $txWeiAmount->toHex(true),
            ]),
        ]);

        $payment->setTx($refundTx);

        $transaction = new Transaction();
        $transaction
            ->setTx($payment->getTx())
            ->setContract('boost_campaign')
            ->setAmount((string) $txWeiAmount)
            ->setWalletAddress($payment->getSource())
            ->setTimestamp($payment->getTimeCreated())
            ->setUserGuid($payment->getOwnerGuid())
            ->setData([
                'payment' => $payment->export(),
            ]);

        $this->repository->add($payment);
        $this->txManager->add($transaction, false);

        return $payment;
    }
}
