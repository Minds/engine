<?php
/**
 * Minds Session Repository
 */
namespace Minds\Core\Sessions;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared\Custom as Prepared;
use Minds\Common\Repository\Response;
use Cassandra\Varint;
use Cassandra\Timestamp;
use Exception;
use Zend\Diactoros\ServerRequestFactory;
use Minds\Entities\User;

class Repository
{
    /** @var Client $client */
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Get the session from the database
     */
    public function get($user_guid, $id)
    {
        $prepared = new Prepared;
        $prepared->query("SELECT * FROM jwt_sessions
            WHERE user_guid = ?
            AND id = ?", [
            new Varint($user_guid),
            (string) $id,
        ]);

        $result = $this->client->request($prepared);

        if ($result === false) {
            throw new \Exception('Session database exception');
        }

        if (!$result || !$result[0]) {
            return null;
        }

        $session = new Session();
        $session->setId((string) $result[0]['id'])
            ->setUserGuid((int) $result[0]['user_guid']->value())
            ->setExpires((int) $result[0]['expires']->time())
            ->setIp($result[0]['ip'])
            ->setLastActive((int) ($result[0]['last_active'] ? $result[0]['last_active']->time() : 0));

        return $session;
    }

    /**
     * Gets a list of a user's jwt sessions
     *
     * @param userGuid $userGuid
     * @return array
     */
    public function getList(int $userGuid): array
    {
        $prepared = new Prepared();
        $prepared->query("SELECT * FROM jwt_sessions WHERE user_guid = ?", [
            new Varint($userGuid),
        ]);

        $rows = $this->client->request($prepared);

        if (!$rows) {
            return [];
        }

        /** @var Session[] */
        $sessions = [];

        foreach ($rows as $row) {
            $session = new Session();
            $session
                ->setUserGuid((string) $row['user_guid']->value())
                ->setExpires((int) $row['expires'] ? $row['expires']->time() : null)
                ->setId($row['id'])
                ->setIp($row['ip'])
                ->setLastActive((int) $row['last_active'] ? $row['last_active']->time() : 0);

            $sessions[] = $session;
        }

        return $sessions;
    }

    public function add(Session $session)
    {
        $prepared = new Prepared;
        $prepared->query("INSERT INTO jwt_sessions
            (user_guid, id, expires, last_active, ip)
            VALUES
            (?,?,?,?,?)", [
                new Varint($session->getUserGuid()),
                $session->getId(),
                new Timestamp($session->getExpires(), 0),
                new Timestamp($session->getLastActive(), 0),
                $session->getIp(),
            ]);
        $this->client->request($prepared);
    }

    /**
     * @param Session $session
     * @param array $fields
     * @return bool
     */
    public function update(Session $session, array $fields = []):bool
    {
        if (!$session) {
            throw new Exception("Session required");
        }

        $statement = "UPDATE jwt_sessions";
        $values = [];

        /**
         * Set statement
         */
        $set = [];

        foreach ($fields as $field) {
            switch ($field) {
                case "ip":
                    $set["ip"] = $session->getIp();
                    break;
                case "last_active":
                    $set["last_active"] = new Timestamp($session->getLastActive(), 0);
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
            "user_guid = ?" => new Varint($session->getUserGuid()),
            "id = ?" =>  $session->getId(),
        ];

        $statement .= " WHERE " . implode(' AND ', array_keys($where));
        array_push($values, ...array_values($where));

        $prepared = new Prepared();
        $prepared->query($statement, $values);

        return (bool) $this->client->request($prepared);
    }

    /**
     * Destroy session
     * @param Session $session
     * @return bool
     */
    public function delete($session)
    {
        $prepared = new Prepared;

        $prepared->query("DELETE FROM jwt_sessions WHERE user_guid = ? AND id = ?", [
                new Varint($session->getUserGuid()),
                $session->getId(),
            ]);

        return (bool) $this->client->request($prepared);
    }

    /**
     * Destroy all sessions
     * @param User $user
     * @throws Exception
     * @return bool
     */
    public function deleteAll($user)
    {
        if (!$user) {
            throw new Exception("User required");
        }

        $prepared = new Prepared;

        $prepared->query("DELETE FROM jwt_sessions WHERE user_guid = ?", [
                new Varint($user->getGuid()),
            ]);

        return (bool) $this->client->request($prepared);
    }


    /**
     * Return counts
     * @param int $user_guid
     * @return int
     * TODO: This should be in its own class
     */
    public function getCount($user_guid = null)
    {
        $prepared = new Prepared;
        $prepared->query("SELECT COUNT(*) as count FROM jwt_sessions WHERE user_guid = ?", [
            new Varint($user_guid),
        ]);

        $response = $this->client->request($prepared);
        if (!$response) {
            return 0;
        }

        return (int) $response[0]['count'];
    }
}
