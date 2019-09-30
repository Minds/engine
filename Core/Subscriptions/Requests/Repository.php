<?php
/**
 * Subscriptions Requests Repository
 */
namespace Minds\Core\Subscriptions\Requests;

use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Common\Repository\Response;
use Minds\Common\Urn;
use Cassandra\Timestamp;
use Cassandra\Bigint;

class Repository
{
    /** @var Client */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }
 
    /**
     * Return a list of subscription requests
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'publisher_guid' => null,
            'show_declined' => false,
            'limit' => 5000,
            'token' => '',
        ], $opts);

        if (!$opts['publisher_guid']) {
            throw new \Exception('publisher_guid not set');
        }

        $prepared = new Prepared\Custom();
        $prepared->query(
            "SELECT * FROM subscription_requests
            WHERE publisher_guid = ?",
            [
                new Bigint($opts['publisher_guid']),
            ]
        );
        $result = $this->db->request($prepared);
        $response = new Response;

        foreach ($result as $row) {
            $subscriptionRequest = new SubscriptionRequest();
            $subscriptionRequest
                ->setPublisherGuid((string) $row['publisher_guid'])
                ->setSubscriberGuid((string) $row['subscriber_guid'])
                ->setTimestampMs((int) $row['timestamp']->time())
                ->setDeclined((bool) $row['declined']);

            if ($subscriptionRequest->isDeclined() && !$opts['show_declined']) {
                continue;
            }
    
            $response[] = $subscriptionRequest;
        }

        return $response;
    }

    /**
     * Return a single subscription request from a urn
     * @param string $urn
     * @return SubscriptionRequest
     */
    public function get(string $urn): ?SubscriptionRequest
    {
        $urn = new Urn($urn);
        list($publisherGuid, $subscriberGuid) = explode('-', $urn->getNss());
 
        $prepared = new Prepared\Custom();
        $prepared->query(
            "SELECT * FROM subscription_requests
            WHERE publisher_guid = ?
            AND subscriber_guid = ?",
            [
                new Bigint($publisherGuid),
                new Bigint($subscriberGuid),
            ]
        );
        $result = $this->db->request($prepared);

        if (!$result) {
            return null;
        }

        $row = $result[0];

        if (!$row) {
            return null;
        }

        $subscriptionRequest = new SubscriptionRequest();
        $subscriptionRequest
                ->setPublisherGuid((string) $row['publisher_guid'])
                ->setSubscriberGuid((string) $row['subscriber_guid'])
                ->setTimestampMs((int) $row['timestamp']->time())
                ->setDeclined((bool) $row['declined']);

        return $subscriptionRequest;
    }

    /**
     * Add a subscription request
     * @param SubscriptionRequest $subscriptionRequest
     * @return bool
     */
    public function add(SubscriptionRequest $subscriptionRequest): bool
    {
        $statement = "INSERT INTO subscription_requests
            (publisher_guid, subscriber_guid, timestamp)
            VALUES
            (?, ?, ?)
            IF NOT EXISTS";
        $values = [
            new Bigint($subscriptionRequest->getPublisherGuid()),
            new Bigint($subscriptionRequest->getSubscriberGuid()),
            new Timestamp($subscriptionRequest->getTimestampMs() ?? round(microtime(true) * 1000)),
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }

    /**
     * Update a subscription request
     * @param SubscriptionRequest $subscriptionRequest
     * @param array $fields
     * @return bool
     */
    public function update(SubscriptionRequest $subscriptionRequest, array $field = []): bool
    {
        $statement = "UPDATE subscription_requests
            SET declined = ?
            WHERE publisher_guid = ?
            AND subscriber_guid = ?";
        $values = [
            $subscriptionRequest->isDeclined(),
            new Bigint($subscriptionRequest->getPublisherGuid()),
            new Bigint($subscriptionRequest->getSubscriberGuid()),
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }

    /**
     * Delete a subscription request
     * @param SubscriptionRequest $subscriptionRequest
     * @return bool
     */
    public function delete(SubscriptionRequest $subscriptionRequest): bool
    {
        $statement = "DELETE FROM subscription_requests
            WHERE publisher_guid = ?
            AND subscriber_guid = ?";
        $values = [
            new Bigint($subscriptionRequest->getPublisherGuid()),
            new Bigint($subscriptionRequest->getSubscriberGuid()),
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->db->request($prepared);
    }
}
