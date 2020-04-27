<?php
namespace Minds\Core\Analytics\Dashboards\Metrics\Engagement;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Analytics\Dashboards\Metrics\AbstractMetric;
use Minds\Core\Analytics\Dashboards\Metrics\MetricSummary;
use Minds\Core\Analytics\Dashboards\Metrics\Visualisations;
use Minds\Core\Analytics\Dashboards\Metrics\HistogramSegment;
use Minds\Core\Analytics\Dashboards\Metrics\HistogramBucket;

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

    /** @var HistogramSegment[] */
    protected $segments = [];

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
        // This is for backwards compatability. We should put deprecated notice here soon
        if (empty($this->segments)) {
            $this->segments = [
                (new HistogramSegment)
                    ->setAggField($this->aggField)
                    ->setAggType('sum'),
            ];
        }

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
               'field' => $this->segments[0]->getAggField(),
            ],
        ];

        $aggs = [];
        foreach ($this->segments as $i => $segment) {
            $key = (string) $i + 2;
            $aggs[$key] = [
                $segment->getAggType() => [
                    'field' => $segment->getAggField(),
                ],
            ];
        }

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
                        'aggs' => $aggs,
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

            foreach ($this->segments as $i => $segment) {
                $key = (string) $i + 2;
                $segment->addBucket(
                    (new HistogramBucket)
                        ->setKey($bucket['key'])
                        ->setTimestampMs($bucket['key'])
                        ->setValue($bucket[$key]['value'])
                );
            }
        }

        $this->visualisation = (new Visualisations\ChartVisualisation())
            ->setXLabel('Date')
            ->setYLabel('Count')
            ->setSegments($this->segments);

        return $this;
    }
}
