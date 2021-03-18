<?php
namespace Minds\Core\Boost;

use Minds\Interfaces\BoostHandlerInterface;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Data;
use Minds\Entities;
use Minds\Helpers;

use MongoDB\BSON;

/**
 * Newsfeed Boost handler
 * @todo Proper DI
 */
class Network implements BoostHandlerInterface
{
    protected $handler = 'newsfeed';
    protected $mongo;


    public function __construct(Data\Interfaces\ClientInterface $mongo = null)
    {
        $this->mongo = $mongo ?: Data\Client::build('MongoDB');
    }

    /**
     * Boost an entity
     * @param  object/int $entity - the entity to boost
     * @param  int $impressions
     * @return boolean
     */
    public function boost($boost, $impressions = 0)
    {
        $documentId = $this->mongo->insert("boost", [
            'guid' => $boost->getGuid(),
            'owner_guid' => $boost->getOwner()->guid,
            'impressions' => $boost->getImpressions(),
            'state' => 'review',
            'type' => $this->handler,
            'priority' => $boost->getPriorityRate(),
            'categories' => $boost->getCategories(),
            'createdAt' => new BSON\UTCDateTime(time() * 1000),
            'approvedAt' => null,
            'rating' => $boost->getRating(),
            'quality' => $boost->getQuality()
        ]);

        if ($documentId) {
            return (string) $documentId;
        }

        return $boost->getGuid();
    }

    public function accept($entity, $impressions)
    {
        return false;
    }

//     /**
//     * Return boosts for review
//     * @param  int    $limit
//     * @param  string $offset
//     * @return array
//     */
//    public function getReviewQueue($limit, $offset = "")
//    {
//        $query = [ 'state'=>'review', 'type'=> $this->handler ];
//        if ($offset) {
//            $query['_id'] = [ '$gt' => $offset ];
//        }
//        $queue = $this->mongo->find("boost", $query, [
//            'limit' => $limit,
//            'sort' => [ 'priority' => -1, '_id' => 1 ],
//        ]);
//        if (!$queue) {
//            return false;
//        }
//
//        $guids = [];
//        $end = "";
//        foreach ($queue as $data) {
//            $_id = (string) $data['_id'];
//            $guids[$_id] = (string) $data['guid'];
//            $end = $data['guid'];
//            //$this->mongo->remove("boost", ['_id' => $_id]);
//        }
//
//        if (!$guids) {
//            return [
//                'data' => [],
//                'next' => ''
//            ];
//        }
//
//        /** @var Repository $repository */
//        $repository = Di::_()->get('Boost\Repository');
//        $boosts = $repository->getAll($this->handler, [
//            'guids' => $guids
//        ]);
//
//        return [
//            'data' => $boosts['data'],
//            'next' => $end
//        ];
//    }
//
//    /**
//     * Return the review count
//     * @return int
//     */
//    public function getReviewQueueCount()
//    {
//        $query = ['state' => 'review', 'type' => $this->handler];
//        $count = $this->mongo->count("boost", $query);
//        return $count;
//    }

    /**
     * Get our own submitted Boosts
     * @param  int $limit
     * @param  string $offset
     * @return array
     */
    public function getOutbox($limit, $offset = "")
    {
        /** @var Repository $repository */
        $repository = Di::_()->get('Boost\Repository');
        $boosts = $repository->getAll($this->handler, [
            'owner_guid' => Core\Session::getLoggedinUser()->guid,
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'DESC'
        ]);

        return $boosts;
    }

    /**
     * Gets a single boost entity
     * @param  mixed $guid
     * @return object
     */
    public function getBoostEntity($guid)
    {
        /** @var Repository $repository */
        $repository = Di::_()->get('Boost\Repository');
        return $repository->getEntity($this->handler, $guid);
    }

    /**
     * Return a boost
     * @return array
     */
    public function getBoost($offset = "")
    {
        return null;
    }

    /**
     * Expire a boost from the queue
     * @param  object $boost
     * @return void
     */
    private function expireBoost($boost)
    {
        if (!$boost) {
            return;
        }

        $boost->setState('completed')
          ->save();

        $this->mongo->remove("boost", [ '_id' => $boost->getId() ]);

        Core\Events\Dispatcher::trigger('notification', 'boost', [
            'to' => [$boost->getOwner()->guid],
            'from' => 100000000000000519,
            'entity' => $boost->getEntity(),
            'notification_view' => 'boost_completed',
            'params' => [
                'impressions' => $boost->getImpressions(),
                'title' => $boost->getEntity()->title ?: $boost->getEntity()->message
            ],
            'impressions' => $boost->getBid()
        ]);
    }

    /**
     * Polyfills boost thumbs
     * @param  string[] $boosts
     * @return string[]
     */
    private function patchThumbs($boosts)
    {
        $keys = [];
        foreach ($boosts as $boost) {
            $keys[] = "thumbs:up:entity:$boost->guid";
        }
        $db = new Data\Call('entities_by_time');
        $thumbs = $db->getRows($keys, ['offset'=> Core\Session::getLoggedInUserGuid()]);
        foreach ($boosts as $k => $boost) {
            $key = "thumbs:up:entity:$boost->guid";
            if (isset($thumbs[$key])) {
                $boosts[$k]->{'thumbs:up:user_guids'} = array_keys($thumbs[$key]);
            }
        }
        return $boosts;
    }

    private function filterBlocked($boosts)
    {
        // Use Network/Iterator. This should not be used by any functions
        return $boosts;
    }

    // Analytics

    public function getBacklogCount()
    {
        return (int) $this->mongo->count('boost', [
            'state' => 'approved',
            'type' => $this->handler
        ]);
    }

    public function getPriorityBacklogCount()
    {
        return (int) $this->mongo->count('boost', [
            'state' => 'approved',
            'type' => $this->handler,
            'priority' => [
                '$exists' => true,
                '$gt' => 0
            ],
        ]);
    }

    public function getBacklogImpressionsSum()
    {
        $result = $this->mongo->aggregate('boost', [
            [ '$match' => [
                'state' => 'approved',
                'type' => $this->handler
            ] ],
            [ '$group' => [
                '_id' => null,
                'total' => [ '$sum' => '$impressions' ]
            ] ]
        ]);

        return reset($result)->total ?: 0;
    }

    public function getAvgApprovalTime()
    {
        $result = $this->mongo->aggregate('boost', [
            [ '$match' => [
                'state' => 'approved',
                'type' => $this->handler,
                'createdAt' => [ '$ne' => null ],
                'approvedAt' => [ '$ne' => null ]
            ] ],
            [ '$project' => [
                'diff' => [
                    '$subtract' => [ '$approvedAt', '$createdAt' ]
                ]
            ] ],
            [ '$group' => [
                '_id' => null,
                'count' => [ '$sum' => 1 ],
                'diffSum' => [ '$sum' => '$diff' ]
            ] ]
        ]);

        $totals = reset($result);

        return ($totals->diffSum ?: 0) / ($totals->count ?: 1);
    }

    /**
     * @param mixed $entity
     * @return boolean
     */
    public static function validateEntity($entity)
    {
        return true;
    }
}
