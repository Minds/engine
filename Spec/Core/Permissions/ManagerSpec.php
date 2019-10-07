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
        $this->user->getGUID()->willReturn(1);
        $this->user->getGUID()->willReturn(1);
        $this->user->getMode()->willReturn(ChannelMode::OPEN);
        $this->user->isSubscribed(1)->willReturn(false);
        $this->user->isSubscribed(2)->willReturn(true);
        $this->user->isSubscribed(3)->willReturn(false);
        $this->subscribedChannel->getGUID()->willReturn(2);
        $this->subscribedChannel->getGUID()->willReturn(2);
        $this->subscribedChannel->getMode()->willReturn(ChannelMode::MODERATED);
        $this->unsubscribedChannel->getGUID()->willReturn(3);
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
        $this->beConstructedWith($this->entitiesBuilder);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_get_permissions_by_guid()
    {
        $this->entitiesBuilder->get([
            "guids" => [10, 11, 12, 13],
        ])
            ->shouldBeCalled()
            ->willReturn($this->mockEntities());
        $this->entitiesBuilder->get([
            "guids" => [10, 11, 12, 13],
        ])
            ->shouldBeCalled()
            ->willReturn($this->mockEntities());

        $permissions = $this->getList([
            'user_guid' => 1,
            'guids' => [10, 11, 12, 13],
            'entities' => [],
        ]);
        $entities = $permissions->getEntities();
        $entities->shouldHaveKey(10);
        $entities->shouldHaveKey(11);
        $entities->shouldHaveKey(12);
        $entities->shouldHaveKey(13);
    }

    public function it_should_get_permissions_by_sending_the_entities()
    {
        $this->entitiesBuilder->get(Argument::any())
            ->shouldNotBeCalled();

        $permissions = $this->getList([
            'user_guid' => 1,
            'guids' => [],
            'entities' => $this->mockEntities(),
        ]);
        $entities = $permissions->getEntities();
        $entities->shouldHaveKey(10);
        $entities->shouldHaveKey(11);
        $entities->shouldHaveKey(12);
        $entities->shouldHaveKey(13);
    }

    public function it_should_throw_an_exception_if_no_user_guid_is_provided()
    {
        $this->shouldThrow(new \InvalidArgumentException('user_guid is required'))->during('getList', [[]]);
    }

    public function it_should_throw_an_exception_if_the_user_does_not_exist()
    {
        $this->entitiesBuilder->single(4)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(new \InvalidArgumentException('User does not exist'))->during('getList', [['user_guid' => 4]]);
    }

    public function it_should_throw_an_exception_if_the_provided_user_guid_doesnt_correspond_to_a_user_entity(Activity $activity)
    {
        $this->entitiesBuilder->single(5)
            ->shouldBeCalled()
            ->willReturn($activity);

        $this->shouldThrow(new \InvalidArgumentException('Entity is not a user'))->during('getList', [['user_guid' => 5]]);
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
