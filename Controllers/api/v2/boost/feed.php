<?php
/**
 * Boost Fetch
 *
 * @version 2
 * @author emi
 *
 */

namespace Minds\Controllers\api\v2\boost;

use Minds\Api\Exportable;
use Minds\Common\Urn;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Helpers;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;

class feed implements Interfaces\Api
{
    /**
     * Equivalent to HTTP GET method
     * @param array $pages
     * @return mixed|null
     * @throws \Exception
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        /** @var Entities\User $currentUser */
        $currentUser = Core\Session::getLoggedinUser();

        if ($currentUser->disabled_boost && $currentUser->isPlus()) {
            return Factory::response([
                'boosts' => [],
            ]);
        }

        // Parse parameters

        $type = $pages[0] ?? 'newsfeed';
        $limit = abs(intval($_GET['limit'] ?? 2));
        $offset = $_GET['offset'] ?? null;
        $rating = intval($_GET['rating'] ?? $currentUser->getBoostRating());
        $platform = $_GET['platform'] ?? 'other';
        $quality = 0;
        $isBoostFeed = $_GET['boostfeed'] ?? false;

        if ($limit === 0) {
            return Factory::response([
                'boosts' => [],
            ]);
        } elseif ($limit > 500) {
            $limit = 500;
        }

        $cacher = Core\Data\cache\factory::build('Redis');
        $offset =  $cacher->get(Core\Session::getLoggedinUser()->guid . ':boost-offset-rotator');

        if ($isBoostFeed) {
            $offset = $_GET['from_timestamp'] ?? 0;
        }

        // Options specific to newly created users (<=1 hour) and iOS users

        if ($platform === 'ios') {
            $rating = 1; // they can only see safe content
            $quality = 90;
        } elseif (time() - $currentUser->getTimeCreated() <= 3600) {
            // No boost for first hour
            return Factory::response([
                'boosts' => [],
            ]);
        }

        //

        $boosts = [];
        $next = null;

        switch ($type) {
            case 'newsfeed':
                // Newsfeed boosts

                $resolver = new Core\Entities\Resolver();

                /** @var Core\Boost\Network\Iterator $iterator */
                $iterator = Core\Di\Di::_()->get('Boost\Network\Iterator');
                $iterator
                    ->setLimit(10)
                    ->setOffset($offset)
                    ->setRating($rating)
                    ->setQuality($quality)
                    ->setType($type)
                    ->setPriority(true)
                    ->setHydrate(false);

                foreach ($iterator as $boost) {
                    $feedSyncEntity = new Core\Feeds\FeedSyncEntity();
                    $feedSyncEntity
                        ->setGuid((string) $boost->getGuid())
                        ->setOwnerGuid((string) $boost->getOwnerGuid())
                        ->setTimestamp($boost->getCreatedTimestamp())
                        ->setUrn(new Urn("urn:boost:{$boost->getType()}:{$boost->getGuid()}"));

                    $entity = $resolver->single(new Urn("urn:boost:{$boost->getType()}:{$boost->getGuid()}"));
                    if (!$entity) {
                        continue; // Duff entity?
                    }

                    $feedSyncEntity->setEntity($entity);

                    $boosts[] = $feedSyncEntity;
                }
               // $boosts = iterator_to_array($iterator, false);

                $next = $iterator->getOffset();

                if (isset($boosts[1]) && !$isBoostFeed) { // Always offset to 2rd in list if in rotator
                    // if (!$offset) {
                    //     $next = $boosts[1]->getTimestamp();
                    // } else {
                    //     $next = 0;
                    // }
                    $len = count($boosts);
                    if ($boosts[$len -1]) {
                        $next = $boosts[$len -1]->getTimestamp();
                    }
                } elseif ($isBoostFeed) {
                    $len = count($boosts);
                    if ($boosts[$len -1]) {
                        $next = $boosts[$len -1]->getTimestamp();
                    }
                }

                $ttl = 1800; // 30 minutes
                if (($next / 1000) < strtotime('48 hours ago')) {
                    $ttl = 300; // 5 minutes;
                }

                $cacher->set(Core\Session::getLoggedinUser()->guid . ':boost-offset-rotator', $next, $ttl);
                break;

            case 'content':
                // TODO: Content boosts
            default:
                return Factory::response([
                    'status' => 'error',
                    'message' => 'Unsupported boost type'
                ]);
        }

        return Factory::response([
            'entities' => Exportable::_($boosts),
            'load-next' => $next ?: null,
        ]);
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
