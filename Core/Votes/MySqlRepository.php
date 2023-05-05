<?php
namespace Minds\Core\Votes;

use Minds\Core\Data\MySQL\Client;
use Selective\Database\Connection;

class MySqlRepository
{
    protected Connection $mysqlQueryMasterBuilder;
    protected Connection $mysqlQueryReplicaBuilder;

    public function __construct(private ?Client $mysqlClient = null)
    {
        $this->mysqlClient ??= new Client();
        $this->mysqlQueryMasterBuilder = new Connection($this->mysqlClient->getConnection(Client::CONNECTION_MASTER));
        $this->mysqlQueryReplicaBuilder = new Connection($this->mysqlClient->getConnection(Client::CONNECTION_REPLICA));
    }

    /**
     * @param Vote $vote
     * @return bool
     */
    public function add(
        Vote $vote
    ): bool {
        $values = [
            'user_guid' => $vote->getActor()->getGuid(),
            'entity_guid' => $vote->getEntity()->getGuid(),
            'entity_type' => $vote->getEntity()->getType(),
            'direction' => $vote->getDirection(asEnum: true),
        ];

        $query = $this->mysqlQueryMasterBuilder
            ->insert()
            ->into('minds_votes')
            ->set($values)
            ->onDuplicateKeyUpdate([
                'deleted' => (int) false,
                'updated_timestamp' => date('c'),
            ]);

        $stmt = $query->prepare();

        return $stmt->execute();
    }

    /**
     * Removes a vote (performs a soft delete)
     * @param Vote $vote
     * @return bool
     */
    public function delete(
        Vote $vote
    ): bool {
        $values = [
            'user_guid' => $vote->getActor()->getGuid(),
            'entity_guid' => $vote->getEntity()->getGuid(),
            'entity_type' => $vote->getEntity()->getType(),
            'direction' => $vote->getDirection(asEnum: true),
            'deleted' => true,
        ];

        $query = $this->mysqlQueryMasterBuilder
            ->insert($values)
            ->into('minds_votes')
            ->set($values)
            ->onDuplicateKeyUpdate([
                'deleted' => true,
                'updated_timestamp' => date('c'),
            ]);

        return $query->execute();
    }
}
