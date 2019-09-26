<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Data\Elasticsearch;

class ActiveUsersMetric extends AbstractMetric
{
    /** @var string */
    protected $id = 'active_users';

    /** @var string */
    protected $label = 'active users';

    /** @var array */
    protected $permissions = [ 'admin' ];

    public function __construct($es = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
    }

    /**
     * Build the metric summary
     * @return self
     */
    public function buildSummary(): self
    {
        $timespan = $this->timespansCollection->getSelected();
        $filters = $this->filtersCollection->getSelected();

        $comparisonTsMs = strtotime("-{$timespan->getComparisonInterval()} days", $timespan->getFromTsMs() / 1000) * 1000;
        $currentTsMs = $timespan->getFromTsMs();

        $must = [];

        // Range must be from previous period
        $must[]['range'] = [
            '@timestamp' => [
                'gte' => $comparisonTsMs,
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

        $query = [
            'index' => 'minds-entitycentric-*',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                    ],
                ],
                'aggs' => [
                    '1' => [
                        'date_histogram' => [
                            'field' => '@timestamp',
                            'fixed_interval' =>  $timespan->getComparisonInterval(),
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
            ],
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $response = $this->es->request($prepared);

        $this->values = new MetricSummary();
        $this->values->setValue($response['aggregations']['1']['buckets'][1]['2']['value'])
            ->setComparisonValue($response['aggregations']['1']['buckets'][0]['2']['value'])
            ->setComparisonInterval($timespan->getComparisonInterval());
        return $this;
    }

    /**
     * Build a visualisation for the metric
     * @return self
     */
    public function buildVisualisation(): self
    {
        $timespan = $this->timespansCollection->getSelected();
        $xValues = [];
        $yValues = [];

        $must = [];

        // Range must be from previous period
        $must[]['range'] = [
            '@timestamp' => [
                'gte' => $timespan->getFromTsMs(),
            ],
        ];

        // Use our global metrics
        $must[]['term'] = [
            'entity_urn' => 'urn:metric:global'
        ];

        // Specify the resolution to avoid duplicates
        $must[] = [
            'term' => [
                'resolution' => $timespan->getInterval(),
            ],
        ];

        // Do the query
        $query = [
            'index' => 'minds-entitycentric-*',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                    ],
                ],
                'aggs' => [
                    '1' => [
                        'date_histogram' => [
                            'field' => '@timestamp',
                            'interval' =>  $timespan->getInterval(),
                            'min_doc_count' =>  1,
                        ],
                        'aggs' => [
                            '2' => [
                                'sum' => [
                                    'field' => 'active::total',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $response = $this->es->request($prepared);

        foreach ($response['aggregations']['1']['buckets'] as $bucket) {
            $date = date(Visualisations\ChartVisualisation::DATE_FORMAT, $bucket['key'] / 1000);

            $xValues[] = $date;
            $yValues[] = $bucket['2']['value'];
        }

        $this->visualisation = (new Visualisations\ChartVisualisation())
            ->setXValues($xValues)
            ->setYValues($yValues)
            ->setXLabel('Date')
            ->setYLabel('Count');

        return $this;
    }
}
