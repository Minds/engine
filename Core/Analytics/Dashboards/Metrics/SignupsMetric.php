<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch;

class SignupsMetric extends AbstractMetric
{
    /** @var Elasticsearch\Client */
    private $es;

    /** @var string */
    protected $id = 'signups';

    /** @var string */
    protected $label = 'Signups';

    /** @var string */
    protected $description = 'New accounts registered';

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
        if ($this->getUserGuid()) {
            return $this;
        }

        $timespan = $this->timespansCollection->getSelected();
        $comparisonTsMs = strtotime("-{$timespan->getComparisonInterval()} days", $timespan->getFromTsMs() / 1000) * 1000;
        $currentTsMs = $timespan->getFromTsMs();

        $aggField = "signups::total";

        $values = [];
        foreach ([ 'value' => $currentTsMs, 'comparison' => $comparisonTsMs ] as $key => $tsMs) {
            $must = [];
     
            // Return our global metrics
            $must[]['term'] = [
                 'entity_urn' => 'urn:metric:global',
             ];

            $must[]['range'] = [
                '@timestamp' => [
                    'gte' => $tsMs,
                    'lt' => strtotime("midnight tomorrow +{$timespan->getComparisonInterval()} days", $tsMs / 1000) * 1000,
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
            ->setComparisonInterval($timespan->getComparisonInterval());
        return $this;
    }

    /**
     * Build a visualisation for the metric
     * @return self
     */
    public function buildVisualisation(): self
    {
        if ($this->getUserGuid()) {
            $this->visualisation = (new Visualisations\ChartVisualisation());
            return $this;
        }

        $timespan = $this->timespansCollection->getSelected();
        $xValues = [];
        $yValues = [];

        // TODO: make this respect the filters
        $field = "signups::total";

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
        /*$must[] = [
            'term' => [
                'resolution' => $timespan->getInterval(),
            ],
        ];*/

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
}
