<?php

/**
 * Minds Groups Admin Queue
 *
 * @author emi
 */

namespace Minds\Core\Groups;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Entities;
use Minds\Plugin;
use Minds\Core\Feeds\Elastic\Manager;

// TODO: Migrate to new Feeds CQL
class AdminQueue
{
    /** @var Core\Data\Cassandra\Client $client */
    protected $client;

     	    /** @var Core\Feeds\Elastic\Manager; $elasticManager */
             protected $elasticManager;

    /**
     * AdminQueue constructor.
     * @param Core\Data\Cassandra\Client $db
     */
    public function __construct($db = null, $elasticManager = null)
    {
        $this->client = $db ?: Di::_()->get('Database\Cassandra\Cql');
        $this->elasticManager = $elasticManager ?: new Manager();    
    }

    /**
     * @param \Minds\Entities\Group $group
     * @param array $options - limit | offset | after
     * @return \Cassandra\Rows
     * @throws \Exception
     */
    public function getAll($group, array $options = [])
    {
        $options = array_merge([
            'limit' => null,
            'offset' => '',
            'after' => ''
        ], $options);

        if (!$group) {
            throw new \Exception('Group is required');
        }

        $rowKey = "group:adminqueue:{$group->getGuid()}";
        $template = "SELECT * FROM entities_by_time WHERE key = ?";
        $values = [ $rowKey ];
        $cqlOpts = [];
        $allowFiltering = false;

        if ($options['after']) {
            $template .= " AND column1 > ?";
            $values[] = (string) $options['after'];
            $allowFiltering = true;
        }

        if ($allowFiltering) {
            $template .= " ALLOW FILTERING";
        }

        if ($options['limit']) {
            $cqlOpts['page_size'] = (int) $options['limit'];
        }

        if ($options['offset']) {
            $cqlOpts['paging_state_token'] = base64_decode($options['offset'], true);
        }

        $query = new Prepared\Custom();
        $query->query($template, $values);
        $query->setOpts($cqlOpts);

        return $this->client->request($query);
    }

    /**
     * Counts amount of items in a groups moderation queue.
     * 
     * @param mixed $group - group entity
     * @param array options - options for query.
     * @return int - the amount of documents found.
     */
    public function count($group = null, array $options = []): int
    {
        $options = array_merge([
            'limit' => null,
            'offset' => 0,
            'pending' => true,
            'type' => 'activity',
            'algorithm' => 'latest',
            'period' => '1y',
            'single_owner_threshold' => 0,
            'access_id' => [2, $group->guid],
            'from_timestamp' => 0,
            'container_guid' => $group->guid,
            'as_activities' => true,
            'count' => true,
        ], $options);

        return $this->elasticManager->count($options);
    }


    /**
     * @param \Minds\Entities\Group $group
     * @param Entities\Activity $activity
     * @return bool
     * @throws \Exception
     */
    public function add($group, $activity)
    {
        if (!$group) {
            throw new \Exception('Group is required');
        }

        if (!$activity || !$activity->guid) {
            throw new \Exception('Activity is required');
        }

        if ($activity->container_guid != $group->getGuid()) {
            throw new \Exception('Activity doesn\'t belong to this group');
        }

        $rowKey = "group:adminqueue:{$group->getGuid()}";

        $query = new Prepared\Custom();
        $query->query(
            "INSERT INTO entities_by_time (key, column1, value) VALUES (?, ?, ?)",
            [
                $rowKey,
                (string) $activity->guid,
                (string) $activity->guid
            ]
        );

        $success = $this->client->request($query);

        return (bool) $success;
    }

    /**
     * @param \Minds\Entities\Group $group
     * @param Entities\Activity $activity
     * @return bool
     * @throws \Exception
     */
    public function delete($group, $activity)
    {
        if (!$group) {
            throw new \Exception('Group is required');
        }

        if (!$activity || !$activity->guid) {
            throw new \Exception('Activity is required');
        }

        if ($activity->container_guid != $group->getGuid()) {
            throw new \Exception('Activity doesn\'t belong to this group');
        }

        $rowKey = "group:adminqueue:{$group->getGuid()}";

        $query = new Prepared\Custom();
        $query->query(
            "DELETE FROM entities_by_time WHERE key = ? AND column1 = ?",
            [
                $rowKey,
                (string) $activity->guid
            ]
        );

        $success = $this->client->request($query);

        return (bool) $success;
    }
}
