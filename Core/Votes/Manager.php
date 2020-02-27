<?php

/**
 * Votes Manager
 *
 * @author emi
 */

namespace Minds\Core\Votes;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\ACL;
use Minds\Entities\Factory;
use Minds\Exceptions\StopEventException;

class Manager
{
    protected $counters;
    protected $indexes;

    protected $entity;
    protected $actor;

    protected $acl;

    /** @var Dispatcher */
    protected $eventsDispatcher;

    /**
     * Manager constructor.
     */
    public function __construct($counters = null, $indexes = null, $acl = null, $eventsDispatcher = null)
    {
        $this->counters = $counters ?:  Di::_()->get('Votes\Counters');
        $this->indexes =  $indexes ?: Di::_()->get('Votes\Indexes');
        $this->acl = $acl ?: ACL::_();
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    /**
     * Casts a vote
     * @param Vote $vote
     * @param array $options
     * @return bool
     * @throws UnverifiedEmailException
     * @throws StopEventException
     */
    public function cast($vote, array $options = [])
    {
        $options = array_merge([
            'events' => true
        ], $options);

        if (!$this->acl->interact($vote->getEntity(), $vote->getActor(), "vote{$vote->getDirection()}")) {
            throw new \Exception('Actor cannot interact with entity');
        }

        $done = $this->eventsDispatcher->trigger('vote:action:cast', $vote->getEntity()->type, [
            'vote' => $vote
        ], null);

        if ($done === null) {
            //update counts
            $this->counters->update($vote);

            //update indexes
            $done = $this->indexes->insert($vote);
        }

        if ($done && $options['events']) {
            $this->eventsDispatcher->trigger('vote', $vote->getDirection(), [
                'vote' => $vote
            ]);
        }

        return $done;
    }

    /**
     * Cancels a vote
     * @param $vote
     * @param array $options
     * @return bool
     * @throws UnverifiedEmailException
     * @throws StopEventException
     */
    public function cancel($vote, array $options = [])
    {
        $options = array_merge([
            'events' => true
        ], $options);

        $done = $this->eventsDispatcher->trigger('vote:action:cancel', $vote->getEntity()->type, [
            'vote' => $vote
        ], null);

        if ($done === null) {
            //update counts
            $this->counters->update($vote, -1);

            //update indexes
            $done = $this->indexes->remove($vote);
        }

        if ($done && $options['events']) {
            $this->eventsDispatcher->trigger('vote:cancel', $vote->getDirection(), [
                'vote' => $vote
            ]);
        }

        return $done;
    }

    /**
     * Returns a boolean stating if actor voted on the entity
     * @param $vote
     * @return bool
     * @throws StopEventException
     */
    public function has($vote)
    {
        $value = $this->eventsDispatcher->trigger('vote:action:has', $vote->getEntity()->type, [
            'vote' => $vote
        ], null);

        if ($value === null) {
            $value = $this->indexes->exists($vote);
        }

        return $value;
    }

    /**
     * Toggles a vote (cancels if exists, votes if doesn't) [wrapper]
     * @param $vote
     * @param array $options
     * @return bool
     * @throws StopEventException
     * @throws UnverifiedEmailException
     */
    public function toggle($vote, array $options = [])
    {
        $options = array_merge([
            'events' => true
        ], $options);

        if (!$this->has($vote)) {
            return $this->cast($vote, $options);
        } else {
            return $this->cancel($vote, $options);
        }
    }
}
