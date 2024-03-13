<?php

namespace Spec\Minds\Core\Groups\V2\Membership;

use DateTime;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Groups\V2\Membership\Repository;
use Minds\Core\Log\Logger;
use Minds\Core\Data\MySQL;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Membership;
use Minds\Exceptions\NotFoundException;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    protected $mysqlClientMock;

    protected $mysqlMasterMock;

    protected $mysqlReplicaMock;

    public function let(
        MySQL\Client $mysqlClientMock,
        Logger $loggerMock,
        PsrWrapper $cacheMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
    ) {
        $this->beConstructedWith($mysqlClientMock, Di::_()->get(Config::class), $loggerMock, $cacheMock);

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->shouldBeCalled()
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->shouldBeCalled()
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_membership(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->quote("123")->willReturn("123");
        $this->mysqlReplicaMock->quote("456")->willReturn("456");

        $this->mysqlReplicaMock->query(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->rowCount()->willReturn(1);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'group_guid' => 123,
                    'user_guid' => 456,
                    'created_timestamp' => date('c'),
                    'membership_level' => 1,
                ]
            ]);

        $membership = $this->get(123, 456);
        $membership->shouldReturnAnInstanceOf(Membership::class);

        $membership->groupGuid->shouldBe(123);
        $membership->userGuid->shouldBe(456);
        $membership->membershipLevel->shouldBe(GroupMembershipLevelEnum::MEMBER);
    }

    public function it_should_throw_404_if_no_membership(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->quote("123")->willReturn("123");
        $this->mysqlReplicaMock->quote("456")->willReturn("456");
        $this->mysqlReplicaMock->quote("1")->willReturn("1");

        $this->mysqlReplicaMock->query(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->rowCount()->willReturn(0);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldNotBeCalled();

        $this->shouldThrow(NotFoundException::class)->duringGet(123, 456);
    }

    public function it_should_return_a_list_of_memberships(PDOStatement $pdoStatementMock)
    {
        $refTime = time();
    
        $this->mysqlReplicaMock->quote("123")->willReturn("123");
        $this->mysqlReplicaMock->quote("456")->willReturn("456");
        $this->mysqlReplicaMock->quote("1")->willReturn("1");

        $this->mysqlReplicaMock->query(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'group_guid' => 123,
                    'user_guid' => 456,
                    'created_timestamp' => date('c', $refTime),
                    'membership_level' => 1,
                ],
                [
                    'group_guid' => 123,
                    'user_guid' => 789,
                    'created_timestamp' => date('c', $refTime),
                    'membership_level' => 2,
                ]
            ]);

        $memberships = $this->getList(groupGuid: 123);
        $memberships->shouldYieldLike([
            new Membership(
                groupGuid: 123,
                userGuid: 456,
                createdTimestamp: new DateTime("@$refTime"),
                membershipLevel: GroupMembershipLevelEnum::MEMBER,
            ),
            new Membership(
                groupGuid: 123,
                userGuid: 789,
                createdTimestamp: new DateTime("@$refTime"),
                membershipLevel: GroupMembershipLevelEnum::MODERATOR,
            ),
        ]);
    }

    public function it_should_return_valid_member_count(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->quote("123")->willReturn("123");
        $this->mysqlReplicaMock->quote("1")->willReturn("1");

        $this->mysqlReplicaMock->query(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'count' => 10
                ]
            ]);

        $this->getCount(123)->shouldBe(10);
    }

    public function it_should_return_focused_member_count(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->quote("123")->willReturn("123");
        $this->mysqlReplicaMock->quote("0")->willReturn("0");

        $this->mysqlReplicaMock->query(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'count' => 2
                ]
            ]);

        $this->getCount(123, membershipLevel: GroupMembershipLevelEnum::REQUESTED)->shouldBe(2);
    }

    public function it_should_add_membership(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlMasterMock->quote("123")->willReturn("123");
        $this->mysqlMasterMock->quote("456")->willReturn("456");
        $this->mysqlMasterMock->quote(date('c', $refTime))->willReturn(date('c', $refTime));
        $this->mysqlMasterMock->quote("1")->willReturn("1");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->willReturn(true);

        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime("@$refTime"),
            membershipLevel: GroupMembershipLevelEnum::MEMBER,
        );

        $this->add($membership)->shouldBe(true);
    }

    public function it_should_remove_membership(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlMasterMock->quote("123")->willReturn("123");
        $this->mysqlMasterMock->quote("456")->willReturn("456");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->willReturn(true);

        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime("@$refTime"),
            membershipLevel: GroupMembershipLevelEnum::MEMBER,
        );

        $this->delete($membership)->shouldBe(true);
    }

    public function it_should_update_membership_level(PDOStatement $pdoStatementMock)
    {
        $refTime = time();

        $this->mysqlMasterMock->quote("123")->willReturn("123");
        $this->mysqlMasterMock->quote("456")->willReturn("456");
        $this->mysqlMasterMock->quote("2")->willReturn("2");
        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");

        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()->willReturn(true);

        $membership = new Membership(
            groupGuid: 123,
            userGuid: 456,
            createdTimestamp: new DateTime("@$refTime"),
            membershipLevel: GroupMembershipLevelEnum::MODERATOR,
        );

        $this->updateMembershipLevel($membership)->shouldBe(true);
    }
}
