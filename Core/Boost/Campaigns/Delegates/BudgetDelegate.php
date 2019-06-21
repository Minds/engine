<?php
/**
 * BudgetDelegate
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Config;
use Minds\Core\Di\Di;

class BudgetDelegate
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * BudgetDelegate constructor.
     * @param Config $config
     */
    public function __construct(
        $config = null
    )
    {
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    public function onCreate(Campaign $campaign)
    {
        if (!$campaign->getBudget() || $campaign->getBudget() <= 0) {
            throw new CampaignException('Campaign should have a budget');
        }

        $campaign = $this->updateImpressionsByCpm($campaign);

        // TODO: Validate offchain balance, or set as pending for onchain

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @param Campaign $oldCampaign
     * @return Campaign
     * @throws CampaignException
     */
    public function onUpdate(Campaign $campaign, Campaign $oldCampaign)
    {
        // TODO: Validate balance, or set as pending for onchain
        // TODO: Ensure budget didn't go lower than impressions met threshold

        $campaign = $this->updateImpressionsByCpm($campaign);

        if (!$campaign->getImpressions()) {
            throw new CampaignException('Impressions value cannot be 0');
        }

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     */
    public function refund(Campaign $campaign)
    {
        // TODO: Check that campaign is in a final incomplete status (revoked/rejected)
        // TODO: Refund!
        // TODO: Store refund info onto Campaign/Boost metadata

        return $campaign;
    }

    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    protected function updateImpressionsByCpm(Campaign $campaign)
    {
        $cpm = (float) $this->config->get('boost')['cpm'];

        if (!$cpm) {
            throw new CampaignException('Missing CPM');
        }

        $impressions = floor((1000 * $campaign->getBudget()) / $cpm);

        $campaign
            ->setImpressions($impressions);

        return $campaign;
    }
}
