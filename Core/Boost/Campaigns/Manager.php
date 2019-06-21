<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;

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
     */
    public function create(Campaign $campaign)
    {
        $campaign = $this->campaignUrnDelegate->onCreate($campaign);

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

        $campaign = $this->normalizeDatesDelegate->onCreate($campaign);
        $campaign = $this->normalizeEntityUrnsDelegate->onCreate($campaign);
        $campaign = $this->normalizeHashtagsDelegate->onCreate($campaign);
        $campaign = $this->budgetDelegate->onCreate($campaign); // Should be ALWAYS called after normalizing dates

        //

        $campaign
            ->setCreatedTimestamp(time() * 1000);

        $done = $this->repository->add($campaign);

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    public function update(Campaign $campaign)
    {
        $campaign = $this->campaignUrnDelegate->onUpdate($campaign, null);

        $oldCampaign = $this->get($campaign->getUrn());

        if (!$campaign) {
            throw new CampaignException('Campaign does not exist');
        }

        // Validate that there's an owner

        if (!$campaign->getOwnerGuid()) {
            throw new CampaignException('Campaign should have an owner');
        }

        // Validate that owner didn't change

        if ($campaign->getOwnerGuid() !== $oldCampaign->getOwnerGuid()) {
            throw new CampaignException('Campaign cannot change owners after created');
        }

        // Validate that there's a name

        if (!$campaign->getName()) {
            throw new CampaignException('Campaign should have a name');
        }

        // Validate that type didn't change

        if ($campaign->getType() !== $oldCampaign->getType()) {
            throw new CampaignException('Campaigns cannot change types after created');
        }

        // Normalize and validate dates

        $campaign = $this->normalizeDatesDelegate->onUpdate($campaign, $oldCampaign);
        $campaign = $this->normalizeEntityUrnsDelegate->onUpdate($campaign, $oldCampaign);
        $campaign = $this->normalizeHashtagsDelegate->onUpdate($campaign, $oldCampaign);
        $campaign = $this->budgetDelegate->onUpdate($campaign, $oldCampaign); // Should be ALWAYS called after normalizing dates

        $done = $this->repository->update($campaign);

        if (!$done) {
            throw new CampaignException('Cannot save campaign');
        }

        return $campaign;
    }
}
