<?php
namespace Minds\Core\Boost;

use Minds\Interfaces\BoostHandlerInterface;
use Minds\Core;
use Minds\Core\Data;
use Minds\Entities;
use Minds\Helpers;

/**
 * Newsfeed Boost handler
 */
class Network implements BoostHandlerInterface
{

    protected $handler = 'newsfeed';
    protected $mongo;
    protected $db;

    public function __construct($options = array(),
      Data\Interfaces\ClientInterface $mongo = null,
      Data\Call $db = null)
    {
        if ($mongo) {
            $this->mongo = $mongo;
        } else {
            $this->mongo = Data\Client::build('MongoDB');
        }

        if ($db) {
            $this->db = $db;
        } else {
            $this->db = new Data\Call('entities_by_time');
        }
    }

    /**
     * Boost an entity
     * @param object/int $entity - the entity to boost
     * @param int $impressions
     * @return boolean
     */
    public function boost($boost, $impressions = 0)
    {

        $this->mongo->insert("boost", $data = [
          'guid' => $boost->getGuid(),
          'owner_guid' => $boost->getOwner()->guid,
          'impressions' => $boost->getBid(),
          'state' => 'review',
          'type' => $this->handler
        ]);

        if(isset($data['_id']))
            return (string) $data['_id'];

        return $boost->getGuid();

    }

     /**
     * Return boosts for review
     * @param int $limit
     * @param string $offset
     * @return array
     */
    public function getReviewQueue($limit, $offset = "")
    {

        $query = [ 'state'=>'review', 'type'=> $this->handler ];
        if ($offset) {
            $query['_id'] = [ '$gt' => $offset ];
        }
        $queue = $this->mongo->find("boost", $query);
        $queue->limit($limit);
        $queue->sort(array('_id'=> 1));

        if (!$queue) {
          return false;
        }

        $guids = [];
        $end = "";
        foreach ($queue as $data) {
            $_id = (string) $data['_id'];
            $guids[$_id] = $data['guid'];
            $end = $data['guid'];
            //$this->mongo->remove("boost", ['_id' => $_id]);
        }

        if(!$guids){
          return false;
        }

        $db = new Data\Call('entities_by_time');
        $data = $db->getRow("boost:$this->handler", [
          'limit'=>$limit,
          'offset'=> $end,
          'reversed'=>true
        ]);

        $data = array_reverse($data); // oldest to newest

        $boosts = [];
        foreach ($data as $guid => $raw_data) {
            //$raw_data['guid']
            $boost = (new Entities\Boost\Network())
              ->loadFromArray(json_decode($raw_data, true));
            //double check
            if(isset($guids[$boost->getId()])){
                $boosts[] = $boost;
            }
        }
        return $boosts;
    }

    /**
     * Return the review count
     * @return int
     */
    public function getReviewQueueCount()
    {
        $query = ['state' => 'review', 'type' => $this->handler];
        $count = $this->mongo->count("boost", $query);
        return $count;
    }

    /**
     * Get our own submitted Boosts
     * @param int $limit
     * @param string $offset
     * @return array
     */
    public function getOutbox($limit, $offset = "")
    {
        $db = new Data\Call('entities_by_time');
        $data = $db->getRow("boost:$this->handler:requested:" . Core\Session::getLoggedinUser()->guid, [
          'limit'=>$limit,
          'offset'=>$offset,
          'reversed'=>true
        ]);

        $boosts = [];
        foreach ($data as $guid => $raw_data) {
            //$raw_data['guid']
            $boosts[] = (new Entities\Boost\Network())
              ->loadFromArray(json_decode($raw_data, true));
        }
        return $boosts;
    }

    public function getBoostEntity($guid)
    {
        $db = new Data\Call('entities_by_time');
        $data = $db->getRow("boost:$this->handler", ['limit'=>1, 'offset'=>$guid]);
        if (key($data) != $guid) {
            return false;
        }

        $boost = (new Entities\Boost\Network($db))
          ->loadFromArray(json_decode($data[$guid], true));
        return $boost;
    }

