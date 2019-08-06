<?php

namespace Spec\Minds\Core\Permissions;

use Minds\Core\Permissions\Permissions;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Permissions\Roles\Roles;
use Minds\Entities\User;
use Minds\Entities\Activity;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;

class PermissionsSpec extends ObjectBehavior
{
    /** @var User */
    private $user;
    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function let(
        User $user,
        EntitiesBuilder $entitiesBuilder
    ) {
        $this->user = $user;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->user->get('guid')->willReturn(1);
    }

    function it_is_initializable()
    {
        $this->beConstructedWith($this->user, $this->entitiesBuilder);
        $this->shouldHaveType(Permissions::class);
    }

    function it_should_return_admin_permissions() {
        $this->user->isAdmin()->willReturn(true);
        $this->user->isBanned()->willReturn(false);
        $this->beConstructedWith($this->user, $this->entitiesBuilder);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(true);
        $this->getIsBanned()->shouldEqual(false);

        $entities = $this->getEntities();
        expect(count($entities))->shouldEqual(1);
        $role = $entities[10]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_ADMIN);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(true);
    }

    function it_should_return_banned_permissions() {
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(true);
        $this->beConstructedWith($this->user, $this->entitiesBuilder);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(true);

        $entities = $this->getEntities();
        expect(count($entities))->shouldEqual(1);
        $role = $entities[10]->getWrappedObject();
        
        expect($role->getName())->shouldEqual(Roles::ROLE_BANNED);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    function it_should_return_owner_permissions() {
        $this->user->isAdmin()->willReturn(false);
        $this->user->isBanned()->willReturn(false);
        $this->beConstructedWith($this->user, $this->entitiesBuilder);
        $this->calculate($this->mockEntities());
        $this->getIsAdmin()->shouldEqual(false);
        $this->getIsBanned()->shouldEqual(false);
        expect($this->getChannels()[1]->getWrappedObject())->toEqual($this->user->getWrappedObject());
        $entities = $this->getEntities();
        $role = $entities[10]->getWrappedObject();
        expect($role->getName())->shouldEqual(Roles::ROLE_CHANNEL_OWNER);
        expect($role->hasPermission(Roles::FLAG_APPOINT_ADMIN))->shouldEqual(false);
    }

    private function mockEntities() {
        $prophet = new Prophet();
        $entities = [];
        $activity = $prophet->prophesize(Activity::class);
        $activity->get('guid')->willReturn(10);
        $activity->getOwnerGUID()->willReturn(1);
        $activity->getContainerEntity()->willReturn($this->user->getWrappedObject());
        $entities[] = $activity;
        return $entities;
    }
}
