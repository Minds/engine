<?php
/**
 * Wire Repository.
 */

namespace Minds\Core\Wire;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Cassandra;
use Cassandra\Varint;
use Cassandra\Timestamp;
use Minds\Common\Urn;

class Repository
{
    private $db;
    private $config;
    private $entitiesBuilder;

    public function __construct($db = null, $config = null, $entitiesBuilder = null)
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
        $this->config = $config ?: Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Inserts wires to the database.
     *
     * @param array[Wire] $wires
     */
    public function add($wires)
    {
        if (!is_array($wires)) {
            $wires = [$wires];
        }

        $requests = [];
        $template = 'INSERT INTO wire
            (receiver_guid, sender_guid, method, timestamp, entity_guid, wire_guid, wei, recurring, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

        foreach ($wires as $wire) {
            $requests[] = [
                'string' => $template,
                'values' => [
                    new Varint($wire->getReceiver()->guid),
                    new Varint($wire->getSender()->guid),
                    $wire->getMethod(),
                    new Timestamp($wire->getTimestamp(), 0),
                    new Varint($wire->getEntity()->guid ?: $wire->getReceiver()->guid),
                    new Varint($wire->getGuid()),
                    new Cassandra\Varint($wire->getAmount()),
                    (bool) $wire->isRecurring(),
                    'success',
                ],
            ];
        }

        return $this->db->batchRequest($requests, Cassandra::BATCH_UNLOGGED);
    }

    public function getList($options = [])
    {
        $options = array_merge([
            'limit' => 12,
            'offset' => '',
            'timestamp' => [
                'gte' => null,
                'lte' => null,
            ],
            'entity_guid' => null,
            'sender_guid' => null,
            'receiver_guid' => null,
            'allowFiltering' => false,
            'method' => 'tokens',
        ], $options);

        $table = 'wire';
        $where = ['method = ?'];
        $values = [$options['method']];
        $orderBy = ' ORDER BY method DESC, timestamp DESC';

        if ($options['receiver_guid']) {
            $where[] = 'receiver_guid = ?';
            $values[] = new Varint($options['receiver_guid']);
        }

        if ($options['sender_guid']) {
            $table = 'wire_by_sender';
            $where[] = 'sender_guid = ?';
            $values[] = new Varint($options['sender_guid']);
        }

        if ($options['entity_guid']) {
            $table = 'wire_by_entity';
            $where[] = 'entity_guid = ?';
            $values[] = new Varint($options['entity_guid']);
            $orderBy = ' ORDER BY method DESC, sender_guid DESC, timestamp DESC';
        }

        if ($options['timestamp']['gte']) {
            $where[] = 'timestamp >= ?';
            $values[] = new Timestamp($options['timestamp']['gte'], 0);
        }

        if ($options['timestamp']['lte']) {
            $where[] = 'timestamp <= ?';
            $values[] = new Timestamp($options['timestamp']['lte'], 0);
        }

        $cql = "SELECT * from $table";

        if ($where) {
            $cql .= ' WHERE '.implode(' AND ', $where);
        }

        $cql .= $orderBy;

        if ($options['allowFiltering']) {
            $cql .= ' ALLOW FILTERING';
        }

        $query = new Custom();
        $query->query($cql, $values);
        $query->setOpts([
            'page_size' => (int) $options['limit'],
            'paging_state_token' => base64_decode($options['offset'], true),
        ]);

        $wires = [];

        try {
            $rows = $this->db->request($query);
        } catch (\Exception $e) {
            error_log($e->getMessage());

            return [];
        }

        if (!$rows) {
            return [];
        }

        foreach ($rows as $row) {
            $entity = $this->entitiesBuilder->single((string) $row['entity_guid']);
            if (!$entity) {
                continue; // Entity deleted. TODO: allow passing entityGuid to Wire so urn can be constructed
            }

            $wire = new Wire();
            $wire->setGuid($row['wire_guid']->value())
                ->setSender($this->entitiesBuilder->single((string) $row['sender_guid']))
                ->setReceiver($this->entitiesBuilder->single((string) $row['receiver_guid']))
                ->setTimestamp($row['timestamp']->time())
                ->setEntity($entity)
                ->setRecurring($row['recurring'])
                ->setMethod($row['method'])
                ->setAmount((string) Core\Util\BigNumber::_($row['amount'] ?: 0)->add((string) $row['wei'] ?: 0));
            $wires[] = $wire;
        }

        return [
            'wires' => $wires,
            'token' => $rows->pagingStateToken(),
        ];
    }

    /**
     * @param string $urn
     * @return Wire
     */
    public function get(string $urn): ?Wire
    {
        $urn = new Urn($urn);
        list($receiverGuid, $method, $timestamp, $entityGuid, $guid) = explode('-', $urn->getNss());

        $list = $this->getList([
            'receiver_guid' => $receiverGuid,
            'method' => $method,
            'timestamp' => [
                'gte' => $timestamp,
                'lte' => $timestamp
            ],
        ]);

        foreach ($list['wires'] as $wire) {
            if ((string) $wire->getGuid() === (string) $guid) {
                return $wire;
            }
        }

        return null;
    }

    public function update($key, $guids)
    {
        // TODO: Implement update() method.
    }

    public function delete($entity)
    {
        // TODO: Implement delete() method.
    }
}
