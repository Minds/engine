<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\Elasticsearch;

class ViewsMetric extends AbstractMetric
{
    /** @var Elasticsearch\Client */
    private $es;

    /** @var string */
    protected $id = 'views';

    /** @var string */
    protected $label = 'Views';

    /** @var string */
    protected $description = 'Views on channel assets';

    /** @var array */
    protected $permissions = [ 'admin' ];

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
        $aggField = "views::total";

        if ($filters['view_type']) {
            $aggField = "views::" . $filters['view_type']->getSelectedOption();
        }

        $values = [];
        foreach ([ 'value' => $currentTsMs, 'comparison' => $comparisonTsMs ] as $key => $tsMs) {
            $must = [];

            // Specify the resolution to avoid duplicates
            $must[] = [
                'term' => [
                    'resolution' => $timespan->getInterval(),
                ],
            ];

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
        $filters = $this->filtersCollection->getSelected();
        $xValues = [];
        $yValues = [];

        // TODO: make this respect the filters
        $field = "views::total";

        if ($filters['view_type']) {
            $field = "views::" . $filters['view_type']->getSelectedOption();
        }

        $must = [];

        // Range must be from previous period
        $must[]['range'] = [
            '@timestamp' => [
                'gte' => $timespan->getFromTsMs(),
            ],
        ];

        // Specify the resolution to avoid duplicates
        $must[] = [
            'term' => [
                'resolution' => $timespan->getInterval(),
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
                            'min_doc_count' =>  1,
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
            $xValues[] = $date;
            $yValues[] = $bucket['2']['value'];
            $buckets[] = [
                'key' => $bucket['key'],
                'date' => date('c', $bucket['key'] / 1000),
                'value' => $bucket['2']['value']
            ];
        }

        $this->visualisation = (new Visualisations\ChartVisualisation())
            ->setXValues($xValues)
            ->setYValues($yValues)
            ->setXLabel('Date')
            ->setYLabel('Count')
            ->setBuckets($buckets);

        return $this;
    }

    private function getUserGuid(): ?string
    {
        $filters = $this->filtersCollection->getSelected();
        $channelFilter = $filters['channel'];

        if (!$channelFilter) {
            return "";
        }

        if ($channelFilter->getSelectedOption() === 'self') {
            return Session::getLoggedInUserGuid();
        }
        if ($channelFilter->getSelectedOption() === 'all') {
            return "";
        }

        // TODO: check permissions first
        return $channelFilter->getSelectedOption();
    }
}
