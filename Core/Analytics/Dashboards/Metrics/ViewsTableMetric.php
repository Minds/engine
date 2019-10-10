<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Data\ElasticSearch;

class ViewsTableMetric extends AbstractMetric
{
    /** @var Elasticsearch\Client */
    private $es;

    /** @var string */
    protected $id = 'views_table';

    /** @var string */
    protected $label = 'Views breakdown';

    /** @var string */
    protected $description = 'Views by post';

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
        $this->summary = new MetricSummary();
        $this->summary
            ->setValue(0)
            ->setComparisonValue(0)
            ->setComparisonInterval(null);
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
                        'terms' => [
                            'field' => 'entity_urn',
                            'min_doc_count' =>  1,
                            'order' => [
                                $field => 'desc',
                            ],
                        ],
                        'aggs' => [
                            'views::total' => [
                                'sum' => [
                                    'field' => 'views::total',
                                ],
                            ],
                            'views::organic' => [
								'sum' => [
                                    'field' => 'views::organic',
                                ],
							],
                            'views::single' => [
                                'sum' => [
                                    'field' => 'views::single',
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
            $subBuckets = [];
            $buckets[] = [
                'key' => $bucket['key'],
                'values' => [
					'views::total' => $bucket['views::total']['value'],
					'views::organic' => $bucket['views::organic']['value'],
					'views::single' => $bucket['views::single']['value'],
				],
            ];
        }

        $this->visualisation = (new Visualisations\TableVisualisation())
            ->setBuckets($buckets)
            ->setColumns([ 'views::total', 'views::organic', 'views::single']);

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