    /**
     * Accept a boost
     * @param mixed $_id
     * @param int impressions
     * @return boolean
     */
    public function accept($boost, $impressions = 0)
    {

        $accept = $this->mongo->update("boost", ['_id' => $boost->getId()], ['state'=>'approved']);
        $boost->setState('approved');
        if ($accept) {
            //remove from review
            //$db->removeAttributes("boost:newsfeed:review", array($guid));
            //clear the counter for boost_impressions
            //Helpers\Counters::clear($guid, "boost_impressions");

            Core\Events\Dispatcher::trigger('notification', 'boost', [
                'to'=> [ $boost->getOwner()->guid ],
                'entity' => $boost,
                'from'=> 100000000000000519,
                'title' => $boost->getEntity()->title,
                'notification_view' => 'boost_accepted',
                'params' => ['impressions' => $boost->getBid()],
                'impressions' => $boost->getBid()
              ]);
            $boost->save();
        }
        return $accept;
    }

    /**
     * Reject a boost
     * @param mixed $_id
     * @return boolean
     */
    public function reject($boost)
    {
        $this->mongo->remove("boost", ['_id'=>$boost->getId()]);
        $boost->setState('rejected')
          ->save();

        Core\Events\Dispatcher::trigger('notification', 'boost', [
            'to'=> [ $boost->getOwner()->guid ],
            'from'=> 100000000000000519,
            'entity' => $boost,
            'title' => $boost->getEntity()->title,
            'notification_view' => 'boost_rejected',
        ]);
        return true;//need to double check somehow..
    }

    /**
     * Return a boost
     * @return array
     */
    public function getBoost($offset = "")
    {
        $boosts = $this->getBoosts(1);
        return $boosts[0];
    }

    /**
     * Expire a boost from the queue
     * @param Object $boost
     * @return void
     */
    private function expireBoost($boost)
    {
        if(!$boost){
            return;
        }

        $boost->setState('completed')
          ->save();

        $this->mongo->remove("boost", [ '_id' => $boost->getId() ]);

        Core\Events\Dispatcher::trigger('notification', 'boost', [
          'to'=> [ $boost->getOwner()->guid ],
          'from'=> 100000000000000519,
          'entity' => $boost,
          'title' => $boost->getEntity()->title,
          'notification_view' => 'boost_completed',
          'params' => ['impressions' => $boost->getBid()],
          'impressions' => $boost->getBid()
        ]);
    }

    public function getBoosts($limit = 2)
    {
        $cacher = Core\Data\cache\factory::build('apcu');
        $mem_log =  $cacher->get(Core\Session::getLoggedinUser()->guid . ":seenboosts") ?: [];

        $boosts = $this->mongo->find("boost", ['type'=>$this->handler, 'state'=>'approved']);

        if (!$boosts) {
            return null;
        }
        $boosts->sort(['_id'=> 1]);
        $boosts->limit(30);
        $return = [];
        foreach ($boosts as $data) {
            if (count($return) >= $limit) {
                break;
            }
            if (in_array((string) $data['_id'], $mem_log)) {
                continue; // already seen
            }

            $impressions = $data['impressions'];
            //increment impression counter
            Helpers\Counters::increment((string) $data['_id'], "boost_impressions", 1);
            //get the current impressions count for this boost
            Helpers\Counters::increment(0, "boost_impressions", 1);
            $count = Helpers\Counters::get((string) $data['_id'], "boost_impressions", false);

            $boost = $this->getBoostEntity($data['guid']);
            $legacy_boost = false;
            if(!$boost){
                $entity = new \Minds\Entities\Activity($data['guid']);
                $legacy_boost = true;
            }

            if ($count > $impressions) {

                if($legacy_boost){
                    $this->mongo->remove("boost", ['_id' => $boost['_id']]);
                    Core\Events\Dispatcher::trigger('notification', 'boost', [
                      'to'=>array($entity->owner_guid),
                      'from'=> 100000000000000519,
                      'entity' => $entity,
                      'title' => $entity->title,
                      'notification_view' => 'boost_completed',
                      'params' => array('impressions'=>$boost['impressions']),
                      'impressions' => $boost['impressions']
                    ]);
                } else {
                    $this->expireBoost($boost);
                }

                continue; //max count met
            }
            array_push($mem_log, (string) $data['_id']);
            $cacher->set(Core\Session::getLoggedinUser()->guid . ":seenboosts", $mem_log, (12 * 3600));

            if($legacy_boost){
                $return[] = $entity;
            } else {
                $return[] = $boost->getEntity();
            }
        }
        return $return;
    }
}
