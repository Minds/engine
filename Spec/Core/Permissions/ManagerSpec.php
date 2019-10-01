<?php

namespace Spec\Minds\Core\Permissions;

use Minds\Core\Permissions\Manager;
use Minds\Entities\User;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Core\EntitiesBuilder;
use PhpSpec\ObjectBehavior;
use Prophecy\Prophet;
use Prophecy\Argument;
use Minds\Common\ChannelMode;
use Minds\Common\Access;

class ManagerSpec extends ObjectBehavior
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
        $this->user->getType()->willReturn('user');
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->user->getGuid()->willReturn(1);
        $this->user->getGUID()->willReturn(1);
        $this->user->getMode()->willReturn(ChannelMode::OPEN);
        $this->user->isSubscribed(1)->willReturn(false);
        $this->user->isSubscribed(2)->willReturn(true);
        $this->user->isSubscribed(3)->willReturn(false);
        $this->subscribedChannel->getGuid()->willReturn(2);
        $this->subscribedChannel->getGUID()->willReturn(2);
        $this->subscribedChannel->getMode()->willReturn(ChannelMode::MODERATED);
        $this->unsubscribedChannel->getGuid()->willReturn(3);
        $this->unsubscribedChannel->getGUID()->willReturn(3);
        $this->unsubscribedChannel->getMode()->willReturn(ChannelMode::CLOSED);
        $this->group->getGuid()->willReturn(100);
        $this->group->isCreator($this->user)->willReturn(true);
        $this->group->isPublic()->willReturn(true);
        $this->entitiesBuilder->single(100)->willReturn($this->group);
        $this->entitiesBuilder->single(1)->willReturn($this->user);
        $this->entitiesBuilder->single(2)->willReturn($this->subscribedChannel);
        $this->entitiesBuilder->single(3)->willReturn($this->unsubscribedChannel);
        $this->entitiesBuilder->build($this->user)->willReturn($this->user);
        $this->entitiesBuilder->build($this->subscribedChannel)->willReturn($this->subscribedChannel);
        $this->entitiesBuilder->build($this->unsubscribedChannel)->willReturn($this->unsubscribedChannel);
        $this->entitiesBuilder->get([
            "user_guid" => 1,
            "guids" => [10, 11, 12, 13],
            "entities" => [],
        ])->willReturn($this->mockEntities());
        $this->entitiesBuilder->get([
            "guids" => [10, 11, 12, 13]
        ])->willReturn($this->mockEntities());
        $this->beConstructedWith($this->entitiesBuilder);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_permissions()
    {
        $permissions = $this->getList([
            'user_guid' => 1,
            'guids'=> [10, 11, 12, 13],
            'entities' => []
            ]);
        $entities = $permissions->getEntities();
        $entities->shouldHaveKey(10);
        $entities->shouldHaveKey(11);
        $entities->shouldHaveKey(12);
        $entities->shouldHaveKey(13);
    }

    private function mockEntities()
    {
        $prophet = new Prophet();
        $entities = [];
        //Mock user's own activity
        $activity = $prophet->prophesize(Activity::class);
        $activity->getGUID()->willReturn(10);
        $activity->getType()->willReturn('activity');
        $activity->getOwnerGUID()->willReturn(1);
        $activity->getOwnerObj()->willReturn($this->user->getWrappedObject());
        $activity->getAccessId()->willReturn(Access::PUBLIC);
        $entities[] = $activity;

        //Mock subscriber channel activity
        $activity = $prophet->prophesize(Activity::class);
        $activity->getGUID()->willReturn(11);
        $activity->getType()->willReturn('activity');
        $activity->getOwnerGUID()->willReturn(2);
        $activity->getOwnerObj()->willReturn($this->subscribedChannel->getWrappedObject());
        $activity->getAccessId()->willReturn(Access::PUBLIC);
        $entities[] = $activity;

        //Mock non-subscriber channel activity
        $activity = $prophet->prophesize(Activity::class);
        $activity->getGUID()->willReturn(12);
        $activity->getType()->willReturn('activity');
        $activity->getOwnerGUID()->willReturn(3);
        $activity->getOwnerObj()->willReturn($this->unsubscribedChannel->getWrappedObject());
        $activity->getAccessId()->willReturn(Access::PUBLIC);
        $entities[] = $activity;

        //Mock group activity
        $activity = $prophet->prophesize(Activity::class);
        $activity->getGUID()->willReturn(13);
        $activity->getType()->willReturn('activity');
        $activity->getOwnerGUID()->willReturn(1);
        $activity->getAccessId()->willReturn(100);
        $entities[] = $activity;

        return $entities;
    }
}
