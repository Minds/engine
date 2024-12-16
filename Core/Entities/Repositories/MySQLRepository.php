<?php
namespace Minds\Core\Entities\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Data\MySQL\MySQLDataTypeEnum;
use Minds\Core\Entities\Enums\EntitySubtypeEnum;
use Minds\Core\Entities\Enums\EntityTypeEnum;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\Activity;
use Minds\Entities\EntityInterface;
use Minds\Entities\Factory;
use Minds\Entities\Group;
use Minds\Entities\Image;
use Minds\Entities\User;
use Minds\Entities\Video;
use PDO;
use PDOStatement;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class MySQLRepository extends AbstractRepository implements EntitiesRepositoryInterface
{
    public function __construct(
        Config $config,
        private ActiveSession $activeSession,
        Client $mysqlClient,
        Logger $logger,
    ) {
        parent::__construct($mysqlClient, $config, $logger);
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
                'has_voted_up' => new RawExp("
                    CASE 
                        WHEN 
                            e.type='activity' AND (
                                SELECT 1 FROM minds_votes
                                WHERE minds_votes.entity_guid = e.guid
                                AND user_guid=:loggedInUser1
                                AND deleted = False
                                AND direction = 1
                            )
                        THEN TRUE 
                        ELSE FALSE
                    END
                "),
                'vote_count' => new RawExp("
                    CASE 
                        WHEN 
                            e.type='activity'
                        THEN (
                                SELECT COUNT(*) FROM minds_votes
                                WHERE minds_votes.entity_guid = e.guid
                                AND deleted = False
                                AND direction = 1
                            ) 
                        ELSE FALSE
                    END
                "),
                'has_voted_down' => new RawExp("
                    CASE 
                        WHEN 
                            e.type='activity' AND (
                                SELECT 1 FROM minds_votes
                                WHERE minds_votes.entity_guid = e.guid
                                AND user_guid=:loggedInUser2
                                AND deleted = False
                                AND direction = 2
                            )
                        THEN TRUE 
                        ELSE FALSE
                    END
                "),
                'friends_count' => new RawExp("
                    CASE 
                        WHEN 
                            e.type='user'
                        THEN (
                                SELECT COUNT(*) FROM friends
                                WHERE friends.user_guid = e.guid
                            ) 
                        ELSE FALSE
                    END
                "),
                'friendsof_count' => new RawExp("
                    CASE 
                        WHEN 
                            e.type='user'
                        THEN (
                                SELECT COUNT(*) FROM friends
                                WHERE friends.friend_guid = e.guid
                            ) 
                        ELSE FALSE
                    END
                "),
                'membership_subscriptions_count' => new RawExp("
                    CASE
                        WHEN
                            e.type = 'user'
                        THEN (              
                            SELECT COUNT(*) FROM minds_site_membership_subscriptions
                            WHERE user_guid=e.guid
                            AND (valid_to IS NULL OR valid_to > NOW())
                        )
                        ELSE
                            0
                    END
                "),
                'rbac_roles.role_ids',
            ])
            ->from(new RawExp('minds_entities as e'))
            ->leftJoin(['u' => 'minds_entities_user'], 'e.guid', Operator::EQ, 'u.guid')
            ->leftJoin(['a' => 'minds_entities_activity'], 'e.guid', Operator::EQ, 'a.guid')
            ->leftJoin(['i' => 'minds_entities_object_image'], 'e.guid', Operator::EQ, 'i.guid')
            ->leftJoin(['v' => 'minds_entities_object_video'], 'e.guid', Operator::EQ, 'v.guid')
            ->leftJoin(['g' => 'minds_entities_group'], 'e.guid', Operator::EQ, 'g.guid')
            ->leftJoin(
                function (SelectQuery $query): void {
                    $query
                        ->from('minds_role_user_assignments')
                        ->columns([
                            'user_guid',
                            'role_ids' => new RawExp('GROUP_CONCAT(role_id)'),
                        ])
                        ->where('tenant_id', Operator::EQ, new RawExp(':rbac_roles_tenantId'))
                        ->groupBy('user_guid')
                        ->alias('rbac_roles');
                },
                'rbac_roles.user_guid',
                Operator::EQ,
                'u.guid'
            );

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
        
        $query->where('e.tenant_id', Operator::EQ, new RawExp(':e_tenantId'));

        if (is_array($guid)) {
            $query->orderBy('e.guid desc');
        }

        $statement = $query->prepare();

        // Active session user WILL be null when this function is called on session init.
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, [
            'guid' => $guid,
            'e_tenantId' => $this->config->get('tenant_id'),
            'rbac_roles_tenantId' => $this->config->get('tenant_id'),
            'loggedInUser1' => $this->activeSession->getUserGuid(),
            'loggedInUser2' => $this->activeSession->getUserGuid(),
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
            ->columns([
                'e.*',
                'u.*',
                'rbac_roles.role_ids',
                'membership_subscriptions_count' => new RawExp("
                    CASE
                        WHEN
                            e.type = 'user'
                        THEN (              
                            SELECT COUNT(*) FROM minds_site_membership_subscriptions
                            WHERE user_guid=e.guid
                            AND (valid_to IS NULL OR valid_to > NOW())
                        )
                        ELSE
                            0
                    END
                "),
            ])
            ->leftJoin(['u' => 'minds_entities_user'], 'e.guid', Operator::EQ, 'u.guid')
            ->leftJoin(
                function (SelectQuery $query): void {
                    $query
                        ->from('minds_role_user_assignments')
                        ->columns([
                            'user_guid',
                            'role_ids' => new RawExp('GROUP_CONCAT(role_id)'),
                        ])
                        ->groupBy('user_guid')
                        ->alias('rbac_roles');
                },
                'rbac_roles.user_guid',
                Operator::EQ,
                'u.guid'
            )
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

        if (!$this->mysqlClientWriter->inTransaction()) {
            $this->beginTransaction();
        }

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

        $set = [... $this->buildSet($data)];

        // Prevent a regression where a user can lose their time_created value
        if (isset($set['time_created'])) {
            $this->logger->error("Attempted to overwrite time_created");
            unset($set['time_created']);
            unset($data['time_created']);
        }

        $query = $this->mysqlClientWriterHandler->update()
            ->table($tableName)
            ->set($set)
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
                    $val = date('c', $val ?: time());
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
                    'email' => MySQLDataTypeEnum::TEXT,
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
                    'password_reset_code' => MySQLDataTypeEnum::TEXT,
                    'merchant' => MySQLDataTypeEnum::JSON,
                    'social_profiles' => MySQLDataTypeEnum::JSON,
                    'tags' => MySQLDataTypeEnum::JSON,
                    'eth_wallet' => MySQLDataTypeEnum::TEXT,
                    'ip' =>  MySQLDataTypeEnum::INT,
                    'language' => MySQLDataTypeEnum::TEXT,
                    'canonical_url' => MySQLDataTypeEnum::TEXT,
                    'source' => MySQLDataTypeEnum::TEXT,
                    'opt_out_analytics' => MySQLDataTypeEnum::BOOL,
                    'pinned_posts' => MySQLDataTypeEnum::JSON,
                ];
            case Activity::class:
                return [
                    ... $entitiesTable,
                    'tenant_id' => MySQLDataTypeEnum::INT,
                    'guid' => MySQLDataTypeEnum::BIGINT,
                    'title' => MySQLDataTypeEnum::TEXT,
                    'message' => MySQLDataTypeEnum::TEXT,
                    'perma_url' => MySQLDataTypeEnum::TEXT,
                    'thumbnail_src' => MySQLDataTypeEnum::TEXT,
                    'blurb ' => MySQLDataTypeEnum::TEXT,
                    'remind_object' => MySQLDataTypeEnum::BLOB,
                    'comments_enabled' => MySQLDataTypeEnum::BOOL,
                    'paywall' => MySQLDataTypeEnum::BOOL,
                    'edited' => MySQLDataTypeEnum::BOOL,
                    'spam' => MySQLDataTypeEnum::BOOL,
                    'deleted' => MySQLDataTypeEnum::BOOL,
                    'pending' => MySQLDataTypeEnum::BOOL,
                    'mature' => MySQLDataTypeEnum::BOOL,
                    'nsfw' => MySQLDataTypeEnum::JSON,
                    'nsfw_lock' => MySQLDataTypeEnum::JSON,
                    'time_created' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_updated' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_sent' => MySQLDataTypeEnum::TIMESTAMP,
                    'license' => MySQLDataTypeEnum::TEXT,
                    'inferred_tags' => MySQLDataTypeEnum::JSON,
                    'tags' => MySQLDataTypeEnum::JSON,
                    'attachments' => MySQLDataTypeEnum::JSON, // temporary denomalization whilst we run in parallel with Cassandra
                    'site_membership' => MySQLDataTypeEnum::BOOL,
                    'paywall_thumbnail' => MySQLDataTypeEnum::JSON,
                    'link_title' => MySQLDataTypeEnum::TEXT,
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
                    'filename' => MySQLDataTypeEnum::TEXT,
                    'blurhash' => MySQLDataTypeEnum::TEXT,
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
                    'banner' => MySQLDataTypeEnum::TIMESTAMP,
                    'conversation_disabled' => MySQLDataTypeEnum::BOOL,
                    //'nsfw' => MySQLDataTypeEnum::JSON,
                    //'nsfw__lock' => MySQLDataTypeEnum::JSON,
                    'time_created' => MySQLDataTypeEnum::TIMESTAMP,
                    'time_updated' => MySQLDataTypeEnum::TIMESTAMP,
                    'pinned_posts' => MySQLDataTypeEnum::JSON,
                ];
        }
        return [];
    }

    private function buildSet(array $data): array
    {
        $columnsMap = [];

        foreach ($data as $k => $v) {
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
                    if (array_key_exists('rbac_roles', $tableMappedRow)) {
                        $row = array_merge($row, $tableMappedRow['rbac_roles']);
                    }

                    if (is_numeric($tableMappedRow['']['membership_subscriptions_count'])) {
                        $row['membership_subscriptions_count'] = (int) $tableMappedRow['']['membership_subscriptions_count'];
                    }

                    $mapToUnix = ['time_created', 'time_updated', 'last_login', 'last_accepted_tos', 'icontime'];

                    $mapToYesNo = ['admin', 'enabled', 'banned'];
   
                    foreach ($mapToYesNo as $k) {
                        if (isset($row[$k])) {
                            $row[$k] = $row[$k] ? 'yes' : 'no';

                            if ($k === 'admin' && $row[$k] === 'no') {
                                $userRoles = explode(',', $row['role_ids'] ?? '');
                                $isAdmin = (bool) count(array_intersect($userRoles, [RolesEnum::ADMIN->value, RolesEnum::OWNER->value]));
                                $row['admin'] = $isAdmin ? 'yes' : 'no';
                            }
                        }
                    }

                    break;
                case EntityTypeEnum::ACTIVITY:
                    $row = [...$row, ...$tableMappedRow['a']];

                    // Hack for votes
                    if ($tableMappedRow['']['has_voted_up'] ?? false) {
                        $row['thumbs:up:user_guids'] = [(string) $this->activeSession->getUserGuid()];
                    }
                    if ($tableMappedRow['']['has_voted_down'] ?? false) {
                        $row['thumbs:down:user_guids'] = [(string) $this->activeSession->getUserGuid()];
                    }

                    // An ugly hack for passing the comment count to the activity post
                    if ($tableMappedRow['']['vote_count'] ?? false) {
                        $row['thumbs:up:count'] = (int) $tableMappedRow['']['vote_count'];
                    }

                    $mapToUnix = ['time_created', 'time_updated', ];
                    
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

                    $mapToUnix = ['time_created', 'time_updated', 'icon_time', 'banner'];

                    break;
                case EntityTypeEnum::AUDIO:
                    continue 2; // Audio is handled from Core/Media/Audio/AudioRepository.php
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
