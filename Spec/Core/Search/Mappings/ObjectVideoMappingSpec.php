<?php

namespace Spec\Minds\Core\Search\Mappings;

use Minds\Entities\Video;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ObjectVideoMappingSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Search\Mappings\ObjectVideoMapping');
    }

    public function it_should_map_a_video(
        Video $video
    ) {
        $now = time();

        $video->get('rating')->willReturn(1);
        $video->get('interactions')->willReturn(42);
        $video->get('height')->willReturn(200);
        $video->get('width')->willReturn(300);
        $video->get('guid')->willReturn(5000);
        $video->get('type')->willReturn('object');
        $video->get('subtype')->willReturn('video');
        $video->get('time_created')->willReturn($now);
        $video->get('access_id')->willReturn(2);
        $video->get('owner_guid')->willReturn(1000);
        $video->get('container_guid')->willReturn(1000);
        $video->get('mature')->willReturn(false);
        $video->get('message')->willReturn('PHPSpec Message #test #hashtag');
        $video->get('name')->willReturn('PHPSpec Name');
        $video->get('title')->willReturn('PHPSpec Title');
        $video->get('blurb')->willReturn('PHPSpec Blurb');
        $video->get('description')->willReturn('PHPSpec Description');
        $video->get('paywall')->willReturn(false);
        $video->isPayWall()->willReturn(false);
        $video->get('license')->willReturn('cc-test-lic');
        $video->getTags()->willReturn(['spaceiscool']);
        $video->getFlag('mature')->willReturn(false);
        $video->get('moderator_guid')->willReturn('123');
        $video->get('time_moderated')->willReturn($now);
        $video->getNsfw()->willReturn([1]);
        $video->get('youtube_channel_id')->willReturn('channel_id');
        $video->get('youtube_id')->willReturn('youtube_id');
        $video->get('transcoding_status')->willReturn('queued');
        $video->getWireThreshold()->willReturn(null);
        $video->get('language')->willReturn(null);

        $this
            ->setEntity($video)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear',
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'interactions' => 42,
                'type' => 'object',
                'subtype' => 'video',
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
                'paywall' => false,
                'rating' => 1,
                'license' => 'cc-test-lic',
                'youtube_id' => 'youtube_id',
                'youtube_channel_id' => 'channel_id',
                'transcoding_status' => 'queued',
                '@timestamp' => $now * 1000,
                'taxonomy' => 'object:video',
                'public' => true,
                // 'wire_support_tier' => null,
                // '@wire_support_tier_expire' => null,
                'tags' => ['spaceiscool', 'test', 'hashtag'],
                'nsfw' => [1],
                'moderator_guid' => '123',
                '@moderated' => $now * 1000,
                'is_portrait' => false
            ]);
    }
}
