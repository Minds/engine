<?php
namespace Minds\Core\Monetization\Partners;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Data\ElasticSearch\Client;

class Sums
{
    /** @var Client */
    protected $es;
    
    /** @var Config */
    protected $config;

    public function __construct($es = null, Config $config = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Get total earnings
     * @param array $opts
     * @return iterable
     */
    public function getTotalEarningsForOwners(array $opts = []): iterable
    {
        $opts = array_merge([
            'from' => null,
            'to' => null,
        ], $opts);

        $must = [];

        if ($opts['from'] || $opts['to']) {
            $must[] = [
                'range' => [
                    '@timestamp' => [
                        'gte' => $opts['from'],
                        'lte' => $opts['to'],
                    ]
                ]
            ];
        }

        $body = [
            'query' => [
                'bool' => [
                    'must' => $must,
                ],
            ],
            'aggs' => [
                '2' => [
                    'terms' => [
                        'field' => 'owner_guid',
                        'size' => 1000,
                        'order' => [
                            '3' => 'desc',
                        ],
                    ],
                    'aggs' => [
                        '3' => [
                            'sum' => [
                                'field' => 'usd_earnings::total',
                            ],
                        ],
                        '4' => [
                            'cardinality' => [
                                'field' => 'entity_urn',
                            ],
                        ]
                    ],
                ],
            ],
        ];

        $query = [
            'index' => 'minds-entitycentric*',
            'body' => $body,
        ];

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->es->request($prepared);

        foreach ($result['aggregations'][2]['buckets'] as $bucket) {
            $ownerGuid = $bucket['key'];
            /*$postsCount = $this->getPostsPerOwner([ $ownerGuid ], [
                'from' => strtotime('first day of this month', $opts['to'] / 1000) * 1000,
                'to' => $opts['to'] * 1000
            ])[$ownerGuid];*/

            $balance = new EarningsBalance();
            $balance->setUserGuid($ownerGuid)
                ->setAmountCents((int) $bucket['3']['value']);
            $balances[] = $balance;
          
            yield $balance;
        }
    }

    public function getPostsPerOwner($owners, array $opts = []): array
    {
        $opts = array_merge([
            'from' => null,
            'to' => null,
        ], $opts);

        $must = [];

        if ($opts['from'] || $opts['to']) {
            $must[] = [
                'range' => [
                    '@timestamp' => [
                        'gte' => $opts['from'],
                        'lte' => $opts['to'],
                    ]
                ]
            ];
        }

        $must[] = [
            'terms' => [
                'owner_guid.keyword' => $owners
            ]
        ];

        $body = [
            'query' => [
                'bool' => [
                    'must' => $must
                ]
            ],
            'aggs' => [
                'owner_guid' => [
                    'terms' => [
                        'field' => 'owner_guid.keyword',
                    ]
                ]
            ],
        ];

        $query = [
            'index' => $this->config->get('elasticsearch')['indexes']['search_prefix'] . '-activity',
            'body' => $body,
        ];

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->es->request($prepared);

        $ownerPostsCount = [];
        foreach ($result['aggregations']['owner_guid']['buckets'] as $bucket) {
            $ownerPostsCount[$bucket['key']] = $bucket['doc_count'] ?: 0;
        }
        return $ownerPostsCount;
    }
}
