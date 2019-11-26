<?php

namespace Spec\Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Boost\Campaigns\Delegates\NormalizeDatesDelegate;
use PhpSpec\ObjectBehavior;

class NormalizeDatesDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NormalizeDatesDelegate::class);
    }

    public function it_should_reject_start_date_in_past_on_create(Campaign $campaign)
    {
        $campaign->getStart()->willReturn(strtotime('-1 day') * 1000);
        $campaign->getEnd()->willReturn(strtotime('+3 days') * 1000);
        $this->shouldThrow(CampaignException::class)->during('onCreate', [$campaign]);
    }

    public function it_should_reject_end_date_before_start_date_on_create(Campaign $campaign)
    {
        $campaign->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaign->getEnd()->willReturn(strtotime('+1 days') * 1000);
        $this->shouldThrow(CampaignException::class)->during('onCreate', [$campaign]);
    }

    public function it_should_adjust_start_and_end_to_first_and_last_timestamps_of_days_on_create(Campaign $campaign)
    {
        $campaign->getStart()->willReturn(strtotime('today 12:01:31') * 1000);
        $campaign->getEnd()->willReturn(strtotime('+1 days 17:32:05') * 1000);

        $campaign->setStart(strtotime('today 00:00:00') * 1000)->shouldBeCalled()->willReturn($campaign);
        $campaign->setEnd(strtotime('+1 days 23:59:59') * 1000)->shouldBeCalled()->willReturn($campaign);

        $this->onCreate($campaign);
    }

    public function it_should_accept_valid_days_on_create(Campaign $campaign)
    {
        $campaign->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaign->getEnd()->willReturn(strtotime('+5 days') * 1000);
        $campaign->setStart(strtotime('+2 days 00:00:00') * 1000)->shouldBeCalled()->willReturn($campaign);
        $campaign->setEnd(strtotime('+5 days 23:59:59') * 1000)->shouldBeCalled()->willReturn($campaign);

        $this->onCreate($campaign);
    }

    public function it_should_reject_campaign_longer_than_one_month(Campaign $campaign)
    {
        $campaign->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaign->getEnd()->willReturn(strtotime('+34 days') * 1000);

        $this->shouldThrow(CampaignException::class)->during('onCreate', [$campaign]);
    }

    /*    public function it_should_validate_dates_are_valid_against_campaign_budget_on_update()
        {
            // TODO: Not Implemented yet
        }*/

    public function it_should_not_change_start_date_if_campaign_has_started_on_update(Campaign $campaign, Campaign $campaignRef)
    {
        $campaign->hasStarted()->shouldBeCalled()->willReturn(true);
        $campaign->hasFinished()->shouldBeCalled()->willReturn(true);
        $campaignRef->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaignRef->getEnd()->willReturn(strtotime('+5 days') * 1000);

        $campaign->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaign->getEnd()->willReturn(strtotime('+5 days') * 1000);

        $this->onUpdate($campaign, $campaignRef);
    }

    public function it_should_change_start_date_if_campaign_has_not_started_on_update(Campaign $campaign, Campaign $campaignRef)
    {
        $campaign->hasStarted()->shouldBeCalled()->willReturn(false);
        $campaign->hasFinished()->shouldBeCalled()->willReturn(false);
        $campaignRef->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaignRef->getEnd()->willReturn(strtotime('+5 days') * 1000);

        $campaign->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaign->getEnd()->willReturn(strtotime('+5 days') * 1000);

        $campaign->setStart(strtotime('+2 days 00:00:00') * 1000)->shouldBeCalled()->willReturn($campaign);
        $campaign->setEnd(strtotime('+5 days 23:59:59') * 1000)->shouldBeCalled();

        $this->onUpdate($campaign, $campaignRef);
    }

    public function it_should_only_change_end_date_if_campaign_hasnt_finished_on_update(Campaign $campaign, Campaign $campaignRef)
    {
        $campaign->hasStarted()->shouldBeCalled()->willReturn(true);
        $campaign->hasFinished()->shouldBeCalled()->willReturn(false);
        $campaignRef->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaignRef->getEnd()->willReturn(strtotime('+7 days') * 1000);

        $campaign->getStart()->willReturn(strtotime('+2 days') * 1000);
        $campaign->getEnd()->willReturn(strtotime('+5 days') * 1000);

        $campaign->setEnd(strtotime('+7 days 23:59:59') * 1000)->shouldBeCalled();

        $this->onUpdate($campaign, $campaignRef);
    }
}
