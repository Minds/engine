<?php

namespace Spec\Minds\Core\Groups\V2\Membership;

use DateTime;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Core\Groups\V2\Membership\Repository;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Membership;
use Minds\Core\Recommendations\Algorithms\SuggestedGroups\SuggestedGroupsRecommendationsAlgorithm;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\ACL;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Exceptions\GroupOperationException;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    protected $repositoryMock;
    protected $entitiesBuilderMock;
    protected $aclMock;
    protected $groupRecsAlgoMock;

    public function let(
        Repository $repositoryMock,
        EntitiesBuilder $entitiesBuilderMock,
        ACL $aclMock,
        SuggestedGroupsRecommendationsAlgorithm $groupRecsAlgo
    ) {
        $this->beConstructedWith($repositoryMock, $entitiesBuilderMock, $aclMock, $groupRecsAlgo);
        $this->repositoryMock = $repositoryMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->aclMock = $aclMock;
        $this->groupRecsAlgoMock = $groupRecsAlgo;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    /**
     * Vitess (new) tests
     */
    public function it_should_return_a_membership(Group $groupMock, User $userMock, Membership $membershipMock)
    {
        $groupMock->getGuid()->willReturn(123);
        $userMock->getGuid()->willReturn(456);

        $this->repositoryMock->get(123, 456)
            ->willReturn($membershipMock);

        $membership = $this->getMembership($groupMock, $userMock);
        $membership->shouldBe($membershipMock);
    }

    public function it_should_return_count(Group $groupMock)
    {
        $groupMock->getGuid()->willReturn(123);

        $this->repositoryMock->getCount(123)
            ->willReturn(10);

        $this->getMembersCount($groupMock)->shouldBe(10);
    }

    public function it_should_return_members(Group $groupMock, User $userMock)
    {
        $groupMock->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $membership1 = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MEMBER,
        );

        $membership2 = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MEMBER,
        );

        $this->repositoryMock->getList(123, null, null, false, Argument::any(), Argument::any())
            ->willYield([
                $membership1,
                $membership2,
            ]);

        $this->entitiesBuilderMock->single(Argument::any())->willReturn($userMock);

        $this->aclMock->read(Argument::any())->willReturn(true);

        $this->getMembers($groupMock)->shouldYieldLike([
            $membership1,
            $membership2,
        ]);
    }

    public function it_should_return_members_for_a_provided_membership_level(Group $groupMock, User $userMock)
    {
        $membershipLevel = GroupMembershipLevelEnum::OWNER;

        $groupMock->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $membership1 = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::OWNER,
        );

        $membership2 = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::OWNER,
        );

        $this->repositoryMock->getList(123, null, $membershipLevel, false, Argument::any(), Argument::any())
            ->willYield([
                $membership1,
                $membership2,
            ]);

        $this->entitiesBuilderMock->single(Argument::any())->willReturn($userMock);

        $this->aclMock->read(Argument::any())->willReturn(true);

        $this->getMembers($groupMock, $membershipLevel)->shouldYieldLike([
            $membership1,
            $membership2,
        ]);
    }

    public function it_should_return_requests(Group $groupMock, User $userMock)
    {
        $groupMock->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456, // any number will do
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );

        $this->repositoryMock->getList(123, null, GroupMembershipLevelEnum::REQUESTED, false, Argument::any(), Argument::any())
            ->willYield([
                $membership,
                $membership
            ]);

        $this->entitiesBuilderMock->single(Argument::any())->willReturn($userMock);

        $this->aclMock->read(Argument::any())->willReturn(true);

        $this->getRequests($groupMock)->shouldYieldLike([
            $userMock,
            $userMock,
        ]);
    }

    public function it_should_return_groups(User $userMock, Group $groupMock)
    {
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn(123);

        $membership = new Membership(
            groupGuid: 456, // any number will do
            userGuid: 123,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );

        $this->repositoryMock->getList(null, 123, null, false, Argument::any(), Argument::any())
            ->willYield([
                $membership,
                $membership
            ]);

        $this->entitiesBuilderMock->single(Argument::any())->willReturn($groupMock);

        $this->aclMock->read(Argument::any())->willReturn(true);

        $this->getGroups($userMock)->shouldYieldLike([
            $groupMock,
            $groupMock,
        ]);
    }

    public function it_should_return_group_guids(User $userMock, Group $groupMock)
    {
        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn(789);

        $membership1 = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );

        $membership2 = new Membership(
            groupGuid: 456,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );

        $this->repositoryMock->getList(null, 789, null, false, Argument::any(), Argument::any())
            ->willYield([
                $membership1,
                $membership2
            ]);

        $this->getGroupGuids($userMock)->shouldYieldLike([
            123,
            456,
        ]);
    }

    public function it_should_alter_a_users_membership_level(Group $groupMock, User $userMock, User $actorMock)
    {
        /**
         * Vitess
         */
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $actorMembership = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::OWNER,
        );
        $this->repositoryMock->get(123, 789)->willReturn($actorMembership);

        $this->repositoryMock->updateMembershipLevel(Argument::type(Membership::class))->willReturn(true);

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);
        $actorMock->getGuid()
            ->willReturn(789);

        $this->modifyMembershipLevel($groupMock, $userMock, $actorMock, GroupMembershipLevelEnum::MODERATOR)->shouldBe(true);
    }

    public function it_should_not_alter_a_users_membership_level_if_not_owner(Group $groupMock, User $userMock, User $actorMock)
    {
        /**
         * Vitess
         */
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $actorMembership = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MODERATOR,
        );
        $this->repositoryMock->get(123, 789)->willReturn($actorMembership);

        $this->repositoryMock->updateMembershipLevel(Argument::type(Membership::class))->shouldNotBeCalled();

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);
        $actorMock->getGuid()
            ->willReturn(789);

        $this->shouldThrow(ForbiddenException::class)->duringModifyMembershipLevel($groupMock, $userMock, $actorMock, GroupMembershipLevelEnum::MODERATOR);
    }

    public function it_should_join_group_as_member(Group $groupMock, User $userMock)
    {
        $this->repositoryMock->add(Argument::that(function ($membership) {
            return $membership->membershipLevel === GroupMembershipLevelEnum::MEMBER;
        }))->willReturn(true);

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(true);
        $userMock->getGuid()
            ->willReturn(456);

        $this->groupRecsAlgoMock->setUser($userMock)->willReturn($this->groupRecsAlgoMock);
        $this->groupRecsAlgoMock->purgeCache()->shouldBeCalled();

        $this->joinGroup($groupMock, $userMock)->shouldBe(true);
    }

    public function it_should_join_group_as_request(Group $groupMock, User $userMock)
    {
        $this->repositoryMock->add(Argument::that(function ($membership) {
            return $membership->membershipLevel === GroupMembershipLevelEnum::REQUESTED;
        }))->willReturn(true);

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);

        $this->groupRecsAlgoMock->setUser($userMock)->willReturn($this->groupRecsAlgoMock);
        $this->groupRecsAlgoMock->purgeCache()->shouldBeCalled();

        $this->joinGroup($groupMock, $userMock)->shouldBe(true);
    }

    public function it_should_leave_group(Group $groupMock, User $userMock)
    {
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MEMBER,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);
        $this->repositoryMock->delete(Argument::type(Membership::class))->willReturn(true);

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);

        $this->leaveGroup($groupMock, $userMock)->shouldBe(true);
    }

    public function it_should_cancel_a_group_join_request(
        Group $groupMock,
        User $userMock,
        Membership $membershipMock
    ) {
        $userGuid = '1234567890123450';
        $groupGuid = '1234567890123451';

        $membershipMock->isAwaiting()
            ->shouldBeCalled()
            ->willReturn(true);

        $groupMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($groupGuid);

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->repositoryMock->get($groupGuid, $userGuid)->willReturn($membershipMock);

        $this->repositoryMock->delete(Argument::type(Membership::class))->willReturn(true);

        $this->cancelRequest($groupMock, $userMock)->shouldBe(true);
    }

    public function it_should_NOT_cancel_a_group_join_request_if_there_is_already_a_pending_request(
        Group $groupMock,
        User $userMock,
        Membership $membershipMock
    ) {
        $userGuid = '1234567890123450';
        $groupGuid = '1234567890123451';

        $membershipMock->isAwaiting()
            ->shouldBeCalled()
            ->willReturn(false);

        $groupMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($groupGuid);

        $userMock->getGuid()
            ->shouldBeCalled()
            ->willReturn($userGuid);

        $this->repositoryMock->get($groupGuid, $userGuid)->willReturn($membershipMock);
       
        $this->repositoryMock->delete(Argument::type(Membership::class))->shouldNotBeCalled();

        $this->shouldThrow(
            new GroupOperationException("Cannot cancel as there is no pending membership request.")
        )->during('cancelRequest', [$groupMock, $userMock]);
    }

    public function it_should_accept_user(Group $groupMock, User $userMock, User $actorMock)
    {
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $actorMembership = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MODERATOR,
        );
        $this->repositoryMock->get(123, 789)->willReturn($actorMembership);

        $this->repositoryMock->updateMembershipLevel(Argument::type(Membership::class))->willReturn(true);

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);
        $actorMock->getGuid()
            ->willReturn(789);

        $this->acceptUser($groupMock, $userMock, $actorMock);
    }

    public function it_should_not_accept_if_not_requested(Group $groupMock, User $userMock, User $actorMock)
    {
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MEMBER,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $this->repositoryMock->updateMembershipLevel(Argument::type(Membership::class))->shouldNotBeCalled();

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);

        $this->shouldThrow(UserErrorException::class)->duringAcceptUser($groupMock, $userMock, $actorMock);
    }

    public function it_should_not_accept_if_actor_not_moderator(Group $groupMock, User $userMock, User $actorMock)
    {
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $actorMembership = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MEMBER,
        );
        $this->repositoryMock->get(123, 789)->willReturn($actorMembership);

        $this->repositoryMock->updateMembershipLevel(Argument::type(Membership::class))->shouldNotBeCalled();

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);
        $actorMock->getGuid()
            ->willReturn(789);

        $this->shouldThrow(ForbiddenException::class)->duringAcceptUser($groupMock, $userMock, $actorMock);
    }

    public function it_should_remove_user(Group $groupMock, User $userMock, User $actorMock)
    {
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $actorMembership = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MODERATOR,
        );
        $this->repositoryMock->get(123, 789)->willReturn($actorMembership);

        $this->repositoryMock->delete(Argument::type(Membership::class))->willReturn(true);

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);
        $actorMock->getGuid()
            ->willReturn(789);

        $this->removeUser($groupMock, $userMock, $actorMock);
    }

    public function it_should_not_remove_user_if_not_moderator(Group $groupMock, User $userMock, User $actorMock)
    {
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $actorMembership = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MEMBER,
        );
        $this->repositoryMock->get(123, 789)->willReturn($actorMembership);

        $this->repositoryMock->delete(Argument::type(Membership::class))->shouldNotBeCalled();

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);
        $actorMock->getGuid()
            ->willReturn(789);

        $this->shouldThrow(ForbiddenException::class)->duringRemoveUser($groupMock, $userMock, $actorMock);
    }

    public function it_should_ban_user(Group $groupMock, User $userMock, User $actorMock)
    {
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::REQUESTED,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $actorMembership = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MODERATOR,
        );
        $this->repositoryMock->get(123, 789)->willReturn($actorMembership);

        $this->repositoryMock->updateMembershipLevel(Argument::type(Membership::class))->willReturn(true);

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);
        $actorMock->getGuid()
            ->willReturn(789);

        $this->banUser($groupMock, $userMock, $actorMock);
    }

    public function it_should_unban_user(Group $groupMock, User $userMock, User $actorMock)
    {
        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::BANNED,
        );
        $this->repositoryMock->get(123, 456)->willReturn($membership);

        $actorMembership = new Membership(
            groupGuid: 123,
            userGuid: 789,
            createdTimestamp: new DateTime(),
            membershipLevel: GroupMembershipLevelEnum::MODERATOR,
        );
        $this->repositoryMock->get(123, 789)->willReturn($actorMembership);

        $this->repositoryMock->updateMembershipLevel(Argument::type(Membership::class))->willReturn(true);

        $groupMock->getGuid()
            ->willReturn(123);
        $groupMock->isPublic()
            ->willReturn(false);
        $userMock->getGuid()
            ->willReturn(456);
        $actorMock->getGuid()
            ->willReturn(789);

        $this->unbanUser($groupMock, $userMock, $actorMock);
    }
}
