<?php
/**
 * TokenEvent
 * @author edgebal
 */

namespace Minds\Core\Blockchain\Events;

use Exception;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Blockchain\Util;
use Minds\Core\Boost\Campaigns\Manager as BoostCampaignsManager;
use Minds\Core\Boost\Campaigns\Payments\Payment;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

class TokenEvent implements BlockchainEventInterface
{
    /** @var array */
    public static $eventsMap = [
        '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef' => 'tokenTransfer',
        'blockchain:fail' => 'tokenFail',
    ];

    /** @var Config */
    protected $config;

    /** @var BoostCampaignsManager */
    protected $boostCampaignsManager;

    /**
     * TokenEvent constructor.
     * @param Config $config
     * @param BoostCampaignsManager $boostCampaignsManager
     */
    public function __construct(
        $config = null,
        $boostCampaignsManager = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->boostCampaignsManager = $boostCampaignsManager ?: Di::_()->get('Boost\Campaigns\Manager');
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
     * @param Transaction $transaction
     * @return void
     * @throws Exception
     */
    public function event($topic, array $log, $transaction)
    {
        $method = static::$eventsMap[$topic];

        if ($log['address'] != $this->config->get('blockchain')['token_address']) {
            throw new Exception('Event does not match address');
        }

        if (method_exists($this, $method)) {
            $this->{$method}($log, $transaction);
        } else {
            throw new Exception('Method not found');
        }
    }

    /**
     * @param array $log
     * @param Transaction $transaction
     * @throws Exception
     */
    public function tokenTransfer($log, $transaction)
    {
        list($amount) = Util::parseData($log['data'], [Util::NUMBER]);
        list($destination) = Util::parseData($log['topics'][2], [Util::ADDRESS]);

        $data = $transaction->getData();

        if (!$destination) {
            throw new Exception('Invalid transfer destination');
        }

        switch ($transaction->getContract()) {
            case 'boost_campaign':
                $wallet = $this->config->get('blockchain')['contracts']['boost_campaigns']['wallet_address'] ?? null;

                if ($destination != $wallet) {
                    throw new Exception('Invalid Boost Campaign wallet address');
                }

                $payment = new Payment();
                $payment
                    ->setOwnerGuid($data['payment']['owner_guid'])
                    ->setCampaignGuid($data['payment']['campaign_guid'])
                    ->setTx($data['payment']['tx'])
                    ->setAmount(BigNumber::fromPlain(BigNumber::fromHex($amount), 18)->toDouble());

                $this->boostCampaignsManager->onPaymentSuccess($payment);
                break;
        }
    }

    /**
     * @param array $log
     * @param Transaction $transaction
     * @throws Exception
     */
    public function tokenFail($log, $transaction)
    {
        list($amount) = Util::parseData($log['data'], [Util::NUMBER]);
        list($destination) = Util::parseData($log['topics'][2], [Util::ADDRESS]);

        $data = $transaction->getData();

        if (!$destination) {
            throw new Exception('Invalid transfer destination');
        }

        switch ($transaction->getContract()) {
            case 'boost_campaign':
                $wallet = $this->config->get('blockchain')['contracts']['boost_campaigns']['wallet_address'] ?? null;

                if ($destination != $wallet) {
                    throw new Exception('Invalid Boost Campaign wallet address');
                }

                $payment = new Payment();
                $payment
                    ->setOwnerGuid($data['payment']['owner_guid'])
                    ->setCampaignGuid($data['payment']['campaign_guid'])
                    ->setTx($data['payment']['tx'])
                    ->setAmount(BigNumber::fromPlain(BigNumber::fromHex($amount), 18)->toDouble());

                $this->boostCampaignsManager->onPaymentFailed($payment);
                break;
        }
    }
}
