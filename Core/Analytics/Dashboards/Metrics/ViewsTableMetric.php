<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Entities\Resolver;
use Minds\Common\Urn;

class ViewsTableMetric extends AbstractMetric
{
    /** @var Elasticsearch\Client */
    private $es;

    /** @var Resolver */
    private $entitiesResolver;

    /** @var string */
    protected $id = 'views_table';

    /** @var string */
    protected $label = 'Views breakdown';

    /** @var string */
    protected $description = 'Views by post';

    /** @var array */
    protected $permissions = [ 'user', 'admin' ];

    public function __construct($es = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
        $this->entitiesResolver = $entitiesResolver ?? new Resolver();
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
        // $must[] = [
        //     'term' => [
        //         'resolution' => $timespan->getInterval(),
        //     ],
        // ];

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
            $entity = $this->entitiesResolver->single(new Urn($bucket['key']));
            $buckets[] = [
                'key' => $bucket['key'],
                'values' => [
                    'entity' => $entity ? $entity->export() : null,
                    'views::total' => $bucket['views::total']['value'],
                    'views::organic' => $bucket['views::organic']['value'],
                    'views::single' => $bucket['views::single']['value'],
                ],
            ];
        }

        $this->visualisation = (new Visualisations\TableVisualisation())
            ->setBuckets($buckets)
            ->setColumns([
                [
                    'id' => 'entity',
                    'label' => '',
                    'order' => 0,
                ],
                [
                    'id' => 'views::total',
                    'label' => 'Total Views',
                    'order' => 1,
                ],
                [
                    'id' => 'views::organic',
                    'label' => 'Organic',
                    'order' => 2,
                ],
                [
                    'id' => 'views::single',
                    'label' => 'Pageviews',
                    'order' => 3,
                ]
            ]);

        return $this;
    }
}
