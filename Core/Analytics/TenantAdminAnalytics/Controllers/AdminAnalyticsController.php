<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Controllers;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsResolutionEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsChartType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsKpiType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart\AnalyticsChartBucketType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart\AnalyticsChartSegmentType;
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
}
