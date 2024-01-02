<?php

namespace Spec\Minds\Core\Onboarding\V5;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingState;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingStepProgressState;
use Minds\Core\Onboarding\V5\Repository;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spec\Minds\Common\Traits\CommonMatchers;

class RepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    private $mysqlClientMock;
    private $mysqlMasterMock;
    private $mysqlReplicaMock;

    public function let(MySQLClient $mysqlClient, PDO $mysqlMasterMock, PDO $mysqlReplicaMock)
    {
        $this->beConstructedWith($mysqlClient, Di::_()->get(Config::class), Di::_()->get('Logger'));
        $this->mysqlClientMock = $mysqlClient;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->wilLReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_onboarding_state_from_the_database(
        PDOStatement $pdoStatement
    ) {
        $userGuid = '123';

        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatement);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatement);
        $this->mysqlClientMock->bindValuesToPreparedStatement(Argument::any(), [
            'user_guid' => $userGuid,
        ])->shouldBeCalled();

        $pdoStatement->execute();

        $pdoStatement->rowCount()->willReturn(1);
        $pdoStatement->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn(
                [
                    'started_at' => date('c'),
                    'completed_at' => date('c'),
                ]
            );

        $this->getOnboardingState($userGuid)
            ->shouldBeAnInstanceOf(OnboardingState::class);
    }

    public function it_should_return_null_when_no_onboarding_state_is_found_in_the_database(
        PDOStatement $pdoStatement
    ) {
        $userGuid = '123';

        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatement);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatement);
        $this->mysqlClientMock->bindValuesToPreparedStatement(Argument::any(), [
            'user_guid' => $userGuid,
        ])->shouldBeCalled();

        $pdoStatement->execute();

        $pdoStatement->rowCount()->willReturn(0);

        $this->getOnboardingState($userGuid)
            ->shouldBe(null);
    }

    public function it_should_set_onboarding_state(
        PDOStatement $pdoStatement
    ) {
        $userGuid = '123';

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatement);
        $this->mysqlClientMock->bindValuesToPreparedStatement(
            Argument::any(),
            Argument::that(function ($arg) {
                return $arg['user_guid'] === 123 &&
                    $arg['completed_at'];
            })
        )->shouldBeCalled();

        $pdoStatement->execute()->shouldBeCalled()->willReturn(true);

        $this->setOnboardingState($userGuid, true)
            ->shouldBeAnInstanceOf(OnboardingState::class);
    }

    public function it_should_get_onboarding_step_progress(
        PDOStatement $pdoStatement
    ) {
        $userGuid = '123';

        $this->mysqlReplicaMock->query(Argument::type('string'))->willReturn($pdoStatement);
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($pdoStatement);
        $this->mysqlClientMock->bindValuesToPreparedStatement(Argument::any(), [
            'user_guid' => $userGuid,
        ])->shouldBeCalled();

        $pdoStatement->execute()->shouldBeCalled()->willReturn(true);

        $pdoStatement->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                [
                    'user_guid' => '123',
                    'step_key' => 'step_key1',
                    'step_type' => 'step_type1',
                    'completed_at' => date('c'),
                ],
                [
                    'user_guid' => '123',
                    'step_key' => 'step_key2',
                    'step_type' => 'step_type2',
                    'completed_at' => date('c'),
                ]
            ]);

        $this->getOnboardingStepProgress($userGuid)
            ->shouldYieldAnInstanceOf(OnboardingStepProgressState::class);
    }

    public function it_should_set_onboarding_completion_state(
        PDOStatement $pdoStatement
    ) {
        $userGuid = '123';

        $this->mysqlMasterMock->prepare(Argument::type('string'))->shouldBeCalled()->willReturn($pdoStatement);
        $this->mysqlClientMock->bindValuesToPreparedStatement(
            Argument::any(),
            Argument::that(function ($arg) {
                return $arg['user_guid'] === 123 &&
                    $arg['step_key'] === 'stepKey' &&
                    $arg['step_type'] === 'stepType' &&
                    $arg['completed_at'];
            })
        )->shouldBeCalled();

        $pdoStatement->execute()->shouldBeCalled()->willReturn(true);

        $this->completeOnboardingStep($userGuid, 'stepKey', 'stepType')
            ->shouldBeAnInstanceOf(OnboardingStepProgressState::class);
    }
}
