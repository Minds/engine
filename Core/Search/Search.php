<?php

/**
 * Minds Search normal search
 *
 * @author emi
 */

namespace Minds\Core\Search;

use Minds\Core;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Di\Di;

class Search
{
    /** @var Core\Data\ElasticSearch\Client $client */
    protected $client;

    /** @var string $esIndex */
    protected $esIndex;

    /** @var string $tagsIndex */
    protected $tagsIndex;

    /**
     * Index constructor.
     * @param null $client
     * @param null $index
     */
    public function __construct($client = null, $index = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->esIndex = $index ?: Di::_()->get('Config')->get('elasticsearch')['indexes']['search_prefix']  . '-user';
        $this->tagsIndex = $index ?: Di::_()->get('Config')->get('elasticsearch')['indexes']['tags'];
    }


    public function suggest($taxonomy, $query, $limit = 12)
    {
        $params = [
            'size' => $limit
        ];

        // TODO: implement $taxonomy
        $index = $this->esIndex;
        if ($taxonomy === 'tags') {
            $index = $this->tagsIndex;
        }
        if ($taxonomy === 'group') {
            $index = 'minds-search-group';
        }

        $prepared = new Prepared\Suggest();
        $prepared->query($index, $query, $params);

        $results = $this->client->request($prepared);

        if (!isset($results['suggest']['autocomplete'][0]['options'])) {
            return [];
        }

        $entities = array_map(function ($document) {
            return $document['_source'];
        }, $results['suggest']['autocomplete'][0]['options']);

        return $entities;
    }
}
