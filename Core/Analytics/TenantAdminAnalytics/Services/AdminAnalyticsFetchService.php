<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Services;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsResolutionEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Repository;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsChartType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsKpiType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart\AnalyticsChartSegmentType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowActivityNode;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowGroupNode;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowUserNode;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Votes\Manager as VotesManager;
use Minds\Core\Feeds\GraphQL\Types\ActivityNode;
use Minds\Core\Feeds\GraphQL\Types\UserNode;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Entities\Activity;
use Minds\Entities\Group;
use Minds\Entities\User;

class AdminAnalyticsFetchService
{
    public function __construct(
        private Repository $repository,
        private EntitiesBuilder $entitiesBuilder,
        private VotesManager $votesManager,
    ) {
        
    }

    /**
     * Returns chart data for a given metric
     */
    public function getChart(
        AnalyticsMetricEnum $metric,
        int $fromUnixTs,
        int $toUnixTs
    ): AnalyticsChartType {
        // Use daily resolution if less than 30 days ago
        $resolution = $fromUnixTs >= strtotime('midnight 30 days ago') ? AnalyticsResolutionEnum::DAY : AnalyticsResolutionEnum::MONTH;

        $buckets = $this->repository->getBucketsByMetric($metric, $resolution, $fromUnixTs, $toUnixTs);

        return new AnalyticsChartType(
            metric: $metric,
            segments: [
                new AnalyticsChartSegmentType(
                    '',
                    $buckets
                )
            ]
        );
    }

    /**
     * Returns the KPIs for metrics requested
     * @param AnalyticsMetricEnum[] $metrics
     * @return AnalyticsKpiType[]
     */
    public function getKpis(
        array $metrics,
        int $fromUnixTs,
        int $toUnixTs
    ): array {
        return $this->repository->getKpis($metrics, $fromUnixTs, $toUnixTs);
    }

    /**
     * Returns popular activities and their metrics
     */
    public function getPopularActivityNodes(
        int $fromUnixTs,
        int $toUnixTs,
        int $limit = 20,
        string &$loadAfter = null,
        bool &$hasMore = false,
    ) {
        $offset = 0;

        if ($loadAfter) {
            $offset = base64_decode($loadAfter, true);
        }
    
        $rows = $this->repository->getPopularActivity(
            offset: $offset,
            fromUnixTs: $fromUnixTs,
            toUnixTs: $toUnixTs,
        );

        $nodes = [];

        foreach ($rows as $row) {
            if (count($nodes) === $limit) {
                $hasMore = true;
                $loadAfter = base64_encode($offset + $limit);
                break;
            }

            $activity = $this->entitiesBuilder->single($row['guid']);
            if (!$activity instanceof Activity) {
                continue;
            }

            $nodes[] = new AnalyticsTableRowActivityNode(
                activity: new ActivityNode($activity, null, $this->votesManager, $this->entitiesBuilder),
                views: $activity->getImpressions(), // These are all time views
                engagements: (int) $row['votes'],
            );
        }

        return $nodes;
    }

    /**
     * Returns popular groups and their metrics
     */
    public function getPopularGroupNodes(
        int $fromUnixTs,
        int $toUnixTs,
        int $limit = 20,
        string &$loadAfter = null,
        bool &$hasMore = false,
    ) {
        $limit = 12;
        $offset = 0;

        if ($loadAfter) {
            $offset = base64_decode($loadAfter, true);
        }
    
        $rows = $this->repository->getPopularGroups(
            offset: $offset,
            fromUnixTs: $fromUnixTs,
            toUnixTs: $toUnixTs,
        );

        $nodes = [];

        foreach ($rows as $row) {
            if (count($nodes) === $limit) {
                $hasMore = true;
                $loadAfter = base64_encode($offset + $limit);
                break;
            }

            $group = $this->entitiesBuilder->single($row['guid']);
            if (!$group instanceof Group) {
                continue;
            }

            $nodes[] = new AnalyticsTableRowGroupNode(
                group: new GroupNode($group),
                newMembers: (int) $row['new_members'],
                // totalMembers: 0,
                // engagements: 0,
            );
        }

        return $nodes;
    }

    /**
     * Returns popular users and their metrics
     */
    public function getPopularUserNodes(
        int $fromUnixTs,
        int $toUnixTs,
        int $limit = 20,
        string &$loadAfter = null,
        bool &$hasMore = false,
    ) {
        $limit = 12;
        $offset = 0;

        if ($loadAfter) {
            $offset = base64_decode($loadAfter, true);
        }
    
        $rows = $this->repository->getPopularUsers(
            offset: $offset,
            fromUnixTs: $fromUnixTs,
            toUnixTs: $toUnixTs,
        );

        $nodes = [];

        foreach ($rows as $row) {
            if (count($nodes) === $limit) {
                $hasMore = true;
                $loadAfter = base64_encode($offset + $limit);
                break;
            }

            $user = $this->entitiesBuilder->single($row['guid']);
            if (!$user instanceof User) {
                continue;
            }

            $nodes[] = new AnalyticsTableRowUserNode(
                user: new UserNode($user),
                newSubscribers: (int) $row['subscribers'],
                totalSubscribers: $user->getSubscribersCount(),
                // engagements: 0,
            );
        }

        return $nodes;
    }
}
