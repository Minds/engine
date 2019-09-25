<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;

class ViewsMetric extends MetricAbstract
{
    /** @var string */
    protected $id = 'views';

    /** @var string */
    protected $label = 'views';

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
        $previousTs = strtotime('-1 ' . $timespan->getAggInterval(), $timespan->getFromTsMs() / 1000) * 1000;
        $currentTs = $timespan->getFromTsMs();

        $must = [];
        $must[]['range'] = [
            '@timestamp' => [
                'gte' => $previousTs,
            ],
        ];

        $aggType = "sum";

        // TODO: Allow this to be changed based on supplied filters
        $aggField = "views::total";

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
