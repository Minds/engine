<?php

namespace Spec\Minds\Core\Groups;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Core\Groups\V2\Membership;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use Minds\Core\Data\Cassandra\Thrift\Relationships;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Membership as MembershipMembership;
use Minds\Entities\Group as GroupEntity;
use PhpSpec\Wrapper\Collaborator;

class ManagementSpec extends ObjectBehavior
{
    protected $dbMock;
    protected $aclMock;
    protected $membershipsManagerMock;
    protected Collaborator $saveMock;

    public function let(
        Relationships $dbMock,
        ACL $aclMock,
        Membership\Manager $membershipsManagerMock,
        Save $saveMock,
    ) {
        $this->beConstructedWith($dbMock, $aclMock, $membershipsManagerMock, $saveMock);

        $this->dbMock = $dbMock;
        $this->aclMock = $aclMock;
        $this->membershipsManagerMock = $membershipsManagerMock;
        $this->saveMock = $saveMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Groups\Management');
    }

    public function it_should_grant_owner(GroupEntity $group, User $user, User $actor, MembershipMembership $membershipMock)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);

        $group->getGuid()->willReturn(50);
    
        $this->membershipsManagerMock->getMembership($group, $user)->shouldBeCalled()->willReturn($membershipMock);
        $membershipMock->isMember()->willReturn(true);

        $group->pushOwnerGuid(1)->shouldBeCalled();

        $this->saveMock->setEntity($group)->shouldBeCalled()->willReturn($this->saveMock);
        $this->saveMock->save()->shouldBeCalled()->willReturn(true);

        $this->dbMock->setGuid(1)->shouldBeCalled();
        $this->dbMock->create('group:owner', 50)->shouldBeCalled()->willReturn(true);

        $this->aclMock->write($group, $actor, null)->shouldBeCalled()->willReturn(true);

        $this->membershipsManagerMock->modifyMembershipLevel($group, $user, $actor, GroupMembershipLevelEnum::OWNER)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->grantOwner($user)->shouldReturn(true);
    }

    public function it_should_revoke_owner(GroupEntity $group, User $user, User $actor)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);

        $group->getGuid()->willReturn(50);
        $group->removeOwnerGuid(1)->shouldBeCalled();

        $this->saveMock->setEntity($group)->shouldBeCalled()->willReturn($this->saveMock);
        $this->saveMock->save()->shouldBeCalled()->willReturn(true);

        $this->dbMock->setGuid(1)->shouldBeCalled();
        $this->dbMock->remove('group:owner', 50)->shouldBeCalled()->willReturn(true);

        $this->aclMock->write($group, $actor, null)->shouldBeCalled()->willReturn(true);

        $this->membershipsManagerMock->modifyMembershipLevel($group, $user, $actor, GroupMembershipLevelEnum::MEMBER)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->revokeOwner($user)->shouldReturn(true);
    }

    public function it_should_grant_moderator(GroupEntity $group, User $user, User $actor, MembershipMembership $membershipMock)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);

        $this->membershipsManagerMock->getMembership($group, $actor)->shouldBeCalled()->willReturn($membershipMock);
        $membershipMock->isOwner()->willReturn(true);

        $group->getGuid()->willReturn(50);

        $this->membershipsManagerMock->getMembership($group, $user)->shouldBeCalled()->willReturn($membershipMock);
        $membershipMock->isMember()->willReturn(true);

        $group->pushModeratorGuid(1)->shouldBeCalled();

        $this->saveMock->setEntity($group)->shouldBeCalled()->willReturn($this->saveMock);
        $this->saveMock->save()->shouldBeCalled()->willReturn(true);

        $this->dbMock->setGuid(1)->shouldBeCalled();
        $this->dbMock->create('group:moderator', 50)->shouldBeCalled()->willReturn(true);

        $this->membershipsManagerMock->modifyMembershipLevel($group, $user, $actor, GroupMembershipLevelEnum::MODERATOR)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->grantModerator($user)->shouldReturn(true);
    }

    public function it_should_revoke_moderator(GroupEntity $group, User $user, User $actor, MembershipMembership $membershipMock)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);

        $this->membershipsManagerMock->getMembership($group, $actor)->shouldBeCalled()->willReturn($membershipMock);
        $membershipMock->isOwner()->willReturn(true);
    
        $group->getGuid()->willReturn(50);
        $group->removeModeratorGuid(1)->shouldBeCalled();

        $this->saveMock->setEntity($group)->shouldBeCalled()->willReturn($this->saveMock);
        $this->saveMock->save()->shouldBeCalled()->willReturn(true);

        $this->dbMock->setGuid(1)->shouldBeCalled();
        $this->dbMock->remove('group:moderator', 50)->shouldBeCalled()->willReturn(true);

        $this->membershipsManagerMock->modifyMembershipLevel($group, $user, $actor, GroupMembershipLevelEnum::MEMBER)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->revokeModerator($user)->shouldReturn(true);
    }
}
