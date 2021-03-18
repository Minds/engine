<?php
namespace Minds\Core\Analytics\Dashboards\Metrics;

use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Util\BigNumber;
use Minds\Core\Blockchain\Wallets\OffChain\Balance as OffchainWalletBalance;
use Minds\Entities\User;

class TokenBalanceMetric extends AbstractMetric
{
    /** @var ElasticSearch\Client */
    private $es;

    /** @var OffchainWalletBalance */
    private $offchainWalletBalance;

    /** @var string */
    protected $id = 'token_balance';

    /** @var string */
    protected $label = 'Token Balance';

    /** @var string */
    protected $description = 'Token Balance';

    /** @var array */
    protected $permissions = [ 'admin', 'user' ];

    /** @var string */
    protected $unit = 'tokens';

    public function __construct($es = null, $offchainWalletBalance = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
        $this->offchainWalletBalance = $offchainWalletBalance ?? Di::_()->get('Blockchain\Wallets\OffChain\Balance');
    }

    /**
     * Build the metric summary
     * @return self
     */
    public function buildSummary(): self
    {
        $timespan = $this->timespansCollection->getSelected();
        $this->summary = new MetricSummary();
        $this->summary->setValue(0)
            ->setComparisonValue(0)
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

        $must = [];

        $must[] = [
            'range' => [
                '@timestamp' => [
                    'gte' => $timespan->getFromTsMs(),
                ]
            ]
        ];

        $must[] = [
            'term' => [
                'user_guid' => $this->getUser()->getGuid(),
            ],
        ];

        $bounds =  [
          'min' => $timespan->getFromTsMs(),
          'max' => time() * 1000,
        ];

        if ($timespan->getId() === 'max') {
            unset($bounds['min']);
        }

        // Do the query
        $query = [
            'index' => 'minds-offchain*',
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
                            'extended_bounds' => $bounds,
                        ],
                        'aggs' => [
                            '2' => [
                                'sum' => [
                                    'field' => 'amount',
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

        $this->offchainWalletBalance->setUser($this->getUser());

        $currentBalance = (string) BigNumber::fromPlain($this->offchainWalletBalance->get(), 18);
        $runningBalance = $currentBalance;

        $buckets = [];
        foreach (array_reverse($response['aggregations']['1']['buckets']) as $bucket) {
            $date = date(Visualisations\ChartVisualisation::DATE_FORMAT, $bucket['key'] / 1000);

            $volume = $bucket['2']['value'];
            $runningBalance = $runningBalance - $volume;

            $xValues[] = $date;
            $yValues[] = $bucket['2']['value'];
            $buckets[] = [
                'key' => $bucket['key'],
                'date' => date('c', $bucket['key'] / 1000),
                'value' => $runningBalance,
            ];
        }


        $this->visualisation = (new Visualisations\ChartVisualisation())
            ->setXValues($xValues ?? [])
            ->setYValues($yValues ?? [])
            ->setXLabel('Date')
            ->setYLabel('Balancce')
            ->setBuckets(array_reverse($buckets));

        return $this;
    }
}
