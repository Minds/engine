<?php
declare(strict_types=1);

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
    public function __construct(
        private ?Client $mysqlClient = null,
        private ?Connection $mysqlQueryBuilder = null
    ) {
        $this->mysqlClient ??= new Client();
        $this->mysqlQueryBuilder ??= new Connection($this->mysqlClient->getConnection(Client::CONNECTION_REPLICA));
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
            'type' => $entityType,
            'tags' => array_map('strtolower', $tags)
        ];

        $query = $this->mysqlQueryBuilder->select()
            ->distinct()
            ->columns(['entity_guid'])
            ->from('minds_default_tag_mapping')
            ->whereWithNamedParameters(
                leftField: 'tag_name',
                operator: Operator::IN,
                parameterName: 'tags',
                totalParameters: count($tags)
            )
            ->where('entity_type', Operator::EQ, new RawExp(':type'))
            ->limit($limit);

        $statement = $query->prepare();
        $this->mysqlClient->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $suggestionData) {
            yield (new Suggestion())
                ->setEntityGuid($suggestionData['entity_guid'])
                ->setEntityType($entityType);
        }
    }
}
