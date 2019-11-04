<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Engagement;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;

abstract class AbstractEngagementMetric extends AbstractMetric
{
    /** @var Elasticsearch\Client */
    private $es;

    /** @var string */
    protected $id = '';

    /** @var string */
    protected $label = '';

    /** @var string */
    protected $description = '';

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    /** @var string */
    protected $unit = 'number';

    /** @var string */
    protected $aggField = '';

    public function __construct($es = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
    }

    /**
     * Build the metrics
     * @return self
     */
    public function buildSummary(): self
    {
        $timespan = $this->timespansCollection->getSelected();
        $filters = $this->filtersCollection->getSelected();
        $comparisonTsMs = strtotime("midnight -{$timespan->getComparisonInterval()} days", $timespan->getFromTsMs() / 1000) * 1000;
        $currentTsMs = $timespan->getFromTsMs();

        $values = [];
        foreach ([ 'value' => $currentTsMs, 'comparison' => $comparisonTsMs ] as $key => $tsMs) {
            $must = [];

            $maxTs = strtotime("midnight tomorrow +{$timespan->getComparisonInterval()} days", $tsMs / 1000);
            $must[]['range'] = [
                '@timestamp' => [
                    'gte' => $tsMs,
                    'lt' => $maxTs * 1000,
                ],
            ];
            
            if ($userGuid = $this->getUserGuid()) {
                $must[] = [
                    'term' => [
                        'owner_guid' => $userGuid,
                    ],
                ];
            }

            $must[] = [
                'exists' => [
                    'field' => $this->aggField,
                ],
            ];

            $indexes = implode(',', [
                'minds-entitycentric-' . date('m-Y', $tsMs / 1000),
                'minds-entitycentric-' . date('m-Y', $maxTs),
            ]);
            $query = [
                'index' =>  'minds-entitycentric-*',
                'size' => 0,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => $must,
                        ],
                    ],
                    'aggs' => [
                        '1' => [
                            'sum' => [
                                'field' => $this->aggField,
                            ],
                        ],
                    ],
                ],
            ];

            // Query elasticsearch
            $prepared = new ElasticSearch\Prepared\Search();
            $prepared->query($query);
            $response = $this->es->request($prepared);
            $values[$key] = $response['aggregations']['1']['value'];
        }

        $this->summary = new MetricSummary();
        $this->summary
            ->setValue($values['value'])
            ->setComparisonValue($values['comparison'])
            ->setComparisonInterval($timespan->getComparisonInterval())
            ->setComparisonPositivity(true);
        return $this;
    }

    /**
     * Build a visualisation for the metric
     * @return self
     */
    public function buildVisualisation(): self
    {
        $timespan = $this->timespansCollection->getSelected();
        $filters = $this->filtersCollection->getSelected();

        $must = [];

        // Range must be from previous period
        $must[]['range'] = [
            '@timestamp' => [
                'gte' => $timespan->getFromTsMs(),
            ],
        ];

        if ($userGuid = $this->getUserGuid()) {
            $must[] = [
                'term' => [
                    'owner_guid' => $userGuid,
                ],
            ];
        }

        $must[] = [
            'exists' => [
               'field' => $this->aggField,
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
                            'min_doc_count' =>  0,
                            'extended_bounds' => [
                                'min' => $timespan->getFromTsMs(),
                                'max' => time() * 1000,
                            ],
                        ],
                        'aggs' => [
                            '2' => [
                                'sum' => [
                                    'field' => $this->aggField,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Query elasticsearch
        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);
        $response = $this->es->request($prepared);

        $buckets = [];
        foreach ($response['aggregations']['1']['buckets'] as $bucket) {
            $date = date(Visualisations\ChartVisualisation::DATE_FORMAT, $bucket['key'] / 1000);
            $buckets[] = [
                'key' => $bucket['key'],
                'date' => date('c', $bucket['key'] / 1000),
                'value' => $bucket['2']['value']
            ];
        }

        $this->visualisation = (new Visualisations\ChartVisualisation())
            ->setXLabel('Date')
            ->setYLabel('Count')
            ->setBuckets($buckets);

        return $this;
    }
}
