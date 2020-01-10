<?php

namespace Minds\Core\Subscriptions;

use Cassandra;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Entities\User;

class Repository
{
    /** @var Client */
    protected $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Gets a subscription or subscribers list from cassandra.
     *
     * @param array $opts -
     *  guid - required!
     *  type - either 'subscribers' or 'subscriptions'.
     *  limit - limit.
     *  offset - offset.
     * @return Response response object.
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => '',
            'guid' => null,
            'type' => null,
        ], $opts);

        if (!$opts['guid']) {
            throw new \Exception('GUID is required');
        }

        $response = new Response;
        if ($opts['type'] === 'subscribers') {
            $statement = "SELECT * FROM friendsof";
        } else {
            $statement = "SELECT * FROM friends";
        }

        $where = ["key = ?"];
        $values = [$opts['guid']];

        $statement .= " WHERE " . implode(' AND ', $where);

        $cqlOpts = [];
        if ($opts['limit']) {
            $cqlOpts['page_size'] = (int) $opts['limit'];
        }

        if ($opts['offset']) {
            $cqlOpts['paging_state_token'] = base64_decode($opts['offset'], true);
        }
        
        $query = new Prepared\Custom();
        $query->query($statement, $values);
        $query->setOpts($cqlOpts);

        try {
            $rows = $this->client->request($query);

            if (!$rows) {
                return $response;
            }

            foreach ($rows as $row) {
                $user = new User($row['column1']);
                $response[] = $user;
            }

            $response->setPagingToken(base64_encode($rows->pagingStateToken()));
            $response->setLastPage($rows->isLastPage());
        } catch (\Exception $e) {
            // do nothing.
        }

        return $response;
    }

    /**
     * Not implemented
     */
    public function get($uuid)
    {
        // Not implemented
    }

    /**
     * Add a subscription
     *
     * @param Category $category
     * @return string|false
     */
    public function add(Subscription $subscription)
    {
        // Do a batch request for consistency
        $requests = [];
            
        // Write to friends table
        $requests[] = [
            'string' => "INSERT INTO friends (key, column1, value) VALUES (?, ?, ?)",
            'values' => [
                (string) $subscription->getSubscriberGuid(),
                (string) $subscription->getPublisherGuid(),
                (string) time(),
            ],
        ];

        // Write to friends_of table
        $requests[] = [
            'string' => "INSERT INTO friendsof (key, column1, value) VALUES (?, ?, ?)",
            'values' => [
                (string) $subscription->getPublisherGuid(),
                (string) $subscription->getSubscriberGuid(),
                (string) time(),
            ],
        ];
        
        // Send request
        if (!$this->client->batchRequest($requests, Cassandra::BATCH_UNLOGGED)) {
            return false;
        };

        $subscription->setActive(true);

        return $subscription;
    }

    /**
     * Delete a subscripiption
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function delete(Subscription $subscription)
    {
        // Do a batch request for consistency
        $requests = [];

        // Write to friends table
        $requests[] = [
            'string' => "DELETE FROM friends WHERE key=? AND column1=?",
            'values' => [
                (string) $subscription->getSubscriberGuid(),
                (string) $subscription->getPublisherGuid(),
            ],
        ];

        // Write to friends_of table
        $requests[] = [
            'string' => "DELETE FROM friendsof WHERE key=? AND column1=?",
            'values' => [
                (string) $subscription->getPublisherGuid(),
                (string) $subscription->getSubscriberGuid(),
            ],
        ];

        // Send request
        if (!$this->client->batchRequest($requests, Cassandra::BATCH_UNLOGGED)) {
            return false;
        };

        $subscription->setActive(false);

        return $subscription;
    }
}
