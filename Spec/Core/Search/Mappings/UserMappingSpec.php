<?php

namespace Spec\Minds\Core\Search\Mappings;

use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UserMappingSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Search\Mappings\UserMapping');
    }

    public function it_should_map_a_user(
        User $user
    ) {
        $now = time();

        $user->get('interactions')->willReturn(42);
        $user->get('guid')->willReturn(1000);
        $user->get('type')->willReturn('user');
        $user->get('subtype')->willReturn('');
        $user->get('time_created')->willReturn($now);
        $user->get('email_confirmed_at')->willReturn($now);
        $user->get('access_id')->willReturn(2);
        $user->get('owner_guid')->willReturn(false);
        $user->get('container_guid')->willReturn(1000);
        $user->get('mature')->willReturn(false);
        $user->get('message')->willReturn('PHPSpec Message #test #hashtag');
        $user->get('name')->willReturn('PHPSpec Name');
        $user->get('title')->willReturn('PHPSpec Title');
        $user->get('blurb')->willReturn('PHPSpec Blurb');
        $user->get('description')->willReturn('PHPSpec Description');
        $user->get('paywall')->willReturn(false);
        $user->get('username')->willReturn('phpspec');
        $user->get('briefdescription')->willReturn('PHPSpec Brief Description #invalidhashtag');
        $user->get('rating')->willReturn(1);
        $user->get('moderator_guid')->willReturn('123');
        $user->get('time_moderated')->willReturn($now);
        $user->get('language')->willReturn('en');
        $user->isBanned()->willReturn(false);
        $user->getEmailConfirmedAt()
            ->shouldBeCalled()
            ->willReturn($now);
        $user->isMature()->willReturn(false);
        $user->getMatureContent()->willReturn(false);
        $user->getGroupMembership()->willReturn([ 2000 ]);
        $user->getNsfw()->willReturn([ 1 ]);
        $user->getTags()->willReturn([ 'spaceiscool' ]);
        $user->get('wire_threshold')->willReturn(null);

        $this
            ->setEntity($user)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '1000',
                'interactions' => 42,
                'type' => 'user',
                'subtype' => '',
                'time_created' => $now,
                'access_id' => '2',
                'owner_guid' => '',
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
                'username' => 'phpspec',
                'briefdescription' => 'PHPSpec Brief Description #invalidhashtag',
                'email_confirmed_at' =>  $now * 1000,
                '@timestamp' => $now * 1000,
                'taxonomy' => 'user',
                'public' => true,
                'wire_support_tier' => null,
                'tags' => [ 'spaceiscool' ],
                'nsfw' => [ 1 ],
                'moderator_guid' => '123',
                '@moderated' => $now * 1000,
                'group_membership' => [ 2000 ],
            ]);
    }

    public function it_should_throw_exception_if_banned(User $user)
    {
        $now = time();

        $user->get('interactions')->willReturn(42);
        $user->get('guid')->willReturn(1000);
        $user->get('type')->willReturn('user');
        $user->get('subtype')->willReturn('');
        $user->get('time_created')->willReturn($now);
        $user->get('email_confirmed_at')->willReturn($now);
        $user->get('access_id')->willReturn(2);
        $user->get('owner_guid')->willReturn(false);
        $user->get('container_guid')->willReturn(1000);
        $user->get('mature')->willReturn(false);
        $user->get('is_mature')->willReturn(false);
        $user->get('message')->willReturn('PHPSpec Message #test #hashtag');
        $user->get('name')->willReturn('PHPSpec Name');
        $user->get('title')->willReturn('PHPSpec Title');
        $user->get('blurb')->willReturn('PHPSpec Blurb');
        $user->get('description')->willReturn('PHPSpec Description');
        $user->get('paywall')->willReturn(false);
        $user->get('username')->willReturn('phpspec');
        $user->get('briefdescription')->willReturn('PHPSpec Brief Description #invalidhashtag');
        $user->get('rating')->willReturn(1);
        $user->getTags()->willReturn([ 'spaceiscool' ]);
        $user->isBanned()->willReturn(true);
        $user->getNsfw()->willReturn([ 1 ]);
        $user->isMature()->willReturn(true);
        $user->getMatureContent()->willReturn(false);
        $user->getGroupMembership()->willReturn([ 2000 ]);
        $user->get('moderator_guid')->willReturn(null);
        $user->get('time_moderated')->willReturn(null);
        $user->get('wire_threshold')->willReturn(null);
        $user->get('language')->willReturn('en');

        $this
            ->setEntity($user)
            ->shouldThrow('Minds\Exceptions\BannedException')
            ->duringMap([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ]);
    }

    public function it_should_suggest_map(
        User $user
    ) {
        $user->get('username')->willReturn('phpspec');
        $user->get('name')->willReturn('testing framework');
        $user->get('featured_id')->willReturn(12000);
        $user->isAdmin()->willReturn(true);

        $this
            ->setEntity($user)
            ->suggestMap([
                'passedValue' => 'PHPSpec',
                'input' => 'phpspec-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'input' => [
                    'phpspec',
                    'testing framework'
                ],
                'weight' => 152
            ]);
    }
    public function it_should_suggest_map_permutating_camelcase_name(
        User $user
    ) {
        $user->get('username')->willReturn('phpspec');
        $user->get('name')->willReturn('TestingFramework');
        $user->get('featured_id')->willReturn(12000);
        $user->isAdmin()->willReturn(true);

        $this
            ->setEntity($user)
            ->suggestMap([
                'passedValue' => 'PHPSpec',
                'input' => 'phpspec-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'input' => [
                    'phpspec',
                    'TestingFramework',
                    'Testing Framework',
                    'Framework Testing',
                ],
                'weight' => 152
            ]);
    }
}
