<?php
/**
* Groups notifications
*/
namespace Minds\Core\Groups;

use Minds\Core\Security;
use Minds\Core\Di\Di;
use Minds\Core\Queue;
use Minds\Entities;
use Minds\Helpers\Counters;
use Minds\Core\Data\Cassandra\Prepared;

use Minds\Behaviors\Actorable;

use Minds\Exceptions\GroupOperationException;

class Notifications
{
    use Actorable;

    protected $relDB;
    protected $indexDB;
    protected $cql;
    protected $group;

    /**
     * Constructor
     */
    public function __construct($relDb = null, $indexDb = null, $cql = null, $notifications = null)
    {
        $this->relDB = $relDb ?: Di::_()->get('Database\Cassandra\Relationships');
        $this->indexDb = $indexDb ?: Di::_()->get('Database\Cassandra\Indexes');
        $this->cql = $cql ?: Di::_()->get('Database\Cassandra\Cql');
        $this->notifications = $notifications ?: Di::_()->get('Notification\Repository');
    }

    /**
     * Set the group
     * @param Entities\Group $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Queues a new Group notification
     * @param  Entities\Activity|array $activity
     * @return mixed
     */
    public function queue($activity)
    {
        $me = $activity['ownerObj']['guid'];

        return Queue\Client::build()
            ->setExchange('mindsqueue')
            ->setQueue('GroupsNotificationDispatcher')
            ->send([
                'entity' => $this->group->getGuid(),
                'params' => [
                    'activity' => is_object($activity) ? $activity->guid : $activity['guid'],
                    'exclude' => [ $me ]
                ]
            ]);
    }

    /**
     * Sends a Group notification for a certain activity
     * @param  array $params
     */
    public function send($params)
    {
        $activity = Entities\Factory::build($params['activity']);
        if (!$activity) {
            return false;
        }
        //generate only one notification, because it's quicker that way
        $notification = (new Entities\Notification())
            ->setTo($activity->getOwner())
            ->setEntity($activity)
            ->setFrom($activity->getOwner())
            ->setOwner($activity->getOwner())
            ->setNotificationView('group_activity')
            ->setDescription($activity->message)
            ->setParams(['group' => $this->group->export() ])
            ->setTimeCreated(time());
        $serialized = json_encode($notification->export());

        $offset = "";
        $from_user = $notification->getFrom();

        while (true) {
            echo "[notification][group][$activity->container_guid]: Running from $offset \n";

            $guids = $this->getRecipients([
                'exclude' => $params['exclude'] ?: [],
                'limit' => 500,
                'offset' => $offset
            ]);

            if ($offset) {
                array_shift($guids);
            }

            if (!$guids) {
                break;
            }

            if ($guids[0] == $offset) {
                break;
            }

            $offset = end($guids);

            $i = 0;
            foreach ($guids as $recipient) {
                $i++;
                $pct = ($i / count($guids)) * 100;
                echo "[notification]: $i / " . count($guids) . " ($pct%) ";

                //if ($from_user->guid && Security\ACL\Block::_()->isBlocked($from_user, $recipient)) {
                //    continue;
                //}

                $this->notifications->setOwner($recipient);
                $this->notifications->store($notification->export());

                echo " (dispatched) \r";
            }

            //now update the counters for each user
            echo "\n[notification]: incrementing counters ";
            Counters::incrementBatch($guids, 'notifications:count');
            echo " (done) \n";

            if (!$offset) {
                break;
            }
        }
        echo "[notification]: Dispatch complete for $activity->guid \n";
    }

    /**
     * Gets Group notification recipients.
     * @param  array $opts
     * @return array
     */
    public function getRecipients(array $opts = [])
    {
        $opts = array_merge([
            'exclude' => [],
            'offset' => "",
            'limit' => 12
        ], $opts);

        $this->relDB->setGuid($this->group->getGuid());

        $guids = $this->relDB->get('member', [
            'inverse' => true,
            'offset' => $opts['offset'],
            'limit' => $opts['limit']
        ]);

        $guids = array_map([ $this, 'toString' ], $guids);
        $exclude = array_unique(array_map([ $this, 'toString' ], $opts['exclude']));

        $mutedRows = $this->getMutedMembers(10000);

        foreach ($mutedRows as $muted) {
            $muted_guid = $muted['column1'];
            if (($index = array_search($muted_guid, $guids)) === false) {
                continue;
            }

            unset($guids[$index]);
        }

        return array_values(array_diff($guids, $exclude));
    }

    /**
     * Gets the GUIDs of muted members
     * @return array
     */
    public function getMutedMembers($limit = 10000)
    {
        $query = 'SELECT * from relationships WHERE key = ? LIMIT ?';
        $values = [ "{$this->group->getGuid()}:group:muted:inverted", (int) $limit ];

        $prepared = new Prepared\Custom();
        $prepared->query($query, $values);

        return $this->cql->request($prepared);
    }

    /**
     * Gets the mute status for passed members
     * @param  array   $users
     * @return array
     */
    public function isMutedBatch(array $users = [])
    {
        if (!$users) {
            return [];
        }

        $mutedRows = $this->getMutedMembers(10000);
        $result = [];

        foreach ($users as $user) {
            $result[(string) $user] = false;
        }

        foreach ($mutedRows as $muted) {
            $muted_guid = $muted['column1'];
            if (!isset($result[$muted_guid])) {
                continue;
            }

            $result[$muted_guid] = true;
        }

        return $result;
    }

    /**
     * Returns if a member has the group muted
     * @param  mixed $user
     * @return boolean
     */
    public function isMuted($user)
    {
        if (!$user) {
            return false;
        }

        $user_guid = is_object($user) ? $user->guid : $user;
        $this->relDB->setGuid($user_guid);

        return $this->relDB->check('group:muted', $this->group->getGuid());
    }

    /**
     * Adds an user to the muted Index list
     * @param  mixed $user
     * @return boolean
     */
    public function mute($user)
    {
        if (!$user) {
            throw new GroupOperationException('User not found');
        }

        $user_guid = is_object($user) ? $user->guid : $user;
        $this->relDB->setGuid($user_guid);

        $done = $this->relDB->create('group:muted', $this->group->getGuid());

        if ($done) {
            return true;
        }

        throw new GroupOperationException('Error muting group');
    }

    /**
     * Removes an user from the muted Index list
     * @param  mixed $user
     * @return boolean
     */
    public function unmute($user)
    {
        if (!$user) {
            throw new GroupOperationException('User not found');
        }

        $user_guid = is_object($user) ? $user->guid : $user;
        $this->relDB->setGuid($user_guid);

        $done = $this->relDB->remove('group:muted', $this->group->getGuid());

        if ($done) {
            return true;
        }

        throw new GroupOperationException('Error unmuting group');
    }

    /**
     * Internal funcion. Typecasts to string.
     * @param  mixed $var
     * @return string
     */
    private function toString($var)
    {
        return (string) $var;
    }
}
