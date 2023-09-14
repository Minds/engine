<?php

namespace Spec\Minds\Core\Groups\V2\Membership;

use DateTime;
use Google\Service\FirebaseRules\Arg;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager;
use Minds\Core\Groups\V2\Membership\Repository;
use Minds\Core\Groups\Membership as LegacyMembership;
use Minds\Core\Experiments;
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

use function PHPSTORM_META\map;

class ManagerSpec extends ObjectBehavior
{
    protected $repositoryMock;
    protected $entitiesBuilderMock;
    protected $aclMock;
    protected $legacyMembershipMock;
    protected $experimentsManagerMock;
    protected $groupRecsAlgoMock;

    public function let(
        Repository $repositoryMock,
        EntitiesBuilder $entitiesBuilderMock,
        ACL $aclMock,
        LegacyMembership $legacyMembershipMock,
        Experiments\Manager $experimentsManagerMock,
        SuggestedGroupsRecommendationsAlgorithm $groupRecsAlgo
    ) {
        $this->beConstructedWith($repositoryMock, $entitiesBuilderMock, $aclMock, $legacyMembershipMock, $experimentsManagerMock, $groupRecsAlgo);
        $this->repositoryMock = $repositoryMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->aclMock = $aclMock;
        $this->legacyMembershipMock = $legacyMembershipMock;
        $this->experimentsManagerMock = $experimentsManagerMock;
        $this->groupRecsAlgoMock = $groupRecsAlgo;

        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    /**
     * Legacy tests
     */

    public function it_should_return_a_membership_from_legacy(Group $groupMock, User $userMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);

        $groupMock->getGuid()
            ->willReturn(123);
        $userMock->getGuid()
            ->willReturn(456);

        $groupMock->isModerator($userMock)->willReturn(false);
        $groupMock->isOwner($userMock)->willReturn(false);

        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($userMock)->willReturn($this->legacyMembershipMock);

        $this->legacyMembershipMock->isMember($userMock)->willReturn(true);

        $membership = $this->getMembership($groupMock, $userMock);
        $membership->shouldReturnAnInstanceOf(Membership::class);

        $membership->groupGuid->shouldBe(123);
        $membership->userGuid->shouldBe(456);
        $membership->membershipLevel->shouldBe(GroupMembershipLevelEnum::MEMBER);
    }

    public function it_should_return_an_owner_membership_from_legacy(Group $groupMock, User $userMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);

        $groupMock->getGuid()
            ->willReturn(123);
        $userMock->getGuid()
            ->willReturn(456);

        $groupMock->isModerator($userMock)->willReturn(false);
        $groupMock->isOwner($userMock)->willReturn(true);

        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($userMock)->willReturn($this->legacyMembershipMock);

        $this->legacyMembershipMock->isMember($userMock)->willReturn(true);

        $membership = $this->getMembership($groupMock, $userMock);
        $membership->shouldReturnAnInstanceOf(Membership::class);

