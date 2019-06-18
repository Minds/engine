<?php
/**
 * NormalizeDates
 * @author edgebal
 */

namespace Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;

class NormalizeDatesDelegate
{
    /**
     * @param Campaign $campaign
     * @return Campaign
     * @throws CampaignException
     */
    public function onCreate(Campaign $campaign)
    {
        $start = intval($campaign->getStart() / 1000);
        $end = intval($campaign->getEnd() / 1000);

        if ($start <= 0 || $end <= 0) {
            throw new CampaignException('Campaign should have a start and end date');
        }

        $today = strtotime(date('Y-m-d') . ' 00:00:00') * 1000;
        $start = strtotime(date('Y-m-d', $start) . ' 00:00:00') * 1000;
        $end = strtotime(date('Y-m-d', $end) . ' 23:59:59') * 1000;

        if ($start < $today) {
            throw new CampaignException('Campaign start should not be in the past');
        } elseif ($start >= $end) {
            throw new CampaignException('Campaign end before starting');
        }

        $campaign
            ->setStart($start)
            ->setEnd($end);

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
        $campaign = $this->onCreate($campaign);

        // TODO: Ensure date updates are valid agains old campaign

        return $campaign;
    }
}
