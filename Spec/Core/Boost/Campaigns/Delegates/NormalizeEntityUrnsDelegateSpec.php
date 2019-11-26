<?php

namespace Spec\Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Boost\Campaigns\Delegates\NormalizeEntityUrnsDelegate;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NormalizeEntityUrnsDelegateSpec extends ObjectBehavior
{
    protected $acl;
    protected $entitiesBuilder;

    public function let(ACL $acl, EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($acl, $entitiesBuilder);
        $this->acl = $acl;
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NormalizeEntityUrnsDelegate::class);
    }

    public function it_should_throw_an_exception_if_campaign_has_no_entities_on_create(Campaign $campaign)
    {
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getEntityUrns()->shouldBeCalled()->willReturn([]);
        $this->shouldThrow(CampaignException::class)->duringOnCreate($campaign);
    }

    public function it_should_throw_an_exception_if_entity_urn_is_invalid_on_create(Campaign $campaign)
    {
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getEntityUrns()->shouldBeCalled()->willReturn(['invalid1', 'invalid2']);
        $this->shouldThrow(CampaignException::class)->duringOnCreate($campaign);
    }

    public function it_should_throw_an_exception_if_entity_urn_does_not_exist_on_create(Campaign $campaign)
    {
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getEntityUrns()->shouldBeCalled()->willReturn(['urn:activity:12345']);
        $this->entitiesBuilder->single(12345)->shouldBeCalled()->willReturn(false);
        $this->shouldThrow(CampaignException::class)->duringOnCreate($campaign);
    }

    public function it_should_throw_an_exception_if_entity_urn_is_not_readable_on_create(Campaign $campaign, Activity $activity)
    {
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getEntityUrns()->shouldBeCalled()->willReturn(['urn:activity:12345']);
        $this->entitiesBuilder->single(12345)->shouldBeCalled()->willReturn($activity);
        $this->acl->read($activity, Argument::type(User::class))->shouldBeCalled()->willReturn(false);
        $this->shouldThrow(CampaignException::class)->duringOnCreate($campaign);
    }

    public function it_should_normalise_entity_urns_on_create(Campaign $campaign, Activity $activity)
    {
        $campaign->getOwnerGuid()->shouldBeCalled()->willReturn(12345);
        $campaign->getEntityUrns()->shouldBeCalled()->willReturn(['urn:activity:12345']);
        $this->entitiesBuilder->single(12345)->shouldBeCalled()->willReturn($activity);
        $this->acl->read($activity, Argument::type(User::class))->shouldBeCalled()->willReturn(true);
        $campaign->setEntityUrns(["urn:activity:12345"])->shouldBeCalled()->willReturn($campaign);
        $this->onCreate($campaign);
    }
}
