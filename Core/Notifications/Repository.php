<?php
/**
 * Notifications Repository.
 */

namespace Minds\Core\Notifications;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Di\Di;
use Minds\Common\Repository\Response;
use Cassandra\Bigint;
use Cassandra\Set as CassandraSet;
use Cassandra\Timeuuid;
use Cassandra\Timestamp;
use Cassandra\Type\Set;
use Minds\Common\Urn;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Core\Data\Cassandra\Scroll;

class Repository
{
    /** @var int */
    const NOTIFICATION_TTL = ((60 * 60) * 24) * 30; // 30 days

    /** @var Client */
    private $cql;

    /** @var Scroll  */
    private $scroll;

    /** @var Urn */
    private $urn;

    public function __construct(Client $cql = null, Scroll $scroll = null, Urn $urn = null)
    {
        $this->cql = $cql ?: Di::_()->get('Database\Cassandra\Cql');
        $this->scroll = $scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
        $this->urn = $urn ?: new Urn;
    }

    /**
     * Get a list of notifications.
     * @param NotificationsListOpts $opts
     * @return iterable
     */
    public function getList(NotificationsListOpts $opts): iterable
    {
        if (!$opts->getToGuid()) {
            throw new \Exception('to_guid must be provided');
        }

        $statement = "SELECT * FROM notifications_mergeable
            WHERE to_guid = ?";

        $values = [
            new Bigint($opts->getToGuid()),
        ];

        if ($opts->getUuid()) {
            $statement .= " AND uuid = ?";
            $values[] = new Timeuuid($opts->getUuid());
        }

        if ($opts->getGroupingType()) {
            $statement = "SELECT * FROM notifications_mergeable_by_type_group
                WHERE to_guid = ?
                AND type_group = ?";
            $values[] = $opts->getGroupingType();
        }

        if ($opts->getLteUuid()) {
            $statement .= " AND uuid <= ?";
            $values[] = new Timeuuid($opts->getLteUuid());
        }

        $query = new Prepared\Custom();
        $query->query($statement, $values);
        $query->setOpts([
            'page_size' => (int) $opts->getLimit(),
            'paging_state_token' => base64_decode($opts->getOffset(), true),
        ]);

        try {
            $pagingToken = ''; // Will be changed by scroll pass by reference
            foreach ($this->scroll->request($query, $pagingToken) as $row) {
                $notification = new Notification();
                $notification
                    ->setUuid($row['uuid']->uuid() ?: null)
                    ->setToGuid(isset($row['to_guid']) ? (int) $row['to_guid']->value() : null)
                    ->setFromGuid(isset($row['from_guid']) ? (int) $row['from_guid']->value() : null)
                    ->setEntityUrn($row['entity_urn'])
                    ->setCreatedTimestamp($row['created_timestamp'] ? $row['created_timestamp']->time() : null)
                    ->setReadTimestamp($row['read_timestamp'] ? $row['read_timestamp']->time() : null)
                    ->setType($row['type'])
                    ->setData(json_decode($row['data'], true));

                yield [ $notification, $pagingToken ];
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get a single notification.
     * @param $urn
     * @return Notification
     */
    public function get($urn): ?Notification
    {
        list($toGuid, $uuid) = explode('-', $this->urn->setUrn($urn)->getNss(), 2);

        if (!$uuid) {
            return null; // Should we throw invalid urn?
        }

        $opts = new NotificationsListOpts();
        $opts->setLimit(1)
            ->setToGuid($toGuid)
            ->setUuid($uuid);

        $response = iterator_to_array($this->getList($opts));

        if (empty($response)) {
            return null;
        }

        return $response[0][0];
    }


    /**
     * Add a notification to the database.
     *
     * @param Notification $notification
     * @return bool
     */
    public function add($notification): bool
    {
        $statement = 'INSERT INTO notifications_mergeable (
            to_guid,
            uuid,
            type,
            type_group,
            from_guid,
            entity_urn,
            created_timestamp,
            read_timestamp,
            data,
            merged_from_guids,
            merged_count
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?)
            USING TTL ?';

        $mergedFromGuids = new CassandraSet(Set::bigint());

        $values = [
            new Bigint($notification->getToGuid()),
            new Timeuuid($notification->getUuid()),
            (string) $notification->getType(),
            $notification->getGroupingType(),
            new Bigint($notification->getFromGuid()),
            (string) $notification->getEntityUrn(),
            new Timestamp($notification->getCreatedTimestamp() ?: time(), 0),
            $notification->getReadTimestamp() ? new Timestamp($notification->getReadTimestamp(), 0) : null,
            json_encode($notification->getData()),
            $mergedFromGuids,
            0,
            static::NOTIFICATION_TTL,
        ];

        $query = new Prepared\Custom();
        $query->query($statement, $values);

        try {
            return (bool) $this->cql->request($query);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param Notification $notification
     * @param array $fields
     * @return bool
     */
    public function update(Notification $notification, array $fields = []):bool
    {
        if (!$notification) {
            throw new \Exception("Notification required");
        }

        $statement = "UPDATE notifications_mergeable";
        $values = [];

        /**
         * Set statement
         */
        $set = [];

        foreach ($fields as $field) {
            switch ($field) {
                case "read_timestamp":
                    $set["read_timestamp"] =  new Timestamp($notification->getReadTimestamp(), 0);
                    break;
            }
        }

        $statement .= " SET ";
        foreach ($set as $field => $value) {
            $statement .= "$field = ?,";
            $values[] = $value;
        }
        $statement = rtrim($statement, ',');

        /**
         * Where statement
         */
        $where = [
            "to_guid = ?" => new Bigint($notification->getToGuid()),
            "uuid = ?" => new Timeuuid($notification->getUuid()),
        ];

        $statement .= " WHERE " . implode(' AND ', array_keys($where));
        array_push($values, ...array_values($where));

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->cql->request($prepared);
    }

    /**
     * Deletes a notification from list
     * @param Notification $notification
     * @return bool
     */
    public function delete($notification): bool
    {
        $statement = 'DELETE FROM notifications_mergeable where to_guid = ? and uuid = ?';
        $values = [  new Bigint($notification->getToGuid()), new Timeuuid($notification->getUuid()) ];
        $query = new Prepared\Custom();
        $query->query($statement, $values);

        $success = $this->cql->request($query);

        return (bool) $success;
    }
}
