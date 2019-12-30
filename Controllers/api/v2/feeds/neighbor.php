<?php
/**
 * neighbor
 *
 * @author edgebal
 */

namespace Minds\Controllers\api\v2\feeds;

use Exception;
use Minds\Api\Factory;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\FeedCollection;
use Minds\Core\Session;
use Minds\Helpers\Text;
use Minds\Interfaces;

class neighbor implements Interfaces\Api
{
    /**
     * @inheritDoc
     */
    public function get($pages)
    {
        $actor = Session::getLoggedinUser() ?: null;

        /** @var FeedCollection $feedCollection */
        $feedCollection = Di::_()->get('Feeds\Collection');
        $feedCollection
            ->setActor($actor);

        try {
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

            $hashtags = null;

            if ($_GET['hashtag'] ?? null) {
                $hashtags = Text::buildArray($_GET['hashtag']);
            } elseif ($_GET['hashtags'] ?? null) {
                $hashtags = Text::buildArray(explode(',', $_GET['hashtags']));
            }

            $nsfw = array_values(array_filter(explode(',', $_GET['nsfw'] ?? '')));

            //

            // TODO: Calculate based on container/owner/subscribed feed
            // TODO: Create a class for calculation and use it on other feed endpoints for consistency
            $accessIds = [2];

            // TODO: Calculate based on container/owner/subscribed feed
            // TODO: Create a class for calculation and use it on other feed endpoints for consistency
            $singleOwnerThreshold = 36;

            //

            $feedCollection
                ->setFilter($pages[0] ?? '')
                ->setAlgorithm($pages[1] ?? '')
                ->setType($type)
                ->setPeriod($_GET['period'] ?? '12h')
                ->setOffset($_GET['offset'] ?? 0)
                ->setCap($actor->isAdmin() ? 5000 : 600)
                ->setAll((bool) ($_GET['all'] ?? false))
                ->setHashtags($hashtags)
                ->setSync((bool) ($_GET['sync'] ?? false))
                ->setPeriodFallback(false)
                ->setAsActivities((bool)($_GET['as_activities'] ?? true))
                ->setQuery(urldecode($_GET['query'] ?? ''))
                ->setCustomType($_GET['custom_type'] ?? '')
                ->setContainerGuid($_GET['container_guid'] ?? null)
                ->setNsfw($nsfw)
                ->setAccessIds($accessIds)
                ->setSingleOwnerThreshold($singleOwnerThreshold)
                ->setLimit(1);

            list($prev, $next) = $feedCollection
                ->fetchAdjacent($_GET['guid'] ?? null);

            return Factory::response([
                'status' => 'success',
                'prev' => $prev,
                'next' => $next
            ]);
        } catch (Exception $e) {
            error_log((string) $e);

            return Factory::response([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function post($pages)
    {
        return Factory::response([]);
    }

    /**
     * @inheritDoc
     */
    public function put($pages)
    {
        return Factory::response([]);
    }

    /**
     * @inheritDoc
     */
    public function delete($pages)
    {
        return Factory::response([]);
    }
}
