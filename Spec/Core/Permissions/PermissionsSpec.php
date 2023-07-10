<?php

namespace Spec\Minds\Core\Permissions;

use Minds\Core\Permissions\Permissions;
use Minds\Core\Permissions\Roles\Roles;
use Minds\Entities\User;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Core\EntitiesBuilder;
use PhpSpec\ObjectBehavior;
use Prophecy\Prophet;
use Minds\Common\Access;
use Minds\Exceptions\ImmutableException;

class PermissionsSpec extends ObjectBehavior
{
    /** @var User */
    private $user;
    /** @var User */
    private $subscribedChannel;
    /** @var User */
    private $unsubscribedChannel;
    /** @var Group */
    private $group;
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function let(
        User $user,
        User $subscribedChannel,
        User $unsubscribedChannel,
        Group $group,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->user = $user;
        $this->group = $group;
        $this->subscribedChannel = $subscribedChannel;
        $this->unsubscribedChannel = $unsubscribedChannel;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->user->getGUID()->willReturn(1);
        $this->subscribedChannel->getGUID()->willReturn(2);
        $this->unsubscribedChannel->getGUID()->willReturn(3);

        $this->user->isSubscribed(1)->willReturn(false);
        $this->user->isSubscribed(2)->willReturn(true);
        $this->user->isSubscribed(3)->willReturn(false);
        $this->group->getGUID()->willReturn(100);
        $this->group->isCreator($this->user)->willReturn(true);
        $this->entitiesBuilder->single(100)->willReturn($this->group);
        $this->beConstructedWith($this->user, null, $this->entitiesBuilder);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Permissions::class);
    }

    public function it_should_except_when_setting_a_user()
    {
        $this->shouldThrow(new ImmutableException('User can only be set in the constructor'))
            ->duringSetUser($this->user);
    }

    private function mockEntities()
    {
        $prophet = new Prophet();
        $entities = [];
        //Mock user's own activity
        $activity = $prophet->prophesize(Activity::class);
        $activity->getGUID()->willReturn(10);
        $activity->getOwnerGUID()->willReturn(1);
        $activity->getAccessID()->willReturn(Access::PUBLIC);
        $entities[] = $activity;

        //Mock subscriber channel activity
        $activity = $prophet->prophesize(Activity::class);
        $activity->getGUID()->willReturn(11);
        $activity->getOwnerGUID()->willReturn(2);
        $activity->getAccessID()->willReturn(Access::PUBLIC);
        $entities[] = $activity;

        //Mock non-subscriber channel activity
        $activity = $prophet->prophesize(Activity::class);
        $activity->getGUID()->willReturn(12);
        $activity->getOwnerGUID()->willReturn(3);
        $activity->getAccessID()->willReturn(Access::PUBLIC);
        $entities[] = $activity;

        //Mock group activity
        $activity = $prophet->prophesize(Activity::class);
        $activity->getGUID()->willReturn(13);
        $activity->getOwnerGUID()->willReturn(1);
        $activity->getAccessID()->willReturn(100);
        $entities[] = $activity;

        return $entities;
    }
}
