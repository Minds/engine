<?php

namespace Spec\Minds\Core\Search\Mappings;

use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActivityMappingSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Search\Mappings\ActivityMapping');
    }

    public function it_should_map_an_activity(
        Activity $activity
    ) {
        $now = time();
        $activity->get('moderator_guid')->willReturn(null);
        $activity->get('time_moderated')->willReturn(null);
        $activity->get('custom_data')->willReturn(['height' => 200, 'width' => 300]);
        $activity->get('rating')->willReturn(1);
        $activity->get('interactions')->willReturn(42);
        $activity->get('guid')->willReturn(5000);
        $activity->get('type')->willReturn('activity');
        $activity->get('subtype')->willReturn('');
        $activity->get('time_created')->willReturn($now);
        $activity->get('access_id')->willReturn(2);
        $activity->get('owner_guid')->willReturn(1000);
        $activity->get('container_guid')->willReturn(1000);
        $activity->get('mature')->willReturn(false);
        $activity->get('message')->willReturn('PHPSpec Message #test #hashtag');
        $activity->get('name')->willReturn('PHPSpec Name');
        $activity->get('title')->willReturn('PHPSpec Title');
        $activity->get('blurb')->willReturn('PHPSpec Blurb');
        $activity->get('description')->willReturn('PHPSpec Description');
        $activity->get('paywall')->willReturn(false);
        $activity->get('custom_type')->willReturn('video');
        $activity->get('entity_guid')->willReturn(8000);
        $activity->get('pending')->willReturn(false);
        $activity->getNsfw()->willReturn([ 1 ]);
        $activity->getTags()->willReturn([ 'spaceiscool' ]);
        $activity->get('license')->willReturn('cc-test-lic');
        $activity->getWireThreshold()->willReturn(null);
        $activity->get('language')->willReturn('en');

        $activity->isPayWall()->willReturn(false);
        $activity->getMature()->willReturn(false);

        $this
            ->setEntity($activity)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'interactions' => 42,
                'type' => 'activity',
                'subtype' => '',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '1000',
                'container_guid' => '1000',
                'mature' => false,
                'message' => 'PHPSpec Message #test #hashtag',
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'paywall' => false,
                'rating' => 1,
                'custom_type' => 'video',
                'entity_guid' => '8000',
                'pending' => false,
                'license' => 'cc-test-lic',
                '@timestamp' => $now * 1000,
                'taxonomy' => 'activity',
                'public' => true,
                // 'wire_support_tier' => null,
                // '@wire_support_tier_expire' => null,
                'tags' => [ 'spaceiscool', 'test', 'hashtag' ],
                'nsfw' => [ 1 ],
                'is_portrait' => false,
            ]);
    }

    public function it_should_map_an_activity_with_a_moderator(
        Activity $activity
    ) {
        $now = time();
        $activity->get('moderator_guid')->willReturn('123');
        $activity->get('time_moderated')->willReturn(123);
        $activity->get('rating')->willReturn(1);
        $activity->get('interactions')->willReturn(42);
        $activity->get('custom_data')->willReturn(['height' => 200, 'width' => 300]);
        $activity->get('guid')->willReturn(5000);
        $activity->get('type')->willReturn('activity');
        $activity->get('subtype')->willReturn('');
        $activity->get('time_created')->willReturn($now);
        $activity->get('access_id')->willReturn(2);
        $activity->get('owner_guid')->willReturn(1000);
        $activity->get('container_guid')->willReturn(1000);
        $activity->get('mature')->willReturn(false);
        $activity->get('message')->willReturn('PHPSpec Message #test #hashtag');
        $activity->get('name')->willReturn('PHPSpec Name');
        $activity->get('title')->willReturn('PHPSpec Title');
        $activity->get('blurb')->willReturn('PHPSpec Blurb');
        $activity->get('description')->willReturn('PHPSpec Description');
        $activity->get('paywall')->willReturn(false);
        $activity->get('custom_type')->willReturn('video');
        $activity->get('entity_guid')->willReturn(8000);
        $activity->get('pending')->willReturn(true);
        $activity->get('license')->willReturn('cc-test-lic');
        $activity->getNsfw()->willReturn([ 1 ]);
        $activity->isPayWall()->willReturn(false);
        $activity->getMature()->willReturn(false);
        $activity->getTags()->willReturn([ 'spaceiscool' ]);
        $activity->getWireThreshold()->willReturn(null);
        $activity->get('language')->willReturn('en');

        $this
            ->setEntity($activity)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'interactions' => 42,
                'type' => 'activity',
                'subtype' => '',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '1000',
                'container_guid' => '1000',
                'mature' => false,
                'message' => 'PHPSpec Message #test #hashtag',
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'paywall' => false,
                'rating' => 1,
                'custom_type' => 'video',
                'entity_guid' => '8000',
                'pending' => true,
                'license' => 'cc-test-lic',
                '@timestamp' => $now * 1000,
                'taxonomy' => 'activity',
                'public' => true,
                // 'wire_support_tier' => null,
                // '@wire_support_tier_expire' => null,
                'tags' => [ 'spaceiscool', 'test', 'hashtag' ],
                'nsfw' => [ 1 ],
                'moderator_guid' => '123',
                '@moderated' => $now * 1000,
                'is_portrait' => false
            ]);
    }
}
