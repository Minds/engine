<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Controllers;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsTableEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Services\AdminAnalyticsFetchService;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsChartType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsKpiType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsTableConnection;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowEdge;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class AdminAnalyticsController
{
    public function __construct(
        private AdminAnalyticsFetchService $fetchService,
    ) {
        
    }

    /**
     * Returns data to be displayed in a chart. All metrics are supported.
     */
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getTenantAdminAnalyticsChart(
        AnalyticsMetricEnum $metric,
        int $fromUnixTs = null,
        int $toUnixTs = null,
        #[InjectUser()] ?User $loggedInUser = null,
    ): AnalyticsChartType {
        if (!$fromUnixTs) {
            $fromUnixTs = strtotime('midnight 30 days ago');
        }
        if (!$toUnixTs) {
            $toUnixTs = time();
        }
        return $this->fetchService->getChart($metric, $fromUnixTs, $toUnixTs);
    }

    /**
     * Returns multiple 'kpis' from a list of provided metrics.
     * @param AnalyticsMetricEnum[] $metrics
     * @return AnalyticsKpiType[]
     */
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getTenantAdminAnalyticsKpis(
        array $metrics,
        int $fromUnixTs = null,
        int $toUnixTs = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): array {
        if (!$fromUnixTs) {
            $fromUnixTs = strtotime('midnight 30 days ago');
        }
        if (!$toUnixTs) {
            $toUnixTs = time();
        }
        return $this->fetchService->getKpis($metrics, $fromUnixTs, $toUnixTs);
    }

    /**
     * Returns a paginated list of popular content
     */
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getTenantAdminAnalyticsTable(
        AnalyticsTableEnum $table,
        int $limit = 20,
        string $after = null,
        int $fromUnixTs = null,
        int $toUnixTs = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): AnalyticsTableConnection {
        if (!$fromUnixTs) {
            $fromUnixTs = strtotime('midnight 30 days ago');
        }

        if (!$toUnixTs) {
            $toUnixTs = time();
        }

        $hasMore = false;
        $startCursor = $after;

        $edges = [];

        switch ($table) {
            case AnalyticsTableEnum::POPULAR_ACTIVITIES:
                $nodes = $this->fetchService->getPopularActivityNodes(
                    fromUnixTs: $fromUnixTs,
                    toUnixTs: $toUnixTs,
                    limit: $limit,
                    loadAfter: $after,
                    hasMore: $hasMore
                );
                foreach ($nodes as $node) {
                    $edges[] = new AnalyticsTableRowEdge(
                        node: $node
                    );
                }
                break;
            case AnalyticsTableEnum::POPULAR_GROUPS:
                $nodes = $this->fetchService->getPopularGroupNodes(
                    fromUnixTs: $fromUnixTs,
                    toUnixTs: $toUnixTs,
                    limit: $limit,
                    loadAfter: $after,
                    hasMore: $hasMore
                );
                foreach ($nodes as $node) {
                    $edges[] = new AnalyticsTableRowEdge(
                        node: $node
                    );
                }
                break;
            case AnalyticsTableEnum::POPULAR_USERS:
                $nodes = $this->fetchService->getPopularUserNodes(
                    fromUnixTs: $fromUnixTs,
                    toUnixTs: $toUnixTs,
                    limit: $limit,
                    loadAfter: $after,
                    hasMore: $hasMore
                );
                foreach ($nodes as $node) {
                    $edges[] = new AnalyticsTableRowEdge(
                        node: $node
                    );
                }
                break;
        }

        $connection = new AnalyticsTableConnection(
            table: $table
        );

        $connection->setEdges($edges);
       
        $connection->setPageInfo(
            new PageInfo(
                hasNextPage: $hasMore,
                hasPreviousPage: false,
                startCursor: $startCursor,
                endCursor: $after
            )
        );
        return $connection;
    }
}
