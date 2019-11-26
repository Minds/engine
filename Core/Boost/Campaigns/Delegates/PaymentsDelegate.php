<?php

namespace Minds\Core\Boost\Campaigns\Delegates;

use Exception;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Boost\Campaigns\Metrics;
use Minds\Core\Boost\Campaigns\Payments;
use Minds\Core\Boost\Campaigns\Payments\Payment;
use Minds\Core\Config;
use Minds\Core\Di\Di;

class PaymentsDelegate
{
    /** @var Config */
    protected $config;

    /** @var Payments\Onchain */
    protected $onchainPayments;

    /** @var Metrics */
    protected $metrics;

    /**
     * PaymentsDelegate constructor.
     * @param Config $config
     * @param Payments\Onchain $onchainPayments
     * @param Metrics $metrics
     * @throws Exception
     */
    public function __construct(
        $config = null,
        $onchainPayments = null,
        $metrics = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->onchainPayments = $onchainPayments ?: new Payments\Onchain();
        $this->metrics = $metrics ?: new Metrics();
    }

    /**
     * @param Campaign $campaign
     * @param mixed $paymentPayload
     * @return Campaign
     * @throws CampaignException
     */
    public function onCreate(Campaign $campaign, $paymentPayload = null)
    {
        $this->validateBudget($campaign);

        if (!$paymentPayload) {
            throw new CampaignException('Missing payment');
        }

        $this->pay($campaign, $paymentPayload);
        $this->validatePayments($campaign);

        return $this->updateImpressionsByCpm($campaign);
    }

    /**
     * @param Campaign $campaign
     * @param Campaign $campaignRef
     * @param mixed $paymentPayload
     * @return Campaign
     * @throws CampaignException
     */
    public function onUpdate(Campaign $campaign, Campaign $campaignRef, $paymentPayload = null)
    {
        $campaignRef->setBudgetType($campaign->getBudgetType());
        $this->validateBudget($campaignRef);

        if ($paymentPayload) {
            // TODO: This looks wrong, we should act upon campaign, not campaignRef
            $this->pay($campaignRef, $paymentPayload);
            $this->validatePayments($campaignRef);
        }

        return $this->updateImpressionsByCpm($campaign);
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    public function onStateChange(Campaign $campaign)
    {
        $isFinished = in_array($campaign->getDeliveryStatus(), [
            Campaign::STATUS_REJECTED,
            Campaign::STATUS_REVOKED,
            Campaign::STATUS_COMPLETED
        ], true);

        if ($isFinished) {
            $this->refund($campaign);
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    public function validateBudget(Campaign $campaign)
    {
        if (!$campaign->getBudget() || $campaign->getBudget() <= 0) {
            throw new CampaignException('Campaign should have a budget');
        }

        if (!in_array($campaign->getBudgetType(), ['tokens'], true)) {
            throw new CampaignException("Invalid budget type: {$campaign->getBudgetType()}");
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     */
    public function validatePayments(Campaign $campaign)
    {
        // TODO: Validate all payments

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @param mixed $payload
     * @return Campaign
     * @throws CampaignException
     */
    public function pay(Campaign $campaign, $payload)
    {
        switch ($campaign->getBudgetType()) {
            case 'tokens':
                if (!$payload || !$payload['txHash'] || !$payload['address'] || !$payload['amount']) {
                    throw new CampaignException('Invalid payment signature');
                }

                $payment = new Payment();
                $payment
                    ->setOwnerGuid($campaign->getOwnerGuid())
                    ->setCampaignGuid($campaign->getGuid())
                    ->setTx($payload['txHash'])
                    ->setSource($payload['address'])
                    ->setAmount((double) $payload['amount'])
                    ->setTimeCreated(time());

                try {
                    $this->onchainPayments->record($payment);
                } catch (Exception $e) {
                    throw new CampaignException("Error registering payment: {$e->getMessage()}");
                }

                $campaign->pushPayment($payment);

                break;

            default:
                throw new CampaignException('Unknown budget type');
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function refund(Campaign $campaign)
    {
        $latestPaymentSource = '';
        $paid = 0;
        $refundThreshold = 0.1;

        foreach ($campaign->getPayments() as $payment) {
            $paid += $payment->getAmount();

            if ($payment->getAmount() > 0 && $payment->getSource()) {
                $latestPaymentSource = $payment->getSource();
            }
        }

        if ($paid <= $refundThreshold) {
            return $campaign;
        }

        $impressionsMet = $this->metrics->setCampaign($campaign)->getImpressionsMet();
        $cost = ($impressionsMet / 1000) * $campaign->cpm();
        $amount = $paid - $cost;

        if ($amount < $refundThreshold) {
            return $campaign;
        }

        switch ($campaign->getBudgetType()) {
            case 'tokens':
                $payment = new Payment();
                $payment
                    ->setOwnerGuid($campaign->getOwnerGuid())
                    ->setCampaignGuid($campaign->getGuid())
                    ->setSource($latestPaymentSource)
                    ->setAmount(-$amount)
                    ->setTimeCreated(time());

                try {
                    $this->onchainPayments->refund($payment);
                } catch (Exception $e) {
                    throw new CampaignException("Error registering refund: {$e->getMessage()}");
                }

                $campaign->pushPayment($payment);

                break;

            default:
                throw new CampaignException('Unknown budget type');
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @param Payment $paymentRef
     * @return Campaign
     */
    public function onConfirm(Campaign $campaign, Payment $paymentRef)
    {
        // TODO: Check ALL other payments to ensure budget

        $campaign->setCreatedTimestamp(time() * 1000);

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @param Payment $paymentRef
     * @return Campaign
     */
    public function onFail(Campaign $campaign, Payment $paymentRef)
    {
        $campaign
            ->setCreatedTimestamp(time() * 1000)
            ->setRevokedTimestamp(time() * 1000);

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    public function updateImpressionsByCpm(Campaign $campaign)
    {
        $cpm = (float) ($this->config->get('boost')['cpm'] ?? 1);

        if (!$cpm) {
            throw new CampaignException('Missing CPM');
        }

        $impressions = floor((1000 * $campaign->getBudget()) / $cpm);

        $campaign
            ->setImpressions($impressions);

        if (!$campaign->getImpressions()) {
            throw new CampaignException('Impressions value cannot be 0');
        }

        return $campaign;
    }
}
