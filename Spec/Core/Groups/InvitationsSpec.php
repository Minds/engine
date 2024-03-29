<?php

namespace Spec\Minds\Core\Groups;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Minds\Entities\User;
use Minds\Core\Security\ACL;
use Minds\Core\Data\Call;
use Minds\Core\Data\Cassandra\Thrift\Relationships;
use Minds\Core\Data\Relationships as DataRelationships;
use Minds\Core\EntitiesBuilder;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Entities\Group as GroupEntity;
use Minds\Core\Groups\V2\Membership\Manager as V2MembershipManager;
use Minds\Core\Groups\V2\Membership\Membership;

class InvitationsSpec extends ObjectBehavior
{
    protected $acl;
    protected $relDb;
    protected $friendsDb;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    /** @var V2MembershipManager */
    protected $membershipManagerMock;

    public function let(
        Relationships $relDb,
        ACL $acl,
        Call $friendsDb,
        EntitiesBuilder $entitiesBuilder,
        ActionEventsTopic $actionEventsTopic,
        V2MembershipManager $membershipManagerMock,
    ) {
        $this->beConstructedWith($relDb, $acl, $friendsDb, $entitiesBuilder, $actionEventsTopic, $membershipManagerMock);
        $this->relDb = $relDb;
        $this->acl = $acl;
        $this->friendsDb = $friendsDb;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->actionEventsTopic = $actionEventsTopic;
        $this->membershipManagerMock = $membershipManagerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Groups\Invitations');
    }

    public function it_should_get_invitations(GroupEntity $group)
    {
        $this->relDb->setGuid(50)->shouldBeCalled();
        $this->relDb->get('group:invited', Argument::any())->shouldBeCalled()->willReturn([11, 12, 13]);

        $group->getGuid()->willReturn(50);

        $this->setGroup($group);
        $this->getInvitations([ 'hydrate' => false ])->shouldReturn([11, 12, 13]);
    }

    public function it_should_check_invited_users_in_batch(GroupEntity $group)
    {
        $group->getGuid()->willReturn(50);

        $this->relDb->setGuid(50)->shouldBeCalled();
        $this->relDb->get('group:invited', Argument::any())->shouldBeCalled()->willReturn([11, 12, 13]);

        $this->setGroup($group);
        $this->isInvitedBatch([11, 12, 14])->shouldReturn([11 => true, 12 => true, 14 => false]);
    }

    public function it_should_check_if_its_invited(GroupEntity $group, User $user)
    {
        $user->get('guid')->willReturn(1);
        $group->getGuid()->willReturn(50);

        $this->relDb->setGuid(1)->shouldBeCalled();
        $this->relDb->check('group:invited', 50)->shouldBeCalled()->willReturn(true);

        $this->setGroup($group);
        $this->isInvited($user)->shouldReturn(true);
    }

