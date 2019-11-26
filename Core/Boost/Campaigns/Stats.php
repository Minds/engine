<?php

namespace Minds\Core\Boost\Campaigns;

use Minds\Core\Analytics\EntityCentric\BoostViewsDaily;
use Minds\Core\Time;
use Minds\Traits\DiAlias;

class Stats
{
    use DiAlias;

    /** @var Campaign */
    protected $campaign;

    /** @var BoostViewsDaily */
    protected $boostViewsDaily;

    public function __construct(BoostViewsDaily $boostViewsDaily = null)
    {
        $this->boostViewsDaily = $boostViewsDaily ?: new BoostViewsDaily();
    }

    /**
     * @param Campaign $campaign
     * @return Stats
     */
    public function setCampaign(Campaign $campaign): self
    {
        $this->campaign = $campaign;
        return $this;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        /* TODO: Evaluate the campaign targetting parameters against our data */

        $campaignDurationDays = ($this->campaign->getEnd() - $this->campaign->getStart()) / Time::ONE_DAY_MS;
        $campaignViewsPerDayReq = ($campaignDurationDays > 0) ? $this->campaign->getImpressions() / $campaignDurationDays : 0;
        $globalViewsPerDay = $this->boostViewsDaily->getAvg();

        return [
            'canBeDelivered' => ($campaignViewsPerDayReq < $globalViewsPerDay),
            'durationDays' => $campaignDurationDays,
            'viewsPerDayRequested' => $campaignViewsPerDayReq,
            'globalViewsPerDay' => $globalViewsPerDay
        ];
    }
}
