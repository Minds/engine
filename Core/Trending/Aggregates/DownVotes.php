<?php
/**
 * Votes aggregates
 */
namespace Minds\Core\Trending\Aggregates;

use Minds\Core\Data\ElasticSearch;
use Minds\Helpers\Text;

class DownVotes extends Aggregate
{
    protected $multiplier = -1;

    protected $uniques = true;

    /** @var string[] */
    protected $guids;

    /**
     * @param bool $uniques
     * @return DownVotes
     */
    public function setUniques(bool $uniques): DownVotes
    {
        $this->uniques = $uniques;
        return $this;
    }

    /**
     * @param string[] $guids
     * @return DownVotes
     */
    public function setGuids(array $guids): DownVotes
    {
        $this->guids = $guids;
        return $this;
    }

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

        if ($this->guids) {
            $must[]['terms'] = [
                'entity_guid' => Text::buildArray($this->guids),
            ];
        }

        $query = [
            'index' => 'minds-metrics-*',
            'type' => 'action',
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
                        ]
                    ]
                ]
            ]
        ];

        if ($this->uniques) {
            $query['body']['aggs']['entities']['aggs'] = [
                'uniques' => [
                    'cardinality' => [
                        'field' => "$cardinality_field.keyword",
                        //'precision_threshold' => 40000
                    ]
                ]
            ];
        }

        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        foreach ($result['aggregations']['entities']['buckets'] as $entity) {
            $value = $this->uniques ?
                ($entity['uniques']['value'] ?: 1) :
                $entity['doc_count'] ?: 1;

            yield $entity['key'] => $value * $this->multiplier;
        }
    }
}