    public function it_should_invite_to_a_public_group(GroupEntity $group, User $user, User $actor, Membership $membershipMock, Membership $membershipMock2)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);
        $actor->isAdmin()->willReturn(false);

        $group->getGuid()->willReturn(50);
        $group->isPublic()->willReturn(true);

        $this->membershipManagerMock->getMembership($group, $actor)->willReturn($membershipMock);
        $membershipMock->isMember()->willReturn(true);
        
        $this->membershipManagerMock->getMembership($group, $user)->willReturn($membershipMock2);
        $membershipMock2->isMember()->willReturn(false);

        $group->getUrn()->willReturn('urn:group:50');

        $this->relDb->setGuid(1)->shouldBeCalled();
        $this->relDb->create('group:invited', 50)->shouldBeCalled()->willReturn(true);

        $this->friendsDb->getRow(2, Argument::any())->shouldBeCalled()->willReturn([ '1' => 123456 ]);

        $this->actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return true;
        }))
            ->willReturn(true);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->invite($user, [ 'notify' => false ])->shouldReturn(true);
    }

    public function it_should_not_invite_to_a_private_group(GroupEntity $group, User $user, User $actor, Membership $membershipMock, Membership $membershipMock2)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);
        $actor->isAdmin()->willReturn(false);

        $group->getGuid()->willReturn(50);
        $group->isPublic()->willReturn(false);
        
        $this->membershipManagerMock->getMembership($group, $actor)->willReturn($membershipMock);
        $membershipMock->isMember()->willReturn(true);
        
        $this->membershipManagerMock->getMembership($group, $user)->willReturn($membershipMock2);
        $membershipMock2->isMember()->willReturn(false);

        $this->relDb->create('group:invited', 50)->shouldNotBeCalled();

        $this->acl->write($group, $actor)->shouldBeCalled()->willReturn(false);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->shouldThrow('\Minds\Exceptions\GroupOperationException')->duringInvite($user, [ 'notify' => false ]);
    }

    public function it_should_invite_to_a_private_group_by_an_owner(GroupEntity $group, User $user, User $actor, Membership $membershipMock, Membership $membershipMock2)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);
        $actor->isAdmin()->willReturn(false);

        $group->getGuid()->willReturn(50);
        $group->isPublic()->willReturn(false);

        $this->membershipManagerMock->getMembership($group, $actor)->willReturn($membershipMock);
        $membershipMock->isMember()->willReturn(true);
        
        $this->membershipManagerMock->getMembership($group, $user)->willReturn($membershipMock2);
        $membershipMock2->isMember()->willReturn(false);

        $group->getUrn()->willReturn('urn:group:50');

        $this->relDb->setGuid(1)->shouldBeCalled();
        $this->relDb->create('group:invited', 50)->shouldBeCalled()->willReturn(true);

        $this->acl->write($group, $actor)->shouldBeCalled()->willReturn(true);

        $this->friendsDb->getRow(2, Argument::any())->shouldBeCalled()->willReturn([ '1' => 123456 ]);

        $this->actionEventsTopic->send(Argument::that(function ($actionEvent) {
            return true;
        }))
            ->willReturn(true);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->invite($user, [ 'notify' => false ])->shouldReturn(true);
    }

    public function it_should_uninvite(GroupEntity $group, User $user, User $actor, Membership $membershipMock, Membership $membershipMock2)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);
        $actor->isAdmin()->willReturn(false);

        $group->getGuid()->willReturn(50);
        $group->isPublic()->willReturn(true);

        $this->membershipManagerMock->getMembership($group, $actor)->willReturn($membershipMock);
        $membershipMock->isMember()->willReturn(true);
        
        $this->membershipManagerMock->getMembership($group, $user)->willReturn($membershipMock2);
        $membershipMock2->isMember()->willReturn(false);

        $this->relDb->setGuid(1)->shouldBeCalled();
        $this->relDb->remove('group:invited', 50)->shouldBeCalled()->willReturn(true);

        $this->friendsDb->getRow(2, Argument::any())->shouldBeCalled()->willReturn([ '1' => 123456 ]);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->uninvite($user)->shouldReturn(true);
    }

    public function it_should_not_uninvite_a_non_subscriber(GroupEntity $group, User $user, User $actor, Membership $membershipMock, Membership $membershipMock2)
    {
        $user->get('guid')->willReturn(1);

        $actor->get('guid')->willReturn(2);
        $actor->isAdmin()->willReturn(false);

        $group->getGuid()->willReturn(50);
        $group->isPublic()->willReturn(true);
        
        $this->membershipManagerMock->getMembership($group, $actor)->willReturn($membershipMock);
        $membershipMock->isMember()->willReturn(true);
        
        $this->membershipManagerMock->getMembership($group, $user)->willReturn($membershipMock2);
        $membershipMock2->isMember()->willReturn(false);

        $this->relDb->remove('group:invited', 50)->shouldNotBeCalled();

        $this->friendsDb->getRow(2, Argument::any())->shouldBeCalled()->willReturn([]);

        $this->setGroup($group);
        $this->setActor($actor);
        $this->shouldThrow('\Minds\Exceptions\GroupOperationException')->duringUninvite($user);
    }

    public function it_should_accept(GroupEntity $group, User $user)
    {
        $user->get('guid')->willReturn(1);
        $group->getGuid()->willReturn(50);

        $this->relDb->setGuid(1)->shouldBeCalled();
        $this->relDb->check('group:invited', 50)->shouldBeCalled()->willReturn(true);
        $this->relDb->remove('group:invited', 50)->shouldBeCalled()->willReturn(true);

        $this->membershipManagerMock->joinGroup($group, $user, GroupMembershipLevelEnum::MEMBER)->shouldBeCalled()->willReturn(true);

        $this->setGroup($group);
        $this->setActor($user);
        $this->accept()->shouldReturn(true);
    }

    public function it_should_fail_to_accept_if_not_invited(GroupEntity $group, User $user)
    {
        $user->get('guid')->willReturn(1);
        $group->getGuid()->willReturn(50);

        $this->relDb->setGuid(1)->shouldBeCalled();
        $this->relDb->check('group:invited', 50)->shouldBeCalled()->willReturn(false);
        $this->relDb->remove('group:invited', 50)->shouldNotBeCalled();

        $this->membershipManagerMock->joinGroup($group, $user)->shouldNotBeCalled();

        $this->setGroup($group);
        $this->setActor($user);
        $this->shouldThrow('\Minds\Exceptions\GroupOperationException')->duringAccept();
    }

    public function it_should_decline(GroupEntity $group, User $user)
    {
        $user->get('guid')->willReturn(1);
        $group->getGuid()->willReturn(50);

        $this->relDb->setGuid(1)->shouldBeCalled();
        $this->relDb->check('group:invited', 50)->shouldBeCalled()->willReturn(true);
        $this->relDb->remove('group:invited', 50)->shouldBeCalled()->willReturn(true);

        $this->setGroup($group);
        $this->setActor($user);
        $this->decline()->shouldReturn(true);
    }
}
