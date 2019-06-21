<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Entities\User;

class Manager
{
    /** @var Repository  */
    protected $repository;

    /** @var Delegates\CampaignUrnDelegate */
    protected $campaignUrnDelegate;

    /** @var Delegates\NormalizeDatesDelegate */
    protected $normalizeDatesDelegate;

    /** @var Delegates\NormalizeEntityUrnsDelegate */
    protected $normalizeEntityUrnsDelegate;

    /** @var Delegates\NormalizeHashtagsDelegate */
    protected $normalizeHashtagsDelegate;

    /** @var Delegates\BudgetDelegate */
    protected $budgetDelegate;

    /** @var User */
    protected $actor;

    /**
     * Manager constructor.
     * @param Repository $repository
     * @param Delegates\CampaignUrnDelegate $campaignUrnDelegate
     * @param Delegates\NormalizeDatesDelegate $normalizeDatesDelegate
     * @param Delegates\NormalizeEntityUrnsDelegate $normalizeEntityUrnsDelegate
     * @param Delegates\NormalizeHashtagsDelegate $normalizeHashtagsDelegate
     * @param Delegates\BudgetDelegate $budgetDelegate
     */
    public function __construct(
        $repository = null,
        $campaignUrnDelegate = null,
        $normalizeDatesDelegate = null,
        $normalizeEntityUrnsDelegate = null,
        $normalizeHashtagsDelegate = null,
        $budgetDelegate = null
    )
    {
        $this->repository = $repository ?: new Repository();

        // Delegates

        $this->campaignUrnDelegate = $campaignUrnDelegate ?: new Delegates\CampaignUrnDelegate();
        $this->normalizeDatesDelegate = $normalizeDatesDelegate ?: new Delegates\NormalizeDatesDelegate();
        $this->normalizeEntityUrnsDelegate = $normalizeEntityUrnsDelegate ?: new Delegates\NormalizeEntityUrnsDelegate();
        $this->normalizeHashtagsDelegate = $normalizeHashtagsDelegate ?: new Delegates\NormalizeHashtagsDelegate();
        $this->budgetDelegate = $budgetDelegate ?: new Delegates\BudgetDelegate();
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
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = [])
    {
        return $this->repository->getList($opts);
    }

