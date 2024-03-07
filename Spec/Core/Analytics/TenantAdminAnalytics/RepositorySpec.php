<?php

namespace Spec\Minds\Core\Analytics\TenantAdminAnalytics;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsResolutionEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Repository;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Data\MySQL;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    protected Collaborator $mysqlClientMock;
    protected Collaborator $mysqlMasterMock;
    protected Collaborator $mysqlReplicaMock;

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

    public function it_should_return_buckets_with_day_resolution(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);
           
        $stmtMock->execute([
            'metric' => 'DAILY_ACTIVE_USERS',
            'fromTs' => '2024-01-28',
            'toTs' => '2024-02-28',
            'tenantId' => -1
        ]);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'grouped_date' => '2024-01-01',
                'value' => '20'
            ]
        ]);

        $response = $this->getBucketsByMetric(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, AnalyticsResolutionEnum::DAY, 1706400000, 1709078400);
        $response[0]->value->shouldBe(20);
        $response->shouldHaveCount(32);
    }
    
    public function it_should_return_buckets_with_month_resolution(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);
           
        $stmtMock->execute([
            'metric' => 'DAILY_ACTIVE_USERS',
            'fromTs' => '2024-01-28',
            'toTs' => '2024-02-28',
            'tenantId' => -1
        ]);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'grouped_date' => '2024-01-01',
                'value' => '20'
            ]
        ]);

        $response = $this->getBucketsByMetric(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, AnalyticsResolutionEnum::MONTH, 1706400000, 1709078400);
        $response[0]->value->shouldBe(20);
    }

    public function it_should_return_kpis(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);
           
        $this->mysqlClientMock->bindValuesToPreparedStatement($stmtMock, [
            "metrics" => ["DAILY_ACTIVE_USERS", "NEW_USERS"],
            'fromTs' => '2024-01-28',
            'toTs' => '2024-02-28',
            'tenantId' => -1
        ])->shouldBeCalled();

        $stmtMock->execute();

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'metric' => 'DAILY_ACTIVE_USERS',
                'value' => '20'
            ],
            [
                'metric' => 'NEW_USERS',
                'value' => '1'
            ]
        ]);
    
        $response =  $this->getKpis([
            AnalyticsMetricEnum::DAILY_ACTIVE_USERS,
            AnalyticsMetricEnum::NEW_USERS,
        ], 1706400000, 1709078400);

        $response[0]->metric->shouldBe(AnalyticsMetricEnum::DAILY_ACTIVE_USERS);
        $response[0]->value->shouldBe(20);
        $response[1]->metric->shouldBe(AnalyticsMetricEnum::NEW_USERS);
        $response[1]->value->shouldBe(1);
    }

    public function it_should_return_popular_activity_guids(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);
           
        $stmtMock->execute([
            'fromTs' => '2024-01-28',
            'toTs' => '2024-02-28',
            'tenantId' => -1
        ]);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'guid' => '123',
                'votes' => '20'
            ]
        ]);
            
        $response = $this->getPopularActivity(0, 1706400000, 1709078400);
        $response->shouldYieldLike([
            [
                'guid' => '123',
                'votes' => '20'
            ]
        ]);
    }


    public function it_should_return_popular_group_guids(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);
           
        $stmtMock->execute([
            'fromTs' => '2024-01-28',
            'toTs' => '2024-02-28',
            'tenantId' => -1
        ]);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'guid' => '123',
                'new_members' => '20'
            ]
        ]);
            
        $response = $this->getPopularGroups(0, 1706400000, 1709078400);
        $response->shouldYieldLike([
            [
                'guid' => '123',
                'new_members' => '20'
            ]
        ]);
    }


    public function it_should_return_popular_user_guids(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);
           
        $stmtMock->execute([
            'fromTs' => '2024-01-28',
            'toTs' => '2024-02-28',
            'tenantId' => -1
        ]);

        $stmtMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'guid' => '123',
                'subscribers' => '20'
            ]
        ]);
            
        $response = $this->getPopularUsers(0, 1706400000, 1709078400);
        $response->shouldYieldLike([
            [
                'guid' => '123',
                'subscribers' => '20'
            ]
        ]);
    }
}
