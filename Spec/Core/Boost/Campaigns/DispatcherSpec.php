<?php

namespace Spec\Minds\Core\Boost\Campaigns;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\Dispatcher;
use Minds\Core\Boost\Campaigns\Manager;
use Minds\Core\Boost\Campaigns\Metrics;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DispatcherSpec extends ObjectBehavior
{
    /** @var Manager */
    protected $manager;

    /** @var Metrics */
    protected $metrics;

    public function let(Manager $manager, Metrics $metrics)
    {
        $this->beConstructedWith($manager, $metrics, 10);
        $this->manager = $manager;
        $this->metrics = $metrics;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Dispatcher::class);
    }

    public function it_should_not_sync_campaign_on_lifecycle_if_below_threshold(Campaign $campaign, Campaign $campaignRef)
    {
        $campaignRef->getUrn()->willReturn('urn:campaign:1000');

        $this->manager->getCampaignByUrn('urn:campaign:1000')->shouldBeCalled()->willReturn($campaign);

        $campaign->isDelivering()->shouldBeCalled()->willReturn(true);
        $campaign->getImpressionsMet()->shouldBeCalled()->willReturn(0);

        $this->metrics->setCampaign($campaign)->shouldBeCalled()->willReturn($this->metrics);
        $this->metrics->getImpressionsMet()->shouldBeCalled()->willReturn(1);

        $this->manager->sync($campaign)->shouldNotBeCalled();

        $campaign->shouldBeCompleted(Argument::any())->shouldBeCalled()->willReturn(false);
        $campaign->shouldBeStarted(Argument::any())->shouldBeCalled()->willReturn(false);

        $this->onLifecycle('urn:campaign:1000');
    }

    public function it_should_sync_campaign_on_lifecycle_if_above_threshold(Campaign $campaign, Campaign $campaignRef)
    {
        $campaign->getUrn()->willReturn('urn:campaign:1000');

        $campaignRef->getUrn()->willReturn('urn:campaign:1000');

        $this->manager->getCampaignByUrn('urn:campaign:1000')->shouldBeCalled()->willReturn($campaign);

        $campaign->isDelivering()->shouldBeCalled()->willReturn(true);
        $campaign->getImpressionsMet()->shouldBeCalled()->willReturn(0);

        $this->metrics->setCampaign($campaign)->shouldBeCalled()->willReturn($this->metrics);
        $this->metrics->getImpressionsMet()->shouldBeCalled()->willReturn(10);

        $campaign->setImpressionsMet(10)->shouldBeCalled()->willReturn($campaign);

        $this->manager->sync($campaign)->shouldBeCalled();

        $campaign->shouldBeCompleted(Argument::any())->shouldBeCalled()->willReturn(false);
        $campaign->shouldBeStarted(Argument::any())->shouldBeCalled()->willReturn(false);

        $this->onLifecycle('urn:campaign:1000');
    }

    public function it_should_complete_campaign(Campaign $campaign)
    {
        $campaign->getUrn()->willReturn('urn:campaign:1000');

        $this->manager->getCampaignByUrn('urn:campaign:1000')->shouldBeCalled()->willReturn($campaign);

        $this->metrics->setCampaign($campaign)->shouldBeCalled()->willReturn($this->metrics);

        $campaign->isDelivering()->shouldBeCalled()->willReturn(false);
        $campaign->shouldBeCompleted(Argument::any())->shouldBeCalled()->willReturn(true);

        $this->manager->completeCampaign($campaign)->shouldBeCalled()->willReturn($campaign);

        $campaign->shouldBeStarted(Argument::any())->willReturn(false);

        $this->onLifecycle('urn:campaign:1000');
    }

    public function it_should_start_campaign(Campaign $campaign)
    {
        $campaign->getUrn()->willReturn('urn:campaign:1000');

        $this->manager->getCampaignByUrn('urn:campaign:1000')->shouldBeCalled()->willReturn($campaign);

        $this->metrics->setCampaign($campaign)->shouldBeCalled()->willReturn($this->metrics);

        $campaign->isDelivering()->shouldBeCalled()->willReturn(false);
        $campaign->shouldBeCompleted(Argument::any())->shouldBeCalled()->willReturn(false);
        $campaign->shouldBeStarted(Argument::any())->shouldBeCalled()->willReturn(true);

        $this->manager->start($campaign)->shouldBeCalled()->willReturn($campaign);

        $this->onLifecycle('urn:campaign:1000');
    }
}
