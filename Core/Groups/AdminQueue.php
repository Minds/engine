<?php

/**
 * Minds Groups Admin Queue
 *
 * @author emi
 */

namespace Minds\Core\Groups;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Entities;
use Minds\Entities\Activity;
use Minds\Plugin;

// TODO: Migrate to new Feeds CQL
class AdminQueue
{
    /**
     * AdminQueue constructor.
     */
    public function __construct(
        protected Cassandra\Client $client,
        protected Cassandra\Scroll $scroll,
        protected EntitiesBuilder $entitiesBuilder,
        protected ACL $acl,
    ) {
    }

    /**
     * Will return posts pending review, and cleanup any deleted or unavailable posts on the fly
     * @param \Minds\Entities\Group $group
     * @param array $options - limit | offset | after
     * @return iterable<Activity>
     * @throws \Exception
     */
    public function getAll($group, array $options, &$loadNext = null): iterable
    {
        $options = array_merge([
            'limit' => 12,
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

        $query = new Cassandra\Prepared\Custom();
        $query->query($template, $values);
        $query->setOpts($cqlOpts);

        $i = 0;
        foreach ($this->scroll->request($query, $pagingToken) as $row) {
            $guid = $row['value'];
            $loadNext = base64_encode((string) $pagingToken);

            // Build the entity
            $entity = $this->entitiesBuilder->single($guid);

            // If no response, then remove from the list
            if (!$entity) {
                $this->delete($group, $guid);
                continue;
            }

            // If the ACL fails, we can also remove from from the list
            if (!$this->acl->read($entity)) {
                $this->delete($group, $guid);
                continue;
            }

            yield $entity;

            // We will iterate until we find enough items
            if (++$i > $options['limit']) {
                break;
            }
        }
    }

    /**
     * @param $group
     * @return \Cassandra\Rows
     * @throws \Exception
     */
    public function count($group)
    {
        if (!$group) {
            throw new \Exception('Group is required');
        }

        $rowKey = "group:adminqueue:{$group->getGuid()}";
        $template = "SELECT COUNT(*) FROM entities_by_time WHERE key = ?";
        $values = [ $rowKey ];

        $query = new Cassandra\Prepared\Custom();
        $query->query($template, $values);

        return $this->client->request($query);
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

        $query = new Cassandra\Prepared\Custom();
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
     * @param Entities\Activity|string $activityOrGuid
     * @return bool
     * @throws \Exception
     */
    public function delete($group, $activityOrGuid): bool
    {
        if (!$group) {
            throw new \Exception('Group is required');
        }

        if (!($activityOrGuid || $activityOrGuid instanceof Activity)) {
            throw new \Exception('Activity is required');
        }

        if ($activityOrGuid instanceof Activity && $activityOrGuid->container_guid != $group->getGuid()) {
            throw new \Exception('Activity doesn\'t belong to this group');
        }

        $rowKey = "group:adminqueue:{$group->getGuid()}";

        $query = new Cassandra\Prepared\Custom();
        $query->query(
            "DELETE FROM entities_by_time WHERE key = ? AND column1 = ?",
            [
                $rowKey,
                is_string($activityOrGuid) ? $activityOrGuid : (string) $activityOrGuid->guid,
            ]
        );

        $success = $this->client->request($query);

        return (bool) $success;
    }
}
