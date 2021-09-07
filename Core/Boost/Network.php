<?php
namespace Minds\Core\Boost;

use Minds\Interfaces\BoostHandlerInterface;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Data;
use Minds\Entities;
use Minds\Helpers;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Notifications;

/**
 * Newsfeed Boost handler
 * @todo Proper DI
 */
class Network implements BoostHandlerInterface
{
    protected $handler = 'newsfeed';

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Notifications\Manager */
    protected $notificationsManager;

    public function __construct(EntitiesBuilder $entitiesBuilder = null, Notifications\Manager $notificationsManager = null)
    {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->notificationsManager = $notificationsManager ?? Di::_()->get('Notifications\Manager');
    }

    /**
     * Boost an entity
     * @param  object/int $entity - the entity to boost
     * @param  int $impressions
     */
    public function boost($boost, $impressions = 0)
    {
        return false;
    }

    public function accept($entity, $impressions)
    {
        return false;
    }

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

        //
        $notification = new Notifications\Notification();

        $notification->setType(Notifications\NotificationTypes::TYPE_BOOST_COMPLETED);
        $notification->setToGuid($boost->getOwnerGuid());
        $notification->setEntityUrn($boost->getUrn());

        $this->notificationsManager->add($notification);
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
    }

    public function getPriorityBacklogCount()
    {
    }

    public function getBacklogImpressionsSum()
    {
    }

    public function getAvgApprovalTime()
    {
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
