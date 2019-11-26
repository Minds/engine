<?php

namespace Spec\Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Boost\Campaigns\Delegates\CampaignUrnDelegate;
use Minds\Core\GuidBuilder;
use PhpSpec\ObjectBehavior;

class CampaignUrnDelegateSpec extends ObjectBehavior
{
    /** @var GuidBuilder */
    protected $guidBuilder;

    public function let(GuidBuilder $guidBuilder)
    {
        $this->beConstructedWith($guidBuilder);
        $this->guidBuilder = $guidBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CampaignUrnDelegate::class);
    }

    public function it_should_throw_an_exception_if_urn_already_set(Campaign $campaign)
    {
        $campaign->getUrn()->shouldBeCalled()->willReturn('urn:campaign:1234');
        $this->shouldThrow(CampaignException::class)->during('onCreate', [$campaign]);
    }

    public function it_should_create_and_set_a_urn(Campaign $campaign)
    {
        $campaign->getUrn()->shouldBeCalled()->willReturn(null);
        $this->guidBuilder->build()->shouldBeCalled()->willReturn(12345);
        $campaign->setUrn('urn:campaign:12345')->shouldBeCalled()->willReturn($campaign);

        $this->onCreate($campaign);
    }
}
