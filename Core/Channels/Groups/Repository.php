<?php
namespace Minds\Core\Channels\Groups;

use Minds\Common\Repository\Response;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Count;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\FeedSyncEntity;

/**
 * Channel Groups Repository
 * @package Minds\Core\Channels\Groups
 */
class Repository
{
    /** @var Client */
    protected $es;

    /** @var Repository\ElasticSearchQuery */
    protected $esQuery;

    /**
     * Repository constructor.
     * @param $es
     * @param $esQuery
     */
    public function __construct(
        $es = null,
        $esQuery = null
    ) {
        $this->es = $es ?: Di::_()->get('Database\ElasticSearch');
        $this->esQuery = $esQuery ?: new Repository\ElasticSearchQuery();
    }

    /**
     * Fetches public groups within provided GUIDs
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'user_guid' => null,
            'pageToken' => '',
            'query' => '',
            'limit' => 500,
        ], $opts);

        if (!$opts['user_guid']) {
            return new Response([]);
        }

        $query = $this->esQuery->build($opts['user_guid'], $opts['query']);

        $query['size'] = $opts['limit'];
        $query['from'] = (int) $opts['pageToken'] ?? 0;

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->es->request($prepared);

        $response = new Response($result['hits']['hits']);
        $response->setPagingToken($query['from'] + $query['size']);
        $response->setLastPage($response->count() < $query['size']);

        return $response->map(function ($document) {
            $feedSyncEntity = new FeedSyncEntity();

            $feedSyncEntity
                ->setGuid($document['_source']['guid'])
                ->setOwnerGuid($document['_source']['owner_guid'])
                ->setTimestamp($document['_source']['time_created'])
                ->setUrn("urn:group:{$document['_source']['guid']}");

            return $feedSyncEntity;
        });
    }

    /**
     * Counts public groups within the provided GUIDs
     * @param array $opts
     * @return int
     */
    public function count(array $opts = []): int
    {
        $opts = array_merge([
            'user_guid' => null,
        ], $opts);

        if (!$opts['user_guid']) {
            return 0;
        }

        $prepared = new Count();
        $prepared->query($this->esQuery->build($opts['user_guid']));

        $response = $this->es->request($prepared);

        return $response['count'] ?? 0;
    }
}
