<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Controllers;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsResolutionEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsTableEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsChartType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsKpiType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsTableConnection;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowEdge;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowActivityNode;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart\AnalyticsChartBucketType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart\AnalyticsChartSegmentType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowGroupNode;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowUserNode;
use Minds\Core\Feeds\GraphQL\Types\ActivityNode;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Query;

class AdminAnalyticsController
{
    #[Query]
    public function getTenantAdminAnalyticsChart(
        AnalyticsMetricEnum $metric,
        AnalyticsResolutionEnum $resolution = null,
        int $fromUnixTs = null,
        int $toUnixTs = null
    ): AnalyticsChartType {
        $buckets = [];
        for ($i = 0; $i < 7; ++$i) {
            $ts = strtotime("midnight - $i days");
            $buckets[] = new AnalyticsChartBucketType(date('c', $ts), $ts * 1000, rand(1, 1000));
        }

        return new AnalyticsChartType(
            metric: $metric,
            segments: [
                new AnalyticsChartSegmentType(
                    '',
                    array_reverse($buckets)
                )
            ]
        );
    }

    /**
     * @param AnalyticsMetricEnum[] $metrics
     * @return AnalyticsKpiType[]
     */
    #[Query]
    public function getTenantAdminAnalyticsKpis(
        array $metrics,
        int $fromUnixTs = null,
        int $toUnixTs = null,
    ): array {
        $kpis = [];
        foreach ($metrics as $metric) {
            $kpis[] = new AnalyticsKpiType(
                metric: $metric,
                value: rand(1, 1000),
                previousPeriodValue: rand(1, 1000),
            );
        }
        return $kpis;
    }


    #[Query]
    public function getTenantAdminAnalyticsTable(
        AnalyticsTableEnum $table,
        int $fromUnixTs = null,
        int $toUnixTs = null,
    ): AnalyticsTableConnection {

        $edges = [];

        switch ($table) {
            case AnalyticsTableEnum::POPULAR_ACTIVITIES:
                $edges[] = new AnalyticsTableRowEdge(
                    node: new AnalyticsTableRowActivityNode(
                        activity: new ActivityNode(new Activity()),
                        views: 12,
                        engagements:1
                    )
                );
                break;
            case AnalyticsTableEnum::POPULAR_GROUPS:
                $edges[] = new AnalyticsTableRowEdge(
                    node: new AnalyticsTableRowGroupNode(
                        group: new GroupNode(new Group()),
                        newMembers: 12,
                        totalMembers: 100,
                        engagements:1
                    )
                );
                break;
            case AnalyticsTableEnum::POPULAR_USERS:
                $edges[] = new AnalyticsTableRowEdge(
                    node: new AnalyticsTableRowUserNode(
                        user: new UserNode(new User()),
                        newSubscribers: 12,
                        totalSubscribers: 100,
                        engagements:1
                    )
                );
                break;
        }

        $connection = new AnalyticsTableConnection(
            table: $table
        );

        $connection->setEdges($edges);
       
        $connection->setPageInfo(
            new PageInfo(
                hasNextPage: false,
                hasPreviousPage: false,
                startCursor: null,
                endCursor: null
            )
        );
        return $connection;
    }
}
