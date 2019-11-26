<?php

namespace Minds\Controllers\api\v1\boost;

use Minds\Api\Factory;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\Entity;
use Minds\Helpers\Counters;
use Minds\Interfaces;
use Minds\Core\Boost;

class fetch implements Interfaces\Api
{
    /**
     * Return a list of boosts that a user needs to review
     * @param array $pages
     */
    public function get($pages)
    {
        $response = [];
        $user = Core\Session::getLoggedinUser();

        if (!$user) {
            Factory::response([
                'status' => 'error',
                'message' => 'You must be loggedin to view boosts',
            ]);
            return;
        }

        if ($user->disabled_boost && $user->isPlus()) {
            Factory::response([]);
            return;
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 2;
        $rating = isset($_GET['rating']) ? (int) $_GET['rating'] : $user->getBoostRating();
        $platform = isset($_GET['platform']) ? $_GET['platform'] : 'other';
        $quality = 0;

        // options specific to newly created users (<=1 hour) and iOS users
        if (time() - $user->getTimeCreated() <= 3600) {
            $rating = Boost\Network\Boost::RATING_SAFE;
            $quality = 75;
        }

        if ($platform === 'ios') {
            $rating = Boost\Network\Boost::RATING_SAFE;
            $quality = 90;
        }

        /** @var  $iterator */
        $iterator = new Core\Boost\Network\Iterator();
        $iterator->setLimit($limit)
            ->setRating($rating)
            ->setQuality($quality)
            ->setOffset($_GET['offset'])
            ->setType($pages[0])
            ->setUserGuid($user->getGUID());

        if (isset($_GET['rating']) && $pages[0] == Boost\Network\Boost::TYPE_NEWSFEED) {
            $cacher = Core\Data\cache\factory::build('Redis');
            $offset =  $cacher->get(Core\Session::getLoggedinUser()->guid . ':boost-offset:newsfeed');
            $iterator->setOffset($offset);
        }

        switch ($pages[0]) {
            case Boost\Network\Boost::TYPE_CONTENT:
                /** @var $entity Entity */
                foreach ($iterator as $guid => $entity) {
                    $response['boosts'][] = array_merge($entity->export(), [
                        'boosted_guid' => (string) $guid,
                        'urn' => "urn:boost:content:{$guid}",
                    ]);
                    Counters::increment($entity->guid, "impression");
                    Counters::increment($entity->owner_guid, "impression");
                }
                $response['load-next'] = $iterator->getOffset();
                
                if (!$response['boosts']) {
                    $result = Di::_()->get('Trending\Repository')->getList([
                        'type' => 'images',
                        'rating' => isset($rating) ? (int) $rating : Boost\Network\Boost::RATING_SAFE,
                        'limit' => $limit,
                    ]);

                    if ($result && isset($result['guids'])) {
                        $entities = Core\Entities::get([ 'guids' => $result['guids'] ]);
                        $response['boosts'] = Factory::exportable($entities);
                    }
                }
                break;
            case Boost\Network\Boost::TYPE_NEWSFEED:
                foreach ($iterator as $guid => $entity) {
                    $response['boosts'][] = array_merge($entity->export(), [
                        'boosted' => true,
                        'boosted_guid' => (string) $guid,
                        'urn' => "urn:boost:newsfeed:{$guid}",
                    ]);
                }
                $response['load-next'] = $iterator->getOffset();
                if (isset($_GET['rating']) && $pages[0] == 'newsfeed') {
                    $cacher->set(Core\Session::getLoggedinUser()->guid . ':boost-offset:newsfeed', $iterator->getOffset(), (3600 / 2));
                }
                break;
        }

        Factory::response($response);
    }

    public function post($pages)
    {
        /* Not Implemented */
    }

    public function put($pages)
    {
        /* Not Implemented */
    }

    public function delete($pages)
    {
        /* Not Implemented */
    }
}
