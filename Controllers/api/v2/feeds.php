<?php

namespace Minds\Controllers\api\v2;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Interfaces;

class feeds implements Interfaces\Api
{
    /**
     * Gets a list of suggested hashtags, including the ones the user has opted in
     * @param array $pages
     * @throws \Exception
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        $filter = $pages[0] ?? null;

        if (!$filter) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid filter'
            ]);
        }

        $algorithm = $pages[1] ?? null;

        if (!$algorithm) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid algorithm'
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
                $type = 'object:image';
                break;
            case 'videos':
                $type = 'object:video';
                break;
            case 'groups':
                $type = 'group';
                break;
            case 'blogs':
                $type = 'object:blog';
                break;
        }

        $period = $_GET['period'] ?? '12h';

        if ($algorithm === 'hot') {
            $period = '12h';
        } elseif ($algorithm === 'latest') {
            $period = '1y';
        }

        $offset = 0;

        if (isset($_GET['offset'])) {
            $offset = intval($_GET['offset']);
        }

        $limit = 12;

        if (isset($_GET['limit'])) {
            $limit = intval($_GET['limit']);
        }

        $hashtag = null;
        if (isset($_GET['hashtag'])) {
            $hashtag = $_GET['hashtag'];
        }

        $all = false;
        if (!$hashtag && isset($_GET['all']) && $_GET['all']) {
            $all = true;
        }

        $query = null;
        if (isset($_GET['query'])) {
            $query = $_GET['query'];
        }

        $container_guid = $_GET['container_guid'] ?? null;
        $custom_type = isset($_GET['custom_type']) && $_GET['custom_type'] ? [$_GET['custom_type']] : null;

        if ($container_guid) {
            $container = EntitiesFactory::build($container_guid);

            if (!$container || !Core\Security\ACL::_()->read($container)) {
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Forbidden'
                ]);
            }
        }

        /** @var Core\Feeds\Top\Manager $manager */
        $manager = Di::_()->get('Feeds\Top\Manager');

        /** @var Core\Feeds\Top\Entities $entities */
        $entities = new Core\Feeds\Top\Entities();

        $opts = [
            'cache_key' => Core\Session::getLoggedInUserGuid(),
            'container_guid' => $container_guid,
            'custom_type' => $custom_type,
            'limit' => $limit,
            'offset' => $offset,
            'type' => $type,
            'algorithm' => $algorithm,
            'period' => $period,
            'query' => $query ?? null,
            //'rating' => $_GET['rating'] ?? 1,
            'rating' => 2,
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
            $result = $manager->getList($opts);
        } catch (\Exception $e) {
            error_log($e);
            return Factory::response(['status' => 'error', 'message' => $e->getMessage()]);
        }

        // Remove all unlisted content if it appears
        $result = array_filter($result, [$entities, 'filter']);

        // Cast to ephemeral Activity entities, if another type
        $result = array_map([$entities, 'cast'], $result);

        return Factory::response([
            'status' => 'success',
            'entities' => Exportable::_(array_values($result)),
            'load-next' => $limit + $offset,
        ]);
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
