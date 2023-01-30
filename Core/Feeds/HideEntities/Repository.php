<?php
namespace Minds\Core\Feeds\HideEntities;

use Minds\Core\Data\MySQL\Client;

class Repository
{
    public function __construct(
        protected ?Client $mysqlClient = null
    ) {
        $this->mysqlClient ??= new Client();
    }

    /**
     * @param HideEntity $hideEntity
     * @return bool
     */
    public function add(HideEntity $hideEntity): bool
    {
        $statement = "INSERT INTO entities_hidden (user_guid, entity_guid) VALUES (:user_guid,:entity_guid) ON DUPLICATE KEY UPDATE entity_guid=:entity_guid";
        $values = [
            'user_guid' => $hideEntity->getUserGuid(),
            'entity_guid' => $hideEntity->getEntityGuid(),
        ];

        $stmt = $this->mysqlClient->getConnection(Client::CONNECTION_MASTER)->prepare($statement);
        return $stmt->execute($values);
    }

    /**
     * @param string $userGuid
     * @param int $gt - unix timestamp of when to run the count from
     * @return int
     */
    public function count(string $userGuid, int $gt = null): int
    {
        $statement = "SELECT count(*) as c FROM entities_hidden
            WHERE user_guid = :user_guid";
        
        $values = [
            'user_guid' => $userGuid,
        ];

        if ($gt) {
            $statement .=" AND created_at > :gt";
            $values['gt'] = date('c', $gt);
        }

        $stmt = $this->mysqlClient->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);
        $stmt->execute($values);

        return $stmt->fetchAll()[0]['c'];
    }
}
