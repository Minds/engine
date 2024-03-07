<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics;

use Exception;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsResolutionEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsTableEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\AnalyticsKpiType;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart\AnalyticsChartBucketType;
use Minds\Core\Analytics\Timestamps;
use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository extends AbstractRepository
{
    const TABLE_NAME = 'minds_tenant_daily_metrics';
    const DATE_FORMAT = 'Y-m-d';

    /**
     * Returns timeseries data for the requested metrics
     */
    public function getBucketsByMetric(
        AnalyticsMetricEnum $metric,
        AnalyticsResolutionEnum $resolution,
        int $fromUnixTs,
        int $toUnixTs
    ) {
        $date = match($resolution) {
            AnalyticsResolutionEnum::DAY => 'date',
            AnalyticsResolutionEnum::MONTH => "DATE_FORMAT(date, '%Y-%m-01')",
            default => throw new Exception("Invalid timeframe")
        };

        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'grouped_date' => new RawExp($date),
                'value' => new RawExp($this->getAggType($metric) . '(value)'),
            ])
            ->where('metric', Operator::EQ, new RawExp(':metric'))
            ->where('date', Operator::GTE, new RawExp(':fromTs'))
            ->where('date', Operator::LT, new RawExp(':toTs'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenantId'))
            ->groupBy('grouped_date')
            ->orderBy('grouped_date ASC');

        $stmt = $query->prepare();

        $values = [
            'metric' => $metric->name,
            'fromTs' => date(self::DATE_FORMAT, $fromUnixTs),
            'toTs' => date(self::DATE_FORMAT, $toUnixTs),
            'tenantId' => $this->getTenantId(),
        ];

        $stmt->execute($values);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // We need to have empty values for each day with no records
        $fromDate = date_create(date('c', $fromUnixTs));
        $toDate = date_create(date('c', $toUnixTs));
        $interval = date_diff($fromDate, $toDate);

        $timestamps = Timestamps::span(
            span: $resolution === AnalyticsResolutionEnum::DAY ? $interval->format('%a') :  $interval->m  + ($interval->y * 12),
            unit: $resolution === AnalyticsResolutionEnum::DAY ? 'day' : 'month',
            ts: $toUnixTs
        );

        $tsToRow = [];
        foreach ($rows as $row) {
            $tsToRow[strtotime($row['grouped_date'])] = $row;
        }
        foreach ($timestamps as $ts) {
            if (!isset($tsToRow[$ts])) {
                $tsToRow[$ts] = [
                    'grouped_date' => date(self::DATE_FORMAT, $ts),
                    'value' =>  null,
                ];
            }
        }
        ksort($tsToRow);

        return array_values(array_map(function ($row) {
            return new AnalyticsChartBucketType($row['grouped_date'], $row['grouped_date'], (int) $row['value']);
        }, $tsToRow));
    }

    /**
     * Returns data for the requested metrics
     * @param AnalyticsMetricEnum[] $metrics
     * @return AnalyticsKpiType[]
     */
    public function getKpis(array $metrics, int $fromUnixTs, int $toUnixTs): array
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from(self::TABLE_NAME)
            ->columns([
                'metric',
                'value_sum' => new RawExp('SUM(value)'),
                'value_avg' => new RawExp('AVG(value)'),
                'value_max' => new RawExp('MAX(value)'),
            ])
            ->whereWithNamedParameters(
                leftField: 'metric',
                operator: Operator::IN,
                parameterName: 'metrics',
                totalParameters: count($metrics)
            )
            ->where('date', Operator::GTE, new RawExp(':fromTs'))
            ->where('date', Operator::LT, new RawExp(':toTs'))
            ->where('tenant_id', Operator::EQ, new RawExp(':tenantId'))
            ->groupBy('metric');

        $stmt = $query->prepare();

        $values = [
            'metrics' => array_map(function ($metric) { return $metric->name; }, $metrics),
            'fromTs' => date(self::DATE_FORMAT, $fromUnixTs),
            'toTs' => date(self::DATE_FORMAT, $toUnixTs),
            'tenantId' => $this->getTenantId(),
        ];

        $this->mysqlHandler->bindValuesToPreparedStatement($stmt, $values);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            $metric = constant(AnalyticsMetricEnum::class . "::{$row['metric']}");
            return new AnalyticsKpiType(
                metric: $metric,
                value: (int) $row['value_' . strtolower($this->getAggType($metric))],
                previousPeriodValue: 0,
            );
        }, $rows);
    }

    /**
     * Returns an ordered list of popular activity guids and their votes
     * @return iterable<array>
     */
    public function getPopularActivity(
        int $offset,
        int $fromUnixTs,
        int $toUnixTs,
    ): iterable {
        $query = $this->mysqlClientReaderHandler->select()
            ->from('minds_entities_activity')
            ->leftJoin('minds_votes', 'minds_entities_activity.guid', Operator::EQ, 'minds_votes.entity_guid')
            ->columns([
                'guid',
                'votes' => new RawExp('count(minds_votes.entity_guid)'),
            ])
            ->where('minds_votes.created_timestamp', Operator::GTE, new RawExp(':fromTs'))
            ->where('minds_votes.created_timestamp', Operator::LT, new RawExp(':toTs'))
            ->where('minds_entities_activity.tenant_id', Operator::EQ, new RawExp(':tenantId'))
            ->groupBy('guid')
            ->limit(10000)
            ->offset($offset)
            ->orderBy('votes desc');

        $stmt = $query->prepare();

        $values = [
            'fromTs' => date(self::DATE_FORMAT, $fromUnixTs),
            'toTs' => date(self::DATE_FORMAT, $toUnixTs),
            'tenantId' => $this->getTenantId(),
        ];

        $stmt->execute($values);
    
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield $row;
        }
    }

    /**
     * Returns an ordered list of popular groups guids and their members
     * @return iterable<array>
     */
    public function getPopularGroups(
        int $offset,
        int $fromUnixTs,
        int $toUnixTs,
    ): iterable {
        $query = $this->mysqlClientReaderHandler->select()
            ->from('minds_entities_group')
            ->leftJoin('minds_group_membership', 'minds_entities_group.guid', Operator::EQ, 'minds_group_membership.group_guid')
            ->columns([
                'guid',
                'new_members' => new RawExp('count(minds_group_membership.group_guid)'),
            ])
            ->where('minds_group_membership.created_timestamp', Operator::GTE, new RawExp(':fromTs'))
            ->where('minds_group_membership.created_timestamp', Operator::LT, new RawExp(':toTs'))
            ->where('minds_entities_group.tenant_id', Operator::EQ, new RawExp(':tenantId'))
            ->groupBy('guid')
            ->limit(10000)
            ->offset($offset)
            ->orderBy('new_members desc');

        $stmt = $query->prepare();

        $values = [
            'fromTs' => date(self::DATE_FORMAT, $fromUnixTs),
            'toTs' => date(self::DATE_FORMAT, $toUnixTs),
            'tenantId' => $this->getTenantId(),
        ];

        $stmt->execute($values);
    
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield $row;
        }
    }

    /**
     * Returns an ordered list of popular user guids and their subscribers
     * @return iterable<array>
     */
    public function getPopularUsers(
        int $offset,
        int $fromUnixTs,
        int $toUnixTs,
    ): iterable {
        $query = $this->mysqlClientReaderHandler->select()
            ->from('minds_entities_user')
            ->leftJoin('friends', 'minds_entities_user.guid', Operator::EQ, 'friends.friend_guid')
            ->columns([
                'guid',
                'subscribers' => new RawExp('count(friends.user_guid)'),
            ])
            ->where('friends.timestamp', Operator::GTE, new RawExp(':fromTs'))
            ->where('friends.timestamp', Operator::LT, new RawExp(':toTs'))
            ->where('minds_entities_user.tenant_id', Operator::EQ, new RawExp(':tenantId'))
            ->groupBy('guid')
            ->limit(10000)
            ->offset($offset)
            ->orderBy('subscribers desc');

        $stmt = $query->prepare();

        $values = [
            'fromTs' => date(self::DATE_FORMAT, $fromUnixTs),
            'toTs' => date(self::DATE_FORMAT, $toUnixTs),
            'tenantId' => $this->getTenantId(),
        ];

        $stmt->execute($values);
    
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield $row;
        }
    }

    private function getTenantId(): int
    {
        return  $this->config->get('tenant_id') ?: -1;
    }

    /**
     * Return the correct aggregation method to use
     */
    private function getAggType(AnalyticsMetricEnum $metric): string
    {
        return  match($metric) {
            AnalyticsMetricEnum::DAILY_ACTIVE_USERS => 'AVG',
            AnalyticsMetricEnum::TOTAL_USERS => 'MAX',
            AnalyticsMetricEnum::TOTAL_SITE_MEMBERSHIP_SUBSCRIPTIONS => 'MAX',
            AnalyticsMetricEnum::NEW_USERS => 'SUM',
            AnalyticsMetricEnum::VISITORS => 'AVG',
            default => 'SUM',
        };
    }
}
