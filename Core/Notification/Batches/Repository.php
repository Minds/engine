<?php
/**
 * Notification Batches Repository
 *
 * @author Mark
 */

namespace Minds\Core\Notification\Batches;

use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Prepared\Custom as Prepared;
use Cassandra\Varint;
use Minds\Core\Di\Di;

class Repository
{
    /** @var PDO */
    protected $db;

    /**
     * Repository constructor.
     * @param null $db
     */
    public function __construct($db = null)
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Gets raw rows from the database as an Iterator
     * @param array $opts
     * @return Rows
     */
    public function getRows(array $opts = [])
    {
        $opts = array_merge([
            'batch_id' => null,
            'user_guid' => null,
            'offset' => null,
            'limit' => null,
            'token' => null,
        ], $opts);

        $query = "SELECT * FROM notification_batches";
        $values = [];
        $where = [];

        if ($opts['batch_id']) {
            $where[] = "batch_id = ?";
            $values[] = $opts['batch_id'];
        }

        if ($opts['user_guid']) {
            $where[] = "user_guid = ?";
            $values[] = new Varint($opts['user_guid']);
        }

        if ($where) {
            $query .= " WHERE " . implode(' AND ', $where);
        }

        $preparedOpts = [];

        //if ($opts['limit']) {
        //    $query .= " LIMIT ?";
        //    $values[] = $opts['limit'];
        // }

        if ($opts['offset']) {
            $query .= " OFFSET ?";
            $values[] = $opts['offset'];
        }
        
        $prepared = new Prepared;
        $prepared->query($query, $values);
        $prepared->setOpts([
            'page_size' => (int) $opts['limit'],
            'paging_state_token' => (string) $opts['token'],
        ]);

        return $this->db->request($prepared);
    }

    /**
     * Get subscriptions from the database
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = [])
    {
        $rows = $this->getRows($opts);
        
        if (!$rows || !isset($rows[0])) {
            return new Response();
        }

        $response = new Response();

        foreach ($rows as $row) {
            $subscription = new BatchSubscription();
            $subscription
                ->setUserGuid($row['user_guid'])
                ->setBatchId($row['batch_id']);
            $response[] = $subscription;
        }

        $response->setPagingToken($rows->pagingStateToken());

        return $response;
    }

    /**
     * Gets a Post Subscription entity
     * @param BatchSubscription $subscription
     * @return BatchSubscription|null
     */
    public function get($subscription)
    {
        $batchSubscriptions = $this->getList([
            'batch_id' => $subscription->getBatchId(),
            'user_guid' => $subscription->getUserGuid(),
            'limit' => 1,
        ]);

        if (isset($batchSubscriptions[0])) {
            return $batchSubscriptions[0];
        }

        return null;
    }

    /**
     * Inserts a Post Subscription into the database. If lazy, it'll be
     * added only if the subscription doesn't exist.
     * @param PostSubscription $postSubscription
     * @param bool $ifNotExists
     * @return bool
     */
    public function add(BatchSubscription $subscription)
    {
        $query = "INSERT INTO notification_batches 
                    (user_guid, batch_id) 
                    VALUES 
                    (?, ?)";
        $values = [
            new Varint($subscription->getUserGuid()),
            $subscription->getBatchId(),
        ];

        $prepared = new Prepared;
        $prepared->query($query, $values);

        return $this->db->request($prepared);
        ;
    }

    /**
     * VOID
     */
    public function update(PostSubscription $postSubscription)
    {
    }

    /**
     * Deletes a Post Subscription from the database
     * @param PostSubscription $postSubscription
     * @return bool
     */
    public function delete(BatchSubscription $subscription)
    {
        $query = "DELETE FROM notification_batches 
            WHERE user_guid = ? 
            AND batch_id = ?";

        $values = [
            new Varint($subscription->getUserGuid()),
            $subscription->getBatchId(),
        ];

        $prepared = new Prepared;
        $prepared->query($query, $values);

        return $this->db->request($prepared);
    }
}
