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
        $start = $this->normaliseStartTime($campaign->getStart());
        $end = $this->normaliseEndTime($campaign->getEnd());
        $this->validateStartTime($start);
        $this->validateEndTime($end);
        $this->validateStartAgainstEnd($start, $end);

        return $campaign->setStart($start)->setEnd($end);
    }

    /**
     * @param Campaign $campaign
     * @param Campaign $campaignRef
     * @return Campaign
     * @throws CampaignException
     */
    public function onUpdate(Campaign $campaign, Campaign $campaignRef)
    {
        // TODO: Ensure date updates from ref are valid against original campaign budget, etc.

        $start = $this->normaliseStartTime($campaignRef->getStart());
        $end = $this->normaliseEndTime($campaignRef->getEnd());

        $this->validateStartTime($start);
        $this->validateEndTime($end);
        $this->validateStartAgainstEnd($start, $end);

        if (!$campaign->hasStarted()) {
            $campaign->setStart($start);
        }

        if (!$campaign->hasFinished() && $campaign->getEnd() < $end) {
            $campaign->setEnd($end);
        }

        return $campaign;
    }

    private function normaliseStartTime(int $startTime): int
    {
        return strtotime(date('Y-m-d', $startTime / 1000) . ' 00:00:00') * 1000;
    }

    private function normaliseEndTime(int $endTime): int
    {
        return strtotime(date('Y-m-d', $endTime / 1000) . ' 23:59:59') * 1000;
    }

    private function validateStartTime(int $startTime): void
    {
        if ($startTime <= 0) {
            throw new CampaignException('Campaign should have a start date');
        }

        $today = strtotime(date('Y-m-d') . ' 00:00:00') * 1000;

        if ($startTime < $today) {
            throw new CampaignException('Campaign start should not be in the past');
        }
    }

    private function validateEndTime(int $endTime): void
    {
        if ($endTime <= 0) {
            throw new CampaignException('Campaign should have an end date');
        }
    }

    private function validateStartAgainstEnd(int $start, int $end): void
    {
        if ($start >= $end) {
            throw new CampaignException('Campaign end before starting');
        }

        $startPlusOneMonth = strtotime('+1 month', $start / 1000) * 1000;

        if ($startPlusOneMonth < $end) {
            throw new CampaignException('Campaign must not be longer than 1 month');
        }
    }
}
