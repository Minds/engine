<?php
namespace Minds\Core\Blockchain\Metrics\Supply;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Data\ElasticSearch;

class TokensRewarded extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
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
                                'term' => [
                                    'contract' => 'offchain:reward',
                                ],
                            ],
                            [
                                'range' => [
                                    '@timestamp' => [
                                        'lte' => $this->to * 1000,
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
                            'field' => 'amount',
                        ],
                    ],
                ],
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
        return BigDecimal::of(0);
    }
}
