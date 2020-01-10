<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;

class PageviewsMetric extends AbstractMetric
{
    /** @var Elasticsearch\Client */
    private $es;

    /** @var string */
    protected $id = 'pageviews';

    /** @var string */
    protected $label = 'Pageviews';

    /** @var string */
    protected $description = "Total pageviews on all of your channel's assets. A pageview is registered when a unique page is viewed and does not include feeds.";

    /** @var array */
    protected $permissions = [ 'admin', 'user' ];

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

        // TODO: Allow this to be changed based on supplied filters
        $aggField = "views::single";

        $values = [];
        foreach ([ 'value' => $currentTsMs, 'comparison' => $comparisonTsMs ] as $key => $tsMs) {
            $must = [];
            
            $must[]['range'] = [
                '@timestamp' => [
                    'gte' => $tsMs,
                    'lte' => strtotime("midnight +{$timespan->getComparisonInterval()} days", $tsMs / 1000) * 1000,
                ],
            ];

            if ($userGuid = $this->getUserGuid()) {
                $must[] = [
                    'term' => [
                        'owner_guid' => $userGuid,
                    ],
                ];
            }

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
                            'sum' => [
                                'field' => $aggField,
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
        $this->filtersCollection->clear();

        // TODO: make this respect the filters
        $field = "views::single";

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
                                    'field' => $field,
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
