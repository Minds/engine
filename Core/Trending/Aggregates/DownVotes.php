<?php
/**
 * Votes aggregates
 */
namespace Minds\Core\Trending\Aggregates;

use Minds\Core\Data\ElasticSearch;

class DownVotes extends Aggregate
{
    protected $multiplier = -1;

    public function get()
    {
        $filter = [
            'term' => [
                'action' => 'vote:down'
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
        
        if ($this->type && $this->type != 'group' && $this->type != 'user') {
            $must[]['match'] = [
                'entity_type' => $this->type
            ];
        }

        if ($this->subtype) {
            $must[]['match'] = [
                'entity_subtype' => $this->subtype
            ];
        }
        
        $field = 'entity_guid';
        $cardinality_field = 'ip_hash';

        if ($this->type == 'group') {
            $field = 'entity_container_guid';
            //$this->multiplier = 4;
            $must[]['range'] = [
                'entity_access_id' => [
                  'gte' => 3, //would be group
                  'lt' => null,
                ]
            ];
        }

        if ($this->type == 'user') {
            $field = 'entity_owner_guid';
        }

        //$must[]['match'] = [
        //    'rating' => $this->rating
        //];

        $query = [
            'index' => 'minds-metrics-*',
            'size' => 0, //we want just the aggregates
            'body' => [
                'query' => [
                    'bool' => [
                        'filter' => $filter,
                        'must' => $must
                    ]
                ],
                'aggs' => [
                    'entities' => [
                        'terms' => [
                            'field' => "$field.keyword",
                            'size' => $this->limit,
             //               'order' => [ 'uniques' => 'DESC' ],
                        ],
                        'aggs' => [
                            'uniques' => [
                                'cardinality' => [
                                    'field' => "$cardinality_field.keyword"
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
