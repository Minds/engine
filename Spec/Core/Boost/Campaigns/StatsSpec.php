<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Core\Analytics\EntityCentric\BoostViewsDaily;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Stats;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StatsSpec extends ObjectBehavior
{
    /** @var BoostViewsDaily */
    protected $boostViewsDaily;

    public function it_is_initializable()
    {
        $this->shouldHaveType(Stats::class);
    }

    public function let(BoostViewsDaily $boostViewsDaily)
    {
        $this->beConstructedWith($boostViewsDaily);
        $this->boostViewsDaily = $boostViewsDaily;
    }

    public function it_should_set_campaign(Campaign $campaign)
    {
        $this->setCampaign($campaign)->shouldReturn($this);
    }

    public function it_should_get_all_stats_with_can_be_delivered_false(Campaign $campaign)
    {
        $stats = [
            'canBeDelivered' => false,
            'durationDays' => 5,
            'viewsPerDayRequested' => 50000,
            'globalViewsPerDay' => 10000
        ];

        $this->setCampaign($campaign);
        $campaign->getEnd()->shouldBeCalled()->willReturn(1570924800000);
        $campaign->getStart()->shouldBeCalled()->willReturn(1570492800000);
        $campaign->getImpressions()->shouldBeCalled()->willReturn(250000);
        $this->boostViewsDaily->getAvg()->shouldBeCalled()->willReturn(10000);

        $this->getAll()->shouldHaveKeyWithValue('canBeDelivered', false);
    }

    public function it_should_get_all_stats(Campaign $campaign)
    {
        $stats = [
            'canBeDelivered' => true,
            'durationDays' => 5,
            'viewsPerDayRequested' => 1000,
            'globalViewsPerDay' => 10000
        ];

        $this->setCampaign($campaign);
        $campaign->getEnd()->shouldBeCalled()->willReturn(1570924800000);
        $campaign->getStart()->shouldBeCalled()->willReturn(1570492800000);
        $campaign->getImpressions()->shouldBeCalled()->willReturn(5000);
        $this->boostViewsDaily->getAvg()->shouldBeCalled()->willReturn(10000);

        $this->getAll()->shouldHaveKeyWithValue('canBeDelivered', true);
    }
}
