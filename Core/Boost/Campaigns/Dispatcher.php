<?php

namespace Minds\Core\Boost\Campaigns;

use Exception;
use Minds\Traits\DiAlias;

class Dispatcher
{
    use DiAlias;

    const IMPRESSIONS_SYNC_THRESHOLD = 5;

    /** @var Manager */
    protected $manager;

    /** @var Metrics */
    protected $metrics;

    /** @var int */
    protected $impressionsSyncThreshold;

    /** @var Campaign */
    protected $campaign;

    /** @var int */
    protected $now;

    /**
     * Dispatcher constructor.
     * @param Manager $manager
     * @param Metrics $metrics
     * @param int $impressionsSyncThreshold
     * @throws Exception
     */
    public function __construct(
        $manager = null,
        $metrics = null,
        $impressionsSyncThreshold = null
    ) {
        $this->manager = $manager ?: new Manager();
        $this->metrics = $metrics ?: new Metrics();
        $this->impressionsSyncThreshold = $impressionsSyncThreshold ?: static::IMPRESSIONS_SYNC_THRESHOLD;
    }

    /**
     * @param string $campaignUrn
     * @throws Exception
     */
    public function onLifecycle(string $campaignUrn)
    {
        $this->now = time() * 1000;
        $this->campaign = $this->manager->getCampaignByUrn($campaignUrn);
        $this->metrics->setCampaign($this->campaign);

        $this->syncIfImpressionsThresholdMet();
        $this->completeCampaign();
        $this->startCampaign();
    }

    /**
     * Sync to database if impressions threshold is met
     * @throws Exception
     */
    public function syncIfImpressionsThresholdMet(): void
    {
        if ($this->campaign->isDelivering()) {
            $currentImpressionsMet = $this->campaign->getImpressionsMet();
            $newImpressionsMet = $this->metrics->getImpressionsMet();

            if ($newImpressionsMet - $currentImpressionsMet >= $this->impressionsSyncThreshold) {
                $this->campaign->setImpressionsMet($newImpressionsMet);
                error_log("[BoostCampaignsDispatcher] Saving updated {$this->campaign->getUrn()}...");
                $this->manager->sync($this->campaign);
            }
        }
    }

    /**
     * Record the campaign as completed
     * @throws CampaignException
     */
    public function completeCampaign(): void
    {
        if ($this->campaign->shouldBeCompleted($this->now)) {
            error_log("[BoostCampaignsDispatcher] Completing {$this->campaign->getUrn()}...");
            $this->manager->completeCampaign($this->campaign);
        }
    }

    /**
     * Record the campaign as started
     * @throws CampaignException
     */
    public function startCampaign(): void
    {
        if ($this->campaign->shouldBeStarted($this->now)) {
            error_log("[BoostCampaignsDispatcher] Starting {$this->campaign->getUrn()}...");
            $this->manager->start($this->campaign);
        }
    }
}
