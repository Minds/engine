<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;

class SignupsMetric extends MetricAbstract
{
    /** @var string */
    protected $id = 'signups';

    /** @var string */
    protected $label = 'signups';

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

        // Range must be from previous period
        $must[]['range'] = [
            '@timestamp' => [
                'gte' => $previousTs,
            ],
        ];

        // Return our global metrics
        $must[]['term'] = [
            'entity_urn' => 'urn:metric:global',
        ];

        // Daily resolution
        // TODO: implement this to avoid duplicated
        // $must[] = [
        //     'term' => [
        //         'resolution' => 'day',
        //     ],
        // ];

        $aggType = "sum";
        $aggField = "signups::total";

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
