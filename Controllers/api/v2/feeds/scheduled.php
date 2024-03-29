<?php

namespace Minds\Controllers\api\v2\feeds;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\User;
use Minds\Interfaces;

class scheduled implements Interfaces\Api
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

        if (!$currentUser ||
            !$container->canEdit()
        ) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You cannot view this users scheduled posts',
            ]);
        }

        $type = '';
        switch ($pages[1]) {
            case 'activities':
                $type = 'activity';
                break;
            case 'images':
                $type = 'object-image';
                break;
            case 'videos':
                $type = 'object-video';
                break;
            case 'blogs':
                $type = 'object-blog';
                break;
            case 'count':
                $type = 'activity';

                /** @var Core\Feeds\Scheduled\Manager $manager */
                $manager = new Core\Feeds\Scheduled\Manager;

                return Factory::response([
                    'status' => 'success',
                    'count' => $manager->getScheduledCount([
                        'container_guid' => $container_guid,
                        'type' => $type,
                        'owner_guid' => $currentUser->guid,
                    ])
                ]);
            default:
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Invalid type',
                ]);
        }

        $hardLimit = 5000;
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

        $sync = (bool) ($_GET['sync'] ?? false);

        $fromTimestamp = $_GET['from_timestamp'] ?? 0;

        $asActivities = (bool) ($_GET['as_activities'] ?? true);

        $forcePublic = (bool) ($_GET['force_public'] ?? false);

        $query = null;

        if (isset($_GET['query'])) {
            $query = $_GET['query'];
        }

        $custom_type = isset($_GET['custom_type']) && $_GET['custom_type'] ? [$_GET['custom_type']] : null;

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
            'algorithm' => 'latest',
            'period' => '1y',
            'sync' => $sync,
            'from_timestamp' => $fromTimestamp,
            'query' => $query,
            'single_owner_threshold' => 0,
            'pinned_guids' => $type === 'activity' ? array_reverse($container->getPinnedPosts()) : null,
            'future' => true,
            'owner_guid' => $currentUser->guid,
        ];

        if (isset($_GET['nsfw'])) {
            $nsfw = $_GET['nsfw'] ?? '';
            $opts['nsfw'] = explode(',', $nsfw);
        }
        
        try {
            $result = $manager->getList($opts);

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

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }
}
