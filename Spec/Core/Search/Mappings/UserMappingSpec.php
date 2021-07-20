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
        $user->getName()->willReturn('PHPSpec Name');
        $user->get('name')->willReturn('PHPSpec Name');
        $user->get('title')->willReturn('PHPSpec Title');
        $user->get('blurb')->willReturn('PHPSpec Blurb');
        $user->get('description')->willReturn('PHPSpec Description');
        $user->get('paywall')->willReturn(false);
        $user->getUsername()->willReturn('phpspec');
        $user->get('username')->willReturn('phpspec');
        $user->get('briefdescription')->willReturn('PHPSpec Brief Description #invalidhashtag');
        $user->get('rating')->willReturn(1);
        $user->get('moderator_guid')->willReturn('123');
        $user->get('time_moderated')->willReturn($now);
        $user->get('language')->willReturn('en');
        $user->isBanned()->willReturn(false);
        $user->getSpam()->willReturn(false);
        $user->getDeleted()->willReturn(false);
        $user->getEmailConfirmedAt()
            ->shouldBeCalled()
            ->willReturn($now);
        $user->isMature()->willReturn(false);
        $user->getMatureContent()->willReturn(false);
        $user->getGroupMembership()->willReturn([ 2000 ]);
        $user->getNsfw()->willReturn([ 1 ]);
        $user->getTags()->willReturn([ 'spaceiscool' ]);
        $user->getPlusExpires()->willReturn(0);
        $user->getProExpires()->willReturn(0);

        $this
            ->setEntity($user)
            ->map([
                'passedValue' => 'PHPSpec',
                'guid' => '4999-will-disappear'
            ])
            ->shouldReturn([
                'passedValue' => 'PHPSpec',
                'guid' => '1000',
                'type' => 'user',
                'time_created' => $now,
                'access_id' => '2',
                'container_guid' => '1000',
                'mature' => false,
                'message' => 'PHPSpec Message #test #hashtag',
                'name' => 'PHPSpec Name',
                'title' => 'PHPSpec Title',
                'blurb' => 'PHPSpec Blurb',
                'description' => 'PHPSpec Description',
                'language' => 'en',
                'rating' => 1,
                'username' => 'phpspec',
                'briefdescription' => 'PHPSpec Brief Description #invalidhashtag',
                'email_confirmed_at' =>  $now * 1000,
                '@timestamp' => $now * 1000,
                'public' => true,
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

        $user->get('guid')->willReturn(1000);
        $user->get('type')->willReturn('user');
        $user->get('time_created')->willReturn($now);
        $user->get('email_confirmed_at')->willReturn($now);
        $user->get('access_id')->willReturn(2);
        $user->get('subtype')->willReturn('');
        $user->get('owner_guid')->willReturn(false);
        $user->get('container_guid')->willReturn(1000);
        $user->get('mature')->willReturn(false);
        $user->get('is_mature')->willReturn(false);
        $user->get('message')->willReturn('PHPSpec Message #test #hashtag');
        $user->getName()->willReturn('PHPSpec Name');
        $user->get('title')->willReturn('PHPSpec Title');
        $user->get('blurb')->willReturn('PHPSpec Blurb');
        $user->get('description')->willReturn('PHPSpec Description');
        $user->get('paywall')->willReturn(false);
        $user->getUsername()->willReturn('phpspec');
        $user->get('briefdescription')->willReturn('PHPSpec Brief Description #invalidhashtag');
        $user->get('rating')->willReturn(1);
        $user->getTags()->willReturn([ 'spaceiscool' ]);
        $user->isBanned()->willReturn(true);
        $user->getSpam()->willReturn(false);
        $user->getDeleted()->willReturn(false);
        $user->getNsfw()->willReturn([ 1 ]);
        $user->isMature()->willReturn(true);
        $user->getMatureContent()->willReturn(false);
        $user->getGroupMembership()->willReturn([ 2000 ]);
        $user->get('moderator_guid')->willReturn(null);
        $user->get('time_moderated')->willReturn(null);
        $user->get('wire_threshold')->willReturn(null);
        $user->get('language')->willReturn('en');
        $user->get('name')->willReturn('PHPSpec Name');
        $user->get('username')->willReturn('phpspec');

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
        $user->getUsername()->willReturn('phpspec');
        $user->getName()->willReturn('testing framework');
        $user->get('featured_id')->willReturn(12000);
        $user->get('icontime')->willReturn(5000);
        $user->get('time_created')->willReturn(4000);
        $user->getSubscribersCount()->willReturn(10);
        $user->isPro()->willReturn(false);
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
                'weight' => 162
            ]);
    }
    public function it_should_suggest_map_permutating_camelcase_name(
        User $user
    ) {
        $user->getUsername()->willReturn('phpspec');
        $user->getName()->willReturn('TestingFramework');
        $user->get('featured_id')->willReturn(12000);
        $user->get('icontime')->willReturn(5000);
        $user->get('time_created')->willReturn(4000);
        $user->getSubscribersCount()->willReturn(10);
        $user->isPro()->willReturn(false);
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
                'weight' => 162
            ]);
    }
}
