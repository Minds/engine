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

    public function it_should_return_admin_permissions()
    {
        $this->user->isAdmin()->willReturn(true);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(true);
        $this->getIsBanned()->shouldEqual(false);

        $entities = $this->getEntities();
        expect(count($entities->getWrappedObject()))->shouldEqual(4);
        $role = $entities[10]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ADMIN);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(true);
        $role = $entities[11]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ADMIN);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(true);
        $role = $entities[12]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ADMIN);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(true);
    }

    public function it_should_return_banned_permissions()
    {
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(true);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(true);
        $entities = $this->getEntities();
        expect(count($entities->getWrappedObject()))->shouldEqual(4);
        $role = $entities[10]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_BANNED);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_owner_permissions()
    {
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[10]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_subscriber_permissions()
    {
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[11]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_non_subscriber_permissions()
    {
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[12]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_group_owner_permissions()
    {
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_GROUP_OWNER);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_GROUP_OWNER);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_group_admin_permissions()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(true);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_GROUP_ADMIN);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_GROUP_ADMIN);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_group_moderator_permissions()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(false);
        $this->group->isModerator($this->user)->willReturn(true);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_GROUP_MODERATOR);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_GROUP_MODERATOR);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_group_banned_permissions()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(true);
        $this->group->isModerator($this->user)->willReturn(false);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_BANNED);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_BANNED);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_group_subscriber_permissions()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(false);
        $this->group->isModerator($this->user)->willReturn(false);
        $this->group->isMember($this->user)->willReturn(true);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_GROUP_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_GROUP_SUBSCRIBER);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_group_non_subscriber_permissions()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(false);
        $this->group->isModerator($this->user)->willReturn(false);
        $this->group->isMember($this->user)->willReturn(false);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_GROUP_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_GROUP_NON_SUBSCRIBER);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
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