    /**
     * @param $urn
     * @return Campaign|null
     * @throws Exception
     */
    public function get($urn)
    {
        $urn = new Urn($urn);
        $guid = $urn->getNss();

        if (!$guid) {
            return null;
        }

        $campaigns = $this->repository->getList([
            'guid' => $guid
        ])->toArray();

        if (!$campaigns) {
            return null;
        }

        return $campaigns[0];
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function create(Campaign $campaign)
    {
        $campaign = $this->campaignUrnDelegate->onCreate($campaign);

        // Owner should be the actor

        $campaign
            ->setOwner($this->actor);

        // Validate that there's an owner

        if (!$campaign->getOwnerGuid()) {
            throw new CampaignException('Campaign should have an owner');
        }

        // Validate that there's a name

        if (!$campaign->getName()) {
            throw new CampaignException('Campaign should have a name');
        }

        // Validate type

        $validTypes = ['newsfeed', 'content', 'banner', 'video'];

        if (!in_array($campaign->getType(), $validTypes)) {
            throw new CampaignException('Invalid campaign type');
        }

        // Run delegates

        $campaign = $this->normalizeDatesDelegate->onCreate($campaign);
        $campaign = $this->normalizeEntityUrnsDelegate->onCreate($campaign);
        $campaign = $this->normalizeHashtagsDelegate->onCreate($campaign);
        $campaign = $this->budgetDelegate->onCreate($campaign); // Should be ALWAYS called after normalizing dates

        // Add

        $campaign
            ->setCreatedTimestamp(time() * 1000);

        $done = $this->repository->add($campaign);

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function update(Campaign $campaignRef)
    {
        // Load campaign

        $campaign = $this->get($campaignRef->getUrn());

        if (!$campaign) {
            throw new CampaignException('Campaign does not exist');
        }

        $isOwner = (string) $this->actor->guid !== (string) $campaign->getOwnerGuid();

        if ($this->actor && !$this->actor->isAdmin() && !$isOwner) {
            throw new CampaignException('You\'re not allowed to edit this campaign');
        }

        // Validate that there's a name

        if (!$campaignRef->getName()) {
            throw new CampaignException('Campaign should have a name');
        }

        // Update

        $campaign
            ->setName($campaignRef->getName());

        // Run delegates

        $campaign = $this->normalizeDatesDelegate->onUpdate($campaign, $campaignRef);
        $campaign = $this->normalizeHashtagsDelegate->onUpdate($campaign, $campaignRef);
        $campaign = $this->budgetDelegate->onUpdate($campaign, $campaignRef); // Should be ALWAYS called after normalizing dates

        $done = $this->repository->update($campaign);

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function start(Campaign $campaignRef)
    {
        // Load campaign

        $campaign = $this->get($campaignRef->getUrn());

        if (!$campaign) {
            throw new CampaignException('Campaign does not exist');
        }

        if ($this->actor) {
            throw new CampaignException('Campaigns should not be manually started');
        }

        // Check state

        if ($campaign->getDeliveryStatus() !== Campaign::CREATED_STATUS) {
            throw new CampaignException('Campaign should be in [created] state in order to start it');
        }

        // Update

        $now = time() * 1000;

        $campaign
            ->setStart($now) // Update start date so we can calculate distribution correctly
            ->setReviewedTimestamp($now);

        $done = $this->repository->update($campaign);

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function cancel(Campaign $campaignRef)
    {
        // Load campaign

        $campaign = $this->get($campaignRef->getUrn());

        if (!$campaign) {
            throw new CampaignException('Campaign does not exist');
        }

        $isOwner = (string) $this->actor->guid !== (string) $campaign->getOwnerGuid();

        if ($this->actor && !$this->actor->isAdmin() && !$isOwner) {
            throw new CampaignException('You\'re not allowed to cancel this campaign');
        }

        // Check state

        if (!in_array($campaign->getDeliveryStatus(), [Campaign::CREATED_STATUS, Campaign::APPROVED_STATUS])) {
            throw new CampaignException('Campaign should be in [created] or [approved] state in order to cancel it');
        }

        // Update

        $campaign
            ->setRevokedTimestamp(time() * 1000);

        $done = $this->repository->update($campaign);

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        $this->budgetDelegate->onStateChange($campaign);

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function reject(Campaign $campaignRef)
    {
        // Load campaign

        $campaign = $this->get($campaignRef->getUrn());

        if (!$campaign) {
            throw new CampaignException('Campaign does not exist');
        }

        if ($this->actor && !$this->actor->isAdmin()) {
            throw new CampaignException('You\'re not allowed to reject this campaign');
        }

        // Check state

        if ($campaign->getDeliveryStatus() !== Campaign::CREATED_STATUS) {
            throw new CampaignException('Campaign should be in [created] state in order to reject it');
        }

        // Update

        $campaign
            ->setRejectedTimestamp(time() * 1000);

        $done = $this->repository->update($campaign);

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        $this->budgetDelegate->onStateChange($campaign);

        return $campaign;
    }

    /**
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     * @throws Exception
     */
    public function complete(Campaign $campaignRef)
    {
        // Load old campaign for comparison

        $campaign = $this->get($campaignRef->getUrn());

        if (!$campaign) {
            throw new CampaignException('Campaign does not exist');
        }

        if ($this->actor) {
            throw new CampaignException('Campaigns should not be manually completed');
        }

        // Check state

        if ($campaign->getDeliveryStatus() !== Campaign::CREATED_STATUS) {
            throw new CampaignException('Campaign should be in [approved] state in order to complete it');
        }

        // Update

        $campaign
            ->setCompletedTimestamp(time() * 1000);

        $done = $this->repository->update($campaign);

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        return $campaign;
    }
}
