<?php
namespace Minds\Core\Blockchain\Metrics\Transactions;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Data\ElasticSearch;

class TransactionsVolume extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
{
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
                                    'amount' => [
                                        'gt' => 0,
                                    ]
                                ],
                            ],
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
                'aggs' => [
                    '1' => [
                        'sum' => [
                            'field' => 'amount'
                        ]
                    ]
                ]
            ],
        ];


        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);
        $response = $this->es->request($prepared);

        return BigDecimal::of($response['aggregations']['1']['value'])->toScale($this->token->getDecimals());
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
                                    'tokenValue' => [
                                        'gt' => 0,
                                    ]
                                ],
                            ],
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
                'aggs' => [
                    '1' => [
                        'sum' => [
                            'field' => 'tokenValue'
                        ]
                    ]
                ]
            ],
        ];


        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);
        $response = $this->es->request($prepared);

        return BigDecimal::of($response['aggregations']['1']['value'])->toScale($this->token->getDecimals());
    }
}
