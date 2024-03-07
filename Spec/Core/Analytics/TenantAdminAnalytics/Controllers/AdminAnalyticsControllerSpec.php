<?php

namespace Spec\Minds\Core\Analytics\TenantAdminAnalytics\Controllers;

use Minds\Core\Analytics\TenantAdminAnalytics\Controllers\AdminAnalyticsController;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsTableEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Services\AdminAnalyticsFetchService;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsChartType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsKpiType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsTableConnection;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowActivityNode;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowGroupNode;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowUserNode;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class AdminAnalyticsControllerSpec extends ObjectBehavior
{
    private Collaborator $fetchServiceMock;

    public function let(AdminAnalyticsFetchService $fetchServiceMock)
    {
        $this->beConstructedWith($fetchServiceMock);
        $this->fetchServiceMock = $fetchServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(AdminAnalyticsController::class);
    }

    public function it_should_return_a_chart_with_provided_timestamps(AnalyticsChartType $chartMock)
    {
        $this->fetchServiceMock->getChart(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, 1706400000, 1709078400)
            ->willReturn($chartMock);

        $response = $this->getTenantAdminAnalyticsChart(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, 1706400000, 1709078400);
        $response->shouldBeAnInstanceOf(AnalyticsChartType::class);
    }

    public function it_should_return_a_chart_with_default_timestamps(AnalyticsChartType $chartMock)
    {
        $this->fetchServiceMock->getChart(AnalyticsMetricEnum::DAILY_ACTIVE_USERS, strtotime('midnight 30 days ago'), time())
            ->willReturn($chartMock);

        $response = $this->getTenantAdminAnalyticsChart(AnalyticsMetricEnum::DAILY_ACTIVE_USERS);
        $response->shouldBeAnInstanceOf(AnalyticsChartType::class);
    }

    public function it_should_return_multiple_kpis_with_provided_timestamps(AnalyticsKpiType $kpiMock)
    {
        $this->fetchServiceMock->getKpis([
            AnalyticsMetricEnum::DAILY_ACTIVE_USERS,
            AnalyticsMetricEnum::MEAN_SESSION_SECS
        ], 1706400000, 1709078400)
            ->willReturn([
                $kpiMock,
                $kpiMock,
            ]);
        
        $response = $this->getTenantAdminAnalyticsKpis([
            AnalyticsMetricEnum::DAILY_ACTIVE_USERS,
            AnalyticsMetricEnum::MEAN_SESSION_SECS
        ], 1706400000, 1709078400);
        $response[0]->shouldBeAnInstanceOf(AnalyticsKpiType::class);
        $response[1]->shouldBeAnInstanceOf(AnalyticsKpiType::class);
    }

    public function it_should_return_multiple_kpis_with_default_timestamps(AnalyticsKpiType $kpiMock)
    {
        $this->fetchServiceMock->getKpis([
            AnalyticsMetricEnum::DAILY_ACTIVE_USERS,
            AnalyticsMetricEnum::MEAN_SESSION_SECS
        ], strtotime('midnight 30 days ago'), time())
            ->willReturn([
                $kpiMock,
                $kpiMock,
            ]);
        
        $response = $this->getTenantAdminAnalyticsKpis([
            AnalyticsMetricEnum::DAILY_ACTIVE_USERS,
            AnalyticsMetricEnum::MEAN_SESSION_SECS
        ]);
        $response[0]->shouldBeAnInstanceOf(AnalyticsKpiType::class);
        $response[1]->shouldBeAnInstanceOf(AnalyticsKpiType::class);
    }

    public function it_should_return_popular_posts_with_provided_timestamps(AnalyticsTableRowActivityNode $mock)
    {
        $this->fetchServiceMock->getPopularActivityNodes(
            1706400000,
            1709078400,
            20,
            null,
            false
        )
        ->willReturn([
            $mock,
            $mock
        ]);

        $connection = $this->getTenantAdminAnalyticsTable(AnalyticsTableEnum::POPULAR_ACTIVITIES, 20, null, 1706400000, 1709078400);
        $connection->shouldBeAnInstanceOf(AnalyticsTableConnection::class);

        $edges = $connection->getEdges();
        $edges[0]->getNode()->shouldBeAnInstanceOf(AnalyticsTableRowActivityNode::class);
        $edges[1]->getNode()->shouldBeAnInstanceOf(AnalyticsTableRowActivityNode::class);
    }

    public function it_should_return_popular_groups_with_provided_timestamps(AnalyticsTableRowGroupNode $mock)
    {
        $this->fetchServiceMock->getPopularGroupNodes(
            1706400000,
            1709078400,
            20,
            null,
            false
        )
        ->willReturn([
            $mock,
            $mock
        ]);

        $connection = $this->getTenantAdminAnalyticsTable(AnalyticsTableEnum::POPULAR_GROUPS, 20, null, 1706400000, 1709078400);
        $connection->shouldBeAnInstanceOf(AnalyticsTableConnection::class);

        $edges = $connection->getEdges();
        $edges[0]->getNode()->shouldBeAnInstanceOf(AnalyticsTableRowGroupNode::class);
        $edges[1]->getNode()->shouldBeAnInstanceOf(AnalyticsTableRowGroupNode::class);
    }

    public function it_should_return_popular_users_with_provided_timestamps(AnalyticsTableRowUserNode $mock)
    {
        $this->fetchServiceMock->getPopularUserNodes(
            1706400000,
            1709078400,
            20,
            null,
            false
        )
        ->willReturn([
            $mock,
            $mock
        ]);

        $connection = $this->getTenantAdminAnalyticsTable(AnalyticsTableEnum::POPULAR_USERS, 20, null, 1706400000, 1709078400);
        $connection->shouldBeAnInstanceOf(AnalyticsTableConnection::class);

        $edges = $connection->getEdges();
        $edges[0]->getNode()->shouldBeAnInstanceOf(AnalyticsTableRowUserNode::class);
        $edges[1]->getNode()->shouldBeAnInstanceOf(AnalyticsTableRowUserNode::class);
    }
}
