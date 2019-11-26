<?php

namespace Spec\Minds\Core\Boost\Campaigns\Delegates;

use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Campaigns\CampaignException;
use Minds\Core\Boost\Campaigns\Delegates\NormalizeHashtagsDelegate;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NormalizeHashtagsDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NormalizeHashtagsDelegate::class);
    }

    public function it_should_throw_exception_if_more_than_5_hashtags_on_create(Campaign $campaign)
    {
        $hashtags = [
            'one',
            'two',
            'three',
            'four',
            'five',
            'six'
        ];

        $campaign->getHashtags()->shouldBeCalled()->willReturn($hashtags);
        $this->shouldThrow(CampaignException::class)->during('onCreate', [$campaign]);
    }

    public function it_should_throw_exception_if_more_than_5_hashtags_on_update(Campaign $campaign, Campaign $campaignRef)
    {
        $hashtags = [
            'one',
            'two',
            'three',
            'four',
            'five',
            'six'
        ];

        $campaignRef->getHashtags()->shouldBeCalled()->willReturn($hashtags);
        $campaign->setHashtags($hashtags)->shouldBeCalled()->willReturn($campaign);
        $campaign->getHashtags()->shouldBeCalled()->willReturn($hashtags);
        $this->shouldThrow(CampaignException::class)->during('onUpdate', [$campaign, $campaignRef]);
    }

    public function it_should_normalise_hashtags_on_create(Campaign $campaign)
    {
        $hashtags = [
            'on e',
            'two',
            'th*ree',
            'four',
            'five'
        ];

        $hashtagsNormal = [
            'one',
            'two',
            'three',
            'four',
            'five'
        ];

        $campaign->getHashtags()->shouldBeCalled()->willReturn($hashtags);
        $campaign->setHashtags($hashtagsNormal)->shouldBeCalled()->willReturn($campaign);
        $this->onCreate($campaign);
    }

    public function it_should_normalise_hashtags_on_update(Campaign $campaign, Campaign $campaignRef)
    {
        $hashtags = [
            'on e',
            'two',
            'th*ree',
            'four',
            'five'
        ];

        $hashtagsNormal = [
            'one',
            'two',
            'three',
            'four',
            'five'
        ];

        $campaignRef->getHashtags()->shouldBeCalled()->willReturn($hashtags);
        $campaign->setHashtags($hashtags)->shouldbeCalled()->willReturn($campaign);
        $campaign->getHashtags()->shouldBeCalled()->willReturn($hashtags);
        $campaign->setHashtags($hashtagsNormal)->shouldBeCalled()->willReturn($campaign);
        $this->onUpdate($campaign, $campaignRef);
    }
}
