<?php
/**
 * Minds Core Search API
 *
 * @version 2
 * @author Emiliano Balbuena
 */
namespace Minds\Controllers\api\v2\search;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Interfaces;
use Minds\Api\Factory;
use Minds\Core\EntitiesBuilder;
use Minds\Entities;

class suggest implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    public function __construct(private ?EntitiesBuilder $entitiesBuilder = null)
    {
        $this->entitiesBuilder ??= Di::_()->get(EntitiesBuilder::class);
    }

    /**
     * Equivalent to HTTP GET method
     * @param  array $pages
     * @return mixed|null
     */
    public function get($pages)
    {
        /** @var Core\Search\Search $search */
        $search = Di::_()->get('Search\Search');

        if (!isset($_GET['q']) || !$_GET['q']) {
            return Factory::response([
                'entities' => []
            ]);
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 12;
        $includeNsfw = isset($_GET['include_nsfw']) ? $_GET['include_nsfw'] === 1 : false;

        //$hydrate = isset($_GET['hydrate']) && $_GET['hydrate'];
        $hydrate = true;

        $query = str_replace('#', '', $_GET['q']);

        $entityType = $pages[0] ?? 'user';

        // Rather than throwing an error, we are just defaulting to user to avoid potential
        // breaking changes across clients following this addition.
        if (!in_array($entityType, ['user', 'group'], true)) {
            $entityType = 'user';
        }

        $exactMatch = null;

        if ($entityType === 'user') {
            // Get any exact match for query to prepend to top after search.
            $exactMatch = $this->entitiesBuilder->getByUserByIndex($query);
        }

        try {
            $entities = $search->suggest($entityType, $query, $limit);
            $entities = array_values(array_filter($entities, function ($entity) {
                return isset($entity['guid']) && !$entity['nsfw'];
            }));

            if ($entities && $hydrate) {
                $guids = [];
                
                foreach ($entities as $entity) {
                    $guids[] = $entity['guid'];
                }
                
                if ($guids) {
                    $entities = array_filter($this->entitiesBuilder->get([ 'guids' => $guids ]) ?: [], function ($entity) use ($includeNsfw, $exactMatch) {
                        if (
                            // Skip NSFW entities if include_nsfw is false.
                            (!$includeNsfw && count($entity->getNsfw())) ||
                            // Skip exact matches, preappend the exported entity directly.
                            $exactMatch?->getGuid() === $entity->getGuid()
                        ) {
                            return false;
                        }
                        return true;
                    });
                    $entities = Factory::exportable(array_values($entities));
                }
            }

            if ($exactMatch) {
                $entities = array_merge([$exactMatch->export()], $entities);
            }

            return Factory::response([
                'entities' => $entities
            ]);
        } catch (\Exception $e) {
            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Equivalent to HTTP POST method
     * @param  array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param  array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param  array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
