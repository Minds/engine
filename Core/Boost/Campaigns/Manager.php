<?php

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Boost\Campaigns\Payments\Payment;
use Minds\Core\Boost\Campaigns\Payments\Repository as PaymentsRepository;
use Minds\Core\Queue\Client as QueueClient;
use Minds\Core\Queue\Interfaces\QueueClient as QueueClientInterface;
use Minds\Entities\User;
use Minds\Traits\DiAlias;

class Manager
{
    use DiAlias;

    /** @var Repository */
    protected $repository;

    /** @var ElasticRepository */
    protected $elasticRepository;

    /** @var Metrics */
    protected $metrics;

    /** @var PaymentsRepository */
    protected $paymentsRepository;

    /** @var QueueClientInterface */
    protected $queueClient;

    /** @var Delegates\CampaignUrnDelegate */
    protected $campaignUrnDelegate;

    /** @var Delegates\NormalizeDatesDelegate */
    protected $normalizeDatesDelegate;

    /** @var Delegates\NormalizeEntityUrnsDelegate */
    protected $normalizeEntityUrnsDelegate;

    /** @var Delegates\NormalizeHashtagsDelegate */
    protected $normalizeHashtagsDelegate;

    /** @var Delegates\PaymentsDelegate */
    protected $paymentsDelegate;

    /** @var User */
    protected $actor;

    /**
     * Manager constructor.
     * @param Repository $repository
     * @param ElasticRepository $elasticRepository
     * @param Metrics $metrics
     * @param PaymentsRepository $paymentsRepository
     * @param QueueClientInterface $queueClient
     * @param Delegates\CampaignUrnDelegate $campaignUrnDelegate
     * @param Delegates\NormalizeDatesDelegate $normalizeDatesDelegate
     * @param Delegates\NormalizeEntityUrnsDelegate $normalizeEntityUrnsDelegate
     * @param Delegates\NormalizeHashtagsDelegate $normalizeHashtagsDelegate
     * @param Delegates\PaymentsDelegate $paymentsDelegate
     * @throws Exception
     */
    public function __construct(
        $repository = null,
        $elasticRepository = null,
        $metrics = null,
        $paymentsRepository = null,
        $queueClient = null,
        $campaignUrnDelegate = null,
        $normalizeDatesDelegate = null,
        $normalizeEntityUrnsDelegate = null,
        $normalizeHashtagsDelegate = null,
        $paymentsDelegate = null
    ) {
        $this->repository = $repository ?: new Repository();
        $this->elasticRepository = $elasticRepository ?: new ElasticRepository();
        $this->metrics = $metrics ?: new Metrics();
        $this->paymentsRepository = $paymentsRepository ?: new PaymentsRepository();
        $this->queueClient = $queueClient ?: QueueClient::build();

        $this->campaignUrnDelegate = $campaignUrnDelegate ?: new Delegates\CampaignUrnDelegate();
        $this->normalizeDatesDelegate = $normalizeDatesDelegate ?: new Delegates\NormalizeDatesDelegate();
        $this->normalizeEntityUrnsDelegate = $normalizeEntityUrnsDelegate ?: new Delegates\NormalizeEntityUrnsDelegate();
        $this->normalizeHashtagsDelegate = $normalizeHashtagsDelegate ?: new Delegates\NormalizeHashtagsDelegate();
        $this->paymentsDelegate = $paymentsDelegate ?: new Delegates\PaymentsDelegate();
    }

    /**
     * @param User $actor
     * @return Manager
     */
    public function setActor(User $actor = null)
    {
        $this->actor = $actor;
        return $this;
    }

    /**
     * Get a list of boost campaigns
     * @param array $opts
     * @return Response
     * @throws Exception
     */
    public function getCampaigns(array $opts = []): Response
    {
        $opts = array_merge([
            'useElastic' => true
        ], $opts);

        $response = $opts['useElastic'] ?
            $this->elasticRepository->getCampaigns($opts) :
            $this->repository->getCampaignByGuid($opts);

        return $response->map(function (Campaign $campaign) {
            try {
                $campaign
                    ->setPayments(
                        $this->paymentsRepository->getList([
                            'owner_guid' => $campaign->getOwnerGuid(),
                            'campaign_guid' => $campaign->getGuid(),
                        ])->toArray()
                    );
            } catch (Exception $e) {
                error_log("[BoostCampaignsManager] {$e}");
            }

            return $campaign;
        });
    }

