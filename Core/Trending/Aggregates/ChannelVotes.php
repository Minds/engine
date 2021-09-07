<?php
/**
 * Channel Votes aggregates
 */
namespace Minds\Core\Trending\Aggregates;

use Minds\Core\Data\ElasticSearch;

class ChannelVotes extends Aggregate
{
    protected $multiplier = 2;

    public function get()
    {
        $filter = [
            'term' => [
                'action' => 'vote:up'
            ]
        ];

        $must = [
            [
                'range' => [
                '@timestamp' => [
                    'gte' => $this->from,
                    'lte' => $this->to
                    ]
                ]
            ]
        ];

        if ($this->type) {
            $must[]['match'] = [
                'entity_type' => 'activity',
            ];
        }

        /*if ($this->subtype) {
            $must[]['match'] = [
                'entity_subtype' => $this->subtype
            ];
        }*/

        $query = [
            'index' => 'minds-metrics-*',
            'size' => 1, //we want just the aggregates
            'body' => [
                'query' => [
                    'bool' => [
                        //'filter' => $filter,
                        'must' => $must,
                        'must_not' => [
                            [
                                'term' => [
                                    'is_remind' => true,
                                ],

                            ],
                            ],
                    ]
                ],
                'aggs' => [
                    'entities' => [
                        'terms' => [
                            'field' => 'entity_owner_guid.keyword',
                            'size' => $this->limit,
                            'order' => [
                                'uniques' => 'desc'
                            ]
                        ],
                        'aggs' => [
                            'uniques' => [
                                'cardinality' => [
                                    'field' => 'user_guid.keyword',
                                //    'precision_threshold' => 40000
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        $entities = [];
        foreach ($result['aggregations']['entities']['buckets'] as $entity) {
            $entities[$entity['key']] = $entity['uniques']['value'] * $this->multiplier;
        }
        return $entities;
    }
}
