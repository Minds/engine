<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;

class ActiveUsersMetric extends MetricAbstract
{
    /** @var string */
    protected $id = 'active_users';

    /** @var string */
    protected $label = 'active users';

    /** @var array */
    protected $permissions = [ 'admin' ];

    /** @var MetricValues */
    protected $values;

    public function __construct($entityCentricManager = null)
    {
        $this->entityCentricManager = $entityCentricManager ?? Di::_()->get('Analytics\EntityCentric\Manager');
    }

    /**
     * Build the metrics
     * @return self
     */
    public function build(): self
    {
        $timespan = $this->timespansCollection->getSelected();
        $filters = $this->filtersCollection->getSelected();

        $previousTs = strtotime('-1 ' . $timespan->getAggInterval(), $timespan->getFromTsMs() / 1000) * 1000;
        $currentTs = $timespan->getFromTsMs();

        $must = [];

        // Range must be from previous period
        $must[]['range'] = [
            '@timestamp' => [
                'gte' => $previousTs,
            ],
        ];

        // Use our global metrics
        $must[]['term'] = [
            'entity_urn' => 'urn:metric:global'
        ];

        // Field name to use for the aggregation
        $aggField = "active::total";
        // The aggregation type, this differs by resolution
        $aggType = "sum";
        // The resolution to use
        $resolution = 'day';
        switch ($timespan->getId()) {
            case 'today':
                $resolution = 'day';
                $aggType = "sum";
                break;
            case 'mtd':
                $resolution = 'month';
                $aggType = "max";
                break;
            case 'ytd':
                $resolution = 'month';
                $aggType = "avg";
                break;
        }

        // Specify the resolution to avoid duplicates
        $must[] = [
            'term' => [
                'resolution' => $resolution,
            ],
        ];

        $response = $this->entityCentricManager->getAggregateByQuery([
            'query' => [
                'bool' => [
                    'must' => $must,
                ],
            ],
            'size' => 0,
            'aggs' => [
                '1' => [
                    'date_histogram' => [
                        'field' => '@timestamp',
                        'interval' =>  $timespan->getAggInterval(),
                        'min_doc_count' =>  1,
                    ],
                    'aggs' => [
                        '2' => [
                            $aggType => [
                                'field' => $aggField,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->values = new MetricsValues();
        $this->values->setCurrent($response[0]['1']['2']['value'])
            ->setPrevious($response[1]['1']['2']['value']);
        return $this;
    }
}
