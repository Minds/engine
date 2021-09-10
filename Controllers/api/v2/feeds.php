<?php

namespace Minds\Controllers\api\v2;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Common\Repository\Response;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\Group;
use Minds\Entities\User;
use Minds\Interfaces;

class feeds implements Interfaces\Api
{
    const PERIOD_FALLBACK = [
        '12h' => '7d',
        '24h' => '7d',
        '7d' => '30d',
        '30d' => '1y',
        '1y' => 'all',
    ];

    /**
     * Gets a list of suggested hashtags, including the ones the user has opted in
     * @param array $pages
     * @throws \Exception
     */
    public function get($pages)
    {
        // Factory::isLoggedIn();

        $now = time();
        $periodsInSecs = Core\Feeds\Elastic\Repository::PERIODS;

        /** @var User $currentUser */
        $currentUser = Core\Session::getLoggedinUser();


        $filter = $pages[0] ?? null;

        if (!$filter) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid filter',
            ]);
        }

        $algorithm = $pages[1] ?? null;

        if (!$algorithm) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid algorithm',
            ]);
        }

        $type = '';
        switch ($pages[2]) {
            case 'activities':
                $type = 'activity';
                break;
            case 'channels':
                $type = 'user';
                break;
            case 'images':
                $type = 'object-image';
                break;
            case 'videos':
                $type = 'object-video';
                break;
            case 'groups':
                $type = 'group';
                break;
            case 'blogs':
                $type = 'object-blog';
                break;
        }

        $period = $_GET['period'] ?? '12h';

        if ($algorithm === 'hot') {
            $period = '12h';
        } elseif ($algorithm === 'latest') {
            $period = '1y';
        }

        $exportCounts = false;

        if (isset($_GET['export_user_counts'])) {
            $exportCounts = true;
        }

        $hardLimit = 150;

        if ($currentUser && $currentUser->isAdmin()) {
            $hardLimit = 5000;
        }

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

        $hashtag = null;
        if (isset($_GET['hashtag'])) {
            $hashtag = strtolower($_GET['hashtag']);
        }

        $all = false;
        if (!$hashtag && isset($_GET['all']) && $_GET['all']) {
            $all = true;
        }

        $sync = (bool) ($_GET['sync'] ?? false);

        $periodFallback = (bool) ($_GET['period_fallback'] ?? false);

        $asActivities = (bool) ($_GET['as_activities'] ?? true);

        $query = isset($_GET['query']) ? urldecode($_GET['query']) : null;

        $container_guid = $_GET['container_guid'] ?? null;
        $custom_type = isset($_GET['custom_type']) && $_GET['custom_type'] ? [$_GET['custom_type']] : null;

        if ($container_guid) {
            $container = EntitiesFactory::build($container_guid);

            if (!$container || !Core\Security\ACL::_()->read($container)) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Forbidden',
                ]);
            }
        }

        /** @var Core\Feeds\Elastic\Manager $manager */
        $manager = Di::_()->get('Feeds\Elastic\Manager');

        /** @var Core\Feeds\Elastic\Entities $elasticEntities */
        $elasticEntities = new Core\Feeds\Elastic\Entities();
        $elasticEntities
            ->setActor($currentUser);

        $opts = [
            'cache_key' => Core\Session::getLoggedInUserGuid(),
            'container_guid' => $container_guid,
            'access_id' => 2,
            'custom_type' => $custom_type,
            'limit' => $limit,
            'offset' => $offset,
            'type' => $type,
            'algorithm' => $algorithm,
            'period' => $period,
            'sync' => $sync,
            'query' => $query ?? null,
            'single_owner_threshold' => 36,
            'as_activities' => $asActivities,
            'wire_support_tier_only' => filter_var($_GET['wire_support_tier_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'plus' => filter_var($_GET['plus'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];

        $nsfw = $_GET['nsfw'] ?? '';
        $opts['nsfw'] = explode(',', $nsfw);

        if ($hashtag) {
            $opts['hashtags'] = [$hashtag];
            $opts['filter_hashtags'] = true;
        } elseif (isset($_GET['hashtags']) && $_GET['hashtags']) {
            $opts['hashtags'] = explode(',', $_GET['hashtags']);
            $opts['filter_hashtags'] = true;
        } elseif (!$all) {
            /** @var Core\Hashtags\User\Manager $hashtagsManager */
            $hashtagsManager = Di::_()->get('Hashtags\User\Manager');
            $hashtagsManager->setUser(Core\Session::getLoggedInUser());

            $result = $hashtagsManager->get([
                'limit' => 50,
                'trending' => false,
                'defaults' => false,
            ]);

            $opts['hashtags'] = array_column($result ?: [], 'value');
            $opts['filter_hashtags'] = false;
        }

        try {
            $entities = new Response();
            $fallbackAt = null;
            $i = 0;

            while ($entities->count() < $limit) {
                $rows = $manager->getList($opts);

                $entities = $entities->pushArray($rows->toArray());

                if (
                    !$periodFallback ||
                    $opts['algorithm'] !== 'top' ||
                    !isset(static::PERIOD_FALLBACK[$opts['period']]) ||
                    in_array($opts['type'], ['user', 'group'], true) ||
                    ++$i > 2 // Stop at 2nd fallback (i.e. 12h > 7d > 30d)
                ) {
                    break;
                }

                $period = $opts['period'];
                $from = $now - $periodsInSecs[$period];
                $opts['from_timestamp'] = $from * 1000;
                $opts['period'] = static::PERIOD_FALLBACK[$period];
                $opts['limit'] = $limit - $entities->count();

                if (!$fallbackAt) {
                    $fallbackAt = $from;
                }
            }

            if (!$sync) {
                // Remove all unlisted content, if ES document is not in sync, it'll
                // also remove pending activities
                $entities = $entities->filter([$elasticEntities, 'filter']);

                if ($asActivities) {
                    // Cast to ephemeral Activity entities, if another type
                    $entities = $entities->map([$elasticEntities, 'cast']);
                }
            }
            if ($type === 'user' && $exportCounts) {
                foreach ($entities as $entity) {
                    $entity->getEntity()->exportCounts = true;
                }
            }

            return Factory::response([
                'status' => 'success',
                'entities' => Exportable::_($entities),
                'fallback_at' => $fallbackAt,
                'load-next' => $limit + $offset,
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
