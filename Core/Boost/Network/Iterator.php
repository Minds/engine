<?php

namespace Minds\Core\Boost\Network;

use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Block;
use MongoDB\BSON\ObjectID;
use Minds\Entities\Boost;

class Iterator implements \Iterator
{
    /** @var ElasticRepository */
    protected $elasticRepository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Expire */
    protected $expire;

    /** @var Metrics */
    protected $metrics;

    /** @var Manager */
    protected $manager;

    /** @var Block\Manager */
    protected $blockManager;

    /** @var int */
    protected $rating = 1;

    /** @var int */
    protected $quality = 0;

    /** @var string */
    protected $offset = null;

    /** @var int */
    protected $limit = 1;

    /** @var string */
    protected $type = 'newsfeed'; // newsfeed, content

    /** @var bool */
    protected $priority = false;

    /** @var string[] */
    protected $categories = null;

    /** @var bool */
    protected $increment = false;

    /** @var bool */
    protected $hydrate = true;

    /** @var int */
    protected $tries = 0;

    /** @var array */
    public $list = null;

    /** @var int */
    const MONGO_LIMIT = 50;

    public function __construct(
        $elasticRepository = null,
        $entitiesBuilder = null,
        $expire = null,
        $metrics = null,
        $manager = null,
        $blockManager = null
    ) {
        $this->elasticRepository = $elasticRepository ?: new ElasticRepository;
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->expire = $expire ?: Di::_()->get('Boost\Network\Expire');
        $this->metrics = $metrics ?: Di::_()->get('Boost\Network\Metrics');
        $this->manager = $manager ?: new Manager;
        $this->blockManager = $blockManager ?? Di::_()->get('Security\Block\Manager');
    }

    public function setRating($rating)
    {
        $this->rating = (int) $rating;
        return $this;
    }

    public function setQuality($quality)
    {
        $this->quality = (int) $quality;
        return $this;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function setLimit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    public function setType($type)
    {
        if ($type === 'newsfeed' || $type === 'content') {
            $this->type = $type;
        }
        return $this;
    }

    public function setPriority($priority)
    {
        $this->priority = (bool) $priority;
        return $this;
    }

    public function setCategories($categories)
    {
        $this->categories = $categories;
        return $this;
    }

    public function setIncrement($increment)
    {
        $this->increment = (bool) $increment;
        return $this;
    }

    /**
     * @param bool $hydrate
     * @return Iterator
     */
    public function setHydrate($hydrate)
    {
        $this->hydrate = $hydrate;
        return $this;
    }

    public function getList()
    {
        $match = [
            'type' => $this->type,
            'state' => 'approved',
            'rating' => [
                //'$exists' => true,
                '$lte' => $this->rating ? $this->rating : (int) Core\Session::getLoggedinUser()->getBoostRating()
            ],
            'quality' => [
                '$gte' => $this->quality
            ]
        ];

        $sort = ['_id' => 1];

        if ($this->priority) {
            $sort = ['priority' => -1, '_id' => 1];
        }

        $boosts = $this->elasticRepository->getList([
            'type' => $this->type,
            'limit' => self::MONGO_LIMIT,
            'offset' => $this->offset,
            'state' => 'approved',
            'rating' => $this->rating ? $this->rating : (int) Core\Session::getLoggedinUser()->getBoostRating(),
        ]);

        if (!$boosts) {
            return null;
        }

        $return = [];
        $i = 0;
        $declareOffsetFrom = $this->limit >= 2 ? 2 : 1;
        foreach ($boosts as $boost) {
            if (count($return) >= $this->limit) {
                break;
            }

            if (++$i === $declareOffsetFrom) {
                $boosts->setPagingToken($boost->getCreatedTimestamp());
            }

            if ($this->hydrate) {
                $impressions = $boost->getImpressions();
                $count = 0;

                $boost->setEntity($this->entitiesBuilder->single($boost->getEntityGuid()));

                if ($this->increment) {
                    $count = $this->metrics->incrementViews($boost);
                }

                if ($count > $impressions) {
                    // Grab the main storage to prevent issues with elastic formatted data
                    $boost = $this->manager->get("urn:boost:{$boost->getType()}:{$boost->getGuid()}", [
                        'hydrate' => true,
                    ]);
                    $this->expire->setBoost($boost);
                    $this->expire->expire();
                    continue; //max count met
                }

                if ($boost->getEntity()) {
                    $return[$boost->getGuid()] = $boost->getEntity();
                }
            } else {
                $return[] = $boost;
            }
        }

        $this->offset = $boosts->getPagingToken();

        if ($this->hydrate) {
            if (empty($return) && $this->tries++ <= 1) {
                $this->offset = 0;
                return $this->getList();
            }

            $return = $this->filterBlocked($return);
        }

        $this->list = $return;
        return $return;
    }

    /**
     * Gets a single boost entity
     * @param  mixed $guid
     * @return object
     */
    private function getBoostEntity($guid)
    {
        /** @var Core\Boost\Repository $repository */
        $repository = Core\Di\Di::_()->get('Boost\Repository');
        return $repository->getEntity($this->type, $guid);
    }

    /**
     * Polyfills boost thumbs
     * @param  string[] $boosts
     * @return string[]
     */
    private function patchThumbs($boosts)
    {
        $keys = [];
        /** @var Boost\Network $boost */
        foreach ($boosts as $boost) {
            $keys[] = "thumbs:up:entity:$boost->guid";
        }
        $db = new Data\Call('entities_by_time');
        $thumbs = $db->getRows($keys, [
            'offset' => Core\Session::getLoggedInUserGuid(),
            'limit' => 1,
        ]);
        foreach ($boosts as $k => $boost) {
            $key = "thumbs:up:entity:$boost->guid";
            if (isset($thumbs[$key])) {
                $boosts[$k]->{'thumbs:up:user_guids'} = array_keys($thumbs[$key]);
            }
        }
        return $boosts;
    }

    /**
     * Filters boosts of channels that user has blocked
     * @param array $boosts
     * @return array
     */
    private function filterBlocked($boosts)
    {
        foreach ($boosts as $i => $boost) {
            $blockEntry = (new Block\BlockEntry())
                ->setActorGuid(Core\Session::getLoggedInUserGuid())
                ->setSubjectGuid($boost->owner_guid);

            if ($this->blockManager->hasBlocked($blockEntry)) {
                unset($boosts[$i]);
            }
        }

        return $boosts;
    }

    public function current()
    {
        return current($this->list);
    }

    public function next()
    {
        next($this->list);
    }

    public function key()
    {
        return key($this->list);
    }

    public function valid()
    {
        if (!$this->list) {
            return false;
        }
        return key($this->list) !== null;
    }

    public function rewind()
    {
        if ($this->list) {
            reset($this->list);
        }
        $this->getList();
    }
}
