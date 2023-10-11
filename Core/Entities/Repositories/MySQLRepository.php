<?php
namespace Minds\Core\Entities\Repositories;

use Minds\Common\Access;
use Minds\Core\Config\Config;
use Minds\Core\Data\Call;
use Minds\Core\Data\Cassandra\Thrift\Indexes;
use Minds\Core\Data\lookup;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Data\MySQL\MySQLDataTypeEnum;
use Minds\Core\Entities\Enums\EntitySubtypeEnum;
use Minds\Core\Entities\Enums\EntityTypeEnum;
use Minds\Core\Log\Logger;
use Minds\Entities\Video;
use Minds\Entities\Activity;
use Minds\Entities\Factory;
use Minds\Entities\EntityInterface;
use Minds\Entities\Group;
use Minds\Entities\Image;
use Minds\Entities\User;
use PDO;
use PDOStatement;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class MySQLRepository extends AbstractRepository implements EntitiesRepositoryInterface
{
    public function __construct(
        private Config $config,
        Client $mysqlClient,
        Logger $logger,
    ) {
        parent::__construct($mysqlClient, $logger);
    }

    /**
     * @inheritDoc
     */
    public function loadFromGuid(int|array $guid): mixed
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'e.*',
                'u.*',
                'a.*',
                'i.*',
                'v.*',
                'g.*',
            ])
            ->from(new RawExp('minds_entities as e'))
            ->leftJoin(['u' => 'minds_entities_user'], 'e.guid', Operator::EQ, 'u.guid')
            ->leftJoin(['a' => 'minds_entities_activity'], 'e.guid', Operator::EQ, 'a.guid')
            ->leftJoin(['i' => 'minds_entities_object_image'], 'e.guid', Operator::EQ, 'i.guid')
            ->leftJoin(['v' => 'minds_entities_object_video'], 'e.guid', Operator::EQ, 'v.guid')
            ->leftJoin(['g' => 'minds_entities_group'], 'e.guid', Operator::EQ, 'g.guid');

        if (is_array($guid)) {
            //$query->where('e.guid', Operator::IN, new RawExp(':guid'));
            $query->whereWithNamedParameters(
                leftField: 'e.guid',
                operator: Operator::IN,
                parameterName: 'guid',
                totalParameters: count($guid)
            );
        } else {
            $query->where('e.guid', Operator::EQ, new RawExp(':guid'));
        }
        
        $query->where('e.tenant_id', Operator::EQ, $this->config->get('tenant_id'));

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'guid' => $guid
        ]);

        $statement->execute();

        $entities = $this->buildEntities($statement);

        return is_array($guid) ? $entities : ($entities[0] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function loadFromIndex(string $index, string $value): ?EntityInterface
    {
        $query = $this->mysqlClientReaderHandler->select()
             ->from(new RawExp('minds_entities as e'))
             ->leftJoin(['u' => 'minds_entities_user'], 'e.guid', Operator::EQ, 'u.guid')
             ->where($index, Operator::EQ, new RawExp(':val'))
             ->where('e.tenant_id', Operator::EQ, $this->config->get('tenant_id'));

        $statement = $query->prepare();

        $statement->execute([
            'val' => strtolower($value)
        ]);

        $entities = $this->buildEntities($statement);

        return $entities[0] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function create(EntityInterface $entity): bool
    {
        $data = $this->buildData($entity);

        $this->beginTransaction();

        // Add the entity to the base table

        $set = [
            'tenant_id' => $this->config->get('tenant_id'),
            'guid' => new RawExp(':guid'),
            'owner_guid' => new RawExp(':owner_guid'),
            'type' => $entity->getType(),
            'subtype' => $entity->getSubtype(),
            'access_id' => (int) $entity->getAccessId(),
        ];

        if (method_exists($entity, 'getContainerGuid')) {
            $set['container_guid'] = (int) $entity->getContainerGuid();
        }

        $query = $this->mysqlClientWriterHandler->insert()
            ->into('minds_entities')
            ->set($set);

        $statement = $query->prepare();

        $values = [
            'guid' => $entity->getGuid(),
            'owner_guid' => $entity->getOwnerGuid(),
        ];

        $success = $statement->execute($values);

        if (!$success) {
            $this->rollbackTransaction();
            return false;
        }

        // Add the entity to its relevant table

        unset($data['type']);
        unset($data['subtype']);
        unset($data['owner_guid']);
        unset($data['container_guid']);
        unset($data['access_id']);

        $tableName = $this->buildTableName($entity);

        $query = $this->mysqlClientWriterHandler->insert()
            ->into($tableName)
            ->set([
                'tenant_id' => $this->config->get('tenant_id'),
                'guid' => new RawExp(':guid'),
                ... $this->buildSet($data),
            ]);

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'guid' => $entity->getGuid(),
            ... $data,
        ]);

        $success = $statement->execute();

        if ($success) {
            $this->commitTransaction();
        } else {
            $this->rollbackTransaction();
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function update(EntityInterface $entity, array $columns = []): bool
    {
        $data = $this->buildData($entity, $columns);
    
        $this->beginTransaction();

        // If the access id has changed, update the base entities table

        if (isset($data['access_id']) || isset($data['container_guid'])) {
            $query = $this->mysqlClientWriterHandler->update()
                ->table('minds_entities')
                ->set([
                    'access_id' => (int) $entity->getAccessId(),
                ])
                ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id'))
                ->where('guid', Operator::EQ, new RawExp(':guid'));

            $statement = $query->prepare();

            $success = $statement->execute([
                'guid' => $entity->getGuid(),
            ]);

            if (!$success) {
                $this->rollbackTransaction();
                return false;
            }

            unset($data['container_guid']);
            unset($data['access_id']);
        }

        // Update the relevant table

        $tableName = $this->buildTableName($entity);

        $query = $this->mysqlClientWriterHandler->update()
            ->table($tableName)
            ->set([
                ... $this->buildSet($data),
            ])
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id'))
            ->where('guid', Operator::EQ, new RawExp(':guid'));

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'guid' => $entity->getGuid(),
            ... $data,
        ]);

        $success = $statement->execute();

        if (!$success) {
            $this->rollbackTransaction();
            return false;
        }

        $this->commitTransaction();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(EntityInterface $entity): bool
    {
        $this->beginTransaction();

        // Delete from normalised table

        $tableName = $this->buildTableName($entity);
       
        $query = $this->mysqlClientWriterHandler->delete()
            ->from($tableName)
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id'))
            ->where('guid', Operator::EQ, new RawExp(':guid'));

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'guid' => $entity->getGuid(),
        ]);

        $success = $statement->execute();

        if (!$success) {
            $this->rollbackTransaction();
            return false;
        }

        // Now delete from the base table

        $query = $this->mysqlClientWriterHandler->delete()
            ->from('minds_entities')
            ->where('tenant_id', Operator::EQ, $this->config->get('tenant_id'))
            ->where('guid', Operator::EQ, new RawExp(':guid'));

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'guid' => $entity->getGuid(),
        ]);

        $success = $statement->execute();

        if (!$success) {
            $this->rollbackTransaction();
            return false;
        }

        $this->commitTransaction();
    
        return true;
    }

    /**
     * Helper function to map an entity to its table name
     */
    private function buildTableName(EntityInterface $entity): string
    {
        return match(get_class($entity)) {
            Activity::class => 'minds_entities_activity',
            Image::class => 'minds_entities_object_image',
            Video::class => 'minds_entities_object_video',
            Group::class => 'minds_entities_group',
            User::class => 'minds_entities_user',
            default => throw new \Exception("Unsupported entity"),
        };
    }

    /**
     * Converts an entity to array form data
     */
    private function buildData(EntityInterface $entity, array $columns = []): array
    {
        switch (get_class($entity)) {
            case User::class:
            case Activity::class:
            case Image::class:
            case Video::class:
            case Group::class:
                /**  @var User|Activity|Image|Video|Group */
                $entity = $entity;
                $rawData = $entity->toArray();
                break;
            default:
                throw new \Exception('Can not save this entity type');
        }

        if ($columns) {
            $rawData = array_filter($rawData, function ($k) use ($columns) {
                return in_array($k, $columns, false);
            }, ARRAY_FILTER_USE_KEY);
        }

        // Always save the timestamp for when last updated
        $rawData['time_updated'] = date('c', time());

        $data = [];

        foreach ($this->getColumns($entity) as $key => $dataType) {
            if (isset($rawData[$key])) {
                $val = $rawData[$key];

                if ($dataType === MySQLDataTypeEnum::TIMESTAMP && is_numeric($val)) {
                    $val = date('c', time());
                }

                if ($dataType === MySQLDataTypeEnum::BOOL && is_numeric($val)) {
                    $val = (bool) $val;
                }

                if ($dataType === MySQLDataTypeEnum::BOOL && in_array($val, ['yes', 'no'], true)) {
                    $val = $val === 'yes';
                }

                $data[$key] = $val;
            }
        }

        return $data;
    }

    /**
     * Returns a list of supported columns for each entity
     */
    private function getColumns(EntityInterface $entity)
    {
        $entitiesTable = [
            'access_id' => MySQLDataTypeEnum::BIGINT,
            'container_guid' => MySQLDataTypeEnum::BIGINT,
        ];
        switch (get_class($entity)) {
            case User::class:
                return [
                    ... $entitiesTable,
                    'tenant_id' => MySQLDataTypeEnum::INT,
                    'guid' => MySQLDataTypeEnum::BIGINT,
                    'username' => MySQLDataTypeEnum::TEXT,
                    'name' => MySQLDataTypeEnum::TEXT,
                    'briefdescription' => MySQLDataTypeEnum::TEXT,
                    'password' => MySQLDataTypeEnum::TEXT,
                    'liquidity_spot_opt_out' => MySQLDataTypeEnum::BOOL,
                    'disabled_boost' => MySQLDataTypeEnum::BOOL,
                    'spam' => MySQLDataTypeEnum::BOOL,
                    'deleted' => MySQLDataTypeEnum::BOOL,
                    'admin' => MySQLDataTypeEnum::BOOL,
                    'enabled' => MySQLDataTypeEnum::BOOL,
                    'banned' => MySQLDataTypeEnum::BOOL,
                    'mature' => MySQLDataTypeEnum::BOOL,
                    'canary' => MySQLDataTypeEnum::BOOL,
                    'verified' => MySQLDataTypeEnum::BOOL,
                    'founder' => MySQLDataTypeEnum::BOOL,
                    //'nsfw' => MySQLDataTypeEnum::JSON,
                    //'nsfw_lock' => MySQLDataTypeEnum::JSON,
                    'time_created' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_updated' => MySQLDataTypeEnum::TIMESTAMP,
                    'last_accepted_tos' => MySQLDataTypeEnum::TIMESTAMP,
                    'icontime' => MySQLDataTypeEnum::TIMESTAMP,
                    'last_login' => MySQLDataTypeEnum::TIMESTAMP,
                    'email_confirmation_token' => MySQLDataTypeEnum::TEXT,
                    'email_confirmed_at' => MySQLDataTypeEnum::TIMESTAMP,
                    'merchant' => MySQLDataTypeEnum::JSON,
                    'social_profiles' => MySQLDataTypeEnum::JSON,
                    'tags' => MySQLDataTypeEnum::JSON,
                    'eth_wallet' => MySQLDataTypeEnum::TEXT,
                    'ip' =>  MySQLDataTypeEnum::INT,
                    'canonical_url' => MySQLDataTypeEnum::TEXT,
                    'source' => MySQLDataTypeEnum::TEXT,
                ];
            case Activity::class:
                return [
                    ... $entitiesTable,
                    'tenant_id' => MySQLDataTypeEnum::INT,
                    'guid' => MySQLDataTypeEnum::BIGINT,
                    'title' => MySQLDataTypeEnum::TEXT,
                    'message' => MySQLDataTypeEnum::TEXT,
                    'remind_object' => MySQLDataTypeEnum::BLOB,
                    'comments_enabled' => MySQLDataTypeEnum::BOOL,
                    'paywall' => MySQLDataTypeEnum::BOOL,
                    'edited' => MySQLDataTypeEnum::BOOL,
                    'spam' => MySQLDataTypeEnum::BOOL,
                    'deleted' => MySQLDataTypeEnum::BOOL,
                    'pending' => MySQLDataTypeEnum::BOOL,
                    'mature' => MySQLDataTypeEnum::BOOL,
                    //'nsfw' => MySQLDataTypeEnum::JSON,
                    //'nsfw_lock' => MySQLDataTypeEnum::JSON,
                    'time_created' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_updated' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_sent' => MySQLDataTypeEnum::TIMESTAMP,
                    'license' => MySQLDataTypeEnum::TEXT,
                    'inferred_tags' => MySQLDataTypeEnum::JSON,
                    'tags' => MySQLDataTypeEnum::JSON,
                    'attachments' => MySQLDataTypeEnum::JSON, // temporary denomalization whilst we run in parallel with Cassandra
                    'canonical_url' => MySQLDataTypeEnum::TEXT,
                    'source' => MySQLDataTypeEnum::TEXT,
                ];
            case Image::class:
                return [
                    ... $entitiesTable,
                    'tenant_id' => MySQLDataTypeEnum::INT,
                    'guid' => MySQLDataTypeEnum::BIGINT,
                    'deleted' => MySQLDataTypeEnum::BOOL,
                    'width' => MySQLDataTypeEnum::INT,
                    'height' => MySQLDataTypeEnum::INT,
                    'time_created' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_updated' => MySQLDataTypeEnum::TIMESTAMP,
                    'auto_caption' => MySQLDataTypeEnum::TEXT,
                ];
            case Video::class:
                return [
                    ... $entitiesTable,
                    'tenant_id' => MySQLDataTypeEnum::INT,
                    'guid' => MySQLDataTypeEnum::BIGINT,
                    'deleted' => MySQLDataTypeEnum::BOOL,
                    'cloudflare_id' => MySQLDataTypeEnum::TEXT,
                    'width' => MySQLDataTypeEnum::INT,
                    'height' => MySQLDataTypeEnum::INT,
                    'time_created' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_updated' => MySQLDataTypeEnum::TIMESTAMP,
                    'auto_caption' => MySQLDataTypeEnum::TEXT,
                ];
            case Group::class:
                return [
                    'tenant_id' => MySQLDataTypeEnum::INT,
                    'guid' => MySQLDataTypeEnum::BIGINT,
                    'deleted' => MySQLDataTypeEnum::BOOL,
                    'name' => MySQLDataTypeEnum::TEXT,
                    'brief_description' => MySQLDataTypeEnum::TEXT,
                    'membership' => MySQLDataTypeEnum::INT,
                    'moderated' => MySQLDataTypeEnum::BOOL,
                    'icon_time' => MySQLDataTypeEnum::TIMESTAMP,
                    'tags' => MySQLDataTypeEnum::JSON,
                    'show_boost' => MySQLDataTypeEnum::BOOL,
                    'banner' => MySQLDataTypeEnum::BOOL,
                    //'nsfw' => MySQLDataTypeEnum::JSON,
                    //'nsfw__lock' => MySQLDataTypeEnum::JSON,
                    'time_created' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_updated' => MySQLDataTypeEnum::TIMESTAMP,
                ];
        }
        return [];
    }

    private function buildSet(array $data): array
    {
        $columnsMap = [];

        foreach ($data as $k => $v) {
            
            if ($k === 'ip') {
                $columnsMap[$k] = new RawExp("INET_ATON(:$k)");
                continue;
            }

            $columnsMap[$k] = new RawExp(':' . $k);
        }

        return $columnsMap;
    }

    /**
     * Builds the entity class from a pdo statement response
     * @return EntityInterface[]|null
     */
    private function buildEntities(PDOStatement $pdoStatement): ?array
    {
        $entities = [];

        $rows = $pdoStatement->fetchAll(PDO::FETCH_NUM);

        if (!$pdoStatement->rowCount()) {
            return null;
        }

        foreach ($rows as $row) {

            /**
             * Map the row to an array
             */
            $tableMappedRow = [];

            foreach ($row as $col => $val) {
                $colMeta = $pdoStatement->getColumnMeta($col);

                $colName = $colMeta['name'];
                $tableName = $colMeta['table'];

                if (!isset($tableMappedRow[$tableName])) {
                    $tableMappedRow[$tableName] = [];
                }

                if ($val === null) {
                    continue;
                }

                // MySQL stores boolean as a TINY. Recast these to PHP bool
                if (isset($colMeta['native_type']) && $colMeta['native_type'] === 'TINY') {
                    $val = (bool) $val;
                }

                $tableMappedRow[$tableName][$colName] = $val;
            }

            // We always want the base entities values
            $row = $tableMappedRow['e'];

            $mapToUnix = [];

            /**
             * Build out the row for our specific entity type
             */
            switch (EntityTypeEnum::tryFrom($row['type'])) {
                case EntityTypeEnum::USER:
                    $row = [...$row, ...$tableMappedRow['u']];

                    $mapToUnix = ['time_created', 'time_updated', 'last_login', 'last_accepted_tos'];

                    $mapToYesNo = ['admin', 'enabled', 'banned'];
   
                    foreach ($mapToYesNo as $k) {
                        if (isset($row[$k])) {
                            $row[$k] = $row[$k] ? 'yes' : 'no';
                        }
                    }

                    break;
                case EntityTypeEnum::ACTIVITY:
                    $row = [...$row, ...$tableMappedRow['a']];

                    $mapToUnix = ['time_created', 'time_updated', 'last_login', 'last_accepted_tos'];
                    
                    break;
                case EntityTypeEnum::OBJECT:
                    switch (EntitySubtypeEnum::tryFrom($row['subtype'])) {
                        case EntitySubtypeEnum::IMAGE:
                            $row = [...$row, ...$tableMappedRow['i']];
                            break;
                        case EntitySubtypeEnum::VIDEO:
                            $row = [...$row, ...$tableMappedRow['v']];
                            break;
                    }
                    break;
                case EntityTypeEnum::GROUP:
                    $row = [...$row, ...$tableMappedRow['g']];

                    $mapToUnix = ['time_created', 'time_updated', 'last_login', 'last_accepted_tos'];

                    break;

            }

            // Remap fields
            foreach ($row as $k => $v) {
                if (in_array($k, $mapToUnix, true)) {
                    $row[$k] = strtotime($v);
                }
            }

            $entities[] = Factory::build($row);

        }

        return $entities;
    }

}
