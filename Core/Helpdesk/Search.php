<?php
/**
 * Help & Support Group posts search
 */
namespace Minds\Core\Helpdesk;

use Minds\Core;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;

class Search
{
    /** @var ElasticSearch\Client */
    private $elastic;

    /** @var string */
    private $indexPrefix;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var int */
    private $offset;

    /**
     * Constructor
     *
     * @param Database\ElasticSearch $elastic
     * @param string $indexPrefix
     */
    public function __construct($elastic = null, $indexPrefix = null, $entitiesBuilder = null)
    {
        $this->elastic = $elastic ?: Di::_()->get('Database\ElasticSearch');
        $this->indexPrefix  = $indexPrefix ?: Di::_()->get('Config')->elasticsearch['indexes']['search_prefix'];
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Search
     *
     * @param string $string
     * @param integer $limit
     * @return array Activities
     */
    public function search($string, $limit = 5)
    {
        $body = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'container_guid' => '100000000000000681'
                            ]
                        ],
                        [
                            'query_string' => [
                                'default_field' => 'message',
                                'query' => $string
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $prepared = new Core\Data\ElasticSearch\Prepared\Search();
        $prepared->query([
            'body' => $body,
            'index' => $this->indexPrefix . '-activity',
            'size' => $limit,
            'from' => (int) $this->offset,
            'client' => [
                'timeout' => 2,
                'connect_timeout' => 1
            ]
        ]);

        $result = $this->elastic->request($prepared);

        if (!isset($result['hits'])) {
            return [];
        }

        $entitiesBuilder = $this->entitiesBuilder;

        $entities = array_map(function ($r) use ($entitiesBuilder) {
            return $entitiesBuilder->single($r['_source']['guid']);
        }, $result['hits']['hits']);

        $entities = array_values(array_filter($entities));

        return $entities;
    }
}
