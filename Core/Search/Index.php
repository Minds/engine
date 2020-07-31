<?php

/**
 * Description
 *
 * @author emi
 */

namespace Minds\Core\Search;

use Minds\Core;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Di\Di;
use Minds\Entities\Entity;
use Minds\Exceptions\BannedException;

class Index
{
    /** @var Core\Data\ElasticSearch\Client $client */
    protected $client;

    /** @var string $esIndex */
    protected $esIndex;

    /** @var Core\EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Core\Search\Hashtags\Manager */
    protected $hashtagsManager;

    /** @var Cleanup */
    protected $cleanup;

    /**
     * Index constructor.
     * @param null $client
     * @param null $index
     * @param null $entitiesBuilder
     * @param null $hashtagsManager
     */
    public function __construct(
        $client = null,
        $index = null,
        $entitiesBuilder = null,
        $hashtagsManager = null,
        $cleanup = null
    ) {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->esIndex = $index ?: Di::_()->get('Config')->elasticsearch['index'];
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->hashtagsManager = $hashtagsManager ?: Di::_()->get('Search\Hashtags\Manager');
        $this->cleanup = $cleanup ?? new Cleanup();
    }

    /**
     * Indexes an entity
     * @param $entity
     * @return bool
     */
    public function index($entity)
    {
        if (!$entity) {
            error_log('[Search/Index] Cannot index an empty entity');
            return false;
        }

        if (!is_object($entity)) {
            $entity = $this->entitiesBuilder->build($entity, false);
        }

        if ($entity->guid == '100000000000000519') {
            error_log('tried to index minds channel, but temporary aborting');
            return true; // TRUE prevents retries
        }

        try {
            /** @var Mappings\MappingInterface $mapper */
            $mapper = Di::_()->get('Search\Mappings')->build($entity);

            $body = $mapper->map();

            if ($suggest = $mapper->suggestMap()) {
                $body = array_merge($body, [
                    'suggest' => $suggest
                ]);
            }

            $query = [
                'index' => $this->esIndex,
                'type' => $mapper->getType(),
                'id' => $mapper->getId(),
                'body' => [
                    'doc' => $body,
                    'doc_as_upsert' => true,
                ],
            ];

            $prepared = new Prepared\Update();
            $prepared->query($query);

            $result = (bool) $this->client->request($prepared);

            // if hashtags were found, index them separately
            if (isset($body['tags']) && is_array($body['tags'])) {
                foreach ($body['tags'] as $tag) {
                    try {
                        $this->hashtagsManager->index($tag);
                    } catch (\Exception $e) {
                    }
                }
            }
            error_log("Indexed {$mapper->getId()}");
        } catch (BannedException $e) {
            $result = true; // Null was resolving as 'false' so setting to true
            $this->cleanup->prune($entity);
        } catch (\Exception $e) {
            error_log('[Search/Index] ' . get_class($e) . ": {$e->getMessage()}");
            $result = false;
        }

        return $result;
    }

    /**
     * @param Entity|string $entity
     * @param array $opts
     * @return bool
     */
    public function update($entity, $opts)
    {
        if (!$entity) {
            error_log("[Search/Index] Cannot update an empty entity's index");
            return false;
        }

        if (!is_object($entity)) {
            $entity = $this->entitiesBuilder->build($entity, false);
        }
        $result = false;

        try {
            /** @var Mappings\MappingInterface $mapper */
            $mapper = Di::_()->get('Search\Mappings')->build($entity);

            $query = [
                'index' => $this->esIndex,
                'type' => $mapper->getType(),
                'id' => $mapper->getId(),
                'body' => ['doc' => $opts]
            ];

            $prepared = new Prepared\Update();
            $prepared->query($query);
            $result = (bool) $this->client->request($prepared);
        } catch (\Exception $e) {
            error_log('[Search/Index] ' . get_class($e) . ": {$e->getMessage()}");
            print_r($e);
        }

        return $result;
    }
}
