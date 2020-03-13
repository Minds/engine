<?php
/**
 * Scroll
 * @author mark
 */

namespace Minds\Core\Data\ElasticSearch;

use Minds\Core\Data\Interfaces\PreparedInterface;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;

class Scroll
{
    /** @var Client */
    protected $client;

    /** @var Logger */
    protected $logger;

    /** @var string */
    public $scrollId;

    const ES_DEFAULT_SCROLL_TIME = "60s";

    /**
     * Scroll constructor.
     * @param Client $client
     */
    public function __construct(
        $client = null,
        $logger = null
    ) {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->logger = $logger ??  Di::_()->get('Logger');
    }

    /**
     * @param PreparedInterface $prepared
     * @return \Generator
     */
    public function request(PreparedInterface $prepared)
    {
        $query = $prepared->build();
        if (!isset($query['scroll'])) {
            $query['scroll'] = self::ES_DEFAULT_SCROLL_TIME;
        }

        try {
            $response = $this->client->getClient()->search($query);
        } catch (NoNodesAvailableException $e) {
            $this->logger->error("No ElasticSearch nodes found, trying again in 5 seconds");
            sleep(5);
            return $this->request($prepared);
        }

        // Now we loop until the scroll "cursors" are exhausted
        while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
            foreach ($response['hits']['hits'] as $doc) {
                yield $doc;
            }

            $scroll_id = $response['_scroll_id'];
            $this->scrollId = $scroll_id;

            $response = $this->scroll($scroll_id);
        }
    }

    /**
     * Perform and retry to scroll
     * @param string $id
     * @return array
     */
    protected function scroll($id): array
    {
        try {
            $response = $this->client->getClient()->scroll(
                [
                    "scroll_id" => $id,
                    "scroll" => self::ES_DEFAULT_SCROLL_TIME
                ]
            );
            return $response;
        } catch (NoNodesAvailableException $e) {
            $this->logger->error("No ElasticSearch nodes found, trying again in 5 seconds");
            sleep(5);
            return $this->scroll($id);
        }
    }
}
