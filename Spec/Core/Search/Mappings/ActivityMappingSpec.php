<?php

namespace Spec\Minds\Core\Search\Mappings;

use Minds\Entities\Activity;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\Group;
use PhpSpec\ObjectBehavior;

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
        $activity->getNsfw()->willReturn([1]);
        $activity->getTags()->willReturn(['spaceiscool']);
        $activity->get('license')->willReturn('cc-test-lic');
        $activity->getWireThreshold()->willReturn(null);
        $activity->get('language')->willReturn('en');
        $activity->get('supermind')->willReturn(false);
        $activity->get('source')->willReturn('local');
        $activity->getAutoCaption()->willReturn("");
        $activity->getInferredTags()->willReturn([]);

        $activity->isPayWall()->willReturn(false);
        $activity->getMature()->willReturn(false);
        $activity->isRemind()->willReturn(false);
        $activity->isQuotedPost()->willReturn(false);
        $activity->isPortrait()->willReturn(false);
        $activity->hasAttachments()->willReturn(false);
        $activity->getContainerEntity()->willReturn(null);

        $this
            ->setEntity($activity)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'type' => 'activity',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '1000',
                'container_guid' => '1000',
                'mature' => false,
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'paywall' => false,
                'rating' => 1,
                'message' => 'PHPSpec Message #test #hashtag',
                'custom_type' => 'video',
                'entity_guid' => '8000',
                'pending' => false,
                'license' => 'cc-test-lic',
                'source' => 'local',
                '@timestamp' => $now * 1000,
                'public' => true,
                // 'wire_support_tier' => null,
                // '@wire_support_tier_expire' => null,
                'tags' => ['spaceiscool', 'test', 'hashtag'],
                'nsfw' => [1],
                'is_portrait' => false,
                'is_remind' => false,
                'is_quoted_post' => false,
                'is_supermind' => false,
                'auto_caption' => "",
                'inferred_tags' => [],
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
        $activity->getNsfw()->willReturn([1]);
        $activity->isPayWall()->willReturn(false);
        $activity->getMature()->willReturn(false);
        $activity->getTags()->willReturn(['spaceiscool']);
        $activity->getWireThreshold()->willReturn(null);
        $activity->get('language')->willReturn('en');
        $activity->get('supermind')->willReturn(false);
        $activity->getAutoCaption()->willReturn("");
        $activity->getInferredTags()->willReturn([]);
        $activity->isRemind()->willReturn(false);
        $activity->isQuotedPost()->willReturn(false);
        $activity->isPortrait()->willReturn(false);
        $activity->hasAttachments()->willReturn(false);
        $activity->get('source')->willReturn('local');
        $activity->getContainerEntity()->willReturn(null);

        $this
            ->setEntity($activity)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'type' => 'activity',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '1000',
                'container_guid' => '1000',
                'mature' => false,
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'paywall' => false,
                'rating' => 1,
                'message' => 'PHPSpec Message #test #hashtag',
                'custom_type' => 'video',
                'entity_guid' => '8000',
                'pending' => true,
                'license' => 'cc-test-lic',
                'source' => 'local',
                '@timestamp' => $now * 1000,
                'public' => true,
                // 'wire_support_tier' => null,
                // '@wire_support_tier_expire' => null,
                'tags' => ['spaceiscool', 'test', 'hashtag'],
                'nsfw' => [1],
                'moderator_guid' => '123',
                '@moderated' => $now * 1000,
                'is_portrait' => false,
                'is_remind' => false,
                'is_quoted_post' => false,
                'is_supermind' => false,
                'auto_caption' => '',
                'inferred_tags' => [],
            ]);
    }

    public function it_should_map_a_reminded_post(
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
        $activity->getNsfw()->willReturn([1]);
        $activity->getTags()->willReturn(['spaceiscool']);
        $activity->get('license')->willReturn('cc-test-lic');
        $activity->getWireThreshold()->willReturn(null);
        $activity->get('language')->willReturn('en');
        $activity->get('supermind')->willReturn(false);
        $activity->getAutoCaption()->willReturn("");
        $activity->getInferredTags()->willReturn([]);

        $activity->isPayWall()->willReturn(false);
        $activity->getMature()->willReturn(false);
        $activity->isRemind()->willReturn(true);
        $activity->isQuotedPost()->willReturn(false);
        $activity->isPortrait()->willReturn(false);
        $activity->hasAttachments()->willReturn(false);
        $activity->get('remind_object')
            ->willReturn(['guid' => 123]);
        $activity->get('source')->willReturn('local');
        $activity->getContainerEntity()->willReturn(null);

        $this
            ->setEntity($activity)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'type' => 'activity',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '1000',
                'container_guid' => '1000',
                'mature' => false,
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'paywall' => false,
                'rating' => 1,
                'message' => 'PHPSpec Message #test #hashtag',
                'custom_type' => 'video',
                'entity_guid' => '8000',
                'pending' => false,
                'license' => 'cc-test-lic',
                'source' => 'local',
                '@timestamp' => $now * 1000,
                'public' => true,
                // 'wire_support_tier' => null,
                // '@wire_support_tier_expire' => null,
                'tags' => ['spaceiscool', 'test', 'hashtag'],
                'nsfw' => [1],
                'is_portrait' => false,
                'is_remind' => true,
                'is_quoted_post' => false,
                'remind_guid' => '123',
                'is_supermind' => false,
                'auto_caption' => '',
                'inferred_tags' => [],
            ]);
    }

    public function it_should_map_a_quoted_posted(
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
        $activity->getNsfw()->willReturn([1]);
        $activity->getTags()->willReturn(['spaceiscool']);
        $activity->get('license')->willReturn('cc-test-lic');
        $activity->getWireThreshold()->willReturn(null);
        $activity->get('language')->willReturn('en');
        $activity->get('supermind')->willReturn(false);
        $activity->getAutoCaption()->willReturn("");
        $activity->getInferredTags()->willReturn([]);

        $activity->isPayWall()->willReturn(false);
        $activity->getMature()->willReturn(false);
        $activity->isRemind()->willReturn(false);
        $activity->isQuotedPost()->willReturn(true);
        $activity->isPortrait()->willReturn(false);
        $activity->hasAttachments()->willReturn(false);
        $activity->get('remind_object')->willReturn(['guid' => 123]);
        $activity->get('source')->willReturn('local');

        $activity->getContainerEntity()->willReturn(null);

        $this
            ->setEntity($activity)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'type' => 'activity',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '1000',
                'container_guid' => '1000',
                'mature' => false,
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'paywall' => false,
                'rating' => 1,
                'message' => 'PHPSpec Message #test #hashtag',
                'custom_type' => 'video',
                'entity_guid' => '8000',
                'pending' => false,
                'license' => 'cc-test-lic',
                'source' => 'local',
                '@timestamp' => $now * 1000,
                'public' => true,
                // 'wire_support_tier' => null,
                // '@wire_support_tier_expire' => null,
                'tags' => ['spaceiscool', 'test', 'hashtag'],
                'nsfw' => [1],
                'is_portrait' => false,
                'is_remind' => false,
                'is_quoted_post' => true,
                'remind_guid' => '123',
                'is_supermind' => false,
                'auto_caption' => '',
                'inferred_tags' => [],
            ]);
    }

    public function it_should_map_an_activity_as_public_if_group_is_public(
        Activity $activity,
        Group $group,
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
        $activity->getNsfw()->willReturn([1]);
        $activity->getTags()->willReturn(['spaceiscool']);
        $activity->get('license')->willReturn('cc-test-lic');
        $activity->getWireThreshold()->willReturn(null);
        $activity->get('language')->willReturn('en');
        $activity->get('supermind')->willReturn(false);
        $activity->get('source')->willReturn('local');
        $activity->getAutoCaption()->willReturn("");
        $activity->getInferredTags()->willReturn([]);

        $activity->isPayWall()->willReturn(false);
        $activity->getMature()->willReturn(false);
        $activity->isRemind()->willReturn(false);
        $activity->isQuotedPost()->willReturn(false);
        $activity->isPortrait()->willReturn(false);
        $activity->hasAttachments()->willReturn(false);
        $activity->getContainerEntity()->willReturn($group);

        $activity->getAccessId()->willReturn('1000');
        $group->isPublic()
        ->willReturn(true);
        $group->getGuid()->willReturn('1000');

        $this
            ->setEntity($activity)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '5000',
                'type' => 'activity',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '1000',
                'container_guid' => '1000',
                'mature' => false,
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'paywall' => false,
                'rating' => 1,
                'message' => 'PHPSpec Message #test #hashtag',
                'custom_type' => 'video',
                'entity_guid' => '8000',
                'pending' => false,
                'license' => 'cc-test-lic',
                'source' => 'local',
                '@timestamp' => $now * 1000,
                'public' => true,
                // 'wire_support_tier' => null,
                // '@wire_support_tier_expire' => null,
                'tags' => ['spaceiscool', 'test', 'hashtag'],
                'nsfw' => [1],
                'is_portrait' => false,
                'is_remind' => false,
                'is_quoted_post' => false,
                'is_supermind' => false,
                'auto_caption' => "",
                'inferred_tags' => [],
            ]);
    }

}
