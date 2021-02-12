<?php
namespace Minds\Core\Blockchain\Metrics\Transactions;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Data\ElasticSearch;

class TransactionsCount extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
{
    /** @var string */
    protected $format = 'number';

    /**
     * @return BigDecimal
     */
    public function fetchOffchain(): BigDecimal
    {
        $query = [
            'index' =>  'minds-offchain*',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    '@timestamp' => [
                                        'lte' => $this->to * 1000,
                                        'gte' => $this->from * 1000,
                                        'format' => 'epoch_millis'
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ];


        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);
        $response = $this->es->request($prepared);

        return BigDecimal::of($response['hits']['total'])->toScale(0);
    }

    /**
     * @return BigDecimal
     */
    public function fetchOnchain(): BigDecimal
    {
        $query = [
            'index' =>  'minds-transactions-onchain*',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    '@timestamp' => [
                                        'lte' => $this->to * 1000,
                                        'gte' => $this->from * 1000,
                                        'format' => 'epoch_millis'
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ];


        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);
        $response = $this->es->request($prepared);

        return BigDecimal::of($response['hits']['total'])->toScale(0);
    }
}