    /**
     * Get a list of boosts and boost campaigns
     * @param array $opts
     * @return Response|null
     */
    public function getCampaignsAndBoosts(array $opts = []): ?Response
    {
        $response = null;

        try {
            $response = $this->elasticRepository->getCampaignsAndBoosts($opts);
        } catch (Exception $e) {
            error_log("[BoostCampaignsManager] {$e}");
        }

        return $response;
    }

    /**
     * Get a single boost campaign by URN string
     * @param string $urn
     * @param array $opts
     * @return Campaign|null
     * @throws Exception
     */
    public function getCampaignByUrn(string $urn, array $opts = []): ?Campaign
    {
        $opts = array_merge([
            'useElastic' => false
        ], $opts);

        $guid = (new Urn($urn))->getNss();

        if (!$guid) {
            return null;
        }

        $campaigns = $this->getCampaigns([
            'guid' => $guid,
            'useElastic' => $opts['useElastic'],
        ])->toArray();

        if (!$campaigns) {
            throw new CampaignException('Campaign not found');
        }

        return $campaigns[0];
    }

    /**
     * @param Campaign $campaign
     * @param mixed $paymentPayload
     * @return Campaign
     * @throws CampaignException
     */
    public function createCampaign(Campaign $campaign, $paymentPayload = null): Campaign
    {
        $campaign = $this->campaignUrnDelegate->onCreate($campaign);
        $campaign->setOwner($this->actor);

        if (!$campaign->getOwnerGuid()) {
            throw new CampaignException('Campaign should have an owner');
        }

        if (!$campaign->getName()) {
            throw new CampaignException('Campaign should have a name');
        }

        $validTypes = ['newsfeed', 'content', 'banner', 'video'];

        if (!in_array($campaign->getType(), $validTypes, true)) {
            throw new CampaignException('Invalid campaign type');
        }

        /** TODO: Checksum Verification */
        //$guid = (new Urn($campaign->getEntityUrns()[0]))->getNss();
        //$checksum = (new Checksum())->setGuid($campaign->getGuid())->setEntity($guid)->generate();
        //if (!$campaign->getChecksum() || ($campaign->getChecksum() !== $checksum)) {
        if (!$campaign->getChecksum()) {
            throw new CampaignException('Invalid checksum value');
        }

        $campaign = $this->normalizeDatesDelegate->onCreate($campaign);
        $campaign = $this->normalizeEntityUrnsDelegate->onCreate($campaign);
        $campaign = $this->normalizeHashtagsDelegate->onCreate($campaign);
        $campaign = $this->paymentsDelegate->onCreate($campaign, $paymentPayload);

        $this->sync($campaign);

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @param mixed $paymentPayload
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function updateCampaign(Campaign $campaignRef, $paymentPayload = null): Campaign
    {
        $campaign = $this->getCampaignByUrn($campaignRef->getUrn());
        $isOwner = (string) $this->actor->guid === (string) $campaign->getOwnerGuid();

        if ($this->actor && !$this->actor->isAdmin() && !$isOwner) {
            throw new CampaignException('You\'re not allowed to edit this campaign');
        }

        if (!in_array($campaign->getDeliveryStatus(), [Campaign::STATUS_PENDING, Campaign::STATUS_CREATED, Campaign::STATUS_APPROVED], true)) {
            throw new CampaignException('Campaign should be in [pending], [created], [approved] state in order to edit it');
        }

        if (!$campaignRef->getName()) {
            throw new CampaignException('Campaign should have a name');
        }

        $campaign->setName($campaignRef->getName());
        $campaign = $this->normalizeDatesDelegate->onUpdate($campaign, $campaignRef);
        $campaign = $this->normalizeHashtagsDelegate->onUpdate($campaign, $campaignRef);
        $campaign = $this->paymentsDelegate->onUpdate($campaign, $campaignRef, $paymentPayload);

        $this->sync($campaign);
        $this->sendToQueue($campaign);

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @throws Exception
     */
    public function sync(Campaign $campaign): void
    {
        $this->repository->putCampaign($campaign);
        $this->elasticRepository->putCampaign($campaign);
    }

    /**
     * @param Campaign $campaignRef
     */
    public function onImpression(Campaign $campaignRef): void
    {
        $this->sendToQueue($campaignRef);
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function start(Campaign $campaignRef): Campaign
    {
        if ($this->actor) {
            throw new CampaignException('Campaigns should not be manually started');
        }

        $campaign = $this->getCampaignByUrn($campaignRef->getUrn());

        if ($campaign->getDeliveryStatus() !== Campaign::STATUS_CREATED) {
            throw new CampaignException('Campaign should be in [created] state in order to start it');
        }

        $campaign->setReviewedTimestamp(time() * 1000);
        $this->sync($campaign);

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function cancelCampaign(Campaign $campaignRef): Campaign
    {
        $campaign = $this->getCampaignByUrn($campaignRef->getUrn());

        $isOwner = (string) $this->actor->guid === (string) $campaign->getOwnerGuid();

        if ($this->actor && !$this->actor->isAdmin() && !$isOwner) {
            throw new CampaignException('You\'re not allowed to cancel this campaign');
        }

        if (!in_array($campaign->getDeliveryStatus(), [Campaign::STATUS_CREATED, Campaign::STATUS_APPROVED], true)) {
            throw new CampaignException('Campaign should be in [created] or [approved] state in order to cancel it');
        }

        $campaign->setImpressions($this->metrics->setCampaign($campaign)->getImpressionsMet());
        $campaign->setRevokedTimestamp(time() * 1000);

        $this->paymentsDelegate->onStateChange($campaign);
        $this->sync($campaign);

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function rejectCampaign(Campaign $campaignRef): Campaign
    {
        $campaign = $this->getCampaignByUrn($campaignRef->getUrn());

        if ($this->actor && !$this->actor->isAdmin()) {
            throw new CampaignException('You\'re not allowed to reject this campaign');
        }

        if ($campaign->getDeliveryStatus() !== Campaign::STATUS_CREATED) {
            throw new CampaignException('Campaign should be in [created] state in order to reject it');
        }

        $campaign->setRejectedTimestamp(time() * 1000);

        $this->paymentsDelegate->onStateChange($campaign);
        $this->sync($campaign);

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function completeCampaign(Campaign $campaignRef): Campaign
    {
        $campaign = $this->getCampaignByUrn($campaignRef->getUrn());

        if ($this->actor) {
            throw new CampaignException('Campaigns should not be manually completed');
        }

        if ($campaign->getDeliveryStatus() !== Campaign::STATUS_APPROVED) {
            throw new CampaignException('Campaign should be in [approved] state in order to complete it');
        }

        $campaign->setCompletedTimestamp(time() * 1000);

        $this->paymentsDelegate->onStateChange($campaign);
        $this->sync($campaign);

        return $campaign;
    }

    /**
     * @param Payment $paymentRef
     * @throws Exception
     */
    public function onPaymentSuccess(Payment $paymentRef): void
    {
        $campaign = $this->getCampaignByUrn("urn:campaign:{$paymentRef->getCampaignGuid()}");
        $campaign = $this->paymentsDelegate->onConfirm($campaign, $paymentRef);

        $this->sync($campaign);
        $this->sendToQueue($campaign);
    }

    /**
     * @param Payment $paymentRef
     * @throws Exception
     */
    public function onPaymentFailed(Payment $paymentRef): void
    {
        $campaign = $this->getCampaignByUrn("urn:campaign:{$paymentRef->getCampaignGuid()}");
        $campaign = $this->paymentsDelegate->onFail($campaign, $paymentRef);

        $this->sync($campaign);
    }

    protected function sendToQueue(Campaign $campaign): void
    {
        $this->queueClient
            ->setQueue('BoostCampaignDispatcher')
            ->send([
                'campaign' => serialize($campaign),
            ]);
    }
}