        $membership->groupGuid->shouldBe(123);
        $membership->userGuid->shouldBe(456);
        $membership->membershipLevel->shouldBe(GroupMembershipLevelEnum::OWNER);
    }

    public function it_should_return_a_moderator_membership_from_legacy(Group $groupMock, User $userMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);

        $groupMock->getGuid()
            ->willReturn(123);
        $userMock->getGuid()
            ->willReturn(456);

        $groupMock->isModerator($userMock)->willReturn(true);
        $groupMock->isOwner($userMock)->willReturn(false);

        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($userMock)->willReturn($this->legacyMembershipMock);

        $this->legacyMembershipMock->isMember($userMock)->willReturn(true);

        $membership = $this->getMembership($groupMock, $userMock);
        $membership->shouldReturnAnInstanceOf(Membership::class);

        $membership->groupGuid->shouldBe(123);
        $membership->userGuid->shouldBe(456);
        $membership->membershipLevel->shouldBe(GroupMembershipLevelEnum::MODERATOR);
    }

    public function it_should_return_count_from_legacy(Group $groupMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);

        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->getMembersCount()->willReturn(10);

        $this->getMembersCount($groupMock)->shouldBe(10);
    }

    public function it_should_return_members_from_legacy(Group $groupMock)
    {
        $refTime = time();

        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);

        $groupMock->getGuid()
            ->willReturn(123);

        $groupMock->getTimeCreated()->willReturn($refTime);

        $user1 = new User();
        $user1->set('guid', 456);
        $user2 = new User();
        $user2->set('guid', 789);

        $groupMock->isModerator(Argument::any())->willReturn(false);
        $groupMock->isOwner($user1)->willReturn(false);
        $groupMock->isOwner($user2)->willReturn(true);

        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->getMembers(Argument::any())->willReturn([
            $user1,
            $user2,
        ]);

        $this->getMembers($groupMock)->shouldYieldLike([
            (new Membership(
                groupGuid: 123,
                userGuid: 456,
                createdTimestamp: new DateTime("@$refTime"),
                membershipLevel: GroupMembershipLevelEnum::MEMBER,
            ))->setUser($user1),
            (new Membership(
                groupGuid: 123,
                userGuid: 789,
                createdTimestamp: new DateTime("@$refTime"),
                membershipLevel: GroupMembershipLevelEnum::OWNER,
            ))->setUser($user2)
        ]);
    }

    public function it_should_return_requests_from_legacy(Group $groupMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);

        $groupMock->getGuid()
            ->willReturn(123);

        $user1 = new User();
        $user1->set('guid', 456);
        $user2 = new User();
        $user2->set('guid', 789);

        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->getRequests(Argument::any())->willReturn([
            $user1,
            $user2,
        ]);

        $this->getRequests($groupMock)->shouldYieldLike([
            $user1, $user2
        ]);
    }

    public function it_should_return_groups_from_legacy(User $userMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);

        $userMock->getGuid()
            ->willReturn(123);

        $group1 = new Group();
        $group2 = new Group();

        $this->legacyMembershipMock->getGroupsByMember([ 'user_guid' => 123, 'limit' => 12, 'offset' => 0 ])
            ->willReturn([
                456,
                789,
            ]);

        $this->entitiesBuilderMock->single(456)->willReturn($group1);
        $this->entitiesBuilderMock->single(789)->willReturn($group2);

        $this->aclMock->read(Argument::any())->willReturn(true);

        $this->getGroups($userMock)->shouldYieldLike([
            $group1,
            $group2,
        ]);
    }

    public function it_should_return_group_guids_from_legacy(User $userMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(false);

        $userMock->getGuid()
            ->willReturn(123);

        $this->legacyMembershipMock->getGroupGuidsByMember([ 'user_guid' => 123, 'limit' => 500 ])
            ->willReturn([
                456,
                789,
            ]);

        $this->getGroupGuids($userMock)->shouldYieldLike([
            456,
            789,
        ]);
    }

    /**
     * Vitess (new) tests
     */
    public function it_should_return_a_membership(Group $groupMock, User $userMock, Membership $membershipMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(true);

        $groupMock->getGuid()->willReturn(123);
        $userMock->getGuid()->willReturn(456);

        $this->repositoryMock->get(123, 456)
            ->willReturn($membershipMock);

        $membership = $this->getMembership($groupMock, $userMock);
        $membership->shouldBe($membershipMock);
    }

    public function it_should_return_count(Group $groupMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(true);

        $groupMock->getGuid()->willReturn(123);

        $this->repositoryMock->getCount(123)
            ->willReturn(10);

        $this->getMembersCount($groupMock)->shouldBe(10);
    }

    public function it_should_return_members(Group $groupMock, User $userMock)
    {
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(true);

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

        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(true);

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
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(true);

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
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(true);

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
        $this->experimentsManagerMock->isOn('engine-2591-groups-memberships')->willReturn(true);

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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->join($userMock, [ 'force' => false, 'isOwner' => false, ])->willReturn(true);

        /**
         * Vitess
         */
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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->join($userMock, [ 'force' => false, 'isOwner' => false, ])->willReturn(true);

        /**
         * Vitess
         */
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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->leave($userMock)->willReturn(true);

        /**
         * Vitess
         */
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

        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($userMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->cancelRequest($userMock)->willReturn(true);

        /**
         * Vitess
         */
       
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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($actorMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->isAwaiting($userMock)->willReturn(true);
        $this->legacyMembershipMock->join($userMock, ['force' => true])->willReturn(true);

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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($actorMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->isAwaiting($userMock)->willReturn(true); // Want to test vitess flow, so allow
        $this->legacyMembershipMock->join($userMock, ['force' => true])->willReturn(true);

        /**
         * Vitess
         */
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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($actorMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->isAwaiting($userMock)->willReturn(true);
        $this->legacyMembershipMock->join($userMock, ['force' => true])->willReturn(true);

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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($actorMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->isAwaiting($userMock)->willReturn(false);
        $this->legacyMembershipMock->kick($userMock)->willReturn(true);

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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($actorMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->isAwaiting($userMock)->willReturn(false);
        $this->legacyMembershipMock->kick($userMock)->willReturn(true);

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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($actorMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->ban($userMock)->willReturn(true);

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
        /**
         * Legacy
         */
        $this->legacyMembershipMock->setGroup($groupMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->setActor($actorMock)->willReturn($this->legacyMembershipMock);
        $this->legacyMembershipMock->unban($userMock)->willReturn(true);

        /**
         * Vitess
         */
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
