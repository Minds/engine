<?php

/**
 * Description
 *
 * @author emi
 */

namespace Minds\Core\Search;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Minds\Common\SystemUser;
use Minds\Core\Config\Config;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Search\Hashtags\Manager;
use Minds\Entities\Entity;
use Minds\Entities\EntityInterface;
use Minds\Exceptions\BannedException;

class Index
{
    const LOG_PREFIX = "[Search/Index]";

    public function __construct(
        protected ?Client $client = null,
        protected ?string $indexPrefix = null,
        protected ?Manager $hashtagsManager = null,
        protected ?Logger $logger = null,
        protected ?Mappings\Factory $mappingFactory = null,
        protected ?Config $config = null,
    ) {
        $this->client ??= Di::_()->get('Database\ElasticSearch');
        $this->indexPrefix ??= Di::_()->get('Config')->get('elasticsearch')['indexes']['search_prefix'];
        $this->hashtagsManager ??= Di::_()->get('Search\Hashtags\Manager');
        $this->logger ??= Di::_()->get('Logger');
        $this->mappingFactory ??= Di::_()->get('Search\Mappings');
        $this->config ??= Di::_()->get(Config::class);
    }

    /**
     * Indexes an entity
     * @param $entity
     * @return bool
     */
    public function index(EntityInterface $entity): bool
    {
        if (!$entity) {
            $this->logger->error(self::LOG_PREFIX . ' Cannot index an empty entity');
            return false;
        }

        if ($entity->getGuid() == SystemUser::GUID) {
            $this->logger->error(self::LOG_PREFIX . ' Tried to index minds channel, but temporary aborting');
            return true; // TRUE prevents retries
        }

        /** @var Mappings\MappingInterface $mapper */
        $mapper = $this->mappingFactory->build($entity);

        try {
            $body = $mapper->map();

            if ($tenantId = $this->config->get('tenant_id')) {
                $body['tenant_id'] = $tenantId;
            }

            if ($suggest = $mapper->suggestMap()) {
                $body = array_merge($body, [
                    'suggest' => $suggest
                ]);
            }

            $query = [
                'index' => $this->indexPrefix . '-' .$mapper->getType(),
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
            $this->logger->info(self::LOG_PREFIX . " Indexed {$mapper->getId()}");
        } catch (BannedException $e) {
            $result = true; // Null was resolving as 'false' so setting to true
            $this->remove($entity);
        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX . " Error indexing '{$mapper->getId()}'"  . get_class($e) . ": {$e->getMessage()}");
            $result = false;
        }

        return $result;
    }

    /**
     * @param Entity|string $entity
     * @param array $opts
     * @return bool
     */
    public function update(EntityInterface $entity, array $opts): bool
    {
        if (!$entity) {
            $this->logger->error(self::LOG_PREFIX . " Cannot update an empty entity's index");
            return false;
        }

        $result = false;

        /** @var Mappings\MappingInterface $mapper */
        $mapper = $this->mappingFactory->build($entity);

        try {
            $query = [
                'index' => $this->indexPrefix . '-' . $mapper->getType(),
                'id' => $mapper->getId(),
                'body' => ['doc' => $opts]
            ];

            $prepared = new Prepared\Update();
            $prepared->query($query);
            $result = (bool) $this->client->request($prepared);
        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX . " Error removing '{$mapper->getId()}'" . get_class($e) . ": {$e->getMessage()}");
            print_r($e);
        }

        return $result;
    }

    /**
     * @param EntityInterface $entity
     * @return bool
     */
    public function remove(EntityInterface $entity): bool
    {
        if (!$entity) {
            $this->logger->error(self::LOG_PREFIX . " Cannot cleanup an empty entity's index");
            return false;
        }

        $result = false;

        /** @var Mappings\MappingInterface $mapper */
        $mapper = $this->mappingFactory->build($entity);

        try {
            $query = [
                'index' => $this->indexPrefix . '-' . $mapper->getType(),
                'id' => $mapper->getId(),
            ];

            $prepared = new Prepared\Delete();
            $prepared->query($query);
            $result = (bool) $this->client->request($prepared);

            $this->logger->info(self::LOG_PREFIX . " Removed {$mapper->getId()}");
        } catch (Missing404Exception $e) {
            $result = true;
            $this->logger->info(self::LOG_PREFIX . " Already deleted {$mapper->getId()}");
        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX . ' ' . get_class($e) . ": {$e->getMessage()}");
        }

        return $result;
    }
}
