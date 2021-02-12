<?php
namespace Minds\Core\Blockchain\Metrics\Supply;

use Brick\Math\BigDecimal;
use Minds\Core\Blockchain\Metrics;
use Minds\Core\Data\ElasticSearch;

class CirculatingSupply extends Metrics\AbstractBlockchainMetric implements Metrics\BlockchainMetricInterface
{
    /** @var string[] */
    const MINDS_WALLETS = [
        '0x0125e3eca599e46702e534efd58bfb9ed8fbd461', // Coinbase
        '0x6f2548b1bee178a49c8ea09be6845f6aeaf3e8da', // Withdraw
        '0x1f28c6fb3ea8ba23038c70a51d8986c5d1276a8d', // Sale
        '0x112ca67c8e9a6ac65e1a2753613d37b89ab7436b', // Boost
        //plus
        //pro
    ];

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

        $totalSupply = BigDecimal::of($response['aggregations']['1']['value'])->toScale($this->token->getDecimals());

        $totalBoostReclaimed = (new TokensReclaimedForBoost())->getOffchain();

        return $totalSupply->plus($totalBoostReclaimed);
    }

    /**
     * @return BigDecimal
     */
    public function fetchOnchain(): BigDecimal
    {
        $cacheKey = get_class($this) . '->getOnchain';
        if ($cached = $this->cache->get(get_class($this) . '->getOnchain')) {
            return BigDecimal::of($cached)->toScale($this->token->getDecimals());
        }

        // Find out best guess blockNumber
        $blockNumber = $this->blockFinder->getBlockByTimestamp($this->to);

        $totalSupply = BigDecimal::of($this->token->totalSupply($blockNumber));

        foreach (static::MINDS_WALLETS as $address) {
            $raw = $this->token->fromTokenUnit($this->token->balanceOf($address, $blockNumber));
            $balance = BigDecimal::of($raw);
           
            $totalSupply = $totalSupply->minus($balance);
        }

        $this->cache->set($cacheKey, $totalSupply, 86400);

        return $totalSupply->toScale($this->token->getDecimals());
    }
}
