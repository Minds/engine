<?php

namespace Minds\Controllers\api\v2;

use Exception;
use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Common\Repository\Response;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\User;
use Minds\Helpers\Text;
use Minds\Interfaces;

class feeds implements Interfaces\Api
{


    /**
     * Gets a list of suggested hashtags, including the ones the user has opted in
     * @param array $pages
     * @throws Exception
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        try {
            /** @var User $actor */
            $actor = Core\Session::getLoggedinUser();

            switch ($pages[2] ?? '') {
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

                default:
                    $type = $pages[2] ?? '';
            }

            $limit = ($_GET['limit'] ?? 0) ?: 12;

            $hashtags = null;

            if ($_GET['hashtag'] ?? null) {
                $hashtags = Text::buildArray($_GET['hashtag']);
            } elseif ($_GET['hashtags'] ?? null) {
                $hashtags = Text::buildArray(explode(',', $_GET['hashtags']));
            }

            /** @var Core\Feeds\FeedCollection $feedCollection */
            $feedCollection = Di::_()->get('Feeds\FeedCollection');
            $feedCollection
                ->setActor($actor)
                ->setFilter($pages[0] ?? '')
                ->setAlgorithm($pages[1] ?? '')
                ->setType($type)
                ->setPeriod($_GET['period'] ?? '12h')
                ->setLimit($limit)
                ->setOffset($_GET['offset'] ?? 0)
                ->setCap($actor->isAdmin() ? 5000 : 600)
                ->setAll((bool)($_GET['all'] ?? false))
                ->setHashtags($hashtags)
                ->setSync((bool)($_GET['sync'] ?? false))
                ->setPeriodFallback((bool)($_GET['period_fallback'] ?? false))
                ->setAsActivities((bool)($_GET['as_activities'] ?? true))
                ->setQuery(urldecode($_GET['query'] ?? ''))
                ->setCustomType($_GET['custom_type'] ?? '')
                ->setContainerGuid($_GET['container_guid'] ?? null)
                ->setNsfw(explode(',', $_GET['nsfw'] ?? ''))
                ->setAccessIds([2])
                ->setSingleOwnerThreshold(36);

            $response = $feedCollection->fetch();

            return Factory::response([
                'status' => 'success',
                'entities' => $response,
                'fallback_at' => $response->getAttribute('feedbackAt'),
                'load-next' => $response->getPagingToken(),
            ]);
        } catch (Exception $e) {
            error_log((string) $e);

            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
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
