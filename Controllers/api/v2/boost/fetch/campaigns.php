<?php
/**
 * Boost & Boost Campaigns fetch
 * @author: eiennohi.
 */

namespace Minds\Controllers\api\v2\boost\fetch;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Common\Urn;
use Minds\Core;
use Minds\Core\Boost;
use Minds\Core\Boost\Campaigns\Campaign;
use Minds\Core\Boost\Network;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\FeedSyncEntity;
use Minds\Core\Session;
use Minds\Entities;
use Minds\Interfaces;

class campaigns implements Interfaces\Api
{
    /**
     * @param array $pages
     * @return mixed|void|null
     */
    public function get($pages)
    {
        Factory::isLoggedIn();

        /** @var Entities\User $currentUser */
        $currentUser = Session::getLoggedinUser();

        if ($currentUser->disabled_boost && $currentUser->isPlus()) {
            Factory::response([
                'entities' => [],
            ]);

            return;
        }

        // Parse parameters

        $type = $pages[0] ?? 'newsfeed';

        if (!in_array($type, ['newsfeed', 'content'], true)) {
            Factory::response([
                'status' => 'error',
                'message' => 'Unsupported boost type',
            ]);

            return;
        }

        $limit = abs(intval($_GET['limit'] ?? 2));
        $rating = intval($_GET['rating'] ?? $currentUser->getBoostRating());
        $platform = $_GET['platform'] ?? 'other';
        $quality = 0;

        if ($limit === 0) {
            Factory::response([
                'boosts' => [],
            ]);

            return;
        } elseif ($limit > 500) {
            $limit = 500;
        }

        // Options specific to newly created users (<=1 hour) and iOS users

        if ($platform === 'ios') {
            $rating = Network\Boost::RATING_SAFE;
            $quality = 90;
        } elseif (time() - $currentUser->getTimeCreated() <= 3600) {
            $rating = Network\Boost::RATING_SAFE;
            $quality = 75;
        }

        $userGuid = Core\Session::getLoggedinUser()->guid;

        $cacher = Core\Data\cache\factory::build('Redis');
        $cacheKey = "{$userGuid}:boost-offset-rotator:{$type}:{$quality}:{$rating}";
        // TODO: ENABLE ME AGAIN!
        //        $offset = $cacher->get($cacheKey);
        $offset = null;

        if (!$offset) {
            $offset = 0;
        }

        /** @var Boost\Campaigns\Manager $manager */
        $manager = Di::_()->get(Boost\Campaigns\Manager::getDiAlias());

        $data = [];

        try {
            $result = $manager->getCampaignsAndBoosts([
                'limit' => $limit,
                'from' => $offset,
                'rating' => $rating,
                'quality' => $quality,
                'type' => $type,
            ]);

            $offset = $result->getPagingToken();

            foreach ($result as $entity) {
                $feedSyncEntity = (new FeedSyncEntity())
                    ->setGuid((string) $entity->getGuid())
                    ->setOwnerGuid((string) $entity->getOwnerGuid())
                    ->setTimestamp($entity->getCreatedTimestamp());

                if ($entity instanceof Campaign) {
                    $feedSyncEntity->setUrn($entity->getUrn());
                } elseif ($entity instanceof Network\Boost) {
                    $feedSyncEntity->setUrn(new Urn("urn:boost:{$entity->getType()}:{$entity->getGuid()}"));
                }

                $data[] = $feedSyncEntity;
            }

            if (isset($data[2])) { // Always offset to 3rd in list
                $offset += 2;
            }

            $ttl = 1800; // 30 minutes
            if (($data[0] / 1000) < strtotime('48 hours ago')) {
                $ttl = 300; // 5 minutes;
            }

            $cacher->set($cacheKey, $offset, $ttl);
        } catch (\Exception $e) {
            error_log($e);
        }

        Factory::response([
            'entities' => Exportable::_($data),
            'load-next' => $offset ?: null,
        ]);
    }

    public function post($pages)
    {
        Factory::response([]);
    }

    public function put($pages)
    {
        Factory::response([]);
    }

    public function delete($pages)
    {
        Factory::response([]);
    }
}
