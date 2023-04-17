<?php

namespace Minds\Core\Suggestions\DefaultTagMapping;

use Iterator;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Suggestions\Suggestion;
use PDO;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Repository for getting defaulted suggestions relevant specified tags.
 * These relations are currently predefined and manual.
 */
class Repository
{
    protected Connection $mysqlQueryBuilder;

    public function __construct(private ?Client $mysqlClient = null)
    {
        $this->mysqlClient ??= new Client();
        $this->mysqlQueryBuilder = new Connection($this->mysqlClient->getConnection(Client::CONNECTION_REPLICA));
    }

    /**
     * Return a list of recommendations by given tags.
     * @param array $entityType - type of entity.
     * @param ?array $tags - tags to get recommendations for - defaults to 'default'.
     * @param array $limit - limit of results to return.
     * @return Iterator yielded results.
     */
    public function getList(
        string $entityType,
        ?array $tags = [],
        int $limit = 12
    ): Iterator {
        if (count($tags) < 1) {
            $tags[] = 'default';
        }

        $values = [
            'type' => $entityType
        ];

        $query = $this->mysqlQueryBuilder->select()
            ->from('minds_default_tag_mapping')
            ->where('tag_name', Operator::IN, $tags) // TODO: Sanitize?
            ->where('entity_type', Operator::EQ, new RawExp(':type'))
            ->limit($limit);

        $statement = $query->prepare();

        $this->mysqlClient->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $suggestionData) {
            yield (new Suggestion())
                ->setEntityGuid($suggestionData['entity_guid'])
                ->setEntityType($suggestionData['entity_type']);
        }
    }
}
