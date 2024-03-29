<?php

namespace Minds\Controllers\api\v2\feeds;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\User;
use Minds\Entities\Group;
use Minds\Interfaces;

class container implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws \Exception
     */
    public function get($pages)
    {
        /** @var User $currentUser */
        $currentUser = Core\Session::getLoggedinUser();

        //

        $container_guid = $pages[0] ?? null;

        if (!$container_guid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid container',
            ]);
        }

        $container = EntitiesFactory::build($container_guid);

        if (!$container || !Core\Security\ACL::_()->read($container, $currentUser)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Forbidden',
            ]);
        }

        if (!($container instanceof User || $container instanceof Group)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Bad request. The container does not appear to be a user or group',
            ]);
        }

        $custom_type = isset($_GET['custom_type']) && $_GET['custom_type'] ? [$_GET['custom_type']] : null;

        $type = '';
        switch ($pages[1]) {
            case 'activities':
                $type = 'activity';
                break;
            case 'images':
                $type = 'activity';
                if (!$custom_type) {
                    $custom_type = 'batch';
                }
                break;
            case 'videos':
                $type = 'activity';
                if (!$custom_type) {
                    $custom_type = 'video';
                }
                break;
            case 'blogs':
                $type = 'object-blog';
                break;
            case 'objects':
                $type = 'object-*';
                break;
            case 'all':
                $type = 'all';
                break;
        }

        //

        $hardLimit = 150;
        $offset = 0;

        if (isset($_GET['offset'])) {
            $offset = intval($_GET['offset']);
        }

        $limit = 12;

        if (isset($_GET['limit'])) {
            $limit = abs(intval($_GET['limit']));
        }

        if (($offset + $limit) > $hardLimit) {
            $limit = $hardLimit - $offset;
        }

        if ($limit <= 0) {
            return Factory::response([
                'status' => 'success',
                'entities' => [],
                'load-next' => $hardLimit,
                'overflow' => true,
            ]);
        }

        //

        $algorithm = (string) ($_GET['algorithm'] ?? 'latest');

        $sync = (bool) ($_GET['sync'] ?? false);

        $fromTimestamp = $_GET['from_timestamp'] ?? 0;

        $asActivities = (bool) ($_GET['as_activities'] ?? true);

        $forcePublic = (bool) ($_GET['force_public'] ?? false);

        $reverseSort = (bool) ($_GET['reverse_sort'] ?? false);

        $query = null;

        if (isset($_GET['query'])) {
            $query = $_GET['query'];
        }

        /** @var Core\Feeds\Elastic\Manager $manager */
        $manager = Di::_()->get('Feeds\Elastic\Manager');

        /** @var Core\Feeds\Elastic\Entities $entities */
        $entities = new Core\Feeds\Elastic\Entities();
        $entities->setActor($currentUser);

        $isOwner = false;

        if ($currentUser) {
            $entities->setActor($currentUser);
            $isOwner = $currentUser->guid == $container_guid;
        }

        $opts = [
            'cache_key' => $currentUser ? $currentUser->guid : null,
            'container_guid' => $container_guid,
            'access_id' => $isOwner && !$forcePublic ? [0, 1, 2, $container_guid] : [2, $container_guid],
            'custom_type' => $custom_type,
            'limit' => $limit,
            'type' => $type,
            'algorithm' => $algorithm,
            'period' => '1y',
            'sync' => $sync,
            'from_timestamp' => $fromTimestamp,
            'reverse_sort' => $reverseSort,
            'query' => $query,
            'portrait' => isset($_GET['portrait']),
            'single_owner_threshold' => 0,
            'pinned_guids' => $type === 'activity' ? array_reverse($container->getPinnedPosts()) : null,
        ];

        if (isset($_GET['nsfw'])) {
            if (is_array($_GET['nsfw'])) {
                $opts['nsfw'] = $_GET['nsfw'];
            } else {
                $nsfw = $_GET['nsfw'] ?? '';
                $opts['nsfw'] = explode(',', $nsfw);
            }
        }

        if (isset($_GET['to_timestamp'])) {
            $opts['to_timestamp'] = $_GET['to_timestamp'] ;
        }

        try {
            $result = $manager->getList($opts);

            /**
             * This was added to prevent that some channels show posts that are not their own
             * https://gitlab.com/minds/front/-/issues/4613
             */
            if ($container instanceof User) {
                $result = $result->filter(function ($entity) use ($container_guid) {
                    return $entity->getOwnerGuid() == $container_guid;
                });
            }

            if (!$sync) {
                // Remove all unlisted content, if ES document is not in sync, it'll
                // also remove pending activities
                $result = $result->filter([$entities, 'filter']);

                if ($asActivities) {
                    // Cast to ephemeral Activity entities, if another type
                    $result = $result->map([$entities, 'cast']);
                }
            }

            return Factory::response([
                'status' => 'success',
                'entities' => Exportable::_($result),
                'load-next' => $result->getPagingToken(),
            ]);
        } catch (\Exception $e) {
            error_log($e);
            return Factory::response(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Equivalent to HTTP POST method
     * @param array $pages
     * @return mixed|null
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP PUT method
     * @param array $pages
     * @return mixed|null
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * Equivalent to HTTP DELETE method
     * @param array $pages
     * @return mixed|null
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
