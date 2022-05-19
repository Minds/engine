<?php

namespace Minds\Core\Feeds\Elastic;

use Composer\Semver\Comparator;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Feeds\Seen\Manager as SeenManager;
use Minds\Core\Search;
use Minds\Core\Security\ACL;
use Minds\Helpers\Flags;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var Search\Search */
    private $search;

    /** @var Events\Dispatcher */
    private $eventsDispatcher;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var ACL */
    protected $acl;

    /** @var SeenManager */
    protected $seenManager;

    /** @var Entities */
    protected $entities;

    private $from;

    private $to;

    private $type = 'activity';

    private $subtype = '';

    public function __construct(
        $repository = null,
        $entitiesBuilder = null,
        $entities = null,
        $search = null,
        $seenManager = null,
        $eventsDispatcher = null,
        $acl = null
    ) {
        $this->repository = $repository ?: new Repository;
        $this->entitiesBuilder = $entitiesBuilder ?: new EntitiesBuilder;
        $this->entities = $entities ?: new Entities;
        $this->search = $search ?: Di::_()->get('Search\Search');
        $this->eventsDispatcher = $eventsDispatcher ?? Di::_()->get('EventsDispatcher');
        $this->acl = $acl ?? Di::_()->get('Security\ACL');
        $this->seenManager = $seenManager ?? Di::_()->get('Feeds\Seen\Manager');

        $this->from = strtotime('-7 days') * 1000;
        $this->to = time() * 1000;
    }

    /**
     * @param string $type
     * @return Manager
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $subtype
     * @return Manager
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * @param array $opts
     * @return Response
     * @throws \Exception
     */
    public function getList(array $opts = [])
    {
        // Mobile will temporarily disable new style reminds from being displayed (default true for mobile)
        $hide_reminds = filter_var($_GET['hide_reminds'] ?? isset($_SERVER['HTTP_APP_VERSION']), FILTER_VALIDATE_BOOLEAN);

        $opts = array_merge([
            'algorithm' => null,
            'cache_key' => null,
            'user_guid' => null,
            'container_guid' => null,
            'owner_guid' => null,
            'subscriptions' => null,
            'access_id' => null,
            'custom_type' => null,
            'offset' => 0,
            'limit' => 12,
            'type' => null,
            'sync' => false,
            'from_timestamp' => null,
            'reverse_sort' => null,
            'query' => null,
            'nsfw' => null,
            'single_owner_threshold' => 36,
            'filter_hashtags' => false,
            'pinned_guids' => null,
            'as_activities' => false,
            'exclude' => null,
            'pending' => false,
            'plus' => false,
            'hide_reminds' => $hide_reminds,
            'wire_support_tier_only' => false,
            'include_group_posts' => false,
            'unseen' => false,
            'demoted' => false,
        ], $opts);

        if (isset($opts['query']) && $opts['query']) {
            $opts['query'] = str_replace('#', '', strtolower($opts['query']));
        }

        if (isset($opts['query']) && $opts['query'] && in_array($opts['type'], ['user', 'group'], true)) {
            $result = $this->search($opts);

            $response = new Response($result);
            return $response;
        }

        if (isset($opts['unseen']) && $opts['unseen']) {
            $seenEntities = $this->seenManager->listSeenEntities();
            if (count($seenEntities) > 0) {
                $opts['exclude'] = array_merge($opts['exclude'] ?? [], $seenEntities);
            }
        }

        $feedSyncEntities = [];
        $scores = [];
        $owners = [];
        $i = 0;

        foreach ($this->repository->getList($opts) as $scoredGuid) {
            if (!$scoredGuid->getGuid()) {
                continue;
            }

            $ownerGuid = $scoredGuid->getOwnerGuid() ?: $scoredGuid->getGuid();

            if (
                $i < $opts['single_owner_threshold']
                && isset($owners[$ownerGuid])
                && !$opts['filter_hashtags']
                && !in_array($opts['type'], ['user', 'group'], true)
            ) {
                continue;
            }
            $owners[$ownerGuid] = true;

            ++$i; // Update here as we don't want to count skipped

            $entityType = $scoredGuid->getType() ?? 'entity';
            if (strpos($entityType, 'object-', 0) === 0) {
                $entityType = str_replace('object-', '', $entityType);
            }

            if ($opts['as_activities'] && !in_array($opts['type'], ['user', 'group'], true)) {
                $entityType = 'activity';
            }

            $urn = implode(':', [
                'urn',
                $entityType ?: 'entity',
                $scoredGuid->getGuid(),
            ]);

            $feedSyncEntities[] = (new FeedSyncEntity())
                ->setGuid((string) $scoredGuid->getGuid())
    
                 ->setOwnerGuid((string) $ownerGuid)
                ->setUrn(new Urn($urn))
                ->setTimestamp($scoredGuid->getTimestamp());

            $scores[(string) $scoredGuid->getGuid()] = $scoredGuid->getScore();
        }

        $entities = [];
        $next = '';

        /**
         * Awkward hack to pin mobile post
         */
        if (isset($_SERVER['HTTP_APP_VERSION']) && Comparator::lessThan($_SERVER['HTTP_APP_VERSION'], '4.17.0')) {
            $mobilePin = (new FeedSyncEntity())
                ->setGuid("1279518512628371457")
                ->setOwnerGuid("100000000000000519")
                ->setUrn(new Urn("urn:activity:1279518512628371457"))
                ->setTimestamp(1630436985);
            array_unshift($feedSyncEntities, $mobilePin);
        }

        if (count($feedSyncEntities) > 0) {
            $next = (string) (array_reduce($feedSyncEntities, function ($carry, FeedSyncEntity $feedSyncEntity) {
                return min($feedSyncEntity->getTimestamp() ?: INF, $carry);
            }, INF) - 1);

            $hydrateGuids = array_map(function (FeedSyncEntity $feedSyncEntity) {
                return $feedSyncEntity->getGuid();
            }, array_slice($feedSyncEntities, 0, 12)); // hydrate the first 12

            $hydratedEntities = $this->entitiesBuilder->get(['guids' => $hydrateGuids]);

            foreach ($hydratedEntities as $entity) {
                if ($opts['pinned_guids'] && in_array($entity->getGuid(), $opts['pinned_guids'], false)) {
                    $entity->pinned = true;
                }
                if ($opts['as_activities']) {
                    $entity = $this->entities->cast($entity);
                }
                $entities[] = (new FeedSyncEntity)
                    ->setGuid($entity->getGuid())
                    ->setOwnerGuid($entity->getOwnerGuid())
                    ->setUrn($entity->getUrn())
                    ->setEntity($entity);
            }

            // TODO: Optimize this
            foreach (array_slice($feedSyncEntities, 12) as $entity) {
                $entities[] = $entity;
            }

            // TODO: confirm if the following is actually necessary
            // especially after the first 12

            /*usort($entities, function ($a, $b) use ($scores) {
               $aGuid = $a instanceof FeedSyncEntity ? $a->getGuid() : $a->guid;
               $bGuid = $b instanceof FeedSyncEntity ? $b->getGuid() : $b->guid;

               $aScore = $scores[(string) $aGuid];
               $bScore = $scores[(string) $bGuid];

               if ($aScore === $bScore) {
                   return 0;
               }

               return $aScore < $bScore ? 1 : -1;
           });*/
        }

        $response = new Response($entities);
        $response->setPagingToken($next ?: '');

        return $response;
    }

    /**
     * @param array $opts
     * @return int
     */
    public function getCount(array $opts = [])
    {
        return $this->repository->getCount($opts);
    }

    /**
     * @param array $opts
     * @return array
     * @throws \Exception
     */
    private function search(array $opts = [])
    {
        $feedSyncEntities = [];

        // Replace #
        $opts['query'] = str_replace('#', '', $opts['query']);

        if (!in_array($opts['type'], ['user', 'group'], true)) {
            return [];
        }

        if ($opts['type'] === 'user') {
            $response = $this->search->suggest('user', $opts['query'], $opts['limit']);
            foreach ($response as $row) {
                $feedSyncEntities[] = (new FeedSyncEntity())
                    ->setGuid((string) $row['guid'])
                    ->setOwnerGuid((string) $row['guid'])
                    ->setUrn("urn:user:{$row['guid']}")
                    ->setTimestamp($row['time_created'] * 1000);
            }
        }

        if ($opts['type'] === 'group') {
            $response = $this->search->suggest('group', $opts['query'], $opts['limit']);
            foreach ($response as $row) {
                $feedSyncEntities[] = (new FeedSyncEntity())
                    ->setGuid($row['guid'])
                    ->setOwnerGuid(-1)
                    ->setUrn("urn:group:{$row['guid']}")
                    ->setTimestamp(0);
            }
        }

        $entities =  [];

        $hydrateGuids = array_map(function (FeedSyncEntity $feedSyncEntity) {
            return $feedSyncEntity->getGuid();
        }, array_slice($feedSyncEntities, 0, 12)); // hydrate the first 12

        if ($hydrateGuids) {
            $hydratedEntities = $this->entitiesBuilder->get(['guids' => $hydrateGuids, 'acl' => false]);

            foreach ($hydratedEntities as $entity) {
                if (Flags::shouldFail($entity) && false) {
                    $this->eventsDispatcher->trigger('search:index', 'all', [
                        'entity' => $entity,
                        'immediate' => false
                    ]);
                    continue;
                }
                if (!$this->acl->read($entity)) {
                    continue;
                }
                if (array_diff($entity->getNsfw(), $opts['nsfw'])) {
                    continue; // User not viewing NSFW
                }
                $entities[] = (new FeedSyncEntity)
                    ->setGuid($entity->getGuid())
                    ->setOwnerGuid($entity->getOwnerGuid())
                    ->setUrn($entity->getUrn())
                    ->setEntity($entity);
            }
        }

        // TODO: Optimize this
        foreach (array_slice($feedSyncEntities, 12) as $entity) {
            $entities[] = $entity;
        }

        return $entities;
    }
}
