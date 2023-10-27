<?php
namespace Minds\Core\Votes;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Votes\Enums\VoteEnum;
use Minds\Exceptions\ServerErrorException;
use PDO;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class MySqlRepository
{
    protected Connection $mysqlQueryMasterBuilder;
    protected Connection $mysqlQueryReplicaBuilder;

    public function __construct(
        private readonly EntitiesBuilder $entitiesBuilder,
        private readonly Config $config,
        private ?Client $mysqlClient = null
    ) {
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
            'tenant_id' => $this->config->get('tenant_id'),
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

        $result = $stmt->execute();

        return $result;
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
            'tenant_id' => $this->config->get('tenant_id'),
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

    /**
     * @param int $userGuid
     * @param VoteEnum $direction
     * @return iterable<Vote>
     * @throws ServerErrorException
     */
    public function getList(
        int $userGuid = null,
        int $entityGuid = null,
        VoteEnum $direction = VoteEnum::UP
    ): iterable {

        $values = [
            'direction' => 1,
        ];

        $query = $this->mysqlQueryReplicaBuilder
            ->select()
            ->from('minds_votes')
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id'))
            ->where('direction', Operator::EQ, new RawExp(':direction'))
            ->where('deleted', Operator::EQ, false)
            ->orderBy('updated_timestamp DESC');

        if ($userGuid) {
            $query = $query->where('user_guid', Operator::EQ, new RawExp(':user_guid'));
            $values['user_guid'] = $userGuid;
        }

        if ($entityGuid) {
            $query = $query->where('entity_guid', Operator::EQ, new RawExp(':entity_guid'));
            $values['entity_guid'] = $entityGuid;
        }

        $stmt = $query->prepare();

        $this->mysqlClient->bindValuesToPreparedStatement($stmt, $values);

        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $urnIfComment = "urn:comment:{$row['entity_guid']}";
            $entity = match ($row['entity_type']) {
                'comment' => $this->entitiesBuilder->getByUrn($urnIfComment),
                default => $this->entitiesBuilder->single($row['entity_guid'])
            };
            yield (new Vote())
                ->setDirection($direction->value)
                ->setActor($this->entitiesBuilder->single($row['user_guid']))
                ->setEntity($entity);
        }
    }
}
