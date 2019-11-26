<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Common\Urn;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Metrics;
use Minds\Core\Counters\Manager as Counters;
use Minds\Core\Entities\Resolver;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MetricsSpec extends ObjectBehavior
{
    /** @var Counters */
    protected $counters;
    /** @var Resolver */
    protected $resolver;
    /** @var Campaign */
    protected $campaign;

    public function it_is_initializable()
    {
        $this->shouldHaveType(Metrics::class);
    }

    public function let(Counters $counters, Resolver $resolver, Campaign $campaign)
    {
        $this->beConstructedWith($counters, $resolver);
        $this->setCampaign($campaign);

        $this->counters = $counters;
        $this->resolver = $resolver;
        $this->campaign = $campaign;
    }

    public function it_should_increment_counters(Activity $activity)
    {
        $this->counters->setEntityGuid(0)->shouldBeCalled()->willReturn($this->counters);
        $this->counters->setMetric('boost_impressions')->shouldBeCalled()->willReturn($this->counters);
        $this->counters->increment()->shouldBeCalled();

        $this->campaign->getGuid()->shouldBeCalled()->willReturn(12345);
        $this->counters->setEntityGuid(12345)->shouldBeCalled()->willReturn($this->counters);

        $this->campaign->getEntityUrns()->shouldBeCalled()->willReturn(['urn:activity:12345']);
        $this->resolver->single(Argument::type(Urn::class))->shouldBeCalled()->willReturn($activity);
        $activity->get('guid')->willReturn('12345');
        $activity->get('owner_guid')->willReturn('12345');
        $this->counters->setMetric('impression')->shouldBeCalled()->willReturn($this->counters);

        $this->increment();
    }

    public function it_should_get_impressions_met()
    {
        $this->campaign->getGuid()->shouldBeCalled()->willReturn(12345);
        $this->counters->setEntityGuid(12345)->shouldBeCalled()->willReturn($this->counters);
        $this->counters->setMetric('boost_impressions')->shouldBeCalled()->willReturn($this->counters);
        $this->counters->get(false)->shouldBeCalled()->willReturn(25);

        $this->getImpressionsMet()->shouldReturn(25);
    }
}
