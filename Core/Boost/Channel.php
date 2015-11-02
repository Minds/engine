<?php

namespace Minds\Core\Boost;

use Minds\Core;
use Minds\Core\Data;
use Minds\Interfaces;
use Minds\Entities;
use Minds\Helpers;

/**
 * Channel boost handler
 */
class Channel implements Interfaces\BoostHandlerInterface
{
    private $guid;

    public function __construct($options)
    {
        if (isset($options['destination'])) {
            if (is_numeric($options['destination'])) {
                $this->guid = $options['destination'];
            } elseif (is_string($options['destination'])) {
                $lookup = new Data\lookup();
                $this->guid = key($lookup->get(strtolower($options['destination'])));
            }
        }
    }

   /**
     * Boost an entity
     * @param object/int $entity - the entity to boost
     * @param int $points
     * @return boolean
     */
    public function boost($entity, $points)
    {
        if (is_object($entity)) {
            $guid = $entity->guid;
        } else {
            $guid = $entity;
        }

        $db = new Data\Call('entities_by_time');
        $result = $db->insert("boost:channel:$this->guid:review", array($guid => $points));

        //send a notification of boost offer
        Core\Events\Dispatcher::trigger('notification', 'elgg/hook/activity', array(
                'to' => array($this->guid),
                'object_guid' => $guid,
                'notification_view' => 'boost_request',
                'params' => array('points'=>$points),
                'points' => $points
                ));

        //send back to use
        Core\Events\Dispatcher::trigger('notification', 'elgg/hook/activity', array(
                'to'=>array(Core\Session::getLoggedinUser()->guid),
                'object_guid' => $guid,
                'notification_view' => 'boost_submitted_p2p',
                'params' => array(
                    'points' => $points,
                    'channel' => isset($_POST['destination']) ? $_POST['destination'] : $this->guid
                 ),
                'points' => $points
                ));

        return $result;
    }

     /**
     * Return boosts for review
     * @param int $limit
     * @param string $offset
     * @return array
     */
    public function getReviewQueue($limit, $offset = "")
    {
        $db = new Data\Call('entities_by_time');
        $guids = $db->getRow("boost:channel:$this->guid:review", array('limit'=>$limit, 'offset'=>$offset, 'reversed'=>false));
        return $guids;
    }

    /**
     * Accept a boost and do a remind
     * @param object/int $entity
     * @param int points
     * @return boolean
     */
    public function accept($entity, $points)
    {
        if (is_object($entity)) {
            $guid = $entity->guid;
        } else {
            $guid = $entity;
        }

        $db = new Data\Call('entities_by_time');


        $embeded = new Entities\Entity($guid);
        $embeded = core\Entities::build($embeded); //more accurate, as entity doesn't do this @todo maybe it should in the future
        \Minds\Helpers\Counters::increment($guid, 'remind');

        $activity = new Entities\Activity();
        $activity->p2p_boosted = true;
        switch ($embeded->type) {
            case 'activity':
                if ($embeded->remind_object) {
                    $activity->setRemind($embeded->remind_object)->save();
                } else {
                    $activity->setRemind($embeded->export())->save();
                }
            break;
            case 'object':



            break;
            default:
                 /**
                   * The following are actually treated as embeded posts.
                   */
                   switch ($embeded->subtype) {
                       case 'blog':
                           $message = false;
                            if ($embeded->owner_guid != elgg_get_logged_in_user_guid()) {
                                $message = 'via <a href="'.$embeded->getOwnerEntity()->getURL() . '">'. $embeded->getOwnerEntity()->name . '</a>';
                            }
                                $activity->p2p_boosted = true;
                $activity->setTitle($embeded->title)
                                ->setBlurb(elgg_get_excerpt($embeded->description))
                                ->setURL($embeded->getURL())
                                ->setThumbnail($embeded->getIconUrl())
                                ->setMessage($message)
                                ->setFromEntity($embeded)
                                ->save();
                            break;
                    }
        }

        //remove from review
        error_log('user_guid is ' . $this->guid);
        $db->removeAttributes("boost:channel:$this->guid:review", array($guid));
        $db->removeAttributes("boost:channel:all:review", array("$this->guid:$guid"));

        $entity = new \Minds\Entities\Activity($guid);
        Core\Events\Dispatcher::trigger('notification', 'elgg/hook/activity', array(
            'to'=>array($entity->owner_guid),
            'object_guid' => $guid,
            'title' => $entity->title,
            'notification_view' => 'boost_accepted',
            'params' => array('points'=>$points),
            'points' => $points
            ));
        return true;
    }

    /**
     * Reject a boost
     * @param object/int $entity
     * @return boolean
     */
    public function reject($entity)
    {

        ///
        /// REFUND THE POINTS TO THE USER
        ///


        if (is_object($entity)) {
            $guid = $entity->guid;
        } else {
            $guid = $entity;
        }
        $db = new Data\Call('entities_by_time');
        $db->removeAttributes("boost:channel:$this->guid:review", array($guid));
        $db->removeAttributes("boost:channel:all:review", array("$this->guid:$guid"));

        $entity = new \Minds\Entities\Activity($guid);
        Core\Events\Dispatcher::trigger('notification', 'elgg/hook/activity', array(
            'to'=>array($entity->owner_guid),
            'object_guid' => $guid,
            'title' => $entity->title,
            'notification_view' => 'boost_rejected',
            ));
        return true;//need to double check somehow..
    }

    /**
     * Return a boost
     * @return array
     */
    public function getBoost($offset = "")
    {

       ///
       //// THIS DOES NOT APPLY BECAUSE IT'S PRE-AGREED
       ///
    }

    public function autoExpire()
    {
        $db = new Data\Call('entities_by_time');
        $boosts = $db->getRow("boost:channel:all:review");
        foreach ($boosts as $boost => $ts) {
            list($destination, $guid) = explode(':', $boost);
            if (time() > $ts + (3600 * 48)) {
                $this->guid = $destination;
                $guids = $this->getReviewQueue(1, $guid);
                $points = reset($guids);


                if (!$destination) {
                    echo "$guid issue with destination.. \n";
                    continue;
                }
                echo "$guid has expired. refunding ($points) points to $destination \n";

                $db->removeAttributes("boost:channel:all:review", array($boost));
                $db->removeAttributes("boost:channel:$destination:review", array($guid));

                $entity =  new \Minds\Entities\Activity($guid);

                Helpers\Wallet::createTransaction($entity->owner_guid, $points, $guid, "boost refund");

                Core\Events\Dispatcher::trigger('notification', 'elgg/hook/activity', array(
                    'to'=>array($entity->owner_guid),
                    'from'=> $destination,
                    'object_guid' => $guid,
                    'title' => $entity->title,
                    'notification_view' => 'boost_rejected',
                    ));
            } else {
                echo "$guid is ok... \n";
            }
        }
    }
}
