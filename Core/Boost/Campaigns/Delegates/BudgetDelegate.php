<?php
/**
 * BudgetDelegate
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;

class BudgetDelegate
{
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

        // TODO: Validate offchain balance, or set as pending for onchain

        return $campaign;
    }

    public function onUpdate(Campaign $campaign, Campaign $oldCampaign)
    {
        // TODO: Validate offchain balance, or set as pending for onchain
        // TODO: Ensure budget didn't go lower than impressions met threshold

        return $campaign;
    }
}
