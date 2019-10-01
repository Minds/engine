<?php

namespace Spec\Minds\Core\Permissions;

use Minds\Core\Permissions\Permissions;
use Minds\Core\Permissions\Roles\Roles;
use Minds\Core\Permissions\Roles\Flags;
use Minds\Entities\User;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Core\EntitiesBuilder;
use PhpSpec\ObjectBehavior;
use Prophecy\Prophet;
use Minds\Common\Access;
use Minds\Exceptions\ImmutableException;
use Minds\Common\ChannelMode;

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
        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        $role = $entities[11]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ADMIN);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(true);
        $role = $entities[12]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ADMIN);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(true);
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
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
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
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        //Owns the activity
        $role = $entities[10]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        //Subscribed to someone
        $role = $entities[11]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($role->hasPermission(Flags::FLAG_VIEW))->shouldEqual(true);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        //Not subscribed to someone
        $role = $entities[12]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        expect($role->hasPermission(Flags::FLAG_VIEW))->shouldEqual(false);
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
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[11]->getWrappedObject();
        //Owns the activity
        $role = $entities[10]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        //Subscribed to someone
        $role = $entities[11]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($role->hasPermission(Flags::FLAG_VIEW))->shouldEqual(true);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        //Not subscribed to someone
        $role = $entities[12]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        expect($role->hasPermission(Flags::FLAG_VIEW))->shouldEqual(false);
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
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        //Owns the activity
        $role = $entities[10]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        //Subscribed to someone
        $role = $entities[11]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($role->hasPermission(Flags::FLAG_VIEW))->shouldEqual(true);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        //Not subscribed to someone
        $role = $entities[12]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
        expect($role->hasPermission(Flags::FLAG_VIEW))->shouldEqual(false);
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
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_GROUP_OWNER);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
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
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_GROUP_ADMIN);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
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
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_GROUP_MODERATOR);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
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
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_BANNED);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_BANNED);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_open_group_subscriber_permissions()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(false);
        $this->group->isModerator($this->user)->willReturn(false);
        $this->group->isMember($this->user)->willReturn(true);
        $this->group->isPublic()->willReturn(true);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_OPEN_GROUP_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_open_group_non_subscriber_permissions()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(false);
        $this->group->isModerator($this->user)->willReturn(false);
        $this->group->isMember($this->user)->willReturn(false);
        $this->group->isPublic()->willReturn(true);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_OPEN_GROUP_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }


    public function it_should_return_entity_owner_when_subscribed_to_a_closed_group()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(false);
        $this->group->isModerator($this->user)->willReturn(false);
        $this->group->isMember($this->user)->willReturn(true);
        $this->group->isPublic()->willReturn(false);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_CLOSED_GROUP_SUBSCRIBER);
        $entities = $this->getEntities()->getWrappedObject();
        $role = $entities[13];

        expect($role->getName())->shouldEqual(Roles::ROLE_ENTITY_OWNER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_non_subscriber_for_owner_when_not_subscribed_to_a_closed_group()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(false);
        $this->group->isModerator($this->user)->willReturn(false);
        $this->group->isMember($this->user)->willReturn(false);
        $this->group->isPublic()->willReturn(false);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_CLOSED_GROUP_NON_SUBSCRIBER);
        $entities = $this->getEntities()->getWrappedObject();
        $role = $entities[13];

        expect($role->getName())->shouldEqual(Roles::ROLE_CLOSED_GROUP_NON_SUBSCRIBER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_return_closed_group_non_subscriber_permissions()
    {
        $this->group->isCreator($this->user)->willReturn(false);
        $this->group->isOwner($this->user)->willReturn(false);
        $this->group->isBanned($this->user)->willReturn(false);
        $this->group->isModerator($this->user)->willReturn(false);
        $this->group->isMember($this->user)->willReturn(false);
        $this->group->isPublic()->willReturn(false);
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_MODERATED_CHANNEL_SUBSCRIBER);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_CLOSED_CHANNEL_NON_SUBSCRIBER);
        $groups = $this->getGroups()->getWrappedObject();
        expect($groups[100]->getName())->toEqual(Roles::ROLE_CLOSED_GROUP_NON_SUBSCRIBER);
        $entities = $this->getEntities();
        $role = $entities[13]->getWrappedObject();

        expect($role->getName())->shouldEqual(Roles::ROLE_CLOSED_GROUP_NON_SUBSCRIBER);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    public function it_should_returned_a_closed_channel_non_subscriber_role_for_logged_out()
    {
        $this->beConstructedWith(null, null, $this->entitiesBuilder);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        $channels = $this->getChannels()->getWrappedObject();
        expect($channels[1]->getName())->toEqual(Roles::ROLE_LOGGED_OUT);
        expect($channels[2]->getName())->toEqual(Roles::ROLE_LOGGED_OUT);
        expect($channels[3]->getName())->toEqual(Roles::ROLE_LOGGED_OUT_CLOSED);
        $entities = $this->getEntities();
        $role = $entities[12]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_LOGGED_OUT_CLOSED);
        expect($role->hasPermission(Flags::FLAG_APPOINT_ADMIN))->shouldEqual(false);
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
